<?php
namespace app\vip\validate;

use think\Validate;


/**
 * 订单列表验证类
 * Class Login
 * @package app\Vip\validate
 */
class Cadmin extends Validate {

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        //这里是AJAX提交 不能验证TOKE
        'paixu|排序' =>  ['require', 'number'],
        'tubiao|图标' => ['require', 'max' => 20],
        'name|名称' => ['require', 'max' => 32],
        'tishi|提示' => ['require', 'max' => 32],
        'jifen|积分' => ['require', 'float' ],
        'yanshi|演示' => ['require'],
        'fangfa|查询方法' => ['require','between'=>'1,3'],
        'weihu|是否维护' => ['require','between'=>'0,1'],
        'guize|规则' => ['require']
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'paixu.require' => '排序不能为空',
        'tubiao.require' => '图标不能为空',

        'jifen.float' => '积分格式错误'

   
    
    ];
    /**
     * 应用场景
     * @var array
     */
    protected $scene = [
        //添加
        'add'  => ['dname', 'ftime'],
        //编辑
        'edit' => ['dname'],
    ];

}