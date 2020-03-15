<?php

namespace app\admin\model;

use think\Model;
use think\Session;

class AuthRule extends Model
{
    protected $table = 'yp_auth_rule';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';



}