<?php

namespace app\admin\model;

use think\Model;


class Messages extends Model
{
    protected $table = 'yp_messages';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';




}