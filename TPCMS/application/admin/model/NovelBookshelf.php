<?php

namespace app\admin\model;

use think\Model;

class NovelBookshelf extends Model
{
    protected $table = 'yp_novel_bookshelf';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';



}