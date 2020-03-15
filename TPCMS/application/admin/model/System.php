<?php

namespace app\admin\model;

use think\Model;
use think\Session;

class System extends Model
{
    protected $table = 'yp_system';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    



}