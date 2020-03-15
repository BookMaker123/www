<?php
namespace app\common;
use think\Db;
use think\facade\Cache;

/**
 * CURl
 */
class Nb
{
    //分类查询
    public static function Chawx($data_sn)
    {
        //查询维修状态

        if ($data_sn['wid'] && $data_sn['sn']) {
            if ((time() - $data_sn['wx_ctime'] < 300)) {
                $warr['wx_tishi'] = (300 - (time() - $data_sn['wx_ctime'])) . '秒后再查（状态限制5分钟查一次）';
                return $warr;
            }
            //查询进度
            //如果是多个维修ID 批量查询直接查第一个
            $wid_arr = explode(" ", $data_sn['wid']);
            if (count($wid_arr) > 1) {
                $data_sn['wid'] = $wid_arr[0];
            }
            return self::Chawxjindu($data_sn);
        } elseif ($data_sn['imei'] || $data_sn['sn']) {
                     //  $warr['wx_tishi'] = '状态查询维护中,可使用监控查询';
          //  return $warr;
            if(isset($data_sn["sn"])&&substr($data_sn["sn"], -4)=="H8TT"){
                return ['wx_tishi'=>'不支持该设备查询状态'];
            }
   
            if ((time() - $data_sn['wx_ctime'] < 600)) {
                $warr['wx_tishi'] = 600 - (time() - $data_sn['wx_ctime']) . '秒后再查(状态限制10分钟查一次)';
                return $warr;
            }
            return self::ChaZtai($data_sn);
          //  $warr['wx_tishi'] = '状态查询维护中,可使用监控查询';
          //  return $warr;
        } else {
            $warr['wx_tishi'] = '参数错误';
            return $warr;
        }
    }
    /**  统计次数 
     * 每个用户独立每天统计！
     * ztaiok_num ztaierror_num jinduok_num jinduerror_num*/
    public static function Tongji($lei)
    {
        //
        $data['cri'] = date("Y-m-d");
        $data['uid']=UID;
        $data['update_time']=time();
        if (!Db::name('sn_tongji')->where('uid', UID)->where('cri', $data['cri'])->find()) {
            Db::name('sn_tongji')->insert($data);            
        }
        //哪里保存 保存哪里 哈哈哈
        Db::name('sn_tongji')->where('uid', UID)->where('cri', $data['cri'])->setInc($lei);
    }



    /**  查状态->没开发 */
    public static function ChaZtaitaotai($arr)
    {        

        $arr["url"] = 'http://47.254.83.150/c/public/?sn=' . $arr['sn'];
        $arr['time'] = 15; //超时时间
        $fanhui = self::Curl($arr);
        $json = json_decode($fanhui, 1);
        if ($json) {
            if (@$json['code'] == 0) {
                self::Tongji('ztai_num_ok');
                $json["ctime"] = time();
                return $json;
            } else {
                self::Tongji('ztai_num_error');
                return $json;
            }

        } else {
            $warr['wx_tishi'] = '查询失败,稍后重试0！';
            self::Tongji('ztai_num_error');
            return $warr;
        }
    }
    /**  状态查询 */
    /* 0没查询 1正常可用 2正常ip但是不可用 3 被封 4不可用   */
    public static function ChaZtai($arr)
    {   
        if(isset($arr["imei"])){
            $arr["sn"]=$arr["imei"];
        }
        if(empty($arr["imei"]) && empty($arr["sn"]))return ['code' => 4, 'wx_tishi' => 'IMEI或序列号不能为空！'];  

        $arr['iparr']= self::Getip();    
        $arr['t1'] = microtime(true); //计算时间开始
        if (preg_match("#^(35|99|01)\d{12,13}$#", $arr["sn"]) || preg_match("#^[A-Za-z][A-Za-z0-9]{10,11}$#", $arr["sn"])) {
        } else {
            return json_encode(['code' => 4, 'wx_tishi' => 'IMEI/SN格式错误!']);
        }
        $arr['time']=15;
        $arr['daili'] = 'http://zproxy.lum-superproxy.io:22225';
        $arr['dailimima'] = "lum-customer-hl_d7719b11-zone-static-ip-" . $arr['iparr']['ip'] . ":s6e05p94uqwq"; //指定美国随机
        $arr["url"] = "https://getsupport.apple.com/?locale=en_JP&sn=".$arr["sn"]."&symptom_id=20369&category_id=SC0105"; //en_US zh_CN
        $arr['header'] = array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            "Content-Type: application/json; charset=UTF-8",
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
            'Referer: https://getsupport.apple.com/',
        );

        $arr["toubu"] = 1;
        $fanhui = Nb::Curl($arr);
        if (strstr($fanhui, '403 Forbidden')) {
            //ip正确被封
            Db::name('ip_us')->inc('error')->update(['id' => $arr['iparr']['id'], 'ztai' => 3, 'time' => time()]);
            self::Tongji('ztaierror_num');
            return ['code' => 4, 'wx_tishi' => '查询失败（10003），请稍后重试！'];
        }elseif (strstr($fanhui, 'CASService')&&strstr($fanhui, $arr["sn"])) {
            //成功获取
            preg_match_all("#Set\-Cookie:(.*?);#is", $fanhui, $mac);
            $cookie = '';
            foreach ($mac[1] as $v) {
                $cookie .= "$v;";
            }
            $arr['cookie'] = $cookie;
            return self::ChaZtai2($arr);
        }else{
            self::Tongji('ztaierror_num');
             //暂时不保存ztai=错误
             Db::name('ip_us')->inc('error')->update(['id' => $arr['iparr']['id'], 'time' => time()]);
            return ['code' => 4, 'wx_tishi' => '查询失败（10001），请稍后重试！'];
        }

    }    
    /**  状态查询2 */
    public static function ChaZtai2($arr)
    {   
        $warr['wx_ctime'] = time();
        $arr["url"] = "https://getsupport.apple.com/web/v1/solutions"; //en_US zh_CN
        $arr["post"] = "{\"serialNumber\":\"" . $arr["sn"] . "\"}";
        $fanhui = Nb::Curl($arr);
        if (strstr($fanhui, '"solutionType":"ORC')) {
            if (strstr($fanhui, 'OVR_ORC_001')) { //替换
                //$warr['code'] = 0;
                $warr['wx_ztai_id'] = '-1';
                $warr['wx_tishi'] = '替换';
            } elseif (strstr($fanhui, 'OVR_ORC_003')) { //维修中
                //$warr['code'] = 0;
                $warr['wx_ztai_id'] = '-4';
                $warr['wx_tishi'] = '已经下单';
            } elseif (strstr($fanhui, 'OVR_ORC_004')) { //维修过
                //$warr['code'] = 0;
                $warr['wx_ztai_id'] = '-5';
                $warr['wx_tishi'] = '维修过';

            } elseif (strstr($fanhui, 'OVR_ORC_005')) { //禁止下单
                //$warr['code'] = 0;
                $warr['wx_ztai_id'] = '-5';
                $warr['wx_tishi'] = '禁止下单';

            } elseif (strstr($fanhui, 'OVR_ORC_010')) { //只修不换
                //$warr['code'] = 0;
                $warr['wx_ztai_id'] = '-5';
                $warr['wx_tishi'] = '未下单01';

            } elseif (strstr($fanhui, 'OVR_ORC_0')) { //未知下单
                //$warr['code'] = 0;
                $warr['wx_ztai_id'] = '-5';
                $warr['wx_tishi'] = '未下单02';
            } else {
                //未下单
                //$warr['code'] = 0;
                $warr['wx_ztai_id'] = '-5';
                $warr['wx_tishi'] = '未下单'; //未下单
            }
            Db::name('sn_listsn')->where('id', $arr['id'])->update($warr);//保存
            self::Tongji('ztaiok_num');            
            //跟新ip
            Db::name('ip_us')->inc('ok')->update(['id' => $arr['iparr']['id'], 'time' => time(),'miao'=>round(microtime(true) - $arr['t1'], 1)]);
            return $warr;
        }elseif (strstr($fanhui, '403 Forbidden')) {
            //ip正确被封
            Db::name('ip_us')->inc('error')->update(['id' => $arr['iparr']['id'], 'ztai' => 3, 'time' => time()]);
            self::Tongji('ztaierror_num');
            return ['code' => 4, 'wx_tishi' => '查询失败（10003），请稍后重试！'];
        } else {
            self::Tongji('ztaierror_num');
            //暂时不保存ztai=错误
            Db::name('ip_us')->inc('error')->update(['id' => $arr['iparr']['id'], 'time' => time()]);
            return ['code' => 4, 'wx_tishi' => '查询失败（10004），请稍后重试！'];
        }
 
    }


    /**  IP获取 返回数组 */
    public static function Getip()
    {      
 
        if(empty(Cache('usiplist'))){
            $ipdata = Db::name('ip_us')->where('ztai', 1)->column('ip,ztai', 'id');
            Cache('usiplist',$ipdata); //获取URLAPI数组
        }


        $linshiiparr = Cache('usiplist');
        $ip = array_pop($linshiiparr);
        $ip['count']=count($linshiiparr);
        Cache('usiplist', $linshiiparr); //保存一个新的储存ip
        return $ip;
    }



    /**  查询进度 */
    public static function Chawxjindu($arr,$ci=0)
    {

        //设置循环网址
        if (@!Cache::get('chaxun_url')) {
            $curl_arr = Cache('wxapi'); //获取URLAPI数组
            Cache::set('chaxun_url', $curl_arr); //保存
        } else {
            $curl_arr = Cache::get('chaxun_url');
        }
        //修改到这里 正在重写！！

        $chaxurl = array_pop($curl_arr); //获取数组的其中一个并删除。
 
 
        $chaxurl = str_replace('#WXID', $arr['wid'], $chaxurl);
        $chaxurl = str_replace('#WXSN', $arr['sn'], $chaxurl);

        Cache::set('chaxun_url', $curl_arr); // //重新保存
        //如果网址是html的就清空下header
        $arr["url"] = $chaxurl;
        $arr['time'] = 30; //超时时间
        //这样就不会出现英文
        $arr['header'] = array(
            "Accept-Language: zh-CN",
            );
     
        $fanhui = self::Curl($arr);

        $warr['wx_ctime'] = time();
        //开始判断
        if (strstr($fanhui, '500.RP_INVALID_REPAIRID')) {
            $warr['wx_tishi'] = "维修ID错误";
            $warr['wx_ztai_id'] = '4';
            self::Tongji('jinduok_num');
            Db::name('sn_listsn')->where('id', $arr['id'])->update($warr);//保存
            return $warr;
        } elseif (strstr($fanhui, '500.RP_INVALID_REPAIRORSERIAL') || strstr($fanhui, 'repairws.repairdetails.error.200.ER00')) {
            $warr['wx_tishi'] = "序列号或维修ID错误";
            $warr['wx_ztai_id'] = '4';
            self::Tongji('jinduok_num');
            Db::name('sn_listsn')->where('id', $arr['id'])->update($warr);//保存
            return $warr;
        } elseif (strstr($fanhui, '503 Service Temporarily Unavailabl')) {
            self::Tongji('jinduerror_num');
            $warr['wx_tishi'] = '查询繁忙，请稍后重试！';
            return $warr;
        } elseif (strstr($fanhui, 'repairws.repairdetails.error.500.ER001')) {
            self::Tongji('jinduerror_num');
            if ($ci == 0) {                
                  return self::Chawxjindu($arr);//重试一次
                  $warr['wx_tishi'] = '查询超时，请稍后重试！' . $ci;
                  return $warr;
            } else {
                $warr['wx_tishi'] = '查询超时，请稍后重试！' . $ci;
                return $warr;
            }
            $warr['wx_tishi'] = '查询超时，请稍后重试！' . $ci;
            return $warr;

        } elseif (strstr($fanhui, 'repairws.repairdetails.error.500.ER004')) {
            self::Tongji('jinduerror_num');
            $warr['wx_tishi'] = '查询繁忙，请稍后重试3！';
            return $warr;
        } elseif (strstr($fanhui, 'repairMetaData')) {
            /**
             * 维修ID正确 查询正确
             */
            $arra = json_decode($fanhui, 1);
            $warr['wx_json'] = json_encode($arra['data']); //所有保存结果json
            //$warr['posturl'] = $chaxurl;
            /** 报价获取价钱 str */
            $zhifuqian='';            
            if (@isset($arra['data']['repairMetaData']['actionUrl'])) {
                $zhifuqian=self::Baojia($arra['data']['repairMetaData']['actionUrl']);
            }
            /** 报价获取价钱 end */

            $warr['wx_ztai_id'] = $arra['data']['repairMetaData']['sapStatusId']; //当前状态ID
            $warr['tx_jindu'] = 0; //是否提醒 手动查询不提醒
            
            /** 当前listsn 变动 */
            if ($arr['wx_ztai_id'] != $warr['wx_ztai_id']) {

            }
            /** jindu列表 变动 不重复 */                  
            if(!Db::name('sn_jindu')->where('wid',$arr['wid'])->where('wx_ztai_id',$warr['wx_ztai_id'])->find()){               
                $warr_jindu['uid'] = UID;
                $warr_jindu['sn'] = $arr['sn'];
                $warr_jindu['sn_hou'] = substr($arr['sn'], '-4');
                $warr_jindu['wid'] = $arr['wid'];              
                $warr_jindu['wx_ztai_id'] = $arra['data']['repairMetaData']['sapStatusId']; //当前状态ID
                if(isset($arra['data']['repairMetaData']['modifiedDate'])){
                    $warr_jindu['wx_time'] = substr($arra['data']['repairMetaData']['modifiedDate'], 0, -3); //当前进度时间
                    $warr_jindu['wx_jindutime'] = date("Ymd", $warr_jindu['wx_time'] );
                }          
                Db::name('sn_jindu')->insert($warr_jindu); //保存到jindu进度库
                $warr['tx_jindu'] = 1;
            }

            @$warr['wx_time'] =isset($arra['data']['repairMetaData']['modifiedDate']) ?substr($arra['data']['repairMetaData']['modifiedDate'], 0, -3):''; //当前进度时间

            if (isset($arra['data']['repairMetaData']['repairStatusDesc']) ) {
                $warr['wx_tishi'] = $arra['data']['repairMetaData']['repairStatusDesc'] . $zhifuqian;
            }
            //当前维修状态描述
            if ($warr['wx_ztai_id'] == '997') {
                $warr['wx_tishi'] = "升级工程，搁置中...";
            }

            if ($warr['wx_ztai_id'] == '0') {
                $warr['wx_ztai_id'] = '1';
                $warr['wx_tishi'] = "查询不到状态";
            }

            //$warr['sn'] = $arra['data']['repairMetaData']['product']['serialNumber']; //序列号
            if (isset($arra['data']['repairMetaData']['trackingInfo']['trackingNum'])) {
                //物流号
                $warr['wx_wuliu'] = @$arra['data']['repairMetaData']['trackingInfo']['trackingNum'];
            }
            //如果进度有改变
            if (@$warr['tx_jindu'] == 1) {
                // $warr['wx_tishi'].='<i class="fa fa-star text-warning" data-toggle="tooltip" title="进度改变"></i>';
            }
            //添加查询次数
            self::Tongji('jinduok_num');
            Db::name('sn_listsn')->where('id', $arr['id'])->update($warr);//保存
            return $warr;
        } else {
            //查询失败
            self::Tongji('jinduerror_num');
            $warr['wx_tishi'] = '查询失败,稍后重试9！';
            return $warr;
        }
    }
    /**  报价分析函数 */
    public static function Baojia($baojiaurl)
    {
        $zhifuqian = '';
        $baojiaarr['header'] = array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            "Accept-Language: zh-CN",
            "Connection: keep-alive",
            "Content-Type: application/json",
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.80 Safari/537.36',
            'Pragma:no-cache',
        );
        $baojiaarr['url'] = $baojiaurl;
        $fanhuibaojia = self::Curl($baojiaarr);
        if (strstr($fanhuibaojia, 'Pay for the repair')) {
            preg_match('#Pay for the repa.*?<b.*?>(.*?)</h3>#s', $fanhuibaojia, $arrzhifu);
            //普保？
            if ($arrzhifu) {
                $zhifuqian = $arrzhifu[1];
                $zhifuqian = str_replace(["\n", "\t"], "", $zhifuqian);
            }
        }
        //延保
        if (strstr($fanhuibaojia, '<br />')) {
            preg_match('#<br />(.*?)</h3>#is', $fanhuibaojia, $arrzhifu);
            if ($arrzhifu) {
                $zhifuqian = $arrzhifu[1];
                $zhifuqian = str_replace(["\n", "\t"], "", $zhifuqian);
                $zhifuqian = str_replace(["tax"], "税", $zhifuqian);

                $timejiaohuanjia = null;
                if (@$arra['data']["repairMetaData"]['requoteExpiryDate']) {
                    $timejiaohuanjia = date('Y-m-d H', substr($arra['data']["repairMetaData"]['requoteExpiryDate'], 0, -3)); //时间转换
                    $zhifuqian .= $timejiaohuanjia . '点前支付';
                }
            }

        }
        return $zhifuqian;
    }
    public static function wuliu($value)
    {
        /**
         * 0：在途，即货物处于运输过程中；
         * 1：揽件，货物已由快递公司揽收并且产生了第一条跟踪信息；
         * 2：疑难，货物寄送过程出了问题；
         * 3：签收，收件人已签收；
         * 4：退签，即货物由于用户拒签、超区等原因退回，而且发件人已经签收；
         * 5：派件，即快递正在进行同城派件；
         * 6：退回，货物正处于退回发件人的途中；
         */
        if (substr($value, 0, 4) == '316B' || substr($value, 0, 4) == '316A') {

            return self::jiali($value);
        }

        $arr['https'] = 1;
        $arr['cookie'] = 'Cookie:BDUSS=FLWFZxSHJxUDlabUJWR3haM0lvSVAzWVNWLWpPVmpWbm5xZmdWTGEtZ0JXYTFXQVFBQUFBJCQAAAAAAAAAAAEAAADyi6UAMjI5OTQ5MwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAHMhVYBzIVWNH; PSTM=1451912393; BIDUPSID=7147967741D689DC9518D72789A5935D; BAIDUID=171720D8AB8120731BF1D157283F6A5B:FG=1; H_PS_PSSID=18436_18728_1456_18725_18240_17945_18790_18731_18768_18778_18546_18779_17001_18782_17072_15623_12021_18098_18017';
        $arr['url'] = "https://sp0.baidu.com/9_Q4sjW91Qh3otqbppnN2DJv/pae/channel/data/asyncqury?cb=jQuery1102017068667733110487_1452236141952&appid=4001&com=&nu=$value&vcode=&token=&_=1452236141962";
        $c = self::Curl($arr);
        preg_match('#jQuery.*?\\((.*?)\\)#', $c, $mmm);
        if (@$mmm[1]) {
            $c_json = json_decode($mmm[1], true);
            if (@$c_json['data']['info']['context']) {
                return json_encode(@$c_json['data']['info']);
            }

        }
    }
    public static function jiali($value)
    {
        /**
         * 嘉里物流
         * state
         * 0：在途，即货物处于运输过程中；
         * 1：揽件，货物已由快递公司揽收并且产生了第一条跟踪信息；
         * 2：疑难，货物寄送过程出了问题；
         * 3：签收，收件人已签收；
         * 4：退签，即货物由于用户拒签、超区等原因退回，而且发件人已经签收；
         * 5：派件，即快递正在进行同城派件；
         * 6：退回，货物正处于退回发件人的途中；
         */
        $json = [];
        $json['status'] = 0;
        $arr['url'] = "http://219.141.231.130/htdocs/appleQueryAction.do?method=QueryStatusInfo&fid=$value";
        $c = self::Curl($arr);

        if (strstr($c, "已签收")) {
            $json['state'] = 3;
        } else {
            $json['state'] = 0;
        }

        $json['com'] = '嘉里大通';

        preg_match_all('#<TR class="bg">.*?<td>(.*?)</td>.*?<td>(.*?)</td>.*?<td>(.*?)</td>.*?<td>(.*?)\n#is', $c, $mmm);
        if ($mmm[1]) {
            $json['status'] = 1;
            foreach ($mmm[1] as $k => $v) {
                $json['context'][$k]['time'] = str_replace(PHP_EOL, '', $mmm[1][$k]) . str_replace(PHP_EOL, '', $mmm[2][$k]);
                $json['context'][$k]['time'] = str_replace('	', '', $json['context'][$k]['time']);
                $json['context'][$k]['time'] = strtotime($mmm[1][$k]); //没有精确到时间
                $json['context'][$k]['desc'] = str_replace(PHP_EOL, '', $mmm[3][$k]) . str_replace(PHP_EOL, '', $mmm[4][$k]);
                $json['context'][$k]['desc'] = str_replace('	', '', $json['context'][$k]['desc']);
            }
        }

        return json_encode($json);
    }
    public static function Curl($arr = array())
    {
        $arr['time']= isset($arr['time']) ? $arr['time']:60;   
        $curl = curl_init($arr['url']);
        // curl_setopt($curl, CURLOPT_DNS_USE_GLOBAL_CACHE, 3600);
        //将curl_exec()不直接输出
        if (empty($arr['xianshi'])) {
            $arr['xianshi'] = 1;
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, $arr['xianshi']);
        //启用时会将头文件的信息作为数据流输出。
        if (isset($arr['toubu'])) {
            curl_setopt($curl, CURLOPT_HEADER, $arr['toubu']);
        }

        if (isset($arr['daili'])) {
            curl_setopt($curl, CURLOPT_PROXY, $arr['daili']);
        }

        if (isset($arr['dailimima'])) {
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $arr['dailimima']);
        }
        //curl时间设置
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $arr['time']); //连接超时时间
        curl_setopt($curl, CURLOPT_TIMEOUT, $arr['time']); //数据传输的最大允许时间
        if (isset($arr['header'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $arr['header']);
        }
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)');

        //根据网址开头判断https
        if (strstr($arr['url'], "https")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }


        //来路
        if (isset($arr['cookie'])) {
            curl_setopt($curl, CURLOPT_COOKIE, $arr['cookie']);
        }
        //来路
        if (isset($arr['referer'])) {
            curl_setopt($curl, CURLOPT_REFERER, $arr['referer']);
        }
        if (isset($arr['post'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $arr['post']);
        }
        $Result = curl_exec($curl);
        curl_close($curl);

        return $Result;
    }

}
