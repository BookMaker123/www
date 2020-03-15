<?php
namespace app\vip\controller;

use app\common\Nb;
use app\common\Jindu;
use app\vip\model\Lists as ListsModel;
use app\vip\model\Listsn as ListsnModel;
use think\Controller;
use think\Db;
use think\facade\Cache;

//https://sweetalert2.github.io/ 提示教程

class Listsn extends AdminController
{
    /**
     * 模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * Lists constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = model('Listsn');
    }
    /**
     * 首页
     * @return mixed
     */
    public function index($id = null, $soso = null)
    {
        if (isset($id)) {
            if (UID == 1)  //调试
                $dingdan = ListsModel::where('id', $id)->find();
            else
                $dingdan = ListsModel::where('uid', UID)->where('id', $id)->find();
            $this->assign('dingdan', $dingdan);
            if (!$dingdan) {
                $this->error('当前列表不存在');
            }
        }

        /** 获取个性化表格数据 */
        $user = Db::name('admin_user')->where('id', UID)->find();
        $biao_json = isset($user['biao_json']) ? $user['biao_json'] : '{"imeiorsn":"1","num":"100","xhao":"1","t":[1,2,3,4,5,6],"b":[1,2,3,4,5,6,7,8,9,10,11,12]}';
        $biao_arr = json_decode($biao_json, true);
        $biaogezaidingyi = ['1' => '#', '2' => '编号', '3' => '监控', '4' => '状态/进度', '5' => '客户信息', '6' => '进货价/出货价/是否销售'];
        $exxiazaidingyi = ['1' => '#', '2' => '机号', '3' => '设备详细', '4' => '型号', '5' => '内存', '6' => '颜色', '7' => '网络型号', '8' => 'IMEI', '9' => '序列号', '10' => '维修ID/维修进度/进度时间', '11' => '保修详细', '12' => '查找我的iPhone', '13' => '客户详细'];

        //为空的时候默认值
        if (empty($biao_arr['t'])) $biao_arr['t'] = [1, 2, 3, 4, 5, 6];
        $tt = [];
        if (isset($biao_arr['t'])) {
            foreach ($biao_arr['t'] as $v) {
                $tt[$v] = 'on';
            }
        }
        //如果ex表格全部为空。全选
        if (empty($biao_arr['b'])) $biao_arr['b'] = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        $bb = [];
        if (isset($biao_arr['b'])) {
            foreach ($biao_arr['b'] as $v) {
                $bb[$v] = 'on';
            }
        }





        //防止报错
        if (empty($biao_arr['imeiorsn'])) {
            $biao_arr['imeiorsn'] = 1;
        }
        if (empty($biao_arr['num'])) {
            $biao_arr['num'] = 100;
        }
        if (empty($biao_arr['xhao'])) {
            $biao_arr['xhao'] = 1;
        }
        $biao_arr['tt'] = $tt;
        $biao_arr['bb'] = $bb;
        //如果为空的时候 记得生成一个
        $this->assign('biaogezaidingyi', $biaogezaidingyi);
        $this->assign('exxiazaidingyi', $exxiazaidingyi);

        $this->assign('biao_json', $biao_arr);
        /** 获取表格数据 */
        $data_list = [];
        $tj = [];
        $map = $this->getMap();
        if ($id) {
            // 排序
            $order = $this->getOrder('id asc');

            $phone_id = input('param.phone_id/s', '', 'trim');//获取手机主键
            if(!empty($phone_id)){
                $data_list = $this->model->order($order)->where('id',$phone_id)->paginate($biao_arr['num']); //->select();
            }else{
                $map[] = ['did', '=', $id];
                if (UID == 1)  //调试
                    $data_list = $this->model->order($order)->where($map)->paginate($biao_arr['num']); //->select();
                else
                    $data_list = $this->model->where('uid', UID)->order($order)->where($map)->paginate($biao_arr['num']); //->select();

                $this->assign('page', $data_list->render());
                $tj = ListsnModel::getListSn($id);
            }
        } elseif ($soso) {
            //搜索 支持模糊搜索和批量搜索
            if ($this->request->isPost()) {
                $data = $this->request->post();
                if (@!$data['so']) {
                    $this->error('搜索内容不能为空');
                }
                if ($soso == 1) {
                    $so_text = trim($data['so']);
                    if (strlen($so_text) < 4) {
                        $this->error('搜索字符不能小于4位');
                    }

                    $data_list = $this->model->where('uid', UID)->where('imei|newimei|sn|newsn|kh_name|kh_phone|kh_beizhu|kh_kuaidi|wid', 'like', "%$so_text%")->select();
                } elseif ($soso == 2) {
                    //批量搜索
                    $soarr = explode("\r\n", $data['so']);
                    $soarrlike = [];
                    foreach ($soarr as $v) {
                        if (trim($v)) {
                            $soarrlike[] = "%" . trim($v) . "%";
                        }
                    }
                    if (!$soarrlike) {
                        $this->error('搜索内容不能为空');
                    }
                    $data_list = $this->model->where('uid', UID)->where('imei|newimei|sn|newsn|wid', 'like', $soarrlike, 'OR')->select();
                }
            }
            $tj = ListsnModel::getListSn('', $data_list);
        } else {
            $this->error('参数错误');
        }

        $this->assign('tj', $tj);

        $xhao_arr = Cache::get('xhao', '');
        $wxid_arr = Cache::get('wxid', '');
        //重写数组
        foreach ($data_list as $k => &$v) {
            if (isset($xhao_arr[$v['sn_hou']])) {
                $v['xinghao'] = $xhao_arr[$v['sn_hou']];
            } else {
                //不存在 保存下日志
                $this->getsnLog($v['sn_hou'], '型号', '缺少这个型号');
            }

            //维修ID重新编辑
            if ($v['wx_ztai_id'] && @$wxid_arr[$v['wx_ztai_id']]) {
                $v['wx_ztai_id_arr'] = $wxid_arr[$v['wx_ztai_id']];
            }

            //查询时间转换格式
            if ($v['wx_ctime']) {
                $v['wx_ctime_str'] = jiangeshijian($v['wx_ctime']);
            }

            //进度时间
            if ($v['wx_json']) {
                $v['wx_arr'] = json_decode($v['wx_json'], true);
                $v['wx_arr'] = $v['wx_arr']['repairMetaData'];
                $zuixinjindu_time = '';
                $zuixinjindu_jiange = '';
                if (isset($v['wx_arr']['modifiedDate'])) {
                    $zuixinjindu_time = substr($v['wx_arr']['modifiedDate'], 0, -3);
                    $zuixinjindu_jiange = jiangeshijian($zuixinjindu_time);
                }
                $time1 = '';
                if (isset($v['wx_arr']['steps'][0]['state']) && @$v['wx_arr']['steps'][0]['state'] != 'FUTR') {
                    @$time1 = substr($v['wx_arr']['steps'][0]['statusDate'], 0, -3);
                }
                //时间转换
                $time2 = '';
                if (isset($v['wx_arr']['steps'][1]['state']) && @$v['wx_arr']['steps'][1]['state'] != 'FUTR') {
                    $time2 = substr($v['wx_arr']['steps'][1]['statusDate'], 0, -3);
                }
                //时间转换
                $time3 = '';
                if (isset($v['wx_arr']['steps'][2]['state']) && @$v['wx_arr']['steps'][2]['state'] != 'FUTR') {
                    @$time3 = substr($v['wx_arr']['steps'][2]['statusDate'], 0, -3);
                }

                $v['wx_arr2'] = [
                    'zuixinjindu_time' => $zuixinjindu_time,
                    'zuixinjindu_jiange' => $zuixinjindu_jiange,
                    'buzou1_time' => $time1,
                    'buzou2_time' => $time2,
                    'buzou3_time' => $time3,
                ];
            }

            //转换型号
            /**未来开发：这里写个记录hou4位是否存在，但是数据库不存在的时候写入数据库 不重复写入数据库，然后我后台审批！牛x*/
        }
        //表格下载
        $mapjson= json_encode($map);
        $this->assign('mapjson', $mapjson);
        $this->assign('data_list', $data_list);

        $this->assign('wxid_arr', Cache::get('wxid', ''));

        return $this->fetch();
    }
    public function c($id = null, $soso = null)
    {
        return $this->fetch();
    }




    /**
     * 将订单转入进度查询
     */
    public function progressQuery()
    {
        $id = $this->request->param('id');
        $dname = $this->request->param('dname');
        $ids = explode(',',$id);//将字符串转数组
        if(count($ids) == 0){
            $this->error('不能为空');
        }
        $order_phone_list = Db::table('nb_cx_order_phone')
            ->alias('a')
            ->join('nb_cx_order_info b','a.order_id = b.id')
            ->where('b.user_id',UID)
            ->where('a.id','in',$ids)
            ->field('mp_sn,mp_imei,baoxiu_type,mp_bx_endtime,mp_buy_start,is_guanhuan')
            ->select();//需要的手机信息
        if(empty($order_phone_list)){
            $this->error('数据异常');
        }
        //先插入订单表并获取订单主键
        $lists_id = Db::name('sn_lists')->insertGetId([
            'uid'           => UID,
            'dname'         => $dname,
            'ztai'          => 0,
            'create_time'   => time(),
            'update_time'   => time(),
        ]);
        if(!$lists_id){
            $this->error('添加失败');
        }
        //处理数据进行批量插入
        $data = array();
        foreach($order_phone_list as $k => $v){
            $data[$k]['uid'] = UID;
            $data[$k]['did'] = $lists_id;
            $data[$k]['sn'] = $v['mp_sn'];
            $data[$k]['sn_hou'] = substr(strtoupper($v['mp_sn']),-4,4);
            $data[$k]['imei'] = $v['mp_imei'];
            $data[$k]['bao_lei'] = $v['baoxiu_type'];
            $data[$k]['bao_guobaotime'] = $v['mp_bx_endtime'];
            $data[$k]['bao_guanhuan'] = $v['is_guanhuan'];
            $data[$k]['buy_time'] = $v['mp_buy_start'];
        }
        try{
            Db::name('sn_listsn')->insertAll($data, true);//批量插入
        }catch (Exception $exception){
            $this->error('添加失败');
        }
        write_json(0,'订单：'.$dname.'  导入成功',['id'=>$lists_id]);
    }




    /**
     * 智能搜索
     */
    public function intell_search($soso = 1){
        $sn_or_imei = $_POST['so'];//sn或者imei号
        $user_id = UID;//用户Id
        if (empty($sn_or_imei)) {
            $this->error('搜索内容不能为空','lists/index');
        }
        $so_text = trim($sn_or_imei);
        if (strlen($so_text) < 4) {
            $this->error('搜索字符不能小于4位','lists/index');
        }

        //模糊搜索
        if($soso == 1){
            $sql = "select DISTINCT  a.*,b.sn,b.imei,b.wid,b.kh_name,b.kh_phone,b.kh_kuaidi,b.id as phone_id from  nb_sn_lists  a left join  nb_sn_listsn b on a.id = b.did  where
                ( b.sn like '%$sn_or_imei%' or b.imei like '%$sn_or_imei%' or b.kh_name like '%$sn_or_imei%' or b.kh_phone like '%$sn_or_imei%'
                 or b.kh_kuaidi like '%$sn_or_imei%' or b.wid like '%$sn_or_imei%' )
                 and a.uid = '$user_id' limit 50";
            $basis_data = @Db::query($sql);//基础查询（旧表）

            $sql1 = "select DISTINCT  a.*,b.mp_sn,b.mp_imei from  nb_cx_order_info  a left join  nb_cx_order_phone b on a.id = b.order_id  where
                 ( b.mp_sn like '%$sn_or_imei%' or b.mp_imei like '%$sn_or_imei%' ) and a.user_id = '$user_id' limit 50";
            $senior_data = @Db::query($sql1);//高级查询（新表）
        }
        //批量搜索
        else if($soso == 2){
            $soarr = explode("\r\n", $sn_or_imei);
            $soarrlike = [];
            foreach ($soarr as $v) {
                if (trim($v)) {
                    $soarrlike[] = "%" . trim($v) . "%";
                }
            }
            if (!$soarrlike) {
                $this->error('搜索内容不能为空','lists/index');
            }
//            $data_list = Db::table('nb_sn_listsn')->where('uid', UID)->where('imei|newimei|sn|newsn|wid', 'like', $soarrlike, 'OR')->fetchSql(true)->select();
            //基础查询
            $basis_data = Db::table('nb_sn_lists')
                ->alias('a')
                ->join('sn_listsn b','a.id=b.did')
                ->where('a.uid',$user_id)
                ->where('b.sn|b.imei|b.wid|b.kh_name|b.kh_phone|b.kh_kuaidi','like',$soarrlike,'OR')
                ->field('a.*,b.sn,b.imei,b.wid,b.kh_name,b.kh_phone,b.kh_kuaidi,b.id as phone_id')
                ->distinct(true)
                ->limit(50)
                ->select();
            //高级查询
            $senior_data = Db::table('nb_cx_order_info')
                ->alias('a')
                ->join('cx_order_phone b','a.id=b.order_id')
                ->where('a.user_id',$user_id)
                ->where('b.mp_sn|b.mp_imei','like',$soarrlike,'OR')
                ->field('a.*,b.mp_sn,b.mp_imei')
                ->distinct(true)
                ->limit(50)
                ->select();
        }

//        dump($basis_data);
//        dump($senior_data);
//        exit;
        $this->assign('total', count($basis_data) + count($senior_data));
        $this->assign('basis_data',$basis_data);//basis_data   基础查询
        $this->assign('senior_data',$senior_data);//senior_data  高级查询

        return $this->fetch('listsn/search');
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

            if ($data['name'] == 'tiamo4') {
                //单个监控
                return $this->getjiankong([$data['pk']], $data['value']);
            }
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!这里有没有安全SQL漏洞呢
            if ($data['name'] == 'tiamo6') {
                if (preg_match("#^[A-Za-z][A-Za-z0-9]{11}$#", $data['value']) || preg_match("#^(35|99|01)\d{12,13}$#", $data['value'])) { } else {
                    return json(['code' => 0, 'msg' => 'IMEI/序列号不正确...']);
                }
            }

            $aibiao = config('aibiao.');
            //判断是否有此表格
            if (isset($aibiao[$data['name']]) && is_array($aibiao[$data['name']])) {
                $data['value'] = input('post.value');
                if ($data['type'] == 'switch') {
                    $data['value'] = $data['value'] == 'true' ? 1 : 0;
                }

                if ($data['type'] == 'kehus') {
                    $baoarr = [];
                    $baoarr['kh_name'] = $data['value']['name'];
                    $baoarr['kh_phone'] = $data['value']['phone'];
                    $baoarr['kh_kuaidi'] = $data['value']['kuaidi'];
                } else {
                    //正常模式
                    $baoarr[$aibiao[$data['name']]['name']] = $data['value'];
                }
            } else {
                return json(['code' => 0, 'msg' => '字段错误']);
            }
            //写入
            if (ListsnModel::where('uid', UID)->where('id', $data['pk'])->update($baoarr)) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
    }
    //post 生成生成excel下载链接
    public function excelpost()
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        if (@$ids) {
            $vvv = '';
            foreach ($ids as $k) {
                $vvv .= $k . ',';
            }
            $this->success('操作成功', url('excel', ['ids' => $vvv]));
        } else {
            $this->error('生成表格URL错误!');
        }
    }
    /**
     * Excel表格下载
     * @return mixed
     */
    public function excel($id = null)
    {
        //从数据库查询需要的数据

        if ($id) {
            if (!Db::name('sn_lists')->where('uid', UID)->where('id', $id)->find()) $this->error('操作失败');
            $map['did'] = $id;
            $data_list = $this->model->where('uid', UID)->where($map)->select(); //
        } else {
            $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
            if ($ids) {
                $data_list = $this->model->where('uid', UID)->where('id', 'in', $ids)->select();
            } else {
                $this->error('操作失败');
            }
        }

        $wxid_arr = Cache::get('wxid', '');
        $xhao_arr = Cache::get('xhao', '');

        foreach ($data_list as &$v) {
            //转换型号

            if (isset($xhao_arr[$v['sn_hou']])) {
                $v['xinghao'] = $xhao_arr[$v['sn_hou']][0];
                $v['neicun'] = $xhao_arr[$v['sn_hou']][1];
                $v['yanse'] = $xhao_arr[$v['sn_hou']][2];
                $v['wangluo'] = $xhao_arr[$v['sn_hou']][3];
                $v['shebei'] = $v['xinghao'] . ' ' . $v['neicun'] . ' ' . $v['yanse'];
            } else {
                $v['xinghao'] = ''; //查保修的时候查到的型号
                $v['neicun'] = '';
                $v['yanse'] = '';
                $v['wangluo'] = '';
                $v['shebei'] = '';
            }

            $v['wx_ztai_id'] = isset($wxid_arr[$v['wx_ztai_id']]) ? $wxid_arr[$v['wx_ztai_id']][1] : '';
            $v['bao_goumaitime']='';
            $v['bao_tian']='';
            switch ($v['bao_lei']) {
                case "PD":
                    $v['bao_lei'] = "延保AC+";
                    $v['bao_goumaitime']=@date('Y-m-d', strtotime ("-729 day", strtotime($v['bao_guobaotime'])));
                    break;
                case "PE":
                    $v['bao_lei'] = "延保AC+";
                    $v['bao_goumaitime']=@date('Y-m-d', strtotime ("-729 day", strtotime($v['bao_guobaotime'])));
                    break;
                case "LI":
                    $v['bao_lei'] = "普保";
                    $v['bao_goumaitime']=@date('Y-m-d', strtotime ("-364 day", strtotime($v['bao_guobaotime'])));
                    break;
                case "OO":
                    $v['bao_lei'] = "过保";
                    break;
                case "T":
                    $v['bao_lei'] = "更换";
                    break;
                case "WJ":
                    $v['bao_lei'] = "未激活";
                    break;
                case "WG":
                    $v['bao_lei'] = "为购买验证";
                    break;
                case "JC":
                    $v['bao_lei'] = "借出设备";
                    break;
                case "X":
                    $v['bao_lei'] = "错误序列号";
            }
            if ($v['bao_guanhuan'] == 1) {
                $v['bao_guanhuan'] = '是';
            } elseif ($v['bao_guanhuan'] == 2) {
                $v['bao_guanhuan'] = '否';
            } else $v['bao_guanhuan'] = '';

            if (isset($v['bao_guobaotime']) && $v['bao_guobaotime'] > 0) {
                $v['bao_guobaotime'] = date("Y-m-d", strtotime($v['bao_guobaotime']));
                //转换日期
            } else $v['bao_guobaotime'] = '';

            if (isset($v['create_time']) && $v['create_time'] > 0) {
                $v['create_time'] = date("Y-m-d", $v['create_time']);
            }
            //最后进度时间
            if (isset($v['wx_time']) && $v['wx_time'] > 0) {
                $v['wx_time'] = date("Y-m-d", $v['wx_time']);
            }
        }

        // 设置表头信息（对应字段名,宽度，显示表头名称）
        //根据用户设置 下载表格


        $user = Db::name('admin_user')->where('id', UID)->find();
        $biao_json = isset($user['biao_json']) ? $user['biao_json'] : '{"imeiorsn":"1","num":"100","xhao":"1","t":[1,2,3,4,5,6],"b":[1,2,3,4,5,6,7,8,9,10,11,12]}';
        $biao_arr = json_decode($biao_json, true);
        //$exxiazaidingyi = ['1' => '#', '2' => '机号', '3' => '设备详细', '4' => '型号', '5' => '内存', '6' => '颜色', '7' => '网络型号', '8' => 'IMEI', '9' => '序列号', '10' => '维修ID/维修进度/进度时间', '11' => '保修详细', '12' => '查找我的iPhone'];

        //为空的时候默认值
        //如果ex表格全部为空。全选
        if (empty($biao_arr['b'])) $biao_arr['b'] = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        $bb = [];
        if (isset($biao_arr['b'])) {
            foreach ($biao_arr['b'] as $v) {
                $bb[$v] = 'on';
            }
        }

        if (array_key_exists(1, $bb)) {
            $cellName[] = ['#', '10', '#'];
        }
        if (array_key_exists(2, $bb)) {
            $cellName[] = ['id', '20', '编号'];
        }
        if (array_key_exists(3, $bb)) {
            $cellName[] = ['shebei', '50', '设备详细'];
        }
        if (array_key_exists(4, $bb)) {
            $cellName[] = ['xinghao', '15', '型号'];
        }
        if (array_key_exists(5, $bb)) {
            $cellName[] = ['neicun', '5', '内存'];
        }
        if (array_key_exists(6, $bb)) {
            $cellName[] = ['yanse', '8', '颜色'];
        }
        if (array_key_exists(7, $bb)) {
            $cellName[] = ['wangluo', '12', '网络'];
        }
        if (array_key_exists(8, $bb)) {
            $cellName[] = ['imei', '18', 'IMEI'];
        }
        if (array_key_exists(9, $bb)) {
            $cellName[] = ['sn', '15', 'SN'];
        }
        if (array_key_exists(10, $bb)) {
            $cellName[] = ['wid', '13', '维修ID'];
            $cellName[] = ['wx_ztai_id', '20', '维修状态'];
            $cellName[] = ['wx_tishi', '20', '最新进度'];
            $cellName[] = ['wx_time', '20', '进度更新时间'];
            $cellName[] = ['newimeiorsn', '18', '新机IMEI/序列号'];

        }
        if (array_key_exists(11, $bb)) {
            $cellName[] = ['bao_lei', 'auto', '保修类型'];
            $cellName[] = ['bao_goumaitime', 'auto', '购买日期（预估）'];
            $cellName[] = ['bao_guobaotime', 'auto', '过保日期'];
           // $cellName[] = ['bao_guanhuan', 'auto', '剩余保修天数'];
            $cellName[] = ['bao_guanhuan', 'auto', '是否官换'];

        }
        if (array_key_exists(12, $bb)) {
            $cellName[] = ['icloud', 'auto', '查找我的iPhone状态'];
        }

        if (array_key_exists(13, $bb)) {
            $cellName[] = ['create_time', 'auto', '创建时间'];
            $cellName[] = ['kh_beizhu', 'auto', '备注'];
            $cellName[] = ['kh_name', 'auto', '客户名称'];
            $cellName[] = ['kh_phone', 'auto', '客户电话'];
        }




        $Excel = new \app\common\Excel;
        $Excel->export("aiguo", $cellName, $data_list);
    }




    //淘汰了
    public function checkapi2()
    {
        $wxid_arr = Cache::get('wxid', '');
        if ($this->request->isPost() && input('?post.tiamo')) {
            $id = input('post.tiamo');
            //判断是否存在！
            $data_sn = Db::name('sn_listsn')->where('uid', UID)->where('id', $id)->find();
            if (!$data_sn['sn'] && !$data_sn['imei'] && !$data_sn['wid']) {
                return json(["status" => false, "tishi" => "IMEI/序列号不能为空"]);
            }
            //判断是否已经查询完毕->还没写 直接数据库获取
            if (in_array($data_sn['wx_ztai_id'], [4, 424, 422, 543, 420, 418, 427])) { }
            //开始查询
            $c = Nb::Chawx($data_sn);
            if (empty($c['wx_ztai_id']) || $c['wx_ztai_id'] == '4') {
                return json(["status" => false, "tishi" => $c['wx_tishi']]);
            } else {
                $weixiuztai = @$wxid_arr[$c['wx_ztai_id']];
                if (@$weixiuztai) {
                    $tishi = "<span class='badge badge-" . $weixiuztai[2] . "'>" . $weixiuztai[1] . '</span>' . $c['wx_tishi'];
                } else {
                    $tishi = $c['wx_tishi'];
                }
            }
            //写个生成简单数组的方法
            $wx_arrx['s'] = [];
            if (isset($c['wx_json']) && $wx_arr = json_decode($c['wx_json'], true)) {
                $wx_arrx['t1'] = isset($wx_arr['repairMetaData']['repairStatusDesc']) ? $wx_arr['repairMetaData']['repairStatusDesc'] : '';
                $wx_arrx['t2'] = isset($wx_arr['repairMetaData']['repairShortDesc']) ? $wx_arr['repairMetaData']['repairShortDesc'] : '';
                $wx_arrx['time'] = isset($wx_arr['repairMetaData']['modifiedDate']) ? substr($wx_arr['repairMetaData']['modifiedDate'], 0, -3) : '';
                $wx_arrx['x'] = isset($wx_arr['repairMetaData']['product']['userFriendlyProductName']) ? $wx_arr['repairMetaData']['product']['userFriendlyProductName'] : '';
                foreach ($wx_arr['repairMetaData']['steps'] as $v) {
                    if (isset($v['state']) && $v['state'] == 'PRES') {
                        unset($v['state']);
                        unset($v['num']);
                        unset($v['statusId']);
                        unset($v['hasAlert']);
                        if (isset($v['statusDate'])) {
                            $v['statusDate'] = date("Y-m-d H:i:s", substr($v['statusDate'], 0, -3));
                        }

                        $wx_arrx['s'][] = $v;
                    }
                }
            }

            return json(["status" => true, "tishi" => $tishi, 'arr' => $wx_arrx]);
        } else {
            return json(["status" => false, "tishi" => "参数错误"]);
        }
    }
    /**
     * 智能添加 最新算法
     * 有个BUG 如果添加列表有重复的 会重复添加！？
     * @return mixed
     */
    public function add($did = null,$quanju=null)
    {
        $t1 = microtime(true);
        if ($did == null) {
            $this->error('添加列表不能为空！');
        }

        if (!Db::name('sn_lists')->where('uid', UID)->where('id', $did)->find()) {
            $this->error($did . "当前列表ID不存在!");
        }

        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证
            if (empty($data['textbox'])) {
                $this->error('虽然我很智能，但是您空白提交，是要我猜您心里的想法?');
            }
            $zhuanhuan = $this->model->zhinengzhuanhuan($data['textbox']);


            if ($zhuanhuan == null) {
                $this->error('智能识别错误，请检查后重试！');
            }

            $this->jiankongwidbiao($zhuanhuan);
            //批量保存  或者编辑 根据SN
            $bianji_ii = 0;
            $tianjia_ii = 0;
            $chongfu_ii = 0;
            //获取UDID（独立机子编号）然后添加一个++;
            if ($udid = Db::name('sn_listsn')->where('uid', UID)->order('id', 'desc')->find()) {
                $udid = $udid['udid'];
            } else {
                $udid = 10000;
            }
            $gengxintishi = "";
            $atime = time();
            /**
             * 新编写批量判断数据库是否存在 不需要每次查询 只需要查询一次 然后数组判断
             * */
            $arrsn = [];
            $arrimei = [];
            //遍历出 in 批量查询
            foreach ($zhuanhuan as $v) {
                if (isset($v['sn'])) {
                    $arrsn[] = $v['sn'];
                }

                if (isset($v['imei'])) {
                    $arrimei[] = $v['imei'];
                }
            }
            //->fetchSql(true)
            //$INcunzai = Db::name('sn_listsn')->where('uid', UID)->whereOr('sn', 'in', $arrsn)->whereOr('imei', 'in', $arrimei)->column('udid,imei,sn,create_time', 'id');
            /** 
            $inmap1 = [
                ['did', '=', $did],
                ['uid', '=', UID],
                ['sn', 'in', $arrsn],
            ];

            $inmap2 = [
                ['did', '=', $did],
                ['uid', '=', UID],
                ['imei', 'in', $arrimei],
            ];
            */
            if($quanju!="on")
                $inmap1[]=['did', '=', $did];
            $inmap1[]=  ['uid', '=', UID];
            $inmap1[]=    ['sn', 'in', $arrsn];
            if($quanju!="on")
                $inmap2[]= ['did', '=', $did];
            $inmap2[]=     ['uid', '=', UID];
            $inmap2[]=   ['imei', 'in', $arrimei];

            $INcunzai = Db::name('sn_listsn')->whereOr([$inmap1, $inmap2])->column('udid,imei,sn,create_time', 'id');
  

            $soSNarr = [];
            $soIMEIarr = [];
            foreach ($INcunzai as $k => $v) {
                if (isset($v['sn'])) {
                    $soSNarr[$k] = $v['sn'];
                }

                if (isset($v['imei'])) {
                    $soIMEIarr[$k] = $v['imei'];
                }
            }

            /** 重复记录和去重复 */
            $chongfuArr = [];
            $chongfutext = '';
            foreach ($zhuanhuan as $k => &$v) {
                //优先判断sn

                if (isset($v['sn']) && array_search($v['sn'], $chongfuArr)) {

                    //重复SN
                    unset($zhuanhuan[$k]); //重复就删除
                    $chongfu_ii++;
                    $chongfutext .= $v['sn'] . "\r\n";
                } elseif (isset($v['imei']) && array_search($v['imei'], $chongfuArr)) {
                    //重复IMEI
                    unset($zhuanhuan[$k]); //重复就删除
                    $chongfu_ii++;
                    $chongfutext .= $v['imei'] . "\r\n";
                } else {
                    //不重复的添加到数组去 下次就判断不在出现！
                    //IMEI
                    if (isset($v['sn'])) {
                        $chongfuArr[$v['sn']] = $v['sn'];
                    }

                    if (isset($v['imei'])) {
                        $chongfuArr[$v['imei']] = $v['imei'];
                    }
                }
            }

            if ($chongfu_ii > 0) {
                $gengxintishi .= "重复数据：\r\n$chongfutext\r\n";
            }
            //开始保存
            $savearr = []; //需要批量编辑数组
            $addarr = []; //需要批量添加数组

            $gengxintishi2 = '';
            foreach ($zhuanhuan as $k => $baos) {
                //快速备注
                if(input('?post.beizhu')&&input('post.beizhu')!=null ) {
                    $baos['kh_beizhu'] = input('post.beizhu', null);
                }
                //比如要IMEI 或者SN才保存
                if (@isset($baos['sn']) || @isset($baos['imei'])) {
                    //开始添加 或者编辑
                    //搜索该表格，是否存在 这序列号，如果存在直接编辑

                    if (@isset($baos['sn'])) {
                        $aaid = array_search($baos['sn'], $soSNarr); //搜索数组
                        if (!$aaid) {
                            //在查找是否更具IMEI 修复单独有IMEI的时候 不编辑
                            if (@isset($baos['imei'])) {
                                $aaid = array_search($baos['imei'], $soIMEIarr);
                            }
                        }
                    } elseif (@isset($baos['imei'])) {
                        $aaid = array_search($baos['imei'], $soIMEIarr);
                    }

                    if ($aaid) {
                        //生成批量编辑数组
                        $baos['update_time'] = $atime;
                        //直接更新
                        if (ListsnModel::where('id', $aaid)->where('uid', UID)->update($baos)) {
                            $bianji_ii++; //更新台数
                            $bianjitext = isset($INcunzai[$aaid]['imei']) ? $INcunzai[$aaid]['imei'] : $INcunzai[$aaid]['sn'];
                            $gengxintishi2 .= $INcunzai[$aaid]['udid'] . "\t" . $bianjitext . "\t" . date("Y-m-d", $INcunzai[$aaid]['create_time']) . "\r\n";
                        }
                    } else {
                        /**批量添加 */
                        $tianjia_ii++;
                        //用户机子独立ID
                        $udid++;
                        //重新排序格式 否则insertAll会报错
                        $addList['uid'] = UID;
                        $addList['did'] = $did;
                        $addList['imei'] = isset($baos['imei']) ? $baos['imei'] : null;
                        $addList['wid'] = isset($baos['wid']) ? $baos['wid'] : null;
                        $addList['sn'] = isset($baos['sn']) ? $baos['sn'] : null;
                        $addList['sn_hou'] = isset($baos['sn_hou']) ? $baos['sn_hou'] : 0;
                        $addList['udid'] = $udid;
                        $addList['create_time'] = $atime;
                        $addList['update_time'] = $atime;
                        $addList['kh_beizhu'] = input('post.beizhu', null);
                        $addarr[] = $addList;
                    }
                } else {
                    $this->error('添加失败，没有匹配到正确的数据！');
                }
            }
            if ($gengxintishi2) {
                $gengxintishi .= "编辑 #\tIMEI/SN\t时间\r\n" . $gengxintishi2;
            }
            //批量添加
            if ($addarr) {
                //批量添加成功
                if (!Db::name('sn_listsn')->insertAll($addarr)) {
                    $tianjia_ii = '添加错误：' . $tianjia_ii;
                }
            }

            $t2 = microtime(true);
            if ($tianjia_ii > 0 || $bianji_ii > 0) {
                $this->success("添加[<code>$tianjia_ii</code>]编[<code>$bianji_ii</code>]去重[<code>$chongfu_ii</code>]", url('index', ['id' => $did]), $gengxintishi);
            } else {
                $this->error('导入数据空，请检查格式！');
            }
        }
    }
    public function jiankongwidbiao($zhuanhuan){
        //遍历序列号+维修ID
        foreach ($zhuanhuan as $v) {
            if (isset($v['sn']) && isset($v['wid']) ) {
                $arrsnwid[] = $v['sn'].'_'.$v['wid'];
            }        
        }
        if(empty($arrsnwid))return;
     
        //判断数据库是否存在

        $INcunzai = Db::name('jiankong_wid')->whereIn('snwid',$arrsnwid)->column('id', 'snwid');

        $arrsave=[];
        $create_time=time();
        foreach ($zhuanhuan as $v) {
            //多维的时候自动分割
            if(isset($v['sn']) && isset($v['wid']) ){
                $arrwid= explode(' ',$v['wid']);
                foreach($arrwid as $vvvv){
                     $snwid=$v['sn'].'_'.$vvvv;
                    if (isset($v['sn']) && isset($v['wid']) && !array_key_exists($snwid,$INcunzai) )  {          
                        $arrsave[] = ['snwid'=>$snwid,'uid'=> UID,'create_time'=>$create_time];
                    }  
                }   
            }
  
        }

        if($arrsave)Db::name('jiankong_wid')->insertAll($arrsave);
    }

    /**
     * 智能添加 老版本一个一个判断是否存在！
     * @return mixed
     */
    public function add2($did = null)
    {
        $t1 = microtime(true);
        if ($did === null) {
            $this->error('添加列表不能为空！');
        }

        if (!Db::name('sn_lists')->where('uid', UID)->where('id', $did)->find()) {
            $this->error($did . "当前列表ID不存在!");
        }

        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证
            if (!$data['textbox']) {
                $this->error('虽然我很智能，但是您空白提交，是要我猜您心里的想法?');
            }
            $zhuanhuan = $this->model->zhinengzhuanhuan($data['textbox']);
            if ($zhuanhuan == null) {
                $this->error('智能识别错误，请检查后重试！');
            }

            //批量保存  或者编辑 根据SN
            $bianji_ii = 0;
            $tianjia_ii = 0;
            //获取UDID（独立机子编号）然后添加一个++;
            if ($udid = Db::name('sn_listsn')->where('uid', UID)->order('id', 'desc')->find()) {
                $udid = $udid['udid'];
            } else {
                $udid = 0;
            }
            $gengxintishi = "编号\tIMEI/SN\t时间\r\n";
            $atime = time();

            foreach ($zhuanhuan as $k => $baos) {
                $baos['uid'] = UID;
                $baos['did'] = $did;
                //比如要IMEI 或者SN才保存
                if (@isset($baos['sn']) || @isset($baos['imei'])) {
                    //开始添加 或者编辑
                    //搜索该表格，是否存在 这序列号，如果存在直接编辑

                    if (@isset($baos['sn'])) {
                        $aaid = Db::name('sn_listsn')->where('uid', UID)->where('sn', $baos['sn'])->find();
                        if (!$aaid) {
                            //在查找是否更具IMEI 修复单独有IMEI的时候 不编辑
                            if (@isset($baos['imei'])) {
                                $aaid = Db::name('sn_listsn')->where('uid', UID)->where('imei', $baos['imei'])->find();
                            }
                        }
                    } elseif (@isset($baos['imei'])) {
                        $aaid = Db::name('sn_listsn')->where('uid', UID)->where('imei', $baos['imei'])->find();
                    }

                    $aaid = 0;
                    if ($aaid) {
                        //存在 直接更新
                        unset($baos['did']); //不更新did
                        $baos['update_time'] = $atime;
                        if (ListsnModel::where('uid', UID)->where('id', $aaid['id'])->update($baos)) {
                            $bianji_ii++;
                            //更新条件 优先imei 其次SN
                            $bianjitext = isset($aaid['imei']) ? $aaid['imei'] : $aaid['sn'];
                            //$gengxintishi .= '#' . $aaid['udid'] . ' ' . @$baos['imei'] . ' ' . @$baos['sn'] . ' ' . @$baos['wid'] . date("Y-m-d H:i", $aaid['create_time']);
                            $gengxintishi .= $aaid['udid'] . "\t" . $bianjitext . "\t" . date("Y-m-d", $aaid['create_time']);
                            if (next($zhuanhuan) == true) {
                                $gengxintishi .= "\r\n";
                            }
                        }
                    } else {
                        //用户机子独立ID
                        $udid++;
                        $baos['udid'] = $udid;
                        $baos['create_time'] = $atime;
                        $baos['update_time'] = $atime;
                        //添加
                        if (ListsnModel::insert($baos)) {
                            $tianjia_ii++;
                        }
                    }
                } else {
                    $this->error('添加失败，没有匹配到正确的数据！');
                }
            }
            $t2 = microtime(true);
            if ($tianjia_ii > 0 || $bianji_ii > 0) {
                $this->success("添加[$tianjia_ii]个，编辑[$bianji_ii]个(" . round($t2 - $t1, 2) . ')秒', url('index', ['id' => $did]), $gengxintishi);
            } else {
                $this->error('导入数据空，请检查格式！');
            }
        }
    }

    /**
     * 批量启用监控
     * @return mixed
     */
    public function pjk($did = null)
    {

        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        if (empty($ids) || count($ids) < 1)  $this->error("加入监控失败，稍后重试...");
        $this->Getjiankong($ids, 'true');
    }
    /**
     * 批量取消监控
     * @return mixed
     */
    public function qjk($did = null)
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        if (empty($ids) || count($ids) < 1)  $this->error("加入监控失败，稍后重试...");
        $idscount = count($ids);
        if (Db::name('sn_listsn')->where('uid', UID)->where('id', 'in', $ids)->update(['jktime' => null, 'jkztai' => 0])) //->where('did', $did)
        {
            $this->success("成功取消监控" . $idscount . "个");
        } else {
            $this->error("加入监控失败，稍后重试...");
        }
    }
    /**
     * 批量标记出货
     * @return mixed
     */
    public function qchu($q = null)
    {
        $chuhuo = $q ? 1 : 0;
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $idscount = count($ids);
        if (Db::name('sn_listsn')->where('uid', UID)->where('id', 'in', $ids)->update(['chuhuo' => $chuhuo])) {
            $this->success("设置出货成功" . $idscount . "个");
        } else {
            $this->error("设置出货失败...");
        }
    }
    /**
     * 批量删除
     * @author pdel <2299493@qq.com>
     * @return mixed
     */
    public function pdel($did = null)
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        if (Db::name('sn_listsn')->where('uid', UID)->where('id', 'in', $ids)->delete()) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
    /**
     * 个性化设置
     * @author pdel <2299493@qq.com>
     * @return mixed
     */
    public function getgexinghua()
    {
        if ($this->request->isPost()) {
            $baoarr = [];
            if (input('imeiorsn') == 1 || input('imeiorsn') == 2 || input('imeiorsn') == 3) {
                $baoarr['imeiorsn'] = input('imeiorsn');
            }
            if (input('xhao') == 1 || input('xhao') == 2 || input('xhao') == 3) {
                $baoarr['xhao'] = input('xhao');
            }
            if (input('tiaoshu') == 100 || input('tiaoshu') == 200 || input('tiaoshu') == 300 || input('tiaoshu') == 500) {
                $baoarr['num'] = input('tiaoshu');
            }
            if (input('t')) {
                foreach (input('t') as $k => $v) {
                    if (is_numeric($k)) {
                        $kkarr[] = $k;
                    }
                }
                $baoarr['t'] = $kkarr;
            }
            if (input('b')) {
                foreach (input('b') as $k2 => $v2) {
                    if (is_numeric($k2)) {
                        $kkarr2[] = $k2;
                    }
                }
                $baoarr['b'] = $kkarr2;
            }
            if ($baoarr) {
                if (Db::name('admin_user')->where('id', UID)->update(['biao_json' => json_encode($baoarr)])) {
                    $this->success('设置成功');
                } else {
                    $this->error('设置失败，请稍后重试！');
                }
            }
        }
        $this->error('设置失败');
    }
}
