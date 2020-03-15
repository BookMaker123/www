<?php 
namespace wxpay;

/**
 * 支付类
 */
class Pay
{
	//默认配置
    protected static $salt = 'payment@#!361~qipai+qun^&*';

    /*微信支付 sign生成方式
      $data =>参数：
                source=4
                trade_no，自定义
                money，金额
                trade_type，pc
                trade_channel，wx
                sign，签名(可填可不填)
    */
    public static function _generateSign($dataGet)
    {
        ksort($dataGet);
        $rawSign = '';
        foreach ($dataGet as $key => $val) {
            if ($val) {
                $rawSign .= $key . '=' . $val . '&';
            }
        }
        $rawSign = rtrim($rawSign, '&');
        $rawSign .= self::$salt;

        return strtoupper(md5($rawSign));
    }

    /*生成唯一订单号*/
    public static function _order_number()
    {
        $order_id_main = date('YmdHis') . rand(10000000,99999999);
 
        //订单号码主体长度
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for($i=0; $i<$order_id_len; $i++){
            $order_id_sum += (int)(substr($order_id_main,$i,1));
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $order_id = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100,2,'0',STR_PAD_LEFT);

        return $order_id;
    }










}



 ?>
