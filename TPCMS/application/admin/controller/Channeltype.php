<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Channeltype as ChanneltypeModel;
use think\Db;
use think\facade\Request;

class Channeltype extends Base
{
	public function index()
	{
        $list  = ChanneltypeModel::order('id', 'asc')->paginate(8,false,['query'=>request()->param()]);
        $count = $list->total();
        $page = $list->render();

        if ($this->request->isPost()) {
            $list  = ChanneltypeModel::where(['typename'=>input('post.typename')])->order('id', 'asc')->paginate(8,false,['query'=>request()->param()]);
            $count = $list->total();
            $page = $list->render();
        }
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('page', $page);
		return $this->fetch();
	}

	public function add()
	{
        $list = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($list,0,'|--');
        $this->assign('lis', $lis);
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Channeltype.add');
            if(true !== $result){return $this->error($result);}
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = '/static/assets/img/avatar.png'; 
            }
            $data['createtime'] = time();
            $data['updatetime'] = time();
			if(ChanneltypeModel::create($data)) return $this->success('添加成功！','index');
        }
		return $this->fetch();
	}

	public function edit()
	{
        $ids = Request::param('ids');
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,0,'|--');
		$list = ChanneltypeModel::where(['id'=>$ids])->find();
        $list_pid = ChanneltypeModel::where(['id'=>$list['pid']])->field('id,typename')->find();
        $this->assign('list_pid', $list_pid);
        $this->assign('list', $list);
        $this->assign('lis', $lis);
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Channeltype.add');
            if(true !== $result){return $this->error($result);}
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = $list['avatar'];
            }
            $data['updatetime'] = time();
			unset($data['__token__']);
            if(ChanneltypeModel::where('id', $list['id'])->update($data)){
                return $this->success('编辑成功！','index');
            }else{
                return $this->error('编辑失败！');
            }
        }
		return $this->fetch();
	}

	public function del()
	{
        $ids = Request::param('ids');
		if ($ids) {
           if (ChanneltypeModel::where(['pid'=>$ids])->find()) {
               return $this->error('删除失败，该栏目下还含有子类！');
           }else{
               if(ChanneltypeModel::where('id',$ids)->delete()) return $this->success('删除成功！'); return $this->error('删除失败！');
           }
        }
		return $this->fetch();
	}
	

}