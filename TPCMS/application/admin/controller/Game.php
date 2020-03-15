<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Channeltype as ChanneltypeModel;
use app\admin\model\Admin as AdminModel;
use app\admin\model\Game as GameModel;
use app\admin\model\Attachment as AttachmentModel;
use think\Db;
use think\facade\Request;
use think\facade\Session;

class Game extends Base
{
    protected $putongwenzhan = 1;
    protected $zhuanti       = 6;
    protected $extens        = 15;
    protected $game          = 16;

	public function index()
	{ 
		$list = GameModel::order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->game);
        $list_pid = ChanneltypeModel::where(['id'=>$this->game])->field('id,typename')->find();
		foreach ($list as $key => $val) {
			$list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
			$list[$key]['typename'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
		}
        if ($this->request->isPost()) {
        	$map = [
        		'title' 		 => input('post.title'),
        		'channeltype_id' => input('post.channeltype_id'),
        		'status' 		 => input('post.status')
        	];
        	if (empty(trim($map['title']))) {
        		unset($map['title']);
        	}
        	if ($map['channeltype_id'] =='-2') {
        		unset($map['channeltype_id']);
        	}
        	if ($map['status'] =='-2') {
        		unset($map['status']);
        	}
            $list = GameModel::where($map)->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
			foreach ($list as $key => $val) {
				$list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
				$list[$key]['typename'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
			}
        }
        $count = $list->count();
        $page = $list->render();
        $this->assign('list', $list); 
        $this->assign('count', $count);
        $this->assign('page', $page);
        $this->assign('list_pid', $list_pid);
        $this->assign('lis', $lis); 
		return $this->fetch();
	}

	public function add()
	{
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->game);
        $list_pid = ChanneltypeModel::where(['id'=>$this->game])->field('id,typename')->find();
        $this->assign('list_pid', $list_pid);
        $this->assign('lis', $lis); 
		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            if ( $files = request()->file('screenshots')) {
            	$data['screenshots'] = updateimg($files);
                $data['screenshots'] = implode(',', $data['screenshots']);
            }else{
                unset($data['screenshots']);
            } 
            $result = $this->validate($data, 'Game.add');
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
            if (GameModel::where('title',$data['title'])->find()) return $this->error('该游戏下载标题已存在！');
            if (GameModel::insertGetId($data)) {
            	return $this->success('成功发布游戏下载！');
            }else{
            	return $this->error('发布游戏下载失败！');
            }
        }
		return $this->fetch();
	}

	public function edit()
	{
        $ids = Request::param('ids');
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->game);
        $list_pid = ChanneltypeModel::where(['id'=>$this->game])->field('id,typename')->find();
        $list = GameModel::where('id',$ids)->find();
		$list['typename'] = ChanneltypeModel::where('id',$list['channeltype_id'])->value('typename');
        $arr_img = AttachmentModel::where('id','in',$list['screenshots'])->column('url');
        $this->assign('arr_img', $arr_img); 
		$this->assign('list', $list); 
        $this->assign('list_pid', $list_pid);
        $this->assign('lis', $lis);  
		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            if ( $files = request()->file('screenshots')) {
            	$data['screenshots'] = updateimg($files);
                $data['screenshots'] = implode(',', $data['screenshots']).','.$list['screenshots'];
            }else{
                $data['screenshots'] = $list['screenshots'];
            }
            $result = $this->validate($data, 'Game.add');
            if(true !== $result){return $this->error($result);}
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = '/static/assets/img/avatar.png'; 
            }
            $data['admin_id'] = session::get('admin_id');
            $data['updatetime'] = time();
            unset($data['__token__']);
            if ($data['title'] !== $list['title']) {
               if (GameModel::where('title',$data['title'])->find()) return $this->error('该游戏下载标题已存在！');
            }
            if (GameModel::where('id',$list['id'])->update($data)) {
            	return $this->success('成功发布游戏下载！');
            }else{
            	return $this->error('发布游戏下载失败！');
            }
        }
		
		return $this->fetch();
	}

    public function del()
	{
        $ids = Request::param('ids');
        if ($ids) {
            if(GameModel::destroy($ids)) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
		return $this->fetch();
	}

    public function true_index()
	{
        $ids = Request::param('ids');
		$list = GameModel::onlyTrashed()->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->game);
        $list_pid = ChanneltypeModel::where(['id'=>$this->game])->field('id,typename')->find();

		foreach ($list as $key => $val) {
			$list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
			$list[$key]['typename'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
		}
        if ($this->request->isPost()) {
        	$map = [
        		'title' 		 => input('post.title'),
        		'channeltype_id' => input('post.channeltype_id'),
        		'status' 		 => input('post.status')
        	];
        	if (empty(trim($map['title']))) {
        		unset($map['title']);
        	}
        	if ($map['channeltype_id'] =='-2') {
        		unset($map['channeltype_id']);
        	}
        	if ($map['status'] =='-2') {
        		unset($map['status']);
        	}
            $list = GameModel::onlyTrashed()->where($map)->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);

			foreach ($list as $key => $val) {
				$list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
				$list[$key]['typename'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
			}
        }
        $count = $list->count();
        $page = $list->render();
        $this->assign('list', $list); 
        $this->assign('count', $count);
        $this->assign('page', $page);
        $this->assign('list_pid', $list_pid);
        $this->assign('lis', $lis); 
		return $this->fetch();
	}

    public function true_del()
    {
        $ids = Request::param('ids');
        if ($ids) {
            if(Db::name('game')->where('id','in',$ids)->delete()) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
        return $this->fetch();
    }

    public function reduction()
    {
        $ids = Request::param('ids');
        if ($ids) {
            $article = GameModel::onlyTrashed()->find($ids);
            if($article->restore()) return $this->success('还原成功！'); return $this->error('还原失败！');
        }
        return $this->fetch();
    }

	
}