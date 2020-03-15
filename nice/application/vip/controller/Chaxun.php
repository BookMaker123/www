<?php

namespace app\vip\controller;

use app\common\Ztai;
use app\common\Jindu;
use app\common\CoreCore;
use app\vip\model\Lists as ListsModel;
use app\vip\model\Listsn as ListsnModel;
use app\wechatapp\controller\ImgIdentify;
use think\Controller;
use think\Db;
use think\facade\Cache;
use function GuzzleHttp\json_decode;
// use app\vip\controller\Check;
use EasyWeChat\Factory;
use app\common\Excel;
use app\common\Core;
use think\Exception;


class Chaxun extends AdminController
{
    /**
     * 初始化
     */
    protected $user = null;
    protected $weixin_user = null;
    public function __construct()
    {
        parent::__construct();
        //判断是否绑定微信
        $this->user = Db::name('admin_user')->where('id', UID)->find();
        if ($this->user['openid'] == null) {
            $this->error("请先绑定微信！");
        }
        $this->weixin_user = Db::name('weixin_user')->where('openid', $this->user['openid'])->find();
    }
    /**
     * 查询页面 动态生成！
     */
    public function index($order_type =1)
    {
        $user_id = UID;
        $chaxun_list = Db::name('cx_order_info o')
            ->join('nb_cx_order_phone p', 'o.id=p.order_id and p.is_del=0','left')
            ->field('o.*,count(p.id) as phone_count')
            ->where('user_id',$user_id)
            ->where('o.is_del',0)
            ->where('o.order_type',$order_type)
            ->group('o.id')
            ->order('o.id desc')
            ->paginate(40)
            ->each(function ($item,$key){
                $item['add_time'] = date('Y-m-d H:i:s',$item['add_time']);
                // $item['phone_count'] = Db::name('cx_order_phone')->where('order_id',$item['id'])->where('is_del',0)->count();
                return $item;
            });
            // dump($chaxun_list);
            // die;
        $sql = "SELECT count(p.id) from nb_cx_order_phone p left join
                nb_cx_order_info o on p.order_id=o.id where o.user_id=$user_id and o.order_type=$order_type and p.is_del=0";
        $equipment_count = @Db::query($sql)[0]['count(p.id)'];//手机数量
        $order_count = Db::name('cx_order_info')->where('order_type',$order_type)->where('user_id',$user_id)->where('is_del',0)->count();//总订单
        $consumption_points = Db::name('chaxun_jifenjilu')->where('jifenxiaofei','<',0)->where('user_id',$user_id)->whereIn('change_type',[1,5,6])->field('SUM(jifenxiaofei)')->select()[0]['SUM(jifenxiaofei)'];//总消费积分


        $this->assign('equipment_count', $equipment_count);//手机数量
        $this->assign('order_count', $order_count);//订单数量
        $this->assign('consumption_points', sprintf("%.2f",abs($consumption_points)));//总消费积分
        $this->assign('page', $chaxun_list->render());
        $this->assign('order_type', $order_type);
        $this->assign('chaxun_list',  $chaxun_list);
        return $this->fetch();
    }


    /**
     * 获取需要显示的class_server 字段
     */
    public function get_table_th($order_id){
        $row = @Db::query( "SELECT   GROUP_CONCAT( DISTINCT(server_id) ) as server_list  from v_server_info where order_id=$order_id ");
        return $row[0]['server_list'];
    }

    //软删除订单信息 不显示在前端
    public function del_order($id){
        if($id==''){
            write_json(44,'请选择要删除的订单号');
        }
        @Db::execute( "update  nb_cx_order_info set  is_del = 1 where id in ($id) and user_id =".UID);
        write_json(0,'删除成功');
    }

    function set_remrak(){
        @Db::execute( "update  nb_cx_order_info set  remark = '".$_POST['data']."' where id = ".intval($_POST['id'])."  and user_id =".UID);
        write_json(0,'更新成功');
    }
    //获取服务列表
    public function ajax_get_fuwu_list(){
        $type = 1;
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $type = $data['type'];
        }

        write_json(0,'success', $this->get_chaxun_list($type));
    }

    /**
     * 订单详情页
     * @param $id               订单主键id
     * @param null $jiankong    监控筛选
     * @param null $jihuo       激活筛选
     * @param null $color       颜色筛选
     * @param null $guobao      过保筛选
     * @param null $model       型号筛选
     * @param null $net         网络筛选
     * @param null $rong        容量筛选
     * @param null $baoxiu      保修筛选
     * @param null $gh          官换筛选
     * @param null $idLock      ID锁筛选
     * @param null $idBlack     ID锁黑白筛选
     * @param null $netLock     网络锁筛选
     * @param null $weixiu      维修筛选
     * @param null $yys         运营商筛选
     * @param null $MDM         MDM锁筛选
     * @param int $limit        每页条数
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public  function order_detail($id,$jiankong = null,$jihuo = null,$color = null,$guobao = null,$model = null,$net = null,$rong = null,$baoxiu = null,$gh = null,$idLock = null,$idBlack = null,$netLock = null,$weixiu = null,$yys = null,$MDM = null,$sn = null,$sn_or_imei_url = null,$country = null,$buy_time = null,$limit = 40){
        //获取订单名称
        $order_id = intval($id);
        $order_info = Db::name('cx_order_info')->where('id',$order_id)->where('user_id',UID)->field('order_title,order_type')->find();
        //这个订单没有权限访问
        if (!$order_info){
            $this->error('您没有访问这个订单的权限');
        }
        $order_title = $order_info['order_title'];
        $order_type = $order_info['order_type'];

        //获取手机 列表
        $order_phone_list = $this->get_order_list($id,urldecode($jiankong),urldecode($jihuo),urldecode($color),urldecode($guobao),urldecode($model),urldecode($net),urldecode($rong),urldecode($baoxiu),urldecode($gh),urldecode($idLock),urldecode($idBlack),urldecode($netLock),urldecode($weixiu),urldecode($yys),urldecode($MDM),urldecode($sn),urldecode($sn_or_imei_url),urldecode($country),urldecode($buy_time),$limit);
        $show_table_server = $this->get_table_th($order_id);// 获取已经查过的SERVERID list

        //测试数据 返到前台 -作处理
        $data_arr=array();
        foreach ($order_phone_list as $k => $row) {
            $row = $this->get_phone_info($row);
            $data_arr[$k] =  $row;
        }

        // 获取分页显示
        $page = $order_phone_list->render();

        //未完成数
        $no_complete_server= Db::name('cx_server_info')->where('order_id',$order_id)->where('is_pay',0)->count();
        //一共查询数量
        $all_server_count= Db::name('cx_server_info')->where('order_id',$order_id)->count();
        // 计算完成 百分比
        $complete_bfb=  $all_server_count<=0 ?100: 100 - floor($no_complete_server/$all_server_count*100);


        $this->assign('order_type',$order_type);//订单类型
        $this->assign('order_title',$order_title);//订单名称

        $this->assign('total',$order_phone_list->total());//总数
        $this->assign('lastPage',$order_phone_list->lastPage());//记录页
        $this->assign('currentPage',$order_phone_list->currentPage());//当前页
        $this->assign('fuwu_list',$this->get_chaxun_list(1)); //获取服务列表
        $this->assign('complete_bfb',$complete_bfb); //已完成订单的百分比

        $this->assign('order_phone_list',$order_phone_list); //已完成订单的百分比

        $this->assign('order_list',$data_arr);
        $this->assign('order_page',$page);
        $this->assign('no_complete_server',$no_complete_server);
        $this->assign('order_id',$order_id);
        $this->assign('show_table',$show_table_server);
        return $this->fetch();

    }


    /**
     * 订单详情页
     * @param $id               订单主键id
     * @param null $wxdata      微信数据
     * @param null $order_type  订单类型
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add($order_id = null,$order_type=1)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //判断是否是二次添加
            if (empty($order_id)) {
                if (array_key_exists('order_title', $data)) {
                    if (empty($data['order_title'])) {
                        $this->error("亲，未填标题喔！");
                    }
                }
            } else {
                //add_order函数必传的参数
                $data['order_title'] = null;
            }
            //判断订单类型
            //查国别
            if ($order_type == 2) {
                $server_id = [1, 9];
                $_POST["id_list"] = $server_id;
            //查GSX
            } elseif($order_type == 3) {
                $server_id = $_POST["id_list"];
                $server_id[] = '1';
            } else {
                $server_id = $_POST["id_list"];
            }
            //判断是否选择服务
            if (!array_key_exists('id_list', $_POST)) {
                $this->error("未勾选查询服务！");
            }

            $res =  add_order($server_id,$data['imeitext'],0,$data['order_title'],$order_id,$order_type);

            if($res['code'] > 1){
                $this->error($res['message']);
            }elseif($res['code'] == 1){
                $this->success($res['message'],$res['url']);
            }

        }
    }


    // 软删除，只做数据隐藏 (批量删除)
    public function delete(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $id_list = $data["id"];
            if(empty($id_list)){
                write_json(44,'请选择要删除的订单号');
            }
            //拆分数组
            $id_list=explode(",",$id_list);
            // 开始删除
            $rs = Db::name('cx_order_phone')->where('id','in',$id_list)->update(["is_del"=>1]);
            if ($rs){
                write_json(0,'删除成功');
            }else{
                write_json(44,'删除失败，请稍后重试');
            }

        }
    }

    //订单列表  表格内容与查询
    public function customize(){
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
                if (Db::name('admin_user')->where('id', UID)->update(['advance_biao_json' => json_encode($baoarr)])) {
                    $this->success('设置成功');
                } else {
                    $this->error('设置失败，请稍后重试！');
                }
            }
        }else{
            /** 获取个性化表格数据   */
            // 复制过来的源码 继续硬编码
            $user = Db::name('admin_user')->where('id', UID)->find();
            //表格显示设置字段
            $user_set_json = isset($user['advance_biao_json']) ? $user['advance_biao_json'] : '{"imeiorsn":"1","num":"100","xhao":"1","t":[1,2,3,4,5,6],"b":[1,2,3,4,5,6,7,8,9,10,11,12]}';
            $user_set_arr = json_decode($user_set_json, true);
            //表格显示头部字段
            $biaogezaidingyi = ['1' => '#', '2' => '手机序列号', '3' => 'IMEI',  '4' => 'IMEI2',  '5' => '型号' ,'6' => '状态/进度',
                '7'=>'设备型号','8'=>'保修类型','9'=>"购买日期","10"=>"过保日期"
                ,"11"=>"官换","12"=>"电话支持","13"=>"购买验证","14"=>"借出设备","15"=>"激活状态","16"=>"激活锁"
                ,"17"=>"ID监控","18"=>"ID黑名单","19"=>"网络锁","22"=>"正在维修",'23'=>"运营商",'24'=>"MDM配置锁"] ;
            //excle 下载头部
            $exxiazaidingyi =$biaogezaidingyi;

            //标记是不是要显示这个表格字段
            $user_table_set = array();
            foreach($biaogezaidingyi as $key=>$val){
                $user_table_set[$key]['field'] = $val;
                if(in_array($key,$user_set_arr['t'])){ //如果包含这个列 显示出来
                    $user_table_set[$key]['show']='show';
                }
            }


            //标记是不是要下载时需要不需要这个表格字段
            $user_excle_set = array();
            foreach($exxiazaidingyi as $key=>$val){
                $user_excle_set[$key]['field'] = $val;
                if(in_array($key,$user_set_arr['b'])){ //如果包含这个列 显示出来
                    $user_excle_set[$key]['show']='show';
                }
            }


            $this->assign('user_table_set', json_encode($user_table_set,true)); //表格显示设置
            $this->assign('user_excle_set', json_encode($user_excle_set,true));   //表格下载设置
            $this->assign('biao_json',$user_set_arr);
            return $user_set_arr;

        }
//        $this->error('设置失败');
    }
    // sn转imei号
    public function sn_to_imei(){
        $sn = input("sn");
        $key = "NiBBvHpo0So3cWJE6dQBwV0h";
        $server_id = 41;
        $sn = "G6TXVHDZKPJ5";
        $url = sprintf("http://www.iphonechecks.services/api/place-order/?api_key=%s&service=%d&imei=%s",$key,$server_id,$sn);
        $rs = Core::get($url);
        $json_rs = json_decode($rs,true);
        // 判断结果
        if ($json_rs['status'] == 'success'){
            $content = $json_rs['response']['result'];

            // 通过正则获取相关信息
            // 手机型号
            $model = "";
            $rss = preg_match('/Model \(型号\) : (.*?)<br>/',$content,$model);
            // imei1和imei2
            $imei1 = "";
            $imei2 = "";
            preg_match('/Model \(型号\) : (.*?)<br>/',$content,$model);
            preg_match('/IMEI \(串号\) : (.*?)<br>/',$content,$imei1);
            preg_match('/IMEI2 \(串号2\) : (.*?)<br>/',$content,$imei2);
            // sn
            $sn = "";
            preg_match('/Serial Number \(序列号\) : (.*?)<br>/',$content,$sn);
            // 预计购买日期
//            $buy_time = "";
//            preg_match('/Estimated Purchase Date \(预计购买日期\) : (.*?)<br>/',$content,$buy_time);

        }
    }

    /**
     * 高级查询  API查询服务
     * $order 订单编号
     */
    public function api_cx_server($order = 1){
        $sql = "SELECT s.* from nb_cx_server_info as s left join nb_cx_order_phone p on s.server_cx_biaoshi =p.id 

            left join nb_cx_order_info o on p.order_id = o.id where o.id= $order and status = 0";
        $data = Db::query($sql);//根据订单号获取待查询数据

        //实例化api类
        $check = new Check();

        $sql1 = "SELECT s.mp_sn,s.mp_imei from nb_cx_order_phone as s left join nb_cx_order_info p on s.order_id =p.id where p.id= $order";
        $arr = Db::query($sql1);//根据订单号获取sn或imei号

        $sn_or_imei = empty(@$arr[0]['mp_sn']) ? @$arr[0]['mp_imei'] : @$arr[0]['mp_sn'];//获取sn或imei 有其一就行

        $successNum = 0;//成功数
        $errorNum = 0;//失败数
        $successIntegral = 0;//成功扣除总积分
        $errorIntegral = 0;//失败返还总积分

        //循环调用api查询相关服务
        foreach ($data as $k => $v){
            switch ($v['server_id']){
                //保修查询
                case 1:
                    //TODO 保修api目前在c#上跑
//                    $result = $check->advanced_wx($sn_or_imei);
                    break;
                //激活锁查询
                case 2:
                    $result = $check->IdLock($sn_or_imei);
                    break;
                //ID黑白名单
                case 3:
                    $result = $check->id_lock_and_blacklist($sn_or_imei);
                    break;
                //ID锁监控
                case 4:
                    //TODO 监控api目前在c#上跑
//                    $result = $check->id_monitor($sn_or_imei);
                    break;
                //网络锁
                case 5:
                    $result = $check->net_lock($sn_or_imei);
                    break;
                //改用运营商检查（原来网络黑名单）
                case 6:
                    $result = $check->network_lock_black($sn_or_imei);
                    break;
                //正在维修
                case 7:
                    //TODO 正在开通
                    break;
                //网络锁监控
                case 8:
                    //TODO 正在开通
                    $result = $check->net_monitor($sn_or_imei);
                    break;
                //国别文字
                case 9:
                    $result = $check->country_order($sn_or_imei);
                    break;
                default:
                    return json(["status" => false, "tishi" => "查询类型失败！"]);
                    break;
            }

            //目前网络锁接口是可以的，所以返回的数据格式根据网络锁接口返回的格式确定（以后也有可能每个接口格式不一，需要修改）
            if($result['status']){// true 查询成功 false 查询失败
                $successIntegral += $v['amount'];
                $status = 1;//状态 1 成功
                $successNum++;
            }else{
                $errorIntegral += $v['amount'];
                $status = 2;//状态 2 失败
                $errorNum++;
            }
            Db::name('cx_server_info')->where($data['id'])->update(['status' => $status,'json_data' => json_encode($result['tishi'])]);

        }
        //TODO 生成一条成功扣除总积分的流水
        //TODO 生成一条失败返还总积分的流水
        //暂时用数组形式返回，有需要可以返回json
        return ['successNum' => $successNum , 'errorNum' => $errorNum , 'successIntegral' => $successIntegral , 'errorIntegral' => $errorIntegral];
    }
    public function testProxy(){
        $url = "ip.gs";
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
        curl_setopt($ch, CURLOPT_PROXY, "218.104.255.13:49860"); //代理服务器地址

        //curl_setopt($ch, CURLOPT_PROXYUSERPWD, ":"); //http代理认证帐号，username:password的格式
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //使用http代理模式

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // 获取头部信息
        curl_setopt($ch, CURLOPT_HEADER, 1);
        // 返回原生的（Raw）输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS,1);
//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
        // $output contains the output string
        $output = curl_exec($ch);
        //    echo $output;
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//        // 解析COOKIE
//        preg_match("/set\-cookie:([^\r\n]*)/i", $header, $matches);

        curl_close($ch);

    }
    public function testRedis(){
        $ip = Core::getIpProxyByRandom();
//        var_dump($ip);
    }


    /**
    获取手机目前地应的信息
     */
    public function get_phone_info($phone_info){
        //表格显示头部字段
        $biaogezaidingyi = ['1' => '#', '2' => '手机序列号', '3' => 'IMEI',  '4' => 'IMEI2',  '5' => '型号' ,'6' => '状态/进度',
            '7'=>'设备型号','8'=>'保修类型','9'=>"购买日期","10"=>"过保日期"
            ,"11"=>"官换","12"=>"电话支持","13"=>"购买验证","14"=>"借出设备","15"=>"激活状态","16"=>"激活锁"
            ,"17"=>"ID监控","18"=>"ID黑名单","19"=>"网络锁","22"=>"正在维修",'23'=>"运营商",'24'=>"MDM配置锁"] ;
        ;

        //双引号引起的错误
        $word=$phone_info['gsx_word'];
        if(strlen($word)>99 ){
            $word =  json_decode($word,true);
            $word  = urlencode($word['word']) ;
        }else{
            $word='';
        }
        $phone_info['gsx_word']= $word;
        //双引号引起的错误
        $zhengji=$phone_info['zhengji'];
        if(strlen($zhengji)>99 ){
            $zhengji=  json_decode($zhengji ,true);
            $zhengji = urlencode($zhengji['word']) ;
        }else{
            $zhengji='';
        }
        $phone_info['zhengji']= $zhengji;

        //保修天数
        $baoxiu_day =( strtotime($phone_info['mp_bx_endtime']) -  strtotime(   date('Ymd',time()) ) ) / 86400 ;
        $jisuan_start_time='';
        //如果保修结束不为空的时候计算开始时间
        if(intval($phone_info['mp_bx_endtime'])>0  ){
            $bx_year =$phone_info['baoxiu_type'] == 'PD'?'-2 year +1 day':'-1 year +1 day';
            $jisuan_start_time =date('Ymd',strtotime($bx_year, strtotime($phone_info['mp_bx_endtime'])));
        }
        //保修开始时间
        $bx_start_date =intval($phone_info['mp_buy_start'])!=0?$phone_info['mp_buy_start']:$jisuan_start_time;
        //拼接机器对应字段  00
        $row = [
            'id' => array('value'=>$phone_info['id']),
            'server_sn' => array('value'=>strtoupper($phone_info['mp_sn'])),
            'server_imei' => array('value'=>$phone_info['mp_imei']),
            'server_imei2' => array('value'=>$phone_info['mp_imei2']),
            'server_model' => array('value'=>$phone_info['mp_model']),
            'server_net_banben' => array('value'=>$phone_info['mp_net']),
            'server_phone_color' => array('value'=>$phone_info['mp_color']),
            'server_phone_size' => array('value'=>$phone_info['mp_rongliang']),
            //保修类型
            'server_phone_bx_lx' => @array('value'=>isset(lang('baoxiu_type')[$phone_info['baoxiu_type']])?lang('baoxiu_type')[$phone_info['baoxiu_type']]:$phone_info['baoxiu_type']),
            'server_buy_date' => array('value'=>$bx_start_date),  //保修开始时间
            'server_bx_end_date' => array(
                'value'=>$phone_info['mp_bx_endtime'] ,
                'end_day_num' => $phone_info['mp_bx_endtime'] !=0 ?$baoxiu_day: 0,
            ),
            'server_guanhuan' => array('value'=>lang('is_guanhuan')[$phone_info['is_guanhuan']]),
            'server_phone_zhichi' => array('value'=>lang('phone_zhichi')[$phone_info['phone_zhichi']]),
            'server_buy_yanzheng' => array('value'=>lang('buy_yanzheng')[$phone_info['buy_yanzheng']]),
            'server_jiechu' => array('value'=>lang('phone_jiechu')[$phone_info['phone_jiechu']]),
            'server_buy_jihuo' => array('value'=>lang('phone_jihuo')[$phone_info['phone_jihuo']]),
            'server_id_lock' => array('value'=>lang('id_lock')[$phone_info['id_lock']]),
            'id_jiankong' => array('value'=>intval($phone_info['id_jiankong_e_time'])>time()?1:0),  //监控结束时间大于现在时间 就是开的
            'server_id_black' => array('value'=>lang('id_lock_black')[$phone_info['id_lock_black']]),
            'server_net_lock' => array('value'=>lang('net_lock')[$phone_info['net_lock']]),
            'server_net_lock_black' => array('value'=>lang('net_lock_black')[$phone_info['net_lock_black']]),
            'server_weixiu' => array('value'=>lang('weixiu')[$phone_info['weixiu']]),
            'server_yunyingshang' => array('value'=>$phone_info['yunyingshang']),
            'server_yunyingshang_country' => array('value'=>$phone_info['yunyingshang_country']),
            'server_sale_country' => array('value'=>isset(lang('sale_country')[$phone_info['sale_country']])?lang('sale_country')[$phone_info['sale_country']]:$phone_info['sale_country']),
            'server_gsx_img' => array('value'=> explode(',',$phone_info['gsx_img'])),
            'server_gsx_word' => array('value'=> $word),
            'server_zhengji' => array('value'=> $zhengji),
            'server_is_tihuan' => array('value'=>lang('is_tihuan')[$phone_info['is_tihuan']]),
            'server_MDM' => array('value'=>lang('MDM')[$phone_info['MDM']]),
            'info' =>$phone_info
        ] ;

        //计算 颜色与版本
        if( ($phone_info['mp_sn']!='' || $phone_info['sn_4'] !='') &&  $phone_info['mp_model']=='') {
            $_z_sn = $phone_info['sn_4'] !=''?$phone_info['sn_4']:$phone_info['mp_sn'];
            $wangluo = Core::getModelBySn4($_z_sn,$phone_info['id']);
            if ($wangluo != "") {
                $row["server_model"]["value"] = $wangluo[1];
                $row["server_net_banben"]["value"] = $wangluo[4];
                $row['server_phone_color']['value'] = $wangluo["3"];
                $row['server_phone_size']['value'] = $wangluo["2"];
            }
        }
        return $row;
    }


    /** 获取服务列表
     * @param $type 查询的类型 目前是高级查询type 1 gsx 查询 2
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_chaxun_list($type){
//        dump($type);
//        die;
        $order = ['paixu'=>'asc'];
        $condition = ['type'=>$type,'weihu'=>0];

        $chaxun_list  = Db::name('chaxun_config')->
        where($condition)->
        order($order)->select();

        return $chaxun_list;
    }


    //AJAX 获取当页的订单详情信息
    public function get_order_list($id,$jiankong,$jihuo,$color,$guobao,$model,$net,$rong,$baoxiu,$gh,$idLock,$idBlack,$netLock,$weixiu,$yys,$MDM,$sn,$sn_or_imei_url,$country,$buy_time,$limit){
        /**筛选状态判断*/
        //首先判断用户是否选择过筛选，若第一次进入为NULL为无筛选状态则默认全选，若筛选过，则会将值存在url上，当下次筛选另一字段时，会将上次筛选状态一并传入，达到联动筛选效果，以下同理
        if(isset($_GET['jiankong']) || $jiankong == null){//监控 isset($_GET['jiankong'])
            $jiankong = isset($_GET['jiankong']) ? $_GET['jiankong'] : -1;
        }
        if(isset($_GET['jihuo']) || $jihuo == null){//激活  isset($_GET['jihuo'])
            $jihuo = isset($_GET['jihuo']) ? $_GET['jihuo'] : -1;
        }
        if(isset($_GET['color']) || $color == null){//颜色  isset($_GET['color'])
            $color = isset($_GET['color']) ? $_GET['color'] : 'all';
        }
        if(isset($_GET['guobao']) || $guobao == null){//过保时间
            $guobao = isset($_GET['guobao']) ? $_GET['guobao'] : 'all';
        }
        if(isset($_GET['model']) || $model == null){//设备型号
            $model = isset($_GET['model']) ? $_GET['model'] : 'all';
        }
        if(isset($_GET['net']) || $net == null){//网络版本
            $net = isset($_GET['net']) ? $_GET['net'] : 'all';
        }
        if(isset($_GET['rong']) || $rong == null){//手机容量
            $rong = isset($_GET['rong']) ? $_GET['rong'] : 'all';
        }
        if(isset($_GET['baoxiu']) || $baoxiu == null){//保修类型
            $baoxiu = isset($_GET['baoxiu']) ? $_GET['baoxiu'] : 'all';
        }
        if(isset($_GET['gh']) || $gh == null){//$gh为官换
            $gh = isset($_GET['gh']) ? $_GET['gh'] : -1;
        }
        if(isset($_GET['idLock']) || $idLock == null){//id锁
            $idLock = isset($_GET['idLock']) ? $_GET['idLock'] : -1;
        }
        if(isset($_GET['idBlack']) || $idBlack == null){//ID锁黑白
            $idBlack = isset($_GET['idBlack']) ? $_GET['idBlack'] : -1;
        }
        if(isset($_GET['netLock']) || $netLock == null){//网络锁
            $netLock = isset($_GET['netLock']) ? $_GET['netLock'] : -1;//$netLock
        }
        if(isset($_GET['weixiu']) || $weixiu == null){//维修状态
            $weixiu = isset($_GET['weixiu']) ? $_GET['weixiu'] : 1;//$weixiu
        }
        if(isset($_GET['yys']) || $yys == null){//运营商
            $yys = isset($_GET['yys']) ? $_GET['yys'] : 'all';//$yys
        }
        if(isset($_GET['MDM']) || $MDM == null){//MDM锁
            $MDM = isset($_GET['MDM']) ? $_GET['MDM'] : -1;//$yys
        }
        if(isset($_GET['sn']) || $MDM == null){//sn状态
            $sn = isset($_GET['sn']) ? $_GET['sn'] : 'all';
        }
        if(isset($_GET['sn_or_imei_url']) || $sn_or_imei_url == null){
            $sn_or_imei_url = isset($_GET['sn_or_imei_url']) ? $_GET['sn_or_imei_url'] : 'all';
        }
        if(isset($_GET['country']) || $country == null){
            $country = isset($_GET['country']) ? $_GET['country'] : 'all';
        }
        if(isset($_GET['buy_time']) || $buy_time == null){//购买日期时间段
            $buy_time = isset($_GET['buy_time']) ? $_GET['buy_time'] : 'all';
        }
        //每页显示多少条默认显示页数
        if(isset($_GET['limit']) || $limit == 40){//isset($_GET['limit'])
            $limit = isset($_GET['limit']) ? $_GET['limit'] : 1500;
        }
        if($limit >= 1501){
            $limit = 1500;//最大只能500条
        }
        $this->assign('limit',$limit);//条数显示
        /**以上为判断本次连接需要筛选的各个字段的状态，当用户选择需要进行筛选的字段时使用GET传值，更改状态，其他字段状态则存进url中用tp规则解析接收，以达到联动筛选*/

//        dump($_GET);
        /**筛选列表 (新代码)*/
        $filed_name = array('mp_color','mp_model','mp_net','mp_rongliang','baoxiu_type','yunyingshang','status','sale_country');//数据库对应字段
        $filed_list = array('color_list','model_list','net_list','rongliang_list','baoxiu_list','yunyingshang_list','status_list','country_list');// $this->assign($key,$value)的键 $key
        foreach ($filed_name as $k => $v){
            if($v == 'status'){
                $sql = "select is_tihuan,$v ,count($v) as count from nb_cx_order_phone where is_del = 0 and order_id=$id group by $v,is_tihuan ORDER BY $v ASC";
            }else{
                $sql = "select $v ,count($v) as count from nb_cx_order_phone where is_del = 0 and  order_id=$id group by $v ORDER BY $v ASC";
            }
            $list = Db::query($sql);
            //判断序列号状态列表
            if($v == 'status'){
                foreach ($list as $ks => $vs){
                    if($vs['status'] == 0){
                        $list[$ks]['name'] = "正常";
                    }else if($vs['status'] == 1){
                        $list[$ks]['name'] = "序列号无效";
                    }else if($vs['status'] == 2){
                        $list[$ks]['name'] = "串号错误";
                    }
                    if($vs['is_tihuan'] == 1){
                        $list[$ks]['status'] = "99";
                        $list[$ks]['name'] = "已替换";
                    }
                    //前端多选默认勾选判断,若上次选中了本选项，则本次默认勾选
                    if(strstr($sn,(string)$vs['status']) && $vs['is_tihuan'] == 0){
                        $list[$ks]['default_option'] = "1";
                    }else if(strstr($sn,(string)$vs['status']) === '0' && $vs['is_tihuan'] == 0){
                        $list[$ks]['default_option'] = "1";
                    }else if(strstr($sn,"99") && $vs['is_tihuan'] == 1){
                        $list[$ks]['default_option'] = "1";
                    }
                }
//                dump($sn);
            }elseif ($v == 'baoxiu_type'){
                //保修
                $baoxiu_list = lang('baoxiu_type');
                foreach ($list as $ks => $vs){
                    if($list[$ks]['baoxiu_type'] == null){
                        $list[$ks]['baoxiu'] = "未知";
                        $list[$ks]['baoxiu_type'] = "未知";
                    }else{
//                        $list[$ks]['baoxiu'] = $baoxiu_list[$vs['baoxiu_type']];
                        $list[$ks]['baoxiu'] = isset($baoxiu_list[$vs['baoxiu_type']])?$baoxiu_list[$vs['baoxiu_type']]:$vs['baoxiu_type'];
                    }
                    //前端多选默认勾选判断,若上次选中了本选项，则本次默认勾选
                    if(strstr($baoxiu,$list[$ks]['baoxiu_type'])){
                        $list[$ks]['default_option'] = '1';
                    }
                }
            }elseif ($v == 'mp_model'){
                foreach ($list as $ks => $vs){
                    //前端多选默认勾选判断,若上次选中了本选项，则本次默认勾选
                    if(strstr($model,$vs['mp_model'])){
                        $list[$ks]['default_option'] = "1";
                    }
                }
            }elseif ($v == 'mp_net'){
                foreach ($list as $ks => $vs){
                    if($list[$ks]['mp_net'] == null){
                        $vs['mp_net'] = "未知";
                        $list[$ks]['mp_net'] = "未知";
                        $list[$ks]['mp_net'] = "未知";
                    }

                    //前端多选默认勾选判断,若上次选中了本选项，则本次默认勾选
                    if(strstr($net,$vs['mp_net'])){
                        $list[$ks]['default_option'] = "1";
                    }
                }
            }elseif ($v == 'mp_color'){
                $color_arr = explode(',',$color);
                foreach ($list as $ks => $vs){
                    if($list[$ks]['mp_color'] == null){
                        $vs['mp_color'] = "未知";
                        $list[$ks]['mp_color'] = "未知";
                        $list[$ks]['mp_color'] = "未知";
                    }
                    //前端多选默认勾选判断,若上次选中了本选项，则本次默认勾选
                    //$color_arr为本次筛选的颜色，$vs['mp_color']是本订单有的颜色，已有的颜色若跟本次选择的颜色匹配，则默认选择（上下同理,只不过有些是不转数组直接字符串用strstr判断）
                    if(in_array($vs['mp_color'],$color_arr)){
                        $list[$ks]['default_option'] = "1";
                    }
                }
            }elseif ($v == 'mp_rongliang'){
                foreach ($list as $ks => $vs){
                    if($list[$ks]['mp_rongliang'] == null){
                        $vs['mp_rongliang'] = "未知";
                        $list[$ks]['mp_rongliang'] = "未知";
                        $list[$ks]['mp_rongliang'] = "未知";
                    }

                    //前端多选默认勾选判断,若上次选中了本选项，则本次默认勾选
                    if(strstr($rong,$vs['mp_rongliang'])){
                        $list[$ks]['default_option'] = "1";
                    }
                }
            }elseif ($v == 'sale_country'){
                $sale_country_list = lang('sale_country');//销售国家
                foreach ($list as $ks => $vs){
                    if($list[$ks]['sale_country'] == null){
                        $list[$ks]['sale_country_name'] = "未知";
                        $list[$ks]['sale_country'] = "未知";
                    }else{
                        $list[$ks]['sale_country_name'] = $sale_country_list[$vs['sale_country']];
                    }
                    //前端多选默认勾选判断,若上次选中了本选项，则本次默认勾选
                    if(in_array($vs['sale_country'],explode(',',$country))  ){
                        $list[$ks]['default_option'] = "1";
                    }
                }
            }


            //将count的值为0的从数组中移除，其余的修改为未知
            if($list[0][$v] === null && intval($list[0]['count']) != 0){
                $list[0][$v] = "未知";
            }else if(intval($list[0]['count']) == 0){
                unset($list[0]);
                $list = array_values($list);
            }

//            dump($list);
            $this->assign($filed_list[$k],$list);
        }
//        exit;
        /**以上是将本次订单所有设备已有的类型进行筛选，如本批订单 设备型号仅有iPhone 8 Plus、iPhone XS Max两种类型，则筛选内容仅有这两条（并且默认选中逻辑） */
        if($guobao == '已过保'){
            $guobao_default_option = 1;
            $this->assign('guobao_default_option',$guobao_default_option);
        }else if($guobao == '有保'){
            $guobao_default_option = 1;
            $this->assign('guobao_default_options',$guobao_default_option);
        }elseif ($guobao != 'all'){
            $guobao_default_arr = explode(",", $guobao);
            if($guobao_default_arr > 1){
                $this->assign('guobao_default_arr',$guobao_default_arr);
            }
        }
        if($guobao == '有保,已过保'){
            $this->assign('guobao_default_option',1);
            $this->assign('guobao_default_options',1);
        }
        //购买日期时间回显
        if($buy_time != 'all'){//不等于all说明有数据
            $buy_time_default_arr = explode(",", $buy_time);
            $this->assign('buy_time_default_arr',$buy_time_default_arr);//数组下标 0 为起始时间，1 为结束时间
        }
        /**以上是默认选中 */

        //前端条件查询数组（这段必须要加，将上次筛选条件加进url达到联合筛选） 这段数组负责将每次连接的筛选状态存在url上
        $param = [
            'id'        => $id,
            'jiankong'  => urlencode($jiankong),
            'jihuo'     => urlencode($jihuo),
            'color'     => urlencode($color),
            'guobao'    => urlencode($guobao),
            'model'     => urlencode($model),
            'net'       => urlencode($net),// 由于 / 符好在url上会被转义，导致获取不到完整的数据，所以先替换成 . ，等下次需要筛选时在替换回来进行查找 str_replace("/",".",$net)
            'rong'      => urlencode($rong),
            'baoxiu'    => urlencode($baoxiu),
            'gh'        => urlencode($gh),
            'idLock'    => urlencode($idLock),
            'idBlack'   => urlencode($idBlack),
            'netLock'   => urlencode($netLock),
            'weixiu'    => urlencode($weixiu),
            'yys'       => urlencode($yys),//运营商
            'MDM'       => urlencode($MDM),
            'sn'        => urlencode($sn),
            'sn_or_imei_url' => urlencode($sn_or_imei_url),
            'country'   => urlencode($country),
            'buy_time'  => urlencode($buy_time),
            'limit'     => $limit,//每页条数
        ];
        $this->assign('param',$param);


        /**查询条件*/
        $where = "a.order_id = $id and a.is_del = 0 and user_id=".UID;
        $where = $this->query_where($where,$jiankong,$jihuo,$color,$guobao,$model,$net,$rong,$baoxiu,$gh,$idLock,$idBlack,$netLock,$weixiu,$yys,$MDM,$sn,$sn_or_imei_url,$country,$buy_time);
//        dump($where);exit;

        /**以上为筛选查询条件的拼接，根据之前筛选条件的判断得到的数据，进行筛选查询*/

        //分页url处理  这里负责将筛选之后的url拼进分页url中
//        $path = '/vip/chaxun/order_detail/id/'.$id.'/jiankong/'.$jiankong.'/jihuo/'.$jihuo.
//            '/color/'.$color.'/model/'.$model.'/net/'.str_replace("/",".",$net).'/rong/'.$rong.
//            '/baoxiu/'.$baoxiu.'/gh/'.$gh.'/idLock/'.$idLock.'/idBlack/'.$idBlack.'/netLock/'.$netLock.
//            '/weixiu/'.$weixiu.'/yys/'.$yys.'/MDM/'.$MDM.'/sn/'.$sn.'/sn_or_imei_url/'.$sn_or_imei_url.
//            '/limit/'.$limit;
        //找出订单内所有的机器
        $order_phone_list =  Db::name('cx_order_phone a')
            ->field('a.*,count(c.id) as count')
            ->join('cx_order_info b','a.order_id=b.id','left')
            ->join('cx_server_info c','a.order_id=c.order_id and a.server_cx_biaoshi = c.server_cx_biaoshi and c.is_pay=0','left')
            ->where($where)
            ->order('a.id','desc')
            ->group('a.server_cx_biaoshi')
            ->paginate($limit)//,false,['path'=>$path]
            ->each(function ($item,$key){
                return $item;
            })
        ;
//        die( Db::name('cx_order_phone a')->getLastSql());
        return  $order_phone_list;
    }

    /**
     * 定时刷新
     */
    public function refresh($id = null,$jiankong = null,$jihuo = null,$color = null,$guobao = null,$model = null,$net = null,$rong = null,$baoxiu = null,$gh = null,$idLock = null,$idBlack = null,$netLock = null,$weixiu = null,$yys = null,$MDM = null,$sn = null,$sn_or_imei_url = null,$country = null,$buy_time = null,$limit = 40){
        $order_phone_list = $this->get_order_list($id,urldecode($jiankong),urldecode($jihuo),urldecode($color),urldecode($guobao),urldecode($model),urldecode($net),urldecode($rong),urldecode($baoxiu),urldecode($gh),urldecode($idLock),urldecode($idBlack),urldecode($netLock),urldecode($weixiu),urldecode($yys),urldecode($MDM),urldecode($sn),urldecode($sn_or_imei_url),urldecode($country),urldecode($buy_time),$limit);

//        $order_id = intval($id);
//        $show_table_server = $this->get_table_th($order_id);// 获取已经查过的SERVERID list
//        $this->customize();
        //测试数据 返到前台 -作处理
        // 获取分页显示
//        $page = $order_phone_list->render();
        //获取订单名称
//        $order_title = Db::name('cx_order_info')->where('id',$order_id)->find()['order_title'];

//        dump($page);exit;
        $data_arr=array();
        foreach ($order_phone_list as $k => $row) {
            $row = $this->get_phone_info($row);
            $data_arr[$k] =  $row;
        }
//        $this->assign('order_title',$order_title);//订单名称
//        $this->assign('total',$order_phone_list->total());//总数
//        $this->assign('lastPage',$order_phone_list->lastPage());//记录页
//        $this->assign('currentPage',$order_phone_list->currentPage());//当前页
//        $this->assign('fuwu_list',$this->get_chaxun_list(1)); //获取服务列表
//        $this->assign('order_page',$page);
//        $this->assign('order_id',$order_id);
//        $this->assign('show_table',$show_table_server);
        //找到订单下是不是有未结束的服务

        $no_complete_server= Db::name('cx_server_info')->where('order_id',$id)->where('is_pay',0)->count();
        //一共查询数量
        $all_server_count= Db::name('cx_server_info')->where('order_id',$id)->count();
        // 计算完成 百分比
        $complete_bfb=  $all_server_count<=0 ?100: 100 - intval(floor($no_complete_server/$all_server_count*100));
        write_json($no_complete_server,$complete_bfb,$data_arr);

    }

    /**
     * 图片识别 多个sn与imei
     */
    public function identify_sn_imei(){
        $img = new \app\wechatapp\controller\ImgIdentify();
        $res = $img->bdy_img_identify($_POST['img']);
        $arr = array_merge($res['sn'][0],$res['imei'][0]);
        if(count($arr) <= 0 ){
            write_json(-1,'未找到IMEI');
        }
        $str = implode("\n", $arr);
        write_json(0,$str);

    }


    //表格下载
    public function excel($order_id,$type,$th,$field,$id,$jiankong = null,$jihuo = null,$color = null,$guobao = null,$model = null,$net = null,$rong = null,$baoxiu = null,$gh = null,$idLock = null,$idBlack = null,$netLock = null,$weixiu = null,$yys = null,$MDM = null,$sn = null,$sn_or_imei_url = null,$country = null,$buy_time = null){
//        dump("excel下载");
//
//        exit;
        $data = $this->request->get();
        if ($order_id) {
            //判断是否是本人操作
            if (!Db::name('cx_order_info')->where('user_id', UID)->where('id', $order_id)->find()) $this->error('操作失败');
            if (empty($data['id']))
            {
                $where = "order_id = $id and is_del = 0";
                $where = $this->query_where($where,urldecode($jiankong),urldecode($jihuo),urldecode($color),urldecode($guobao),urldecode($model),urldecode($net),urldecode($rong),urldecode($baoxiu),urldecode($gh),urldecode($idLock),urldecode($idBlack),urldecode($netLock),urldecode($weixiu),urldecode($yys),urldecode($MDM),urldecode($sn),urldecode($sn_or_imei_url),urldecode($country),urldecode($buy_time));
                $where = str_replace("a."," ",$where);
//                dump($where);exit;
                //整单下载
                $order_list = Db::name('cx_order_phone')->where($where)->select();
            } else {
                //勾选下载
                $data['id'] = explode(",", $data['id']);
                $order_list = Db::name('cx_order_phone')->whereIn('id', $data['id'])->select();
            }
            //切割成数组
            $table_th = explode(",", $th);
            $table_field = explode(",", $field);
            //循环删除gsx文字、gsx图片、整机查询
            foreach($table_th as $k => $v){
                if($v == 'GSX文字' || $v == 'GSX图片' || $v == '整机查询'){//目前前端传进来的只有"GSX文字"，后期经过修改后可能字符串会有调整，所以还需修改
                    unset($table_th[$k]);
                }
            }
            $table_th = array_values($table_th);//重新排序
            $th_arr =[[
                '0' => 'sn_id',
                '1' => 10,
                '2' => '序列号',
            ]];
            //循环得到表头
            foreach ($table_th as $k=>$v) {
                $th_arr[]=  [
                    '0' => $table_field[$k] ,
                    '1' => 20,
                    '2' => $v
                ] ;
            }

            //双循环得到数据
            $field_arr=array();
            $table_td =array();
            $sn_id = 0;
            foreach ($order_list as $key =>$row ){
                $sn_id = $sn_id+1;
                $row= $this->get_phone_info($row);
                foreach ($table_field as $k=>$v) {
                    $table_td['sn_id'] = $sn_id;
                    $table_td[$v] = $row[$v]['value'];
                }
                $field_arr[$key]=$table_td;
            }

            //下载表格
            $Excel = new Excel();
            $Excel->export("aiguo", $th_arr, $field_arr);
            exit;
        }
    }

    /**
     * 将筛选查询条件进行拼接
     */
    public function query_where($where,$jiankong,$jihuo,$color,$guobao,$model,$net,$rong,$baoxiu,$gh,$idLock,$idBlack,$netLock,$weixiu,$yys,$MDM,$sn,$sn_or_imei_url,$country,$buy_time){
        //监控条件
        $nowtime = time();//当前时间
        if($jiankong == 1){
            //结束时间大于当前时间已开启监控
            $where .= " and id_jiankong_e_time > $nowtime";
        }else if($jiankong == 2){
            //结束时间小于当前时间未开启监控
            $where .= " and id_jiankong_e_time < $nowtime";
        }
        //激活状态条件
        if($jihuo == 1){
            //已激活
            $where .= " and phone_jihuo = $jihuo";
        }else if($jihuo == 2){
            //未激活
            $where .= " and phone_jihuo = $jihuo";
        }else if($jihuo == 3){
            //未知
            $where .= " and phone_jihuo = 0";
        }
        //颜色
        if(is_string($color)){
            $p_color = explode(",", $color);
            if(strstr($color,'未知')){//strstr($model,'未知')
                if($color == "未知"){
                    $where .= " and (a.mp_color is NULL or trim(a.mp_color)='')";//有可能只选一个未知
                }else{
                    $where .= " and ((a.mp_color is NULL or trim(a.mp_color)='') or a.mp_color IN (";

                    foreach($p_color as $k => $v){
                        if($k == count($p_color) - 1){
                            $where .= "'$v'))";
                        }else if($v != "未知"){
                            $where .= "'$v',";
                        }
                    }
                }
            }else if($color != 'all' && $color != ''){
                $where .= " and a.mp_color IN (";
                foreach($p_color as $k => $v){
                    if($k == count($p_color) - 1){
                        $where .= "'$v')";
                    }else{
                        $where .= "'$v',";
                    }
                }
            }
        }
        //过保日期
        if($guobao == '已过保'){
            $nowdate = date('Ymd',$nowtime);//20200105格式的日期
            $where .= "  and mp_bx_endtime < $nowdate";//小于今日的为已过保
        }else if($guobao == '有保'){
            $nowdate = date('Ymd',$nowtime);//20200105格式的日期
            $where .= "  and mp_bx_endtime > $nowdate";//大于今日的为有保
        }else if(isset($guobao)){
            $p_guobao = explode(",", $guobao);
            if(is_numeric($p_guobao[1]) && is_numeric($p_guobao[0])){
                $nowdate = date('Ymd',$nowtime + 60 * 60 * 24 * $p_guobao[0]);//20200105格式的日期
                $futuredate = date('Ymd',$nowtime + 60 * 60 * 24 * $p_guobao[1]);
                $where .= " and mp_bx_endtime between $nowdate and $futuredate";
            }
        }
        //型号模型
        if($model != 'all' && $model != null){
            $p_model = explode(",", $model);
            if(strstr($model,'未知')){//strstr($model,'未知')
                if($model == "未知"){
                    $where .= " and (a.mp_model is NULL or trim(a.mp_model)='')";//有可能只选一个未知
                }else {
                    $where .= " and ((a.mp_model is NULL or trim(a.mp_model)='') or a.mp_model IN (";
                    foreach($p_model as $k => $v){
                        if($k == count($p_model) - 1){
                            $where .= "'$v'))";
                        }else if($v != "未知"){
                            $where .= "'$v',";
                        }
                    }
                }
            }else{
                $where .= " and a.mp_model IN (";
                foreach($p_model as $k => $v){
                    if($k == count($p_model) - 1){
                        $where .= "'$v')";
                    }else{
                        $where .= "'$v',";
                    }
                }
            }

        }
        //网络版本
        if($net != 'all' && $net != null){
//            $net = str_replace(".","/",$net);//将之前替换成 . 的符号在替换会 /
            $p_net = explode(",", $net);
            if(strstr($net,'未知')){
                if($net == "未知"){
                    $where .= " and (a.mp_net is NULL or trim(a.mp_net)='')";//有可能只选一个未知
                }else {
                    $where .= " and ((a.mp_net is NULL or trim(a.mp_net)='') or a.mp_net IN (";
                    foreach($p_net as $k => $v){
                        if($k == count($p_net) - 1){
                            $where .= "'$v'))";
                        }else if($v != "未知"){
                            $where .= "'$v',";
                        }
                    }
                }
            }else{
                $where .= " and a.mp_net IN (";
                foreach($p_net as $k => $v){
                    if($k == count($p_net) - 1){
                        $where .= "'$v')";
                    }else{
                        $where .= "'$v',";
                    }
                }
            }
        }
        //容量 $rong
        if($rong != 'all' && $rong != null){
            $p_rong = explode(",", $rong);
            if(strstr($rong,'未知')){//strstr($model,'未知')
                if($rong == "未知"){
                    $where .= " and (a.mp_rongliang is NULL or trim(a.mp_rongliang)='')";//有可能只选一个未知
                }else {
                    $where .= " and ((a.mp_rongliang is NULL or trim(a.mp_rongliang)='') or  a.mp_rongliang IN (";
                    foreach($p_rong as $k => $v){
                        if($k == count($p_rong) - 1){
                            $where .= "'$v'))";
                        }else if($v != "未知"){
                            $where .= "'$v',";
                        }
                    }
                }
            }else{
                $where .= " and a.mp_rongliang IN (";
                foreach($p_rong as $k => $v){
                    if($k == count($p_rong) - 1){
                        $where .= "'$v')";
                    }else{
                        $where .= "'$v',";
                    }
                }
            }
        }
        //保修类型
        if($baoxiu != 'all' && $baoxiu != null){
            $p_baoxiu = explode(",", $baoxiu);
            if(strstr($baoxiu,'未知')){//strstr($model,'未知')
                if($baoxiu == "未知"){
                    $where .= " and (a.baoxiu_type is NULL or trim(a.baoxiu_type)='')";//有可能只选一个未知
                }else {
                    $where .= " and ((a.baoxiu_type is NULL or trim(a.baoxiu_type)='') or a.baoxiu_type IN (";
                    foreach($p_baoxiu as $k => $v){
                        if($k == count($p_baoxiu) - 1){
                            $where .= "'$v'))";
                        }else if($v != "未知"){
                            $where .= "'$v',";
                        }
                    }
                }
            }else{
                $where .= " and a.baoxiu_type IN (";
                foreach($p_baoxiu as $k => $v){
                    if($k == count($p_baoxiu) - 1){
                        $where .= "'$v')";
                    }else{
                        $where .= "'$v',";
                    }
                }
            }
        }
        //是否官换 $gh
        if($gh == 1){
            //是官换
            $where .= " and is_guanhuan = $gh";
        }else if($gh == 2){
            //非官换
            $where .= " and is_guanhuan = $gh";
        }else if($gh == 3){
            //未知
            $where .= " and is_guanhuan = 0";
        }
        //激活锁状态 $idLock
        if($idLock == 1){
            //有锁
            $where .= " and id_lock = $idLock";
        }else if($idLock == 2){
            //无锁
            $where .= " and id_lock = $idLock";
        }else if($idLock == 3){
            //未知
            $where .= " and id_lock = 0";
        }
        //id黑白名单 $idBlack
        if($idBlack == 1){
            //黑名单
            $where .= " and id_lock_black = $idBlack";
        }else if($idBlack == 2){
            //白名单
            $where .= " and id_lock_black = $idBlack";
        }else if($idBlack == 3){
            //未知
            $where .= " and id_lock_black = 0";
        }
        //网络锁 $netLock
        if($netLock == 1){
            //有锁
            $where .= " and net_lock = $netLock";
        }else if($netLock == 2){
            //无锁
            $where .= " and net_lock = $netLock";
        }else if($netLock == 3){
            //未知
            $where .= " and net_lock = 0";
        }
        //维修状态 $weixiu
        if($weixiu == 2){
            //未知
            $where .= " and weixiu = 0";
        }else if($weixiu == 3){
            //未维修
            $where .= " and weixiu <> -4 and weixiu <> -0";
        }else if($weixiu == -4){
            //维修中
            $where .= " and weixiu = $weixiu";
        }
        //运营商
        if($yys != 'all' && $yys != null){
            if($yys == "未知"){
                $where .= " and yunyingshang is null";
            }else if($yys != 'all'){
                $where .= " and yunyingshang = '$yys'";
            }
        }
        //MDM锁  $MDM
        if($MDM == 1){
            //开锁，即无锁
            $where .= " and MDM = $MDM";
        }else if($MDM == 2){
            //2关锁，即有锁
            $where .= " and MDM = $MDM";
        }else if($MDM == 3){
            //未知
            $where .= " and MDM = 0";
        }
        //sn状态
        if(isset($sn) && $sn != '' && $sn != '未知'){
            if(strstr($sn,'99')){
                $where .= " and (a.status in ($sn)";
                $where .= " or a.is_tihuan = 1)";
            }else{
                $where .= " and a.status in ($sn) and is_tihuan = 0";
            }
        }
        //销售国家
        if($country != 'all' && $country != null){
            $p_country = explode(",", $country);
            if(strstr($country,'未知')){//strstr($model,'未知')
                if($country == "未知"){
                    $where .= " and (a.sale_country is NULL or trim(a.sale_country)='')";//有可能只选一个未知
                }else {
                    $where .= " and ((a.sale_country is NULL or trim(a.sale_country)='') or  a.sale_country IN (";
                    foreach($p_country as $k => $v){
                        if($k == count($p_country) - 1){
                            $where .= "'$v'))";
                        }else if($v != "未知"){
                            $where .= "'$v',";
                        }
                    }
                }
            }else{
                $where .= " and a.sale_country IN (";
                foreach($p_country as $k => $v){
                    if($k == count($p_country) - 1){
                        $where .= "'$v')";
                    }else{
                        $where .= "'$v',";
                    }
                }
            }
        }
        //购买日期
        if($buy_time != 'all' && $buy_time != 'undefined'){
            $p_buy_time = explode(",", $buy_time);
            $where .= " and mp_buy_start between '$p_buy_time[0]' and '$p_buy_time[1]'";
        }
//        dump($where);exit;
        //多选点击筛选时还可以输入sn或者imei进行辅助查询 支持模糊查询
        if(isset($sn_or_imei_url) && $sn_or_imei_url != 'all' && $sn_or_imei_url != ''){
            if(strlen($sn_or_imei_url) < 4){
                $this->error("搜索字符太少");
            }
            $where .= " and (a.mp_sn like '%$sn_or_imei_url%' or a.mp_imei like '%$sn_or_imei_url%')";
        }
//        dump($where);exit;
        return $where;
    }

    // 读取OSS配置
    function update_aliyun(){
        //获取配置项，并赋值给对象$config
        $config=config('api.aliyun_oss');

        //实例化OSS
        $ossClient=new \OSS\OssClient($config['KeyId'],$config['KeySecret'],$config['Endpoint']);
        //try 要执行的代码,如果代码执行过程中某一条语句发生异常,则程序直接跳转到CATCH块中,由$e收集错误信息和显示
        try{
            //没忘吧，new_oss()是我们上一步所写的自定义函数
            //uploadFile的上传方法
            $res=    $ossClient->uploadFile($config['Bucket'], 'gsx_img/txt.txt', '../public/txt.txt');
            $res= $res['info']['url'];
            die($res);
            return json($res);
        } catch(OssException $e) {
            //如果出错这里返回报错信息
            return $e->getMessage();
        }
        return $oss;

    }

    /**
     * 上传指定的本地文件内容
     *
     * @param OssClient $ossClient OSSClient实例
     * @param string $bucket 存储空间名称
     * @param string $object 上传的文件名称
     * @param string $Path 本地文件路径
     * @return null
     */
    function uploadFile($bucket,$object,$Path){
        //try 要执行的代码,如果代码执行过程中某一条语句发生异常,则程序直接跳转到CATCH块中,由$e收集错误信息和显示
        try{
            //没忘吧，new_oss()是我们上一步所写的自定义函数
            $ossClient =$this->new_oss();
            //uploadFile的上传方法
            $res=    $ossClient->uploadFile($bucket, $object, $Path);
            return json($res);
        } catch(OssException $e) {
            //如果出错这里返回报错信息
            return $e->getMessage();
        }
    }


}






