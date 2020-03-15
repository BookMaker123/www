<?php

// [ 应用入口文件 ]
namespace think;

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'on');
//exit('升级系统，6月6日下午6点完成...');

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';



// 支持事先使用静态方法设置Request对象和Config对象

// 执行应用并响应
Container::get('app')->run()->send();
