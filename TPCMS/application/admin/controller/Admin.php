<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Admin as AdminModel;
use app\admin\model\Attachment as AttachmentModel;
use app\admin\model\AuthGroup as AuthGroupModel;
use app\admin\model\AuthGroupAccess;
use think\Db;
use think\facade\Request;
use fast\Random;


class Admin extends Base
{
    public function index()
    {
        $map[] = array('a.status','=','normal');
        if ($this->request->isPost()) {
            $map[] = array('username','like',"%".input('post.username','trim')."%");
        }
        $list = AdminModel::adminlist($map,10);
        $count = $list->total();
        $page = $list->render();
   
       
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('page', $page);

        return $this->fetch();
    }

    public function add()
    {   
        $lis = AuthGroupModel::where('status','normal')->order('id', 'asc')->select();
        $lis = recursion($lis,0,'|--');
        $this->assign('lis', $lis);
    	if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Admin.add');
            if(true !== $result){return $this->error($result);}
            $data['salt'] = Random::alnum();
            $data['password'] = md5(md5($data['password']) . $data['salt']);
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = '/static/assets/img/avatar.png'; //设置新管理员默认头像。
            }
            $group_access['group_id'] = $data['group_id'];
            $data['createtime'] = time();
            $data['updatetime'] = time();
            unset($data['group_id']);
            unset($data['password2']);
            unset($data['__token__']);
            if (AdminModel::where('username',$data['username'])->find()) return json('该账户已存在！');
            if($admin_id = AdminModel::insertGetId($data)){
                $group_access['uid'] = $admin_id;
                if(model('AuthGroupAccess')->insert($group_access)){
                    return json('添加成功！');
                }else{
                    return json('添加失败！');
                }
                return json('添加成功！');
            }else{
                return json('添加失败！');
            }

    	}
        
        return $this->fetch();
    }

    public function edit()
    {   
        $ids = Request::param('ids');
        $lis = AuthGroupModel::where('status','normal')->order('id', 'asc')->select();
        $lis = recursion($lis,0,'|--');
        $map = [
            'a.id'=>$ids,
        ];
        $list = AdminModel::admin_access($map);

        $this->assign('list', $list);
        $this->assign('lis', $lis);

        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Admin.edit');
            if(true !== $result){return $this->error($result);}
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = $list['avatar'];
            }
            $group_access['group_id'] = $data['group_id'];
            $data['updatetime'] = time();
            unset($data['group_id']);
            unset($data['__token__']);
            if ($data['username'] !== $list['username']) {
               if (AdminModel::where('username',$data['username'])->find()) json('该账号名已存在！');
            }
            if(AdminModel::where('id', $list['id'])->update($data)){
                $group_access['uid'] = $list['id'];
                model('AuthGroupAccess')->where('uid', $list['id'])->update($group_access);
                return json('编辑成功！');
            }else{
                return json('编辑失败！');
            }
        }

        return $this->fetch();
    }

    public function del()
    {   
        $ids = Request::param('ids');
        if ($ids === 7) {
            return json('不允许删除admin！');
        }else{
            if ($ids) {
                if(Db::name('auth_group_access')->where('uid','in',$ids)->delete()){
                    if(Db::name('admin')->where('id','in',$ids)->delete()) return json('删除成功！'); return json('删除失败！');
                }
                return json('删除成功！');
            }
        }
        return $this->fetch();
    }

    public function password_edit()
    {   
        $ids = Request::param('ids');
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Member.passw');
            if(true !== $result){return $this->error($result);}
            $data['salt'] = Random::alnum();
            $data['password'] = md5(md5($data['password']) . $data['salt']);
            $data['updatetime'] = time();
            unset($data['password2']);
            unset($data['__token__']);
            if(AdminModel::where('id', $ids)->update($data)){
                return $this->success('密码修改成功');
            }else{
                return $this->error('编辑失败！');
            }
        }
        return $this->fetch();
    }


}


 ?>