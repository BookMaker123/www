<?php 
namespace app\admin\validate;

use think\Validate;

class Messages extends Validate
{
    protected $rule = [
        'title'                  => 'require|max:16',
        '__token__'              => 'require|token',
    ];

    protected $message  =   [
        'title.require'                 => '用户消息标题不能为空!',
        'title.max:100'                 => '用户消息标题不能超过100个字符!',
        '__token__.require'             => '非法提交!',
        '__token__.token'               => '请不要重复提交表单!',
    ];

    // 定义不同场景
    protected $scene = [
        'add'  =>  ['title,__token__'],
        'edit'  =>  ['title,__token__'],
    ];
}