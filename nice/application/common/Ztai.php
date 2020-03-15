<?php
namespace app\common;

use think\Db;
use think\facade\Cache;
use app\common\Nb;

use app\common\Jindu;

/**
 * Ztai
 */
class Ztai
{

    public static function go($arr)
    {
        //优先用IMEI查
        if (isset($arr["imei"])) {
            $arr["sn"] = $arr["imei"]; //
        }
        $arr['iparr'] = self::Getip();
        if(UID == 1){
           Core::logToDb($arr['iparr'] ,'','国外的IP ');
        }
        $arr['t1'] = microtime(true); //计算时间开始
        $arr['time'] = 30;
        $arr['daili'] = 'http://zproxy.lum-superproxy.io:22225';
        $arr['dailimima'] = "lum-customer-hl_d7719b11-zone-static-ip-" . $arr['iparr']['ip'] . ":s6e05p94uqwq"; //指定美国随机
        $arr["url"] = "https://getsupport.apple.com/?locale=en_JP&sn=" . $arr["sn"] . "&symptom_id=20369&category_id=SC0105"; //en_US zh_CN
        $arr['header'] = array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            "Content-Type: application/json; charset=UTF-8",
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
            'Referer: https://getsupport.apple.com/',
        );

        $arr["toubu"] = 1;
        $fanhui = Jindu::_curl($arr);

        if ($fanhui['httpcode'] == 403)  {
            //ip正确被封
            Db::name('ip_us')->inc('feng')->update(['id' => $arr['iparr']['id'], 'ztai' => 3, 'time' => time()]);
            Nb::Tongji('ztaierror_num');
            return ['status' => false, 'tishi' => '查询失败（10003），请稍后重试！'];
        }

        if ($fanhui['httpcode'] != 200) {
            return ["status" => false, "tishi" => "网络错误，请稍后重试10001！"];
        }

        $fanhui = $fanhui['html'];
        $arr["m1"]=round(microtime(true) - $arr['t1'], 1);
        $warr['wx_ctime'] = time();

        //序列号正确   不存在 snDetails的时候就是替换吗
        if (strstr($fanhui, '403 Forbidden') ) {
            //ip正确被封
            Db::name('ip_us')->inc('feng')->update(['id' => $arr['iparr']['id'], 'ztai' => 3, 'time' => time()]);
            Nb::Tongji('ztaierror_num');
            return ['status' => false, 'tishi' => '查询失败（10003），请稍后重试！'];
        }elseif (strstr($fanhui, 'Invalid Serial Number')) {
            //序列号错误
            Db::name('ip_us')->inc('ok')->update(['id' => $arr['iparr']['id'], 'time' => time()]);
            Nb::Tongji('ztaiok_num');
            $warr['wx_ztai_id'] = '-2';
            $warr['wx_tishi'] = '序列号错误';
            Db::name('sn_listsn')->where('id', $arr['id'])->update($warr); //保存
            return ['status' => false, 'tishi' => "<span class='badge badge-dark'>IMEI/序列号错误</span>"];
        } 
        elseif (strstr($fanhui, 'CASService') && strstr($fanhui, $arr["sn"])) {

            Db::name('ip_us')->inc('ok')->update(['id' => $arr['iparr']['id'], 'time' => time()]);
            //成功获取
            preg_match_all("#Set\-Cookie:(.*?);#is", $fanhui, $mac);
            $cookie = '';
            foreach ($mac[1] as $v) {
                $cookie .= "$v;";
            }
            $arr['cookie'] = $cookie;
            return self::ChaZtai2($arr);
        } else {
            Nb::Tongji('ztaierror_num');
            //暂时不保存ztai=错误
            Db::name('ip_us')->inc('error')->update(['id' => $arr['iparr']['id'],'ztai' => 2, 'time' => time()]);
            return ['status' => false, 'tishi' => '查询失败（10001），请稍后重试！'];
        }
    }
    /**  状态查询2 */
    public static function ChaZtai2($arr)
    {

        $warr['wx_ctime'] = time();
        // unset($arr["daili"] );unset($arr["dailimima"] );
        if(UID==1){
            Core::logToDb($arr,'','  代理 查询状态 ： ');
        }

        $arr["url"] = "https://getsupport.apple.com/web/v1/solutions"; //en_US zh_CN
        $arr["post"] = "{\"serialNumber\":\"" . $arr["sn"] . "\"}";
        $fanhui = Jindu::_curl($arr);


        if ($fanhui['httpcode'] != 200) {
            return ["status" => false, "tishi" => "网络错误，请稍后重试20001！!"];
        }
        $fanhui = $fanhui['html'];

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
            Db::name('sn_listsn')->where('id', $arr['id'])->update($warr); //保存
            Nb::Tongji('ztaiok_num');
            //跟新ip
            Db::name('ip_us')->inc('ok')->update(['id' => $arr['iparr']['id'], 'time' => time(), 'miao' => round(microtime(true) - $arr['t1'], 1) ]);
            //提示颜色
            $wxid_arr = Cache::get('wxid', '');
            if (isset($wxid_arr[$warr['wx_ztai_id']])) {
                $tishi = "<span class='badge badge-" . $wxid_arr[$warr['wx_ztai_id']][2] . "'>" . $wxid_arr[$warr['wx_ztai_id']][1] . '</span>' . $warr['wx_tishi'];
            } else {
                $tishi = $warr['wx_tishi'];
            }
            return ["status" => true, "tishi" => $tishi .$arr["m1"].' '.round(microtime(true) - $arr['t1'], 1)];
        } elseif (strstr($fanhui, '403 Forbidden')) {
            //ip正确被封
            Db::name('ip_us')->inc('error')->update(['id' => $arr['iparr']['id'], 'ztai' => 3, 'time' => time()]);
            Nb::Tongji('ztaierror_num');
            return ['status' => false, 'wx_tishi' => '查询失败（20003），请稍后重试！'];
        } else {
            Nb::Tongji('ztaierror_num');
            //暂时不保存ztai=错误
            Db::name('ip_us')->inc('error')->update(['id' => $arr['iparr']['id'], 'time' => time()]);
            return ['status' => false, 'wx_tishi' => '查询失败（20004），请稍后重试！'];
        }
    }

    /**  IP获取 返回数组 */
    public static function Getip()
    {
        if (empty(Cache('usiplist'))) {
            $ipdata = Db::name('ip_us')->where('ztai', 1)->column('ip,ztai', 'id');
            Cache('usiplist', $ipdata); //获取URLAPI数组m
            Core::logToDb('重新拿了usiplist','重新拿了usiplist','重新拿了usiplist');
        }
        $linshiiparr = Cache('usiplist');
        $ip = array_pop($linshiiparr);
        $ip['count'] = count($linshiiparr);
        Cache('usiplist', $linshiiparr,7200); //保存一个新的储存ip  两个小时后重新拿一批出来 用
        return $ip;
    }
}
