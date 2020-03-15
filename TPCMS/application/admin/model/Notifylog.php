<?php

namespace app\admin\model;

use think\Model;
use think\Session;

class Notifylog extends Model
{
    protected $table = 'yp_notify_url_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';



}
