<?php
namespace app\api\controller;

use think\Controller;
use think\Db;
use app\common\Nb;


use EasyWeChat\Factory;

class Ip extends Controller
{
    public function getip()
    {
        $arr= Nb::Getip(); 
        return json($arr);
    }
}