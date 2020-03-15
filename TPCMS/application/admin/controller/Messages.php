<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Member as MemberModel;
use app\admin\model\Messages as MessagesModel;
use app\admin\model\MessageStatus as MessageStatusModel;
use app\admin\model\Admin as AdminModel;
use think\Db;
use think\facade\Request;
use think\facade\Session;

class Messages extends Base
{
	public function index()
	{
		$list = MessagesModel::order('id', 'desc')->paginate(10,false,['query'=>request()->param()]);
		if (isset($list['user_id'])) {
			if ($list['user_id'] !== 0) {
				$list['user_id'] = json_encode(MessageStatusModel::where('message_id',$list['id'])->select());
			}
		}
        $count = $list->count();
        $page = $list->render();
		$this->assign('list', $list); 
        $this->assign('count', $count);
        $this->assign('page', $page);
		return $this->fetch();
	}

	public function add()
	{
		if ($this->request->isPost()) {
            $data = $this->request->post('','null','trim');
            $result = $this->validate($data, 'Messages.add');
            if(true !== $result){return $this->error($result);}
            $data['admin_id'] = session::get('admin_id');
            $data['createtime'] = time();
            $data['updatetime'] = time();
            unset($data['__token__']);
            if ($data['user_id'] ==0) {
                Db::startTrans();
                try {
                    $message_status = MemberModel::column('id');
                    $message_id = MessagesModel::insertGetId($data);
                    foreach ($message_status as $k => $va) {
                        $status_arr[$k]['user_id'] = $va;
                        $status_arr[$k]['message_id'] = $message_id;
                        $status_arr[$k]['createtime'] = time();
                        $status_arr[$k]['updatetime'] = time();
                    }
                    MessageStatusModel::insertAll($status_arr);
                    Db::commit();
                    return json('成功发送用户消息！');
                } catch (\Exception $e) {
                    Db::rollback();
                    return json('发送用户消息失败！');
                }
            }else{
                Db::startTrans();
                try {
                    $status_arr['user_id'] = $data['user_id'];
                    $status_arr['message_id'] = MessagesModel::insertGetId($data);
                    $status_arr['createtime'] = time();
                    $status_arr['updatetime'] = time();
                    MessageStatusModel::create($status_arr);
                    Db::commit();
                    return json('成功发送用户消息！');
                } catch (\Exception $e) {
                    Db::rollback();
                    return json('发送用户消息失败！');
                }
            }
        }
		return $this->fetch();
	}

    public function del()
    {
        $ids = Request::param('ids');
        if ($ids) {
            Db::startTrans();
            try {
                MessagesModel::where('id','in',$ids)->delete();
                MessageStatusModel::where('message_id','in',$ids)->delete();
                Db::commit();
                return json('删除成功！');
            } catch (\Exception $e) {
                Db::rollback();
                return json($e->getMessage());
            }
            return json('删除失败！');
        }
        return $this->fetch();
    }

}