<?php
namespace app\vip\controller;

use app\vip\model\User as UserM;
use app\vip\model\User as UserModel;
use EasyWeChat\Factory;
use think\captcha\Captcha;
use think\Controller;
use app\common\SendSms;
use think\Db;

class Login extends Controller
{
    /**
     * User模型对象
     */

    private $appc; //全局变量
    /**
     * 初始化
     * Login constructor.
     */
    public function __construct()
    {
        $config = ['app_id' => 'wxa9d038fa00381958', 'secret' => '1512ce21b2411321e613ea764f806acf', 'oauth' => ['scopes' => ['snsapi_userinfo'], 'callback' => url('login/wxlogin')]];
        $this->appc = Factory::officialAccount($config);
        parent::__construct(); //必须要获取这个 isGet才不会报错
        //应该是检测是否登录，如果登录直接跳转
        $action = $this->request->action();
        //推出登錄的按鈕的控制 不跳轉
        if (!empty(session('user.id')) && $action !== 'signout' && $action !== 'change' && $action !== 'check_user' && $action !== 'wxuser') {
            return $this->redirect('@vip');
        }
    }


    /**
     * 后台登录入口
     * @return mixed|\think\response\Json
     */
    public function index($t = null)
    {
        if ($this->request->isGet()) {
            //中间件 检查是否微信登录 如果$t有传值 说明无当前用户
            if ($this->request->InApp == 'WeChat' && $t == null) {
                $this->appc->oauth->redirect()->send();
                return;
            }
            //登录页面-基础数据
            $basic_data = ['title' => '爱果进销商管理系统', 'data' => ''];
            $this->assign($basic_data);
            return $this->fetch('');
        } elseif ($this->request->isPost()) {
            $post = $this->request->post();
            //是否保存7天登录
            $rememberme = isset($post['remember-me']) ? true : false;

            //判断是否开启验证码登录选择验证规则
            //$SysInfo = Cache::get('SysInfo');
            //$SysInfo['VercodeType'] != 1 ? $validate_type = 'app\vip\validate\Login.index_off' : $validate_type = 'app\vip\validate\Login.index_on';
            //直接不开启验证码验证
            $validate_type = 'app\vip\validate\Login.index_on';

            //判断登录是否成功
            if ($post['username']) {
                //验证参数
                $validate = $this->validate($post, $validate_type);
                if (true !== $validate) {
                    $this->error($validate);
                }
                $login = UserM::login($post['username'], $post['password'],$type = 1);//用户名登入
            } elseif (!empty($post['phone'])) {
                $post['username'] = 'aiguo';
                //验证参数
                $validate = $this->validate($post, $validate_type);
                if (true !== $validate) {
                    $this->error($validate);
                }
                $login = UserM::login($post['phone'], $post['password'],$type = 2);//手机号登入
            } elseif (empty($post['phone'])) {
                $this->error('手机号不能为空');
            }



            //登录失败
            if ($login['code'] == 1) {
                isset($login['user']['id']) ? $user_id = $login['user']['id'] : $user_id = '';
                $this->error($login['msg']);
            }

            //登陆成功 微信登陆 绑定微信信息
            $user = session('user');
            if (!empty($user['openid'])) {
                //绑定 微信
                UserM::where('id', $login['user']['id'])->update(['openid' => $user['openid'], 'wx_touxiang' => $user['wx_touxiang'], 'wx_name' => $user['wx_name'], 'wx_gxtime' => time()]);
                //如果是微信登陆
                $login['user']['openid'] = $user['openid'];
                $login['user']['wx_touxiang'] = $user['wx_touxiang'];
                $login['user']['wx_name'] = $user['wx_name'];
            }


            //储存session数据
            $login['user']['login_at'] = time();
            session('user', $login['user']);

            if (empty($login['user']['phone_number'])) {
                $phone_number = 'no_phone';
                $url = url('vip/lists/index',['phone_number'=>$phone_number]);
                $this->success($login['msg'], $url);
            }
            $this->success($login['msg']);
        }
    }
    /**
     * 微信授权跳转页面
     */
    public function wxlogin($code = null, $state = null)
    {
        //不是授权返回的页面 直接跳转
        if (empty($code)) return $this->redirect(url('login/index', ['t' => 1]));
        $oauth = $this->appc->oauth;
        // 获取 OAuth 授权结果用户信息
        $user = $oauth->user();
        $wxuser['user']['login_at'] = time();
        $wxuser['user']['openid'] = $user->getId();
        $wxuser['user']['wx_touxiang'] = $user->getAvatar();
        $wxuser['user']['wx_name'] = $user->getNickname();
        session('user', $wxuser['user']);
        $login = UserM::where('openid', $user->getId())->find();
        //判断是否存在
        if ($login) {
            //更新下微信数据  ->多选择后再更新？  
            UserM::where('id', $login['id'])->update(['wx_touxiang' => $user->getAvatar(), 'wx_name' => $user->getNickname(), 'wx_gxtime' => time()]);
            return $this->redirect(url('login/wxuser'));
        } else {
            //传值 t如果等于1 说明 用户不存在
            return $this->redirect(url('login/wxuser'));
            return $this->redirect(url('login/index', ['t' => 1]));
        }
    }
    /**
     * 多用户选择
     */
    public function wxuser($username = null)
    {
        $user = session('user');
        //为了测试服务器可以操作增加的功能 如果用户没有选择 OPENID 不是空 添加到微信表里
        if($username == null && !empty($user) && !empty($user['openid']) ){
            $wx_user = \think\Db::name('weixin_user u')->where('openid',$user['openid'])->find();
            if(!$wx_user){
                $array=array(
                    'openid' => $user['openid'],
                    'nickname'=> $user['wx_name'],
                    'headimgurl'=> $user['wx_touxiang'],
                );
                \think\Db::name('weixin_user')->data($array)->insert();
            }

        }
        //为了测试服务器可以操作增加的功能


        if (empty($user) || empty($user['openid'])) return $this->redirect(url('login/index', ['t' => 1]));
        if ($username != null) {
            //选择这个用户登陆
            $login = UserM::where('openid', $user['openid'])->where('username', $username)->find();
            $wxuser['user'] = $login;
            $wxuser['user']['login_at'] = time();
            $wxuser['user']['openid'] = $user['openid'];
            $wxuser['user']['wx_touxiang'] = $user['wx_touxiang'];
            $wxuser['user']['wx_name'] = $user['wx_name'];
            if ($login) {
                $url = cookie('__forward__');
                $url = strstr($url,'.',true);
                if (empty($url)) {
                    $url = 'vip/lists/index';
                }
                cookie('__definurl__',$url);
                session('user', $wxuser['user']);
                UserM::where('id', $login['id'])->update(['wx_touxiang' => $user['wx_touxiang'], 'wx_name' => $user['wx_name'], 'wx_gxtime' => time()]);
                if (empty($login['phone_number'])) {
                    $phone_number = 'no_phone';
                    return $this->redirect($url.".html?phone_number=".$phone_number);
                }
                return $this->redirect($url);
            } else {
                $this->error('当前用户验证失败');
            }
        } else {
            //多用户选择页面
            $loginarr = UserM::where('openid', $user['openid'])->select();
            if (count($loginarr) == 1) {
                $this->wxuser($loginarr[0]['username']);
            }
            $this->assign('loginarr', $loginarr);
            return $this->fetch('login/wxuser');
        }
    }
    /**
     * 绑定微信
     */
    public function wxbind()
    {
        $user = session('user');
        if (empty($user) || empty($user['openid'])) return $this->redirect(url('login/index', ['t' => 1]));

        return $this->fetch();
    }
    /**
     * 退出登录
     * @return mixed|\think\response\Json
     */
    public function signout()
    {

        session(null);
        cookie('uid', null);
        cookie('signin_token', null);
        if ($this->request->InApp == 'WeChat')
            return $this->redirect(url('login/index', ['t' => 1]));

        $this->redirect('@vip/login');
    }

    /**
     * 登陆微信二维码
     * @return mixed|Captcha
     */
    public function qrcode()
    {
        $config = Config('wechat.');
        $appwx = Factory::officialAccount($config);
        $result = $appwx->qrcode->temporary('aiguologin', 6 * 24 * 3600);
        $ticket = $result['ticket'];
        session('ticket', $ticket);

        /** 保存二维码信息到数据库 */
        $_login['ticket'] = $ticket;
        $_login['create_time'] = time();
        $wxlogin_id = Db::name('weixin_login')->insertGetId($_login); //如果不存在，就写入数据库 如果存在就不用写了
        if ($wxlogin_id) {
            //二维码地址
            $wxerweima = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=$ticket";
            $this->redirect($wxerweima);
        }
    }
    /** 扫码后授权验证 */
    public function wxok()
    {
        //测试代码测试玩删除
//        session('ticket', 'qwddsafsdrewddasa');
        if (session('?ticket')) {
            //查询数据库 是否已经授权登录 返回openid
            $denglogin = Db::name('weixin_login')->where('openid is not null')->where('ticket', session('ticket'))->find();
//            测试代码测试玩删除
//            $denglogin['openid'] = 'oOEJGwut0I2666xupktodUzcLCzE';
//            $denglogin['headimgurl'] = '';
            if ($denglogin) {
               //获取用户资料
               $wx_user = Db::name('weixin_user')->where('openid', $denglogin['openid'])->find();
                $wxuser['user']['login_at'] = time();
                $wxuser['user']['openid'] = $denglogin['openid'] ;
                $wxuser['user']['wx_touxiang'] = $denglogin['headimgurl'];
                $wxuser['user']['wx_name'] = $wx_user['nickname'];
                session('user', $wxuser['user']);                
                $this->success('登录成功', 'wxuser');
            }
        }
    }

    /**
     * 验证码图片
     * @return mixed|Captcha
     */
    public function verify()
    {
        $config = [
            // 验证码字体大小
            // 'fontSize'    =>    30,
            // 验证码位数
            // 'length'      =>    4,
            // 关闭验证码杂点
            //'zhSet'    =>    '爱',
            // 'bg'=>[243, 251, 254],

        ];
        $captcha = new Captcha($config);
        $captcha->codeSet = '0123456789';
        $captcha->useImgBg = 0;
        $captcha->useCurve = 0;
        $captcha->useNoise = 1;
        //  $captcha->bg = [255, 255, 1];
        $captcha->length = 4;
        $captcha->imageH = 50;
        $captcha->imageW = 180;
        return $captcha->entry();
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
            $user_name = $data['user_name'];
            if ($data['edit'] == 'edit_pwd') {
                $phone = Db::name('admin_user')->where('phone_number', $phonecode)->select();
                //判断 已经发送过了
                if( !empty(session('smscode.time')) &&  session('smscode.time')  > time()){
                    $end_time = session('smscode.time')  - time();
                    write_json(55, '还需要'.$end_time.'秒,才可以发送！');
                }

                if (empty(trim($data['password'])) || trim($data['password']) == '') {
                    write_json(1, '未输入新密码');
                }

                if (strlen($data['password']) <= 5 || strlen($data['password']) >= 21) {
                    write_json(1, '输入密码的长度在6~20之间的数字和字母');
                }

                if (empty($phone)) {
                    write_json(1, '此手机号未注册爱果账号',$phone);
                }
            } else {
                if(strlen($user_name)<= 3 || strlen($user_name)>=21){
                    write_json(77, '用户名长度在3~21个字符之间');
                }
                //判断 已经发送过了
                if( !empty(session('smscode.time')) &&  session('smscode.time')  > time()){
                    $end_time = session('smscode.time')  - time();
                    write_json(55, '还需要'.$end_time.'秒,才可以发送！');
                }

                $phone = Db::name('admin_user')->where('phone_number', $phonecode)->find();
                    if ($phone) {
                        write_json(1, '此手机号已注册');
                    }
            }

            $user_name = Db::name('admin_user')->where('username', $user_name)->find();
            if ($user_name) {
                write_json(3, '用户名已注册,请换一个注册');
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
     * 编辑用户
     * @return mixed
     */
    public function edit_pwd()
    {

        if ($this->request->isPost()){
            $data = $this->request->post();
            if (empty(trim($data['password'])) || trim($data['password']) == '') {
                return $this->error('未输入新密码');
            }

            if (strlen($data['password']) <= 5 || strlen($data['password']) >= 21) {
                return $this->error('输入密码的长度在6~20之间的数字和字母');
            }

            if (empty($data['phone'])) {
                $this->error('手机号不能为空');
            }

            $pattern = "/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\d{8}$/";
            if (preg_match($pattern, $data['phone']) == 0) {
                $this->error('手机号不正确');
            }

            if (session('smscode.time') < time()){
                $this->error('验证码已过期');
            }

            if((int)$data['code'] !== session('smscode.code')) {
                $this->error('验证码错误');
            }

            $id = Db::name('admin_user')->where('phone_number', $data['phone'])->value('id');
            $data['id'] = $id;
            $UserModel = new UserModel();
            if ($user = $UserModel->allowField(['nickname', 'email', 'password', 'mobile', 'avatar'])->update($data)) {
                session('smscode.code','');
                $url = url('@vip');
                return $this->success('修改成功',$url);
            } else {
                return $this->error('修改失败');
            }
        }
        return $this->fetch();
    }

    public function check_user($uid,$url)
    {
        $user_id = intval(session('user.id'));
        if ($user_id <= 0) {
            $this->redirect($url);
            exit();
        }

        if ($user_id == $uid) {
            $this->redirect($url);
            exit();
        }

        if ($user_id !== $uid) {
            $username = session('user.username');
            $data = $this->wxuser();
            return $data;
        }

    }

}
