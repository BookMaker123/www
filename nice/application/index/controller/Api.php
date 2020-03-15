<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use app\common\Nb;

//状态api接口
class Api extends Controller
{
    public function index()
    {

    }
    //http://aiguo/index/api/getsn?id=1606
    public function getsn($id = null)
    {
        if ($id == null) {
            return json(['code' => 0, 'tishi' => '参数错误001']);
        }
        $data_sn = Db::name('sn_listsn')->field('id,sn,imei,wx_ctime')->find($id);
        if ($data_sn) {
            if ((time() - $data_sn['wx_ctime'] < 600)) {        
                return json(['code' => 0, 'tishi' => 600 - (time() - $data_sn['wx_ctime']) . '秒后再查(状态限制10分钟查一次)']);
            }
            //判断不支持下单的！
            if(isset($data_sn["sn"])&&substr($data_sn["sn"], -4)=="H8TT"){
                return json(['code' => 0, 'tishi' => '不支持该设备查询状态']);
            }
            //还可用做点别的判断 哈哈   

            $arr['code']=1;
            if($data_sn['imei']!=null) $arr['imeiorsn']= $data_sn['imei']; 
            else if($data_sn['sn']!=null) $arr['imeiorsn']= $data_sn['sn'];  
            else  return json(['code' => 0, 'tishi' => 'IMEI或序列号不能为空']);
            //返回ip 自动更换             
            $arr['ip']= Nb::Getip(); 
            return json($arr);
        } else {
            return json(['code' => 0, 'tishi' => '参数错误002']);
        }
    }

    public function postztai($id = null)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post(); 
            Db::name('sn_listsn')->where('id', $id )->update($data);
        }
    }
    public function postip($id = null)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post(); 
            Db::name('ip_us')->update($data);
        }
    }


}
