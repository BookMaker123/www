<?php
namespace app\wechatapp\controller;

use EasyWeChat\Factory;
use Db;
use app\wechatapp\model\Weixin_user as Weixin_userModel;


/**
 * 公众号入口   aiguovip.com/wechatapp
 */
class Index extends ApiController
{

    private $appwx; //全局变量

    public function __construct($name = null)
    {
        parent::__construct(); //必须要获取这个 isGet才不会报错

        //获取微信配置文件
        $config = Config('wechat.');
        $this->appwx = Factory::officialAccount($config);
    }
    public function index()
    {
         $this->appwx->server->push(
            function ($message) {
                //开启内侧
                $weUser = $this->weUser($message); //获取用户资料
                if ($weUser == false) return '很抱歉，获取用户信息失败，正在升级，请稍后重试！';
                if ($message['MsgType'] == 'event') {
                    $class = 'app\\wechatapp\\controller\\We_event';
                    return call_user_func([new $class(
                        $message,
                        $this->appwx,
                        $weUser
                    ), strtolower($message['Event'])]);
                } else {
                    $class = 'app\\wechatapp\\controller\\We_message';
                    return call_user_func([new $class(
                        $message,
                        $this->appwx,
                        $weUser
                    ), strtolower($message['MsgType'])]);
                }
            }
        );
        $response = $this->appwx->server->serve();
        // 将响应输出
        $response->send();
    }

    protected function weUser($message)
    {
        //获取wx用户资料，如果数据库已经存在，就直接冲数据库获取
        $openId = $message['FromUserName'];
        $user = Db::name('weixin_user')->where('openid', $openId)->find();
        //如果微信已经存在
        if ($user) {
            //如果用户存在 但是是取消过的，那就重新设置关注
            if ($user['subscribe'] == 0)
                Db::name('weixin_user')->where('id', $user['id'])->update(['subscribe' => '1']);
            //如果间隔时间 更新一次 不是每次都更新
            if ((time() - $user['update_time']) > 3600 * 24) {
                $user_curl = $this->appwx->user->get($openId);
                if (@$user_curl['errcode'] == 40001)
                    return $user;
                $user_arr = json_decode(json_encode($user_curl), true); //对象转换数组
                $user_arr['update_time'] = time();
                //更新 ->allowField(true) 抛开预防腾讯升级 导致字段变化产生异常
                $userup = new Weixin_userModel;
                $userup->allowField(true)->save($user_arr,['id' => $user['id']]);
            }
            return $user;
        } else {
            $user_curl = $this->appwx->user->get($openId);
            if (@$user_curl['errcode'] == 40001)
                return false;
            $user_arr = json_decode(json_encode($user_curl), true); //对象转换数组
            $user_arr['create_time'] = time();
            $user_arr['update_time'] = time();
            //写入 strict(false) 抛出多余的异常
            $user_id = Db::name('weixin_user')->strict(false)->insertGetId($user_arr); //添加会返回主键
            if ($user_id) {
                $user = Db::name('weixin_user')->where('openid', $openId)->find();
                return $user;
            } else
                return false;
        }
    }
}
