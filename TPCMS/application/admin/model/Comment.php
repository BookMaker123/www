<?php

namespace app\admin\model;

use think\Model;

class Comment extends Model
{
    protected $table = 'yp_comment';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';



}