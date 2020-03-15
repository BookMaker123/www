<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Channeltype as ChanneltypeModel;
use app\admin\model\Special as SpecialModel;
use app\admin\model\Admin as AdminModel;
use app\admin\model\Game as GameModel;
use app\admin\model\Article as ArticleModel; 
use think\Db;
use think\facade\Request;
use think\facade\Session;

class Special extends Base
{
    protected $putongwenzhan = 1;
    protected $zhuanti       = 6;
    protected $extens        = 15;
    protected $game          = 16;

	public function index()
	{
		$list = SpecialModel::order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->zhuanti);
        $list_pid = ChanneltypeModel::where(['id'=>$this->zhuanti])->field('id,typename')->find();

		foreach ($list as $key => $val) {
			$list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
			$list[$key]['channeltype_name'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
		}
        if ($this->request->isPost()) {
            $map = [
                'typename'          => input('post.typename'),
                'channeltype_id' => input('post.channeltype_id'),
                'status'         => input('post.status')
            ];
            if (empty(trim($map['typename']))) {
                unset($map['typename']);
            }
            if ($map['channeltype_id'] =='-2') {
                unset($map['channeltype_id']);
            }
            if ($map['status'] =='-2') {
                unset($map['status']);
            }
            $list = SpecialModel::where($map)->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
            foreach ($list as $key => $val) {
                $list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
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
        $lis = recursion_channeltype($lis,$this->zhuanti);
        $list_pid = ChanneltypeModel::where(['id'=>$this->zhuanti])->field('id,typename')->find();
        $this->assign('list_pid', $list_pid);
        $this->assign('lis', $lis); 
		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Special.add');
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
            if (SpecialModel::where('typename',$data['typename'])->find()) return $this->error('该专题标题已存在！');
            if (SpecialModel::insertGetId($data)) {
            	return $this->success('成功发布专题！');
            }else{
            	return $this->error('发布专题失败！');
            }
        }
		return $this->fetch();
	}

	public function edit()
	{
        $ids = Request::param('ids');
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->zhuanti);
        $list_pid = ChanneltypeModel::where(['id'=>$this->zhuanti])->field('id,typename')->find();
        $list = SpecialModel::where('id',$ids)->find();
		$list['channeltype_name'] = ChanneltypeModel::where('id',$list['channeltype_id'])->value('typename');
		$this->assign('list', $list); 
        $this->assign('list_pid', $list_pid);
        $this->assign('lis', $lis); 
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Special.add');
            if(true !== $result){return $this->error($result);}
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = $list['avatar']; 
            }
            $data['admin_id'] = session::get('admin_id');
            $data['updatetime'] = time();
            unset($data['__token__']);
            if ($list['typename'] !== $data['typename']) {
            	if (SpecialModel::where('typename',$data['typename'])->find()) return $this->error('该文章标题已存在！');
            }
            if (SpecialModel::where('id',$list['id'])->update($data)) {
            	return $this->success('成功编辑专题！');
            }else{
            	return $this->error('编辑专题失败！');
            }
        }

		return $this->fetch();
	}

    public function del()
    {
        $ids = Request::param('ids');
        if ($ids) {
            if(SpecialModel::destroy($ids)) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
        return $this->fetch();
    }

    public function true_index()
    {
        $ids = Request::param('ids');
		$list = SpecialModel::onlyTrashed()->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->zhuanti);
        $list_pid = ChanneltypeModel::where(['id'=>$this->zhuanti])->field('id,typename')->find();
		foreach ($list as $key => $val) {
			$list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
			$list[$key]['channeltype_name'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
		}
        if ($this->request->isPost()) {

            $map = [
                'typename'          => input('post.typename'),
                'channeltype_id' => input('post.channeltype_id'),
                'status'         => input('post.status')
            ];
            if (empty(trim($map['typename']))) {
                unset($map['typename']);
            }
            if ($map['channeltype_id'] =='-2') {
                unset($map['channeltype_id']);
            }
            if ($map['status'] =='-2') {
                unset($map['status']);
            }
            $list = SpecialModel::onlyTrashed()->where($map)->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
            foreach ($list as $key => $val) {
                $list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
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
            if(Db::name('spectype')->where('id','in',$ids)->delete()) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
        return $this->fetch();
    }

    public function reduction()
    {
        $ids = Request::param('ids');
        if ($ids) {
            $article = SpecialModel::onlyTrashed()->find($ids);
            if($article->restore()) return $this->success('还原成功！'); return $this->error('还原失败！');
        }
        return $this->fetch();
    }

    public function content_select_list($f='')
    {
        switch ($f) {
            case 'form1.arcid1':
                $f = 'form1.arcid1';
                break;

            case 'form1.arcid2':
                $f = 'form1.arcid2';
                break;

            case 'form1.arcid3':
                $f = 'form1.arcid3';
                break;

            case 'form1.arcid4':
                $f = 'form1.arcid4';
                break;
            
            default:
                $f = '提交错误！';
                break;
        }
        $map = ['status'=>1];
        $list = GameModel::where($map)->order('id', 'desc')->paginate(20);
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->game);
        $list_pid = ChanneltypeModel::where(['id'=>$this->game])->field('id,typename')->find();
        foreach ($list as $key => $val) {
            $list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
            $list[$key]['typename'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
        }
        $count = $list->count();
        $page = $list->render();
        $this->assign('lis', $lis); 
        $this->assign('list', $list); 
        $this->assign('count', $count);
        $this->assign('page', $page);
        $this->assign('f', $f);
        return $this->fetch();
    }

    public function content_select_list2($f='')
    {
        switch ($f) {
            case 'form1.arcid1':
                $f = 'form1.arcid1';
                break;

            case 'form1.arcid2':
                $f = 'form1.arcid2';
                break;

            case 'form1.arcid3':
                $f = 'form1.arcid3';
                break;

            case 'form1.arcid4':
                $f = 'form1.arcid4';
                break;
            
            default:
                $f = '提交错误！';
                break;
        }
        $map = ['status'=>1];
        $list = ArticleModel::where($map)->order('id', 'desc')->paginate(20);
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->putongwenzhan);
        $list_pid = ChanneltypeModel::where(['id'=>$this->putongwenzhan])->field('id,typename')->find();
        foreach ($list as $key => $val) {
            $list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
            $list[$key]['typename'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
        }
        $count = $list->count();
        $page = $list->render();
        $this->assign('lis', $lis); 
        $this->assign('list', $list); 
        $this->assign('count', $count);
        $this->assign('page', $page);
        $this->assign('f', $f); 
        return $this->fetch();
    }

}



















 ?>