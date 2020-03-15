<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\AuthRule as AuthRuleModel;
use think\facade\Request;
use think\Db;
use think\facade\Cache;

class Rule extends Base
{   
    public function index()
    {
        $list  = AuthRuleModel::order('id', 'asc')->select();
        if ($this->request->isPost()) {
            $list  = AuthRuleModel::where(['title'=>input('post.title')])->order('id', 'asc')->select();
            return $this->fetch();
        }
        $list = recursion_menu($list,0,'|--');
        $count = count($list);
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }

    public function add() 
    {
        $list = AuthRuleModel::where(['status'=>'normal'])->field('id,pid,name,title')->order('id', 'asc')->select();
        $lis = recursion_menu($list,0,'|--');
        $this->assign('lis', $lis);
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Rule.add');
            if(true !== $result){return $this->error($result);}
            $data['createtime'] = time();
            $data['updatetime'] = time();
            if(AuthRuleModel::create($data)){
                Cache::rm('__menu__');
                return $this->success('添加成功！','index');
            }
        }
        return $this->fetch();
    }   

    public function edit()
    {
        $ids = Request::param('ids');
        $list = AuthRuleModel::where('id',$ids)->field('id,pid,name,title,weigh,status,ismenu')->find();
        $pidname = AuthRuleModel::where('id',$list['pid'])->field('id,title')->find();
        $lis = AuthRuleModel::where(['status'=>'normal'])->field('id,pid,name,title')->order('id', 'asc')->select();
        $lis = recursion_menu($lis,0,'|--');
        $this->assign('list', $list);
        $this->assign('pidname', $pidname);
        $this->assign('lis', $lis);
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Rule.edit');
            if(true !== $result){return $this->error($result);}
            unset($data['__token__']);
            $data['updatetime'] = time();
            if(Db::name('auth_rule')->where('id',$list['id'])->update($data)){
                Cache::rm('__menu__');
                return $this->success('编辑成功！','index');
            } 
        }
        
        return $this->fetch();
    }

    public function del()
    {   
        $ids = Request::param('ids');
        if ($ids) {
            if (Db::name('auth_rule')->where('pid',$ids)->find()) {
                return $this->error('删除失败，该栏目下还含有子级栏目！');
            }else{
                if(Db::name('auth_rule')->where('id',$ids)->delete()){
                    Cache::rm('__menu__');
                    return $this->success('删除成功！');
                }else{
                    return $this->error('删除失败！');
                }
            }
        }
        return $this->fetch();
    }

}











 ?>