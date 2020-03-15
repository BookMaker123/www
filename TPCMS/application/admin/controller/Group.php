<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\AuthGroup as AuthGroupModel;
use app\admin\model\AuthRule as AuthRuleModel;
use think\facade\Request;
use think\Db;

class Group extends Base
{ 
    public function index()
    {
        $list = AuthGroupModel::order('id', 'asc')->select();
        $count = $list->count();
        $list = recursion($list,0,'|--');
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }

    public function add()
    {
    	$list = AuthGroupModel::where('status','normal')->field('id,pid,name,rules')->order('id', 'asc')->select();
		$lis = recursion($list,0,'|--');
        $authlist = AuthRuleModel::where(['status'=>'normal'])->field('id,pid,name,title')->order('id', 'asc')->select();
        $authlist = digui($authlist);
    	$this->assign('lis', $lis);
        $this->assign('authlist', $authlist);
    	if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Group.add');
            if(true !== $result){return $this->error($result);}
            $data['rules'] = implode(',', $data['rules']);
            $data['createtime'] = time();
            $data['updatetime'] = time();
            if(!AuthGroupModel::create($data)) return $this->success('添加成功！','index');
    	}
        return $this->fetch();
    }

    public function edit()
    {
        $ids = Request::param('ids');
        $lis = AuthGroupModel::where('status','normal')->field('id,pid,name,rules')->order('id', 'asc')->select();
        $lis = recursion($lis,0,'|--');
        $list = AuthGroupModel::where('id',$ids)->find();
        $list_pid = AuthGroupModel::where('id',$list['pid'])->field('id,pid,name')->find();
        $authlist = AuthRuleModel::where(['status'=>'normal'])->field('id,pid,name,title')->order('id', 'asc')->select();
        $authlist = digui($authlist);
        $authlist_id = AuthRuleModel::where('status','normal')->column('id');
        $list_id = explode(',', $list['rules']);
        $authlist_id = array_intersect($authlist_id,$list_id);
        $authlist_id = implode(',', $authlist_id);
        foreach ($authlist as $kk=> $vali){
            $authlist[$kk]['chek'] = $authlist_id;
        }
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Group.edit');
            if(true !== $result){return $this->error($result);}
            $data['rules'] = implode(',', $data['rules']);
            unset($data['__token__']);
            $data['updatetime'] = time();
            if ($data['name'] !== $list['name']) {
               if (AuthGroupModel::where('name',$data['name'])->find()) return $this->error('该角色名称已存在！');
            }
            if(!AuthGroupModel::where('id',$list['id'])->update($data)) return $this->success('添加成功！','index');
        }
        $this->assign('lis', $lis);
        $this->assign('list', $list);
        $this->assign('list_pid', $list_pid);
        $this->assign('authlist', $authlist);
        return $this->fetch();
    }

    public function del()
    {   
        $ids = Request::param('ids');
        if ($ids) {
            if (Db::name('auth_group')->where('pid',$ids)->find()) {
                return $this->error('删除失败，该管理角色下还含有子级管理角色！');
            }else{
                if (Db::name('auth_group_access')->where('group_id',$ids)->find()) return $this->error('删除失败！');
                if(Db::name('auth_group')->where('id',$ids)->delete()) return $this->success('删除成功！'); return $this->error('删除失败！');
            }
        }
        return $this->fetch();
    }

}











 ?>