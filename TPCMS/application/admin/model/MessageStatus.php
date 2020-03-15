<?php

namespace app\admin\model;

use think\Model;


class MessageStatus extends Model
{
    protected $table = 'yp_message_status';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';




}