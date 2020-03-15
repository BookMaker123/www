<?php

namespace app\admin\model;

use think\Model;

class CommentMember extends Model
{
    protected $table = 'yp_comment_member';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';



}