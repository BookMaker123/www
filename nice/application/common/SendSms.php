<?php
namespace app\common;

use think\Controller;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

// Download：https://github.com/aliyun/openapi-sdk-php
// Usage：https://github.com/aliyun/openapi-sdk-php/blob/master/README.md

class SendSms
{
    static $sendsms = 6666;
    /**
     * 短信发送验证码
     * @param string $AccessKeyID,$AccessKeySecret 钥匙,密码
     * @param AlibabaCloud::rpc() 请求头部设置
     * @param $result->toArray() 返回的数据
     * @param $result->toArray() 返回的数据
     * @param string $SignName 标识
     * @param string $TemplateCode 短信模板ID
     * @param  string $sendsms 4位数的验证码
     */
    public static function index($PhoneNumbers)
    {
        self::$sendsms = self::smscode(4);
        $sendsms = self::$sendsms;
        $AccessKeyID = config("api.sendsms.AccessKeyID");
        $AccessKeySecret = config("api.sendsms.AccessKeySecret");
        $SignName = config("api.sendsms.SignName");
        $TemplateCode = config("api.sendsms.TemplateCode");

        AlibabaCloud::accessKeyClient($AccessKeyID, $AccessKeySecret)
            ->regionId('cn-hangzhou') // replace regionId as you need
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options(['query' => ['RegionId' => "default",
                    'PhoneNumbers' => "$PhoneNumbers",
                    'SignName' => $SignName,
                    'TemplateCode' => $TemplateCode,
                    'TemplateParam' => "{\"code\":\"$sendsms\"}",],])
                ->request();
            //请求返回的数据
            $res = $result->toArray();
            if($res['Code'] == 'OK'){
                $data['status'] = 1;
                $data['info']   = $res['Message'];
            }else{
                $data['status'] = 0;
                $data['info']   = $res['Message'];
            }
            return $data;
        } catch
        //抛出异常
        (ClientException $e) {
            $data['status'] = 0;
            $data['info']   = $e->getErrorMessage();
            return $data;
//            return $e->getErrorMessage() . PHP_EOL;
        } catch (ServerException $e) {
            $data['status'] = 0;
            $data['info']   = $e->getErrorMessage();
            return $data;
//            return $e->getErrorMessage() . PHP_EOL;
        }
    }

    /**
     * 生成验证码
     * @param string $length 验证码的长度
     * @param string $smscode 4位数的验证码
     * @param session 存入时间和验证码
     */
    public static function smscode($length=4)
    {
        $min = pow(10 , ($length - 1));
        $max = pow(10, $length) - 1;
        $smscode = rand($min, $max);
        //存入时间和验证码
        session('smscode.code', $smscode);
        session('smscode.time', strtotime(date('Y-m-d H:i:s', strtotime('+3minute'))));
        return $smscode;
    }





}