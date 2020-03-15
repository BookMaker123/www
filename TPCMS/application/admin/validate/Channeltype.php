<?php 
namespace app\admin\validate;

use think\Validate;

class Channeltype extends Validate
{
    protected $rule = [
        'typename'                      => 'require|max:16',
        'arcurl'                        => 'require',
        'money'                         => 'require',
        'seotitle'                      => 'max:100',
        'keywords'                      => 'max:255',
        'description'                   => 'max:255',
        '__token__'                     => 'require|token',
    ];

    protected $message  =   [
        'typename.require'              => '栏目名称不能为空！',
        'typename.max:16'               => '栏目名称不能超过16个字符！',
        'arcurl.require'                => '栏目URL不能为空！',
        'seotitle.max:100'              => 'SEO标题不能超过100个字符！',
        'keywords.max:255'              => '关键字不能超过255个字符！',
        'description.max:255'           => '关键词描述不能超过255个字符！',
        '__token__.require'             => '非法提交!',
        '__token__.token'               => '请不要重复提交表单!',
    ];

    // 定义不同场景
    protected $scene = [
        'add'  =>  ['typename','arcurl','__token__'],
        'edit'  =>  ['typename','arcurl','__token__'],
    ];
}















 ?>