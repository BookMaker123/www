<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Article as ArticleModel;
use app\admin\model\Channeltype as ChanneltypeModel;
use app\admin\model\System as SystemModel;


class Guanyu extends Base
{
	public function index()
	{
		$list = SystemModel::where('id',1)->value('guanyuwomen');
		$this->assign('list', $list); 
        if ($this->request->isPost()) {
        	if (SystemModel::where('id',1)->update(['guanyuwomen'=>input('post.guanyuwomen')])) {
            	return $this->success('编辑成功！');
            }else{
            	return $this->error('编辑失败！');
            }
        }
		return $this->fetch();
	}

	public function kefu()
	{
		$list = SystemModel::where('id',1)->value('zaixiankefu');
		$this->assign('list', $list); 
        if ($this->request->isPost()) {
        	if (SystemModel::where('id',1)->update(['zaixiankefu'=>input('post.zaixiankefu')])) {
            	return $this->success('编辑成功！');
            }else{
            	return $this->error('编辑失败！');
            }
        }
		return $this->fetch();
	}

	public function hezuo()
	{
		$list = SystemModel::where('id',1)->value('shangwuhezuo');
		$this->assign('list', $list); 
        if ($this->request->isPost()) {
        	if (SystemModel::where('id',1)->update(['shangwuhezuo'=>input('post.shangwuhezuo')])) {
            	return $this->success('编辑成功！');
            }else{
            	return $this->error('编辑失败！');
            }
        }
		return $this->fetch();
	}

	public function notice()
	{
		$list = SystemModel::where('id',1)->value('yonhuxieyi');
		$this->assign('list', $list); 
        if ($this->request->isPost()) {
        	if (SystemModel::where('id',1)->update(['yonhuxieyi'=>input('post.yonhuxieyi')])) {
            	return $this->success('编辑成功！');
            }else{
            	return $this->error('编辑失败！');
            }
        }
		return $this->fetch();
	}

	public function mianze()
	{
		$list = SystemModel::where('id',1)->value('mianzeshenming');
		$this->assign('list', $list); 
        if ($this->request->isPost()) {
        	if (SystemModel::where('id',1)->update(['mianzeshenming'=>input('post.mianzeshenming')])) {
            	return $this->success('编辑成功！');
            }else{
            	return $this->error('编辑失败！');
            }
        }
		return $this->fetch();
	}

	public function ditu()
	{
		$list = SystemModel::where('id',1)->value('ditus');
		$this->assign('list', $list); 
        if ($this->request->isPost()) {
        	if (SystemModel::where('id',1)->update(['ditus'=>input('post.ditus')])) {
            	return $this->success('编辑成功！');
            }else{
            	return $this->error('编辑失败！');
            }
        }
		return $this->fetch();
	}




}