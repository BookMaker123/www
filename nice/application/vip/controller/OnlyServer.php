<?php

namespace app\vip\controller;

use think\Controller;
use think\Db;
use app\common\Core;
use EasyWeChat\Factory;
use think\Url;
//use app\vip\controller\Check;


class OnlyServer extends Controller
{

    /**
     * 初始化
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 定时查询服务
     *
     * $id 主键id
     */
    public function only_server_query($id){
        set_time_limit(150);//运行时间设置3分钟
        $sql = "SELECT * from v_server_info where id = ".intval($id).' and status=0';//查询视图
        $data = @Db::query($sql)[0];
        if(!$data){
            write_json(-99,'此服务已经完成查询');
        }

        //手机状态不正常时
        if($data['phone_status'] !=0 ){
            Db::name('cx_server_info')->where('id',$data['id'])->update(['is_pay' => 1,'status'=>2,'remark'=>'手机状态：' . $data['phone_status']]);//若phone_status等于1，将本次查询打回并修改is_pay
            write_json(-78,'此手机序列号有误');
        }
        //若状态不为待查询或者实际查询大于或等于最大查询得时候，返回
        if(  $data['cx_count'] >= $data['max_cx_count']){
            Db::name('cx_server_info')->where('id',$data['id'])->update(['is_pay' => 1,'status'=>2]);//若phone_status等于1，将本次查询打回并修改is_pay
            write_json(-75,'实际查询大于或等于最大查询得时候');
        }
        //获取sn或imei 有其一就行
        $sn_or_imei = empty($data['mp_sn']) ? $data['mp_imei'] : $data['mp_sn'];

        //先查询手机表，查看服务是否已存在手机表中
        if($this->mobile_list($data)){//若存在则返回true
            if($data['is_weixin'] == 1){
                Core::get("https://www.aiguovip.com/vip/Only_Server/weixin_msg_push/server_id/".$id);
            }
            write_json(-70,'此数据已在手机表存在');
        }

        $check = new Check();

        switch ($data['server_id']){
            //保修查询
            case 1:
                //先查询数据库，如若数据库有数据得话则不调用接口，直接使用数据库里的数据
                $sql1 = "select * from nb_cx_server_info s left join nb_cx_order_phone p on  s.server_cx_biaoshi = p.server_cx_biaoshi where s.`status`=1 and s.server_id =1
                         and ( mp_sn= '$sn_or_imei' or mp_imei = '$sn_or_imei' or mp_imei2= '$sn_or_imei' ) limit 1 ";
                $baoxiu_data = @Db::query($sql1)[0];//获取保修数据
                if(empty($baoxiu_data)){//若为空则说明这台机器无人查询过，则调用接口
                    //TODO 保修api目前在c#上跑
//                    $result = $check->advanced_wx($sn_or_imei);
//                  $up_field = 'is_baoxiu';//order_phone表内对应的字段 ,用于后续更新用
                }else{
                    $baoxiu_arr = json_decode($baoxiu_data['json_data'],true);//这里面没有内存	颜色	网络，所以还需要去redis里面获取
                    $sn = $data['mp_sn'];//用sn获取
                    $wangluo = Core::getModelBySn4($sn);
                    $baoxiu_arr['capacity'] = $wangluo[2];//容量
                    $baoxiu_arr['color'] = $wangluo[3];//颜色
                    $baoxiu_arr['wangluo'] = $wangluo[4];//网络
                    $result = ['net_code' => 200,'status' => true,'tishi' => $baoxiu_arr , 'baoxiu' => $baoxiu_arr];
                }
                $this->up_data($id,$result,$data,'','',1);
                break;
            //激活锁查询
            case 2:
                $result = $check->IdLock($sn_or_imei);
                Core::logToDb($result,$id,'');//数据库记录调试日志  ID毕竟变态，所以不管成功与否都记录日志
                $up_field = 'id_lock';//order_phone表内对应的字段 ,用于后续更新用
                $key = 'if_id_lock';//返还数据键
                $this->up_data($id,$result,$data,$up_field,$key);
                break;
            //ID黑白名单
            case 3:
                $result = $check->id_lock_and_blacklist($sn_or_imei);
                $this->up_data($id,$result,$data,'','',3);
                break;
            //网络锁
            case 5:
//                $this->net_lock_i_imei($check,$id,$data);//i-imei用这两行代码，在其他接口崩了的时候在使用
//                return;
                $result = $check->net_lock($sn_or_imei,2,'',2);//iphonecheck用这个
                $up_field = 'net_lock';//order_phone表内对应的字段 ,用于后续更新用
                $key = 'ifnetlock';
                $this->up_data($id,$result,$data,$up_field,$key);
                break;
            //改用运营商检查（原来网络黑名单）
            case 6:
                $result = $check->network_lock_black($sn_or_imei);
                $up_field = 'yunyingshang_country';//order_phone表内对应的字段 ,用于后续更新用
                $key = 'str';
                $case = 6;
                $this->up_data($id,$result,$data,$up_field,$key,$case);
                break;
            //正在维修
            case 7:
                //TODO 正在开通
                $up_field = 'weixiu';//order_phone表内对应的字段 ,用于后续更新用
//                $this->up_data($id,$result,$data,$field,$sn_or_imei,$up_field);
                break;
            //国别文字
            case 9:
                //国别下单必须要保修先查
                $sql = "select a.*,b.`status`as bx_status ,b.remark as bx_remark from nb_cx_server_info a left join nb_cx_server_info b on
                        a.server_cx_biaoshi = b.server_cx_biaoshi and b.server_id =1 where a.id  = $id";
                $data_s = @Db::query($sql)[0];
                //进行下单之前先保证sn或imei正确
                if($data_s['bx_status'] == 2 || $data_s['bx_status'] === null){//为2则订单失败
                    if( $data_s['bx_status'] === null) $data_s['bx_remark']='添加国别查询异常';
                    Db::name('cx_server_info')->where('id',$id)->update(['status' => 2 ,'remark' => $data_s['bx_remark'] , 'cx_time' => time() , 'is_pay' => 1]);
                    return;
                }elseif ($data_s['bx_status'] != 1){//不为1则返回等待下次查询
                    return;
                }
                //国别需要判断是否替换，若已替换，则不查询
                if($data['is_tihuan'] == 1){
                    $data_s['bx_remark']='该机子已替换';
                    Db::name('cx_server_info')->where('id',$id)->update(['status' => 2 ,'remark' => $data_s['bx_remark'] , 'cx_time' => time() , 'is_pay' => 1]);
                    return;
                }
                //判断下单与否
                if(intval($data_s['three_order_id']) != 0){
                    //已下单，进行查询操作
                    $result = $check->country_query($data_s['three_order_id']);
                    if($result['net_code'] == -999){//-999表示第三方等待查询中
                        return;
                    }
                }else{
                    //获取sn或imei 有其一就行
                    //未下单，进行下单操作(下单成功直接return，失败继续)
                    $result = $check->country_order($data['mp_sn'],$data['mp_imei'],$id);
                    if($result['status']){
                        return;
                    }
                }
                $case = 9;
                $this->up_data($id,$result,$data,'','',$case);
                break;
            //GSX查询
            case 13:
                //GSX下单必须要保修先查
                $sql = "select a.*,b.`status`as bx_status ,b.remark as bx_remark from nb_cx_server_info a left join nb_cx_server_info b on
                        a.server_cx_biaoshi = b.server_cx_biaoshi and b.server_id =1 where a.id  = $id";
                $data_s = @Db::query($sql)[0];
                //进行下单之前先保证sn或imei正确
                if($data_s['bx_status'] == 2 || $data_s['bx_status'] === null){//为2则订单失败
                    if( $data_s['bx_status'] === null) $data_s['bx_remark']='添加GSX查询异常';

                    Db::name('cx_server_info')->where('id',$id)->update(['status' => 2 ,'remark' => $data_s['bx_remark'] , 'cx_time' => time() , 'is_pay' => 1]);
                    return;
                }elseif ($data_s['bx_status'] != 1){//不为1则返回等待下次查询
                    return;
                }
                //GSX需要判断是否替换，若已替换，则不查询
                if($data['is_tihuan'] == 1){
                    $data_s['bx_remark']='该机子已替换';
                    Db::name('cx_server_info')->where('id',$id)->update(['status' => 2 ,'remark' => $data_s['bx_remark'] , 'cx_time' => time() , 'is_pay' => 1]);
                    return;
                }
                //判断下单与否
                if(intval($data_s['three_order_id']) != 0){
                    //已下单，进行查询操作
                    $result = $check->GSX_query($data_s['three_order_id']);
                    if($result['net_code'] == -999){//-999表示第三方等待查询中
                        return;
                    }
                }else{
                    //未下单，进行下单操作(下单成功直接return，失败继续)
                    $result = $check->GSX_order_CaseHistory($data['mp_sn'],$data['mp_imei'],$id);
                    if($result['status']){
                        return;
                    }
                }
//                $result = $check->GSX_query(13366644);//测试用的
                $case = 13;
                $this->up_data($id,$result,$data,'','',$case);
                break;
            //MDM锁查询
            case 15:
                $result = $check->MDM_query($sn_or_imei);
                $this->up_data($id,$result,$data,'','',15);
                break;
            //sn转imei
            case 16:
//                $this->sn2imei_i_imei($check,$id,$data);//i-imei用这两行代码，在其他接口崩了的时候在使用
                if(strlen($sn_or_imei)<=10){
                    $result=  ['net_code' => 200 , 'status' => false , 'tishi' => 'IEI或者SN为空：'.$sn_or_imei];
                }else{
                   $result = $check->sn2imei($sn_or_imei);
                }
                $up_field = 'mp_imei';//order_phone表内对应的字段 ,用于后续更新用
                $key = 'imei';
                $case = 16;
                $this->up_data($id,$result,$data,$up_field,$key,$case);
                break;
            case 17:
                //GSX图片下单必须要保修先查
                $sql = "select a.*,b.`status`as bx_status ,b.remark as bx_remark from nb_cx_server_info a left join nb_cx_server_info b on
                        a.server_cx_biaoshi = b.server_cx_biaoshi and b.server_id =1 where a.id  = $id";
                $data_s = @Db::query($sql)[0];
                //进行下单之前先保证sn或imei正确
                if($data_s['bx_status'] == 2 || $data_s['bx_status'] === null){//为2则订单失败
                    if( $data_s['bx_status'] === null) $data_s['bx_remark']='添加GSX图片查询异常';

                    Db::name('cx_server_info')->where('id',$id)->update(['status' => 2 ,'remark' => $data_s['bx_remark'] , 'cx_time' => time() , 'is_pay' => 1]);
                    return;
                }elseif ($data_s['bx_status'] != 1){//不为1则返回等待下次查询
                    return;
                }
                //GSX需要判断是否替换，若已替换，则不查询
                if($data['is_tihuan'] == 1){
                    $data_s['bx_remark']='该机子已替换';
                    Db::name('cx_server_info')->where('id',$id)->update(['status' => 2 ,'remark' => $data_s['bx_remark'] , 'cx_time' => time() , 'is_pay' => 1]);
                    return;
                }
                //判断下单与否
                if(intval($data_s['three_order_id']) != 0){
                    //已下单，进行查询操作
                    $result = $check->get_picture_result($data_s['three_order_id']);
                    if($result['net_code'] == -999){//-999表示第三方等待查询中
                        return;
                    }
                }else{
                    //未下单，进行下单操作(下单成功直接return，失败继续)
                    $result = $check->picture_result($data['mp_sn'],$data['mp_imei'],$id);
                    if($result['status']){
                        return;
                    }
                }
                $case = 17;
                $this->up_data($id,$result,$data,'','',$case);
                break;
            //整机查询
            case 18:
                $this->complete_i_imei($check,$id,$data);//i-imei
                break;

            default:
                return json(["status" => false, "tishi" => "查询类型失败！"]);
                break;
        }

        dump("====查询成功====");
    }

    /**
     * 更新数据库
     * @param $id           主键 server 表 id
     * @param $result       接口 返还数据
     * @param $data         server视图查询结果
     * @param $up_field     更新字段
     * @param $key          返还数据键
     * @param $server_id    服务ID
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function up_data($id,$result,$data,$up_field,$key,$server_id = null){

        if($result['net_code'] == 200){//连接成功
            //返回数据结果状态
            if($result['status']){//连接成功并且查询成功，将结果更新进数据表
                $status = 1;
            }else{//连接成功但查询失败，将结果更新进cx_server_info数据表
                $status = 2;
            }
            //运营商查询结果处理
            if($server_id == 6){//这里是需要特殊服务的接口
                $json_data = $result['tishi']['str'];
                //如果结果是对的 如果查询到结果
                if($status == 1){
                    // 获取手机imei
                    $imei_data = strstr($json_data,'IMEI (串号) : ');
                    $imei = substr($imei_data, 15  ,stripos($imei_data,'<br') -15 );
                    //获取 imei2
                    $imei2_data =  strstr($json_data,'串号2) : ');
                    $imei2 = substr($imei2_data, 11 ,stripos($imei2_data,'<br') -11);
                    // 获取 SN
                    $sn_data =  strstr($json_data,'序列号) : ');
                    $mp_sn= substr($sn_data, 13  ,stripos($sn_data,'<br') -13 );
                    if(strstr($json_data,'Carrier (运营商) : Unlocked (无锁)')){
                        $net_lock = 2;//无锁
                        $yunyingshang = 'Unlocked';
                        $yunyingshang_country = 'N/A';
                    }else{
                        $yys_data = strstr($json_data,'Carrier');
                        $yys = substr($yys_data,0,stripos($yys_data,'SimLock')-6);
                        if(strstr($json_data,'Locked (有锁)')){
                            $net_lock = 1;
                        }else if(strstr($json_data,'Unlocked (无锁)')){
                            $net_lock = 2;
                        }
                        $str = strstr(@$yys,'Country (国家)');
                        $num = stripos($str,' : ');
                        $yunyingshang_country = substr(@$str,$num + 3);
                        $yunyingshang = substr(@$yys, 22 ,stripos(@$yys,'<br') - 22);
                    }
                    //整理要更新的字段 data  查询结果里更新 四个字段结果
                    $update_data=array(
                        'mp_sn'=> @$mp_sn,
                        'mp_imei'=> @$imei,
                        'mp_imei2'=> @$imei2,
                        'net_lock' => @$net_lock,
                        'yunyingshang' => $yunyingshang,//运营商
                        'yunyingshang_country'=> $yunyingshang_country,//运营商国家

                    );
                    Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update($update_data);//运营商
                }
                else{
                    //更新运营商状态
                    $update_data=array(
                        'yunyingshang_country'=>'查询失败'
                    );
                    Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update($update_data);//运营商
                }
                //将本次查询返回数据更新cx_server_info
                Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => $status , 'json_data' =>$json_data , 'cx_time' => time() , 'is_pay' => 1]);
                //运营商查询结束 server_id 6 功能结束
                return ;
            }elseif ($server_id == 3){//ID黑白
                if($status == 1){
                    Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update([
                        'id_lock'       => $result['tishi']['if_id_lock'],
                        'id_lock_black' => $result['tishi']['id_blacklist'],
                    ]);
                }

            }elseif($server_id == 15){//MDM锁
                if($status == 1){
                    $str = $result['tishi']['data'];
                    $sn =  cut_str($str,'SN: ','<br>');
//                    $imei = cut_str($str,'IMEI: ','<br>');
                    $update_data = [
                        'MDM'       => $result['tishi']['mdm'],
                        'mp_sn'     => $sn,
//                        'mp_imei'   => $imei,
                    ];
                    $update_data=array_filter($update_data);//去空
                    Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update($update_data);

                    //获取sn或imei 有其一就行
                    $sn_or_imei = empty($data['mp_sn']) ? $data['mp_imei'] : $data['mp_sn'];
                    //先判断手机表中有无这个手机
                    $mobile_sql = "select * from nb_phone_list WHERE (mp_sn='$sn_or_imei' and mp_imei='$sn_or_imei')";
                    $mobile_data = @Db::query($mobile_sql)[0];//获取手机数据
                    if(!empty($mobile_data)){
                        Db::name('phone_list')->where('id',$mobile_data['id'])->update($update_data);
                    }else{
                        Db::name('phone_list')->insert($update_data);//若手机表没有这台手机的数据，则添加
                    }

                }
                Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => $status , 'json_data' =>json_encode($result['tishi']) , 'cx_time' => time() , 'is_pay' => 1]);
                //mdm服务完成
                return;
            }elseif ($server_id == 16){//sn转imei
                //如果结果是对的 如果查询到结果
                if($status == 1){
                    //截取IMEI号
                    $str2 = $result['tishi']['response']['result'].'<br>'; // 有时候返回没有BR  给加上一个
                    $sn =   cut_str( cut_str($str2,'Serial Number','<br>'),': ');

                    $imei = cut_str( cut_str($str2,'IMEI','<br>'),': ');
                    $goumai_date =  cut_str( cut_str($str2,'Estimated Purchase Dat','<br>') ,':');

                    $update_data=array();
                    if($sn !='') $update_data['mp_sn']=$sn;

                    if($imei !='' ) $update_data['mp_imei']=$imei;
                    if($goumai_date !='') $update_data['mp_buy_start']=$goumai_date;

                    Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update(
                        $update_data
                    );

                    //获取sn或imei 有其一就行
                    $sn_or_imei = empty($data['mp_sn']) ? $data['mp_imei'] : $data['mp_sn'];
                    //先判断手机表中有无这个手机
                    $mobile_sql = "select * from nb_phone_list WHERE (mp_sn='$sn_or_imei' and mp_imei='$sn_or_imei')";
                    $mobile_data = @Db::query($mobile_sql)[0];//获取手机数据
                    $update_data['buy_time'] = $goumai_date;
                    $update_data['mp_buy_start'] = strtotime($goumai_date);
                    $update_data=array_filter($update_data);//去空
                    if(!empty($mobile_data)){
                        Db::name('phone_list')->where('id',$mobile_data['id'])->update($update_data);
                    }else{
                        Db::name('phone_list')->insert($update_data);//若手机表没有这台手机的数据，则添加
                    }


                }else{
                    if($data['mp_sn'] != ''){
                        $status_xh = 1;
                    }elseif($data['mp_imei'] != ''){
                        $status_xh = 2;
                    }
                    //sn转imei
//                    Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update(['status' => $status_xh]);//sn或imei错误将手机表内得状态改为 1 错误
                }

                Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => $status , 'json_data' =>json_encode($result) , 'cx_time' => time() , 'is_pay' => 1]);
                if($data['is_weixin'] == 1){
                    Core::get("https://www.aiguovip.com/vip/Only_Server/weixin_msg_push/server_id/".$data['id']);
                }
                //sn转imei服务完成
                return;
            }elseif ($server_id == 9){//国别文字
                if($status == 1){//查询成功
                    $result['tishi'] = strtolower($result['tishi']);//首先全部转小写，傻逼接口，一会返回全大写，一会返回首字母大写
                    $update_data = array();
                    //imei
                    $imei = cut_str($result['tishi'],'imei number: ','<br');
                    if($imei != ''){
                        $update_data['mp_imei'] = $imei;
                    }
                    //imei2
                    $imei2 = cut_str($result['tishi'],'imei2 number: ','<br');
                    if($imei2 != ''){
                        $update_data['mp_imei2'] = $imei2;
                    }
                    //sn
                    $sn = cut_str($result['tishi'],'serial number: ','<br');
                    if($sn != ''){
                        $update_data['mp_sn'] = strtoupper($sn);
                    }
                    //id锁
                    $id_lock = cut_str($result['tishi'],'find my iphone: ','<br');
                    $id_lock = cut_str($id_lock,'>','<');
                    if($id_lock == ''){//若为空
                        $id_lock = cut_str($result['tishi'],'icloud lock: ','<br');
                        $id_lock = cut_str($id_lock,'>','<');
                    }
                    if(strstr($id_lock,'on')){
                        $update_data['id_lock'] = 1;
                    }else if(strstr($id_lock,'off')){
                        $update_data['id_lock'] = 2;
                    }
                    //id黑白
                    $id_black = cut_str($result['tishi'],'icloud status: ','<br');
                    $id_black = cut_str($id_black,'>','<');
                    if(strstr($id_black,'clean')){
                        $update_data['id_lock_black'] = 2;
                    }elseif ($id_black != ''){
                        $update_data['id_lock_black'] = 1;
                    }
                    //MDM锁
                    $MDM = cut_str($result['tishi'],'mdm status: ','<br');
                    $MDM = cut_str($MDM,'>','<');
                    if(strstr($MDM,'on')){
                        $update_data['MDM'] = 1;
                    }elseif(strstr($MDM,'off')){
                        $update_data['MDM'] = 2;
                    }
                    //是否官换
                    $guanhuan = cut_str($result['tishi'],'replaced device: ','<br');
                    $guanhuan = cut_str($guanhuan,'>','<');
                    if(strstr($guanhuan,'yes')){
                        $update_data['is_guanhuan'] = 1;
                    }elseif ($guanhuan != ''){
                        $update_data['is_guanhuan'] = 2;
                    }
                    //保修状态
                    $baoxiu = cut_str($result['tishi'],'coverage status: ','<br');
                    if($baoxiu != ''){
                        $update_data['baoxiu_type'] = $baoxiu;
                    }
                    //销售产品
                    $product = cut_str($result['tishi'],'product sold by: ','<br');
                    if($product != ''){
//                        $update_data['product'] = $product;
                    }
                    //销售国家
                    $country = cut_str($result['tishi'],'purchase country: ','<br');
                    if($country != ''){
                        if($country == 'hong kong'){
                            $update_data['sale_country'] = 'china ' . $country;
                        }else{
                            $update_data['sale_country'] = $country;
                        }
                    }
                    //预计购买时间
                    $buy_time = cut_str($result['tishi'],'coverage start: ','<br');
                    if($buy_time != ''){
                        $buy_time = date('Y-m-d',strtotime($buy_time));
                        $update_data['mp_buy_start'] = $buy_time;
                    }else{
                        //预计购买时间
                        $buy_time = cut_str($result['tishi'],'purchase date: ','<br');
                        $buy_time = date('Y-m-d',strtotime($buy_time));
                        $update_data['mp_buy_start'] = $buy_time;
                    }
                    //保修到期时间
                    $baoxiu_time = cut_str($result['tishi'],'coverage end: ','<br');
                    if($baoxiu_time != ''){
                        $baoxiu_time = date('Ymd',strtotime($baoxiu_time));
                        $update_data['mp_bx_endtime'] = $baoxiu_time;
                    }
                    //网络锁
                    $net_lock = cut_str($result['tishi'],'sim-lock: ','<br');
                    $net_lock = cut_str($net_lock,'>','<');
                    if(strstr($net_lock,'unlocked')){
                        $update_data['net_lock'] = 2;
                    }elseif ($net_lock != ''){
                        $update_data['net_lock'] = 1;
                    }
                    //去除数组中空的元素
                    $update_data=array_filter($update_data);
                    Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update($update_data);
                }
                Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => $status , 'json_data' =>json_encode($result['tishi']) , 'cx_time' => time() , 'is_pay' => 1]);
                //国别查询完成
                return;
            }elseif($server_id == 13){//GSX查询
                if($status == 1){
                    $split = array(
                        'Part Description:',
                        'Initial Activation Policy ID:',
                        'mp_sn'=>'Serial Number:',
                        'Initial Activation Policy:',
                        'mp_imei'=>'IMEI:',
                        'Last Applied Activation policy ID:',
                        'IMEI 2:',
                        'Policy:',
                        'MEID:',
                        'Next Activation Policy ID:',
                        'CSN/CSN2/EID:',
                        'Next Activation Policy:',
                        'Bluetooth MAC Address:',
                        'First Activation Date:',
                        'Wi-Fi Mac Address:',
                        'Last Activation Date:',
                        'Software Version:',
                        'Last Restore Date:',
                        'Software Build:',
                        'Unlock Date:',
                        'Carrier:',
                        'Unlocked:',
                        'ICCID:',
                        'Unbricked:',
                        'Carrier 2:',
                        'ICCID 2:',
                        'Coverage Details',
                        'yunyingshang'=>'Product Sold By:',
                        'baoxiu_type'=>'Coverage Status:',
                        'mp_bx_endtime'=>'Coverage End Date:',
                        'mp_buy_start'=>'Estimated Purchase Date:',
                        'sale_country'=>'Purchased In:',
                        'Loaner:',
                        'Repair',
                        'Replacement Details:',
                    );
                    foreach ($split as $key => $v){
                        $result['tishi'] = str_replace($v,'<br>'.$v,$result['tishi']);
                    }
                    $update_data = array();
                    foreach ($split as $k => $v){
                        if(!is_numeric($k)){
                            if($k == 'mp_bx_endtime'){
                                $str = trim(cut_str($result['tishi'],$v,'<br>'));
                                $str = date('Ymd',strtotime($str));
                            }elseif($k == 'mp_buy_start'){
                                $str = trim(cut_str($result['tishi'],$v,'<br>'));
                                $str = date('Y-m-d',strtotime($str));
                            }else{
                                $str = trim(cut_str($result['tishi'],$v,'<br>'));
                            }
                            $update_data[$k] = $str;
                        }
                    }
                    //是否借出
//                    $loaner = trim(cut_str($result['tishi'],'Loaner:','&quot'));
//                    $update_data['phone_jiechu'] = $country;
                    $update_data['gsx_word'] = json_encode(array('word'=>$result['tishi']));
                    //去除数组中空的元素
                    $update_data=array_filter($update_data);
                    Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update($update_data);
                }

                Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => $status , 'json_data' =>json_encode($result['tishi']) , 'cx_time' => time() , 'is_pay' => 1]);
                //GSX查询完成
                return;
            }elseif($server_id == 17){//GSX图片
                if($status == 1){
                    $str = str_replace(' ',',',str_replace(' ö ',',',$result['tishi']));
                    $arr = explode(',',$str);
                    $str1 = '';
                    foreach($arr as $k => $v){
                        if(count($arr) == $k + 1){
                            $str1 .= $this->dowimg($v,$data['id'].'_'.$k);
                        }else{
                            $str1 .= $this->dowimg($v,$data['id'].'_'.$k) . ',';
                        }
                    }
                    Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update(['gsx_img' => $str1]);
                }
                Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => $status , 'json_data' =>json_encode($result['tishi']) , 'cx_time' => time() , 'is_pay' => 1]);
                //GSX图片查询完成
                return;
            }
            //这里是不需要特殊服务的接口统一代码 直接更新字段
            else{
                if($status == 1){
                    Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update([$up_field => $result['tishi'][$key]]);
                }
            }
            //最终结果更新进cx_order_phone，失败不更新数据表


            //将本次查询返回数据更新cx_server_info
            Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => $status , 'json_data' => json_encode($result['tishi']) , 'cx_time' => time() , 'is_pay' => 1]);

            //返回两百以外的情况下
        }else{
            //如果本次服务是id锁查询，则不管它，让他下次继续跑
            if($data['server_id'] != 2){
                //若连接失败再重新查询，并且查询次数 + 1
                Db::name('cx_server_info')->where('id',$data['id'])->update(['cx_count' => $data['cx_count'] + 1]);
                //只要不是ID锁的服务，连接失败后需要重新调用
                $this->only_server_query($id);
            }
        }
        Core::logToDb($result);//数据库记录调试日志
    }


    /**
     * 手机表中若存在则不查接口
     */
    public function mobile_list($data){
        //获取sn或imei 有其一就行
        $sn_or_imei = empty($data['mp_sn']) ? $data['mp_imei'] : $data['mp_sn'];

        switch($data['server_id']){
            case 15:
                $sql = "select * from nb_phone_list where (mp_sn='$sn_or_imei' or mp_imei='$sn_or_imei') and MDM <> 0";
                $datas = @Db::query($sql)[0];//获取手机数据
                if(empty($datas)){
                    return false;//若不存在则返回false
                }
                $update_filed = [
                    'mp_sn'         => $datas['mp_sn'],
                    'mp_imei'       => $datas['mp_imei'],
                    'MDM'           => $datas['MDM'],
                ];
                //去除数组中空得元素
                $update_filed = array_filter($update_filed);
                Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update($update_filed);
                Db::name('cx_server_info')->where('id',$data['id'])->update(['status'=>1,'json_data'=>'手机表中获取','cx_time'=>time(),'is_pay'=>1]);
                return true;
                break;
            case 16:
                $sql = "select * from nb_phone_list where (mp_sn='$sn_or_imei' or mp_imei='$sn_or_imei') and  buy_time <> 0";
                $datas = @Db::query($sql)[0];//获取imei数据
                if(empty($datas)){
                    return false;//若不存在则返回false
                }
                $update_filed = [
                    'mp_sn'         => $datas['mp_sn'],
                    'mp_imei'       => $datas['mp_imei'],
                    'mp_buy_start'  => $datas['buy_time'],
                ];
                //去除数组中空得元素
                $update_filed = array_filter($update_filed);
                Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update($update_filed);
                Db::name('cx_server_info')->where('id',$data['id'])->update(['status'=>1,'json_data'=>'手机表中获取','cx_time'=>time(),'is_pay'=>1]);
                return true;
                break;
            default:
                return false;
        }
    }

    public function net_lock_i_imei($check,$id,$data){
        $sql = "select a.*,b.`status`as bx_status ,b.remark as bx_remark from nb_cx_server_info a left join nb_cx_server_info b on
                        a.server_cx_biaoshi = b.server_cx_biaoshi and b.server_id =1 where a.id  = $id";
        $data_s = @Db::query($sql)[0];
        //判断下单与否
        if(intval($data_s['three_order_id']) != 0){
            //已下单，进行查询操作
            $result = $check->country_query($data_s['three_order_id']);
            if($result['net_code'] == -999){//-999表示第三方等待查询中
                return;
            }
        }else{
            //获取sn或imei 有其一就行
            if(!empty($data['mp_imei'])){
                $sn_or_imei = $data['mp_imei'];
                $type_api = 1;
            }else{
                $sn_or_imei = $data['mp_sn'];
                $type_api = 2;
            }
            //未下单，进行下单操作(下单成功直接return，失败继续)
            $result = $check->net_lock($sn_or_imei,$type_api,$id);
            if($result['status']){
                return;
            }
        }

        if(!$result['status']){
            Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => 2 , 'json_data' => json_encode($result['tishi']) , 'cx_time' => time() , 'is_pay' => 1]);
            return;
        }
        //网络锁
        $update['net_lock'] = 0;
        if(strstr($result['tishi'],'Unlocked') && strstr($result['tishi'],'Sim-Lock')){
            $update['net_lock'] = 2;
        }else if(strstr($result['tishi'],'Sim-Lock')){
            $update['net_lock'] = 1;
        }
        $update=array_filter($update);
        Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update($update);
        Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => 1 , 'json_data' => json_encode($result['tishi']) , 'cx_time' => time() , 'is_pay' => 1]);
    }


    /**
     * sn&imei互转  i-imei的接口
     */
    public function sn2imei_i_imei($check,$id,$data){
        $sql = "select a.*,b.`status`as bx_status ,b.remark as bx_remark from nb_cx_server_info a left join nb_cx_server_info b on
                        a.server_cx_biaoshi = b.server_cx_biaoshi and b.server_id =1 where a.id  = $id";
        $data_s = @Db::query($sql)[0];
        //判断下单与否
        if(intval($data_s['three_order_id']) != 0){
            //已下单，进行查询操作
            $result = $check->country_query($data_s['three_order_id']);
            if($result['net_code'] == -999){//-999表示第三方等待查询中
                return;
            }
        }else{
            //获取sn或imei 有其一就行
            if(!empty($data['mp_imei'])){
                $sn_or_imei = $data['mp_imei'];
                $type_api = 1;
            }else{
                $sn_or_imei = $data['mp_sn'];
                $type_api = 2;
            }
            //未下单，进行下单操作(下单成功直接return，失败继续) 由于i-imei这个网站，网络锁与sn&imei的接口一样，所以这里使用网络锁
            $result = $check->net_lock($sn_or_imei,$type_api,$id,3);
            if($result['status']){
                return;
            }
        }

        if(!$result['status']){
            Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => 2 , 'json_data' => json_encode($result['tishi']) , 'cx_time' => time() , 'is_pay' => 1]);
            return;
        }
        $update['mp_sn'] = cut_str($result['tishi'],'Serial: ','<br');//sn
        $update['mp_imei'] = cut_str($result['tishi'],'IMEI: ','<br');//imei
        $update['mp_imei2'] = cut_str($result['tishi'],'IMEI2: ','<br');//imei2
        $update['mp_buy_start'] = cut_str($result['tishi'],'Estimated Purchase Date: ','<br');//购买时间
        if(strstr($result['tishi'],'Status') && strstr($result['tishi'],'Replaced Device')){//当Status键与Replaced Device值同时存在时 ,说明该手机已替换
            $update['is_tihuan'] = 1;//已替换
        }
        $update=array_filter($update);
        Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update($update);
        Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => 1 , 'json_data' => json_encode($result['tishi']) , 'cx_time' => time() , 'is_pay' => 1]);
        //如果是微信公众号还需要推送微信信息
        if($data_s['is_weixin'] == 1){
            Core::get("https://www.aiguovip.com/vip/Only_Server/weixin_msg_push/server_id/".$id);
//            $this->weixin_msg_push($id);
        }
    }

    /**
     * 整机查询
     */
    public function complete_i_imei($check,$id,$data){
        $sql = "select a.*,b.`status`as bx_status ,b.remark as bx_remark from nb_cx_server_info a left join nb_cx_server_info b on
                        a.server_cx_biaoshi = b.server_cx_biaoshi and b.server_id =1 where a.id  = $id";
        $data_s = @Db::query($sql)[0];
        //判断下单与否
        if(intval($data_s['three_order_id']) != 0){
            //已下单，进行查询操作
            $result = $check->country_query($data_s['three_order_id']);
            if($result['net_code'] == -999){//-999表示第三方等待查询中
                return;
            }
        }else{
            //获取sn或imei 有其一就行
            if(!empty($data['mp_imei'])){
                $sn_or_imei = $data['mp_imei'];
                $type_api = 1;
            }else{
                $sn_or_imei = $data['mp_sn'];
                $type_api = 2;
            }
            //未下单，进行下单操作(下单成功直接return，失败继续) 由于i-imei这个网站，网络锁与整机查询的接口一样，所以这里使用网络锁
            $result = $check->net_lock($sn_or_imei,$type_api,$id,3);
            if($result['status']){
                return;
            }
        }

        if(!$result['status']){
            Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => 2 , 'json_data' => json_encode($result['tishi']) , 'cx_time' => time() , 'is_pay' => 1]);
            return;
        }

        $update['mp_sn'] = cut_str($result['tishi'],'Serial: ','<br');//sn
        $update['mp_imei'] = cut_str($result['tishi'],'IMEI: ','<br');//imei
        $update['mp_imei2'] = cut_str($result['tishi'],'IMEI2: ','<br');//imei2
        $model = cut_str($result['tishi'],'Model Description: ','<br');//型号，用来判断国家
        $update['sale_country'] = $this->countryIdentify($model,$update['mp_sn']);
        $update['mp_buy_start'] = cut_str($result['tishi'],'Estimated Purchase Date: ','<br');//购买时间
//        if(empty($update['sale_country']))  $update['sale_country'] = cut_str($result['tishi'],'Purchase Country: ','<br');//若前面计算不出来则直接截取购买国家
        $update['net_lock_black'] = cut_str($result['tishi'],'Blacklisted by: ','<br');//网络黑白名单
        $update['baoxiu_type'] = cut_str($result['tishi'],'Coverage Status: ','<br');//保修类型
        $update['esn'] = cut_str($result['tishi'],'ESN: ','<br');//ESN
        $update['imsi'] = cut_str($result['tishi'],'IMSI: ','<br');//IMSI
        $update['iccid'] = cut_str($result['tishi'],'ICCID: ','<br');//ICCID
        //网络锁
        if(strstr($result['tishi'],'Unlocked') && strstr($result['tishi'],'Sim-Lock')){
            $update['net_lock'] = 2;
        }else if(strstr($result['tishi'],'Sim-Lock')){
            $update['net_lock'] = 1;
        }
        //ID黑白
        if(strstr($result['tishi'],'Clean') && strstr($result['tishi'],'iCloud Status')){
            $update['id_lock_black'] = 2;//白名单
        }else if(strstr($result['tishi'],'iCloud Status')){
            $update['id_lock_black'] = 1;//黑名单
        }
        //ID锁
        if(strstr($result['tishi'],'OFF') && strstr($result['tishi'],'iCloud Lock')){
            $update['id_lock'] = 2;
            $update['id_lock_black'] = 2;//ID锁如果关着，则默认ID黑白未白名单
        }else if(strstr($result['tishi'],'iCloud Lock')){
            $update['id_lock'] = 1;
        }
        //当Status键与Replaced Device值同时存在时 ,说明该手机已替换
        if(strstr($result['tishi'],'Status') && strstr($result['tishi'],'Replaced Device')){
            $update['is_tihuan'] = 1;//已替换
        }
        $update['zhengji'] =  json_encode(array('word'=>$result['tishi']));//整机查询

        $update=array_filter($update);//去空
        Db::name('cx_order_phone')->where('server_cx_biaoshi',$data['server_cx_biaoshi'])->update($update);
        Db::name('cx_server_info')->where('id',$data['id'])->update(['status' => 1 , 'json_data' => json_encode($result['tishi']) , 'cx_time' => time() , 'is_pay' => 1]);
        //如果是微信公众号的还需要发推送
        if($data_s['is_weixin'] == 1){
            Core::get("https://www.aiguovip.com/vip/Only_Server/weixin_msg_push/server_id/".$id);
//            $this->weixin_msg_push($id);
        }
    }


    /**
     * 国别识别计算(根据型号计算)
     * $model  型号
     * $sn     序列号(GDG开头的)
     */
    public function countryIdentify($model,$sn){
        //GDG序列号
        if(strstr($sn,'GDG') || strstr($model,'CH')){
            return '中国';
        }
        //普通型号
        if(strstr($model,'CHA') || strstr($model,'CHN')){
            return '中国';
        }else if(strstr($model,'USA')){
            return '美国';
        }else if(strstr($model,'ITP') || strstr($model,'ITS')){
            return '新加坡/中国香港';
        }else if(strstr($model,'JPN')){
            return '日本';
        }else if(strstr($model,'ZDD')){
            return '欧盟';
        }else if(strstr($model,'THA')){
            return '泰国';
        }else if(strstr($model,'YPT')){
            return '西班牙';
        }else if(strstr($model,'VIE')){
            return '越南';
        }else if(strstr($model,'AUS')){
            return '澳大利亚';
        }else if(strstr($model,'GBR')){
            return '英国';
        }else if(strstr($model,'LAE')){
            return '新几内亚';
        }
        return '无国别信息';
    }

    /**
     * imei转sn
     */
    public function imei_to_sn(){
        //更新所有 已知IMEI 手机状态正常的 新增手机SN 号
//        Db::execute("update nb_cx_order_phone a, nb_cx_order_phone b set  a.mp_sn =b.mp_sn
//                    where (a.mp_imei =b.mp_imei or a.mp_imei2 = b.mp_imei2)
//                    and b.mp_sn is not null and a.mp_sn is null and a.`status` =0  and b.status=0");
        //查找所有sn为空或者是null ， 并且status = 0 的数据
        $sql = "select distinct  mp_imei from nb_cx_order_phone where mp_sn is null or trim(mp_sn)='' and status = 0 limit 10";
        $data = Db::query($sql);

        write_json(3,'',$data);
        $check = new Check();
        foreach ($data as $k => $v){
            $sn = '';
            $status = 0;
            for($i = 0;$i < 3 ;$i++){ // 执行三次查询
                $res = $check->imei2sn($v['mp_imei']);//imei转sn
                if($res != 'Again'){
                    $sn = $res;
                    if(empty($res) || $res == 'IMEI无效'){
                        $status = 2;//imei值错误
                    }else{
                        $status = 0;
                    }
                    break;//跳出本次循环
                }
                //若返回Again则再次获取一遍
            }

            //将获取到的数据更新进数据表order_phone
            if($sn!=''){
                $wangluo = Core::getModelBySn4($sn);
                Db::name('cx_order_phone')->where(" (mp_sn is null or trim(mp_sn)='') and mp_imei='$v[mp_imei]'")->update([
                    'mp_sn' => $sn ,
                    'status' => $status,
                    'mp_model'=> $wangluo[1],
                    'mp_rongliang'=> $wangluo[2],
                    'mp_color'=> $wangluo[3],
                    'mp_net'=>$wangluo[4]
                ]);
            }

        }
        //Core::logToDb(@$res);//数据库记录调试日志

    }

    public function wxTest(){
        $openid = "oaXm5jgGLlG8pT5a8gfUT356Ow2A";
        $config = Config('wechat.');
        $wxapp = Factory::officialAccount($config);
        $t = $wxapp->template_message->send([
            'touser' => $openid,
            'template_id' => '3RhwKIDiSiZMz87Rp2s2EpW3Zz6KLo8M8a7r3NZzf5w',
            'url' => "https://www.aiguovip.com/",
            'data' => [
                "first" => ['ID监控查询成功', "#5599FF"],
                "keyword1" => ["无锁", "#0000FF"],
                "keyword2" => ["无锁", "#0000FF"],
                "remark" => ["\r" . '感谢支持！', "#5599FF"],
            ],
        ]);
        dump('发送成功');
    }


    public function change_openid(){
        $old_open_id = 'oaXm5jmL8CqKQKaEIu-eD1EDagRM' ;
        $old_open_id ='oaXm5jsfXi3p3eBTz1PGmZYaKKdY';
        $config = Config('wechat.');

        $wxapp = Factory::officialAccount($config);
        //$user =$wxapp->user->get($old_open_id);
        // print_r($user);
        $res  =Db::name('weixin_user')->field('count(openid)')->select();
        echo 'testssssssssssss';
        print_r($res);


        $t = $wxapp->user->changeOpenid('wxdfcfa4e309b7fa6c',array($old_open_id));
        print_r($t);

    }
    /**
     * id监控
     * $user_id        user_id
     * $sn             sn或imei
     * $order_id       订单ID
     * $id_lock        id锁
     * $if_repair      维修状态
     */
    public function monitor_service($user_id,$sn,$order_id,$id_lock=0,$if_repair=0){
        //通知用户  send_temp
        //查订单标题
        $order_sn = Db::name('cx_order_info')->where('id',$order_id)->value('cx_order_sn');
        // id监控通知
        if ($id_lock != 0){
            $str =  $id_lock==1?"无锁=>有锁":"有锁=>无锁";
            $remark = ["您订单[".$order_sn."]中的[".$sn."]机器监控到ID状态已发生变化"."\n"."当前状态为:".$str, "#5599FF"];
            $url = url('/vip/chaxun/order_detail','id='.$order_id,'html', 'https://'.$_SERVER['HTTP_HOST']);
            $temp_ID = 'b0XMO7rzV3j5GcofXcevs9q819UG2AQYn60wFz4Ou7E';
            $data = array(
                "first" => $remark,
                "keyword1" => ['ID和维修监控',"#0000FF"],
                "keyword2" => [date("Y/m/d H:i:s"),"#5599FF"]
//              "remark" => ['变动原因：'.$_POST['remark'],"#0000FF"]
            );

            //微信发送记录生成
            $json_data = json_encode($data);
            $data_log = array(
                'data'=>$json_data,
                'c_time'=>time(),
                'uid'=>$user_id,
                'remark'=>'监控数据微信发送'
            );
            Db::name('weixin_log')->insert($data_log);
            wechat_sending($user_id,$temp_ID,$url,$data);

        }
        if ($if_repair != 0){
            // 维修监控通知
            $status_arr = [
                "0"=> "未知",
                "-1"=> "替换",
                "-4"=> "已下单",
                "-5"=> "未下单",
                "-6"=> "维修过",
                "-7"=> "禁止下单",
                "-8"=>"只修不换",
                "-9"=>"未知下单"
            ];
            $str =  $status_arr[$if_repair];
            $remark = ["您监控的手机[$sn]维修状态发生变化"."\n"."当前状态为:".$str, "#5599FF"];
            $url = url('/vip/chaxun/order_detail','id='.$order_id,'html', $_SERVER[REQUEST_SCHEME].'://'.$_SERVER[SERVER_NAME]);
            $temp_ID = 'b0XMO7rzV3j5GcofXcevs9q819UG2AQYn60wFz4Ou7E';
            $data = array(
                "first" => $remark,
                "keyword1" => ['ID和维修监控',"#0000FF"],
                "keyword2" => [date("Y/m/d H:i:s"),"#5599FF"]
//                  "remark" => ['变动原因：'.$_POST['remark'],"#0000FF"]
            );

            //微信发送记录生成
            $json_data = json_encode($data);
            $data_log = array(
                'data'=>$json_data,
                'c_time'=>time(),
                'uid'=>$user_id,
                'remark'=>'监控数据微信发送'
            );
            Db::name('weixin_log')->insert($data_log);
            wechat_sending($user_id,$temp_ID,$url,$data);

        }
        // 发送消息日志



    }


    /**
     * 消费异常监控
     */
    public function service_abnormal(){
        $sql = "SELECT
                 a.NAME,
                 count(
                  case when b.`status` =2 then 1 else null  end
                 ) AS error_count
                 ,
                  count(
                  case when b.`status` =1  then 1 else null  end
                 ) AS success_count
                
                 ,
                   count(
                  case when b.`status` =0  then 1 else null  end
                 ) AS cx_ing
                  ,count(b.id) count 
                 
                FROM
                 nb_chaxun_config a
                 LEFT JOIN nb_cx_server_info b ON a.id = b.server_id 
                 
                GROUP BY
                 a.id";
        $data = Db::query($sql);

        $old_data = Db::name('service_abnormal')->order("id", "desc")->find();
        $json_data = json_decode($old_data['json_data'],true);

        foreach($data as $k => $v){
            $v['difference'] = $v['error_count'] - $json_data[$k]['error_count'];
            $data[$k] = $v;
        }
        Db::name('service_abnormal')->insert([
            'json_data' => json_encode($data),
            'add_time'     => time(),
        ]);
        dump('本次记录成功');
    }

    /**
     * 维修监控  逻辑同ID锁监控一致
     */
    public function maintain_monitor(){
        set_time_limit(900);
        $check = new Check();
        $now_time = time();//当前时间
        //到期时间大于当前时间的为正在监控中(每次拿出15条进行循环)
        $data = Db::name('cx_order_phone')->where('weixiu_jiankong_e_time' , '>' , $now_time)->limit(15)->select();
        foreach ($data as $k => $v){
            $sn = $v['mp_sn'];
            $result = $check->maintain($sn);//这里是维修接口
            //当net_code等于200并且status为true并且ID锁状态发生改变时进入
            //TODO 以下 && $v['weixiu'] != $result['tishi']['if_id_lock']  这里的判断要根据接口返回格式编写，现在维修接口暂未开通
            if($result['net_code'] == 200 && $result['status']){
                //获取服务数据
                $serverInfo = Db::name('cx_server_info')->where('server_cx_biaoshi',$v['server_cx_biaoshi'])->where('server_id',7)->find();
                //判断服务是否存在
                if(empty($serverInfo)){
                    $amount = Db::name('chaxun_config')->where('id',7)->find()['jifen'];//获取维修积分
                    //若为空则插入
                    Db::name('cx_server_info')->insert([
                        'server_cx_biaoshi' => $v['server_cx_biaoshi'],
                        'server_id'     => 2,
                        'amount'        => $amount,
                        'status'        => 1,
                        'remark'        => '',
                        'json_data'     => json_encode($result['tishi']),
                        'cx_time'       => time(),
                        'add_time'      => time(),
                        'is_pay'        => 2,
                        'cx_count'      => 0,
                    ]);
                    Db::name('cx_order_phone')->where('id',$v['id'])->update(['weixiu' => $result['tishi']['if_id_lock']]);//更新状态值
                }else{
                    //更新状态值
                    Db::name('cx_server_info')->where('server_cx_biaoshi',$v['server_cx_biaoshi'])->where('server_id',7)->update([
                        'json_data' => json_encode($result['tishi']) ,
                        'cx_time'   => time(),
                        'is_pay'    => 2,
                    ]);
                    Db::name('cx_order_phone')->where('id',$v['id'])->update(['weixiu' => $result['tishi']['if_id_lock']]);//更新状态值
                }

            }
        }
    }

    /**
     * 下载图片
     */
    function dowimg($url,$file_name){
//        $url = "https://prnt.sc/p4njh0";
        if($_SESSION['GET_IMG_SESSION'] == ''){
            $cookie =Core::get_URL_cookie($url,array('text'=>1),"Accept-Language: zh-CN");
            $_SESSION['GET_IMG_SESSION'] = $cookie;
        }
        $cookie = $_SESSION['GET_IMG_SESSION'];
        $rs = Core::post_zjb($url,array('text'=>1),'',"Accept-Language: zh-CN");

        $str =$rs['body'];
        preg_match('/https:\/\/image.prntscr.com\/image.*?png/',$str,$mcc);
        $file_name = $file_name . '.png';
        $this->download_img($mcc[0],'static/gsx_img/'.$file_name);//先下载到服务器
        $this->update_aliyun($file_name,'static/gsx_img/'.$file_name);//再下载到阿里云oss上
        return $file_name;
    }
    public function download_img($url,$file_name) {
        $ch= curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY ,0); //只要BODY头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER ,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST ,FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $package=curl_exec($ch);
        $httpinfo=curl_getinfo($ch);
        curl_close($ch);

        //return array_merge(array('body'=>$package),array('header'=>$httpinfo));
        $local_file=fopen($file_name,'w');
        if( false !==$local_file){
            if(false !==fwrite($local_file,$package)){
                fclose($local_file);
            }

        }


    }

    // 读取OSS配置
    function update_aliyun($file_name,$url){
        //获取配置项，并赋值给对象$config
        $config=config('api.aliyun_oss');

        //实例化OSS
        $ossClient=new \OSS\OssClient($config['KeyId'],$config['KeySecret'],$config['Endpoint']);
        //try 要执行的代码,如果代码执行过程中某一条语句发生异常,则程序直接跳转到CATCH块中,由$e收集错误信息和显示
        try{
            //没忘吧，new_oss()是我们上一步所写的自定义函数
            //uploadFile的上传方法
            $res=    $ossClient->uploadFile($config['Bucket'], 'gsx_img/'.$file_name, $url);//'../public/txt.txt'
            return json($res);
        } catch(OssException $e) {
            //如果出错这里返回报错信息
            return $e->getMessage();
        }
        return $oss;

    }

    /**
     * 获取I-IMEI网站的服务ID号
     */
    public function get_IMEI_list(){
        //在这里是不能实例化这个类的，不然会冲突
//        $api = new \DhruFusion();
//        $request = $api->action('imeiservicelist');
//        dump($request);
    }



    public function wx_msg_push($user_id,$order_id,$timeout){
        $m = Ceil($timeout/60);
        $s = $timeout%60;
        $temp_ID = 'b0XMO7rzV3j5GcofXcevs9q819UG2AQYn60wFz4Ou7E';
        $content = '订单号为'.$order_id.'已超过'.$m.'分钟'.$s.'秒'.'未结单';
        $data = array(
            "first" => '检测某服务不稳定了',
            "keyword1" => [$content,"#0000FF"],
            "keyword2" => [date("Y/m/d H:i:s"),"#5599FF"]
        );
        wechat_sending($user_id,$temp_ID,"",$data);
    }
    public function weixin_msg_push($server_id,$sn_or_imei=null,$msg=null){
        Db::name('cx_server_info')->where('id',$server_id)->update(['is_weixin' => 2]);//第一步进来先更新为 2 已发送
        $server_info = Db::name('cx_server_info s')
                        ->join('nb_cx_order_phone p',' s.server_cx_biaoshi=p.server_cx_biaoshi','left')
                        ->join('nb_cx_order_info o ','  s.order_id =o.id ','left')
                        ->field('s.status as server_status,s.server_id,p.status,o.id,o.user_id,p.*')
                        ->where('s.id',$server_id)->find();
        $server_count = Db::name('cx_server_info')->where('order_id',$server_info['order_id'])->where('is_weixin',2)->count();//这个订单下已发送的服务个数
        $temp_ID = 'b0XMO7rzV3j5GcofXcevs9q819UG2AQYn60wFz4Ou7E';
        $user_id = $server_info['user_id'];
        $url = urlencode("https://www.aiguovip.com/vip/chaxun/order_detail/id/$server_info[order_id]");
        //得到服务的详情
        $chaxun_config= Db::name('chaxun_config')->where('id',$server_info['server_id'])->field('name')->find();//查询服务
        if($server_info['server_status'] == 2){//服务查询失败
            $str = "服务查询失败，积分稍后退回账号";
            if($server_count == 21){
                $str .= "\n公众号内只展示20条服务查询信息，更多内容请进入官网查看";
            }
            $data = array(
                "first" => $str,
                "keyword1" => [$chaxun_config['name'],"#0000FF"],
                "keyword2" => [date("Y/m/d H:i:s",time()),"#5599FF"],
                   "remark" => ['点击查看更多',"#FA5151"]
            );
            wechat_sending($user_id,$temp_ID,"https://www.aiguovip.com/vip/login/check_user/uid/$user_id/url/$url",$data);
            return;
        }

        if($server_count == 21){//等于21的时候，说明查询服务个数超出，需要提示客服去官网查看
            $data = array(
                "first" => "公众号内只展示20条服务查询信息，更多内容请进入官网查看",
                "keyword1" => [$chaxun_config['name'],"#0000FF"],
                "keyword2" => [date("Y/m/d H:i:s",time()),"#5599FF"],
                   "remark" => ['点击查看更多',"#FA5151"]
            );
            wechat_sending($user_id,$temp_ID,"https://www.aiguovip.com/vip/login/check_user/uid/$user_id/url/$url",$data);
            return;
        }
        if($server_count >= 20){
            return;//若大于20个就返回
        }

        $phone_info =Chaxun::get_phone_info($server_info);
        $content="已为您查询到结果：\r\n";
        if($server_info['status'] == 2){//服务查询失败
            $content .='序列号:'.$server_info['mp_sn']."\r";
            $content .='IMEI:'.$server_info['imei']."\r\n";
            $content .='手机型号:'.$phone_info['server_model']['value']."\r\n";
            $content .='本次查询失败'."\r\n";
            $data = array(
                "first" => $content,
                "keyword1" => [$chaxun_config['name'],"#0000FF"],
                "keyword2" => [date("Y/m/d H:i:s",time()),"#5599FF"],
                   "remark" => ['点击查看更多',"#FA5151"]
            );

            wechat_sending($user_id,$temp_ID,"https://www.aiguovip.com/vip/login/check_user/uid/$user_id/url/$url",$data);
            return;
        }

        switch($server_info['server_id']){
            case 1:
                $content .='序列号:'.$server_info['mp_sn']."\r";
                $content .='IMEI:'.$server_info['mp_imei']."\r\n";
                $content .='手机型号:'.$phone_info['server_model']['value']."\r\n";
                $content .='手机颜色:'.$phone_info['server_phone_color']['value']."\r\n";
                $content .='手机容量:'.$phone_info['server_phone_size']['value']."\r\n";
                $content .='激活时间:'.$phone_info['server_buy_date']['value']."\r\n";
                $content .='到保时间:'.$phone_info['server_bx_end_date']['value']."\r\n";
                $content .='剩余时间:'.$phone_info['server_bx_end_date']['end_day_num'].'天'."\r\n";
                $content .='保修类型:'.$phone_info['server_phone_bx_lx']['value']."\r\n";
                $content .='是否官换:'.$phone_info['server_guanhuan']['value']."\r\n";
                $content .='设备借出:'.$phone_info['server_jiechu']['value']."\r\n";
                break;
            case 2:
                //ID锁
                $content .='序列号:'.$server_info['mp_sn']."\r";
                $content .='IMEI:'.$server_info['mp_imei']."\r\n";
                $content .='手机型号:'.$phone_info['server_model']['value']."\r\n";
                $content .='ID锁状态:'.$msg."\r\n";
                break;
            case 7:
                //正在维修
                $content .='序列号:'.$server_info['mp_sn']."\r";
                $content .='IMEI:'.$server_info['mp_imei']."\r\n";
                $content .='手机型号:'.$phone_info['server_model']['value']."\r\n";
                $content .='维修状态:'.$msg."\r\n";
                break;
            case 16:
                //sn&imei
                $content .='序列号:'.$server_info['mp_sn']."\r";
                $content .='IMEI:'.$server_info['mp_imei']."\r\n";
                $content .='手机型号:'.$phone_info['server_model']['value']."\r\n";
                $content .='购买日期:'.$phone_info['server_buy_date']['value']."\r\n";
                break;
            case 18:
                //整机查询
                $content .='序列号:'.$server_info['mp_sn']."\r";
                $content .='IMEI:'.$server_info['mp_imei']."\r\n";
                $content .='IMEI2:'.$server_info['mp_imei2']."\r\n";
                $content .='手机型号:'.$phone_info['server_model']['value']."\r\n";
                $content .='手机颜色:'.$phone_info['server_phone_color']['value']."\r\n";
                $content .='手机容量:'.$phone_info['server_phone_size']['value']."\r\n";
                $content .='ID锁:'.$phone_info['server_id_lock']['value']."\r\n";
                $content .='ID黑白:'.$phone_info['server_id_black']['value']."\r\n";
                $content .='购买日期:'.$phone_info['server_buy_date']['value'].'天'."\r\n";
                $content .='网络锁:'.$phone_info['server_net_lock']['value']."\r\n";
                $content .='网络锁黑白:'.$phone_info['server_net_lock_black']['value']."\r\n";
                $content .='是否替换:'.$phone_info['server_is_tihuan']['value']."\r\n";
                $content .='购买国家:'.$phone_info['server_sale_country']['value']."\r\n";
                break;

        }
        //结束位置

        $data = array(
            "first" => $content,
            "keyword1" => [$chaxun_config['name'],"#0000FF"],
            "keyword2" => [date("Y/m/d H:i:s",time()),"#5599FF"],
            "remark" => ['点击查看更多',"#FA5151"]
        );
        wechat_sending($user_id,$temp_ID,"https://www.aiguovip.com/vip/login/check_user/uid/$user_id?url=$url",$data);
    }
    //微信通知对方 --
    public function  test(){
       $arr= weixin_login('oOEJGwm5DEvSkq2j5IL1ibyqd6wk','1');
       print_r($arr);
       die();
    }

}