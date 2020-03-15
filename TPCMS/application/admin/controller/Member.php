<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Member as MemberModel;
use think\facade\Session;
use think\Db;
use think\facade\Request; 
use fast\Random;

class Member extends Base
{
	public function index()
	{
		$list = MemberModel::order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
        $count = $list->count();
        $page = $list->render();
		$this->assign('list', $list);
		$this->assign('count', $count);
        $this->assign('page', $page);
		return $this->fetch();
	}

	public function add()
	{
		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Member.add');
            if(true !== $result){return $this->error($result);}
            $data['salt'] = Random::alnum();
            $data['password'] = md5(md5($data['password']) . $data['salt']);
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = '/static/assets/img/avatar.png'; 
            }
            $data['admin_id'] = session::get('admin_id');
            $data['mobile']      = intval($data['mobile']);
            $data['createtime'] = time();
            $data['updatetime'] = time();
            unset($data['password2']);
            unset($data['__token__']);
            if (MemberModel::where('username',$data['username'])->find()) return $this->error('该会员账号已存在！');
            if ($aid = MemberModel::insertGetId($data)) {
            	return $this->success('成功添加会员账号！');
            }else{
            	return $this->error('添加会员账号失败！');
            }
        }
		return $this->fetch();
	}

	public function edit()
	{
        $ids = Request::param('ids');
		$list = MemberModel::where('id', $ids)->find();
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Member.edit');
            if(true !== $result){return $this->error($result);}
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = $list['avatar'];
            }
            $data['mobile']      = intval($data['mobile']);
            $data['updatetime'] = time();
            unset($data['password2']);
            unset($data['__token__']);
            if ($data['username'] !== $list['username']) {
               if (MemberModel::where('username',$data['username'])->find()) return $this->error('该账号名已存在！');
            }
            if(MemberModel::where('id', $list['id'])->update($data)){
                return $this->success('编辑成功！','index');
            }else{
                return $this->error('编辑失败！');
            }
		}
		$this->assign('list', $list);
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
            if(MemberModel::where('id', $ids)->update($data)){
                return $this->success('编辑成功！','index');
            }else{
                return $this->error('编辑失败！');
            }
		}
		return $this->fetch();
	}
	public function show()
	{
        $ids = Request::param('ids');
		if ($ids = input('get.ids')) {
			$list = MemberModel::where('id', $ids)->find();
			$this->assign('list', $list);
		}else{
			return $this->success('查看失败！');
		}
		return $this->fetch();
	}

	public function true_index()
	{
        $ids = Request::param('ids');
		$list = MemberModel::onlyTrashed()->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
        $count = $list->count();
        $page = $list->render();
		$this->assign('list', $list);
		$this->assign('count', $count);
        $this->assign('page', $page);
		return $this->fetch();
	}

    public function del()
    {
        $ids = Request::param('ids');
        if ($ids) {
            if(MemberModel::destroy($ids)) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
        return $this->fetch();
    }

    public function true_del()
    {
        $ids = Request::param('ids');
        if ($ids) {
            if(Db::name('member')->where('id','in',$ids)->delete()) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
        return $this->fetch();
    }

    public function reduction()
    {
        $ids = Request::param('ids');
        if ($ids) {
            $article = MemberModel::onlyTrashed()->find($ids);
            if($article->restore()) return $this->success('还原成功！'); return $this->error('还原失败！');
        }
        return $this->fetch();
    }

}