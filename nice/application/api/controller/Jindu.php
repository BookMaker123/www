<?php

namespace app\api\controller;

use think\Controller;
use think\Db;
use app\common\Jindu as Jinducheck;

class Jindu extends Controller
{

    private $wxapp; //全局变量

    /**
     * 初始化
     */
    public function __construct()
    {
        parent::__construct();
    }
    public function checkus($wid=null,$sn=null){

    }

    public function checkapi($wid=null,$sn=null)
    {
        $arr['url']= Jinducheck::getwxUrl2019();
        $arr['url'] = str_replace('#WXID', $wid, $arr['url']);
        $arr['url'] = str_replace('#WXSN', $sn, $arr['url']);
        $arr['header'] = array(
            "Accept-Language: zh-CN",
        );
        $fanhui = Jinducheck::_curl($arr);


        if ($fanhui['httpcode'] != 200) {
            return $fanhui['html'];
        }
        return $fanhui = $fanhui['html'];
    }

    /**
     * 进度保存API 用于软件 和老系统保存
     */
    public function save()
    {

        if ($this->request->isPost()) {
            $data = $this->request->post();
            // $data['wid']
            //  $data['sn']
            // $data['uid']
            // $data['json']
            $fanhui_arr = json_decode($data['json'], 1);
            $repairMetaData = $fanhui_arr['data']['repairMetaData'];
            //判断是否存在 已经存在不保存
            if (!Db::name('sn_jindu')->where('wid', $data['wid'])->where('wx_ztai_id', $repairMetaData['sapStatusId'])->find()) {
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
                $warr_jindu['uid'] = -$data['uid'];
                $warr_jindu['sn'] = $data['sn'];
                $warr_jindu['sn_hou'] = substr($data['sn'], '-4');
                $warr_jindu['wid'] = $data['wid'];
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
    }
}
