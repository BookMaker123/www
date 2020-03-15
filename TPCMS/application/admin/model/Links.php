<?php

namespace app\admin\model;

use think\Model;

class Links extends Model
{
    protected $table = 'yp_links';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';



}