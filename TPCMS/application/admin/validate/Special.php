<?php 
namespace app\admin\validate;

use think\Validate;

class Special extends Validate
{
    protected $rule = [
        'typename'                  => 'require|max:16',
        '__token__'              => 'require|token',
    ];

    protected $message  =   [
        'typename.require'                 => '专题标题不能为空!',
        'typename.max:100'                 => '专题标题不能超过100个字符!',
        '__token__.require'             => '非法提交!',
        '__token__.token'               => '请不要重复提交表单!',
    ];

    // 定义不同场景
    protected $scene = [
        'add'  =>  ['typename,__token__'],
        'edit'  =>  ['typename,__token__'],
    ];
}