<?php

namespace app\wechatapp\controller;

use think\Db;
use EasyWeChat\Factory;


class We_event extends AutoReply
{
    protected $message;
    protected $appwx;
    protected $weUser;
    protected $user_id;//用户ID
    protected $login_info;//登陆状态
    protected $wx_register_text;//微信注册信息

    public function __construct($message, $appwx, $weUser)
    {
        session_start();
        $this->message = $message;
        $this->appwx = $appwx;
        $this->weUser = $weUser;
        //获取公众号积分配置文件
        if($_SESSION['user']['openid'] == ''){
            $wx_login = new \app\vip\controller\Login();
            $pid = $_GET['scene_id'];
            $this->login_info = $wx_login->weixin_login($this->weUser['openid'],$pid);
            $this->user_id = $_SESSION['think']['user']['id'];
            if($this->login_info['is_reg'] == 1){//注册
                $this->wx_register_text = '已自动为你生成爱果管理系统账号'."\n".'账号：'.$_SESSION['user']['username'] ."\n". '密码：888888'."\n\n". '可登陆<a href="https://www.aiguovip.com">www.aiguovip.com</a>修改密码';
            }
        }

    }


    /** 关注 在扫码没有关注的情况下，直接调到扫码*/
    public function subscribe()
    {
        $xingbie = "";
        if ($this->weUser['sex'] == 1)
            $xingbie = 'Hi，帅哥。';
        elseif ($this->weUser['sex'] == 2)
            $xingbie = 'Hi，美女。';
        $text = $xingbie . '欢迎关注爱果查询，感谢有您。'; //

        //扫爱果维修登录的码
        if (@$this->message['Ticket'] && @$this->message['EventKey'] == 'qrscene_aiguologin')
            return $text . "\n\n" . $this->aiguowxLogin();
        return $text ;
    }

    /** 取消关注*/
    public function unsubscribe()
    {
        //取消关注
        $user_id = Db::name('weixin_user')->where('id', $this->weUser['id'])->update(['subscribe' => '0']);
        return '为什么不在关注小爱了，是不是小爱那里做的不好呢';

    }
    /** 在关注的情况下扫码*/
    public function scan()
    {
//        $s = json_encode($_REQUEST) . json_encode($this->message);
        return 'aaa';
        //扫爱果维修登录的码
        if (@$this->message['Ticket'] && @$this->message['EventKey'] == 'aiguologin')
            return $this->aiguowxLogin();
    }
    /**
     * 扫爱果维修登录码
     * @param string $msg_type 触发类型
     * @param object $app easywechat项目
     * @return array
     */
    protected function aiguowxLogin()
    {
        $_db_User['openid'] = $this->message['FromUserName'];
        $_db_User['nickname'] = $this->weUser['nickname'];
        $_db_User['headimgurl'] = $this->weUser['headimgurl'];
        $_db_User['login_time'] = time();
        if (Db::name('weixin_login')->where('ticket', $this->message['Ticket'])->update($_db_User))
            return $this->weUser['nickname'] . ",您成功扫码授权登录爱果管理系统！" . "\n\n" . $this->wx_register_text;
        else
            return '很抱歉，授权登录爱果维修系统失败'.$_db_User['openid'].'test' ;

    }

    /** 点击自定义菜单事件*/
    public function click()
    {
        if($this->message['EventKey'] == 'promote'){//专属二维码
            $config = Config('wechat.');
            $wxapp = Factory::officialAccount($config);
            $result = $wxapp->qrcode->temporary(intval($this->user_id), 6 * 24 * 3600);//传入用户的uid
            $url = $wxapp->qrcode->url($result['ticket']);
            return "<img src='$url'>";
        }


        $res = $this->change_server($this->message['EventKey']);
        return $res;
    }

    /**
     *  选择服务
     */
    public function change_server($message){
        //用户在微信公众号菜单栏选择的服务
        //sn&imei互转
        if($message == 'SN2IMEI'){
            cache('EventKey' . $this->user_id, 'SN2IMEI', 180);
            $server_name = 'sn&imei互转';
            $id = '16';
        }else if($message == 'warranty'){
            cache('EventKey' . $this->user_id, 'warranty', 180);
            $server_name = '保修查询';
            $id = '1';
        }else if($message == 'id_lock'){
            cache('EventKey' . $this->user_id, 'id_lock', 180);
            $server_name = 'ID激活锁查询';
            $id = '2';
        }else if($message == 'ZHENGJI'){
            cache('EventKey' . $this->user_id, 'ZHENGJI', 180);
            $server_name = '整机查询';
            $id = '18';
        }else if($message == 'weixiu_ing'){
            cache('EventKey' . $this->user_id, 'weixiu_ing', 180);
            $server_name = '正在维修';
            $id = '7';
        }

        if(empty(cache('server_data_' . $message))){//若缓存为空，则查询数据库
            $server_data = Db::name('chaxun_config')->where('id',$id)->find();
            cache('server_data_' . $message,$server_data, 60*60*24);//存储一天
        }else{
            $server_data = cache('server_data_' . $message);
        }

        return '已切换为：'.$server_name. "\n".
'请输入序列号或IMEI
批量一次最多1500条
请用空格或换行隔开
- - - - -
查询内容：'. $server_data['guize']."\n". '用户ID：' . $_SESSION['think']['user']['id'] . "\n" . '消耗余额：' .$server_data['jifen']. "\n" .'当前余额：' . $_SESSION['think']['user']['jifen']
            ."\n". $this->wx_register_text;
    }


    // 上报地理位置事件
    // 用户同意上报地理位置后，每次进入公众号会话时，都会在进入时上报地理位置，或在进入会话后每5秒上报一次地理位置，公众号可以在公众平台网站中修改以上设置。上报地理位置时，微信会将上报地理位置事件推送到开发者填写的URL。
    public function location()
    {
        return;
        // 没设自动回复就执行下面代码
        $text = '我们已收到您上报的地理位置事件 纬度: ' . $this->message['Latitude'] . ' 经度: ' . $this->message['Longitude'] ."\n\n". $this->wx_register_text; // 文本内容
        return $text;
    }



    /**
     * 模板消息发送反馈
     */
    public function templatesendjobfinish()
    {
        return;
    }
    /**
     * 点击连接 反馈
     */
    public function view()
    {
        return;
    }

}
