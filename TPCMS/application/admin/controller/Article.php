<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Admin as AdminModel;
use app\admin\model\Channeltype as ChanneltypeModel;
use app\admin\model\Article as ArticleModel;
use app\admin\model\Tags as TagsModel;
use think\Db;
use think\facade\Request;
use think\facade\Session;

class Article extends Base
{	
    protected $putongwenzhan = 1;
    protected $zhuanti       = 6;
    protected $extens        = 15;
    protected $game          = 16;

	public function index()
	{
        $ids = Request::param('ids');
		$list = ArticleModel::order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->putongwenzhan);
        $list_pid = ChanneltypeModel::where(['id'=>$this->putongwenzhan])->field('id,typename')->find();
        

		foreach ($list as $key => $val) {
			$list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
			$list[$key]['typename'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
		}

        if ($this->request->isPost()) {
        	$map = [ 'channeltype_id' => input('post.channeltype_id'),'status' 		 => input('post.status')];
        	if ($map['channeltype_id'] =='-2') {
        		unset($map['channeltype_id']);
        	}
        	if ($map['status'] =='-2') {
        		unset($map['status']);
        	}
            if (empty(trim(input('post.title')))) {
                $list = ArticleModel::where($map)->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
            }else{
                $list = ArticleModel::where($map)->where('title','like','%'.input('post.title').'%')->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
            }
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
        $lis = recursion_channeltype($lis,$this->putongwenzhan);
        $list_pid = ChanneltypeModel::where(['id'=>$this->putongwenzhan])->field('id,typename')->find();
        $this->assign('list_pid', $list_pid);
        $this->assign('lis', $lis); 
		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Article.add');
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
            if (ArticleModel::where('title',$data['title'])->find()) return $this->error('该文章标题已存在！');
            if ($aid = ArticleModel::insertGetId($data)) {
            	TagsModel::InsertTags($data['tags'],$aid,$data['channeltype_id']);
            	return $this->success('成功发布文章！');
            }else{
            	return $this->error('发布文章失败！');
            }
        }

		return $this->fetch();
	}

	public function edit()
	{
        $ids = Request::param('ids');
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->putongwenzhan);
        $list_pid = ChanneltypeModel::where(['id'=>$this->putongwenzhan])->field('id,typename')->find();
        $list = ArticleModel::where('id',$ids)->find();
		$list['typename'] = ChanneltypeModel::where('id',$list['channeltype_id'])->value('typename');
		$list['tags'] = TagsModel::GetTags($ids);
		$this->assign('list', $list); 
        $this->assign('list_pid', $list_pid);
        $this->assign('lis', $lis); 
		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Article.add');
            if(true !== $result){return $this->error($result);}
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = '/static/assets/img/avatar.png';
            }
            $data['admin_id'] = session::get('admin_id');
            $data['updatetime'] = time();
            unset($data['__token__']);
            if ($list['title'] !== $data['title']) {
            	if (ArticleModel::where('title',$data['title'])->find()) return $this->error('该文章标题已存在！');
            }
            if (ArticleModel::where('id',$list['id'])->update($data)) {
            	TagsModel::UpIndexKey($data['tags'],$list['id'],$data['channeltype_id']);
            	return $this->success('成功编辑文章！');
            }else{
            	return $this->error('编辑文章失败！');
            }
        }
		return $this->fetch();
	}

    public function del()
    {
        $ids = Request::param('ids');
        if ($ids) {
            if(ArticleModel::destroy($ids)) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
        return $this->fetch();
    }

    public function true_index($ids='')
    {
        $list = ArticleModel::onlyTrashed()->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
        $lis = ChanneltypeModel::where(['status'=>'normal'])->field('id,pid,typename,arcurl')->order('id', 'asc')->select();
        $lis = recursion_channeltype($lis,$this->putongwenzhan);
        $list_pid = ChanneltypeModel::where(['id'=>$this->putongwenzhan])->field('id,typename')->find();
        foreach ($list as $key => $val) {
            $list[$key]['username'] = AdminModel::where('id',$val['admin_id'])->value('username');
            $list[$key]['typename'] = ChanneltypeModel::where('id',$val['channeltype_id'])->value('typename');
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
            $list = ArticleModel::onlyTrashed()->where($map)->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
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
            if(Db::name('article')->where('id','in',$ids)->delete()) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
        return $this->fetch();
    }

    public function reduction()
    {
        $ids = Request::param('ids');
        if ($ids) {
            $article = ArticleModel::onlyTrashed()->find($ids);
            if($article->restore()) return $this->success('还原成功！'); return $this->error('还原失败！');
        }
        return $this->fetch();
    }
    
}