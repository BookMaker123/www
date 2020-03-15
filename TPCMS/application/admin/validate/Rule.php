<?php 
namespace app\admin\validate;

use think\Validate;

class Rule extends Validate
{
    protected $rule = [
        'title'               => 'require',
        '__token__'              => 'require|token',
    ];

    protected $message  =   [
        'title.require'              => '标题名称不能为空！',
        '__token__.require'             => '非法提交!',
        '__token__.token'               => '请不要重复提交表单!',
    ];

    // 定义不同场景
    protected $scene = [
        'add'  =>  ['title','__token__'],
        'edit'  =>  ['title','__token__'],
    ];
}















 ?>