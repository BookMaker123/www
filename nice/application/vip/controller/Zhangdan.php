<?php
namespace app\vip\controller;

use think\Controller;
use think\Db;

class Zhangdan extends AdminController
{
    /**
     * 账单管理
     */
    protected $model = null;

    public function __construct()
    {
        parent::__construct();

    }
    /**
     * 首页
     * @return mixed
     */
    public function index($id = null)
    {


        return $this->fetch();
    }
   
}
