<?php
namespace app\vip\controller;

use think\Controller;
use think\Db;

class Tasks extends AdminController
{
    /**
     * 任务管理
     */
    protected $model = null;

    public function __construct()
    {
        parent::__construct();

    }
    /**
     * 首页
     * @return mixed
     */
    public function index($id = null)
    {
        $data_list = Db::name('user_tasks')->where('uid', UID)->order('create_time desc')->select();
        $chongbianarr = [];
        foreach ($data_list as $v) {
            //未完成
            if ($v['ztai'] == 0) {
                if ($v['xing'] == 0) {
                    $chongbianarr[0][] = $v;
                }
                if ($v['xing'] == 1) {
                    $chongbianarr[3][] = $v;
                }

            } elseif ($v['ztai'] == 1) {
                $chongbianarr[1][] = $v;
            }

        }

        $this->assign('data_list', $chongbianarr);

        return $this->fetch();
    }
    public function updata()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $tasksarr['uid'] = UID;
            $tasksarr['update_time'] = time();
            if ($data['s'] == 'taskAdd') {
                if (!$data['t']) {
                    return json(['code' => 0, 'msg' => '参数错误']);
                }

                //添加
                $tasksarr['create_time'] = time();
                $tasksarr['text'] = $data['t'];

                if ($id = Db::name('user_tasks')->insert($tasksarr)) {
                    return json(['code' => true, 'id' => $id, 'msg' => '添加任务成功']);
                } else {
                    return json(['code' => 0, 'msg' => '任务添加失败']);
                }

            } elseif ($data['s'] == 'taskRemove') {
                //删除
                if (Db::name('user_tasks')->where('uid', UID)->delete($data['t'])) {
                    return json(['code' => true, 'msg' => '删除任务成功']);
                } else {
                    return json(['code' => 0, 'msg' => '操作失败']);
                }
            } elseif ($data['s'] == 'taskStarAdd' || $data['s'] == 'taskStarRemove' || $data['s'] == 'taskSetActive' || $data['s'] == 'taskSetCompleted') {
                //安全判断
                if (!is_numeric($data['t'])) {
                    return json(['code' => 0, 'msg' => '参数错误']);
                }

                switch ($data['s']) {
                    case 'taskStarAdd':
                        $tasksarr['xing'] = 1;
                        break;
                    case 'taskStarRemove':
                        $tasksarr['xing'] = 0;
                        break;
                    case 'taskSetActive':
                        $tasksarr['ztai'] = 0;
                        break;
                    case 'taskSetCompleted':
                        $tasksarr['ztai'] = 1;
                        break;

                }
                if ($id = Db::name('user_tasks')->where('uid', UID)->where('id', $data['t'])->update($tasksarr)) {
                    return json(['code' => true, 'id' => $id, 'msg' => '添加任务成功']);
                } else {
                    return json(['code' => 0, 'msg' => '操作失败！']);
                }
            } else {
                return json(['code' => 0, 'msg' => '参数错误']);
            }

        } else {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

    }
}
