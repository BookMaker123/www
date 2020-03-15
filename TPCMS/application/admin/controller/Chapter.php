<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Novel as NovelModel;
use app\admin\model\Category as CategoryModel;
use app\admin\model\NovelChapter as NovelChapterModel;
use app\admin\model\NovelDivide as NovelDivideModel;
use app\admin\model\Tags as TagsModel;
use think\Db;
use think\facade\Request;
use think\facade\Session;
 
class Chapter extends Base
{
	public function index()
	{	
		$ids = Request::param('ids');
        if (!NovelModel::where('id',$ids)->find()) {      
            abort(404, '页面不存在!');
        }
		if (NovelDivideModel::where('novel_id',$ids)->find()) {
			$divide = NovelDivideModel::where('novel_id',$ids)->select();
			$novel_id = $ids;
            $count_chapter = NovelChapterModel::where('novel_id',$novel_id)->count();
			foreach ($divide as $k => $val) {
			 	$divide[$k]['chapter_data'] = NovelChapterModel::where('divide_id',$val['id'])->order('id', 'asc')->select();
			}
            $this->assign('count_chapter', $count_chapter);
			$this->assign('novel_id', $novel_id);
			$this->assign('divide', $divide);
		}else{
            $novel_id = $ids;
			$chapterList = NovelChapterModel::where('novel_id',$novel_id)->select();
            $count_chapter = $chapterList->count();
            $this->assign('novel_id', $novel_id);
            $this->assign('chapterList', $chapterList);
            $this->assign('count_chapter', $count_chapter);
		}

		if ($divide_id = Request::param('divide_id')) {
			$list = NovelChapterModel::where('id',$divide_id)->order('id', 'asc')->find();
			$list['divide'] = NovelDivideModel::where('id',$list['divide_id'])->value('divide');
			$this->assign('list', $list);
		}

		return $this->fetch();
	}
 
	public function add()
	{
		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $divide_id = $data['divide_id'];
			$data['createtime'] = time();
            $data['updatetime'] = time();
            unset($data['__token__']);
            if (NovelChapterModel::where('title',$data['title'])->find()) return json('该书籍章节名称已存在！');
            if (NovelChapterModel::insertGetId($data)) {
                $cunt_chapter = NovelChapterModel::where('novel_id',$data['novel_id'])->count();
                $lists = [
                    'id'           => $data['novel_id'],
                    'cunt_chapter' => $cunt_chapter,
                    'latelytime'   => time()
                ];
                return json('成功发布书籍章节！');
            }else{
                return json('发布书籍章节失败！');
            }
		}
	}

	public function edit()
	{
 		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            if (isset($data['id'])) {
            	$list = NovelChapterModel::where('id',$data['id'])->find();
            }else{       
				abort(404, '页面不存在!');
            }
            $divide_id = $data['divide_id'];
            $data['updatetime'] = time();
            unset($data['__token__']);
            if ($list['title'] !== $data['title']) {
            	if (NovelChapterModel::where('title',$data['title'])->find()) return json('该书籍章节名称已存在！');
            }
            if (NovelChapterModel::update($data)) {
                $cunt_chapter = NovelChapterModel::where('novel_id',$data['novel_id'])->count();
                $lists = [
                    'id'           => $data['novel_id'],
                    'cunt_chapter' => $cunt_chapter,
                    'latelytime'   => time()
                ];
                NovelModel::update($lists);
                return json('成功编辑书籍章节！');
            }else{
                return json('编辑书籍章节失败！');
            }
		}
		return $this->fetch();
	}

    public function del()
    {
        $ids = Request::param('ids');
        if ($ids) {
            if(NovelChapterModel::destroy($ids)) return json('删除章节成功！'); return json('删除章节失败！');
        }
	}


}