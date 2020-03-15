<?php
namespace app\vip\validate;

use think\Validate;


/**
 * 订单列表验证类
 * Class Login
 * @package app\Vip\validate
 */
class Lists extends Validate {

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        //这里是AJAX提交 不能验证TOKE
        'ftime|发货时间' =>  ['require', 'date'],
        'dname|订单名称' => ['require', 'max' => 6],

    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'ftime.require' => '请选择订单日期',
        'ftime.date' => '无效日期',        
        'dname.require' => '请填写订单名称',
        'dname.max' => '订单名称不能超过6个字符',
        'dname.regex' => '配置名称由字母和下划线组成',
    ];
    /**
     * 应用场景
     * @var array
     */
    protected $scene = [
        //开启时间
        'ftime_on'  => ['dname', 'ftime'],

        //关闭时间
        'ftime_off' => ['dname'],
    ];

}