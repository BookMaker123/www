<?php 
namespace app\admin\controller;

use app\admin\model\AuthGroup as AuthGroupModel;
use think\Controller;
use think\Loader;
use think\facade\Session;

class Base extends Controller
{	
	public function initialize()
	{	
        if(!Session::has('admin_id')){ $this->redirect('login/index'); }
        $modulename = $this->request->module();
        $controllername = Loader::parseName($this->request->controller());
        $actionname = strtolower($this->request->action());
        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        $uid = session::get('admin_id');
        if (!$this->check($path,$uid)) {
        	session('admin_id',null);
            return $this->error('没有权限操作！');
        }
	}

	public function check($name,$uid,$relation = 'or',$mode = 'url')
	{
        $rulelist = AuthGroupModel::getRuleList($uid);
        if (in_array('*', $rulelist)) {
            return true;
        }
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = [$name];
            }
        }
        $list = []; 
        if ('url' == $mode) {
            $REQUEST = unserialize(strtolower(serialize($this->request->param())));
        }
        foreach ($rulelist as $rule) {
            $query = preg_replace('/^.+\?/U', '', $rule);
            if ('url' == $mode && $query != $rule) {
                parse_str($query, $param); 
                $intersect = array_intersect_assoc($REQUEST, $param);
                $rule = preg_replace('/\?.*$/U', '', $rule);
                if (in_array($rule, $name) && $intersect == $param) {
                    $list[] = $rule;
                }
            } else {
                if (in_array($rule, $name)) {
                    $list[] = $rule;
                }
            }
        }
        if ('or' == $relation && !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ('and' == $relation && empty($diff)) {
            return true;
        }
        return false;
	}

}























 ?>