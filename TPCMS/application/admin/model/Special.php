<?php

namespace app\admin\model;

use think\Model;
use think\model\concern\SoftDelete;
use think\Session;

class Special extends Model
{
	use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $table = 'yp_spectype';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
   public static function front($ids='')
   {
     $front= self::where('status',1)->where('id','<',$ids)->order('id desc')->limit('1')->field('id,typename')->find();
     return $front;
  
   }

   public static function after($ids='')
   {
      $after= self::where('status',1)->where('id','>',$ids)->order('id asc')->limit('1')->field('id,typename')->find();
      return $after; 
   }

   

}