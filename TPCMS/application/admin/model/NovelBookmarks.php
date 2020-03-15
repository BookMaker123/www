<?php

namespace app\admin\model;

use think\Model;

class NovelBookmarks extends Model
{
    protected $table = 'yp_novel_bookmarks';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';



}