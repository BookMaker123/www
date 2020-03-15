<?php
namespace app\wechatapp\controller;
use think\Db;
use app\common\Nb;

/*
* 组装自动回复内容
*/

class AutoReply
{
    /**
     * 回复
     * @param array $data we_reply表查出来的单条数据
     * @param object $app easywechat项目
     * @return object/false
     */
    protected function reply($message, $app, $weUser)
    {

        $viparr = config('vip.' . $weUser['cx_lei']); //获取查询参数
        $haotime = time();
        //判断是否维护 &&$message['FromUserName']!="oaXm5jpmc0hL_TSjaGupUfFpU73k"
        if ($viparr['weihu']&&$message['FromUserName']!="oaXm5jpmc0hL_TSjaGupUfFpU73k")
        {
            return '[' . $viparr['tishi'] . ']' . $viparr['weihutishi'];
        }

        $openId = $message['FromUserName'];
        $_text = $message['Content']; //发送的消息


        if (@$weUser['cx_lei'] == null)
            return '请先选择查询类型';
        if ($_text == 'qk5')
        {
            $shanchu = Db::name('sn')->where('cx_lei', 5)->where('openid', $openId)->delete();
            return "成功数据库清空[维修ID]" . $shanchu . "个";
        }
        /** 判断查询类型 */
        if (!config('?vip.' . $weUser['cx_lei']))
            return '查询类型错误,请重新选择查询类型！';

        //把所有查询设置成数组
        $imeisn_arr = $this->arr_imeisn($_text, $openId, $weUser, $haotime);


        /** 判断批量查询数量 */
        if (count($imeisn_arr['list']) > $viparr['chashu'])
            return '[' . $viparr['tishi'] . "]批量查询不能超过" . $viparr['chashu'] . "个，当前个数" . count($imeisn_arr['list']);

        /** 如果查询的数量大于0 就开始扣积分 */
        if (count($imeisn_arr['list']) > 0)
        {
            /** 判断是否要消费积分 需要消费积分就判断积分是否足够*/
            //如果需要扣积分 直接扣？
            $koujifentishi = '';
            $koujifen = count($imeisn_arr['list']) * $viparr['jifen'];
            if ($koujifen > 0)
            {
                //判断多一次积分
                $user = Db::name('user')->where('openid', $openId)->find();
                //如果积分充足就保存
                if ($koujifen <= $user['vip_score'])
                {
                    //开始扣积分？
                    if (!Db::name('user')->where('openid', $openId)->setDec('vip_score', $koujifen))
                        return '积分消费失败，稍后重试';
                } else
                    return "积分不足，查询失败。\n\n所需积分：" . $koujifen . "\n当前积分：" . $user['vip_score'] . "\n\n<a href='http://wx.aiguovip.com/pay'>充值积分</a>";
                $koujifentishi = "\n\n消费积分：" . $koujifen;
                $koujifentishi .= "\n剩余积分：" . ($user['vip_score'] - $koujifen) . "\n";
            }

            /** 积分判断通过，现在开始查询保存到数据库*/
            if (!Db::name('sn')->insertAll($imeisn_arr['list']))
            {
                //这里应该还要写个！！！！！！！！！返回积分？
                return '提交查询失败，请稍后重试!如果有扣除积分，请联系客服！';
            }

            /** 结果URL 根据保存的数据库 返回URL*/
            if ($viparr['url'])
            {
                $tibietishi = '';
                if ($weUser['cx_lei'] == 5)
                {
                    $tibietishi = '查询维修ID推荐【序列号】速度更快'. "\n";
                }
                $openIdjiami = base64_encode(Nb::authcode($openId, 'jiami', 'tiamodandy520'));
                $jieguourl = $viparr['url'] . "/group/" . $weUser['cx_lei'] . "/haotime/$haotime/uid/$openIdjiami.html";
                $viparr['url'] = $tibietishi . "\n<a href='" . $jieguourl . "'>查看结果列表</a>";
            }
            return '正在为您查询[' . $viparr['tishi'] . ']' . count($imeisn_arr['list']) . '个,请稍等...' . $imeisn_arr['chongfu'] . $imeisn_arr['cuowu'] . $koujifentishi . $viparr['url'];
        } else
        {
            return '请输入正确的查询IMEI/SN' . $imeisn_arr['chongfu'] . $imeisn_arr['cuowu'];
        }


        return '未知错误，请联系客服！';
    }


    /**
     * 是否需要自动回复
     * @param string $msg_type 触发类型
     * @param object $app easywechat项目
     * @return array
     */
    protected function needAutoReply($msg_type)
    {
        // return Db::name('we_reply')->where(['msg_type' => $msg_type, 'expires_date' => ['>', time()], 'status' => 1])->order('id desc')->find();

    }

    /**
     * 整理IMEI 和SN 成为数组
     * [list]=imei&sn保存列表array
     * [cuowu]=错误提示
     * @param string $msg_type 触发类型
     * @param object $app easywechat项目
     * @return array
     */
    private function arr_imeisn($_text, $openId, $weUser, $haotime)
    {

        //转换格式
        $_text = preg_replace('# #', "\n", $_text); //空格转换换行
        $_text = preg_replace('# #', "\n", $_text); //空格转换换行
        $_text = preg_replace('#	#', "\n", $_text); //空格转换换行
        $imeiarr = explode("\n", $_text);

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

        foreach ($unique_arr as $k => $v)
        {
            //如果是15位的IMEi 就判断下IMEI最后一位是否正确
            if (preg_match("#^(35|99|01)\d{13}$#", $v) && (intval($this->imei15(substr($v, 0, 14)) - intval(substr($v, -1)) != 0)))
            {
                $cuowu .= '[' . $v . '] 错误IMEI,行：' . ($k + 1);
                if ($Last != $v)
                    $cuowu .= "\n";
                continue;
            }
            //只支持序列号查询
            $viparr = config('vip.' . $weUser['cx_lei']); //获取查询参数
            if ($viparr['fangfa'] == 2)
            {
                if (preg_match("#^(35|99|01)\d{12,13}$#", $v))
                {
                    $cuowu .= '[' . $v . '] 不支持IMEI' . ($k + 1);
                    if ($Last != $v)
                        $cuowu .= "\n";
                    continue;
                }
            }
            //只支持IMEI
            if ($viparr['fangfa'] == 3)
            {
                if (preg_match("#^[A-Za-z][A-Za-z0-9]{10,11}$#", $v))
                {
                    $cuowu .= '[' . $v . '] 不支持序列号';
                    if ($Last != $v)
                        $cuowu .= "\n";
                    continue;
                }
            }


            if (preg_match("#^(35|99|01)\d{12,13}$#", $v) || preg_match("#^[A-Za-z][A-Za-z0-9]{10,11}$#", $v))
            {
                //如果是meid的时候，自动转换
                if (preg_match("#^(35|99|01)\d{12}$#", $v))
                {
                    $v = $v . intval($this->imei15(substr($v, 0, 14)));
                }

                //判断如果是提交的是序列号 直接保存序列号 不需要在转换
                $xlhao = null;
                if (preg_match("#^[A-Za-z][A-Za-z0-9]{10,11}$#", $v))
                    $xlhao = strtoupper($v);
                $yzh_imei['list'][] = array(
                    'openid' => $openId,
                    'cx_lei' => $weUser['cx_lei'],
                    'haotime' => $haotime,
                    'hao' => strtoupper($v),
                    'sn' => $xlhao,
                    );
            } else
            {
                if ($v && $v != " " && $v != "\n")
                {
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
        if ($repeat_arr)
        {
            $repeat_chongfu = "";
            $Last = end($repeat_arr);
            foreach ($repeat_arr as $chongvvv)
            {
                if ($chongvvv && $chongvvv != " " && $chongvvv != "\n")
                {
                    if (preg_match("#^(35|99|01)\d{12,13}$#", $chongvvv) || preg_match("#^[A-Za-z][A-Za-z0-9]{10,11}$#", $chongvvv))
                    {
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

        for ($i = 1; $i < 14; $i = $i + 2)
        {
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

}
