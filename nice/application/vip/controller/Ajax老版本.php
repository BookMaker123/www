<?php

namespace app\vip\controller;

use EasyWeChat\Factory;
use think\Controller;
use think\Db;
use think\facade\Cache;
use app\vip\model\Listsn as ListsnModel;


//https://sweetalert2.github.io/ 提示教程
/**
 * 用于处理ajax请求的控制器
 * @package app\admin\controller
 */
class Ajax extends AdminController
{

    /**
     * 模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * Lists constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = model('Listsn');
    }
    /**
     * 首页
     * @return mixed
     */
    public function index($id = null, $soso = null)
    { }

    /**
     * 获取筛选数据
     * @param string $table 查找表格
     * @param string $field 查找字段
     * @param array $map 查询条件
     * @param string $options 选项，用于显示转换
     * @param string $list 选项缓存列表名称
     * @author Jun <2299493@qq.com>
     * @return \think\response\Json
     */
    public function getFilterList($table = '', $field = '', $map = [], $options = '', $list = '')
    {
        //要筛选的字段 要把 默认值设置成：0 防止筛选不了
        //字段安全隐藏
        $ziduan = '';
        switch ($field) {
            case 't1':
                $ziduan = 'sn_hou';
                $fanzhuanid = Cache::get('xhao_id', '');
                $data_list = Db::name('sn_listsn')->where('uid', UID)->where($map)->group($ziduan)->column($ziduan);
                break;
            case 't2':
                $ziduan = 'wx_ztai_id';
                $fanzhuanid = Cache::get('wxid_id', '');
                $data_list = Db::name('sn_listsn')->where('uid', UID)->where($map)->group($ziduan)->column($ziduan);
                break;
            case 't3':
                $ziduan = 'chuhuo';
                $data_list = Db::name('sn_listsn')->where('uid', UID)->where($map)->group($ziduan)->column($ziduan);

                break;
            case 'j1':
                $ziduan = 'wx_ztai_id';
                $data_list = Db::name('sn_jindu')->where($map)->group($ziduan)->column($ziduan);
                break;
            case 'j2':
                $ziduan = 'sn_hou';
                $data_list = Db::name('sn_jindu')->where($map)->group($ziduan)->column($ziduan);
                break;
            case 'j3':
                $ziduan = 'wx_jindutime';
                $data_list = Db::name('sn_jindu')->where($map)->group($ziduan)->column($ziduan);  
                break;
            default:
                return json(['code' => 0, 'msg' => '字段错误']);
        }

        if ($data_list === false) {
            return json(['code' => 0, 'msg' => '查询失败']);
        }

        $data_list = self::parse_array($data_list);
        //反转数组 防止直接显示字符串
        $data_list2 = [];


        foreach ($data_list as $k => $v) {
            if (isset($fanzhuanid['q'][$k])) {
                $data_list2[$field . '_' . $fanzhuanid['q'][$k]] = $v;
            } else {
                if (!$k) $k = '0';
                $data_list2[$field . '_' . $k] = $v;
            }
        }
        // $data_list2=$data_list;
        //重新序列号后4位匹配型号。编排数组
        if ($field == 't1') {
            //解释型号
            $xhao_arr = Cache::get('xhao', '');

            foreach ($data_list2 as &$v) {
                if (isset($xhao_arr[$v])) {
                    //找到型号的
                    $map2 = [];
                    $map2[] = ['did', '=', $map['did']];
                    $map2[] = ['sn_hou', '=', $v];

                    $counts = Db::name('sn_listsn')->where('uid', UID)->where($map2)->count();
                    $v = $xhao_arr[$v][0] . ' ' . $xhao_arr[$v][1] . ' ' . $xhao_arr[$v][2] . " <span class='badge badge-pill badge-info'>$counts</span>";
                } elseif ($v) {
                    $map2 = [];
                    $map2[] = ['did', '=', $map['did']];
                    $map2[] = ['sn_hou', '=', $v];

                    $counts = Db::name('sn_listsn')->where('uid', UID)->where($map2)->count();
                    //未知型号
                    $v = $v . '(未知型号)' . " <span class='badge badge-pill badge-info'>$counts</span>";
                } else {
                    $map2 = [];
                    $map2[] = ['did', '=', $map['did']];
                    $map2[] = ['sn_hou', '=', 0];
                    $counts = Db::name('sn_listsn')->where('uid', UID)->where($map2)->count();
                    //null的时候
                    $v = '(暂未分类)' . " <span class='badge badge-pill badge-info'>$counts</span>";
                }
            }
        }

        if ($field == 't2') {
            //解释WXID
            $wxid_arr = Cache::get('wxid', '');
            foreach ($data_list2 as &$v) {
                if (isset($wxid_arr[$v])) {
                    //找到型号的
                    $map2 = [];
                    $map2[] = ['did', '=', $map['did']];
                    $map2[] = ['wx_ztai_id', '=', $v];
                    $counts = Db::name('sn_listsn')->where($map2)->count();
                    // $v=$wxid_arr[$v][0].' '.$wxid_arr[$v][1].' '.$wxid_arr[$v][2]." <span class='badge badge-pill badge-info'>$counts</span>";
                    $v = " <span class='badge badge-pill badge-" . $wxid_arr[$v][2] . "'>" . $wxid_arr[$v][1] . "</span>" . " <span class='badge badge-pill badge-info'>$counts 台</span>";
                } elseif ($v) {
                    $map2 = [];
                    $map2[] = ['did', '=', $map['did']];
                    $map2[] = ['wx_ztai_id', '=', $v];
                    $counts = Db::name('sn_listsn')->where($map2)->count();
                    //未知型号
                    $v = $v . '(未知类型)' . " <span class='badge badge-pill badge-info'>$counts 台</span>";
                } else {
                    $counts = Db::name('sn_listsn')->where('did', $map['did'])->where('wx_ztai_id', 'null')->count();
                    //没有后4位的时候
                    $v = '没查进度状态' . " <span class='badge badge-pill badge-info'>$counts 台</span>";;
                }
            }
        }
        if ($field == 't3') {
            foreach ($data_list2 as &$v) {
                $counts = Db::name('sn_listsn')->where('chuhuo', $v)->count();
                if ($v == 0) {
                    $v = '未销售';
                }
                if ($v == 1) {
                    $v = '已销售';
                }

                $v .= " <span class='badge badge-pill badge-info'>$counts 台</span>";;;
            }
        }

        //进度 筛选
        if ($field == 'j1') {
            //解释WXID
            $wxid_arr = Cache::get('wxid', '');
            foreach ($data_list2 as &$v) {
                if (isset($wxid_arr[$v])) {
                    //找到型号的
                    $map2 = [];
                    $map2[] = ['wx_ztai_id', '=', $v];
                    $counts = Db::name('sn_jindu')->where($map2)->count();
                    // $v=$wxid_arr[$v][0].' '.$wxid_arr[$v][1].' '.$wxid_arr[$v][2]." <span class='badge badge-pill badge-info'>$counts</span>";
                    $v = " <span class='badge badge-pill badge-" . $wxid_arr[$v][2] . "'>" . $wxid_arr[$v][1] . "</span>" . " <span class='badge badge-pill badge-info'>$counts 台</span>";
                } elseif ($v) {
                    $map2 = [];
                    $map2[] = ['wx_ztai_id', '=', $v];
                    $counts = Db::name('sn_jindu')->where($map2)->count();
                    //未知型号
                    $v = $v . '(未知类型)' . " <span class='badge badge-pill badge-info'>$counts 台</span>";
                } else {
                    $counts = Db::name('sn_jindu')->where('wx_ztai_id', 'null')->count();
                    //没有后4位的时候
                    $v = '没查进度状态' . " <span class='badge badge-pill badge-info'>$counts 台</span>";;
                }
            }
        }
        if ($field == 'j2') {
            //解释型号
            $xhao_arr = Cache::get('xhao', '');

            foreach ($data_list2 as &$v) {
                if (isset($xhao_arr[$v])) {
                    //找到型号的
                    $map2 = [];
                    $map2[] = ['sn_hou', '=', $v];

                    
                    $v = $xhao_arr[$v][0] . ' ' . $xhao_arr[$v][1] . ' ' . $xhao_arr[$v][2] . " ";
                } elseif ($v) {
                    $map2 = [];
                    $map2[] = ['sn_hou', '=', $v];

                   
                    //未知型号
                    $v = $v . '(未知型号)' . " ";
                } else {
                    $map2 = [];
                    $map2[] = ['sn_hou', '=', 0];
                  
                    //null的时候
                    $v = '(暂未分类)' . " ";
                }
            }
        }






        if (!$data_list2) $data_list2 = '';
        $result = [
            'code' => 1,
            'msg' => '筛选获取成功',
            'list' => $data_list2,
        ];
        return json($result);
    }

    /**
     * 设置配色方案
     * @param string $theme 配色名称
     * oneui.app.min.js  post配色提交
     */
    public function setTheme($theme = '')
    {

        if (1) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败，请重试');
        }
    }

    /**  将一维数组解析成键值相同的数组 */
    final protected function parse_array($arr)
    {
        $result = [];
        foreach ($arr as $item) {
            $result[$item] = $item;
        }
        return $result;
    }

    /**
     * 取消微信
     */
    public function qxweixin()
    {
        $weixin['openid'] = null;
        //不清空
        // $weixin['wx_name'] = null;
        // $weixin['wx_touxiang'] = null;
        //取消微信绑定，先清空所有监控
        $data_list_jk = ListsnModel::where('uid', UID)->where('jkztai', 1)->select();
        if ($data_list_jk) {
            foreach ($data_list_jk as $v)
                $jk_list[] = ['id' => $v['id'], 'jkztai' => 0];
            if (isset($jk_list)) {
                $pl_shan_jk = new ListsnModel;
                $plbaocun = $pl_shan_jk->saveAll($jk_list);
                if (!$plbaocun)
                    $this->error('解绑失败，请稍后重试！');
            }
        }
        //清空下微信记录   
        $user = session('user');
        unset($user['password']);
        unset($user['openid']);
        session('user', $user);
        $quxiaowx = Db::name('admin_user')->where('id', UID)->update($weixin);
        if ($quxiaowx)
            return $this->success('微信解绑成功');
        else
            return $this->error('微信解绑失败');
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
        session('ticket_wxband', $ticket);

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
    public function wxbindok()
    {
        if (session('?ticket_wxband')) {
            //查询数据库 是否已经授权登录 返回openid
            $denglogin = Db::name('weixin_login')->where('openid is not null')->where('ticket', session('ticket_wxband'))->find();
            if ($denglogin) {
                //获取用户资料
                $wx_user = Db::name('weixin_user')->where('openid', $denglogin['openid'])->find();
                if (!$wx_user) $wx_user['nickname'] = ' ';
                Db::name('admin_user')->where('id', UID)->update(['openid' => $denglogin['openid'], 'wx_touxiang' => $denglogin['headimgurl'], 'wx_name' => $wx_user['nickname'], 'wx_gxtime' => time()]);
                //重新获取一次session
                $login = Db::name('admin_user')->where('id', UID)->find();
                $wxuser['user'] = $login;
                if ($login)   session('user', $wxuser['user']);
                $this->success('微信绑定成功');
            }
        }
    }
}
