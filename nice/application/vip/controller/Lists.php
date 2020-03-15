<?php
namespace app\vip\controller;

use app\vip\model\Lists as ListsModel;
use app\vip\model\Listsn as ListsnModel;
use think\Controller;

class Lists extends AdminController
{
    /**
     * 空白操作
     */
    public function _empty($name)
    {
 
      //  return $this->fetch();
    }
    protected $model = null;
    /**1
     * 初始化
     * Lists constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = model('Lists');
    }
    /**
     * 首页
     * @return mixed
     */
    public function index($id = null)
    {
        $data_list = ListsModel::where('uid', UID)->order('ftime asc,create_time asc')->select();

        //根据分类 重新排序数组
        $data_arr = [];
        $zongshu = 0;
        $zongchenggong=0;
        $zongjubao=0;
        foreach ($data_list as $v) {
            $a = ListsnModel::getListSn($v['id']);
            $zongshu += $a['zong'];
            $zongchenggong+= $a['chenggong'];
            $zongjubao+= $a['jubao'];
            $data_arr[$v['ztai']][] = ['d' => $v, 'tj' => $a];
        }


        //[ error ] [2]count(): Parameter must be an array or an object that implements Countable 这里也报错
        //$tab_tj[0] = @count($data_arr[0]) > 0 ? count($data_arr[0]) : '0';
        //$tab_tj[1] = @count($data_arr[1]) > 0 ? count($data_arr[1]) : '0';

        $tab_tj[0] = isset($data_arr[0]) ? count($data_arr[0]) : '0';
        $tab_tj[1] = isset($data_arr[1]) ? count($data_arr[1]) : '0';
        $tab_tj['zongtaishu'] = $zongshu;
        $tab_tj['zongchenggong'] = $zongchenggong;
        $tab_tj['zongjubao'] = $zongjubao;

        $tab_tj['zongchenggong100']  = $zongchenggong>0 ?round($zongchenggong/ ($zongchenggong + $zongjubao) * 100, 2):'0';
        $tab_tj['zongjubao100']  = (100-$tab_tj['zongchenggong100']);

        $this->assign('data_list', $data_arr);
        $this->assign('tab_tj', $tab_tj);

        return $this->fetch();
    }

    /**
     * 添加 没完成
     * @return mixed
     */
    public function add()
    {
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证
            $validate = $this->validate($data, 'Lists.ftime_on');
            if (true !== $validate) {
                $this->error($validate);
            }
            //添加保存
            if ($Dingid = $this->model->add($data)) {
                $this->success('新增订单成功', url('listsn/index', ['id' => $Dingid]));
            } else {
                $this->error('新增订单失败');
            }

        }
    }
    /**
     * 添加 没完成
     * @return mixed
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证
   
            $validate = $this->validate($data,  'Lists.ftime_off');
            
            if (true !== $validate) {
                $this->error($validate);
            }
            //添加保存
            if ($Dingid = $this->model->edit($data)) {
                $this->success('订单编辑成功');
            } else {
                $this->error('订单编辑失败');
            }

        }
    }
    /**
     * 快速编辑
     * @return mixed
     */
    public function quickedit()
    {
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证 -还没写
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!这里有没有安全SQL漏洞呢

            $aibiao = config('aibiao.');
            //判断是否有此表格
            if (isset($aibiao[$data['name']]) && is_array($aibiao[$data['name']])) {
                $data['value'] = input('post.value');
                if ($data['type'] == 'switch') {
                    $data['value'] = $data['value'] == 'true' ? 1 : 0;
                }
                $baoarr[$aibiao[$data['name']]['name']] = $data['value'];
            } else {
                return json(['code' => 0, 'msg' => '字段错误']);
            }

            //写入
            if (ListsModel::where('uid', UID)->where('id', $data['pk'])->update($baoarr)) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
    }
    /**
     * 删除
     * @return mixed
     */
    public function del($id = null)
    {
        if ($id == null) {
            $this->error('参数错误');
        }
        //删除列表内IMEI
        if ($del_tai = ListsnModel::where('uid', UID)->where('did', $id)->delete()) {
            //删除列表
            if (!ListsModel::where('uid', UID)->where('id', $id)->delete()) {
                $this->error('删除失败');
            }
            $this->success('成功删除列表' . $del_tai . '台');
        } else {
            //删除列表
            if (ListsModel::where('uid', UID)->where('id', $id)->delete()) {
                $this->success('成功删除列表');
            } else {
                $this->error('删除失败2');
            }

        }
        //  if (ListsnModel::where('uid', UID)->where('id', $id)->delete())

    }

}
