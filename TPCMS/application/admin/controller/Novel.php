<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Novel as NovelModel;
use app\admin\model\Category as CategoryModel;
use app\admin\model\NovelChapter as NovelChapterModel;
use app\admin\model\Tags as TagsModel;
use think\Db;
use think\facade\Request;
use think\facade\Session;

class Novel extends Base
{
	public function index()
	{
        $lis  = CategoryModel::where(['status'=>'normal'])->field('id,pid,title')->order('id', 'asc')->select();
        $lis = tree($lis,0,0,'--');
        $list = NovelModel::order('id', 'desc')->paginate(5,false,['query'=>request()->param()]);
        if ($this->request->isPost()) {
            $map = [ 
                'category' => input('post.category'),
                'status'         => input('post.status')
            ];
            if ($map['category'] =='-2') {
                unset($map['category']);
            }
            if ($map['status'] =='-2') {
                unset($map['status']);
            }
            if (empty(trim(input('post.title')))) {
                $list = NovelModel::where($map)->order('id', 'desc')->paginate(5,false,['query'=>request()->param()]);
            }else{
                $list = NovelModel::where($map)->where('title','like','%'.input('post.title').'%')->order('id', 'desc')->paginate(5,false,['query'=>request()->param()]);
            }
        }
        foreach ($list as $k => $va) {
            $va['category'] = CategoryModel::where('id',$va['category'])->value('title');
        }
        $count = $list->count();
        $page = $list->render();
        $this->assign('lis', $lis);
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('page', $page);
		return $this->fetch();
	}
	public function add()
	{
        $lis  = CategoryModel::where(['status'=>'normal'])->field('id,pid,title')->order('id', 'asc')->select();
        $lis = tree($lis,0,0,'--');
        $this->assign('lis', $lis);
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Category.add');
            if(true !== $result){return $this->error($result);}
            if (isset($data['flags'])) {
                $data['flags'] = implode(',', $data['flags']);
            }
            $data['titles'] = titles($data['title']);
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = '/static/assets/img/avatar.png'; 
            }
            $data['createtime'] = time();
            $data['updatetime'] = time(); 
            $data['latelytime'] = time();
            unset($data['__token__']);
            if (NovelModel::where('title',$data['title'])->find()) return json('该书籍名称已存在！');
            if ($aid = NovelModel::insertGetId($data)) {
                TagsModel::InsertTags($data['tags'],$aid,$data['category']);
                return json('成功发布书籍！');
            }else{
                return json('发布书籍失败！');
            }
        }
		return $this->fetch();
	}

	public function edit()
	{
        $ids = Request::param('ids');
        $lis  = CategoryModel::where(['status'=>'normal'])->field('id,pid,title')->order('id', 'asc')->select();
        $lis = tree($lis,0,0,'--');
        $this->assign('lis', $lis);
        $list = NovelModel::find($ids);
        $list_pid = CategoryModel::where(['id'=>$list['category']])->field('id,title')->find();
        $list['tags'] = TagsModel::GetTags($ids);
        $this->assign('list', $list); 
        $this->assign('list_pid', $list_pid);
        $this->assign('lis', $lis); 
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Category.add');
            if(true !== $result){return $this->error($result);}
            if (isset($data['flags'])) {
                $data['flags'] = implode(',', $data['flags']);
            }else{
                $data['flags'] = $list['flags'];
            }
            $data['titles'] = titles($data['title']);
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = $list['avatar']; 
            }
            $data['updatetime'] = time();
            $data['latelytime'] = time();
            unset($data['__token__']);
            if ($list['title'] !== $data['title']) {
                if (NovelModel::where('title',$data['title'])->find()) return json('该书籍名称已存在！');
            }
            if (NovelModel::where('id',$list['id'])->update($data)) {
                TagsModel::UpIndexKey($data['tags'],$list['id'],$data['category']);
                return json('成功编辑书籍！');
            }else{
                return json('编辑书籍失败！');
            }
        }
		return $this->fetch();
	}	

    public function del()
    {
        $ids = Request::param('ids');
        if ($ids) {
            if (NovelChapterModel::where('novel_id',$ids)->find()) {
                return json('删除失败，该书籍下还含有书籍章节，你不能删除！');
            }
            if(NovelModel::destroy($ids)) return $this->success('删除成功！'); return $this->error('删除失败！');
        }
        return $this->fetch();
    }

    public function true_index()
    {
        $lis  = CategoryModel::where(['status'=>'normal'])->field('id,pid,title')->order('id', 'desc')->select();
        $lis = tree($lis,0,0,'--');
        $list = NovelModel::onlyTrashed()->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
        foreach ($list as $k => $va) {
            $va['category'] = CategoryModel::where('id',$va['category'])->value('title');
        }
        if ($this->request->isPost()) {
            $map = [ 
                'category' => input('post.category'),
                'status'         => input('post.status')
            ];
            if ($map['category'] =='-2') {
                unset($map['category']);
            }
            if ($map['status'] =='-2') {
                unset($map['status']);
            }
            if (empty(trim(input('post.title')))) {
                $list = NovelModel::onlyTrashed()->where($map)->order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
            }else{
                $list = NovelModel::onlyTrashed()->where($map)->where('title','like','%'.input('post.title').'%')->order('id', 'desc')->paginate(10);
            }
            foreach ($list as $k => $va) {
                $va['category'] = CategoryModel::where('id',$va['category'])->value('title');
            }
        }
        $count = $list->count();
        $page = $list->render();
        $this->assign('lis', $lis);
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function true_del()
    {
        $ids = Request::param('ids');
        return json('已做屏蔽，如需要请在 Novel/true_del 注释 "return" ！');die;
        if ($ids) {
            if(Db::name('novel')->where('id','in',$ids)->delete()) return $this->success('删除成功！'); return $this->error('删除失败！');
        } 
        return $this->fetch();
    }

    public function reduction()
    {
        $ids = Request::param('ids');
        if ($ids) {
            $article = NovelModel::onlyTrashed()->find($ids);
            if($article->restore()) return $this->success('还原成功！'); return $this->error('还原失败！');
        }
        return $this->fetch();
    }


}