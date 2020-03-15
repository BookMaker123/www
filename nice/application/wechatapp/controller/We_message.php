<?php

namespace app\wechatapp\controller;

use think\Db;
use EasyWeChat\Factory;
/*
* 处理接收到的除事件外的消息
* 方法名全部小写
*/

class We_message extends AutoReply
{
    protected $message;
    protected $appwx;
    protected $weUser;
    protected $user_id;//用户ID
    protected $server_id;//服务id（选择哪个服务）
    protected $obj_server;//添加服务类对象
    protected $login_info;//登陆状态
    protected $wx_register_text;//微信注册信息

    public function __construct($message, $appwx, $weUser)
    {
        session_start();
        $this->message = $message;
        $this->appwx = $appwx;
        $this->weUser = $weUser;
        if($_SESSION['user']['openid'] == ''){
            $wx_login = new \app\vip\controller\Login();
            $pid = $_GET['pid'];
            $this->login_info = $wx_login->weixin_login($this->weUser['openid'],$pid);
            $this->user_id = $_SESSION['think']['user']['id'];
            //获取服务id
            $this->server_id = $this->server_id(cache('EventKey'.$this->user_id));
            if($this->login_info['is_reg'] == 1){//注册
                $this->wx_register_text = '已自动为你生成爱果管理系统账号'."\n".'账号：'.$_SESSION['think']['user']['username'] ."\n". '密码：888888'."\n\n". '可登陆<a href="https://www.aiguovip.com">www.aiguovip.com</a>修改密码';
            }
        }

    }

    /** 收到文字消息的处理方法*/
    public function text()
    {
        if($this->message['Content'] == 'ccc'){
            $config = Config('wechat.');
            $wxapp = Factory::officialAccount($config);
            $result = $wxapp->qrcode->temporary(intval($this->user_id), 6 * 24 * 3600);//传入用户的uid
            $url = $wxapp->qrcode->url($result['ticket']);
            return "<img src=\"$url\">";
        }
        if($this->server_id == '-99'){
            return "服务异常，请先选择要查询的服务";
        }
        $msg = $this->message['Content'];
        $result =  add_order([$this->server_id],$this->message['Content'],$is_wx=1,'微信公众查询' . date('Ymd'),null,$order_type=1);//添加服务  //$this->server_id
        if($result['code'] == 1){//成功
            return "已提交以下数据,请等候几秒"."\n" . $msg . $result['message'] . "\n\n" .$this->wx_register_text;
        }else{
            return '添加错误：' . $result['message'] . "\n\n" .$this->wx_register_text;
        }
    }

    /** 收到图片消息的处理方法 */
    public function image()
    {
        if($this->server_id == '-99'){
            return "服务异常，请先选择要查询的服务";
        }
        $img = new ImgIdentify();
        $res = $img->bdy_img_identify_url($this->message['PicUrl']);
        return json_encode($res);
        //如果sn和imei都为空
        $sn_or_imei = empty($res['sn'][0][0]) ? $res['imei'][0][0] : $res['sn'][0][0];
        return $sn_or_imei;
        if(empty($sn_or_imei)){
            return '没有识别到sn或imei，请重新上传'."\n".$this->wx_register_text;
        }

        $result=  add_order([$this->server_id],$sn_or_imei,$is_wx=1,'微信公众查询' . date('Ymd'),null,$order_type=1);//添加服务  //$this->server_id
        if($result['code'] == 1){//成功
            return "识别到$sn_or_imei"."进行查询\n"  . $result['message'] . "\n\n" .$this->wx_register_text;
        }else{
            return '添加错误：' . $result['message'] . "\n\n" .$this->wx_register_text;
        }

    }

    /** 收到语音消息的处理方法 */
    public function voice()
    {
        if (isset($this->message['Recognition']))
        { // 已开通语音识别
            $text = '语音识别内容如下：' . $this->message['Recognition'];
        } else
        {
            $text = '没成功识别语音！'; // 文本内容
        }
        return $text;
    }

    /** 收到视频消息的处理方法*/
    public function video()
    {
        $text = '发视频给我干嘛呢？'; // 文本内容
        return $text;
    }

    /** 收到地理位置消息的处理方法 */
    public function location()
    {

        $text = '您的地址是：' . $this->message['Label']."\r坐标X：". $this->message['Location_X']."\r坐标Y：". $this->message['Location_Y']."\r缩放：". $this->message['Scale']; // 文本内容
        return $text;
    }


    /**
     * 获取服务id
     */
    public function server_id($server_name){
        if($server_name == 'SN2IMEI'){//sn&imei互转
            return 16;
        }else if($server_name == 'warranty'){//保修
            return 1;
        }else if($server_name == 'id_lock'){//ID锁
            return 2;
        }else if($server_name == 'ZHENGJI'){//整机查询
            return 18;
        }else if($server_name == 'weixiu_ing'){//正在维修
            return 7;
        }
        else{
            return -99;//服务异常，无选择服务状态
        }
    }



}
