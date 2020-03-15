<?php

// +----------------------------------------------------------------------
// | Author: Jun
// | 在这里写的函数可以 全局
// +----------------------------------------------------------------------

// 应用公共文件
use app\common\Core;
use app\vip\model\User as UserM;
use app\vip\model\User as UserModel;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\Image;
use think\Db;

error_reporting(E_ERROR | E_WARNING | E_PARSE); //

if (!function_exists('is_mobile')) {

    /**
     * 判断客户端是否为手机
     * @return bool
     */
    function is_mobile()
    {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $is_pc = (strpos($agent, 'windows nt')) ? true : false;
        $is_mac = (strpos($agent, 'mac os')) ? true : false;
        $is_iphone = (strpos($agent, 'iphone')) ? true : false;
        $is_android = (strpos($agent, 'android')) ? true : false;
        $is_ipad = (strpos($agent, 'ipad')) ? true : false;
        if ($is_pc) {
            return false;
        }

        if ($is_mac) {
            return true;
        }

        if ($is_iphone) {
            return true;
        }

        if ($is_android) {
            return true;
        }

        if ($is_ipad) {
            return true;
        }

    }
}

if (!function_exists('jiangeshijian')) {

    /**
     * 时间转换 刚刚 几分钟 几小时 昨天 昨天
     * @return str
     */
    function jiangeshijian($t)
    {
        $strsj = '';
        $time = $t;
        $rtime = ''; //清空时间
        $htime = '';
        $time = time() - $time;
        if ($time < 60) {
            $strsj = '刚刚';
        } elseif ($time < 60 * 60) {
            $min = floor($time / 60);
            $strsj = $min . '分钟前';
        } elseif ($time < 60 * 60 * 24) {
            $h = floor($time / (60 * 60));
            $strsj = $h . '小时前 ' . $htime;
        } elseif ($time < 60 * 60 * 24 * 3) {
            $d = floor($time / (60 * 60 * 24));
            if ($d == 1) {
                $strsj = '昨天 ' . $rtime;
            } else {
                $strsj = '前天 ' . $rtime;
            }

        } else {
            $strsj = floor($time / (60 * 60 * 24)) . '天';
        }
        return $strsj;
    }
}

//不要天
if (!function_exists('jiangeshijian2')) {

    /**
     * 时间转换 刚刚 几分钟 几小时 昨天 昨天
     * @return str
     */
    function jiangeshijian2($t)
    {
        $strsj = '';
        $time = $t;
        $rtime = ''; //清空时间
        $htime = '';
        $time = time() - $time;
        if ($time < 60) {
            $strsj = '刚刚';
        } elseif ($time < 60 * 60) {
            $min = floor($time / 60);
            $strsj = $min . '分钟前';
        } elseif ($time < 60 * 60 * 24) {
            $h = floor($time / (60 * 60));
            $strsj = $h . '小时前 ' . $htime;
        } elseif ($time < 60 * 60 * 24 * 3) {
            $h = floor($time / (60 * 60));
            $strsj = $h . '小时前 ' . $htime;

        } else {
            $h = floor($time / (60 * 60));
            $strsj = $h . '小时前 ' . $htime;
        }
        return $strsj;
    }
}

//打印json 并结束
function write_json($code,$message="",$content=array()){
    $info['code']=$code;
    $info['message']=$message;
    $info['content']=$content;
    echo json_encode($info);
    exit();
}

/**
 * @param $user_id 用户id
 * @param $jifen 发生变化的积分账户
 * @param  $dongjie_jifen 发生变化 的冻结账户
 * @param  $remark 变化说明
 * @param  $change_type  1 查询扣积分  2 高级查询积分变动 3 后台管理员修改 4会员充值 5监控开启扣积分 6国别查询扣除积分
 * @param  $order_id 扣积分时候的ORDER_ID
 **/

function change_user_account($user_id,$jifen,$dongjie_jifen,$remark,$change_type=1,$order_id= 0){
    /* 插入帐户变动记录 */
    $account_log = array(
        'user_id'       => $user_id,
        'jifenxiaofei'    => $jifen,
        'dongjie_jifen'  => $dongjie_jifen,
        'create_time'   => time(),
        'chaxuntext'   => $remark,
        'change_type'   => $change_type,
        'order_id' => $order_id
    );
    Db::name('chaxun_jifenjilu')->strict(false)->insert($account_log);
    //当前账户绑定的微信更新积分
    Db::execute("update nb_admin_user set jifen=jifen+($jifen), dongjie_jifen=dongjie_jifen+($dongjie_jifen) where id= $user_id ") ;
}

/**
 * @param 生成唯一的订单号
 **/
function get_order_sn()
{
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);
    return date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * 判断是否微信浏览器访问
 */
function is_wxBrowers(){
    $str=strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger');
    if($str!==false){
        return true; //微信浏览器
    }
    return false; //非微信浏览器
}



/**截取指定两个字符之间的字符串
 *  * @param  $input 字符串
 * @param $start 开始字符串
 * @param  $end  结束字符串

 **/
function cut_str($input,$start,$end=''){
    $start_pos =strlen($start)+strpos($input, $start);
    if(strpos($input, $start)  === false ){
        return '';
    }
    if( $end==''){
        $substr = substr($input, $start_pos);
    }else{
        $substr = substr($input, $start_pos,(strlen($input) - strpos($input, $end, $start_pos))*(-1));
    }

    return $substr;
}


/**
 * @param  微信发送函数
 * @param  $uid 用户ID
 * @param  $mb_ID 模板ID
 * @param  $keyword2 时间或其他
 * @param  $remark 订单进度或说明，其他
 * @param  $url 链接地址
 **/
function wechat_sending($uid,$temp_ID,$url,$data = [])
{
    $openid = Db::name('admin_user')->where('id',$uid)->value('openid');

    //微信账号不存在 不发送ID  ;
    if(strlen($openid) <= 6){
        return;
    }

    $config = Config('wechat.');
    $wxapp = Factory::officialAccount($config);

    $t = $wxapp->template_message->send([
        'touser' => $openid,
        'template_id' => $temp_ID,
        'url' => $url,
        'data' => $data,
    ]);



    if (@$t['errmsg'] == 'ok') {
        //发送成功 就编辑全部 发送不成功不编辑！
//            $Save = new ListsnModel;
//            $bao = $Save->saveAll($plbaoarr);
        return '发送成功';
    } else {
        if($t['errcode']=='43004'){
            return '没有关注爱果';
//               $user = Db::name("admin_user")->where('id', 6)->update(['openid' => null]);

        }
        return '提醒发送失败！';
    }
}


function check_phone($phone){
    $pattern = "/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\d{8}$/";
    if (preg_match($pattern, $phone) == 0) {
        return false;
    }else{
        return true;
    }
}

/**
 * 下载图片到本地。
 * @param $url       要下载的图片地址
 * @param $file_name  下载回来存放路径加地址
return 本地的图片路径 'static/gsx_img/'.$file_name
 */
function download_img($url,$file_name) {
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



/**
 * 订单详情页
 * @param $server_id        服务id
 * @param null $sn_imei_data  sn或imei
 * @param null $is_wx  是否是微信
 * @param null $order_title 标题
 * @param null $order_id    二次添加的订单ID
 * @param null $order_type  订单类型
 * @return mixed
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function add_order($server_id,$sn_imei_data,$is_wx=0,$order_title=null,$order_id=null,$order_type=1)
{
    $haotime = time();

    //有传入IMEI 内容
    if ($sn_imei_data) {
        //定义积分
        $jifen_sum = 0;
        //查询查询列表对应的 信息内容
        $fuwu_list = Db::name('chaxun_config')->field('id,jifen')->where('id', 'in', $server_id)->select();
        //判断附带的服务积分要设为零
        foreach ($fuwu_list as $kes=>$vls) {
            if ($order_type == 2 || $order_type == 3) {
                if ($vls['id'] == 1) {
                    $fuwu_list[$kes]['jifen'] = 0;
                }
            }
        }
        //计算所有服务需要的 收款金额 下面会 乘以要查询的个数
        //计算总积分附带的服务去掉
        if ($order_type == 2 || $order_type == 3) {
            $server_id = array_diff($server_id,[1]);
        }
        $jifen_sum = Db::name('chaxun_config')->where('id', 'in', $server_id)->value('sum(jifen)');
        //判断多一次积分
        $user = Db::name('admin_user')->where('id', session('user.id'))->find();

        //把所有查询设置成数组
        /** $imeisn_arr
         * [list] 正确列表
         * [cuowu] 错误信息
         * [chongfu] 重复信息 返回数组
         */
        $imeisn_arr = arr_imeisn($sn_imei_data, $haotime);
        $imeisn_arr2 = $imeisn_arr['list'];
        foreach ($imeisn_arr['list'] as $ks => $vs) {
            $var = $vs['mp_sn'];
            if (empty($var)) {
                $imeisn_arr['list'][$ks]['mp_model'] = null;
                $imeisn_arr['list'][$ks]['mp_rongliang'] = null;
                $imeisn_arr['list'][$ks]['mp_color'] = null;
                $imeisn_arr['list'][$ks]['mp_net'] = null;
                continue;
            }
            $wangluo = Core::getModelBySn4($var);
            $imeisn_arr['list'][$ks]['mp_model'] = $wangluo[1];
            $imeisn_arr['list'][$ks]['mp_rongliang'] = $wangluo[2];
            $imeisn_arr['list'][$ks]['mp_color'] = $wangluo[3];
            $imeisn_arr['list'][$ks]['mp_net'] = $wangluo[4];
        }

        //二次智能添加处理
        if ($order_id) {
            $order_list = Db::name('cx_order_info')->where('id', $order_id)->where('user_id', session('user.id'))->select();
            if (count($order_list) > 0) {
                //二维转一维数组
                $result = [];
                array_walk_recursive($imeisn_arr2, function ($value) use (&$result) {
                    array_push($result, $value);
                });
                $arr_qc_sn = array_unique(array_merge(array_filter($result)));//数组去空，去序列化，去重
                //查最后增加的标识
                $biaoshi = Db::name('cx_order_phone')->where('order_id', $order_id)->order('server_cx_biaoshi desc')->value('server_cx_biaoshi');
                //查重复的数据
                $mp_sn= Db::name('cx_order_phone')->field('mp_sn,mp_imei')->where('order_id', $order_id)->whereIn('mp_sn|mp_imei', $arr_qc_sn)->select();
                //--- 以前的去重 ---过滤数据库已添加的数据
//                        $unique_arrs = [];
//                        foreach ($imeisn_arr2 as $key => $value) {
//                            if (!in_array($value, $order_sn)) {
//                                $unique_arrs[$key] = $value;
//                            }
//                        }
//                        $unique_arrs = array_values($unique_arrs);

                //--- 修改后的去重 ---过滤数据库已添加的数据
                foreach ($imeisn_arr2 as $ky => $vl) {
                    foreach ($mp_sn as $kysn => $vlsn) {
                        if ($vl['mp_sn'] == $vlsn['mp_sn']) {
                            //记录重复的内容
                            $imeisn_arr['repeat_content'] = $imeisn_arr['repeat_content']."\n".$vl['mp_sn'];
                            $imeisn_arr2[$ky]['mp_sn'] = '';
                        }

                        if ($vl['mp_imei'] == $vlsn['mp_imei']) {
                            //记录重复的内容
                            $imeisn_arr['repeat_content'] = $imeisn_arr['repeat_content']."\n".$vl['mp_imei'];
                            $imeisn_arr2[$ky]['mp_imei'] = '';
                        }

                        if ($imeisn_arr2[$ky]['mp_sn'] == '' && $imeisn_arr2[$ky]['mp_imei'] == '') {
                            unset($imeisn_arr2[$ky]);
                        }
                    }
                }

                $unique_arrs = $imeisn_arr2;
                //判断过滤后是否为空
                if (empty($unique_arrs)) {
                    $arr_str = implode(",", $arr_qc_sn);
                    $arr_str = preg_replace('#,#', "\r\n", $arr_str); //逗号转换换行
                    $data['code'] = 6;
                    $data['message'] = "亲，你添加的序列号/串号：".$arr_str."之前都已添加！"."\n"."或序列号错误";
                    return $data;
                }

                //计算总积分
                $koujifen = count($imeisn_arr['list']) * $jifen_sum;

                //如果积分充足就保存
                if ($koujifen > $user['jifen']) {
                    $data['code'] = 2;
                    $data['message'] = '亲，您的积分不足!';
                    return $data;
                }

                //赋值
                $imeisn_arr2 = $unique_arrs;
                foreach ($imeisn_arr2 as $ks => $vs) {
                    $var = $vs['mp_sn'];
                    if (empty($var)) {
                        $imeisn_arr2[$ks]['mp_model'] = null;
                        $imeisn_arr2[$ks]['mp_rongliang'] = null;
                        $imeisn_arr2[$ks]['mp_color'] = null;
                        $imeisn_arr2[$ks]['mp_net'] = null;
                        continue;
                    }
                    $wangluo = Core::getModelBySn4($var);
                    $imeisn_arr2[$ks]['mp_model'] = $wangluo[1];
                    $imeisn_arr2[$ks]['mp_rongliang'] = $wangluo[2];
                    $imeisn_arr2[$ks]['mp_color'] = $wangluo[3];
                    $imeisn_arr2[$ks]['mp_net'] = $wangluo[4];
                }
                $imeisn_arr['total'] = count($unique_arrs); //统计添加的数量

                //插入订单手机表
                foreach ($imeisn_arr2 as $k => $v) {
                    $biaoshi = substr($biaoshi, 0, -4) . sprintf("%04d", substr($biaoshi, -4) + 1);
                    $order_ph[] = ['order_id' => $order_id,
                        'mp_sn' => strtoupper($v['mp_sn']),
                        'mp_imei' => $v['mp_imei'],
                        'server_cx_biaoshi' => $biaoshi,
                        'mp_model' => $v['mp_model'],
                        'mp_rongliang' => $v['mp_rongliang'],
                        'mp_color' => $v['mp_color'],
                        'mp_net' => $v['mp_net']
                    ];
                    //插入订单手机服务表
                    foreach ($fuwu_list as $val) {
                        $order_server[] = ['server_id'=>$val['id'],'amount'=>$val['jifen'],'server_cx_biaoshi'=>$biaoshi,'add_time'=>$haotime,'order_id'=>$order_id,'is_weixin'=>$is_wx];
                    }
                }

                //统计查询的数量
                $cx_server_count = $order_list[0]['cx_server_count'] + count($order_server);
                if (!Db::name('cx_order_info')->where('id', $order_id)->update(['cx_server_count' => $cx_server_count])) {
                    $data['code'] = 3;
                    $data['message'] = "添加查询的统计数量失败";
                    return $data;
                }
                //插入序列号
                if (!Db::name('cx_order_phone')->insertAll($order_ph)) {
                    $data['code'] = 4;
                    $data['message'] = "查询维修添加失败";
                    return $data;

                }
                //插入查绚ID
                if (!Db::name('cx_server_info')->insertAll($order_server)) {
                    $data['code'] = 5;
                    $data['message'] = "查询维修ID添加失败";
                    return $data;
                }

                //扣积分
                $remark = '使用高级查询花费' . $koujifen . '积分，冻结积分增加' . $koujifen . '积分,二次添加';
                change_user_account(session('user.id'), '-' . $koujifen, $koujifen, $remark, 2);
                //提示
                $data['url'] = url('vip/chaxun/order_detail', ['id' => $order_id]);
                $data['code'] = 1;
                $data['message'] = '一共为您成功查询到[' . count($imeisn_arr['list']) . ']条数据，无效：' . ($imeisn_arr['total'] - count($imeisn_arr['list'])) . '条数据,重复：' . $imeisn_arr['repeat_sum'] . '条数据，重复的内容：' . $imeisn_arr['repeat_content'];
                return $data;
            } else {
                $data['code'] = 7;
                $data['message'] = '此订单您没有操作权限';
                return $data;

            }
        }

        /** 如果查询的数量大于0 就开始扣积分 */
        if (count($imeisn_arr['list']) > 0) {
            /** 判断是否要消费积分 需要消费积分就判断积分是否足够*/
            //如果需要扣积分 直接扣？
            //计算所需总积分
            $koujifen = count($imeisn_arr['list']) * $jifen_sum;

            if ($koujifen > 0) {
                //如果积分充足就保存
                if ($koujifen <= $user['jifen']) {
                    //开始扣积分？
                    $order['user_id'] = session('user.id');
                    $order['order_amount'] = $koujifen;
                    $order['add_time'] = $haotime;
                    $order['order_title'] = $order_title;
                    $order['order_type'] = $order_type;
                    do {
                        $order['cx_order_sn'] = get_order_sn(); //获取新订单号
                        $sums = Db::name('cx_order_info')->where('cx_order_sn', $order['cx_order_sn'])->count();
                    } while ($sums > 0);


                    //生成订单
                    if (!Db::name('cx_order_info')->insert($order)) {
                        $data['code'] = 8;
                        $data['message'] = "查询流水添加失败";
                        return $data;
                    }
                    $order_Id = Db::name('cx_order_info')->getLastInsID();

                    //插入订单手机表
                    foreach ($imeisn_arr['list'] as $k => $v) {
                        $server_cx_biaoshi = $order['cx_order_sn'] . sprintf("%04d", $k + 1);
                        $order_ph[] = [
                            'order_id' => $order_Id,
                            'mp_sn' => strtoupper($v['mp_sn']),
                            'mp_imei' => $v['mp_imei'],
                            'server_cx_biaoshi' => $server_cx_biaoshi,
                            'mp_model' => $v['mp_model'],
                            'mp_rongliang' => $v['mp_rongliang'],
                            'mp_color' => $v['mp_color'],
                            'mp_net' => $v['mp_net']
                        ];
                        //插入订单手机服务表
                        foreach ($fuwu_list as $val) {
                            $order_server[] = [
                                'server_id' => $val['id'],
                                'amount' => $val['jifen'],
                                'server_cx_biaoshi' => $server_cx_biaoshi,
                                'add_time' => $haotime,
                                'order_id' => $order_Id,
                                'is_weixin'=>$is_wx
                            ];
                        }
                    }

                    //统计查询的数量
                    $cx_server_count = count($order_server);
                    if (!Db::name('cx_order_info')->where('id', $order_Id)->update(['cx_server_count' => $cx_server_count])) {
                        $data['code'] = 9;
                        $data['message'] = "添加查询的统计数量失败";
                        return $data;
                    }
                    //插入序列号
                    if (!Db::name('cx_order_phone')->insertAll($order_ph)) {
                        $data['code'] = 10;
                        $data['message'] = "查询维修添加失败";
                        return $data;
                    }
                    //插入查绚ID
                    if (!Db::name('cx_server_info')->insertAll($order_server)) {
                        $data['code'] = 11;
                        $data['message'] = "查询维修ID添加失败";
                        return $data;
                    }
                    //扣积分
                    $remark = '使用高级查询花费' . $koujifen . '积分，冻结积分增加' . $koujifen . '积分';
                    change_user_account(session('user.id'), '-' . $koujifen, $koujifen, $remark, 2, $order_Id);
                    //返回微信的数据
                    if ($is_wx == 1) {
                        $sn_imei = array_slice($imeisn_arr['list'],0,20);
                        foreach($sn_imei as $val) {
                            $sn_im[] = $val['mp_sn'];
                            $sn_im[] = $val['mp_imei'];
                        }
                        $data['url'] = url('vip/chaxun/order_detail', ['id' => $order_Id]);
                        $data['code'] = 1;
                        $data['message'] = ['sn_imei'=>array_filter($sn_im),'total'=>count($imeisn_arr['list'])];
                        return $data;
                    }

                    $data['url'] = url('vip/chaxun/order_detail', ['id' => $order_Id]);
                    $data['code'] = 1;
                    $data['message'] = '一共为您成功查询到[' . count($imeisn_arr['list']) . ']条数据，无效：' . ($imeisn_arr['total'] - count($imeisn_arr['list'])) . '条数据,重复：' . $imeisn_arr['repeat_sum'] . '条数据，重复的内容：' . $imeisn_arr['repeat_content'] . '错误' . $imeisn_arr['cuowu'];
                    return $data;
                } else  $data['code'] = 13;
                $data['message']  = "积分不足。\r\n所需积分：" . $koujifen . " ,当前积分：" . $user['jifen'];
                return $data;
            } else  $data['code'] = 14;
            $data['message']  = "添加的积分为空";
            return $data;
            //判断序列号或者IMEI
        } else  $data['code'] = 15;
        $data['message'] = "无效的序列号/串号！" . "\n" . "或序列号/串号不能为空！";
        return $data;
    } else  $data['code'] = 16;
    $data['message'] = "提交参数错误";
    return $data;
}



/**
 * 整理IMEI 和SN 成为数组
 * [list]=imei&sn保存列表array
 * [cuowu]=错误提示
 * @param string $msg_type 触发类型
 * @param object $app easywechat项目
 * @return array
 */
function arr_imeisn($_text,$haotime)
{
    //转换格式
    $_text = str_replace('	', " ", $_text); //空格转换换行
    $imeiarr = explode("\r\n", $_text);

    $unique_arr = array_unique($imeiarr); //去重复后的列表
//        $_text = preg_replace('# #', "\r\n", $_text); //空格转换换行
//        $_text = preg_replace('# #', "\r\n", $_text); //空格转换换行
//        $_text = preg_replace('#	#', "\r\n", $_text); //空格转换换行
//        $imeiarr2 = explode("\r\n", $_text); //切割成数组
//        $unique_arr2 = array_unique($imeiarr2); //去掉重复的值
//        $repeat_arr2 = array_diff_assoc($imeiarr2, $unique_arr2);//得到重复的数据 //count($repeat_arr) 输出重复个数
//        $imeiarr2 = array_unique($repeat_arr2); //去重复后的列表
//        $unique_arrs = []; //最后循环去重得到的数据
//        foreach ($imeiarr as $key=>$value)
//        {
//            if (!in_array($value, $imeiarr2))
//            {
//                $unique_arrs[$key] = $value;
//            }
//        }
//        $unique_arrs = array_values($unique_arrs);
//        var_dump($unique_arrs);die;
    /** 获取重复数据的数组 */
    $repeat_arr = array_diff_assoc($imeiarr, $unique_arr); //count($repeat_arr) 输出重复个数
    $repeat_chongfu = chongfu($repeat_arr); //输出重复的内容
    /** 判断IMEI或者SN 或者维修ID的正确 */
    $yzh_imei = array();
    $yzh_imei['list'] = [];
    $yzh_imei['total'] = count($imeiarr);
    $yzh_imei['repeat_sum'] = count($repeat_arr);
    $yzh_imei['repeat_content'] = $repeat_chongfu;
    if ($yzh_imei['repeat_content'] == ''){
        $yzh_imei['repeat_content'] = '没有重复的内容';
    }

    $cuowu = "";
    $yzh_imei['cuowu'] = '';
    $yzh_imei['chongfu'] = '';

    $Last = end($unique_arr);
    $arr_quchong = [];
    //将已经找出来的串号进行循环
    foreach ($unique_arr as $k => $v) {
        $split_input_item = array_values(array_filter(explode(" ", $v)));  // 转换成标准的三维数组
        $get_sn_imei_arr = get_sn_imei_arr($split_input_item);

        if(count($get_sn_imei_arr) == 0 ){
            $cuowu .= '[' . $v . '] 错误行：' . ($k + 1)." \n";
        }else{
            $arr_quchong[] = array(
                'mp_sn' => @$get_sn_imei_arr['sn'],
                'mp_imei' => @$get_sn_imei_arr['imei']
            );
        }
    }
    //过滤重复的数据
    if (count($arr_quchong) > 0) {
        $yzh_imei['list'] = array();
        foreach ($arr_quchong as $key => $value) {
            if (!in_array($value, $yzh_imei['list'])) {
                $yzh_imei['list'][$key] = $value;
            }
        }
        $yzh_imei['list'] = array_values($yzh_imei['list']);
    }
    if ($cuowu)
        $yzh_imei['cuowu'] = "\n--------------------\n输入错误：\n" . $cuowu;
    if ($repeat_chongfu) $yzh_imei['chongfu'] = $repeat_chongfu;
    return $yzh_imei;
}


/**
 * 重复处理
 * @param string $msg_type 触发类型
 * @param object $app easywechat项目
 * @return array
 */
function chongfu($repeat_arr)
{
    $repeat_chongfu = '';
    if ($repeat_arr) {
        $repeat_chongfu = "";
        $Last = end($repeat_arr);
        foreach ($repeat_arr as $chongvvv) {
            if ($chongvvv && $chongvvv != " " && $chongvvv != "\n") {
                if (preg_match("#^(35|99|01)\d{12,13}$#", $chongvvv) || preg_match("#^[A-Za-z][A-Za-z0-9]{10,11}$#", $chongvvv)) {
                    $repeat_chongfu .= $chongvvv;
                    if ($Last != $chongvvv)
                        $repeat_chongfu .= "\n";
                }
            }
        }
        if ($repeat_chongfu)
            $repeat_chongfu = "\n--------------------\n删除重复：\n" . $repeat_chongfu;
    }
    return $repeat_chongfu;
}

/**
 * 计算IMEI最后一位
 * @param string $msg_type 触发类型
 * @param object $app easywechat项目
 * @return int 返回第15位数字
 */
function imei15($imei)
{
    //计算最后一位
    //  $imei = '12345678901234';
    $step2 = 0;
    $step2a = 0;
    $step2b = 0;
    $step3 = 0;

    for ($i = count(str_split($imei)); $i < 14; $i++)
        $imei = $imei . "0";

    for ($i = 1; $i < 14; $i = $i + 2) {
        $step1 = str_split($imei)[$i] * 2 . "0";
        $step2a = $step2a + intval(str_split($step1)[0]) + intval(str_split($step1)[1]);
    }
    for ($i = 0; $i < 14; $i = $i + 2)
        $step2b = $step2b + intval(str_split($imei)[$i]);

    $step2 = $step2a + $step2b;

    if ($step2 % 10 == 0)
        $step3 = 0;
    else
        $step3 = 10 - $step2 % 10;
    if (is_numeric($step3))
        return $step3;
}


//目前只支持传入一个三维数组  返回 array(sn, imei, gnum)
function get_sn_imei_arr($arr){
    $imei_sn=array();
    foreach ($arr as $index=>$item){
        if( preg_match("#^[A-Za-z][A-Za-z0-9]{11}$#", $item)){
            if (strpos(strtoupper($item),'O') ==false && strpos(strtoupper($item),'I') ==false) {
                $imei_sn['sn'] = $item;
            }
        }


        // 如果有IMEI
        if(preg_match("#^(35|99|01)\d{12,13}$#", $item) ){
            //如果是MEID的时候，自动转换IMEI
            if (preg_match("#^(35|99|01)\d{12}$#", $item)) {
                $item = $item . intval(imei15(substr($item , 0, 14)));
                $imei_sn['imei'] = $item;
            }

            $meid = substr($item,0,14);
            $items = $meid . intval(imei15(substr($item , 0, 14)));
            if ($items == $item) $imei_sn['imei'] = $item;

        }
    }
    return $imei_sn ;
}


//创建用户
function create_my_ewm($user_id){
    if($arr = cache('user_ewm1'.$user_id)){ //加一个二维码有效期放在CACHE里
        return $arr;
    }

    $config = Config('wechat.');
    $wxapp = Factory::officialAccount($config);
    $result = $wxapp->qrcode->temporary(intval($user_id), 6 * 24 * 3600);//传入用户的uid
    //生成带参数的url 二维码url
    $url = $wxapp->qrcode->url($result['ticket']);

    // 下载用户推广 二维码
    $img_path  ='static/user_ewm/ewm_'.$user_id.'.jpg';
    download_img($url,$img_path); //先下载到服务器
    //上传图片到微信生成 media_id;  --
    $QR = imagecreatefromstring(file_get_contents($img_path));   //二维码
    $logo = imagecreatefromstring(file_get_contents("static/logo.jpg"));
    $QR_width = imagesx($QR);//二维码图片宽度
    $QR_height = imagesy($QR);//二维码图片高度
    $logo_width = imagesx($logo);//logo图片宽度
    $logo_height = imagesy($logo);//logo图片高度
    $logo_qr_width = intval($QR_width / 5);
    $scale = $logo_width/$logo_qr_width;
    $logo_qr_height = intval($logo_height/$scale);
    $from_width = ($QR_width - $logo_qr_width) / 2;
    //重新组合图片并调整大小
    imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);
    imagepng($QR, $img_path);//生成二维码
    // 生成推广码
    $moban = imagecreatefromstring(file_get_contents("static/user_ewm/0moban.jpg"));
    $mb_width = imagesx($moban);//二维码图片宽度
    $mb_height = imagesy($moban);//二维码图片高度
    $QR = imagecreatefromstring(file_get_contents($img_path));   //二维码

    imagecopyresampled($moban, $QR, 217, 486, 0, 0, 210,210, $QR_width, $QR_height);
    imagepng($moban, $img_path);//生成二维码

    $mediaId=$wxapp->media->uploadImage($img_path);
    $image = new Image($mediaId['media_id']);
    $arr =  array('wx_image'=>$image,'img_path'=>$img_path);
    cache('user_ewm1'.$user_id,$arr,3600*24*28);  //加一个二维码有效期放在CACHE里
    return $arr;
}


/**
 * 生成随机字符串
 * @author
 * @param int $length
 * @return string
 */
function createNonceStr($length = 4) {
    $chars = "123456789ABCDEFGHIJK";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}


/**
 * 微信登入
 * @return mixed
 */
function weixin_login($openid ,$pid=null,$status=null)
{
    $time = time();

    $config = Config('wechat.');
    $appc= Factory::officialAccount($config);
    $weixin_info =  $appc->user->get($openid);        //获取用户的微信信息


    session('user.wx_touxiang', $weixin_info['headimgurl']);
    session('user.openid',      $weixin_info['openid']);
    session('user.wx_name',     $weixin_info['nickname']);
    $user = session('user');

    //微信表的信息
    $weixin_user = Db::name('weixin_user')->where('openid', $openid)->find();
    //如果微信表没有的要赠送积分 并把微信表插入进去
    if(!$weixin_user){
        $is_reg=true;
        $send_score =2; //用户积分赠送
        if(intval($pid)> 0) //给上级发积分
            $send_p_score =2;
        Db::name('weixin_user')->insert($weixin_info);//插入微信INFO
    }

    //用户表里有没有这个记录了
    $admin_user = Db::name('admin_user')->where('openid', $openid)->find();
    //存在直接登录返回
    if($admin_user){
        $loginarr = UserM::where('openid', $openid)->select();
        $login = UserM::where('openid', $user['openid'])->where('username', $loginarr[0]['username'])->find();
        $wxuser['user'] = $login;
        $wxuser['user']['login_at'] = $time;
        $wxuser['user']['openid'] = $user['openid'];
        $wxuser['user']['wx_touxiang'] = $user['wx_touxiang'];
        $wxuser['user']['wx_name'] = $user['wx_name'];
        session('user', $wxuser['user']);
        $arr = ['code'=>1,'is_reg'=>2,'data'=>'登入成功'];
        return $arr;
    }else{
        //循环不重复出用户名 8位随机
        do {
            //注册用户
            $str_name = createNonceStr(8);
            $sums = Db::name('admin_user')->where('username', $str_name)->count();
        } while ($sums > 0);

        $admin_user = [
            'username'=>$str_name, // 新的用户名
            'nickname'=>$weixin_info['nickname'],
            'wx_name'=>$weixin_info['nickname'],
            'password'=>'888888',
            'avatar'=>$weixin_info['headimgurl'],
            'create_time'=> $time,
            'wx_gxtime'=> $time,
            'jifen' =>intval($send_score),
            'openid'=>$openid,
            'partent_id' => intval($pid)
        ];

        $user = UserModel::create($admin_user); //创建用户并发送消息
        session('user', $user);
        if ($user) {
            //判断openid是否存在
            if (intval($send_score)>0) {
                $zs_str =  '系统已赠送您' . number_format($send_score,2) . '积分,点击详情查看';
                change_user_account($user['id'], $send_score, 0, '注册赠送', 7, 0);  //添加记录
            }

            $first = '会员【' . $admin_user['nickname'] . '】注册成功!'.$zs_str ;
            //发送微信
            $temp_ID = '9H3Tc-xgtG8GHAfiNTa3Bki5Zh2vs4SNl1-YRgCvrms';
            $url = 'https://www.aiguovip.com';
            $data = array(
                "first" => [$first, "#5599FF"],
                "keyword1" => [$admin_user['username'], "#0000FF"],
                "keyword2" => ['888888', "#0000FF"],
                "remark" => ["\r现在您可以登陆使用爱果系统了。", "#5599FF"],
            );
            wechat_sending($user['id'], $temp_ID, $url, $data);

        }


        //如果上级赠送积分大于0 发模版给他
        if (intval($send_p_score) > 0){
            $parent_info= Db::name('admin_user')->field('id,jifen')->where('id', $pid)->find();
            if ($parent_info){
                //添加记录
                $send_p_score_format =  number_format($send_p_score,2);

                $remark = '你推荐的会员【'.$user['nickname'].'】注册成功，赠送您'.$send_p_score_format.'积分';
                change_user_account($parent_info['id'], $send_p_score, 0, $remark, 7, 0);
                //发送微信
                $url = 'http://www.aiguovip.com/vip/user/jifenjilu.html';
                $temp_ID = 'j3yBQHzf4mG0eEmjr_ldtirtEJE4xj2S5OB_NKlutQg';
                //当前余额
                $sum_jifen = $send_p_score+$parent_info['jifen'];
                $sum_jifen = number_format($sum_jifen,2);

                $data = array(
                    "keyword1" => [$send_p_score_format.'元',"#5599FF"],
                    "keyword2" => [$sum_jifen.'元',"#5599FF"],
                    "remark" => ['变动原因：'.$remark,"#0000FF"]
                );
                // echo 'user_id.'.$parent_info['id'];
                // print_r($data);
                wechat_sending($parent_info['id'],$temp_ID,$url,$data);
            }
        }
    }
    if ($is_reg){
        $arr = ['code'=>1,'is_reg'=>1,'data'=>'账号:'.$user['username'].'密码:888888'];
    }else{
        $arr = ['code'=>1,'is_reg'=>2,'data'=>'登入成功'];
    }

    return $arr;

}


