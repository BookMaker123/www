<?php
namespace app\vip\controller;
use app\common\SendSms;
use app\vip\model\User as UserModel;
use think\Controller;
use think\Db;
use EasyWeChat\Factory;
use think\helper\Hash;
use think\view\driver\Think;

class User extends AdminController
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
        $config = Config('wechat.');
        $this->wxapp = Factory::officialAccount($config);
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
        //        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['id'] = UID;
            //确认原密码
            $password = Db::name('admin_user')->where('id',UID)->value('password');
            $bool = Hash::check((string)$data['ori_password'],$password);
            if (!$bool ) {
                return $this->error('原密码输入错误');
            }
            // 如果没有填写密码，则不更新密码
            if (empty($data['ori_password']) || $data['ori_password'] == '') {
//                unset($data['ori_password']);
                return $this->error('未输入原密码');
            }

            if (empty(trim($data['password'])) || trim($data['password']) == '') {
                return $this->error('未输入新密码');
            }

            if (strlen($data['password']) <= 5 || strlen($data['password']) >= 21) {
                return $this->error('输入密码的长度在6~20位数字和字母');
            }

            if (empty($data['password1']) || $data['password1']== '') {
                return $this->error('未输入确认密码');
            }

            if ($data['password'] !== $data['password1']) {
                return $this->error('输入的新密码和确认密码不一致');
            }

            $bool = Hash::check((string)$data['password'],$password);
            if ($bool) {
                return $this->error('原密码与新密码不能一致');
            }

            unset($data['ori_password']);
            unset($data['password1']);
            // allowField(['name','email']) post数组中只有name和email字段会写入别的无视 强大？
            // allowField(true) 过滤post数组中的非数据表字段数据
            if ($user = $UserModel->allowField(['nickname', 'email', 'password', 'mobile', 'avatar'])->update($data)) {
                return $this->success('修改成功');
            } else {
                return $this->error('修改失败');
            }
        }
    }

//用户积分
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
     * 绑定手机
     * @return mixed|Captcha
     */
    public function phone_binding()
    {
        if ($this->request->isPost()){
            $data = $this->request->post();
            if (empty($data['phone'])) {
                return $this->error('手机号不能为空');
            }
            $pattern = "/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\d{8}$/";
            if (preg_match($pattern, $data['phone']) == 0) {
                return $this->error('手机号不正确');
            }

            if (session('smscode.time') < time()){
                return $this->error('验证码已过期');
            }

            if((int)$data['code'] !== session('smscode.code')) {
                return $this->error('验证码错误');
            }

            $phone_count = Db::name('admin_user')->where('phone_number',$data['phone'])->count();
            if ($phone_count !== 0) {
                return $this->error('手机号：'.$data['phone'].'已注册');
            }
            Db::name('admin_user')->where('id',UID)->update(['phone_number'=>$data['phone']]);
            session('user.phone_number',$data['phone']);
            session('smscode.code',null);
            $url = url('@vip');
            $this->success('绑定成功',$url);
        }
        return $this->fetch();
    }


    /**
     * 解绑手机号码
     */
    public function phone_untied()
    {
        if ($this->request->isPost()){
            $data = $this->request->post();
            if (empty($data['phone'])) {
                return $this->error('手机号不能为空');
            }

            if (session('smscode.time') < time()){
                return $this->error('验证码已过期');
            }

            if((int)$data['code'] !== session('smscode.code')) {
                return $this->error('验证码错误');
            }

            Db::name('admin_user')->where('id',UID)->update(['phone_number'=>'']);
            session('user.phone_number',null);
            session('smscode.code',null);
            $url = url('@vip');
            $this->success('解绑成功',$url);
        }
        return $this->fetch();
    }

    /**
     * 发送短信
     * @return mixed|Captcha
     */
    public function sendsms()
    {
        $sendsms = new SendSms;
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $phonecode = $data['phone'];
            if ($data['phone_status'] == 1) {

                $pattern = "/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\d{8}$/";
                if (preg_match($pattern, $phonecode) == 0) {
                    write_json(1,'手机号不正确');
                }

                $phone = Db::name('admin_user')->where('phone_number', $phonecode)->find();
                if ($phone) {
                    write_json(1, '此手机号已注册');
                }
            } else {
                if (session('user.phone_number') !== $phonecode) {
                    write_json(1, '请正确输入绑定此账户的手机号码');
                }
            }
            $sendcode = $sendsms->index($phonecode);
            if ($sendcode['status'] == 1) {
                write_json(0,'发送成功',$sendcode['status']);
            } else {
                write_json(1, '发送失败', $sendcode['info']);
            }
        }

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

        if($money < 48){
            write_json(1,'金额不可小于49',['code_url' => '','money'=>'','is_wx'=>'','order_sn'=>'']);
        }
        //$money =0.01;  //测试充值金额
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
            'openid'        => $_SESSION['think']['user']['openid'],
        ]);

        //判断是否是微信浏览器访问，然后选择扫码或手机公众号支付
        if(is_wxBrowers()){
            /**==============>公众支付*/
            $res = $this->wx_public_pay($money,$orderSn,$app);
            if($res['status'] == 'SUCCESS'){
                write_json(0,'success',['jssdk' => $res['data']['jssdk'] , 'is_wx' => 1]);//成功  is_wx 等于 1 就是微信浏览器
            }else{
                write_json(1,$res['data']['return_msg'],['code_url' => '','is_wx'=>1]);//失败  is_wx 等于 0 就不是微信浏览器
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
            'notify_url' => 'https://'.$_SERVER['HTTP_HOST'].'/api/Payreturn/wx_pay_callback' , // 支付结果通知网址，如果不设置则会使用配置里的默认地址
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
        $openid = $_SESSION['think']['user']['openid'];

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
