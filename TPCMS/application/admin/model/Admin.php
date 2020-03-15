<?php

namespace app\admin\model;

use think\Model;
use think\Session;
use think\Db;
use think\facade\Request;

class Admin extends Model
{
    protected $table = 'yp_admin';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public static function adminlist($data,$limt)
    {
    	$adminlist = Db::name('admin')
    				->alias('a')
                	->join('auth_group_access b','b.uid = a.id')
                    ->join('auth_group c','c.id = b.group_id')
                    ->where($data)
                	->field('a.id,username,nickname,phone,email,avatar,ip,a.status,a.loginfailure,a.logintime,a.createtime,a.updatetime,c.name')
                	->order('id', 'asc')
                	->paginate($limt,false,['query'=>request()->param()]);
        return $adminlist;
    }

    public static function adminwher($map,$row)
    {
        $adminlist = Db::name('admin')
                    ->alias('a')
                    ->join('auth_group_access b','b.uid = a.id')
                    ->join('auth_group c','c.id = b.group_id')
                    ->field('a.id,username,nickname,phone,email,avatar,ip,a.status,a.loginfailure,a.logintime,a.createtime,a.updatetime,c.name')
                    ->order('id', 'asc')
                    ->paginate($row,false,['query'=>request()->param()]);

        return $adminlist;
    }

    public static function admin_access($map='')
    {
        $admin_access = Db::name('admin')
                        ->alias('a')
                        ->join('auth_group_access b','b.uid = a.id')
                        ->join('auth_group c','c.id = b.group_id')
                        ->where($map)
                        ->field('a.id,username,nickname,password,phone,email,avatar,ip,a.status as admin_status,c.status as auth_group_status,a.loginfailure,a.logintime,a.createtime,a.updatetime,b.group_id,c.name')
                        ->find();

        return $admin_access;
    }

}
