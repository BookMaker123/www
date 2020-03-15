<?php
namespace app\index\controller;

use app\common\Nb;
use think\Controller;
use think\Db;
use think\facade\Cache;

class Index extends Controller
{
    public function index()
    {
        return $this->redirect('@vip/lists');

    }

}
