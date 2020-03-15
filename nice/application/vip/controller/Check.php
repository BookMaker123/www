<?php
namespace app\vip\controller;

use app\common\Ztai;
use app\common\Jindu;
use app\common\Core;
use app\vip\model\Config;
use app\vip\model\Lists as ListsModel;
use app\vip\model\Listsn as ListsnModel;
use think\Controller;
use think\Db;
use think\Exception;
use think\facade\Cache;
include ('dhrufusionapi.class.php');
class Check extends AdminController
{

    /**
     * 初始化
     * Lists constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }
    public function index()
    { }
    /**
     * 所有查询接口
     * 支持简单查询 和复查查询post过来的
     * @return mixed
     */
    public function api()
    {
        if ($this->request->isPost() && input('?post.tiamo')) {
            $id = input('post.tiamo');

            // 如果有G号或者没有G号的数据
            $data_sn = Db::name('sn_listsn')->where('uid', UID)->where('id', $id)->find();

            //用户传入什么事件
            switch (input('post.c_type')) {
                case 'wx':
                    return $this->wx($data_sn);
                    break;
                case 'id':
                    return $this->id2($data_sn);
                    break;
                case 'imeiorsn':
                    return $this->imeiorsn($data_sn);
                    break;
                default:
                    return json(["status" => false, "tishi" => "查询类型失败！"]);
                    break;
            }
        }
    }


    /**
     * 查询案例国别(文字)
     */
    public function case_api()
    {

        if ($this->request->isPost()) {

            // 获取到记录sn_or_imei
            $id = input('post.tiamo');
            $id = 43;
            if ($id == ""){
                return ['status'=>false,"msg"=>"ID 不为空"];
            }
            // 通过id获取序列号
            $chaxun = Db::name('chaxun_list')->where('uid', UID)->where('id', $id)->find();

            $sn_or_imei = $chaxun['sn'] ? $chaxun['sn']:$chaxun['imei'];
            $myCheck["service"] = 96;
            $myCheck["imei"] = $sn_or_imei;
            $myCheck["key"] = config("api.ifreeicloud.key");

            $rs = Core::post('https://api.ifreeicloud.co.uk',$myCheck);
//            $str = 'Model: IPHONE 6S NB30 64GB RGLD
//IMEI Number: 359156076262994
//MEID Number: 35915607626299
//Serial Number: FVMSG2L0GRYF
//MDM Status: OFF
//Replaced Device: NO
//Coverage Status: Out Of Warranty (No Coverage)
//Product Sold By: Apple Store
//Purchase Country: Canada
//Estimated Purchase Date: 11/03/15
//Sim-Lock: UNLOCKED
//Find My iPhone: ON
//iCloud Status: CLEAN';

//            $rs['data'] = new \StdClass();
//            $rs['data']->success = true;
//            $rs['data']->error = "";
//            $rs['data']->response = $str;
//            $httpcode = 200;

            $myResult = $rs['data'];
            $httpcode = $rs['status'];
            $output=[];
            if($httpcode != 200) {
                $output['status'] = false;
                $output['tishi'] = "状态码:".$httpcode;
                $update_data['case_status'] = 2;
//                echo "Error: HTTP Code $httpcode";
            } elseif($myResult->success !== true) {
                $output['status'] = false;
                $output['tishi'] = $myResult->error;
                $update_data['case_status'] = 2;
//                echo "Error: $myResult->error";
            } else {
//                echo $myResult->response;
                $output['status'] = true;
                $output['tishi'] = "success";

                // 取出json数据
                $json_arr = [];
                if($chaxun['json']!=""){
                    $json_arr = json_decode($chaxun['json'], true);
                }
                $json_arr['case_result'] = $myResult->response;
                // 案例状态 1表示完成 2表示失败 0表示待扫描
                $update_data['case_status'] = 1;
                $update_data['json'] = json_encode($json_arr);

            }
            // 将结果保存到数据库
            Db::name('chaxun_list')->where('uid', UID)->where('id', $id)->update($update_data);
            return $output;
        }
    }



    /**
     * 所有查询接口
     * 该接口只查询实时变化状态的服务项
     * @return mixed
     */
    public function all_in_api()
    {
        // 暂时硬编码 id锁查询 -> 2  保修查询 -> 1 id黑白名单 -> 3 id监控->4 网络锁->5 网络监控->6
        if ($this->request->isPost()) {
            // 获取到记录ID
            $id = input('post.tiamo');
            // 匹配该ID对应的需要实时查询的服务
            $chaxun_item = Db::name('chaxun_list')->where('uid',UID)->where('id', $id)->find();
            $sn = $chaxun_item['sn'];
            $imei1 = $chaxun_item['imei1'];
            $imei2 = $chaxun_item['imei2'];

//            $sn_or_imei =
            $chaxun_server_id_list = $chaxun_item["chaxun_server_code_list"];
            if ($chaxun_server_id_list != ""){
                $arr = explode(",",$chaxun_server_id_list);
                // 初始化数组
                $id_lock_status = [];
                foreach ($arr as $server_code){
                    $sn_or_imei = $sn?$sn:$imei1;
                    switch ($server_code) {
                        // 保修查询
                        case 'warranty':
                             return $this->advanced_wx($data_sn);
                            break;

                        case 'id_lock':
                            // id锁查询 -> 2
                            // 成本0
                            $id_lock_status = $this->testIdLock($sn_or_imei);
                            break;
                        case 'id_blacklist':
                            // 此处和id_lock同时查询
                            // http://www.iphonechecks.services/user/api 33 UU9999# IMEI/SN 都支持
                            // 成本 0.03
                            // id黑白名单与id激活锁可以同时查
//                            return $this->id_blacklist($chaxun_item);
                            break;
                        case '4':
                            //id监控->4
//                            return $this->id_monitor($chaxun_item);
                            break;
                        case 'net_lock':
                            //网络锁->5
                            $ifnetlock = $this->net_lock($sn_or_imei);
                            break;
                        case 'net_blacklist':
                            //网络锁->5
                            $net_blacklist = $this->net_blacklist($sn_or_imei);
                            break;
                        case '6':
                            //网络监控->6
                            $net_monitor = $this->net_monitor($sn_or_imei);
                            break;
                        case 'imei2sn':
                            $this->imei2sn($chaxun_item);
                            break;
                        default:
                            return json(["status" => false, "tishi" => "查询类型失败！"]);
                            break;

                    }
                }
//                var_dump($id_lock_status);exit;
                // 记录这一次查询时间
                $now_time = date('y/m/d H:i:s', time());
                // 取出json数据
                $json_arr = [];

                if($chaxun_item['json']!=""){
                    $json_arr = json_decode($chaxun_item['json'], true);
                }
                // 只提示发生状态变化的信息
                $rs_arr = ["status" => true, 'tishi'=>''];
                if (isset($id_lock_status["status"])){

                    if (!array_key_exists("if_id_lock",$json_arr) || $json_arr['if_id_lock'] !=$id_lock_status['tishi']['if_id_lock']){
                        $rs_arr["tishi"] .=$id_lock_status['tishi']['if_id_lock']==1?"有ID锁":"无ID锁";
                        $json_arr['if_id_lock'] = $id_lock_status['tishi']['if_id_lock'];
                        // 只有有ID锁才有黑白名单
                        if ($id_lock_status['tishi']['if_id_lock'] == 1){
//                            if ( !array_key_exists("id_blacklist",$json_arr) ||$json_arr['id_blacklist'] !=$id_lock_status['tishi']['id_blacklist']){
//                                $rs_arr["tishi"] .=$id_lock_status['tishi']['id_blacklist']==1?"黑名单":"白名单";
//                                $json_arr['id_blacklist'] = $id_lock_status['tishi']['id_blacklist'];
//                            }
                        }
                    }


                }

                if (isset($ifnetlock['status'])){
                    if (!array_key_exists("ifnetlock",$json_arr) || $json_arr['ifnetlock'] !=$ifnetlock['tishi']['ifnetlock']){
                        $rs_arr["tishi"] .=$ifnetlock['tishi']['ifnetlock']==1?"有网络锁":"无网络锁";
                        $json_arr['ifnetlock'] = $ifnetlock['tishi']['ifnetlock'];
                    }
                }
                if (!$rs_arr['tishi']){
                    $rs_arr['tishi'] = "暂无变化";
                }
                $json_arr['last_time'] = $now_time;

                // 将数据保存到数据库
                Db::name('chaxun_list')->where('id',$chaxun_item['id'])->update(['json' => json_encode($json_arr)]);

                return json($rs_arr);
                
            }
        }
    }

    //查维修状态  传入的是一个数组 查询列表  zjbsky   nbsn_listsn
    public static function wx($data_sn)
    {
         

        if (strstr($data_sn['wx_json'], 'FINL')) {
            //return json(["status" => false, "tishi" => "已完成"]);
        }
        if (isset($data_sn['wid'])) { //有G号的去查进度
            return self::jindu($data_sn);
        } else if (isset($data_sn['imei']) || isset($data_sn['sn'])) { //没有G号的去查询维修状态
            return self::ztai($data_sn);
        } else
            return json(["status" => false, "tishi" => "查询参数错误"]);
    }
    // 查询是否正在维修
    public static function advanced_wx($data_sn)
    {
        if (strstr($data_sn['wx_json'], 'FINL')) {
            //return json(["status" => false, "tishi" => "已完成"]);
        }
        if (isset($data_sn['wid'])) {
            return self::jindu($data_sn);
        } else if (isset($data_sn['imei']) || isset($data_sn['sn'])) {
            return self::ztai($data_sn);
        } else
            return json(["status" => false, "tishi" => "查询参数错误"]);
    }
    public static function ztai($data_sn)
    {

        if (!preg_match("#^[A-Za-z][A-Za-z0-9]{11}$#", $data_sn['sn']) && isset($data_sn['sn'])) {
            return json(["status" => false, "tishi" => "序列号格式错误"]);
        }
        if (!preg_match("#^(35|99|01)\d{12,13}$#", $data_sn['imei']) && isset($data_sn['imei'])) {
            return json(["status" => false, "tishi" => "IMEI格式错误"]);
        }
        
        //UID 1不限制 方便测试！
        if ((time() - $data_sn['wx_ctime'] < 300) && UID != 1) {
            return json(["status" => false, "tishi" => (300 - (time() - $data_sn['wx_ctime'])) . '秒后再查（状态查询限制5分钟查一次）']);
        }
        //需要在这里判断各种序列号格式 不查状态！！！
        return json(Ztai::go($data_sn));
    }

    //查询进度开始
    public static function jindu($data_sn)
    {
        if (empty($data_sn['sn']) || empty($data_sn['wid'])) {
            return json(["status" => false, "tishi" => "序列号和维修ID不能为空"]);
        }
        //UID 1不限制 方便测试！
        if ((time() - $data_sn['wx_ctime'] < 60) && UID != 1) {
            return json(["status" => false, "tishi" => (60 - (time() - $data_sn['wx_ctime'])) . '秒后再查（进度查询限制1分钟查一次）']);
        }

        //这里写个不复查的
        if (in_array($data_sn['wx_ztai_id'], [4, 424, 422, 543, 420, 418, 427])) { }

        $jindu = Jindu::go($data_sn);
        return json($jindu);
    }

    public function maintain($sn = 'GDGXM000JCLP'){
//        $ip = Core::getIpProxyByRandom();//获取代理IP
        $ip = Core::getIpProxyByLum();
        $url1 = "https://getsupport.apple.com/?locale=en_JP&sn=" . $sn . "&symptom_id=20369&category_id=SC0105";
        $headers1 = [
            'Referer' => "https://getsupport.apple.com/",//来源URL     可选项
            'UserAgent' => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36",//用户的浏览器类型，版本，操作系统     可选项有默认值
            'Accept' => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",//    可选项有默认值
            'ContentType' => "application/json; charset=UTF-8",//返回类型    可选项有默认值
        ];
        $rs1 = Core::get($url1,$headers1,[],$ip);
        echo $rs1['data'];
    }

    /**
     * 新的接口
     * @param $sn
     * @return array|bool
     */
    public function IdLock($sn){
//        $ip = Core::getIpProxyByRandom();
//        $ip = "";

        $ip = Core::getIpProxyByLum();

        //此处错误较多，所以要捕获错误，反馈程序，进行重新查询
        try{

        $url1 = "https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html";
        $headers1 = [
            'Referer' => "https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html",
            'UserAgent' => "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2970.0 Safari/537.36",//用户的浏览器类型，版本，操作系统     可选项有默认值
            'Accept' => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3",//    可选项有默认值
            'ContentType' => "application/json;charset=UTF-8",//返回类型    可选项有默认值
            'Referer' => "https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html",//来源URL     可选项
        ];

        $rs1 = Core::get($url1,$headers1,[],$ip);

        if($rs1['status'] == 403){//返回403表示代理ip被封，需要删除
            $arr = explode(':',$ip);
            Cache::store('redis')->rm($arr[0]);
            return ["net_code"=>"400","status" => false,  "tishi" => "失败"];
        }

        if($rs1['status'] == 200){
            if (empty($rs1['data'])) return false;
            if (preg_match('|token]" value="(.*)"|isUS', $rs1['data'], $text) == 1) {
                $token = $text[1];
            }
        }else{
            return ["net_code"=>"400","status" => false, "tishi" => "第一步失败状态码：".$rs1['status']];
        }
//        $token = "";
        //第二步
        $url2 = "https://www.recyclez-moi.fr/js/routing?callback=fos.Router.setData";
        $headers2 = [
            'Referer' => "https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html",
            'UserAgent' => "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2970.0 Safari/537.36",//用户的浏览器类型，版本，操作系统     可选项有默认值
            'Accept' => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3",//    可选项有默认值
            'ContentType' => "application/json;charset=UTF-8",//返回类型    可选项有默认值
            'Referer' => "https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html",//来源URL     可选项
            "Accept-Encoding"=>"gzip, deflate, br"
        ];
        $cookies2 = [
            "_ga"=>"GA1.2.325564589.1564044126", "_gid"=>"GA1.2.2033993407.1564044126",
            " displayCookieConsent"=>"y",
        ];

        $rs2 = Core::get($url2,$headers2,$cookies2,$ip);

        if($rs2['status'] == 403){//返回403表示代理ip被封，需要删除
            $arr = explode(':',$ip);
            Cache::store('redis')->rm($arr[0]);
            return ["net_code" => $rs2['net_code'],"status" => false, "tishi" => "失败"];
        }
        if($rs2['status']){
            if (empty($rs2['data'])) return false;
            if (preg_match('|token]" value="(.*)"|isUS', $rs2['data'], $text) == 1) {
                $token = $text[1];
            }

            preg_match("/set\-cookie:([^\r\n]*)/i", $rs2['header'], $matches);
            // 提取cookie值到数组
            $PHPSESSID = "";
            if (count( $matches)){
                $matches[1];
                $cc = explode(";",$matches[1]);
                $PHPSESSID = explode("=",$cc[0])[1];
            }

        }

        //第三步
        $url3 = "https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html";
        $headers3 = [
            'Referer' => "https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html",
            'UserAgent' => "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2970.0 Safari/537.36",//用户的浏览器类型，版本，操作系统     可选项有默认值
            'Accept' => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3",//    可选项有默认值
            'ContentType' => "application/x-www-form-urlencoded",//返回类型    可选项有默认值
            'Referer' => "https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html",//来源URL     可选项
            "Accept-Encoding"=>"gzip, deflate, br"
        ];


        $cookies3 = [
            "_ga"=>"GA1.2.325564589.1564044126", "_gid"=>"GA1.2.2033993407.1564044126",
            " displayCookieConsent"=>"y","PHPSESSID"=>$PHPSESSID,
        ];


        $data3 = [
            "cwd_catalogueReprise_form_valorisation[questions][0][matriceQuestionReponseSelected]"=>15,
            "cwd_catalogueReprise_form_valorisation[questions][1][matriceQuestionReponseSelected]"=>17,
            "cwd_catalogueReprise_form_valorisation[questions][2][matriceQuestionReponseSelected]"=>19,
            "cwd_catalogueReprise_form_valorisation[refUnique]"=> $sn ,
            "cwd_catalogueReprise_form_valorisation[valider]"=>"",
            "erreurGsx"=>"",
            "cwd_catalogueReprise_form_valorisation[produit]"=>59561,
            "cwd_catalogueReprise_form_valorisation[_token]"=> $token,
        ];


        $rs3 = Core::post($url3, $data3, $headers3, $cookies3,$ip);

        if($rs3['status'] == 403){//返回403表示代理ip被封，需要删除
            $arr = explode(':',$ip);
            Cache::store('redis')->rm($arr[0]);
            return ["net_code" => $rs3['net_code'],"status" => false, "tishi" => "失败"];
        }
        if (strpos($rs3['data'], "Le jeton CSRF est invalide. Veuillez renvoyer le formulaire")){//strpos($rs3['data'], "Le jeton CSRF est invalide. Veuillez renvoyer le formulaire")
            if (preg_match('|token]" value="(.*)"|isUS', $rs3['data'], $text) == 1) {
                $token = $text[1];
            }

            // 第四步
            $url4 = "https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html";
            $headers4 = [
                'Referer' => "https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html",
                'UserAgent' => "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2970.0 Safari/537.36",//用户的浏览器类型，版本，操作系统     可选项有默认值
                'Accept' => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3",//    可选项有默认值
                'ContentType' => "application/x-www-form-urlencoded",//返回类型    可选项有默认值
                'Referer' => "https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html",//来源URL     可选项
                "Accept-Encoding"=>"gzip, deflate, br",
                "Host" => "www.recyclez-moi.fr",
            ];
            $cookies4 = [
                "_ga"=>"GA1.2.325564589.1564044126", "_gid"=>"GA1.2.2033993407.1564044126",
                " displayCookieConsent"=>"y",
                "PHPSESSID"=>$PHPSESSID,//$PHPSESSID
            ];
            $data4 = [
                "cwd_catalogueReprise_form_valorisation[questions][0][matriceQuestionReponseSelected]"=>15,
                "cwd_catalogueReprise_form_valorisation[questions][1][matriceQuestionReponseSelected]"=>17,
                "cwd_catalogueReprise_form_valorisation[questions][2][matriceQuestionReponseSelected]"=>19,
                "cwd_catalogueReprise_form_valorisation[refUnique]"=> $sn ,
                "cwd_catalogueReprise_form_valorisation[valider]"=>"",
                "erreurGsx"=>"",
                "cwd_catalogueReprise_form_valorisation[produit]"=>59561,
                "cwd_catalogueReprise_form_valorisation[_token]"=> $token,//$token
            ];

            $rs4 = Core::post($url4, $data4, $headers4, $cookies4,$ip);
            if($rs4['status'] == 403){//返回403表示代理ip被封，需要删除
                $arr = explode(':',$ip);
                Cache::store('redis')->rm($arr[0]);
                return ["net_code" => $rs4['net_code'],"status" => false, "tishi" => "失败"];
            }

            // Location: /fr/commande-reprise/panier.html/5
            if (strpos($rs4['data'],"imei_champ_texte")){
                return ["net_code" => $rs4['status'],"status" => true, "tishi" => ["if_id_lock"=>'1']];
            }else if (strpos($rs4['header'],"Location: /fr/commande-reprise/panier.html/5")){
                return ["net_code" => 200,"status" => true, "tishi" => ["if_id_lock"=>'2']];
            }else{
                return ["net_code" => $rs4['status'],"status" => false, "tishi" => "失败"];// , 'token' => $token , 'PHPSESSID' => $PHPSESSID

            }
        }

        }catch (Exception $exception){
            return ["net_code" => 400,"status" => false, "tishi" => '意向不到的错误'];
        }

    }

    /**
     * 老的接口
     * @param $sn_or_imei
     * @return array|bool
     */
    public static function old_IdLock($sn_or_imei)
    {
        if (!empty($sn_or_imei)) $link = 'https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html';
        $token = Cache::store('redis')->get('recyclez_token');

        if ($token){
            $rs = Core::get($link);
            if($rs['status']){
                if (empty($rs['data'])) return false;
                if (preg_match('|token]" value="(.*)"|isUS', $rs['data'], $text) == 1) {
                    $token = $text[1];
                }

                if (empty($token)) return false;
                // token有效暂设6小时
                Cache::store('redis')->set('recyclez_token',$token,3600*6);
            }
        }

        $headers =array(
            'Host' => 'www.recyclez-moi.fr',
            'Connection' => 'keep-alive',
            'Cache-Control' => 'max-age=0',
            'Origin' => 'https://www.recyclez-moi.fr',
            'Upgrade-Insecure-Requests' => '1',
            'DNT' => '1',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
            'Referer' => 'https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html',
            'Accept-Language' => 'ru,en-US;q=0.9,en;q=0.8,uk;q=0.7',

        );
        $data = [
            'cwd_catalogueReprise_form_valorisation[refUnique]'=>$sn_or_imei ,
            'cwd_catalogueReprise_form_valorisation[_token]'=>$token
        ];
        $link2 = 'https://www.recyclez-moi.fr/fr/reprise/tablette/valorisation-apple_ipad_32gb_wifi-59561.html?'.$data;

        $rs = Core::post($link2, [], $headers,[]);

//
//        if (!empty($chaxun_item)){
//            $page = Core::post($link.'?cwd_catalogueReprise_form_valorisation[questions][0][matriceQuestionReponseSelected]=15&cwd_catalogueReprise_form_valorisation[questions][1][matriceQuestionReponseSelected]=17&cwd_catalogueReprise_form_valorisation[questions][2][matriceQuestionReponseSelected]=19&cwd_catalogueReprise_form_valorisation[refUnique]='.$chaxun_item['sn'].'&cwd_catalogueReprise_form_valorisation[valider]=&erreurGsx=&cwd_catalogueReprise_form_valorisation[produit]=59561&cwd_catalogueReprise_form_valorisation[_token]=' . $token,[],$headers);
//            dump($page);
//            var_dump($link.'?cwd_catalogueReprise_form_valorisation[questions][0][matriceQuestionReponseSelected]=15&cwd_catalogueReprise_form_valorisation[questions][1][matriceQuestionReponseSelected]=17&cwd_catalogueReprise_form_valorisation[questions][2][matriceQuestionReponseSelected]=19&cwd_catalogueReprise_form_valorisation[refUnique]='.$chaxun_item['sn'].'&cwd_catalogueReprise_form_valorisation[valider]=&erreurGsx=&cwd_catalogueReprise_form_valorisation[produit]=59561&cwd_catalogueReprise_form_valorisation[_token]=' . $token);
//            if($page == ""){
//                return ["status" => false, "tishi" => "查询失败"];
//            }
//        }
//
//        if (preg_match('|dans iCloud|', $page)) {
//            $json_arr = [];
//            // 取出json数据
//            if(isset($chaxun_item['json'])){
//                $json_arr = json_decode($chaxun_item['json'], true);
//            }
//            // 1表示有锁 2表示无锁
//            $json_arr['if_id_lock'] = 1;
//            // 将数据保存到数据库
//            $rs = Db::name('chaxun_list')->where('id',$chaxun_item['id'])->update(['json' => json_encode($json_arr)]);
//
//            return ["status" => true, "tishi" => ["if_id_lock"=>$json_arr['if_id_lock']]];
//        }
//

        // if (empty($page))return false;
        // if (!preg_match('|'.$this->input.'|', $page)) myexception('Unknown status');

        // if (preg_match('|dans iCloud|', $page)) $this->result->fmi = '<strong style="color: red">ON</strong>';
        // else $this->result->fmi = $this->result->fmi = '<strong style="color: green">OFF</strong>';

        return ["status" => true, "tishi" => ["if_id_lock"=>'2']];
    }
    // ID激活锁和黑白名单
    public function id_lock_and_blacklist($sn_or_imei)
    {
        $key = config("api.iphonechecks.key");
        $api_debug = config("api.api_debug");
        //为true改为假数据
        if($api_debug){
            $rs['status'] = 200;
            $rs['data'] = '{"status":"success","response":{"order_id":"658182346","reference_id":"658182346","imei":"C39XG6XBKPJ5","message":"Result passed","result":"IMEI/SN (串号/序列号) : C39XG6XBKPJ5
                            Model (型号) : iPhone XS Max 256GB Gold
                            iCloud (状态) : Clean (白名单)
                            Find My iPhone (激活锁) : ON (开启)
                            "}}';
        }else{
            $server_id = 33;
            $sn = $sn_or_imei;
            $url = sprintf("http://www.iphonechecks.services/api/place-order/?api_key=%s&service=%d&imei=%s",$key,$server_id,$sn);

            $rs = Core::get($url);
        }

//         dump($rs);exit;


        // 返回的字符串 不支持转成json格式
//        $json_rs = json_decode($rs,true);
//        var_dump($json_rs);
        // 判断结果
        // 不通过正则，直接使用strstr字符串搜索

        if (strstr($rs['data'], 'success')){
            $id_blacklist = 0; // 1表示黑名单 2表示白名单
            if (strstr($rs['data'], 'ON')){
                $if_id_lock = 1; // 1表示开着锁 2表示关闭锁
                if(strstr($rs['data'], 'Clean')){
                    $id_blacklist = 2;

                }else if(strstr($rs['data'], 'LOST')){
                    $id_blacklist = 1;

                }

            }else{
                $if_id_lock = 2;

            }

            //如果id锁为关闭，则默认id为白名单
            if($id_blacklist == 0 && strstr($rs['data'], 'iCloud (状态) : OFF(关闭)')) $id_blacklist = 2;

            return ['net_code' => $rs['status'],'status'=>true,"tishi"=>["if_id_lock"=>$if_id_lock,"id_blacklist"=>$id_blacklist]];
        }else{
            return ['net_code' => $rs['status'],'status'=>false,"tishi"=>"失败"];
        }
//        echo $rs;
    }

    /**
     * 网络锁黑名单（改用运营商检查）
     *   返回信息 有 IMEI imei 1 国家 网络锁
     * $sn_or_imei
     */
    public function network_lock_black($sn_or_imei){

        $api_debug = config("api.api_debug");
        //为true改为假数据
//        if($api_debug){
//            $rs['status'] = 200;
// //           $rs['data'] = '{"status":"success","response":{"order_id":"864411711","reference_id":"864411711","imei":"354846094527916","message":"Result passed","result":"Model (型号) : iPhone X 64GB Space Gray<br />IMEI (串号) : 354846094527916<br />Serial (序列号) : G6TX3C1GJCLF<br />Carrier (运营商) : US Sprint/MVNO Locked Policy<br />Country (国家) : US<br />SimLock (网络锁) : <span style="color:red;">Locked (有锁)</span><br />"}}';
//            $rs['data'] = '{"status":"success","response":{"order_id":"175945387","reference_id":"175945387","imei":"C39XG6XBKPJ5","message":"Result passed","result":"Model (型号) : iPhone XS Max 256GB Gold<br />IMEI (串号) : 357326097076531<br />IMEI2 (串号2) : 357326097233652<br />Serial (序列号) : C39XG6XBKPJ5<br />Carrier (运营商) : Unlocked (无锁)<br />SimLock (网络锁) : <span style=\"color:green;\">Unlocked (无锁)</span><br />"}}';
//        }else{

//        }
        //这个接口将更新SN - IMEI imei2  所以不能使用测试数据了
        $key = config("api.iphonechecks.key");
        $server_id = 39;//使用中文接口
        $sn = $sn_or_imei;

        $url = sprintf("http://www.iphonechecks.services/api/place-order/?api_key=%s&service=%d&imei=%s",$key,$server_id,$sn);

        $rs = Core::get($url);
        // 测试数据
//        $rs['status'] = 200;
//        $rs['data'] = '{"status":"error","response":{"order_id":"974112996","reference_id":"974112996","imei":"GDGXM000JCL","result":"Unknown"}}';
//        $rs['data'] = '{"status":"success","response":{"order_id":"864411711","reference_id":"864411711","imei":"354846094527916","message":"Result passed","result":"Model (型号) : iPhone X 64GB Space Gray<br />IMEI (串号) : 354846094527916<br />Serial (序列号) : G6TX3C1GJCLF<br />Carrier (运营商) : US Sprint/MVNO Locked Policy<br />Country (国家) : US<br />SimLock (网络锁) : <span style=\"color:red;\">Locked (有锁)</span><br />"}}';
        if(strstr($rs['data'], 'success')){
            $arr = json_decode($rs['data'],true);
            $str = $arr['response']['result'];
            return ['net_code' => $rs['status'],'status'=>true,"tishi"=>['str' => $str]];
        }else{
            return ['net_code' => $rs['status'],'status'=>false,"tishi"=>['str' => $rs['data']]];
        }

    }

    /**
     * 苹果：SN（序列号）转IMEI
     * @param $sn
     */
    public function sn2imei($sn){
        $api_debug = config("api.api_debug");
        //为true改为假数据
        if($api_debug){
            $rs['status'] = 200;
            $rs['data'] = '{"status":"success","response":{"order_id":"568452256","reference_id":"568452256","imei":"F17VKVVXJCLF","message":"Result passed","result":"Model (型号) : iPhone X<br>IMEI (串号) : 356724080893857<br>Serial Number (序列号) : F17VKVVXJCLF<br>Estimated Purchase Date (预计购买日期) : 2017-11-09"}}';
        }else{
            $key = config("api.iphonechecks.key");
            $server_id = 41;//使用中文接口
            $url = sprintf("http://www.iphonechecks.services/api/place-order/?api_key=%s&service=%d&imei=%s",$key,$server_id,$sn);
            $rs = Core::get($url);
        }

        $str = json_decode($rs['data'],true);
        if(strstr($rs['data'],'success')){
//            $str2 = $str['response']['result'];
//            $str3 = strstr($str2,'IMEI (串号)');//待处理de字符串
//            $num = stripos(strstr($str2,'IMEI (串号)'),'<br>');//先从IMEI (串号)处截取，然后再获取 <br>第一次出现的位置
//            $str4 = substr($str3,0,$num);//得到类似 IMEI (串号) : 3567*******3857 这样的一个字符串，下面继续处理字符串
//            $num2 = stripos($str4,': ') + 2;//获得需要从哪里开始截取的位置，并 + 上条件本身的位数
//            $str5 = substr($str4,$num2);//最终结果：  3567*******385
            return ['net_code' => $rs['status'] , 'status' => true , 'tishi' => $str];
        }else{
            return ['net_code' => $rs['status'] , 'status' => false , 'tishi' => $str];
        }

    }


    /**
     * 功能：传IMEI值得到序列号SN
     * @param $imei 传入一个IMEI值
     * 返回手机的序列号 查询不到返回‘’ 空字符串
     **/

    function imei2sn($imei){
        $url = "https://privateapi.bulkcheckers.com/imei2sn/index.php?username=longtengycg&imei=$imei";
        $opts = array(
            'http'=>array(
                'method'=>"GET",
                'timeout'=>14,//单位秒
            )
        );
        $rs = file_get_contents($url, false, stream_context_create($opts));
        //没有获取到结果
        if( strlen($rs)<=13) return '';
        //截取有用的字符 并去除空格
        $rs= trim(substr($rs,strpos($rs, 'Serial Number:')+15));
        //Again
        if(strstr($rs,'Again')){
            return 'Again';
        }
        //串码是否正确 的判断
        if(strpos($rs,"is not valid number") == true){
          return 'IMEI无效';
        }
        return $rs;
    }

    public static  function id_blacklist(){
        return json(["status" => false, "tishi" => "查询类型失败！"]);
    }

    public static function id_monitor(){
        return json(["status" => false, "tishi" => "查询类型失败！"]);
    }

    /**
     * 实例：IMEI (串号) : 357326097076531 SimLock (网络锁) : Unlocked (无锁)
     * @param $sn_or_imei
     * @return \think\response\Json
     */
    public static function net_lock($sn_or_imei,$type_api,$id = null,$type = 3){
//        $type = 3;//控制网络锁查询查哪个接口 1 https://imeicheck、 2 http://www.iphonechecks  type 参数网络锁可以不传其他接口为了走网络锁的这个接口必须传 3 免得以后网络锁换了，其他接口还可以用
        $api_debug = config("api.api_debug");
        //为true改为假数据
        if($api_debug){
    //        $rs['data'] = '{"status":"success","response":{"order_id":"434984579","reference_id":"434984579","imei":"C39XG6XBKPJ5","message":"Result passed","result":"IMEI (串号) : 357326097076531<br />SimLock (网络锁) : <span style=\"color:green;\">Unlocked (无锁)</span><br />"}}';
            $rs['status'] = 200;
            $rs['data'] = '{"status":"success","response":{"order_id":"658182346","reference_id":"658182346","imei":"C39XG6XBKPJ5","message":"Result passed","result":"IMEI/SN (串号/序列号) : C39XG6XBKPJ5
Model (型号) : iPhone XS Max 256GB Gold
iCloud (状态) : Clean (白名单)
Find My iPhone (激活锁) : ON (开启)
"}}';
        }else{

            if($type == 1){
                $server_id = '6';
                $key = 'ay4bwclnpg';
                $url = sprintf("https://imeicheck.info/user/api/getdata?IMEI=%s&ACCESS_KEY=%s&SERVICE_ID=%s",$sn_or_imei,$key,$server_id);
                $rs = Core::get($url);

                if($rs['status'] == 200){//成功
                    $tishi = true;
                    $ifnetlock = 0; // 1表示有网络锁 2表示无网络锁
                    if(strstr($rs['data'],'Wrong')){
                        $tishi = false;
                        $ifnetlock = $rs['data'];
                        return ['net_code' => $rs['status'],"status" => $tishi, "tishi" => ["ifnetlock"=>$ifnetlock]];
                    }
                    if (strstr($rs['data'], 'Unlocked')){
                        $ifnetlock = 2;
                    }else if(strstr($rs['data'], 'Locked')){
                        $ifnetlock = 1;
                    }
                }else{
                    $tishi = false;
                    $ifnetlock = $rs['data'];
                }
                return ['net_code' => $rs['status'],"status" => $tishi, "tishi" => ["ifnetlock"=>$ifnetlock]];
            }elseif($type == 3){//i-imei
                $api = new \DhruFusion();
                if($type_api == 1){//imei接口
                    $para['IMEI'] = $sn_or_imei;
                    $para['ID'] = "41"; // got from 'imeiservicelist' [SERVICEID]  $para['ID'] : 2300
                }else if($type_api == 2){//sn接口
                    $para['IMEI'] = $sn_or_imei;
                    $para['ID'] = "509"; // got from 'imeiservicelist' [SERVICEID]  $para['ID'] : 2300
                }
                $request = $api->action('placeimeiorder', $para);//订单下一次需要1$（7RMB）,暂时先注释了
                $flag = array_key_exists('SUCCESS', $request);//判断是否成功
                if($flag){//下单成功
                    Db::name('cx_server_info')->where('id',$id)->update(['three_order_id' => $request['SUCCESS'][0]['REFERENCEID']]);
                    return ['net_code' => 200,'status' => true, 'tishi' => '下单成功'];
                }else{//下单失败
                    return ['net_code' => 200,'status' => false, 'tishi' => $request];
                }
            }elseif($type == 2){
                $server_id = 40;
                $sn = $sn_or_imei;
                $key = config("api.iphonechecks.key");
                $url = sprintf("http://www.iphonechecks.services/api/place-order/?api_key=%s&service=%d&imei=%s",$key,$server_id,$sn);
                $rs = Core::get($url);
            }
        }

        if (strstr($rs['data'], 'success')){
            $tishi = true;
            $ifnetlock = 0; // 1表示有网络锁 2表示无网络锁
            if (strstr($rs['data'], 'Unlocked')){
                $ifnetlock = 2;

            }else if(strstr($rs['data'], 'Locked')){
                $ifnetlock = 1;
            }
        }else{
            $tishi = false;
            $ifnetlock = '失败';
        }
        return ['net_code' => $rs['status'],"status" => $tishi, "tishi" => ["ifnetlock"=>$ifnetlock]];
    }

    /**
     * 国别下单
     */
    public function country_order($sn,$imei,$id){
//        define("REQUESTFORMAT", "JSON"); // we recommend json format (More information http://php.net/manual/en/book.json.php)
//        define('DHRUFUSION_URL', "http://i-imei.com/");
//        define("USERNAME", "longtengycg");
//        define("API_ACCESS_KEY", "UN1-26C-UGF-PIO-R7I-NVL-4FA-F9A");

        $api_debug = config("api.api_debug");
        //为true改为假数据
        if($api_debug){
            $request = array(
                'ID' => 2300,
                'IMEI' => 'C39XG6XBKPJ5',
                'SUCCESS' => array(
                    0 => array(
                        'MESSAGE' => 'Order received',
                        'REFERENCEID' => 13320447,//13509819   13320447
                        )),
                'apiversion' => '2.3.1');
        }else{
            $api = new \DhruFusion();
            if( strlen($sn) > 5){//sn
                $para['IMEI'] = $sn;
                $para['ID'] = "2300"; // got from 'imeiservicelist' [SERVICEID]  $para['ID'] : 2300
            }else if(  strlen($imei) > 5  ){//imei
                $para['IMEI'] = $imei;
                $para['ID'] = "2597"; // got from 'imeiservicelist' $para['ID'] : 2597
            }


            $request = $api->action('placeimeiorder', $para);//订单下一次需要1$（7RMB）,暂时先注释了
        }

        $flag = array_key_exists('SUCCESS', $request);//判断是否成功
        if($flag){//下单成功
            Db::name('cx_server_info')->where('id',$id)->update(['three_order_id' => $request['SUCCESS'][0]['REFERENCEID']]);
            return ['net_code' => 200,'status' => true, 'tishi' => '下单成功'];
//            $res = $this->country_query($request['SUCCESS'][0]['REFERENCEID']);
//            return $res;
        }else{//下单失败
            return ['net_code' => 200,'status' => false, 'tishi' => $request];
        }
    }

    /**
     * 国别订单详情查询
     *
     * @param $id  国别下单返回的id
     */
    public function country_query($id){
        $api = new \DhruFusion();
        $para['ID'] = $id; // got REFERENCEID from placeimeiorder
        $request = $api->action('getimeiorder', $para);
        if($request['SUCCESS'][0]['STATUS'] == 0){//第三方返回等待
            return ['net_code' => -999];
        }
        $flag = array_key_exists('SUCCESS', $request);//判断是否成功
        if($flag){//查询成功

            return ['net_code' => 200,'status' => true, 'tishi' => $request['SUCCESS'][0]['CODE']];
        }else{//查询失败
            //下单要钱，查询不要钱，所以查询失败还是要扣钱，需要重新查询
            return ['net_code' => 200,'status' => false, 'tishi' => $id];
        }
    }

    /**
     * GSX查询更换/案例历史  下单
     *
     * @param $sn
     */
    public function GSX_order_CaseHistory($sn,$id){
        $api = new \DhruFusion();
        $para['IMEI'] = $sn;
        $para['ID'] = "347"; // got from 'imeiservicelist' [SERVICEID]  测试使用的sn：C39XG6XBKPJ5

        //TODO 正式使用时需要开启以下代码
        $request = $api->action('placeimeiorder', $para);//订单下一次需要1$（7RMB）,暂时先注释了
//        $request = array(
//            "ID" => "347",
//            "IMEI" => "C39XG6XBKPJ5",
//            "SUCCESS" => array(
//                0 => array(
//                    "MESSAGE" => "Order received",
//                    "REFERENCEID" => "13369401",//13369401   好像不能用13330749
//                    "apiversion" => "2.3.1",
//                    )
//                )
//            );

        $flag = array_key_exists('SUCCESS', $request);//判断是否成功
        if($flag){//下单成功
            Db::name('cx_server_info')->where('id',$id)->update(['three_order_id' => $request['SUCCESS'][0]['REFERENCEID']]);
            return ['net_code' => 200,'status' => true, 'tishi' => '下单成功'];
//            $res = $this->GSX_query($request['SUCCESS'][0]['REFERENCEID']);
//            return $res;
        }else{//下单失败
            return ['net_code' => 200,'status' => false, 'tishi' => $request];
        }

    }

    /**
     * GSX查询
     */
    public function GSX_query($id){
        //TODO 后续还需要对返回的数据做处理
        $api = new \DhruFusion();
        $para['ID'] = $id; // got REFERENCEID from placeimeiorder
        $request = $api->action('getimeiorder', $para);
        if($request['SUCCESS'][0]['STATUS'] == 0){//第三方返回等待
            return ['net_code' => -999];
        }
        $flag = array_key_exists('SUCCESS', $request);//判断是否成功
        if($flag){//查询成功
            return ['net_code' => 200,'status' => true, 'tishi' => $request['SUCCESS'][0]['CODE']];
        }else{//查询失败
            return ['net_code' => 200,'status' => false, 'tishi' => $request];//下单要钱，查询不要钱，所以查询失败还是要扣钱，需要重新查询
        }
    }

    /**
     * MDM查询
     *
     * @param $sn_or_imei  只允许使用IMEI查询
     */
    public function MDM_query($imei){
//        $url = sprintf("http://www.iphonechecks.services/api/place-order/?api_key=%s&service=%d&imei=%s",$key,$server_id,$sn);  357326097076531

        $api_debug = config("api.api_debug");
        //为true改为假数据
        if($api_debug){
            $rs = array(
            "status" => 200,
            "data" => "IMEI: 357326097076531<br>SN: C39XG6XBKPJ5<br>MDM: <strong><span style='color:green;'>OFF</span></strong>"
            );
        }else{
            $server_id = '64';
            $key = 'ay4bwclnpg';
            $url = sprintf("https://imeicheck.info/user/api/getdata?IMEI=%s&ACCESS_KEY=%s&SERVICE_ID=%s",$imei,$key,$server_id);
            $rs = Core::get($url);
        }



        $str = strip_tags(str_replace("<br>",',',$rs['data']));
        if($rs['status'] == 200){//连接成功并查询成功
            if(strstr($str,'OFF')){
                $mdm = 2;//关闭
            }else{
                $mdm = 1;//开锁
            }
            return ['net_code' => $rs['status'],'status' => true,'tishi' => ['data' => $rs['data'] , 'mdm' => $mdm]];
        }else{//连接失败
            return ['net_code' => $rs['status'],'status' => false,'tishi' => '失败'];
        }

    }



    /**
     * 售出+更换+案例历史检查（图片结果） 下单
     */
    public function picture_result($sn,$imei,$id){
        $config = array(
            'username' => 'longtengycg',
            'key'      => 'LBO-XPV-HC6-PFI-UQC-HKO-HDN-C9P',
            'url'      => 'https://www.unlock-server.net/dhru',
        );
        $api = new \DhruFusion();
        if($sn != ''){
            $para['ID'] = "333"; // 这个ID是查sn得
            $para['IMEI'] = $sn;
        }elseif($imei != ''){
            $para['ID'] = "374"; // 这个ID是查imei得
            $para['IMEI'] = $imei;
        }

        $request = $api->general_action('placeimeiorder', $para,$config);//订单下一次需要1$（7RMB）,暂时先注释了
//        dump($request);exit;
        $flag = array_key_exists('SUCCESS', $request);//判断是否成功
        if($flag){//下单成功
            Db::name('cx_server_info')->where('id',$id)->update(['three_order_id' => $request['SUCCESS'][0]['REFERENCEID']]);
            return ['net_code' => 200,'status' => true, 'tishi' => '下单成功'];
//            $res = $this->get_picture_result($request['SUCCESS'][0]['REFERENCEID']);
//            return $res;
        }else{//下单失败
            return ['net_code' => 200,'status' => false, 'tishi' => $request];
        }
    }

    /**
     * 获取案例 图片结果
     */
    public function get_picture_result($id){
        $config = array(
            'username' => 'longtengycg',
            'key'      => 'LBO-XPV-HC6-PFI-UQC-HKO-HDN-C9P',
            'url'      => 'https://www.unlock-server.net/dhru',
        );
        //TODO 后续还需要对返回的数据做处理
        $api = new \DhruFusion();
        $para['ID'] = $id; // got REFERENCEID from placeimeiorder
        $request = $api->general_action('getimeiorder', $para,$config);
        if($request['SUCCESS'][0]['STATUS'] == 0){//第三方返回等待
            return ['net_code' => -999];
        }
        $flag = array_key_exists('SUCCESS', $request);//判断是否成功
        if($flag){//查询成功
            return ['net_code' => 200,'status' => true, 'tishi' => $request['SUCCESS'][0]['CODE']];
        }else{//查询失败
            return ['net_code' => 200,'status' => true, 'tishi' => '查询失败'];//下单要钱，查询不要钱，所以查询失败还是要扣钱，需要重新查询
        }
    }


    /**
     * 139＃已售出+案例+替换SN 1-24小时
     * 测试http://appleunlockserver.com接口能否跑通
     */
    public function sold_case_replacementby_sn($imei = '123456'){
        $config = array(
            'username' => 'longtengycg',
            'key'      => 'CZ9-Q4L-RO2-5AR-XX5-XKU-0XX-ETR',
            'url'      => 'http://appleunlockserver.com/api/index.php',
        );
        $api = new \DhruFusion();
        $para['IMEI'] = $imei;
        $para['ID'] = "139"; // got from 'imeiservicelist' [SERVICEID]  测试使用的sn：C39XG6XBKPJ5

        $request = $api->general_action('placeimeiorder', $para,$config);
        dump($request);exit;

    }

    /**
     * 测试https://iunlock.codes接口能否跑通
    */
    public function iunlock_codes($imei = '123456'){
        $config = array(
            'username' => 'longtengycg',
            'key'      => 'N1F-1TW-29M-2MZ-CH1-UTL-US-QTL',
            'url'      => 'https://iunlock.codes/api/index.php',
        );
        $api = new \DhruFusion();
        $para['IMEI'] = $imei;
        $para['ID'] = "#2023"; // got from 'imeiservicelist' [SERVICEID]  测试使用的sn：C39XG6XBKPJ5

        $request = $api->general_action('placeimeiorder', $para,$config);
        dump($request);exit;
    }


    public static function net_monitor(){
        return json(["status" => false, "tishi" => "查询类型失败！"]);
    }

    public static function net_blacklist(){
        return json(["status" => false, "tishi" => "查询类型失败！"]);
    }

    public  function test(){
        $ipdata  = Db::name('ip_zdaye')->where('ztai',1)->orderRaw('rand()')->find();
        var_dump($ipdata);
    }

    /**
     * i-imei 通用接口下单测试
     */
    public function i_imei_test_order($sn,$type){
        $api = new \DhruFusion();
        if($type == 1){//imei接口
            $para['IMEI'] = $sn;
            $para['ID'] = "41"; // got from 'imeiservicelist' [SERVICEID]  $para['ID'] : 2300
        }else if($type == 2){//sn接口
            $para['IMEI'] = $sn;
            $para['ID'] = "509"; // got from 'imeiservicelist' [SERVICEID]  $para['ID'] : 2300
        }

        $request = $api->action('placeimeiorder', $para);//订单下一次需要1$（7RMB）,暂时先注释了
        dump($request);exit;
    }

    /**
     * i-imei 通用接口返回测试
     */
    public function i_imei_test_api($id){
        $api = new \DhruFusion();
        $para['ID'] = $id; // got REFERENCEID from placeimeiorder
        $request = $api->action('getimeiorder', $para);
        dump($request);exit;
    }




}
