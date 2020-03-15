<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Channeltype as ChanneltypeModel;
use app\admin\model\Extens as ExtensModel;
use app\admin\model\Member as MemberModel;
use app\admin\model\Messages as MessagesModel;
use app\admin\model\MessageStatus as MessageStatusModel;
use app\admin\model\Admin as AdminModel;
use think\Db; 
use think\facade\Request;
use think\facade\Session;

class Extens extends Base
{
	protected $putongwenzhan = 1;
	protected $zhuanti       = 6;
	protected $extens        = 15;
    protected $game          = 16;

	public function index()
	{
		$list = ExtensModel::order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = digui($lis,$this->extens);
        $list_pid = ChanneltypeModel::where(['id'=>$this->extens])->field('id,typename')->find();
		foreach ($list as $key => $val) {
			$list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
            $list[$key]['username2'] = MemberModel::where('id',$val['user_id'])->value('username');
			$list[$key]['channeltype_name'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
		}
        if ($this->request->isPost()) {
            $map = [
                'title'          => input('post.title'),
                'channeltype_id' => input('post.channeltype_id'),
                'status'         => input('post.status')
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
            $list = ExtensModel::where($map)->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
            foreach ($list as $key => $val) {
                $list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
                $list[$key]['username2'] = AdminModel::where('id',$val['user_id'])->value('username');
                $list[$key]['channeltype_name'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
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
        $lis = digui($lis,$this->extens);
        $list_pid = ChanneltypeModel::where(['id'=>$this->extens])->field('id,typename')->find();
        $this->assign('list_pid', $list_pid);
        $this->assign('lis', $lis); 
		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Extens.add');
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
            if (ExtensModel::where('title',$data['title'])->find()) return $this->error('该推广信息标题已存在！');
            if (ExtensModel::insertGetId($data)) {
            	return $this->success('成功发布推广信息！');
            }else{
            	return $this->error('发布推广信息失败！');
            }
        }
		return $this->fetch();
	}

	public function edit()
	{
        $ids = Request::param('ids');
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = digui($lis,$this->extens);
        $list_pid = ChanneltypeModel::where(['id'=>$this->extens])->field('id,typename')->find();
        $list = ExtensModel::where('id',$ids)->find();
		$list['channeltype_name'] = ChanneltypeModel::where('id',$list['channeltype_id'])->value('typename');
		$this->assign('list', $list); 
        $this->assign('list_pid', $list_pid);
        $this->assign('lis', $lis); 
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Extens.edit');
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
            	if (ExtensModel::where('title',$data['title'])->find()) return $this->error('该推广信息标题已存在！');
            }
            if (ExtensModel::where('id',$list['id'])->update($data)) {
                if (!empty($list['user_id']) && $data['status'] == 1 || $data['status'] == 2) {
                    if ($data['status'] == 1) {
                        $data['status'] = '审核已通过';
                    }elseif ($data['status'] == 2) {
                        $data['status'] = '审核未通过';
                    }
                    $data2 = [
                        'type'         => 1,
                        'admin_id'     => session::get('admin_id'),
                        'user_id'      => $list['user_id'],
                        'title'        => '系统通知',
                        'content'      => '尊敬的用户您好：您发布的<span style="color:red;">“ '.$list['title'].' ”</span>'.$data['status'].'！',
                        'createtime'   => time(),
                        'updatetime'   => time(),
                    ];
                    $message_id = MessagesModel::insertGetId($data2);
                    $data3 = [
                        'message_id'   => $message_id,
                        'user_id'      => $list['user_id'],
                        'createtime'   => time(),
                        'updatetime'   => time(),
                    ];
                    MessageStatusModel::insertGetId($data3);
                }
            	return $this->success('成功编辑推广信息！');
            }else{
            	return $this->error('编辑推广信息失败！');
            }
        }

		return $this->fetch();
	}

    public function del()
    {
        $ids = Request::param('ids');
        if ($ids) {
            if(ExtensModel::destroy($ids)) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
        return $this->fetch();
    }

    public function true_index()
    {
        $ids = Request::param('ids');
		$list = ExtensModel::onlyTrashed()->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->extens);
        $list_pid = ChanneltypeModel::where(['id'=>$this->extens])->field('id,typename')->find();
		foreach ($list as $key => $val) {
			$list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
            $list[$key]['username2'] = MemberModel::where('id',$val['user_id'])->value('username');
			$list[$key]['channeltype_name'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
		}
        if ($this->request->isPost()) {
            $map = [
                'title'          => input('post.title'),
                'channeltype_id' => input('post.channeltype_id'),
                'status'         => input('post.status')
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
            $list = ExtensModel::onlyTrashed()->where($map)->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
            foreach ($list as $key => $val) {
                $list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
                $list[$key]['username2'] = AdminModel::where('id',$val['user_id'])->value('username');
                $list[$key]['channeltype_name'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
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
            if(Db::name('extens')->where('id','in',$ids)->delete()) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
        return $this->fetch();
    }

    public function reduction()
    {
        $ids = Request::param('ids');
        if ($ids) {
            $article = ExtensModel::onlyTrashed()->find($ids);
            if($article->restore()) return $this->success('还原成功！'); return $this->error('还原失败！');
        }
        return $this->fetch();
    }









}



















 ?>