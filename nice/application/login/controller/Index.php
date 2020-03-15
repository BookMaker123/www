<?php
namespace app\login\controller;


use think\Controller;

//老版本 登录跳转
class Index extends Controller
{
    public function index()
    {
        //
        return $this->redirect('@vip/lists');

    }

}
