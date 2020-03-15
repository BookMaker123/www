<?php

namespace app\vip\model;
use think\facade\Cache;
use think\Model;

/**
 * 列表模型
 * Class Lists
 * @package app\vip\model
 */

//全部获取列表的查询都写到这里来 就好看了
class Listsn extends Model
{

    /**
     * 绑定数据表
     * @var string
     */
    protected $name = 'sn_listsn';
   // protected $autoWriteTimestamp = true;//create_time和update_time 开启自动写入时间戳字段


    //自动转换格式
    protected $type = [
        'time' => 'timestamp:Y-m-d', //自动转换时间timestamp:Y-m-d
        //还可以自动保存json
    ];

    /**
     * 智能添加
     * @return mixed
     */
    public function zhinengadd($post)
    {

    }


    /**
     * 智能转换格式
     * @return mixed
     */
    public function zhinengzhuanhuan($str)
    {
        /** 快速导入果快格式**/
        if (strstr($str, "序列号：")) {
            $str = $str . "\n";
            preg_match_all("#序列号：(.*?)\nIMEI：(.*?)\n#is", $str, $guokuaiarr);
            $xintxt = '';
            foreach ($guokuaiarr[1] as $vkey => $vvv) {
                //去掉回车
                $guokuaiarr[1][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[1][$vkey]);
                $guokuaiarr[2][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[2][$vkey]);
                $xintxt .= $guokuaiarr[1][$vkey] . "\t" . $guokuaiarr[2][$vkey] . "\r\n";
            }
            if ($xintxt) {
                $str = $xintxt;
            }

        }elseif (strstr($str, "Repair ID")) {
            /** 快速导入快速查码**/
            $str = $str . "\n";
            preg_match_all("#IMEI:(.*?)\nSerial Number:(.*?)\nRepair ID:(.*?)\n#is", $str, $guokuaiarr);
            $xintxt = '';
            foreach ($guokuaiarr[1] as $vkey => $vvv) {
                //去掉回车
                $guokuaiarr[1][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[1][$vkey]);
                $guokuaiarr[2][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[2][$vkey]);
                $guokuaiarr[3][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[3][$vkey]);
                $xintxt .= $guokuaiarr[1][$vkey] . "\t" . $guokuaiarr[2][$vkey] .  "\t" . $guokuaiarr[3][$vkey] . "\r\n";
            }
            if ($xintxt) {
                $str = $xintxt;
            }
        }  elseif (strstr($str, "快速查码")) {
            /** 快速导入快速查码**/
            $str = $str . "\n";
            preg_match_all("#imei：(.*?)\nsn：(.*?)\n#is", $str, $guokuaiarr);
            $xintxt = '';
            foreach ($guokuaiarr[1] as $vkey => $vvv) {
                //去掉回车
                $guokuaiarr[1][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[1][$vkey]);
                $guokuaiarr[2][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[2][$vkey]);
                $xintxt .= $guokuaiarr[1][$vkey] . "\t" . $guokuaiarr[2][$vkey] . "\r\n";
            }
            if ($xintxt) {
                $str = $xintxt;
            }
        } elseif (strstr($str, "串号序列号互查")) {
            /** 快速导入快速查码**/
            $str = $str . "\n";
            preg_match_all("#IMEI:(.*?)\n序列号:(.*?)\n#is", $str, $guokuaiarr);
            $xintxt = '';
            foreach ($guokuaiarr[1] as $vkey => $vvv) {
                //去掉回车
                $guokuaiarr[1][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[1][$vkey]);
                $guokuaiarr[2][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[2][$vkey]);
                $xintxt .= $guokuaiarr[1][$vkey] . "\t" . $guokuaiarr[2][$vkey] . "\r\n";
            }
            if ($xintxt) {
                $str = $xintxt;
            }
        } elseif (strstr($str, "Serial Number : ")) {
            /** info**/
            $str = $str . "\n";
            preg_match_all("#IMEI : (.*?)\n.*?Serial Number :(.*?)\n#is", $str, $guokuaiarr);
            $xintxt = '';
            foreach ($guokuaiarr[1] as $vkey => $vvv) {
                //去掉回车
                $guokuaiarr[1][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[1][$vkey]);
                $guokuaiarr[2][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[2][$vkey]);
                $xintxt .= $guokuaiarr[1][$vkey] . "\t" . $guokuaiarr[2][$vkey] . "\r\n";
            }
            if ($xintxt) {
                $str = $xintxt;
            }

        } elseif (strstr($str, "Serial: ")) {
            /** i-imei**/
            $str = $str . "\n";
            preg_match_all("#IMEI: (.*?)\n.*?Serial: (.*?)\n#is", $str, $guokuaiarr);
            $xintxt = '';
            foreach ($guokuaiarr[1] as $vkey => $vvv) {
                //去掉回车
                $guokuaiarr[1][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[1][$vkey]);
                $guokuaiarr[2][$vkey] = str_replace(array("\r\n", "\r", "\n"), "", $guokuaiarr[2][$vkey]);

                $xintxt .= $guokuaiarr[1][$vkey] . "\t" . $guokuaiarr[2][$vkey] . "\r\n";
            }
            if ($xintxt) {
                $str = $xintxt;
            }
        }

        //开始
        $str = preg_replace('#	#', " ", $str); //长条替换空格
        $str = preg_replace('#  #', " ", $str); //双空格替换单空格
        $str = str_replace("\t", " ", $str);

        //开始按行分割
        $lines = explode("\r\n", $str); //将多行数据分开
  

        $i = 0;
        $wx = [];
        //转换成数组
        foreach ($lines as $val) {
    
            if (isset($val) and $val) {
                $val=trim($val);       
      
                $snsn_xx=[];//修复添加的时候自动编辑别的维修ID
                if (strpos($val, "\t") > 0) {
                    $snsn_xx = explode("	", $val);
                } elseif (strpos($val, " ") > 0) {
     
                    $snsn_xx = explode(" ", $val);
                } else {
                  
                    $snsn_xx[] = $val;
                }
                if (!$snsn_xx) {
                    return '';
                }

                //自动匹配 序列号 等信息
                for ($x = 0; $x < count($snsn_xx); $x++) {
                    if (preg_match("#^[A-Za-z][A-Za-z0-9]{11}$#", trim($snsn_xx[$x]))) {
                        $wx[$i]['sn'] = trim(strtoupper($snsn_xx[$x]));
                        $wx[$i]['sn_hou'] = substr($wx[$i]['sn'], -4);
                    }
                    if (preg_match("#^(35|99|01)\d{12,13}$#", trim($snsn_xx[$x]))) {
                        $wx[$i]['imei'] = trim($snsn_xx[$x]);
                    }
                    
                    //判断维修ID  && isset($wx[$i]['sn']) 不判断是否需要序列号
                    if (preg_match("#^(A|G|R|D|N|a|g|r|d|n)\d{9}$#", trim($snsn_xx[$x])) ) {
                        //添加支持多维修ID导入
                        if (@empty($wx[$i]['wid'])) {
                            $wx[$i]['wid'] = '';
                        }
                        $wx[$i]['wid'] .= trim(strtoupper($snsn_xx[$x])) . ' ';
                    }
                    //判断邮箱和密码
                    if (stristr($snsn_xx[$x], "@")) {
                        $wx[$i]['appid'] = $snsn_xx[$x];
                        $wx[$i]['apppass'] = $snsn_xx[$x + 1];
                    }
                }
                if (isset($wx[$i]['wid'])) {
                    $wx[$i]['wid'] = trim($wx[$i]['wid']);
                }
                //多维修ID
            }
            $i++;
        }
        //重新遍历数组 
        foreach ($wx as $k=>&$v){
                 //使  得当只有维修ID 没有sn的时候不能添加
            if(isset($v['wid'])&&empty($v['sn']))  unset($wx[$k]);
          
        }
        return $wx;
    }

    /**
     * 根据ID分类 分类各种下单状态，成功率 颜色？
     * @return mixed
     */
    public static function getListSn($id, $data = null)
    {
        //生成所有成功失败的wxid
        /**
         * 是否维修 是否申请服务  是否。。。等
         */
        if ($data != null) {
            $list = $data;
        } else {
            $list = self::where('did', $id)->column('id,sn,wid,wx_ztai_id,sn_hou');
        }
        $wxid_arr = Cache::get('wxid', '');
        $xhao_arr = Cache::get('xhao', '');
        foreach ($wxid_arr as $k => $v) {
            if ($v[3] == 8) {
                $tj_wxidarr['ok'][] = $k;
            }

            if ($v[3] == 3) {
                $tj_wxidarr['no'][] = $k;
            }

        }
  

        $list_arr['zong'] = count($list);
        $list_arr['chenggong'] = 0;
        $list_arr['jubao'] = 0;
        //以下还没开发好
        $list_arr['xiadan'] = 0;
        $list_arr['xinghaoarr'] = [];

        foreach ($list as $v) {
            //型号数组
            if (!isset($list_arr['xinghaoarr'][$v['sn_hou']])) {
                $list_arr['xinghaoarr'][$v['sn_hou']]['s'] = 0;
                $list_arr['xinghaoarr'][$v['sn_hou']]['x'] = isset($xhao_arr[$v['sn_hou']]) ?$xhao_arr[$v['sn_hou']]:'';
                
            } else {
                $list_arr['xinghaoarr'][$v['sn_hou']]['s'] ++;
            }

            //成功
            if (in_array($v['wx_ztai_id'], $tj_wxidarr['ok'])) {
                $list_arr['chenggong']++;
            }
            if (in_array($v['wx_ztai_id'], $tj_wxidarr['no'])) {
                $list_arr['jubao']++;
            }
        }

        //[ error ] [2]Division by zero 报错出现在这里  
        //已经修复/0报错  
        if(($list_arr['chenggong'] + $list_arr['jubao'])==0)
        {
            //成功和失败都是0 就是100%
            $list_arr['chenggong100'] =0;
            $list_arr['shibai100'] =0;
        }elseif($list_arr['chenggong']==0||$list_arr['jubao'] ==0){

            $list_arr['chenggong100']  = $list_arr['chenggong']==0 ?round($list_arr['chenggong'] / ($list_arr['chenggong'] + $list_arr['jubao']) * 100, 2):'0';
            $list_arr['shibai100']  = $list_arr['jubao']==0 ?round($list_arr['jubao'] / ($list_arr['chenggong'] + $list_arr['jubao']) * 100, 2):'0';
        }else{
            $list_arr['chenggong100']  = round($list_arr['chenggong'] / ($list_arr['chenggong'] + $list_arr['jubao']) * 100, 2);
            $list_arr['shibai100']  = round($list_arr['jubao'] / ($list_arr['chenggong'] + $list_arr['jubao']) * 100, 2);
        }
        return $list_arr;
    }

}
