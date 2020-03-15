<?php
namespace app\wechatapp\controller;

require_once 'AipOcr.php';//百度云图片智能识别

class ImgIdentify{

    protected $app_id;
    protected $api_key;
    protected $secret_key;

    public function __construct()
    {
        $this->app_id = config("api.bdy.app_id");
        $this->api_key = config("api.bdy.api_key");
        $this->secret_key = config("api.bdy.secret_key");
    }

    /**
     * 传入图片的url
     */
    public function bdy_img_identify_url($url){
        $client = new \AipOcr($this->app_id, $this->api_key, $this->secret_key);
        //参数设置
        $options["language_type"] = "CHN_ENG";//识别语言类型
        $options["detect_direction"] = "true";//检测图像朝向
        $options["detect_language"] = "true";//检测语言
        $options["probability"] = "true";//每一行的置信度
        // 调用通用文字识别（含位置信息版）
        $arr = $client->generalUrl($url, $options);
        $str = '';
        foreach($arr['words_result'] as $k => $v){
            $str .= $v['words'];
        }
        //正则
        $sn_ppp = '/[A-Z][A-Z-0-9]{11}/';//sn正则匹配  GDGZ112DJCLF
        $imei_ppp = '/[1-9][0-9]{14}/';//imei正则匹配  354840098465918
        preg_match_all($sn_ppp,$str,$sn);
        preg_match_all($imei_ppp,$str,$imei);
        return ['sn'=>$sn,'imei'=>$imei];
    }

    /**
     *
     * 传入图片的base64
     */
    public function bdy_img_identify($image){
        $image = substr($image, strpos($image,'base64,')+7);
        $client = new \AipOcr($this->app_id, $this->api_key, $this->secret_key);
        //参数设置
        $options["language_type"] = "CHN_ENG";//识别语言类型
        $options["detect_direction"] = "true";//检测图像朝向
        $options["detect_language"] = "true";//检测语言
        $options["probability"] = "true";//每一行的置信度
        // 调用通用文字识别（含位置信息版）
        $arr = $client->general($image, $options);
        $str = '';
        foreach($arr['words_result'] as $k => $v){
            $str .= $v['words'];
        }
        $str = str_replace(' ', '', $str);//去除所有空格
        //正则
        $sn_ppp = '/[A-Z][A-Z0-9]{11}/';//sn正则匹配  GDGZ112DJCLF
        $imei_ppp = '/[1-9][0-9]{14}/';//imei正则匹配  354840098465918
        preg_match_all($sn_ppp,$str,$sn);
        preg_match_all($imei_ppp,$str,$imei);
        return ['sn'=>$sn,'imei'=>$imei];
    }


}