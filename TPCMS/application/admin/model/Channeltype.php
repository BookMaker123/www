<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use think\Session;

class Channeltype extends Model
{
    protected $table = 'yp_channeltype';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function maxgroup($value='')
    {
    	
        return $maxgroup;
    }

    public function pidgroup($value='')
    {

        return $pidgroup;
    }

    public function songroup($value='')
    {

        return $songroup;
    }




}