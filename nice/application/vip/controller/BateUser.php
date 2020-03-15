<?php
namespace app\vip\controller;
use app\vip\model\User as UserModel;
use think\Controller;
use think\Db;
use EasyWeChat\Factory;

class BateUser extends AdminController
{
    /**
     * 空白操作
     */
    public function _empty($name)
    {
 
      //  return $this->fetch();
    }
    protected $model = null;
    private $wxapp; //全局变量
    /**
     * 初始化
     * Lists constructor.
     */
    public function __construct()
    {

        parent::__construct();
  
    }
    /**
     * 首页
     * @return mixed
     */
    public function index($id = null)
    {
        echo 'tiamo';
    }


    /**
     * 编辑用户
     * @return mixed
     */
    public function edit()
    {
        $UserModel = new UserModel();
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['id'] = UID;
            // 如果没有填写密码，则不更新密码
            if (empty($data['password']) || $data['password'] == '') {
                unset($data['password']);
            }


            // allowField(['name','email']) post数组中只有name和email字段会写入别的无视 强大？
            // allowField(true) 过滤post数组中的非数据表字段数据
            if ($user = $UserModel->allowField(['nickname', 'email', 'password', 'mobile', 'avatar'])->update($data)) {
                return $this->success('修改成功');
            } else {
                return $this->error('修改失败');
            }
        }
        echo 1;
    }


    public function jifenjilu()
    {
        $data_list = Db::name('chaxun_jifenjilu')
            ->alias('j')
            ->join('admin_user u','j.user_id=u.id','left')
            ->where('j.user_id',UID)
            ->field('j.*,u.username as username')
            ->order('j.id desc')
            ->paginate(100);

        $this->assign('page', $data_list->render());
        $this->assign('data_list', $data_list);
        return $this->fetch();

    }

    /**
     * 微信支付调度
     */
    public function wx_pay_choose(){
        $config = Config('wechat.');
        $config_pay = array(
            'app_id'             => $config['app_id'],
            'mch_id'             => $config['mch_id'],
            'key'                => $config['key'],   // API 密钥
        );
        //实例化微信支付
        $app = Factory::payment($config_pay);

        //充值金额
        $money = $this->request->param('money');

        if($money < 50){
            write_json(1,'金额不可小于50',['code_url' => '','money'=>'','is_wx'=>'','order_sn'=>'']);
        }

        do{
            $orderSn = get_order_sn(); //获取新订单号
            $sums = Db::name('pay_log')->where('order_sn',$orderSn)->count();
        }while ($sums > 0);
        Db::name('pay_log')->insertGetId([
            'user_id'       => UID,
            'pay_type'      => 1,//微信支付
            'pay_money'     => $money,
            'is_pay'        => 0,
            'add_time'      => time(),
            'order_sn'      => $orderSn,//订单号
        ]);

        //判断是否是微信浏览器访问，然后选择扫码或手机公众号支付
        if(is_wxBrowers()){
            /**==============>公众支付*/
            $res = $this->wx_public_pay($money,$orderSn,$app);
            if($res['status'] == 'SUCCESS'){
                write_json(0,'success',['jssdk' => $res['data']['jssdk'] , 'is_wx' => 1]);//成功  is_wx 等于 1 就是微信浏览器
            }else{
                write_json(1,$res['data']['return_msg'],['code_url' => '','is_wx'=>0]);//失败  is_wx 等于 0 就不是微信浏览器
            }

        }else{

            /**==============>扫码支付*/
            $res = $this->wx_sweep_pay($money,$orderSn,$app);
            if($res['status'] == 'SUCCESS'){
                write_json(0,'success',['code_url' => $res['data']['code_url'],'money'=>$money,'is_wx'=>0,'order_sn'=>$orderSn]);//成功  is_wx 等于 0 就不是微信浏览器
            }else{
                write_json(1,$res['data']['return_msg'],['code_url' => '','money'=>$money,'is_wx'=>0,'order_sn'=>$orderSn]);//失败  is_wx 等于 0 就不是微信浏览器
            }

        }



    }

    /**
     * 充值积分(微信扫码支付)
     */
    public function wx_sweep_pay($money = 0,$orderSn,$app){
        $result = $app->order->unify([
            'body' => '积分充值',
            'product_id'=>'99',
            'out_trade_no' => $orderSn,
            'total_fee' => $money * 100,//单位：分
            'notify_url' =>  'https://'.$_SERVER['HTTP_HOST'].'/api/Payreturn/wx_pay_callback' , // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'NATIVE', // 请对应换成你的支付方式对应的值类型
        ]);
        //下单成功
        if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
            return ['status' => 'SUCCESS' , 'data' => $result];
        }else{
            return ['status' => 'ERROR' , 'data' => $result];
        }
    }


    /**
     * 充值积分(微信公众支付)
     */
    public function wx_public_pay($money = 0,$orderSn,$app){
//        $openid = $_SESSION['think']['user']['openid'];
        $openid = 'oOEJGwhlxA41Q3aO0sHS26OOgC3Y';

        $result = $app->order->unify([
            'body' => '积分充值',
            'out_trade_no' => $orderSn,
            'total_fee' => $money * 100,//单位：分
            'notify_url' => 'https://'.$_SERVER['HTTP_HOST'].'/api/Payreturn/wx_pay_callback', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $openid,
        ]);

        //下单成功
        if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
            $jssdk = $app->jssdk;
            $json = $jssdk->bridgeConfig($result['prepay_id']); // 返回 json 字符串，如果想返回数组，传第二个参数 false
            $result['jssdk'] = $json;
            return ['status' => 'SUCCESS' , 'data' => $result];
        }else{
            return ['status' => 'ERROR' , 'data' => $result];
        }
    }

    /**
     * 定时查询订单是否支付成功
     */
    public function timer_query_pay(){
        //订单号
        $order_sn = $this->request->param('order_sn');
        $data = Db::name('pay_log')->where('order_sn',strval($order_sn))->find();

        if($data['is_pay'] == 1){
            write_json(1,'success');
        }elseif($data['is_pay'] == 2){
            write_json(2,'success');
        }else{
            write_json(0,'success');
        }

    }







}
