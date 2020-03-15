<?php
namespace app\vip\controller;

use app\vip\model\User as UserModel;
use EasyWeChat\Factory;
use think\Controller;
use think\Db;


class Signup extends Controller
{
    /**
     * 开放注册用户
     */

    private $appc; //全局变量
    private $wxapp; //全局变量

    /**
     * 初始化
     * Login constructor.
     */
    public function __construct()
    {

        $this->wxapp = Factory::officialAccount(Config('wechat.'));

        $config = ['app_id' => 'wxa9d038fa00381958', 'secret' => '1512ce21b2411321e613ea764f806acf', 'oauth' => ['scopes' => ['snsapi_userinfo'], 'callback' => url('signup/wxsig')]];
        $this->appc = Factory::officialAccount($config);

        parent::__construct(); //必须要获取这个 isGet才不会报错
        //应该是检测是否登录，如果登录直接跳转
        $action = $this->request->action();
        //推出登錄的按鈕的控制 不跳轉
        if (!empty(session('user.id')) && $action !== 'signout' && $action !== 'change') {
            return $this->redirect('@vip');
        }
    }

    /**
     * 注册入口
     * @return mixed|\think\response\Json
     */
    public function index()
    {
        if ($this->request->isGet()) {
            //中间件 检查是否微信登录 如果$t有传值 说明无当前用户
            if ($this->request->InApp == 'WeChat') {
                $this->appc->oauth->redirect()->send();
                return;
            } else {
                //微信扫码登陆的清空下 支持注册
                    if (session('?user.openid')) {
                        $user=session('user');
                        session('openid',$user['openid']);
                        session('wx_touxiang', $user['wx_touxiang']);
                        session('wx_name', $user['wx_name'] );
                        return $this->fetch('wxsig');

              }

                $this->error('请用微信登陆注册', 'login/index');
            }
        }
    }
    /**
     * 微信注册
     * @return mixed|\think\response\Json
     */
    public function wxsig($code = null, $state = null)
    {

        if(empty($code) && session('openid') ==""){
            return $this->redirect(url('login/index', ['t' => 1]));
        }
        //有CODE 获取 CODE
        if(!empty($code) && session('openid') ==''){
            $oauth = $this->appc->oauth;
            $user = $oauth->user();
            if ($user->getId()) {
                //开始注册页面
                session('openid', $user->getId());
                session('wx_touxiang', $user->getAvatar());
                session('wx_name', $user->getNickname());
                return $this->redirect(url('signup/wxsig', ['t' => 1]));
            }
        }
        return $this->fetch();

    }


    /**
     * 微信注册
     * @return mixed|\think\response\Json
     */
    public function wxsignuppost()
    {
        if ($this->request->isPost()) {
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

            $phone_number = Db::name('admin_user')
                ->alias('u')
                ->join('weixin_user w','u.openid = w.openid')
                ->where('w.openid',session('openid'))
                ->column('u.phone_number');

            if (!in_array($data['phone'],$phone_number)) {
                $data['role'] = 3;//注册的权限
                $data['kh_uid'] = 9999;//随便定义个
                $data['nickname']=$data['username'];
                $data['phone_number']=$data['phone'];
                $data['status']=1;
                $data['openid'] = session('openid');
                $data['wx_touxiang'] = session('wx_touxiang');
                $data['wx_name'] = session('wx_name');
                // 验证
                $result = $this->validate($data, 'User');
                if(true !== $result) return $this->error($result);
                session('smscode.code','');
                if ($user = UserModel::create($data)) {
                    //注册成功后推送账号密码到微信
                    //发送微信
                    $t = $this->wxapp->template_message->send([
                        'touser' => session('openid'),
                        'template_id' => '9H3Tc-xgtG8GHAfiNTa3Bki5Zh2vs4SNl1-YRgCvrms',
                        'url' => "http://www.aiguovip.com",
                        'data' => [
                            "first" => [session('wx_name').'，您已经成功注册爱果用户！', "#5599FF"],
                            "keyword1" => [$data['username'], "#0000FF"],
                            "keyword2" => [$data['password'], "#0000FF"],
                            "remark" => ["\r现在您可以登陆使用爱果系统了。", "#5599FF"] ,
                        ],
                    ]);

                    return $this->success('注册成功！', url('login/index'));
                } else {
                    return $this->error('注册失败');
                }

            } else {
                return $this->error('此手机号已注册');
            }

            $this->error('注册失败');
        } else {
            $this->error('提交错误');
        }
    }
}
