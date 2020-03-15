<?php

namespace app\admin\model;

use think\Model;
use think\model\concern\SoftDelete;
use think\Session;

class Member extends Model
{
	use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $table = 'yp_member';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $loginTime  = 'logintime';
     



}