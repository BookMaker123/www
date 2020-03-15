<?php
/**
 * Created by PhpStorm.
 * User: Aven
 * E-mail: 741606767@qq.com
 * Date: 2019/1/23
 * Time: 11:12
 */

namespace app\admin\validate;


use think\Validate;

class Login extends Validate
{
    protected $rule = [
        'username'             => 'require',
        'password'         	   => 'require|max:100',
    ];

    protected $message  =   [
        'username.require'          => '账号不能为空！',
        'password.require'          => '密码不能为空！',
        'password.max:250'          => '密码不能超过100个字符！',
    ];




}