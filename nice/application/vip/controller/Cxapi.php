<?php
namespace app\vip\controller;

use app\common\Ztai;
use app\common\Jindu;
use app\common\Core;
use app\vip\model\Config;
use app\vip\model\Lists as ListsModel;
use app\vip\model\Listsn as ListsnModel;
use think\Controller;
use think\Db;
use think\facade\Cache;
include ('dhrufusionapi.class.php');
class Cxapi extends AdminController
{

    /**
     * 初始化
     * Lists constructor.
     */
    public function __construct()
    {
        parent::__construct();
        //判断是否绑定微信
        $this->user = Db::name('admin_user')->where('id', UID)->find();
        if ($this->user['openid'] == null) {
           write_json(-999,'登录失败');
        }
    }

    public function index()
    {
    }

    /**
     * 开启监控接口
     *
     * @return mixed
     */
    public function open_jiankong()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //查询订单id
            $order_id = $_POST['order_id'];
            //切割为数组
            $data_phone_id = explode(",", $data['id']);

            //查询用户user_id
            $user = Db::name('cx_order_info')->where('id',$order_id)->where("user_id =".UID)->select();

            //判断用户拥有这个订单的权限
            if (!$user) {
                write_json(1,"请先登入或操作用户错误");
            }
            //打开监控
            if ($data['open'] == 1) {
                //判断多一次积分
                $user_jifen = Db::name('admin_user')->where('id',UID)->value('jifen');
                //获取监控单价
                $jifen = Db::name('chaxun_config')->where('id', 4)->value('jifen');
                //手机数量
                $amount_num = count($data_phone_id);
                //总价
                $sum_amount = $amount_num * $jifen;
                //如果积分充足就开启监控
                if ($sum_amount > $user_jifen ) {
                    write_json(1,'亲，你的积分余额不足，你还需充值'.$sum_amount.'积分',$sum_amount);
                }
                //查2,7 b拿到ID与正在维修 服务的单价
//                $server_amount = Db::name('chaxun_config')->where('id','in',[2,7])->column('jifen');

                //查到 这个 激活锁查询ID 服务ID为 2 的 已经 存在SERVER表里的服务
                $phone_id2 = Db::name('cx_order_phone p')
                    ->join('cx_server_info s','p.server_cx_biaoshi=s.server_cx_biaoshi','left')
                    ->whereIn('p.id',$data_phone_id)
                    ->where('s.server_id',2)
                    ->column('p.id');
                //找到不存在ID监控的手机号
                $phone_id2 = array_diff($data_phone_id,$phone_id2);
                if (!empty($phone_id2)) {
                    $bisodhi = Db::name('cx_order_phone')->field('server_cx_biaoshi,order_id')->where('id','in',$phone_id2)->select();
                    foreach ($bisodhi as $v) {
                        $order_server2[] = ['server_id'=>2,'amount'=>0,'server_cx_biaoshi'=> $v['server_cx_biaoshi'],'add_time'=>time(),'order_id'=> $v['order_id']];
                    }
                    $mun =Db::name('cx_server_info')->insertAll($order_server2);
                    if ($mun = 0) {
                        write_json(1,'监控有点问题',$mun);
                    }
                }
                //查到 这个 正在维修的 服务ID为 7 的 已经 存在SERVER表里的服务
                $phone_id7 = Db::name('cx_order_phone p')
                    ->join('cx_server_info s','p.server_cx_biaoshi=s.server_cx_biaoshi','left')
                    ->whereIn('p.id',$data_phone_id)
                    ->where('s.server_id',7)
                    ->column('p.id');
                //取差是否为空，为空说明有此服务
                $phone_id7 = array_diff($data_phone_id,$phone_id7);
                if (!empty($phone_id7)) {
                    $bisodhi = Db::name('cx_order_phone')->field('server_cx_biaoshi,order_id')->where('id','in',$phone_id7)->select();
                    foreach ($bisodhi as $v) {
                        $order_server7[] = ['server_id'=>7,'amount'=>0,'server_cx_biaoshi'=> $v['server_cx_biaoshi'],'add_time'=>time(),'order_id'=> $v['order_id']];
                    }
                    $mun =Db::name('cx_server_info')->insertAll($order_server7);
                    if ($mun = 0) {
                        write_json(1,'监控有点问题',$mun);
                    }
                }

                $jiankong_e_time = strtotime(date('Y-m-d H:i:s',strtotime('+15day')));
                $update_count = Db::name('cx_order_phone')->where('id_jiankong_e_time < '.time())->whereIn('id',$data_phone_id)->where('order_id='.$order_id)->setField(['id_jiankong_e_time' => $jiankong_e_time]);
                if ( $update_count <= 0)
                {
                    write_json(1,'没有要开启监控的订单,或已经开启');
                }

                //计算总积分
                $sum_jifen = $update_count*$jifen;
                //扣几分，生成记录
                $remark = '监控开启消费'.$sum_jifen.'积分';
                change_user_account(UID,'-'.$sum_jifen,0,$remark,5 ,$order_id );
                write_json(0,'已开启监控'.$update_count.'条,一共花费'.$sum_jifen.'积分',$sum_jifen);
            //关闭监控
            } elseif($data['open'] == 0) {
                $update_count = Db::name('cx_order_phone')->where('id_jiankong_e_time > '.time())->whereIn('id',$data_phone_id)->setField(['id_jiankong_e_time' => 0]);
                if ( $update_count <= 0)
                {
                    write_json(1,'没有要关闭的监控');
                }
                write_json(0,'已关闭监控'.$update_count .'条');
            }
        }

    }

    /**
     * 添加服务
     *
     * @return mixed
     */
    public function add_server()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //查询订单id
            $order_id = intval($_POST['order_id']);
            //判断这个订单的权限
            $res = Db::name('cx_order_info')->where('id',$order_id)->where("user_id =".UID)->select();
            //判断用户拥有这个订单的权限
            if (!$res) {
                write_json(1,"您没有操作这个订单的权限");
            }

            //找出此用户下的订单手机项  --并判断用户权限
            $sql = "select   p.id  as id_list from nb_cx_order_phone p  where order_id=$order_id and   p.status = 0    and p.id in(".$data['id'].")";

            $res = Db::query($sql);
            $data_phone_id=array();
            foreach ($res as $row)
                $data_phone_id[]=$row['id_list'];

            if(count($data_phone_id)<=0){
                write_json(1,"没有可添加服务的机器!");
            }

            //查询服务积分单价
            $jifen = Db::name('chaxun_config')->where('id', $data['server_id'])->value('jifen');
            // 查询手机标识
            $biaoshi_list = Db::name('cx_order_phone')->whereIn('id',$data_phone_id)->column('server_cx_biaoshi');
            //查询添加的服务未完城的订单
            $biaoshi_error = Db::name('cx_server_info')->whereIn('server_cx_biaoshi',$biaoshi_list)->where([['server_id','=',$data['server_id']],['status','<=',0]])->column('server_cx_biaoshi');

            if (count($biaoshi_error) > 0) {
                //去重，序列化
                $biaoshi_error = array_unique(array_merge($biaoshi_error));
                //获取已完成服务标识，差值
                $biaoshi = array_diff($biaoshi_list,$biaoshi_error);
                $Remark_error = '有'.count($biaoshi_error).'个服务正在查询';
            } else {
                $biaoshi = $biaoshi_list;
            }

            //计算总积分
            $koujifen = count($biaoshi) * $jifen;

            //判断多一次积分
            $user_jifen = Db::name('admin_user')->where('id', UID)->value('jifen');
            //如果要扣的积分 大于身上的积分 就提示一下
            if ($koujifen > $user_jifen) {
                write_json(-12,'亲，你的积分余额不足，你还需充值'.$koujifen.'积分',['jifen'=>$koujifen]);
            }


            //查询已有服务的数量
//            $server_biaoshi = Db::name('cx_server_info')->whereIn('server_cx_biaoshi',$ph_biaoshi)->where('server_id',$data['server_id'])->column('server_cx_biaoshi');
//            //过滤已有的服务
//            $biaoshi = array_diff($ph_biaoshi,$server_biaoshi);

            if (empty($biaoshi)) {
                if (count($biaoshi_error) > 0) {
                    write_json(13,$Remark_error,$biaoshi);
                }
                write_json(1,"没有此服务，查询失败",$biaoshi);
            }
            //插入服务的数据
            foreach ($biaoshi as $v) {
                $order_server[] = ['server_cx_biaoshi'=>$v,'server_id'=>$data['server_id'],'amount'=>$jifen,'add_time'=>time(),'order_id'=>$data['order_id']];
            }

            $server_count = Db::name('cx_server_info')->insertAll($order_server);

            //判断是否插入失败
            if ($server_count <= 0) {
                write_json(1,"服务查询失败",$order_server);
            }else{
                Db::name('cx_order_info')->where('id',$data['order_id'])->setInc('cx_server_count', $server_count);
            }
            //计算总积分
            $sum_jifen = $server_count*$jifen;
            //扣几分，生成记录
            $remark = '查询'.$data['server_name'].'消费'.$sum_jifen.'积分';
            change_user_account(UID,'-'.$sum_jifen,$sum_jifen,$remark,2,$order_id);
            write_json(0,'查询'.$data['server_name'].'：'.$server_count.'条,'.$Remark_error.'一共消费'.$sum_jifen.'积分，冻结积分增加'.$sum_jifen.'积分',$sum_jifen);
        }
    }

    /**
     * 添加国别文字查询
     *
     * @return mixed
     */
    public function add_countrytext()
    {
        if ($this->request->isPost()) {
            $time = time();
            $data = $this->request->post();

            $order_id = $_POST['order_id'];

            //查询用户user_id
            $user = Db::name('cx_order_info')->where('id',$order_id)->where("user_id =".UID)->select();
            //判断用户拥有这个订单的权限
            if (!$user) {
                write_json(1,"请先登入或操作用户错误");
            }
            //切割为数组
            $data_phone_id = explode(",", $data['id']);
//            //查询订单id
//            $order_id = Db::name('cx_order_phone') ->where([['status','=',0],['is_tihuan','=',0],['is_del','=',0]])->where('id','in',$data_phone_id)->column('order_id');
//            //查询用户user_id
//            $user = Db::name('cx_order_info')->whereIn('id',$order_id)->column('user_id');
//            //判断用户拥有这个订单的权限
//            if (!in_array(UID,$user)) {
//                write_json(1,"请先登入或操作用户错误",$user);
//            }



            //过滤判断status=0,is_tihuan=0,is_del=0,才能转换国别
                $phone_list = Db::name('cx_order_phone a')
                    ->where([['a.status','=',0],['a.is_tihuan','=',0],['a.is_del','=',0],['order_id','=',$order_id]])
                    ->wherein('a.id',$data_phone_id)
                    ->select();

                //判断是否为空
                $phone_count =count($phone_list);
                if ($phone_count <=0) {
                    write_json(-3,'您未选择转移的手机！',Db::name('cx_order_phone a')->getLastSql());
                }
                //获取总价格
                $order_amount = $phone_count * $data['countryjifen'];
                //判断多一次积分
                $user_jifen = Db::name('admin_user')->where('id', UID)->value('jifen');
                //如果积分充足就新增服务
                if ($order_amount> $user_jifen) {
                    write_json(-13,'你的积分余额不足,你还需充值'.$order_amount.'积分',$order_amount);
                }

                //获取唯一的订单号
                do
                {
                    $order_sn = get_order_sn(); //获取新订单号
                    $sums = Db::name('cx_order_info')->where('cx_order_sn',$order_sn)->count();
                }
                while ($sums > 0);
                //新建国别订单
                $order_num =  Db::execute("insert into nb_cx_order_info (cx_order_sn,user_id,add_time,order_title,order_amount,order_type)
                    select $order_sn as cx_order_sn, user_id,$time as add_time,order_title,$order_amount as order_amount,2 as order_type from nb_cx_order_info where id = $order_id");
//               获取插入的订单ID
               $id = Db::name('cx_order_info')->getLastInsID();

               //判断是否插入
               if ($order_num == false) {
                   write_json(1,'新建订单失败',$order_num);
               }
            //获取国别服务ID和单价
            $server_amount = Db::name('chaxun_config')->field('id,jifen')->where('id','in',[1,9])->select();
               //循环要插入手机表和服务表的数组
               foreach ($phone_list as $k=>$v) {
                   unset($phone_list[$k]['id']);
                   $phone_list[$k]['order_id'] = $id;
                   $o_sn = $order_sn.substr($v['server_cx_biaoshi'],-4);
                   $phone_list[$k]['server_cx_biaoshi'] = $o_sn;
                   foreach ($server_amount as $vo) {
                       if ($vo['id'] == 1) {
                           $vo['jifen'] = 0;
                       }
                       $server_info[] = ['server_cx_biaoshi'=>$o_sn,'server_id'=>$vo['id'],'amount'=>$vo['jifen'],'add_time'=>$time,'order_id'=>$id];
                   }
               }
                $num = Db::name('cx_order_phone')->insertAll($phone_list);
                if (!$num)
                    {
                        write_json(1,'手机转国别文字查询失败',$phone_list);
                    }

               if (Db::name('cx_server_info')->insertAll($server_info))
               {
                //扣几分
                $remark = '使用国别文字查询花费'.$order_amount.'积分!冻结积分增加'.$order_amount.'积分';
                change_user_account(UID,'-'.$order_amount,$order_amount,$remark,6,$id); // 新的订单ID

                Db::name('cx_order_info')->where('id',$id)->setInc('cx_server_count', $num); //更改订单状态 为查询中
                write_json(0,'成功转国别文字查询'.$num.'条',array('order_id'=>$id));
            } else {
                write_json(1,'服务转国别文字查询失败',$server_info);
            }
        }

    }

    /**
     * 转GSX查询
     *
     * @return mixed
     */

    public function chaxun_GSX()
    {
        if ($this->request->isPost()) {
            $time = time();
            $data = $this->request->post();

            $order_id = $_POST['order_id'];

            //查询用户user_id
            $user = Db::name('cx_order_info')->where('id', $order_id)->where("user_id =" . UID)->select();
            //判断用户拥有这个订单的权限
            if (!$user) {
                write_json(1, "请先登入或操作用户错误");
            }

            //切割为数组
            $data_phone_id = explode(",", $data['id']);

            //过滤判断status=0,is_tihuan=0,is_del=0,才能转换GSX
            $phone_list = Db::name('cx_order_phone a')
                ->where([['a.status','=',0],['a.is_tihuan','=',0],['a.is_del','=',0],['order_id','=',$order_id]])
                ->wherein('a.id',$data_phone_id)
                ->select();

            //判断是否为空
            $phone_count = count($phone_list);
            if ($phone_count <=0) {
                write_json(-3,'您未选择转入的手机！',Db::name('cx_order_phone a')->getLastSql());
            }

            //获取GSX单价
            $server_jifen = Db::name('chaxun_config')->where('id','=',13)->value('jifen');

            //获取总价格
            $order_amount = $phone_count * $server_jifen;
            //判断多一次积分
            $user_jifen = Db::name('admin_user')->where('id', UID)->value('jifen');
            //如果积分充足就新增服务
            if ($order_amount> $user_jifen) {
                write_json(-13,'你的积分余额不足,你还需充值'.$order_amount.'积分',$order_amount);
            }

            //获取唯一的订单号
            do
            {
                $order_sn = get_order_sn(); //获取新订单号
                $sums = Db::name('cx_order_info')->where('cx_order_sn',$order_sn)->count();
            }
            while ($sums > 0);
            //新建国别订单
            $order_num =  Db::execute("insert into nb_cx_order_info (cx_order_sn,user_id,add_time,order_title,order_amount,order_type)
                    select $order_sn as cx_order_sn, user_id,$time as add_time,order_title,$order_amount as order_amount,3 as order_type from nb_cx_order_info where id = $order_id");
//               获取插入的订单ID
            $id = Db::name('cx_order_info')->getLastInsID();

            //判断是否插入
            if ($order_num == false) {
                write_json(1,'新建订单失败',$order_num);
            }
            //获取国别服务ID和单价
            $server_amount = Db::name('chaxun_config')->field('id,jifen')->where('id','in',[1,13])->select();
            //循环要插入手机表和服务表的数组
            foreach ($phone_list as $k=>$v) {
                unset($phone_list[$k]['id']);
                $phone_list[$k]['order_id'] = $id;
                $o_sn = $order_sn.substr($v['server_cx_biaoshi'],-4);
                $phone_list[$k]['server_cx_biaoshi'] = $o_sn;
                foreach ($server_amount as $vo) {
                    if ($vo['id'] == 1) {
                        $vo['jifen'] = 0;
                    }
                    $server_info[] = ['server_cx_biaoshi'=>$o_sn,'server_id'=>$vo['id'],'amount'=>$vo['jifen'],'add_time'=>$time,'order_id'=>$id];
                }
            }
            $num = Db::name('cx_order_phone')->insertAll($phone_list);
            if (!$num)
            {
                write_json(1,'手机转GSX查询失败',$phone_list);
            }

            if (Db::name('cx_server_info')->insertAll($server_info))
            {
                //扣几分
                $remark = '使用GSX查询花费'.$order_amount.'积分!冻结积分增加'.$order_amount.'积分';
                change_user_account(UID,'-'.$order_amount,$order_amount,$remark,6,$id); // 新的订单ID

                Db::name('cx_order_info')->where('id',$id)->setInc('cx_server_count', $num); //更改订单状态 为查询中
                write_json(0,'成功转GSX查询'.$num.'条',array('order_id'=>$id));
            } else {
                write_json(1,'服务转GSX查询失败',$server_info);
            }

        }


    }

}