<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Notifylog as NotifylogModel;
use app\admin\model\Member as MemberModel;
use think\Db;
use think\facade\Request;
use think\facade\Session;

class Notifylog extends Base
{
	public function index()
	{
		$list = NotifylogModel::order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
        if ($this->request->isPost()) {
        	$map = [
        		'trade_no' => input('post.trade_no'),
        	];
        	if (empty(trim($map['trade_no']))) {
        		unset($map['trade_no']);
        	}
            $list = NotifylogModel::where($map)->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);

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
            if(Db::name('notify_url_log')->where('id','in',$ids)->delete()) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
		return $this->fetch();
	}

}