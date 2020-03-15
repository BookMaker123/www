<?php 
namespace app\admin\validate;

use think\Validate;

class Extens extends Validate
{
    protected $rule = [
        'title'                 			 => 'require|max:100',
        'channeltype_id'                  	 => 'require',
        'swot'                  			 => 'require|max:40',
        'rate'                  			 => 'require|number',
        'wxhao'                  			 => 'require|max:20',
        'avatar'                 			 => 'require',
        'description'                 		 => 'require|max:100',
        '__token__'                			 => 'require|token',
    ];

    protected $message  =   [
        'title.require'                      => '专题标题不能为空!',
        'title.max:100'                		 => '专题标题不能超过100个字符!',
        'swot.require'                 		 => '优势/特色不能为空!',
        'swot.max:40'                        => '优势/特色不能超过40个字符!',
        'rate.require'                		 => '费率不能为空!',
        'rate.number'                		 => '费率必须为数字!',
        'wxhao.require'                		 => '微信号不能为空!',
        'wxhao.max:20'                		 => '微信号不能超过20个字符!',
        'avatar.require'                	 => '二维码不能为空!',
        'description.require'                => '介绍不能为空!',
        'description.max:100'                => '介绍不能超过100个字符!',
        '__token__.require'             	 => '非法提交!',
        '__token__.token'                	 => '请不要重复提交表单!',
    ];

    // 定义不同场景
    protected $scene = [
        'add'   =>  ['title,swot,rate,wxhao,avatar,description,__token__'],
        'edit'  =>  ['title,swot,rate,wxhao,avatar,description,__token__'],
    ];
}