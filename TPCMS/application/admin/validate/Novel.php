<?php 
namespace app\admin\validate;

use think\Validate;

class Novel extends Validate
{
    protected $rule = [
        'title'                  => 'require|max:50',
        'translator'             => 'require|max:20',
        'author'                 => 'require|max:20',
        'description'            => 'require',
        '__token__'              => 'require|token',
    ];

    protected $message  =   [
        'title.require'                => '书籍名称不能为空!',
        'title.max:50'                 => '书籍名称不能超过50个字符!',
        'translator.require'           => '书籍译者不能为空!',
        'translator.max:20'            => '书籍译者不能超过20个字符!',
        'author.require'               => '书籍作者不能为空!',
        'author.max:20'                => '书籍作者不能超过20个字符!',
        'description.require'          => '书籍简介不能为空!',
        '__token__.require'            => '非法提交!',
        '__token__.token'              => '请不要重复提交表单!',
    ];

    // 定义不同场景
    protected $scene = [
        'add'  =>  ['title,translator,author,__token__'],
        'edit'  =>  ['title,translator,author,__token__'],
    ];
}















 ?>