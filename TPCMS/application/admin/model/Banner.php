<?php

namespace app\admin\model;

use think\Model;

class Banner extends Model
{
    protected $table = 'yp_banner';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';



}