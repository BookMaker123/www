<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Paylog as PaylogModel;
use app\admin\model\Member as MemberModel;
use think\Db;
use think\facade\Request;
use think\facade\Session;

class Paylog extends Base
{
	public function index()
	{
		$list = PaylogModel::order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
		foreach ($list as $key => $val) {
			$list[$key]['username'] = MemberModel::where('id',$val['user_id'])->value('username');
		}
        if ($this->request->isPost()) {
        	$map = [
        		'title' 		 => input('post.title'),
        		'status' 		 => input('post.status'),
        	];
        	if (empty(trim($map['title']))) {
        		unset($map['title']);
        	}
        	if ($map['status'] =='-2') {
        		unset($map['status']);
        	}
            $list = PaylogModel::where($map)->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);

			foreach ($list as $key => $val) {
				$list[$key]['username'] = MemberModel::where('id',$val['user_id'])->value('username');
			}
        }
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
            if(PaylogModel::where('id','in',$ids)->delete()) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
		return $this->fetch();
	}

}