<?php

namespace app\api\controller;

use EasyWeChat\Factory;
use think\Controller;
use think\Db;

class Payreturn extends Controller{
    /**
     * 初始化
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 微信扫码支付回调地址
     */
    public function wx_pay_callback(){
        $config = Config('wechat.');
        $config_pay = array(
            'app_id'             => $config['app_id'],
            'mch_id'             => $config['mch_id'],
            'key'                => $config['key'],   // API 密钥
        );

        $app = Factory::payment($config_pay);

        $response = $app->handlePaidNotify(function ($message, $fail) {
            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
            $order_sn = Db::name('pay_log')->where('order_sn',$message['out_trade_no'])->find();

            if (empty($order_sn) || $order_sn['is_pay'] != 0) { // 如果订单不存在 或者 订单已经支付过了
                return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }

            //若返回的金额与数据库不等
            if($order_sn['pay_money'] != $message['total_fee'] / 100){
                return $fail('通信失败，参数有误');
            }

            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                // 用户是否支付成功
                if ($message['result_code'] === 'SUCCESS') {
                    $update['pay_time'] = time(); // 更新支付时间为当前时间
                    $update['is_pay'] = 1;
                    $update['three_pay_id'] = $message['transaction_id'];//微信流水订单号
                    change_user_account($order_sn['user_id'],$message['total_fee'] / 100,0,'用户充值',4, '微信支付：'.$message['transaction_id']);
                } elseif ($message['result_code'] === 'FAIL') {// 用户支付失败
                    Db::name('log')->insert(['msg'=>'支付失败','c_time'=>date('Y/m/d',time())]);
                    $update['pay_time'] = time(); // 更新支付时间为当前时间
                    $update['is_pay'] = 2;
                    $update['three_pay_id'] = $message['transaction_id'];//微信流水订单号
                }
            } else {
                return $fail('通信失败，请稍后再通知我');
            }

            Db::name('pay_log')->where('pay_id',$order_sn['pay_id'])->update($update); // 保存订单
            return true; // 返回处理完成
        });

        $response->send();
    }



}