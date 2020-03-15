<?php 
namespace app\admin\validate;

use think\Validate;

class Member extends Validate
{
    protected $rule = [
        'username'               => 'require|max:16',
        'password'               => 'require|min:4',
        'phone'                  => 'mobile',
        'email'                  => 'email',
        'code'                   => 'require|captcha',
        '__token__'              => 'require|token',
    ];

    protected $message  =   [
        'username.require'              => '账号名不能为空！',
        'username.max:16'               => '账号名不能超过16个字符！',
        'password.require'              => '密码不能为空！',
        'password.min'                  => '密码不能小于4个字符！',
        'email'                         => '邮箱格式错误',
        'phone'                         => '手机格式错误',
        'code.require'                  => '验证码不能为空！',
        'code.captcha'                  => '验证码错误！',
        '__token__.require'             => '非法提交!',
        '__token__.token'               => '请不要重复提交表单!',
    ];

    // 定义不同场景
    protected $scene = [
        'add'   =>  ['username','password','phone','email','__token__'],
        'edit'  =>  ['username','phone','email','__token__'],
        'passw' =>  ['password'],
    ];
}
