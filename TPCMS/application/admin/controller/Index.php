<?php
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\AuthGroup as AuthGroupModel;
use app\admin\model\Admin as AdmiminModel;
use think\facade\Session;
use think\facade\Request;
use think\facade\Cache;
use fast\Random;

class Index extends Base
{
    public function index()
    {
        $uid = session::get('admin_id');
        $userRule = AuthGroupModel::getRuleList($uid);
        $selected = $referer = [];
        $module = request()->module();
        $ruleList = collection(\app\admin\model\AuthRule::where('status', 'normal')
            ->where('ismenu', 1)
            ->order('weigh', 'desc')
            ->cache("__menu__")
            ->select())->toArray();
        $indexRuleList = \app\admin\model\AuthRule::where('status', 'normal')
            ->where('ismenu', 0)
            ->where('name', 'like', '%/index')
            ->column('name,pid');
        $pidArr = array_filter(array_unique(array_map(function ($item) {
            return $item['pid'];
        }, $ruleList)));
        foreach ($ruleList as $k => &$v) {
            if (!in_array($v['name'], $userRule)) {
                unset($ruleList[$k]);
                continue;
            }
            $indexRuleName = $v['name'] . '/index';
            if (isset($indexRuleList[$indexRuleName]) && !in_array($indexRuleName, $userRule)) {
                unset($ruleList[$k]);
                continue;
            }
            $v['url'] = $v['name'];
            $v['contro'] = trim($v['name'],strrchr($v['name'],'/'));
            $v['badge'] = isset($badgeList[$v['name']]) ? $badgeList[$v['name']] : '';

            $selected = $v['name'];
            $referer = url($v['url']);
        }
        $lastArr = array_diff($pidArr, array_filter(array_unique(array_map(function ($item) {
            return $item['pid'];
        }, $ruleList))));
        foreach ($ruleList as $index => $item) {
            if (in_array($item['id'], $lastArr)) {
                unset($ruleList[$index]);
            }
        }
        $ruleList = digui($ruleList);
        $list = AdmiminModel::where('id',$uid)->field('username,nickname,avatar,phone,email')->find();
        $this->assign('list',$list);
        $this->assign('ruleList',$ruleList);
        return $this->fetch();
    }

    public function welcome()
    {
        $data['pcname'] = gethostbyaddr("127.0.0.1");
        $data['ip']     = GetHostByName($_SERVER['REMOTE_ADDR']);
        $data['userdomain'] = $_SERVER['SERVER_NAME'];
        $data['port'] = $_SERVER['SERVER_PORT'];
        $data['unames'] = php_uname('s').php_uname('r');
        $data['uname'] = php_uname(); 
        $data['version'] = PHP_VERSION; 
        $data['zend'] = Zend_Version();
        $data['sapi'] = php_sapi_name();
        $data['filesize'] = get_cfg_var ("upload_max_filesize")?get_cfg_var ("upload_max_filesize"):"不允许";
        $data['execution'] = get_cfg_var("max_execution_time")."秒 ";
        $data['memory'] = get_cfg_var ("memory_limit")?get_cfg_var("memory_limit"):"无"; 
        $data['accept'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $this->assign('data',$data);
        
        return $this->fetch();
    }

    public function user_edit()
    {
        if ($this->request->isPost()) {
            $map = array();
            $uid = session::get('admin_id');
            if (!$uid) {
                Session::clear();
                $this->error('用户未登入','login/index');
            }
            $nickname = $this->request->post('nickname','null','trim');
            if ($nickname) {
                $map['nickname'] = $nickname;
            }
            $phone = $this->request->post('phone','null','trim');
            if ($phone) {
                $map['phone'] = $phone;
            }
            $email = $this->request->post('email','null','trim');
            if ($email) {
                $map['email'] = $email;
            }
            $password = $this->request->post('password','null','trim');
            $password2 = $this->request->post('password2','null','trim');
            if ($password) {
                $data = array('password'=>$password,'password2'=>$password2);
                $result = $this->validate($data, 'Member.passw');
                if(true !== $result){return $this->error($result);}
                $map['salt'] = Random::alnum();
                $map['password'] = md5(md5($password) . $map['salt']);
            }

            // $result = $this->validate($map, 'Index.myselfinfo');
            // if(true !== $result){return $this->error($result);}
            if ($file = request()->file('avatar')) {
                $map['avatar'] = upload($file);
            }

            if (empty($map)) {
                $this->error('没有任何修改请勿再次提交');
            }
            $map['updatetime'] = time();
            if(AdmiminModel::where('id', $uid)->update($map)){
                if (isset($map['avatar'])) {
                   $avatar = $map['avatar'];
                } else {
                    $avatar = '';
                }
                $data = array('nickname'=>$nickname,'phone'=>$phone,'email'=>$email,'avatar'=>$avatar);
                return  $this->success('修改成功','',$data);
            }else{
                return  $this->error('修改失败');
            }
        }
    }
    
// 清除缓存
    public function clear_all() {
        // $CACHE_PATH = config('cache.runtime_path').'/cache/';
        $TEMP_PATH = config('cache.runtime_path').'/temp/';
        // $LOG_PATH = config('cache.runtime_path').'/log/';
        if (delete_dir_file($TEMP_PATH)) {
            $this->success('清除缓存成功!');
        } else {
            $this->error('清除缓存失败!');
        }
        // if (delete_dir_file($CACHE_PATH) || delete_dir_file($TEMP_PATH) || delete_dir_file($LOG_PATH)) {
        //     $this->success('清除缓存成功!');
        // } else {
        //     $this->error('清除缓存失败!');
        // }
    }
    
}
