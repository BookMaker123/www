<?php 
namespace app\admin\validate;

use think\Validate;

class Group extends Validate
{
    protected $rule = [
        'name'               => 'require|max:16',
        '__token__'              => 'require|token',
    ];

    protected $message  =   [
        'name.require'              => '角色名称不能为空！',
        'name.max:16'               => '角色名称不能超过16个字符！',
        '__token__.require'             => '非法提交!',
        '__token__.token'               => '请不要重复提交表单!',
    ];

    // 定义不同场景
    protected $scene = [
        'add'  =>  ['name','__token__'],
        'edit'  =>  ['name','__token__'],
    ];
}















 ?>