<?php
namespace app\common;

use think\Db;
use think\facade\Cache;

/**
 * CURl
 */
class Jindu
{

    public static function go($data_sn)
    {
        $arr['url'] = self::getwxUrl();
        //替换
        $arr['url'] = str_replace('#WXID', $data_sn['wid'], $arr['url']);
        $arr['url'] = str_replace('#WXSN', $data_sn['sn'], $arr['url']);
        $arr['header'] = array(
            "Accept-Language: zh-CN",
        );
        $fanhui = self::_curl($arr);
        if ($fanhui['httpcode'] != 200) {
            return ["status" => false, "tishi" => "网络错误，请稍后重试！"];
        }
        $fanhui = $fanhui['html'];
        //开始判断
        $warr['wx_ctime'] = time();
        if (strstr($fanhui, '500.RP_INVALID_REPAIRORSERIAL') || strstr($fanhui, 'repairws.repairdetails.error.200.ER00') || strstr($fanhui, '500.RP_INVALID_REPAIRID')) {
            $warr['wx_tishi'] = "序列号或维修ID错误";
            $warr['wx_ztai_id'] = '4';
            self::Tongji('jinduok_num');
            Db::name('sn_listsn')->where('id', $data_sn['id'])->update($warr); //保存
            return ["status" => false, "tishi" => "序列号或维修ID错误"];
        }

        if (strstr($fanhui, 'repairws.repairdetails.error.500.ER001') || strstr($fanhui, 'repairws.repairdetails.error.500.ER004') || strstr($fanhui, '503 Service Temporarily Unavailabl')) {
            self::Tongji('jinduerror_num');
            return ["status" => false, "tishi" => "查询繁忙，请稍后重试！"];
        }

        if (strstr($fanhui, 'repairMetaData')) {
            $fanhui_arr = json_decode($fanhui, 1);
            $warr['wx_json'] = json_encode($fanhui_arr['data']); //所有保存结果json
            $repairMetaData = $fanhui_arr['data']['repairMetaData'];

            //修复苹果API接口BUG 经常查不到状态
            if(isset($repairMetaData['sapStatusId']) && $repairMetaData['sapStatusId']=='0' && isset($repairMetaData['steps'][0]['statusId'])){
                $new_sapStatusId='0';
                $new_repairStatusDesc='查询不到状态';
                foreach ($repairMetaData['steps'] as $v) {
                    if (isset($v['state']) && $v['state']!='FUTR') {
                        $new_sapStatusId=$v['statusId'];                        
                        if(isset($v['stepHeader']))$new_repairStatusDesc=$v['stepHeader'];
                        if($v['statusId']=='997')$new_repairStatusDesc='升级工程部';
                        
                    }
                }
                //重新构架 repairMetaData json
                $repairMetaData['sapStatusId']=$new_sapStatusId;
                $repairMetaData['repairStatusDesc']=$new_repairStatusDesc.'[]';            
            }
            self::Baojinduku($data_sn, $repairMetaData); //保存到Jindu库
            $warr['wx_ztai_id'] = $repairMetaData['sapStatusId']; //当前状态ID
            //保存进度时间
            $zuixinjindu_jiange='';
            if (isset($repairMetaData['modifiedDate'])) {
                $warr['wx_time'] = substr($repairMetaData['modifiedDate'], 0, -3); //当前进度时间
                $zuixinjindu_jiange = jiangeshijian2($warr['wx_time']);
            } else if (isset($repairMetaData['steps'][0]['statusDate'])) {
                //比如一些维修取消的时候保存
                $warr['wx_time'] = substr($repairMetaData['steps'][0]['statusDate'], 0, -3); //当前进度时间
                $zuixinjindu_jiange = jiangeshijian2($warr['wx_time']);
            }
 
            //报价地址
            $zhifuqian = '';
            if (@isset($repairMetaData['actionUrl'])) {
                $zhifuqian = self::Baojia($repairMetaData['actionUrl']);
            }
            //各种提示
            if (isset($repairMetaData['repairStatusDesc'])) {
                $warr['wx_tishi'] = $repairMetaData['repairStatusDesc'] . $zhifuqian;
            }
            //当前维修状态描述
            if ($warr['wx_ztai_id'] == '997') {
                $warr['wx_tishi'] = "升级工程，搁置中...";
            }
            if ($warr['wx_ztai_id'] == '0') {
                $warr['wx_ztai_id'] = '1';
                $warr['wx_tishi'] = "查询不到状态";
                if(isset($repairMetaData['steps'])){
                    //未知错误
                    $warr['wx_tishi'] = "查询失败，请重试";
                }
            }

            //物流号
            if (isset($repairMetaData['trackingInfo']['trackingNum'])) {
                $warr['wx_wuliu'] = $repairMetaData['trackingInfo']['trackingNum'];
            }
            //申请服务时间 如果才在第一步


 
            //添加查询次数
            self::Tongji('jinduok_num');
            Db::name('sn_listsn')->where('id', $data_sn['id'])->update($warr); //保存
            //提示颜色
            $wxid_arr = Cache::get('wxid', '');



            if (isset($wxid_arr[$warr['wx_ztai_id']])) {
                $tishi = "<span class='badge badge-" . $wxid_arr[$warr['wx_ztai_id']][2] . "'>" . $wxid_arr[$warr['wx_ztai_id']][1] . '</span>' . $warr['wx_tishi'];
            } else {
                $tishi = $warr['wx_tishi'];
            }
            //变动更新提示
            if($data_sn['wx_ztai_id']!=$warr['wx_ztai_id']){
                $tishigxbiandong='';
                if (isset($wxid_arr[$data_sn['wx_ztai_id']])) {
                    $tishigxbiandong = "<span class='badge badge-" . $wxid_arr[$data_sn['wx_ztai_id']][2] . "'>" . $wxid_arr[$data_sn['wx_ztai_id']][1] . '</span>>' ;
                }
                $tishi =$tishigxbiandong. $tishi ;
            }  
            $tishi.="<code></code><span class='badge badge-pill badge-secondary'>$zuixinjindu_jiange</span>";
            return ["status" => true, "tishi" => $tishi, 'arr' => self::SanbuzouArr($repairMetaData)];

        } else {
            //查询失败
            self::Tongji('jinduerror_num');
            return ["status" => false, "tishi" => "查询繁忙，请稍后重试！"];
        }
    }
    //分析
    public static function Fenxi($repairMetaData){

    }



    public static function SanbuzouArr($repairMetaData)
    {
        $wx_arrx['t1'] = isset($repairMetaData['repairStatusDesc']) ? $repairMetaData['repairStatusDesc'] : '';
        $wx_arrx['t2'] = isset($repairMetaData['repairShortDesc']) ? $repairMetaData['repairShortDesc'] : '';
        $wx_arrx['time'] = isset($repairMetaData['modifiedDate']) ? substr($repairMetaData['modifiedDate'], 0, -3) : '';
        $wx_arrx['x'] = isset($repairMetaData['product']['userFriendlyProductName']) ? $repairMetaData['product']['userFriendlyProductName'] : '';
        $wx_arrx['s']=[];
        if(isset($repairMetaData['steps'])){
            foreach ($repairMetaData['steps'] as $v) {
                //state： PAST完成 PRES进行中 FUTR还没到这部 FINL 第三步完成？
                if (isset($v['state']) && $v['state']!='FUTR') {
                    if($v['statusId']=='997')  {
                        $v['stepLabel']  ='升级工程';
                        $v['stepHeader']  ='升级工程部检测，预计3-5天出结果...';
                        
                    }    
                    unset($v['state']);unset($v['num']);unset($v['statusId']);unset($v['hasAlert']);
                 
                    if (isset($v['statusDate'])) {
                        $v['statusDate'] = date("Y-m-d H:i:s", substr($v['statusDate'], 0, -3));
                    }    
                    $wx_arrx['s'][] = $v;
                }
            }
        }
        return $wx_arrx;
    }

    public static function Baojinduku($data_sn, $repairMetaData)
    {
        if (!Db::name('sn_jindu')->where('wid', $data_sn['wid'])->where('wx_ztai_id', $repairMetaData['sapStatusId'])->find()) {
            $warr_jindu['uid'] = UID;
            $warr_jindu['sn'] = $data_sn['sn'];
            $warr_jindu['sn_hou'] = substr($data_sn['sn'], '-4');
            $warr_jindu['wid'] = $data_sn['wid'];
            $warr_jindu['wx_ztai_id'] = $repairMetaData['sapStatusId']; //当前状态ID
            if (isset($repairMetaData['modifiedDate'])) {
                $warr_jindu['wx_time'] = substr($repairMetaData['modifiedDate'], 0, -3); //当前进度时间
                $warr_jindu['wx_jindutime'] = date("Ymd", $warr_jindu['wx_time']);
            } else if (isset($repairMetaData['steps'][0])) {
                $warr_jindu['wx_time'] = substr($repairMetaData['steps'][0]['statusDate'], 0, -3); //当前进度时间
                $warr_jindu['wx_jindutime'] = date("Ymd", $warr_jindu['wx_time']);
            }
            $warr_jindu['ctime'] = time();
            Db::name('sn_jindu')->insert($warr_jindu); //保存到jindu进度库
        }
    }
    public static function Tongji($lei)
    {
        //
        $data['cri'] = date("Y-m-d");
        $data['uid'] = UID;
        $data['update_time'] = time();
        if (!Db::name('sn_tongji')->where('uid', UID)->where('cri', $data['cri'])->find()) {
            Db::name('sn_tongji')->insert($data);
        }
        //哪里保存 保存哪里 哈哈哈
        Db::name('sn_tongji')->where('uid', UID)->where('cri', $data['cri'])->setInc($lei);
    }

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
        $fanhuibaojia = self::_curl($baojiaarr);

        if ($fanhuibaojia['httpcode'] = 200) {
            $fanhuibaojia=  $fanhuibaojia['html'];
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
        }

        return $zhifuqian;
    }
    /**
     * 按顺序获取URL 每次都不同 防止查询错误！
     */
    public static function getwxUrl()
    {

        if (empty(Cache('chaxun_url'))) {
            $curl_arr = Cache('wxapi'); //获取URLAPI数组
            Cache('chaxun_url', $curl_arr); //保存
        } else {
            $curl_arr = Cache('chaxun_url');
        }
        $wxurl = array_pop($curl_arr); //获取数组的其中一个并删除。
        Cache('chaxun_url', $curl_arr); // //重新保存
        return $wxurl;
    }

    public static function _curl($arr = array())
    {
        $arr['time'] = isset($arr['time']) ? $arr['time'] : 60;
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
        $arr['html'] = curl_exec($curl); //返回结果
        $arr['httpcode'] = curl_getinfo($curl, CURLINFO_HTTP_CODE); //返回http代码 判断200
        curl_close($curl);
        return $arr;
    }

}
