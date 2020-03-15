<?php
namespace app\vip\controller;

use think\Controller;

class Index extends AdminController
{
    /**
     * 首页
     * @return mixed
     */
    public function index()
    {
        //登陆自动跳转到指定页面
        if(cookie('?__forward__')&& strstr(cookie('__forward__'), 'listsn')){
            return $this->redirect(cookie('__forward__'));
        }
        return $this->redirect('@vip/lists');
    }
}
