<?php

namespace app\admin\model;

use think\Model;
use think\model\concern\SoftDelete;
use think\Session;

class NovelChapter extends Model
{
	  use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $table = 'yp_novel_chapter';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

   public static function front($ids='',$novel_id='')
   {
     $front= self::where(['status'=>1,'novel_id'=>$novel_id])->where('id','<',$ids)->order('id desc')->limit('1')->field('id,title')->find();
     return $front;
  
   }

   public static function after($ids='',$novel_id='')
   {
      $after= self::where(['status'=>1,'novel_id'=>$novel_id])->where('id','>',$ids)->order('id asc')->limit('1')->field('id,title')->find();
      return $after; 
   }
 
   public static function latest()
   {
     $latest = self::alias('a')
                      ->join('novel b ','b.id= a.novel_id')
                      ->join('category c ','c.id= b.category')
                      ->field('a.id as ids,c.title as category,b.title as novel,titles,a.title as chapter_bt,b.translator as translator,b.author as author,a.createtime')
                      ->where(['a.status'=>1])
                      ->order('a.createtime desc')
                      ->limit(50)
                      ->select();
     return $latest;
   }

}