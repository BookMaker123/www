<?php
namespace app\api\controller;

use think\Controller;

use EasyWeChat\Factory;
use think\Db;


class Caidan extends Controller
{

    private $wxapp; //全局变量

    /**
     * 初始化
     */
    public function __construct()
    {
        $config = Config('wechat.');
        $this->wxapp = Factory::officialAccount($config);
        parent::__construct(); //必须要获取这个 isGet才不会报错

    }
    public function index()
    { 
        /** 获取SDK服务端实例 */

        $buttons = [
            ["name" => "爱果管理", "sub_button" => [
                ["type" => "view", "name" =>"进入新系统", "url" => "http://www.aiguovip.com"],   
                ["type" => "view", "name" =>"进入老系统", "url" => "http://1.aiguovip.com"],
                ["type" => "pic_photo_or_album", "name" =>"拍照查询","key"=>'PicUrl'],//pic_photo_or_album
            ],
            ],
            ["name" => "爱果查询", "sub_button" => [
                    ["type" => "click", "name" =>"购买日期", "key" => "SN2IMEI"],
                    ["type" => "click", "name" =>"保修查询", "key" => "warranty"],
                    ["type" => "click", "name" =>"ID锁查询", "key" => "id_lock"],
                    ["type" => "click", "name" =>"整机报告", "key" => "ZHENGJI"],
                    ["type" => "click", "name" =>"正在维修", "key" => "weixiu_ing"],
                ],
            ],
            ["name" => "用户中心", "sub_button" => [
                    ["type" => "click", "name" =>"推广二维码", "key" => "my_ewm"],
                    ["type" => "view", "name" =>"我的积分", "url" =>  "https://www.aiguovip.com/vip/user/jifenjilu.html"],
                    ["type" => "view", "name" =>"我的订单", "url" => "https://www.aiguovip.com/vip/chaxun/index.html"],
                    ["type" => "view", "name" =>"在线客服", "url" => "https://mp.weixin.qq.com/s/2R281Fr4seUvvZnblA8O7g"],

                ],
            ],
         ];
        if($cai=$this->wxapp->menu->create($buttons)){
            echo    '设置菜单成功2';
            print_r($cai);

        };

    }

    public function change_openid(){
//        $old_open_id = ['oaXm5jmL8CqKQKaEIu-eD1EDagRM','oaXm5jsfXi3p3eBTz1PGmZYaKKdY'] ;
//
//
//        $config = Config('wechat.');
//
//        $wxapp = Factory::officialAccount($config);
//
//        $info = $wxapp->user->get('oOEJGwuk_7k164fNPd9rAxsY-85U');
//        print_r($info);
//        die();

        $test ='Model (\u578b\u53f7) : iPhone X<br>IMEI (\u4e32\u53f7) : 354857092923387<br>Serial Number (\u5e8f\u5217\u53f7) : GDGWN15ZJCL6<br>Estimated Purchase Date (\u9884\u8ba1\u8d2d\u4e70\u65e5\u671f) : 2018-01-19';;

    }
}