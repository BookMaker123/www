<?php

namespace app\admin\model;

use think\Model;
use think\Db;
use think\Session;

class AuthGroup extends Model
{
    protected $table = 'yp_auth_group';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public static function getGroups($uid)
    {
        static $groups = [];
        if (isset($groups[$uid])) {
            return $groups[$uid];
        }
        $user_groups = Db::name('auth_group_access')
            ->alias('aga')
            ->join('__' . strtoupper('auth_group') . '__ ag', 'aga.group_id = ag.id', 'LEFT')
            ->field('aga.uid,aga.group_id,ag.id,ag.pid,ag.name,ag.rules')
            ->where("aga.uid='{$uid}' and ag.status='normal'")
            ->select();
        $groups[$uid] = $user_groups ?: [];
        return $groups[$uid];
    }

    public static function getRuleIds($uid)
    {
        $groups = self::getGroups($uid);
        $ids = []; 
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        return $ids;
    }

    public static function getRuleList($uid='')
    {        
        static $_rulelist = []; 
        if (isset($_rulelist[$uid])) {
            return $_rulelist[$uid];
        }
        $ids = self::getRuleIds($uid);
        if (empty($ids)) {
            $_rulelist[$uid] = [];
            return [];
        }
        $where = [
            'status' => 'normal'
        ];
        if (!in_array('*', $ids)) {
            $idss = implode(',', $ids);
            $rules = Db::name('auth_rule')
                    ->where('id','in',$idss)
                    ->where($where)
                    ->field('id,pid,condition,name,title,ismenu')
                    ->select();
        }else{
            $rules = Db::name('auth_rule')
                    ->where($where)
                    ->field('id,pid,condition,name,title,ismenu')
                    ->select();
        }
        $rulelist = []; 
        if (in_array('*', $ids)) {
            $rulelist[] = "*";
        }
        foreach ($rules as $rule) {
            if (!empty($rule['condition']) && !in_array('*', $ids)) {
                $condition = ''; 
                $user = $this->getUserInfo($uid); 
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $rulelist[$rule['id']] = strtolower($rule['name']);
                }
            } else {
                $rulelist[$rule['id']] = strtolower($rule['name']);
            }
        }
        $_rulelist[$uid] = $rulelist;
        session('_rule_list_' . $uid, $rulelist);
        return array_unique($rulelist);
    }
    
}