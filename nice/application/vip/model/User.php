<?php

namespace app\vip\model;
use think\Model;
use think\helper\Hash;

/**
 * 系统管理员模型
 * Class User
 * @package app\vip\model
 */
class User extends Model {

    /**
     * 绑定数据表
     * @var string
     */
    protected $name= 'admin_user';
    // 对密码进行加密
    public function setPasswordAttr($value)
    {
        return Hash::make((string )$value);
    }
    /**
     * 登录验证
     * @param $username 管理员账户
     * @param $password 管理员密码
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function login($username, $password,$type) {
        if ($type == 1) {
            $where_login = [
                ['username', '=', $username],
                //['is_deleted', 'In', [0, 1]],
                //['status', 'In', [0, 1]],
            ];
        } elseif ($type == 2) {
            $where_login = [
                ['phone_number', '=', $username],
                //['is_deleted', 'In', [0, 1]],
                //['status', 'In', [0, 1]],
            ];
        }

        $login = self::where($where_login)->find();

       // if ($login['password'] != password($password)) return ['code' => 1, 'msg' => '密码不正确，请重新输入！', 'user' => $login];
        if (!Hash::check((string)$password, $login['password'])) return ['code' => 1, 'msg' => '用户名存在或者密码错误！', 'user' => $login];
            

        //if ($login['is_deleted'] == 1) return ['code' => 1, 'msg' => '该账户已被删除，请联系超级管理员！', 'user' => $login];
        //if ($login['status'] == 0) return ['code' => 1, 'msg' => '该账户已被停用，请联系超级管理员！', 'user' => $login];
        unset($login['password']);
        return ['code' => 0, 'msg' => '登录成功，正在进入后台系统！', 'user' => $login];
    }
}