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
        $data_sn['wid'] = trim($data_sn['wid']); //去头尾空格
        $wid_arr = explode(" ", $data_sn['wid']);
        if (count($wid_arr) > 1) {
            $data_sn['wid'] = $wid_arr[0];
        }
        $data_sn['jinduok'] = 0;
        //
        if (input('?post.tiamowid')) {
            $data_sn['wid'] = input('post.tiamowid');
        }
        //已经完成和多维是否是另外的WXID
        if (strstr($data_sn['wx_json'], 'FINL') && strstr($data_sn['wx_json'], $data_sn['wid'])) {
            //不重复查询
            $fanhui2['data'] = json_decode($data_sn['wx_json'], 1);
            $data_sn['jinduok'] = 1;
            return self::Fenxi(json_encode($fanhui2), $data_sn);
        }



        //判断jiankong_wid 库是否存在
        $jiankong_wid = Db::name('jiankong_wid')->where('snwid', $data_sn['sn'] . '_' . $data_sn['wid'])->order('id', 'desc')->find();

        if ($jiankong_wid && ($jiankong_wid['ztai'] == 1 || $jiankong_wid['ztai'] == 2) && json_decode($jiankong_wid['wx_json'], 1)) {
            //已经完成和60秒内查询的 直接调用数据库
            if (strstr($jiankong_wid['wx_json'], 'FINL')  || (time() - $jiankong_wid['ctime']) < 60 * 1) {
                $data_sn['bugengxin_ctime'] = 1;
                $fanhui2['data'] = json_decode($jiankong_wid['wx_json'], 1);
                $data_sn['jinduok'] = 1;
                return self::Fenxi(json_encode($fanhui2), $data_sn); // 分析状态值
            }
        }

        //临时使用，进度恢复了删除这里
        if (preg_match("#^(G|g)\d{9}$#", trim($data_sn['wid']))) {
           // return ["status" => false, "tishi" => "苹果系统维护，请稍后再查..."];
            return self::ceshiZtai($data_sn);
        }
        //临时使用，进度恢复了删除这里
        $arr['url'] = self::getwxUrl2019();
        //替换
        $arr['url'] = str_replace('#WXID', $data_sn['wid'], $arr['url']);
        $arr['url'] = str_replace('#WXSN', $data_sn['sn'], $arr['url']);
        $arr['header'] = array(
            "Accept-Language: zh-CN",
        );
        //$arr['daili']="115.152.147.46:4248";//{"ip":"115.152.147.46","port":4248,"expire_time":"2019-07-27 16:25:08","
        $fanhui = self::_curl($arr);
        // R号临时修复
        //测试业务流<!---->
        $i=1;
        while ($fanhui['httpcode'] != 200 || !strstr($fanhui['html'], 'repairMetaData') || strstr($fanhui['html'], 'repairws.repairdetails.error.500.ER001') || strstr($fanhui['html'], 'repairws.repairdetails.error.500.ER004') || strstr($fanhui['html'], '503 Service Temporarily Unavailabl')) {

            $arr['url'] = self::getwxUrl2019();
            //替换
            $arr['url'] = str_replace('#WXID', $data_sn['wid'], $arr['url']);
            $arr['url'] = str_replace('#WXSN', $data_sn['sn'], $arr['url']);
            $arr['header'] = array(
                "Accept-Language: zh-CN",
            );
            $i++;
            //   Core::logToDb($arr,'',' 重新找IP '.$i);
            Core::logToDb("查询繁忙，累计查询".$i.'次','jindu-R');
            $fanhui = self::_curl($arr);
            if( $i>= 4){
                Core::logToDb("查询繁忙，累计查询4次",'jindu-R');
                break;
            }
        }
        //测试业务流 end <!---->
        if ($fanhui['httpcode'] != 200) {
            return ["status" => false, "tishi" => "网络错误，请稍后重试！"];
        }
        $fanhui = $fanhui['html'];

        if (strstr($fanhui, 'No status available')) {
            return ["status" => false, "tishi" => "苹果系统维护，请稍后再查..."];
        }
        return self::Fenxi($fanhui, $data_sn);
    }
    //零时测试技术支持
    public static function ceshiZtai($data_sn)
    {
        $arr['header'] = array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            "Content-Type: application/json; charset=UTF-8",
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
            'Referer: https://getsupport.apple.com/',
            'Accept-Language: zh-CN;q=0.8,zh;q=0.7',
        );

        $arr['post'] = '{"repairId":"' . $data_sn['wid'] . '","transid":"' . $data_sn['sn'] . '","userOffset":480,"dims":".Wa44j1e3NlY5BSo9z4ofjb75PaK4Vpjt.gEngMQEjZr_WhXTA2s.XTVV26y8GGEDd5ihORoVyFGh8cmvSuCKzIlnY6xljQlpRD_0QnIpWvLPrqgXK_Pmtd0SHp815LyjaY2.rINj.rIN4WzcjftckcKyAd65hz74WySXvOxwawgCgIlNU.3Io3.Nzl998tp7ppfAaZ6m1CdC5MQjGejuTDRNziCvTDfWocATPTgEPnLypZHgfLMC7Awvw0BpUMnGWoz75PP9BLsBwe98vDdYejftckuyPBDjaY2ftckZZLQ084akJk...5LJEK9QxQeLaD.SAxN4t1VKWZWuxbuJjkWiMgdVgEL3NvWjV2pNk0ug97SYidmYUL0TFl9kmFxHeUdvEoWicCmx_B4W1kl1BNlY6Rc0Y5BOgkLT0XxU..7qF"}';        $arr["url"] = "https://getsupport.apple.com/web/v1/repairlookup"; //en_US zh_CN
        $arr["url"] = "https://getsupport.apple.com/web/v1/repairlookup"; //en_US zh_CN
        

        $arr['time']=3;
        $iparr = self::zdayetiquip();// 获取一个站大爷的IP zjbsky

        $arr['daili'] = $iparr['ip'];
        $fanhui = self::_curl($arr);
        //测试业务流<!---->
        if(  $fanhui['httpcode'] != 200 ){
            $i=1;
            // Core::logToDb($arr,'','send_templete_user');
            do{
                $i++;
                $iparr  = Db::name('ip_zdaye')->orderRaw('rand()')->find();
                $arr['daili'] = $iparr['ip'];

             //   Core::logToDb($arr,'',' 重新找IP '.$i);

                $fanhui = self::_curl($arr);
            } while ($fanhui['httpcode'] != 200 && $i< 4);

        }
        //测试业务流 end <!---->

        if ($fanhui['httpcode'] != 200) {
            return ["status" => false, "tishi" => "网络错误，请稍后重试！!"];
        }
        //不能这样判断
        $warr['wx_ctime'] = time();
        $chaxunjieguo='';
        if (strstr($fanhui['html'], '"repairStatus":"已请求"')) {       
            $chaxunjieguo= '申请服务（临时接口）'; //  
            $warr['wx_ztai_id']='-4401';
        } elseif (strstr($fanhui['html'], '"repairStatus":"正在维修"')) {
            $chaxunjieguo= '进行中（临时接口）'; //
            $warr['wx_ztai_id']='-4402';
        } elseif (strstr($fanhui['html'], '"repairStatus":"已关闭"')) {
            $chaxunjieguo= '已完成（临时接口）'; //
            $warr['wx_ztai_id']='-4403';

        } elseif (strstr($fanhui['html'], '"repairStatus":"未经维修"')) {
            $chaxunjieguo= '拒保（临时接口）'; //
            $warr['wx_ztai_id']='-4404';

        } elseif (strstr($fanhui['html'], '"repairStatus":"搁置中"')) {
            $chaxunjieguo= '升级工程部（临时接口）'; //
            $warr['wx_ztai_id']='-4405';

        }   elseif (strstr($fanhui['html'], '"repairStatus":"正在重新报价"')) {
            $chaxunjieguo= '报价中（临时接口）'; //
            $warr['wx_ztai_id']='-4406';
        }  elseif (strstr($fanhui['html'], '"repairStatus":"零售店：其他"')) {
            $chaxunjieguo= '零售店：其他'; //
            $warr['wx_ztai_id']='-4407';
        }      
        
        elseif (strstr($fanhui['html'], '请输入有效的维修 ID 或案例 ID')) {
            $chaxunjieguo= '维修ID或者序列号错误'; //
            $warr['wx_ztai_id']='-21';
        }
        else{
            //print_r($fanhui['html']);
            $chaxunjieguo= '未知（临时接口）'; //
        }

        $wxid_arr = Cache::get('wxid', '');
        if (@isset($wxid_arr[$warr['wx_ztai_id']])) {
            $tishi = "<span class='badge badge-" . $wxid_arr[$warr['wx_ztai_id']][2] . "'>" . $wxid_arr[$warr['wx_ztai_id']][1] . '</span>苹果官网第三方（G号维护）当前结果为临时提供...' ;
            Db::name('sn_listsn')->where('id', $data_sn['id'])->update($warr); //保存
        } else {
            $tishi = $chaxunjieguo;
        }

        return ["status" => true, "tishi" => $tishi ];
 }


 //在数据库里找到一个IP 来用
    public static function zdayetiquip()
    {
        $ipdata = Db::name('ip_zdaye')->where('ztai',0)->where('timeout', '>',time()-20)->order('tiqutime', 'ACS')->find();
        //更新下数据集
        Db::name('ip_zdaye')->where('id', $ipdata['id'])->update(['tiqutime'=>time()]);
        return $ipdata;
    }

  //临时升级    20分钟更换一次
  public static function ceshiZtaigetwxUrl2019()
  {
      $h = [
        'https://1569576395704989.cn-hangzhou.fc.aliyuncs.com/2016-08-15/proxy/api/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.cn-shanghai.fc.aliyuncs.com/2016-08-15/proxy/api/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.cn-qingdao.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.cn-beijing.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.cn-zhangjiakou.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.cn-huhehaote.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.cn-shenzhen.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.cn-hongkong.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.ap-southeast-1.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.ap-southeast-2.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.ap-southeast-5.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.ap-northeast-1.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.eu-central-1.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.us-west-1.fc.aliyuncs.com/2016-08-15/proxy/checkztai/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.us-east-1.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
        'https://1569576395704989.ap-south-1.fc.aliyuncs.com/2016-08-15/proxy/jindu/jishuzhichi/tiamo_-_#WXSN_-_#WXID',
      ];

      if (empty(Cache('jishuzhichi_url'))) {
        $curl_arr = $h; //获取URLAPI数组
        Cache('jishuzhichi_url', $curl_arr); //保存
      } else {
        $curl_arr = Cache('jishuzhichi_url');
      }
    $wxurl = array_pop($curl_arr); //获取数组的其中一个并删除。
    Cache('jishuzhichi_url', $curl_arr); // //重新保存
    return $wxurl;
  }






    //添加监控表
    public static function Jiankong_wid($data_sn, $savearr)
    {
        if (isset($data_sn['sn']) && isset($data_sn['wid'])) {
            $snwid = $data_sn['sn'] . '_' . $data_sn['wid'];
            if (Db::name('jiankong_wid')->where('snwid', $snwid)->find()) {
                //更新
                Db::name('jiankong_wid')->where('snwid', $snwid)->update($savearr);
            } else {
                //添加
                $savearr['snwid'] = $snwid;
                $savearr['create_time'] = time();
                Db::name('jiankong_wid')->strict(false)->insert($savearr);
            }
        }
    }

    //分析
    public static function Fenxi($fanhui, $data_sn)
    {
        //开始判断
        $warr['wx_ctime'] = time();
        if (strstr($fanhui, '500.RP_INVALID_REPAIRORSERIAL') || strstr($fanhui, 'repairws.repairdetails.error.200.ER00') || strstr($fanhui, '500.RP_INVALID_REPAIRID')) {
            $warr['wx_tishi'] = "序列号或维修ID错误";
            $warr['wx_ztai_id'] = '4';
            self::Tongji('jinduok_num');
            Db::name('sn_listsn')->where('id', $data_sn['id'])->update($warr); //保存

            self::Jiankong_wid($data_sn, ['uid' => UID, 'wx_ztai_id' => -21, 'ztai' => 4, 'ctime' => time()]);

            return ["status" => false, "tishi" => "序列号或维修ID错误"];
        }

        if (strstr($fanhui, 'repairws.repairdetails.error.500.ER001') || strstr($fanhui, 'repairws.repairdetails.error.500.ER004') || strstr($fanhui, '503 Service Temporarily Unavailabl')) {
            self::Tongji('jinduerror_num');
            return ["status" => false, "tishi" => "查询繁忙，请稍后重试！"];
        }



        if (strstr($fanhui, 'errorMessage')) {
            //self::Tongji('jinduerror_num');
            $fanhui_arr = json_decode($fanhui, 1);


            return ["status" => false, "tishi" => $fanhui_arr['errors'][0]['errorMessage']];
        }


        if (strstr($fanhui, '403 Forbidden')) {
            self::Tongji('jinduerror_num');
            return ["status" => false, "tishi" => "查询错误，稍后重试"];
        }


        if (strstr($fanhui, 'repairMetaData')) {
            $fanhui_arr = json_decode($fanhui, 1);
            $warr['wx_json'] = json_encode($fanhui_arr['data']); //所有保存结果json
            $repairMetaData = $fanhui_arr['data']['repairMetaData'];

            //修复苹果API接口BUG 经常查不到状态
            if (isset($repairMetaData['sapStatusId']) && $repairMetaData['sapStatusId'] == '0' && isset($repairMetaData['steps'][0]['statusId'])) {
                $new_sapStatusId = '0';
                $new_repairStatusDesc = '查询不到状态';
                foreach ($repairMetaData['steps'] as $v) {
                    if (isset($v['state']) && $v['state'] != 'FUTR') {
                        $new_sapStatusId = $v['statusId'];
                        if (isset($v['stepHeader'])) $new_repairStatusDesc = $v['stepHeader'];
                        if ($v['statusId'] == '997') $new_repairStatusDesc = '升级工程部';
                    }
                }
                //重新构架 repairMetaData json
                $repairMetaData['sapStatusId'] = $new_sapStatusId;
                $repairMetaData['repairStatusDesc'] = $new_repairStatusDesc . '[]';
            }
            self::Baojinduku($data_sn, $repairMetaData, $warr); //保存到Jindu库
            $warr['wx_ztai_id'] = $repairMetaData['sapStatusId']; //当前状态ID
            //保存进度时间
            $zuixinjindu_jiange = '';
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
                $zhifuqian = self::Baojia($repairMetaData['actionUrl'], $repairMetaData);
            }
            //各种提示
            if (isset($repairMetaData['repairStatusDesc'])) {
                $warr['wx_tishi'] = $repairMetaData['repairStatusDesc'] . $zhifuqian;
            }
            //当前维修状态描述
            if ($warr['wx_ztai_id'] == '997') {
                $warr['wx_tishi'] = "升级工程，搁置中...";
            }
            //当前维修状态描述
            if ($warr['wx_ztai_id'] == '470') {
                $warr['wx_tishi'] = "查不到状态。";
            }
            if ($warr['wx_ztai_id'] == '0') {
                $warr['wx_ztai_id'] = '1';
                $warr['wx_tishi'] = "查询不到状态";
                if (isset($repairMetaData['steps'])) {
                    //未知错误
                    $warr['wx_tishi'] = "查询失败，请重试";
                }
            }
            if (empty($warr['wx_tishi']))           $warr['wx_tishi'] = "查不到状态2。";

            //物流号
            if (isset($repairMetaData['trackingInfo']['trackingNum'])) {
                $warr['wx_wuliu'] = $repairMetaData['trackingInfo']['trackingNum'];
            }

            if ($data_sn['wx_sqtime'] == null || $data_sn['wx_sqtime'] == '') {
                //申请服务时间 进入工厂会改变申请服务时间 
                //如果第三个时间小于 //state： PAST完成 PRES进行中 FUTR还没到这部 FINL 第三步完成？
                $warr['wx_sqtime'] = null;
                if (isset($repairMetaData['steps'])) {
                    //1.如果第一步 PRES 进行中 说明申请服务
                    if (isset($repairMetaData['steps'][0]['statusDate']) && $repairMetaData['steps'][0]['state'] == 'PRES') {
                        $warr['wx_sqtime'] = @substr($repairMetaData['steps'][0]['statusDate'], 0, -3);
                    }
                    //2.如果第三步FUTR 说明第三步时间还再 直接获取第三步时间
                    if (isset($repairMetaData['steps'][2]['statusDate']) && $repairMetaData['steps'][2]['state'] == 'FUTR') {
                        $warr['wx_sqtime'] = @substr($repairMetaData['steps'][2]['statusDate'], 0, -3);
                    }
                    //3.还没想到 日后再https://getsupport.apple.com/获取？                
                }
            }
            //添加查询次数
            self::Tongji('jinduok_num');
            Db::name('sn_listsn')->where('id', $data_sn['id'])->update($warr); //保存
            //ztai 1=进行中 2完成 3       4错误维修ID 或者序列号
            if (strstr($warr['wx_json'], 'FINL')) {
                $ztaiq = 2;
            } else {
                $ztaiq = 1;
            }
            if (isset($data_sn['bugengxin_ctime']) && $data_sn['bugengxin_ctime'] == 1) { } else {
                self::Jiankong_wid($data_sn, ['uid' => UID, 'wx_ztai_id' => $warr['wx_ztai_id'], 'wx_time' => $warr['wx_time'], 'ztai' => $ztaiq, 'wx_json' => $warr['wx_json'], 'ctime' => time()]);
            }

            //提示颜色
            $wxid_arr = Cache::get('wxid', '');
            if (isset($wxid_arr[$warr['wx_ztai_id']])) {
                $tishi = "<span class='badge badge-" . $wxid_arr[$warr['wx_ztai_id']][2] . "'>" . $wxid_arr[$warr['wx_ztai_id']][1] . '</span>' . $warr['wx_tishi'];
            } else {
                $tishi = $warr['wx_tishi'];
            }
            //变动更新提示
            if ($data_sn['wx_ztai_id'] != $warr['wx_ztai_id']) {
                $tishigxbiandong = '';
                if (isset($wxid_arr[$data_sn['wx_ztai_id']])) {
                    $tishigxbiandong = "<span class='badge badge-" . $wxid_arr[$data_sn['wx_ztai_id']][2] . "'>" . $wxid_arr[$data_sn['wx_ztai_id']][1] . '</span>>';
                }
                $tishi = $tishigxbiandong . $tishi;
            }
            $tishi .= "<code></code><span class='badge badge-pill badge-secondary'>$zuixinjindu_jiange</span>";
            if ($data_sn['jinduok'] == 1) $tishi .= '<small class="text-muted">完成</small>';
            else $tishi .= '<small class="text-muted">进行中</small>';
            return ["status" => true, "tishi" => $tishi, 'arr' => self::SanbuzouArr($repairMetaData)];
        } else {
            //查询失败
            self::Tongji('jinduerror_num');
            return ["status" => false, "tishi" => "查询繁忙，请稍后重试1！"];
        }
    }
    public static function SanbuzouArr($repairMetaData)
    {
        $wx_arrx['t1'] = isset($repairMetaData['repairStatusDesc']) ? $repairMetaData['repairStatusDesc'] : '';
        $wx_arrx['t2'] = isset($repairMetaData['repairShortDesc']) ? $repairMetaData['repairShortDesc'] : '';
        $wx_arrx['time'] = isset($repairMetaData['modifiedDate']) ? substr($repairMetaData['modifiedDate'], 0, -3) : '';
        $wx_arrx['x'] = isset($repairMetaData['product']['userFriendlyProductName']) ? $repairMetaData['product']['userFriendlyProductName'] : '';
        $wx_arrx['s'] = [];
        if (isset($repairMetaData['steps'])) {
            foreach ($repairMetaData['steps'] as $v) {
                //state： PAST完成 PRES进行中 FUTR还没到这部 FINL 第三步完成？
                if (isset($v['state']) && $v['state'] != 'FUTR') {
                    if ($v['statusId'] == '997') {
                        $v['stepLabel']  = '升级工程';
                        $v['stepHeader']  = '升级工程部检测，预计3-5天出结果...';
                    }
                    unset($v['state']);
                    unset($v['num']);
                    unset($v['statusId']);
                    unset($v['hasAlert']);

                    if (isset($v['statusDate'])) {
                        $v['statusDate'] = date("Y-m-d H:i:s", substr($v['statusDate'], 0, -3));
                    }
                    $wx_arrx['s'][] = $v;
                }
            }
        }
        return $wx_arrx;
    }

    public static function Baojinduku($data_sn, $repairMetaData, $warr)
    {
        /** 换机地址分析 repairStoreAddress */

        if (!Db::name('sn_jindu')->where('wid', $data_sn['wid'])->where('wx_ztai_id', $repairMetaData['sapStatusId'])->find()) {
            if (array_key_exists('repairStoreAddress', $repairMetaData)) {
                @$repairStoreAddress = $repairMetaData['repairStoreAddress'];
                @$warr_jindu['address1'] = $repairStoreAddress['address1'];
                @$warr_jindu['address2'] =  $repairStoreAddress['address2'];
                @$warr_jindu['address3'] =  $repairStoreAddress['address3'];
                @$warr_jindu['address4'] =  $repairStoreAddress['address4'];
                @$warr_jindu['county'] =  $repairStoreAddress['county'];
                @$warr_jindu['city'] = $repairStoreAddress['city'];
                @$warr_jindu['state'] = $repairStoreAddress['state'];
                @$warr_jindu['postal'] = $repairStoreAddress['postal'];
                @$warr_jindu['country'] = $repairStoreAddress['country'];
                @$warr_jindu['orgName'] = $repairStoreAddress['orgName'];
                @$warr_jindu['addressType'] = $repairStoreAddress['addressType'];
            }
            $warr_jindu['uid'] = UID;
            $warr_jindu['sn'] = $data_sn['sn'];
            $warr_jindu['sn_hou'] = substr($data_sn['sn'], '-4');
            $warr_jindu['wid'] = $data_sn['wid'];
            $warr_jindu['wx_ztai_id'] = $repairMetaData['sapStatusId']; //当前状态ID
            @$warr_jindu['wx_json'] = $warr['wx_json'];
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

    public static function Baojia($baojiaurl, $repairMetaData)
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
            $fanhuibaojia =  $fanhuibaojia['html'];
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
                    if (@$repairMetaData['requoteExpiryDate']) {
                        $timejiaohuanjia = date('Y-m-d H', substr($repairMetaData['requoteExpiryDate'], 0, -3)); //时间转换
                        $zhifuqian .= $timejiaohuanjia . '点前支付' . '<a target="_blank" href="' . $baojiaurl . '">进入付款</a>  ';
                    }
                }
            }
        }

        return $zhifuqian;
    }
    //临时升级    20分钟更换一次
    public static function getwxUrl2019()
    {
        $h[0] = [
            'https://1569576395704989.cn-hangzhou.fc.aliyuncs.com/2016-08-15/proxy/api/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.cn-shanghai.fc.aliyuncs.com/2016-08-15/proxy/api/checkjindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.cn-qingdao.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.cn-beijing.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.cn-zhangjiakou.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.cn-huhehaote.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.cn-shenzhen.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.cn-hongkong.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'http://1.aiguo5.com/wx_curl.php?wid=#WXID&sn=#WXSN',
        ];

        $h[1] = [
            'https://1569576395704989.ap-southeast-1.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.ap-southeast-2.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.ap-southeast-5.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.ap-northeast-1.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.eu-central-1.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.us-west-1.fc.aliyuncs.com/2016-08-15/proxy/checkztai/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.us-east-1.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'https://1569576395704989.ap-south-1.fc.aliyuncs.com/2016-08-15/proxy/jindu/jindu/tiamo_-_#WXSN_-_#WXID',
            'http://1.aiguovip.com/wx_curl.php?wid=#WXID&sn=#WXSN',
        ];

        $jiangetime = 60 * 20; //间隔15分钟 测试15分钟更换一个 看封不封
        if (empty(Cache('chaxun_h'))) {
            Cache('chaxun_h', ['h' => 0, 'time' => time() + $jiangetime, 'http' => $h[0]]); //默认0 保存时间
        }
        $chaxun_h = Cache('chaxun_h');
        //15分钟更换一次
        if ($chaxun_h['time'] < time()) {
            if ($chaxun_h['h'] == 0) Cache('chaxun_h', ['h' => 1, 'time' => time() + $jiangetime, 'http' => $h[1]]); //默认0 保存时间
            if ($chaxun_h['h'] == 1) Cache('chaxun_h', ['h' => 0, 'time' => time() + $jiangetime, 'http' => $h[0]]); //默认0 保存时间
        }
        $chaxun_h = Cache('chaxun_h');

        if (empty(Cache('chaxun_url2'))) {
            $curl_arr = $chaxun_h['http'];
            Cache('chaxun_url2', $curl_arr); //保存
        } else {
            $curl_arr = Cache('chaxun_url2');
        }
        $wxurl = array_pop($curl_arr); //获取数组的其中一个并删除。
        Cache('chaxun_url2', $curl_arr); // //重新保存
        return $wxurl;
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
        $t1 = microtime(true);
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
        $t2 = microtime(true);
        $arr['miao'] = round($t2 - $t1, 3);
        return $arr;
    }
}
