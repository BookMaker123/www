<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Category as CategoryModel;
use app\admin\model\Novel as NovelModel;
use think\facade\Request; 

class Category extends Base
{
	public function index()
	{
		$list  = CategoryModel::order('id', 'asc')->select();
		if ($this->request->isPost()) {
            $list  = CategoryModel::where(['title'=>input('post.title')])->order('id', 'asc')->select();
            return $this->fetch();
        }
        $list = tree($list,0,0,'&nbsp;&nbsp;&nbsp;&nbsp;');
        $count = count($list); 
        $this->assign('list', $list);
        $this->assign('count', $count);
		return $this->fetch();
	}

	public function add()
	{
        $list = CategoryModel::where(['status'=>'normal'])->field('id,pid,title')->order('id', 'asc')->select();
        $lis = recursion_menu($list,0,'|--');
        $this->assign('lis', $lis);
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Category.add');
            if(true !== $result){return $this->error($result);}
            $data['titley'] = titles($data['title']);
            if ($file = request()->file('avatar')) {
                $data['avatar'] = upload($file);
            }else{
                $data['avatar'] = '/static/assets/img/avatar.png'; 
            }
            if ($file = request()->file('avatar2')) {
                $data['avatar2'] = upload($file);
            }else{
                $data['avatar2'] = '/static/assets/img/avatar.png'; 
            }
            $data['createtime'] = time();
            $data['updatetime'] = time();
            unset($data['__token__']);
            if(CategoryModel::create($data)) return json('添加成功！'); return json('添加失败！');
        }

		return $this->fetch();
	}

	public function edit()
	{
        $ids = Request::param('ids');
        $list = CategoryModel::where('id',$ids)->find();
        $pidname = CategoryModel::where('id',$list['pid'])->field('id,pid,title')->find();
        $lis = CategoryModel::where(['status'=>'normal'])->field('id,pid,title')->order('id', 'asc')->select();
        $lis = recursion_menu($lis,0,'|--');
        $this->assign('list', $list);
        $this->assign('pidname', $pidname);
        $this->assign('lis', $lis);
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Category.edit');
            if(true !== $result){return $this->error($result);}
            $data['titley'] = titles($data['title']);
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
            unset($data['__token__']);
            $data['updatetime'] = time();
            if(CategoryModel::where('id',$list['id'])->update($data)) return json('编辑成功！'); return json('编辑失败！');

        }
		return $this->fetch();
	}

	public function del()
	{
        $ids = Request::param('ids');
        if ($ids) {
            if (NovelModel::where('category',$ids)->find()) {
                return json('删除失败，该栏目下还含有书籍，你不能删除！');
            }else{
                if (CategoryModel::where('pid',$ids)->find()) {
                    return json('删除失败，该栏目下还含有子级栏目！');
                }else{
                    if(CategoryModel::destroy($ids)){
                        return json('删除成功！');
                    }else{
                        return json('删除失败！');
                    }
                }
            }
        }
		return $this->fetch();
	}





}







