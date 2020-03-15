<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Novel as NovelModel;
use app\admin\model\NovelChapter as NovelChapterModel;
use app\admin\model\NovelDivide as NovelDivideModel;
use think\Db;
use think\facade\Request;
use think\facade\Session;


class Divide extends Base
{
	public function add()
	{
		$novel_id = Request::param('novel_id');
		$this->assign('novel_id', $novel_id);
		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Divide.add');
            if(true !== $result){return $this->error($result);}
            if ($chushi = NovelChapterModel::where(['novel_id'=>$novel_id,'divide_id'=>0])->column('id')) {
                $chushi = implode(',', $chushi);
            }
            $data['createtime'] = time();
            $data['updatetime'] = time();
            unset($data['__token__']);
            if (NovelDivideModel::where(['divide'=>$data['divide'],'novel_id'=>$novel_id])->find()) return json('该书籍分卷名称已存在！');
            if($novelDivide_id = NovelDivideModel::insertGetId($data)){
                if($chushi){
                    NovelChapterModel::where('id','in',$chushi)->update(['divide_id'=>$novelDivide_id]);
                }
                return json('添加成功！');
            }else{
                return json('添加失败！');
            }
        }

		return $this->fetch();
	}

    public function edit()
    {
        $ids = Request::param('ids');
        $list = NovelDivideModel::where('id',$ids)->find();
        $this->assign('list', $list);
        if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Divide.edit');
            if(true !== $result){return $this->error($result);}
            $data['updatetime'] = time();
            unset($data['__token__']);
            if ($list['divide'] !== $data['divide']) {
                if (NovelDivideModel::where('divide',$data['divide'])->find()) return $this->error('该书籍分卷名称已存在！');
            }
            if(NovelDivideModel::where('id',$list['id'])->update($data)) return json('编辑成功！'); return json('编辑失败！');
        }
        return $this->fetch();
    }
    public function del()
    {
        $divide_id = Request::param('divide_id');
        if (NovelChapterModel::where('divide_id',$divide_id)->find()) return json('您不能含有章节的分卷！');
        if ($divide_id) {
            if(NovelDivideModel::destroy($divide_id)) return json('删除成功！'); return json('删除失败！');
        }
        return $this->fetch();
    }



}