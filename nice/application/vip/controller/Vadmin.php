<?php
namespace app\vip\controller;

use think\Controller;
use think\Db;
use think\facade\Cache;
use app\vip\model\Config as ConfigModel;
use app\vip\model\User as UserModel;
use app\vip\model\Jindu as JinduModel;
use function GuzzleHttp\json_decode;
use EasyWeChat\Factory;
use think\Console;


class Vadmin extends AdminController
{

    /**
     * 初始化
     * Lists constructor.
     */
    public function __construct()
    {
        parent::__construct();
        //必须要UID1才可以进入
        if (UID != 1) {
            $this->error('错误');
            exit('没有权限');
        }
        $config = Config('wechat.');
        $this->wxapp = Factory::officialAccount($config);
    }

    /**
     * 首页
     * @return mixed
     */
    public function index()
    {

    }

    public function chaxun()
    {

    }

    public function j()
    {
        $lists= Db::name('sn_listsn')->distinct(true)->field('uid')->select();
        echo count($lists);



    }

    /**
     * 型号分类     //好像没说明用 所以没有继续开发
     * @return mixed
     */
    public function log()
    {
        $map = $this->getMap();

        $order = $this->getOrder('hou asc');
        $order = '';
        $data_list = Db::name('sn_log')->order($order)->where($map)->select();
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * 型号分类     //好像没说明用 所以没有继续开发
     * @return mixed
     */
    public function tongji($r = null)
    {
        if ($r == null) $r = date("Y-m-d", time());

        $map = $this->getMap();
        $data_list = Db::name('sn_tongji')->where('cri', '=', $r)->where($map)->order('ztaiok_num DESC,jinduok_num DESC')->select();
        $ztaiok_num_z = 0;
        $ztaino_num_z = 0;
        $jinduok_num_z = 0;
        $jinduno_num_z = 0;
        foreach ($data_list as $v) {
            $ztaiok_num_z = $ztaiok_num_z + $v['ztaiok_num'];
            $ztaino_num_z = $ztaino_num_z + $v['ztaierror_num'];
            $jinduok_num_z = $jinduok_num_z + $v['jinduok_num'];
            $jinduno_num_z = $jinduno_num_z + $v['jinduerror_num'];
        }
        $zarr = [$ztaiok_num_z, $ztaino_num_z, $jinduok_num_z, $jinduno_num_z];
        $this->assign('zarr', $zarr);
        $this->assign('data_list', $data_list);
        return $this->fetch();

    }

    /**
     * 型号分类     //好像没说明用 所以没有继续开发
     * @return mixed
     */
    public function ip()
    {
        $data_iparr['1'] = Db::name('ip_us')->where("ztai", 1)->count();
        $data_iparr['2'] = Db::name('ip_us')->where("ztai", 2)->count();
        $data_iparr['3'] = Db::name('ip_us')->where("ztai", 3)->count();
        $data_iparr['4'] = Db::name('ip_us')->where("ztai", 4)->count();
        $data_iparr['0'] = Db::name('ip_us')->where("ztai", 0)->count();

        $this->assign('data_iparr', $data_iparr);

        $map = $this->getMap();
        $order = $this->getOrder('ok desc');$order='';

        $data_list = Db::name('ip_us')->order('ok desc,error desc')->where($map)->paginate();
        $this->assign('page', $data_list->render());
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    public function user($user=null)
    {

        $map = $this->getMap();
        $order = $this->getOrder('ok desc');$order='';
        if($user)$map['username']=$user;
        $data_list = UserModel::order('id desc')->where($map)->paginate();


        $this->assign('page', $data_list->render());
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * 积分添加
     */
    public function editvip($id, $username,$vip)
    {


        if (UserModel::where('id', $id)->where('username', $username)->update(['vip' => $vip])    ) {
            $weixin_user = UserModel::where('id', $id)->find();
            //微信提示
            //发送微信
            $t = $this->wxapp->template_message->send([
                'touser' => $weixin_user['openid'],
                'template_id' => '3RhwKIDiSiZMz87Rp2s2EpW3Zz6KLo8M8a7r3NZzf5w',
                'url' => "https://www.aiguovip.com/",
                'data' => [
                    "first" => ['会员等级', "#5599FF"],
                    "keyword1" => ['会员等级：'.$vip, "#0000FF"],
                    "keyword2" => ['会员等级：'.$vip, "#0000FF"],
                    "remark" => ["\r" . '感谢支持！', "#5599FF"],
                ],
            ]);
            $this->success('会员等级成功');
        } else {
            $this->error('会员等级失败');
        }
    }
    /**
     * 修改密码
     */
    public function edituser($id, $username,$password)
    {

        $UserModel = new UserModel();
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            // 如果没有填写密码，则不更新密码
            if (empty($data['password']) || $data['password'] == '') {
                unset($data['password']);
            }

            if ($user = $UserModel->allowField(['nickname', 'email', 'password', 'mobile','avatar'])->update($data)) {
                return $this->success('修改成功');
            } else {
                return $this->error('修改失败');
            }
        }
    }
    /**
     * 系统设置
     * @return mixed
     */
    public function config()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            foreach($data as $k => $v){

                ConfigModel::where('name', $k)->update(['value' => $v]);
                //xhao--wxapi-
                if($k=='ztai')$k='wxid';
                Cache::rm($k); //删除缓存临时测试
            }
            cache('apple_config', null);//清空下缓存 下次直接获取最新的

            $this->success("编辑成功" );
        }else{

            $data_list = Db::name('admin_config')->select();
            $data_list2=[];
            foreach ($data_list as $v){
                $data_list2[$v['name']]=$v;
            }

            $this->assign('data_list', $data_list2);
            return $this->fetch();
        }
    }


    /**
     * 注册用户
     * @return mixed
     */
    public function adduser()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['role'] = 3;//注册的权限
            $data['kh_uid'] = UID;//随便定义个
            $data['nickname']=$data['username'];
            $data['status']=1;
            // 验证
            $result = $this->validate($data, 'User');
            // 验证失败 输出错误信息
            if(true !== $result) return $this->error($result);



            if ($user = UserModel::create($data)) {
                return $this->success('新增成功', url('adduser'));
            } else {
                return $this->error('新增失败');
            }
        }else{

            return $this->fetch();
        }
    }

    public function jindu($qian=null,$uid=null)
    {
        $data_list = Db::name('sn_jindu')->group('sn_hou')->column('sn_hou');
        $wxid_arr = Cache::get('wxid', '');
        $xhao_arr = Cache::get('xhao', '');
        $this->assign('wxid', $wxid_arr);
        $this->assign('xhao', $xhao_arr);

        //不存在的
        $bughou4=[];
        foreach ($data_list as $v){
            if(isset($xhao_arr[$v])) {
            }else{
                $sn=Db::name('sn_jindu')->where('sn_hou',$v)->find();
                $bughou4[]= $v. ' '.$sn['sn'];
            }
        }
        $data_bugwxid = Db::name('sn_jindu')->group('wx_ztai_id')->column('wx_ztai_id');
        $bugwxid=[];
        foreach ($data_bugwxid as $v){
            if(isset($wxid_arr[$v])) {
            }else{
                $sn=Db::name('sn_jindu')->where('wx_ztai_id',$v)->find();
                $bugwxid[]= $v. ' '.$sn['sn']. ' '.$sn['wid'];







            }

        }
        $map = $this->getMap();
        $mapjson= json_encode($map);
        $this->assign('mapjson', $mapjson);
        $order = $this->getOrder('id desc');
        if($qian)
            $map[]=['sn','like',"$qian%"];
        if($uid)
            $map[]=['uid','=',$uid];

        //->alias('a')->Join('admin_user b', 'a.uid = b.id')->field('a.*,b.username')
        $data_list2 = Db::name('sn_jindu')->where($map)->order($order)->paginate(1000);

        $this->assign('page', $data_list2->render());
        $this->assign('data_list', $data_list2);
        $this->assign('bughou4', $bughou4);
        $this->assign('bugwxid', $bugwxid);
        return $this->fetch();
    }

    public function dizhi($uid=null)
    {
        $guojia='阿富汗	AFG
        阿联酋	ARE
        阿根廷	ARG
        澳大利亚	AUS
        奥地利	AUT
        阿塞拜疆	AZE
        比利时	BEL
        孟加拉国	BGD
        保加利亚	BGR
        白俄罗斯	BLR
        巴西	BRA
        文莱	BRN
        加拿大	CAN
        瑞士	CHE
        智利	CHL
        中国	CHN
        古巴	CUB
        捷克	CZE
        德国	DEU
        丹麦	DNK
        埃及	EGY
        西班牙	ESP
        埃塞俄比亚	ETH
        卢森堡	KUX
        芬兰	FIN
        法国	FRA
        英国	GBR
        格鲁吉亚	GEO
        希腊	GRC
        中国香港	HKG
        匈牙利	HUN
        印度尼西亚	IDN
        印度	IND
        爱尔兰	IRL
        伊朗	IRN
        伊拉克	IRQ
        以色列	ISR
        意大利	ITA
        日本	JPN
        哈萨克斯坦	KAZ
        柬埔寨	KHM
        韩国	KOR
        科威特	KWT
        老挝	LAO
        黎巴嫩	LBN
        利比亚	LBY
        斯里兰卡	LKA
        中国澳门	MAC
        墨西哥	MEX
        马其顿	MKD
        马耳他	MLT
        缅甸	MMR
        蒙古	MNG
        毛里求斯	MUS
        马来西亚	MYS
        尼日尔	NER
        尼日利亚	NGA
        荷兰	NLD
        挪威	NOR
        尼泊尔	NPL
        新西兰	NZL
        巴基斯坦	PAK
        秘鲁	PER
        菲律宾	PHL
        波兰	POL
        朝鲜	PRK
        葡萄牙	PRT
        巴勒斯坦	PST
        卡塔尔	QAT
        罗马尼亚	ROM
        俄罗斯	RUS
        沙特阿拉伯	SAU
        新加坡	SGP
        斯洛伐克	SVK
        斯洛文尼亚	SVN
        瑞典	SWE
        叙利亚	SYR
        泰国	THA
        中国台湾	TWN
        乌克兰	UKA
        美国	USA
        乌兹别克斯坦	UZB
        委内瑞拉	VEN
        越南	VNM
        南斯拉夫	YUG
        南非	ZAF
        津巴布韦	ZWE';
        $guojiaa=explode("\r\n",$guojia);
        foreach ($guojiaa as $v){
            $g_v= explode("	",$v);
            $guojia_arr[$g_v[1]]=$g_v[0];
        }
        $this->assign('guojia_arr', $guojia_arr);
        $wxid_arr = Cache::get('wxid', '');
        $xhao_arr = Cache::get('xhao', '');
        $this->assign('wxid', $wxid_arr);
        $this->assign('xhao', $xhao_arr);


        $map = $this->getMap();
        $mapjson= json_encode($map);
        $this->assign('mapjson', $mapjson);
        if($uid)
            $map[]=['uid','=',$uid];

        $map[]=['wid','like','R%'];
        //->alias('a')->Join('admin_user b', 'a.uid = b.id')->field('a.*,b.username')
        $data_list2 = Db::name('sn_jindu')->where($map)->whereNotNull('addressType')->order('id desc')->paginate(2000);
        $this->assign('page', $data_list2->render());
        $this->assign('data_list', $data_list2);



        return $this->fetch();






    }
    public function jindu2($qian=null,$uid=null)

    {
        $data_list2 = Db::name('sn_jindu')->paginate(10000);

        foreach ($data_list2 as $v){
            if (isset($v['sn']) && isset($v['wid']) ) {
                $arrsnwid[] = $v['sn'].'_'.$v['wid'];
            }
        }

        $INcunzai = Db::name('jiankong_wid')->whereIn('snwid',$arrsnwid)->column('id', 'snwid');
        $arrsave=[];
        $create_time=time();
        foreach ($data_list2 as $v) {
            //多维的时候自动分割
            if(isset($v['sn']) && isset($v['wid']) ){
                $arrwid= explode(' ',$v['wid']);
                foreach($arrwid as $vvvv){
                    $snwid=$v['sn'].'_'.$vvvv;
                    if (isset($v['sn']) && isset($v['wid']) && !array_key_exists($snwid,$INcunzai) )  {
                        $arrsave[] = ['snwid'=>$snwid,'uid'=> $v['uid'],'create_time'=>$create_time];
                    }
                }
            }

        }
        print_r($arrsave);
        if($arrsave){
            $bao=Db::name('jiankong_wid')->insertAll($arrsave);
            if($bao){
                echo '保存成功'.$bao;
            }
        }
    }
    public function xiufufu($qian=null,$uid=null)
    {
        $data_list2 = Db::name('sn_jindu')->where('uid','<',1)->paginate(100);

        foreach ($data_list2 as $v){
            $earr[]=['uid'=>$v['uid']*-1,'id'=>$v['id'] ];
        }
        // print_r($earr);
        $user = new JinduModel;

        $qq = $user->saveAll($earr);
        if($qq){
            return $this->success('编辑完成');

        }
    }

    public function listsn($qian=null,$uid=null,$bao_lei=null)

    {

        $wxid_arr = Cache::get('wxid', '');
        $xhao_arr = Cache::get('xhao', '');
        $this->assign('wxid', $wxid_arr);
        $this->assign('xhao', $xhao_arr);


        $map = $this->getMap();
        $mapjson= json_encode($map);
        $this->assign('mapjson', $mapjson);
        $order = $this->getOrder('id desc');
        if($qian)
            $map[]=['sn','like',"$qian%"];
        if($uid)
            $map[]=['uid','=',$uid];

        if($bao_lei)
            $map[] =['bao_lei','=',$bao_lei];
        //->alias('a')->Join('admin_user b', 'a.uid = b.id')->field('a.*,b.username')
        $data_list2 = Db::name('sn_listsn')->where($map)->order($order)->paginate(1000);
        $this->assign('page', $data_list2->render());
        $this->assign('data_list', $data_list2);
        return $this->fetch();

    }

    // 一键清理所有缓存文件 cache，log， temp
    public function clear_cache(){
        $this->clear_log_chache();
        $this->clear_sys_cache();
        $this->clear_temp_ahce();


        $this->success('缓存清理成功');
    }
    // 清除模版缓存 不删除cache目录
    public function clear_sys_cache()
    {
        $dirName ='../runtime/temp';
        $dh = opendir($dirName);
        //循环读取文件
        while ($file = readdir($dh)) {
            var_dump($file);
            if ($file != '.' && $file != '..') {
                $fullpath = $dirName . '/' . $file;
               // echo '删除文件：'.$fullpath.'<br>';
                //判断是否为目录
                if (!is_dir($fullpath)) {
                    //如果不是,删除该文件
                    if (!unlink($fullpath)) {
                        $this->error('无法删除,可能是没有权限!');
                    }
                } else {
                    //如果是目录,递归本身删除下级目录
                    $this->delDir($fullpath);
                }
            }
        }
        $this->success('清除成功');
    }

    // 清除模版缓存 不删除 temp目录
    public function clear_temp_ahce()
    {
        array_map('unlink', glob(TEMP_PATH . DS . '.php'));
        //$this->success('清除成功' );
    }

    // 清除日志缓存 不删出log目录
    public function clear_log_chache()
    {
        $path = glob(LOG_PATH . '/');
        foreach ($path as $item) {
            array_map('unlink', glob($item . DS . '.'));
            rmdir($item);
        }
        //$this->success('清除成功');

    }

}
