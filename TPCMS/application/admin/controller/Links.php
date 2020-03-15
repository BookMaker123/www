<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Links as LinksModel;
use app\admin\model\Admin as AdminModel;
use think\Db;
use think\facade\Request;
use think\facade\Session;

class Links extends Base
{
	public function index()
	{
		$list = LinksModel::order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
		foreach ($list as $key => $val) {
			$list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
		}
        if ($this->request->isPost()) {
        	$map = [
        		'title' 		 => input('post.title'),
        		'status' 		 => input('post.status')
        	];
        	if (empty(trim($map['title']))) {
        		unset($map['title']);
        	}
        	if ($map['status'] =='-2') {
        		unset($map['status']);
        	}
            $list = LinksModel::where($map)->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
			foreach ($list as $key => $val) {
				$list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
			}
        }
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
            $result = $this->validate($data, 'Links.add');
            if(true !== $result){return $this->error($result);}
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = '/static/assets/img/avatar.png'; 
            }
            $data['admin_id'] = session::get('admin_id');
            $data['createtime'] = time();
            $data['updatetime'] = time();
            unset($data['__token__']);
            if (LinksModel::where('title',$data['title'])->find()) return $this->error('该友情链接已存在！');
            if ($aid = LinksModel::insertGetId($data)) {
            	return $this->success('成功发布友情链接！');
            }else{
            	return $this->error('发布友情链接失败！');
            }
        }
		return $this->fetch();
	}

	public function edit()
	{
        $ids = Request::param('ids');
        $list = LinksModel::where('id', $ids)->find();
		$this->assign('list', $list); 
		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Links.edit');
            if(true !== $result){return $this->error($result);}
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = $list['avatar']; 
            }
            $data['admin_id'] = session::get('admin_id');
            $data['updatetime'] = time();
            unset($data['__token__']);
            if ($list['title'] !== $data['title']) {
            	if (LinksModel::where('title',$data['title'])->find()) return $this->error('该友情链接已存在！');
            }
            if (LinksModel::where('id',$list['id'])->update($data)) {
            	return $this->success('成功编辑友情链接！');
            }else{
            	return $this->error('编辑友情链接失败！');
            }
        }
		return $this->fetch();
	}

	public function del()
	{
        $ids = Request::param('ids');
		if ($ids) {
            if(Db::name('links')->where('id','in',$ids)->delete()) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
		return $this->fetch();
	}




}