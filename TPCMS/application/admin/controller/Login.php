<?php
namespace app\admin\controller;

use app\admin\model\Admin as AdminModel;
use think\Controller;
use think\facade\Request;
use think\facade\Session;
use think\Db;
use think\captcha\Captcha;

class Login extends Controller
{	
    public function index()
    {
    	if(Session::has('admin_id')){ return $this->redirect('index/index'); }
    	if ($this->request->isPost()){
            $data = input('post.');
            $result = $this->validate($data, 'Login');
            if(true !== $result) $this->error($result);
            if(!captcha_check($data['captcha'])) return $this->error('验证码错误，请重新输入！');
            if(!$username = AdminModel::where(['username'=>$data['username'],'status'=>'normal'])->find()) return $this->error('用户名不存在！');
            $data['salt'] = $username['salt'];
            $data['password'] = md5(md5($data['password']) . $data['salt']);
            if ($admin = AdminModel::where('password',$data['password'])->find()){
                Session::set('admin_id',$admin['id']);
                AdminModel::where('id',$admin['id'])->update(['logintime'=>time(),'ip'=>Request::instance()->ip()]);
                return $this->success('登录成功！','index/index');
            }else{
                return $this->error('用户密码错误！');
            }
        }
        return $this->fetch();
    }

    public function logout()
    {
        Session::clear();
        return $this->success('退出登录成功！','login/index');
    }

}
