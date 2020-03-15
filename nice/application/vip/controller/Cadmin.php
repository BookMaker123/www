<?php

namespace app\vip\controller;

use think\Controller;
use think\Db;
use think\facade\Cache;
use EasyWeChat\Factory;
use app\vip\model\User as UserModel;
use think\paginator\driver\Bootstrap4;
use app\vip\controller\Chaxun;


class Cadmin extends AdminController
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

    /**
     * 高级查询设置
     */
    public function config()
    {
        $order = $this->getOrder('paixu asc');
        $order = '';
        $data_list = Db::name('chaxun_config')->order($order)->select();
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    public function configadd()
    {
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证
            $validate = $this->validate($data, 'Cadmin');
            if (true !== $validate) {
                $this->error($validate);
            }
            if (empty($data['editid'])) {
                //添加保存
                if ($Dingid = Db::name('chaxun_config')->strict(false)->insert($data)) {
                    $this->success('添加服务成功', url('cadmin/config', ['id' => $Dingid]));
                } else {
                    $this->error('添加服务失败');
                }
            } else {
                $id = $data['editid'];
                unset($data['editid']);
                if ($Dingid = Db::name('chaxun_config')->where('id', $id)->update($data)) {
                    $this->success('编辑服务成功', url('cadmin/config', ['id' => $Dingid]));
                } else {
                    $this->error('编辑服务失败');
                }
            }
        }
    }

    /**
     * 微信用户
     */
    public function weixinuser()
    {
        $where_str = '';
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $search = $data['name'];
            $where_str = " and (w.nickname like '%" . $search . "%'  OR  u.username like '%" . $search . "%' )";
        }

        $data_list = Db::name('weixin_user')
            ->alias('w')
            ->Join('admin_user u', 'w.openid=u.openid', 'left')
            ->field('w.id,w.headimgurl,w.nickname,w.sex,w.country,w.province,w.city,w.openid,group_concat(u.username) username')
            ->where('1=1' . $where_str)
            ->group('w.openid')
            ->order("w.id desc")
            ->paginate(100);

        $this->assign('page', $data_list->render());
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }


    /**
     * 注册用户
     */
    public function adminuser($sort = null, $sort2 = null, $search = null, $type_odd = null)
    {
        $sort3 = ['paixu' => 'fa fa-sort text-muted', 'paixu_name' => '积分倒序'];
        $sort4 = ['paixu' => 'fa fa-sort text-muted', 'paixu_name' => '冻结积分倒序'];
        $filed = 'u.id';
        $sortes = 'desc';
        $where_str = '';
        $where_str2 = '';
        //排序
        if (!empty($sort)) {
            $sortes = $sort;
            $filed = 'jifen';
            $sort = $sort == 'asc' ? 'desc' : 'asc';
            $sort3['paixu'] = $sort == 'asc' ? 'fa fa-caret-up' : 'fa fa-caret-down';
            $sort3['paixu_name'] = $sort == 'asc' ? '积分顺序' : '积分倒序';
        } elseif (!empty($sort2)) {
            $sortes = $sort2;
            $filed = 'dongjie_jifen';
            $sort2 = $sort2 == 'asc' ? 'desc' : 'asc';
            $sort4['paixu'] = $sort2 == 'asc' ? 'fa fa-caret-up' : 'fa fa-caret-down';
            $sort4['paixu_name'] = $sort2 == 'asc' ? '冻结积分顺序' : '冻结积分倒序';
        }
        //post传参
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $search = $data['searchname'];
        }

        //搜索是否为空
        if (!empty($search)) {
            $where_str = " and (u.nickname like '%" . $search . "%'  OR  u.username like '%" . $search . "%' OR u.openid like '%" . $search . "%')";
        }

        //分类总积分
        //当天的充值总积分
        $start_hour = date('Y-m-d 00:00:00', time());
        $time_day = Db::name('chaxun_jifenjilu')->whereColumn('create_time', '>=', strtotime($start_hour))->where('change_type', 4)->sum('jifenxiaofei');
        //当月的充值总积分
        $start_day = date('Y-m-1 00:00:00', time());
        $time_month = Db::name('chaxun_jifenjilu')->whereColumn('create_time', '>=', strtotime($start_day))->where('change_type', 4)->sum('jifenxiaofei');
        //总冻结积分和总积分
        $data_list2 = Db::name('admin_user')->field('sum(jifen) as sum_jifen,sum(dongjie_jifen) as sum_dongjie_jifen')->select();
        $data_list2[0]['time_day'] = $time_day;
        $data_list2[0]['time_month'] = $time_month;
        $sorts = ['sort' => [$sort, $sort3], 'sort2' => [$sort2, $sort4], 'search' => $search, 'type_odd' => 0];


        if ($type_odd == 1) {
            //查找异常
            $data_list = Db::query("select u.vip,u.id as uid,u.wx_touxiang as headimgurl ,u.nickname,u.username,u.openid,u.jifen,u.dongjie_jifen from nb_admin_user u 
left join nb_chaxun_jifenjilu j on u.id = j.user_id GROUP BY u.id HAVING u.jifen <> sum(jifenxiaofei)");
            $sorts['type_odd'] = 1;
            $this->assign('data_list2', $data_list2);
            $this->assign('sort', $sorts);
            $this->assign('data_list', $data_list);
            return $this->fetch();
        } else {
            $data_list = Db::name('admin_user u')
                ->join('weixin_user w','w.openid=u.openid', 'left')
                ->field('u.vip,u.id as uid,u.wx_touxiang as headimgurl ,w.nickname,u.username,u.openid,u.jifen,u.dongjie_jifen ')
                ->where('1=1' . $where_str)
                ->order("$filed $sortes")
                ->paginate(100);
        }

        $this->assign('sort', $sorts);
        $this->assign('page', $data_list->render());
        $this->assign('data_list2', $data_list2);
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * 修改密码
     */
    public function editpwd()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $username = $data['username'];
            if (!empty(trim($data['password'])) && trim($data['password']) !== '') {
                if (strlen($data['password']) <= 5 || strlen($data['password']) >= 21) {
                    $this->error('修改会员' . $username . '输入密码的长度在6~20位数字和字母');
                }
                unset($data['username']);
                $UserModel = new UserModel();
                if ($user = $UserModel->allowField(['nickname', 'email', 'password', 'mobile', 'avatar'])->update($data)) {
                    $this->success('修改会员'. $username .'密码修改成功');
                } else {
                    $this->error('修改会员'. $username .'密码修改失败');
                }
            } else {
                $this->error('修改会员'. $username .'密码修改不能为空');
            }
        }
    }

    /**
     * 修改vip等级
     */
    public function editvip()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $username = $data['username'];
            if (!empty(trim($data['vip'])) && trim($data['vip']) !== '') {
                unset($data['username']);
                if (Db::name('admin_user')->update($data)) {
                   $this->success( '修改会员'.$username.'成功');
                } else {
                    $this->error( '修改会员'.$username.'失败');
                }
            } else {
                $this->error('修改会员'. $username.'vip为空');
            }
        }

    }

    /**
     * 查询列表
     */
    public function loglist()
    {
        $data_list = Db::name('chaxun_list')->order('id desc')->paginate(100);
        $this->assign('page', $data_list->render());
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * 结算的服务列表
     */
    public function clear_server_list()
    {
        //查所有完成的订单
        $data_list = $arr = Db::name('v_complete_order_cx')
            ->alias('c')
            ->join('admin_user u', 'c.user_id=u.id', 'left')
            ->field('c.*,u.username as username')
            ->order('c.id desc')
            ->paginate(50);
        $this->assign('page', $data_list->render());
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * 手动结单
     */
    public function manual_check()
    {
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
//            write_json(1,"操作成功",$data);
            //手动修改没查询出来的订单
            $data_list = Db::name('cx_server_info')
                ->where([['order_id', '=', $data['order_id']], ['is_pay', '=', 0], ['status', '=', 0]])
                ->update(['status' => 2, 'is_pay' => 1]);

            if ($data_list) {
                write_json(0, "操作成功" . $data_list);
            } else {
                write_json(1, "操作失败" . Db::name('cx_server_info')->getLastSql(), $data_list);
            }
        }
    }

    /**
     * order_server_list 服务列表
     */
    public function server_list($order_id = null)
    {
        //查所有完成的订单
        $data_list = $arr = Db::table('v_server_info')
            ->where('order_id', $order_id)
            ->field('mp_sn,mp_imei,name,amount,add_time,phone_status,is_pay,status,cx_time,json_data,three_order_id,id,remark')
            ->order('is_pay asc,add_time asc ')
            ->paginate(50);
//        dump($data_list);die;
        $this->assign('page', $data_list->render());
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * 消费记录
     */
    public function xiaofeilog($uid = null)
    {
        //搜索扣款类型

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!empty(trim($data['type']))) {
                $type_where = 'and j.change_type =' . $data['type'];
            }
        }
        //单个用户消费积分记录
        if (!empty($uid)) {
            $type_where .= " and  j.user_id =$uid ";
        }

        $data_list = Db::name('chaxun_jifenjilu')
            ->alias('j')
            ->join('admin_user u', 'j.user_id=u.id', 'left')
            ->where('1=1 ' . $type_where)
            ->field('j.*,u.username as username')
            ->order('j.id desc')
            ->paginate(100);


        $type_list = Db::name('chaxun_jifenjilu')->group('change_type')->column('change_type');
        $this->assign('type_list', $type_list);
        $this->assign('page', $data_list->render());
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * 积分添加
     */
    public function addjifen()
    {
        $uid = $_POST['id'];
        $userjifen = (float)$_POST['addjifen'];
        $freezejifen = (float)$_POST['freezejifen'];
        $username = $_POST['username'];

        $user_id = Db::name('admin_user')->where('id',$uid)->find();

        if (empty(trim($userjifen)) &&  empty(trim($freezejifen))) {

            $this->error('修改会员'.$username.'修改的积分不能为空');
        }

        if (!$user_id) {
            $this->error('修改会员'.$username.'此用户不存在，修改失败');
        }

        $openid = Db::name('admin_user')->where('id',$uid)->value('openid');

        change_user_account($uid,$userjifen,$freezejifen,$_POST['remark'],3);

        //微信提示
        //发送微信
        $remark1 = '';
        $remark2 = '';
        $name = '';
        $name2 = '';
        $sum_dongjifen = '';
        if ($userjifen > 0) {
            $sum_jifen = $user_id['jifen']+$userjifen;
            $sum_jifen = round( $sum_jifen, 2);
            $userjifen = '+'.$userjifen;
        } else if($userjifen < 0) {
            $sum_jifen = $user_id['jifen']+$userjifen;
            $sum_jifen = round( $sum_jifen, 2);
        }
        if ($freezejifen > 0) {
            $name = '充值冻结金额：';
            $name2 = '当前冻结金额：';
            $sum_dongjifen = $user_id['dongjie_jifen']+$freezejifen;
            $sum_dongjifen = round($sum_dongjifen, 2);
            $freezejifen = '+'.$freezejifen;
        } else if($freezejifen < 0) {
            $name = '充值冻结金额：';
            $name2 = '当前冻结金额：';
            $sum_dongjifen = $user_id['dongjie_jifen']+$freezejifen;
            $sum_dongjifen = round($sum_dongjifen, 2);
        }

        $url = '';
        $temp_ID = 'j3yBQHzf4mG0eEmjr_ldtirtEJE4xj2S5OB_NKlutQg';
        if ($freezejifen == 0.00) {
            $data = array(
                "keyword1" => [$userjifen,"#5599FF"],
                "keyword2" => [(string)$sum_jifen,"#5599FF"],
                "remark" => ['变动原因：'.$_POST['remark'],"#0000FF"]
            );
        } else {

            $data = array(
                "first" => [$name.$freezejifen."\n".$name2.$sum_dongjifen,"#5599FF"],
                "keyword1" => [$userjifen,"#5599FF"],
                "keyword2" => [(string)$sum_jifen,"#5599FF"],
                "remark" => ['变动原因：'.$_POST['remark'],"#0000FF"]
            );

        }
        wechat_sending($uid,$temp_ID,$url,$data);
        $this->success('修改会员'.$username.'修改成功');
    }
    /**
     * 每日报表
     */
    public function dayreport()
    {

        $data_list = Db::name('day_report')->order('add_time desc')->paginate(100);
        $this->assign('page', $data_list->render());
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * 每日报表————服务异常监控
     */
    public function service_abnormal_monitor()
    {
        $data_list = Db::name('service_abnormal')
            ->order('id desc')
            ->paginate(100)
            ->each(function ($item, $key) {
                $item['json_data'] = json_decode($item['json_data'], true);
                $item['add_time'] = date('m/d H:i:s', $item['add_time']);
                return $item;
            });
//        dump($data_list);exit;
        $this->assign('page', $data_list->render());
        $this->assign('data_list', $data_list);
        return $this->fetch('cadmin/monitor');
    }

    /**
     * 充值记录
     */
    public function prepaid_records()
    {
        $openid = $this->request->param('openid');
        $data_list = Db::name('pay_log')
            ->where('openid', $openid)
            ->order('pay_id desc')
            ->paginate(100)
            ->each(function ($item, $key) {
                $item['add_time'] = date('Y/m/d H:i:s', $item['add_time']);
                if ($item['pay_time'] != 0) {
                    $item['pay_time'] = date('Y/m/d H:i:s', $item['pay_time']);
                }
                return $item;
            });
        $this->assign('page', $data_list->render());
        $this->assign('data_list', $data_list);
        return $this->fetch('user/prepaid_records');

    }


    /**
     * 订单管理
     */
    public function order_info($sort = null, $sort2 = null, $search = null,$jiankong = null)
    {
        $sort3 = ['paixu' => 'fa fa-sort text-muted', 'paixu_name' => '时间倒序'];
        $sort4 = ['paixu' => 'fa fa-sort text-muted', 'paixu_name' => '冻结积分倒序'];
        $filed = 'o.add_time';
        $sortes = 'desc';
        $where_str = '';
        //排序
        if (!empty($sort)) {
            $sortes = $sort;
            $sort = $sort == 'asc' ? 'desc' : 'asc';
            $sort3['paixu'] = $sort == 'asc' ? 'fa fa-caret-down' : 'fa fa-caret-up';
            $sort3['paixu_name'] = $sort == 'asc' ? '时间倒序' : '时间顺序';
        } elseif (!empty($sort2)) {
            $sortes = $sort2;
            $sort2 = $sort2 == 'asc' ? 'desc' : 'asc';
            $sort4['paixu'] = $sort2 == 'asc' ? 'fa fa-caret-down' : 'fa fa-caret-up';
            $sort4['paixu_name'] = $sort2 == 'asc' ? '冻结积分倒序' : '冻结积分顺序';
        }
        //post传参
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $search = $data['searchname'];
        }

        //查看所有监控
        if ($jiankong == 'ok') {
            $where_str = " and p.id_jiankong_e_time>1";
        }

        //搜索是否为空
        if (!empty($search)) {
            $where_str = " and (u.username like '%" . $search . "%'  OR  o.cx_order_sn like '%" . $search . "%' OR o.order_title like '%" . $search . "%' OR p.mp_sn like '%".$search."%')";
        }

            $data_list = Db::name('cx_order_info o')
        ->join('nb_cx_order_phone p', 'o.id=p.order_id ','left')
        ->join('admin_user u', 'o.user_id=u.id', 'left')
        ->field('o.*,u.username,count(p.id) as phone_count')
        ->where('1=1' . $where_str)
        ->order("$filed $sortes")
        ->group('o.id')
        ->paginate(50)
        ->each(function ($item,$key){
            return $item;
        });

        $sorts = ['sort' => [$sort, $sort3], 'sort2' => [$sort2, $sort4], 'search' => $search, 'type_odd' => 0];
        $this->assign('sort', $sorts);
        $this->assign('page', $data_list->render());
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * 手机列表
     */
    public function order_phone($order_id=null,$jiankong = null,$jihuo = null,$color = null,$guobao = null,$model = null,$net = null,$rong = null,$baoxiu = null,$gh = null,$idLock = null,$idBlack = null,$netLock = null,$weixiu = null,$yys = null,$MDM = null,$sn = null,$sn_or_imei_url = null,$country = null,$buy_time = null,$limit = 40)
    {
        //加载查询detail类和方法
        $chaxun = new Chaxun;
        //获取订单名称
        $order_info = Db::name('cx_order_info')->where('id',intval($order_id))->field('order_title,order_type')->find();
        //这个订单没有权限访问
        if ($order_id !== 'jiankong') {
            if (!$order_info){
                $this->error('您没有访问这个订单的权限');
            }
        }

        $order_title = $order_info['order_title'];
        $order_type = $order_info['order_type'];

        //获取手机 列表
        if ($order_id == 'jiankong') {
            $order_phone_list =  Db::name('cx_order_phone')
                ->where('id_jiankong_e_time','>',0)
                ->order('id','desc')
                ->paginate($limit);
        } else {
            $order_phone_list =  Db::name('cx_order_phone a')
                ->field('a.*,count(c.id) as count')
                ->join('cx_order_info b','a.order_id=b.id','left')
                ->join('cx_server_info c','a.order_id=c.order_id and a.server_cx_biaoshi = c.server_cx_biaoshi and c.is_pay=0','left')
                ->where('a.order_id='.intval($order_id))
                ->order('a.id','desc')
                ->group('a.server_cx_biaoshi')
                ->paginate($limit)//,false,['path'=>$path]
                ->each(function ($item,$key){
                    return $item;
                });
        }

//        $order_phone_list = $chaxun->get_order_list($order_id,urldecode($jiankong),urldecode($jihuo),urldecode($color),urldecode($guobao),urldecode($model),urldecode($net),urldecode($rong),urldecode($baoxiu),urldecode($gh),urldecode($idLock),urldecode($idBlack),urldecode($netLock),urldecode($weixiu),urldecode($yys),urldecode($MDM),urldecode($sn),urldecode($sn_or_imei_url),urldecode($country),urldecode($buy_time),$limit);
        $show_table_server = $chaxun->get_table_th(intval($order_id));// 获取已经查过的SERVERID list

        //测试数据 返到前台 -作处理
        $data_arr=array();
        foreach ($order_phone_list as $k => $row) {
            $row = $chaxun->get_phone_info($row);
            $data_arr[$k] =  $row;
        }

        // 获取分页显示
        $page = $order_phone_list->render();

        //未完成数
        $no_complete_server= Db::name('cx_server_info')->where('order_id',intval($order_id))->where('is_pay',0)->count();
        //一共查询数量
        $all_server_count= Db::name('cx_server_info')->where('order_id',intval($order_id))->count();
        // 计算完成 百分比
        $complete_bfb=  $all_server_count<=0 ?100: 100 - floor($no_complete_server/$all_server_count*100);


        $this->assign('order_type',$order_type);//订单类型
        $this->assign('order_title',$order_title);//订单名称

        $this->assign('total',$order_phone_list->total());//总数
        $this->assign('lastPage',$order_phone_list->lastPage());//记录页
        $this->assign('currentPage',$order_phone_list->currentPage());//当前页
        $this->assign('fuwu_list',$chaxun->get_chaxun_list(1)); //获取服务列表
        $this->assign('complete_bfb',$complete_bfb); //已完成订单的百分比

        $this->assign('order_phone_list',$order_phone_list); //已完成订单的百分比

        $this->assign('order_list',$data_arr);
        $this->assign('order_page',$page);
        $this->assign('no_complete_server',$no_complete_server);
        $this->assign('order_id',intval($order_id));
        $this->assign('show_table',$show_table_server);
        return $this->fetch();
    }

    /**
     * 服务列表
     */
    public function server_info()
    {
        if ($this->request->isGet()) {
            $biaoshi = $_GET['biaoshi'];
            $order_id = $_GET['order_id'];
            $where_str = '';
            $filed = 'id';
            $sortes = 'desc';

            $data_list = Db::name('cx_server_info')
                ->where('server_cx_biaoshi',$biaoshi)
                ->order("$filed $sortes")
                ->paginate(100);

            $this->assign('page', $data_list->render());
            $this->assign('data_list', $data_list);
            $this->assign('order_id', $order_id);
            return $this->fetch();
        }

    }


    /**
     * 国家列表
     */
    public function country_ling()
    {
        $data_list = Db::query("select DISTINCT  sale_country from  nb_cx_order_phone");
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * 微信模板记录
     */
    public function weixin_log($search=null)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $search = $data['searchname'];
        }
        //搜索是否为空
        if (!empty($search)) {
            $where_str = " and a.username like '%" . $search . "%'";
        }

        $data_list = Db::name('weixin_log')
            ->alias('l')
            ->join('admin_user a','l.uid=a.id','left')
            ->json(['data'])
            ->field('l.*,a.username')
            ->where('1=1'.$where_str)
            ->order('l.id','desc')
            ->paginate(100);
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     *采集数据
     */
    public function curl_collect($search=null)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $url = $data['url'];
            //初始化curl，返回资源
            $ch = curl_init();
            //2.设置curl工具请求服务器文件地址
            //参数1：curl资源
            //参数2：设置请求选项
            //参数3：请求选项值
            //设置将结果放回而不是显示
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            //跳过https证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//不验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//不验证
            //是否显示头部信息
            // curl_setopt($ch, CURLOPT_HEADER, 1);//不验证


            curl_setopt($ch,CURLOPT_URL,$url);

            //开始发起请求
            $data = curl_exec($ch);
//            file_put_contents('./code.jpg',$data);
            //关闭CURL
            curl_close($ch);
            $reg = '/<a.+?data-za-detail-view-element_name="Title">(.+?)<\/a>.+?<span.+?class="RichText ztext CopyrightRichText-richText">(.+?)<\/span>/';
            preg_match_all($reg,$data,$match);
            echo '<pre>';
            dump($match);
            die;
        }
        //搜索是否为空
        if (!empty($search)) {
            $where_str = " and a.username like '%" . $search . "%'";
        }

        $data_list = Db::name('weixin_log')
            ->alias('l')
            ->join('admin_user a','l.uid=a.id','left')
            ->json(['data'])
            ->field('l.*,a.username')
            ->where('1=1'.$where_str)
            ->order('l.id','desc')
            ->paginate(100);
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * 测试获取临时二维码
     */
    public function test_wx_qrcode(){
//        $result = $appwx->qrcode->temporary('aiguologin', 6 * 24 * 3600);
//        $ticket = $result['ticket'];
//        session('ticket', $ticket);


        $result = $this->wxapp->qrcode->temporary('pid'.'7072', 6 * 24 * 3600);
        dump($result);
        $url = $this->wxapp->qrcode->url($result['ticket']);
        echo "<img src='$url'>";
    }

}
