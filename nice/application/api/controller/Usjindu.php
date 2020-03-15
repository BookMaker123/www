<?php

namespace app\api\controller;

use think\Controller;
use think\Db;
use app\common\Jindu as Jinducheck;
use app\common\Ztai;

class Usjindu extends Controller
{

    private $wxapp; //全局变量

    /**
     * 初始化
     */
    public function __construct()
    {
        parent::__construct();
    }
    public function checkus($wid=null,$sn=null,$ci=0){

       $iparr= Ztai::Getip();  
   
       $arr['time'] = 30;
       $arr['daili'] = 'http://zproxy.lum-superproxy.io:22225';
       $arr['dailimima'] = "lum-customer-hl_d7719b11-zone-static-ip-" . $iparr['ip']. ":s6e05p94uqwq"; //指定美国随机
       $arr["url"] = "https://mysupport.apple.com/api/v1/supportaccount/getRepairStatus?repairId=$wid&serialNumber=$sn&selectedLocale=zh_CN"; //en_US zh_CN
       $arr['header'] = array(
           'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
           "Content-Type: application/json; charset=UTF-8",
           'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
           'Referer: https://getsupport.apple.com/',
       );

     $fanhui = Jinducheck::_curl($arr);
     if ($fanhui['httpcode'] == 200 || $fanhui['httpcode'] == '200') {
        return $fanhui['html'];
    }elseif($fanhui['httpcode'] == 0 ){       
        //代理错误 执行三次
        if($ci<3){
            $ci++;
            return $this->checkus($wid,$sn,$ci);
        }
    }  
    }


}