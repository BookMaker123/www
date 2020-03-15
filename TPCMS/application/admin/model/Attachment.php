<?php

namespace app\admin\model;

use think\Model;
use think\Session;

class Attachment extends Model
{
    protected $table = 'yp_attachment';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';



}
