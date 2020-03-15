<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Banner as BannerModel;
use app\admin\model\Admin as AdminModel;
use think\Db;
use think\facade\Request;
use think\facade\Session;

class Banner extends Base
{
	public function index()
	{
		$list = BannerModel::order('biaoshi', 'asc')->paginate(10,false,['query'=>request()->param()]);
		foreach ($list as $key => $val) {
			$list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
		}
        if ($this->request->isPost()) {

        	$map = [
        		'biaoshi' 		 => input('post.biaoshi'),
        		'status' 		 => input('post.status')
        	];

        	if (empty(trim($map['biaoshi']))) {
        		unset($map['biaoshi']);
        	}
        	if ($map['status'] =='-2') {
        		unset($map['status']);
        	}
            $list = BannerModel::where($map)->order('biaoshi', 'asc')->paginate(10,false,['query'=>request()->param()]);

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
            $result = $this->validate($data, 'Banner.add');
            if(true !== $result){return $this->error($result);}
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                return $this->error('请上传Banner图！'); 
            }
            if ($file = request()->file('avatar2')) {
                $data['avatar2'] = upload($file);
            }else{
                return $this->error('请上传Banner缩略图！'); 
            }
            $data['admin_id'] = session::get('admin_id');
            $data['createtime'] = time();
            $data['updatetime'] = time();
            unset($data['__token__']);
            if (BannerModel::where('title',$data['title'])->find()) return $this->error('该Banner图标题已存在！');
            if ($aid = BannerModel::insertGetId($data)) {
            	return $this->success('成功发布Banner图！');
            }else{
            	return $this->error('发布Banner图失败！');
            }
        }
		return $this->fetch();
	}

	public function edit()
	{
        $ids = Request::param('ids');
        $list = BannerModel::where('id', $ids)->find();
		$this->assign('list', $list); 
		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Banner.edit');
            if(true !== $result){return $this->error($result);}
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = $list['avatar']; 
            }
            if ($file = request()->file('avatar2')) {
                $data['avatar2'] = upload($file);
            }else{
                $data['avatar2'] = $list['avatar2']; 
            }
            $data['admin_id'] = session::get('admin_id');
            $data['updatetime'] = time();
            unset($data['__token__']);
            if ($list['title'] !== $data['title']) {
            	if (BannerModel::where('title',$data['title'])->find()) return $this->error('该Banner图标题已存在！');
            }
            if (BannerModel::where('id',$list['id'])->update($data)) {
            	return $this->success('成功编辑Banner图！');
            }else{
            	return $this->error('编辑Banner图失败！');
            }
        }
		return $this->fetch();
	}

	public function del()
	{
        $ids = Request::param('ids');
		if ($ids) {
            if(Db::name('banner')->where('id','in',$ids)->delete()) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
		return $this->fetch();
	}




}