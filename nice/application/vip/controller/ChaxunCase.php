<?php

namespace app\vip\controller;

use app\common\Ztai;
use app\common\Jindu;
use app\common\Core;
use app\vip\model\Lists as ListsModel;
use app\vip\model\Listsn as ListsnModel;
use think\Controller;
use think\Db;
use think\facade\Cache;
use function GuzzleHttp\json_decode;

class ChaxunCase extends AdminController
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
    public function index()
    {
        $user_id = UID;
        $chaxun_list = Db::name('cx_order_info o')
            ->join('nb_cx_order_phone p', 'o.id=p.order_id', 'left')
            ->field('o.*, count(p.id) as phone_count')
            ->where('user_id', $user_id)
            ->where('o.is_del', 0)
            ->group('o.id')
            ->order('o.id desc')
            ->paginate(40)
            ->each(function ($item, $key) {
                $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
                return $item;
            });

        $this->assign('page', $chaxun_list->render());
        $this->assign('chaxun_list', $chaxun_list);
        return $this->fetch();
    }

    function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }

    /**  
     * 查询添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $haotime = time();
            if ($data['imeitext']) {
                $request = request();
                $type =$request->param()['type'];
               
                if ($type == "") $this->error("提交类型错误！");
               
                $fuwu = Db::name('chaxun_config')->where(['type'=>$type])->find();

                //把所有查询设置成数组
                /** $imeisn_arr 
                 * [list] 正确列表 
                 * [cuowu] 错误信息
                 * [chongfu] 重复信息
                 */
                $imeisn_arr = $this->arr_imeisn($data['imeitext'],  $this->user, $haotime);
                
                /** 如果查询的数量大于0 就开始扣积分 */
                if (count($imeisn_arr['list']) > 0) {
                    /** 判断是否要消费积分 需要消费积分就判断积分是否足够*/
                    //如果需要扣积分 直接扣？
                    $koujifentishi = '';
                    //计算所需总积分
                    $koujifen = count($imeisn_arr['list']) * $fuwu["jifen"];
                    if ($koujifen > 0) {

                        //判断多一次积分
                        $user = Db::name('weixin_user')->where('openid', $this->user['openid'])->find();
                        //如果积分充足就保存
                        if ($koujifen <= $user['jifen']) {
                            //开始扣积分？
                            if (!Db::name('weixin_user')->where('openid', $this->user['openid'])->setDec('jifen', $koujifen))
                                $this->error("积分消费失败，稍后重试");
                        } else
                            $this->error("积分不足。\r\n所需积分：" . $koujifen . " ,当前积分：" . $user['jifen']);
                        //保存消费积分记录
                        $jilutxt = '';
                        foreach ($imeisn_arr['list'] as $key=>$vvv) {
                            $jilutxt .= $vvv['hao'] . "\r\n";
                            // 将服务列表保存到数据库
                            $imeisn_arr['list'][$key]["chaxun_server_id_list"] = $fuwu['id'];
                            $imeisn_arr['list'][$key]["uid"] = UID;
                            
                        }
                        $jilu = ['openid' => $this->user['openid'], 'cx_lei' => "", 'shuliang' => count($imeisn_arr['list']), 'jifenxiaofei' => $koujifen, 'chaxuntext' => $jilutxt, 'create_time' => time()];
                        
                        Db::name('chaxun_jifenjilu')->strict(false)->insert($jilu);

                        $koujifentishi = "\n\n消费积分：" . $koujifen;
                        $koujifentishi .= "\n剩余积分：" . ($user['jifen'] - $koujifen) . "\n";
                       
                        // 将序列号、imei、服务列表保存到数据
                        if (Db::name('chaxun_list')->insertAll($imeisn_arr['list'])) {                            
                            $this->success('正在为您查询[' . $fuwu['name'] . ']' . count($imeisn_arr['list']) . '个,请稍等...' . $imeisn_arr['chongfu'] . $imeisn_arr['cuowu'] );
                            // 此处提供一个免费imei转sn的服务
                            
                        } else {
                            //这里应该还要写个！！！！！！！！！返回积分？
                            $this->error("提交查询失败，请稍后重试!如果有扣除积分，请联系客服！");
                        }
                    }
                }
                //判断序列号或者IMEI
            } else $this->error("查询内容为空！");
        } else {
            $this->error("提交参数错误");
        }
    }


    /**
     * 整理IMEI 和SN 成为数组
     * [list]=imei&sn保存列表array
     * [cuowu]=错误提示
     * @param string $msg_type 触发类型
     * @param object $app easywechat项目
     * @return array
     */
    private function arr_imeisn($_text, $User, $haotime)
    {
        //转换格式
        $_text = preg_replace('# #', "\r\n", $_text); //空格转换换行
        $_text = preg_replace('# #', "\r\n", $_text); //空格转换换行
        $_text = preg_replace('#	#', "\r\n", $_text); //空格转换换行
        $imeiarr = explode("\r\n", $_text);
        
        $unique_arr = array_unique($imeiarr); //去重复后的列表
        /** 获取重复数据的数组 */
        $repeat_arr = array_diff_assoc($imeiarr, $unique_arr); //count($repeat_arr) 输出重复个数
        $repeat_chongfu = $this->chongfu($repeat_arr); //输出重复的内容
        /** 判断IMEI或者SN 或者维修ID的正确 */
        $yzh_imei = array();
        $yzh_imei['list'] = [];

        $cuowu = "";
        $yzh_imei['cuowu'] = '';
        $yzh_imei['chongfu'] = '';
        
        $Last = end($unique_arr);
        foreach ($unique_arr as $k => $v) {
            
            //如果是15位的IMEi 就判断下IMEI最后一位是否正确
            if (preg_match("#^(35|99|01)\d{13}$#", $v) && (intval($this->imei15(substr($v, 0, 14)) - intval(substr($v, -1)) != 0))) {
                $cuowu .= '[' . $v . '] 错误IMEI,行：' . ($k + 1);
                if ($Last != $v)
                    $cuowu .= "\n";
                continue;
            }
        
            if (!preg_match("#^(35|99|01)\d{12,13}$#", $v) && !preg_match("#^[A-Za-z][A-Za-z0-9]{10,11}$#", $v)){
                $cuowu .= '[' . $v . '] 格式不正确' . ($k + 1);
                if ($Last != $v)
                    $cuowu .= "\n";
                continue;
            }
            if (preg_match("#^(35|99|01)\d{12,13}$#", $v) || preg_match("#^[A-Za-z][A-Za-z0-9]{10,11}$#", $v)) {
                //如果是MEID的时候，自动转换IMEI
                if (preg_match("#^(35|99|01)\d{12}$#", $v)) {
                    $v = $v . intval($this->imei15(substr($v, 0, 14)));
                }
               
                //判断如果是提交的是序列号 直接保存序列号 不需要在转换
                $xlhao = null;
                if (preg_match("#^[A-Za-z][A-Za-z0-9]{10,11}$#", $v))
                    $xlhao = strtoupper($v);
                $yzh_imei['list'][] = array(
                    'openid' => $User['openid'],
                    'haotime' => $haotime,
                    'hao' => strtoupper($v),
                    'sn' => $xlhao,
                );
            } else {
                if ($v && $v != " " && $v != "\n") {
                    $cuowu .= '[' . $v . ']错,行：' . ($k + 1);
                    if ($Last != $v)
                        $cuowu .= "\n";
                }
            }
        }

        if ($cuowu)
            $yzh_imei['cuowu'] = "\n--------------------\n输入错误：\n" . $cuowu;
        if ($repeat_chongfu)
            $yzh_imei['chongfu'] = $repeat_chongfu;

        return $yzh_imei;
    }
    /**
     * 重复处理
     * @param string $msg_type 触发类型
     * @param object $app easywechat项目
     * @return array
     */
    private function chongfu($repeat_arr)
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
    private function imei15($imei)
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
    // 软删除，只做数据隐藏 (批量删除)
    public function delete(){
        if ($this->request->isPost()) {
           
            $data = $this->request->post();
            $id_list = $data["ids"];
            if(count($id_list)>0){
                // 开始删除
                $rs = Db::name('chaxun_list')->where('id','in',$id_list)->where('openid',$this->user['openid'])->update(["soft_delete"=>1]);
                if ($rs){
                    return ["code"=>200,"msg"=>"删除成功","url"=>url('vip/chaxuntest/index')];
                }
            }else{
                return "参数不正确";
            }
        
        }
    }


}
