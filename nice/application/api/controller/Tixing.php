<?php
namespace app\api\controller;

use app\common\Nb;
use think\Controller;
use think\Db;
use think\facade\Cache;
use app\vip\model\Lists as ListsModel;
use app\vip\model\Listsn as ListsnModel;
use EasyWeChat\Factory;

class Tixing extends Controller
{

    private $wxapp; //全局变量

    /**
     * 初始化
     */
    public function __construct()
    {
        $config = Config('wechat.');
        $this->wxapp = Factory::officialAccount($config);
        parent::__construct(); //必须要获取这个 isGet才不会报错

    }
    public function index()
    { 

        
    }


    public function ztai()
    {
        $map['jkztai'] = 1; //只获取监控的
        $ti_list = Db::name("sn_listsn")->where($map)->where('tx_xiadan', 1)->find();
        if (!$ti_list) return '没有要提醒的';
        $user = Db::name("admin_user")->where('id', $ti_list['uid'])->find();
        if (!$user || !$user['openid']) {
            //没有绑定微信 取消监控 不在提醒
            $data_list_jk = ListsnModel::where('uid', $ti_list['uid'])->where('did', $ti_list['did'])->where('jkztai', 1)->select();
            if ($data_list_jk) {
                foreach ($data_list_jk as $v)
                    $jk_list[] = ['id' => $v['id'], 'jkztai' => 0];

                $pl_shan_jk = new ListsnModel;
                $plbaocun = $pl_shan_jk->saveAll($jk_list);
                if ($plbaocun)
                    return "用户没有绑定微信，取消所有监控成功！";
                else
                    return "用户没有绑定微信，取消所有监控失败！";
            }
        }

        //获取列表资料
        $dingdan = Db::name("sn_lists")->where('id', $ti_list['did'])->find();

        $ti_list2 = Db::name("sn_listsn")->where($map)->where('tx_xiadan', 1)->where('did', $ti_list['did'])->column('sn,imei,wx_ztai_id', 'id');
        if(!$ti_list2)  return "没有";

        $tishi_text = '';
        $wxid_arr = Cache::get('wxid', '');
        $biandongii = 0;
        foreach ($ti_list2 as $v) {
            //设置批量保存数组 只提醒一次
            $plbaoarr[] = ['id' => $v['id'], 'tx_xiadan' => 0];
            //便利需要提醒的 结果
            if (!$v['imei'] || $v['imei'] == '' || $v['imei'] == null)
                $v['imei'] = $v['sn'];
            //维修ID重新编辑
            if ($v['wx_ztai_id'] && @$wxid_arr[$v['wx_ztai_id']])
                $leixing = $wxid_arr[$v['wx_ztai_id']][1];
            else
                $leixing = $v['wx_tishi'];
            $tishi_text .= $v['imei'] . ' ' . $leixing . "\r";
            $biandongii++;
        }
        $liebiao = $user['username']."\r"."订单：[" . $dingdan['dname'] . "]" . $dingdan['jieshao'] . '状态更新：' . $biandongii . '台';

        //发送微信
        $t = $this->wxapp->template_message->send([
            'touser' => $user['openid'],
            'template_id' => 'b0XMO7rzV3j5GcofXcevs9q819UG2AQYn60wFz4Ou7E',
            'url' => "http://www.aiguovip.com/vip/listsn/index/id/" . $ti_list['did'] . ".html",
            'data' => [
                "first" => [$liebiao, "#5599FF"],
                "keyword1" => ['维修下单状态提醒', "#0000FF"],
                "keyword2" => [date("Y/m/d H:i:s"), "#0000FF"],
                "remark" => ["\r" . $tishi_text, "#5599FF"],
            ],
        ]);
        if (@$t['errmsg'] == 'ok') {
            //发送成功 就编辑全部 发送不成功不编辑！
            $Save = new ListsnModel;
            $bao = $Save->saveAll($plbaoarr);
            return $liebiao."\r".$tishi_text;
        } else {
           if($t['errcode']=='43004'){
               //没有关注爱果
               $user = Db::name("admin_user")->where('id', $ti_list['uid'])->update(['openid' => null]);

           }
           print_r($t);
            return '提醒发送失败！';
        }
        return;
    }

    public function jindu()
    {
        $map['jkztai'] = 1; //只获取监控的
        $ti_list = Db::name("sn_listsn")->where($map)->where('tx_jindu', 1)->find();

        if (!$ti_list) return '没有要提醒的';

        $user = Db::name("admin_user")->where('id', $ti_list['uid'])->find();
        if (!$user || !$user['openid']) {
            //没有绑定微信 取消监控 不在提醒
            $data_list_jk = ListsnModel::where('uid', $ti_list['uid'])->where('did', $ti_list['did'])->where('jkztai', 1)->select();
            if ($data_list_jk) {
                foreach ($data_list_jk as $v)
                    $jk_list[] = ['id' => $v['id'], 'jkztai' => 0];

                $pl_shan_jk = new ListsnModel;
                $plbaocun = $pl_shan_jk->saveAll($jk_list);
                if ($plbaocun)
                    return "用户没有绑定微信，取消所有监控成功！";
                else
                    return "用户没有绑定微信，取消所有监控失败！";
            }
        }

        //获取列表资料
        $dingdan = Db::name("sn_lists")->where('id', $ti_list['did'])->find();

        $ti_list2 = Db::name("sn_listsn")->where($map)->where('tx_jindu', 1)->where('did', $ti_list['did'])->column('sn,imei,wx_ztai_id,wid', 'id');
        if(!$ti_list2)  return "没有";

        $tishi_text = '';
        $wxid_arr = Cache::get('wxid', '');
        $biandongii = 0;
        foreach ($ti_list2 as $v) {
            //设置批量保存数组 只提醒一次
            $plbaoarr[] = ['id' => $v['id'], 'tx_jindu' => 0];
            //便利需要提醒的 结果
            if (!$v['imei'] || $v['imei'] == '' || $v['imei'] == null)
                $v['imei'] = $v['sn'];
            //维修ID重新编辑
            if ($v['wx_ztai_id'] && @$wxid_arr[$v['wx_ztai_id']])
                $leixing = $wxid_arr[$v['wx_ztai_id']][1];
            else
                $leixing = @$v['wx_tishi'];
            $tishi_text .= $v['imei'] . ' ' . $leixing . "\r";
            $biandongii++;
        }

        $liebiao = $user['username']."\r"."订单：[" . $dingdan['dname'] . "]" . $dingdan['jieshao'] . '进度更新：' . $biandongii . '台';

        //发送微信
        $t = $this->wxapp->template_message->send([
            'touser' => $user['openid'],
            'template_id' => 'IrpUTTVBS5pYIue9URaAcFd1__doI_WiHAcUYBkbM94',
            'url' => "http://www.aiguovip.com/vip/listsn/index/id/" . $ti_list['did'] . ".html",
            'data' => [
                "first" => [$liebiao, "#5599FF"],
                "keyword1" => ['苹果维修进度更新', "#0000FF"],
                "keyword2" => [date("Y/m/d H:i:s"), "#0000FF"],
                "remark" => ["\r" . $tishi_text, "#5599FF"],
            ],
        ]);
        if (@$t['errmsg'] == 'ok') {
            //发送成功 就编辑全部 发送不成功不编辑！
            $Save = new ListsnModel;
            $bao = $Save->saveAll($plbaoarr);
            return $liebiao."\r".$tishi_text;
        } else {
           if($t['errcode']=='43004'){
               //没有关注爱果
               $user = Db::name("admin_user")->where('id', $ti_list['uid'])->update(['openid' => null]);

           }
           print_r($t);
            return '提醒发送失败！';
        }
        return;
    }
}
