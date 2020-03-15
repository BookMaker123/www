<?php
namespace app\api\controller;

use think\Controller;
use think\Db;
use app\common\Nb;

use app\common\Jindu as Jinducheck;


use EasyWeChat\Factory;

class Zdaye extends Controller
{


    public function checkus($wid=null,$sn=null,$ci=0)
    {
        $iparr=self::tiquip();
        $arr['time'] = 5;
       $arr['daili'] = $iparr['ip'];
       $arr['header'] = array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        "Accept-Language: zh-CN",
        "Connection: keep-alive",
        "Content-Type: application/json",
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.80 Safari/537.36',
        'Origin: https://mysupport.apple.com',
        'Host:mysupport.apple.com',
        'Origin:https://mysupport.apple.com',
        'Pragma:no-cache',
        'Referer: https://mysupport.apple.com/',
    );
    $arr['cookie']='s_fid=21F8C51F71D44F71-08B9E3F815EC7A7D; s_vi=[CS]v1|2E5D8B0E852E6D6E-40000C30800000F6[CE]; SA-Locale=zh_CN; dssf=1; dssid2=efe2bfc8-3a34-416e-a204-7d5cf57ed0dc; optimizelyEndUserId=oeu1556286333614r0.4545094627538273; optimizelyBuckets=%7B%7D; pxro=1; xp_ci=3z1v86T2zFmUz4rBzBBRzsthgfbGn; accs=o; as_affl=p238%7C%7Cmtid%3A%3A18707vxu38484%26mnid%3A%3A16ZSDHcN8-dc_mtid_18707vxu38484_pcrid_30634057292_%26cid%3A%3Aaos-CN-kwba-Brand%26%7C%7C20190709_021536; optimizelySegments=%7B%22341793217%22%3A%22direct%22%2C%22341794206%22%3A%22false%22%2C%22341824156%22%3A%22gc%22%2C%22341932127%22%3A%22none%22%2C%22381100110%22%3A%22gc%22%2C%22381740105%22%3A%22none%22%2C%22382310071%22%3A%22search%22%2C%22382310072%22%3A%22false%22%2C%22382810055%22%3A%22direct%22%2C%22382810056%22%3A%22false%22%2C%22382870052%22%3A%22gc%22%2C%22382950046%22%3A%22none%22%7D; POD=cn~zh; s_vnum_n2_cn=4%7C29; s_vnum_n2_us=4%7C69; mbox=PC#022202053fe844aaa36555b597260559.22_24#1627660516|session#fa09e356c4494da4a2b38d6f098e8a3d#1564417576; sh_mysupport=2f6b04b8ec7c0653edb6674fae74ba32; SA_SESSIONID=90818ba7-0678-46c0-81cc-475d886a851f; MS_AFFINITY=mdn-1; JSESSIONID=node0ak8ryk4x247410mvp2f7j7fb32823120.node0';

        $arr["url"] = "https://mysupport.apple.com/api/v1/supportaccount/getRepairStatus?repairId=$wid&serialNumber=$sn&selectedLocale=zh_CN"; //en_US zh_CN
        $t1 = microtime(true);

    $fanhui = Jinducheck::_curl($arr);

      $t2 = microtime(true);
      $miao=round($t2-$t1,3);
 
      if ($fanhui['httpcode'] == 200 || $fanhui['httpcode'] == '200') {
           //  Db::name('ip_zdaye')->where('id', $iparr['id'])->update(['miao'=>$miao,'time'=>time()]);
             return $fanhui['html'];
      }elseif($fanhui['httpcode'] == 0 ){       
             //代理错误 执行三次
             //保存错误
            // Db::name('ip_zdaye')->where('id', $iparr['id'])->update(['ztai'=>2,'time'=>time()]);
             if($ci<3){
                 $ci++;
                return $this->checkus($wid,$sn,$ci);
             }
         }  
    }
    
    public static function tiquip()
    {
        $ipdata = Db::name('ip_zdaye')->where('ztai',0)->where('timeout', '>',time()-20)->order('tiqutime', 'ACS')->find();
        if(!$ipdata){
           $fanhui = Jinducheck::_curl(['url'=>'http://www.zdopen.com/ShortProxy/GetIP/?api=201907191708548517&akey=a939ad68c06a804a&adr=%E7%94%B5%E4%BF%A1&order=1&type=3']);
           if (strstr($fanhui['html'], 'proxy_list') ){
                $arr=json_decode($fanhui['html'],1);
                foreach ($arr['data']['proxy_list'] as $v){
                    $vv[]=['ip'=>$v['ip'].':'.$v['port']  ,'adr'=>$v['adr'] ,'timeout'=>($v['timeout'] +time() )  ];
                }
                Db::name('ip_zdaye')->insertAll($vv);         
            }
            $ipdata = Db::name('ip_zdaye')->where('ztai',0)->where('timeout', '>',time()-20)->order('tiqutime', 'ACS')->find();
        }
        //更新下数据集
        Db::name('ip_zdaye')->where('id', $ipdata['id'])->update(['tiqutime'=>time()]);
        return $ipdata;
    }




    /**
     * 提取站大爷代理api 10秒钟获取一次
     */
    public function t()
    {
        //删除过期ip
        $shanconut= Db::name('ip_zdaye')->where('timeout', '<',time()-10)->delete();

        $fanhui = Jinducheck::_curl(['url'=>'http://www.zdopen.com/ShortProxy/GetIP/?api=201907191708548517&akey=a939ad68c06a804a&adr=%E7%94%B5%E4%BF%A1&order=1&type=3']);
        if (strstr($fanhui['html'], 'proxy_list') ){
             $arr=json_decode($fanhui['html'],1);
             foreach ($arr['data']['proxy_list'] as $v){
                 //判断该ip是否存在
                 if(!Db::name('ip_zdaye')->where('ip', $v['ip'].':'.$v['port'] )->find())
                 {
                    $vv[]=['ip'=>$v['ip'].':'.$v['port']  ,'adr'=>$v['adr'] ,'timeout'=>($v['timeout'] +time() )  ];
                 }
            }
            $cout= Db::name('ip_zdaye')->insertAll($vv);         
            return '[新增]'.$cout .'[过期]'.$shanconut;
         }else{
            return '提取失败，删除过期：'.$shanconut;
         }
    }
    /**
     * 提取可用 没有监控是否被封
     */
    public function tapi(){
        $ipdata = Db::name('ip_zdaye')->where('ztai',0)->where('timeout', '>',time()-10)->order('tiqutime', 'ACS')->select();
  
        $txt='';
        foreach($ipdata as $k =>  $v){
            $txt.=$v['ip'];
            if($v != end($ipdata)) {
                $txt.="\r\n";
            }
        }
        return $txt;

    }
    
}