<?php
namespace app\vip\controller;

use think\Controller;
use think\Db;
use app\common\Core;
use app\vip\controller\Check as check;
use app\vip\controller\Chaxun as chaxun;
class Test extends AdminController
{   
    public function index($input = null){
        // 1. 初始化
        $ch = curl_init();

// 2. 设置选项
        curl_setopt($ch, CURLOPT_URL, "https://www.awaimai.com");  // 设置要抓取的页面地址
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);              // 抓取结果直接返回（如果为0，则直接输出内容到页面）
        curl_setopt($ch, CURLOPT_HEADER, 0);                      // 不需要页面的HTTP头

        //避免https 的ssl验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
// 3. 执行并获取HTML文档内容，可用echo输出内容
        $output = curl_exec($ch);
        dump($output);
        if ($output === FALSE) {
            echo "cURL Error: " . curl_error($ch);
        }
        $info = curl_getinfo($ch);
        echo '获取'. $info['url'] . '耗时'. $info['total_time'] . '秒';
// 4. 释放curl句柄
        curl_close($ch);
//        echo $output;
//        echo str_replace("百度","屌丝",$output);
//        echo $output;
//        $rs = Core::getIntervalDayNum("20190909", "20180908");
//        $uid = 10;
//        $remark = '会员'.'xxx先生'.'使用高级查询花费'.'666'.'积分，冻结积分增加'.'666'.'积分,二次添加';
//        $content = '高级查询';
//        $tishi_text = '';
//        wechat_sending($uid,$remark,$content,$tishi_text);
//        var_dump('你好，郑先生？');
//        die;

//        $phone_number = Db::name('admin_user')
//            ->alias('u')
//            ->join('weixin_user w','u.openid = w.openid')
//            ->where('w.openid','oOEJGwut0I2666xupktodUzcLCzE')
//            ->column('u.phone_number');
//
//        if (!in_array('13434834599',$phone_number)) {
//            echo "不在";
//        } else {
//            return $this->redirect('');
//        }
//         $check = new check;
//         $sn = 'GDGWP074JCLN';
//         $imin = '357326097076531';
////         $re = $check->net_lock($sn);
////         $re = $check->net_blacklist($imin);
////         $re = $check->imei2sn($imin);
//        $name = $input;
//        $url = sprintf("http://api.qingyunke.com/api.php?key=free&appid=0&msg=".$name);
//        $rs = Core::get($url);
//        $reply_arr = json_decode($rs['data']);
//        $reply = $reply_arr->content;
//        echo $reply;

//        $chaxun = new chaxun;
//        $wxdata = [
//            'order_title'=>'哇哈哈',
//            'id_list'=>[1],
//            'imeitext'=>'FM1X2UMYG72M
//FM1X2V57G73P
//FM1X2TKRG73V
//FM1WTV7GG76C
//FM1X7WGJG76D'
//        ];
//        $chaxun->add($wxdata);
//        $wxdata = [
//            'order_title'=>'哇哈哈666',
//            'id_list'=>[1],
//            'imeitext'=>'FM1X2UMYG72M
//FM1X2V57G73P
//FM1X2TKRG73V
//FM1WTV7GG76C
//FM1X7WGJG76D'
//        ];
//       $arr = wxdata($wxdata);
//       dump($arr);
//        echo '哈哈哈';
//        $server_id = [1];
//        $sn_imei_data = 'FM1X2UMYG72M
//FM1X2V57G73P
//FM1X2TKRG73V
//FM1WTV7GG76C
//FM1X7WGJG76D';
//        $is_wx = 1;
//        $order_title = '234234';
//        $data = add_order($server_id,$sn_imei_data,$is_wx,$order_title);
//        dump($data);
//        weixin_login('oOEJGwut0I2666xupktodUzcLCzE',7195);
    }

}