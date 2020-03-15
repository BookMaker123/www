<?php

namespace app\admin\model;

use think\Model;
use think\Session;
use think\Db;

class Tags extends Model
{
    protected $table = 'yp_tags';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public static function InsertTags($tag, $aid,$category)
	{
        $tags = explode(',',$tag);
        foreach($tags as $tag)
        {
            $tag = trim($tag);
            if(isset($tag[26]) || $tag!=stripslashes($tag)) { continue; }
            self::InsertOneTag($tag,$aid,$category);
        }

	}
 
	public static function InsertOneTag($tag='', $aid='',$category='')
	{
        $tag = trim($tag);
        if($tag == ''){ return ''; }
        if(empty($category)){ $category = 0; }
        $tags_data = [];
        $tags_data['tagname'] = $tag;
        $tags_data['category_id'] = $category;
        $row = model('tags')->where('tagname',$tag)->find();
        if (empty($row)) {
        	$tags_data['createtime'] = time();
            $tags_data['updatetime'] = time();
        	$rs  = model('tags')->insertGetId($tags_data);
        	$tid = $rs;
        }else{
        	$arr = [];
        	$arr['updatetime'] = time();
        	$arr['num'] = $row['num']+1;
        	$rs  = Db::name('tags')->where('id',$row['id'])->update($arr);
        	$tid = $row['id'];
        }
        if($rs){
        	model('tagmap')->insert(['tagid'=>$tid,'aid'=>$aid]);
        }
	}

    public static function GetTags($aid='')
    {
        $dsql = Db::name('tagmap')->where('aid',$aid)->select();
        $row = [];
        foreach ($dsql as $key => $va) {
            $row[] = Db::name('tags')->where('id',$va['tagid'])->value('tagname');
        }
        $tags = implode(",", $row);
        return $tags;
    }

    public static function UpIndexKey($tags='',$aid, $channeltype_id)
    {
        if($tags!='')
        {   
            $oldtag = self::GetTags($aid);
            $oldtags = explode(',',$oldtag);
            $tagss = explode(',',$tags);
            foreach($tagss as $tag)
            {
                $tag = trim($tag);
                if(isset($tag[26]) || $tag!=stripslashes($tag))
                {
                    continue;
                }
                if(!in_array($tag,$oldtags))
                {
                    self::InsertOneTag($tag,$aid,$channeltype_id);
                }
            }
            foreach($oldtags as $tag)
            {
                if(!in_array($tag,$tagss))
                {   
                    $tags_id = Db::name('tags')->where('tagname',$tag)->field('id')->find();

                    Db::name('tagmap')->where('tagid','in',$tags_id)->delete();
                }else{
                    $tagid = Db::name('tagmap')->where('aid',$aid)->column('tagid');
                    Db::name('tags')->where('id','in',$tagid)->update(['category_id'=>$channeltype_id]);
                }
            }
        }else{
            Db::name('tagmap')->where('aid',$aid)->delete();
        }
    }



}