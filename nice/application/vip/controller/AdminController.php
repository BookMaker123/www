<?php
namespace app\vip\controller;

use think\Controller;
use think\Db;
use think\facade\Cache;
use think\Request;
/**
 * 后台基础控制器
 * Class AdminController
 * @package controller
 */
class AdminController extends Controller
{
    /**
     * 开启登录控制
     * @var bool
     */
    protected $is_login = true;
    protected $exclude_login_path = ["vip/OnlyServer/only_server_query","vip/HandlePay/handle_pay","vip/OnlyServer/id_monitor","vip/OnlyServer/imei_to_sn"];
    /**
     * 初始化数据
     * Index constructor.
     */
    public function __construct()
    {
        parent::__construct();

        //检测登录情况
        if ($this->is_login == true) {
            // 判断是否登录，并定义用户ID常量
            defined('UID') or define('UID', $this->__checkLogin());
        }
        //设置Apple缓存
        $this->CachegetAppleconfig();
        $openid = session('user.openid');
        //获取用户积分
        $user_jifen = Db::name('admin_user')->where('id',UID)->value('jifen');

        $this->assign('user_jifen',$user_jifen);
        //获取国别单价
        $country_jifen = Db::name('chaxun_config')->where('id','=', 9)->value('jifen');
        $this->assign('country_jifen',$country_jifen);

    }

    /**
     * 检测登录
     */
    public function __checkLogin()
    {
        $user = session('user');
        //判断是否登录
        $request=request();
        $path = $request->module().'/'.$request->controller().'/'.$request->action();

        if (empty($user) ||empty($user['id']) ) {
            if (in_array($path, $this->exclude_login_path)){
                return true;
            }else{
                //设置登陆前URL cookie
                cookie('__forward__', $_SERVER['REQUEST_URI']);
                // 未登录
                return $this->redirect('@vip/login');
            }

        } else {
            return $user['id'];
        }
    }

    /**
     * 缓冲写法
     */
    public function CachegetAppleconfig()
    {
        //快速设置语言
        if(input('?get.lang')){
            if(input('get.lang')=='en-us') {
                cookie('think_var', 'en-us'); 
               Db::name('admin_user')->update(['id' => UID, 'yuyan' => 'en-us']);
               session('user.yuyan','en-us');
              
            }
            else {      
                cookie('think_var', 'zh-cn');           
                Db::name('admin_user')->update(['id' => UID, 'yuyan' => 'zh-cn']);
                session('user.yuyan','zh-cn');
                
               
            }  
        }else{
            $yuyan=session('user.yuyan')=='zh-cn' ? 'zh-cn':'en-us';
            cookie('think_var', $yuyan); 
        }
  

        //print_r(\cache('xhao_id'));
        //当chaxun_url为空的时候重新获取
        Cache::remember('wxapi', function () {
            $admin_config = Db::name('admin_config')->where('name', 'wxapi')->find();
            $url_arr = @array_filter(explode("\n", $admin_config['value']));
            $url_array = [];
            foreach ($url_arr as $k => $v) {
                //判断是否用\t隔开
                if (strstr($v, "http")) {
                    $url_array[] = trim($v);
                }
            }
            return $url_array;
        });

        /**
         * 匹配型号 颜色 缓存不存在则写入缓存数据后返回
         */
        Cache::remember('xhao', function () {
            Cache::rm('xhao_id'); //防止盗版 反转数组
            $admin_config = Db::name('admin_config')->where('name', 'xhao')->find();

            $hou4_arr = @array_filter(explode("\n", $admin_config['value']));
            $hou4_array = [];
            foreach ($hou4_arr as $k => $v) {
                //判断是否用\t隔开
                if (strstr($v, "\t")) {
                    $hou4_c = explode("\t", $v);
                    //判断3-4位
                    if (preg_match("#^[A-Za-z][A-Za-z0-9]{3,4}$#", trim($hou4_c[0]))) {
                        $hou4_array[trim($hou4_c["0"])] = array(
                            $hou4_c["1"],
                            $hou4_c["2"],
                            $hou4_c["3"],
                            $hou4_c["4"],
                            $hou4_c["5"]);
                    }
                }
            }
            return $hou4_array;
        });

        /**
         * 匹配进度ID 颜色 缓存不存在则写入缓存数据后返回
         */
        Cache::remember('wxid', function () {
            Cache::rm('wxid_id'); //防止盗版 反转数组
            $admin_config = Db::name('admin_config')->where('name', 'ztai')->find();
            $wxztai_arr = @array_filter(explode("\n", $admin_config['value']));
            $wxid_array = array();
            foreach ($wxztai_arr as $k => $v) {

                if (strstr($v, "\t")) {
                    $ztaiidid = explode("\t", $v);
                    if(is_numeric(trim($ztaiidid["0"]))){
                        $wxid_array[trim($ztaiidid["0"])] = @array(
                            $ztaiidid["0"],
                            $ztaiidid["1"],
                            $ztaiidid["2"],
                            $ztaiidid["3"],
                        );
                    }

                }
            }
            return $wxid_array;
        });
        //防止被盗版 直接生成反转ID和逆向ID
        Cache::remember('xhao_id', function () {
            //设置一个0开头
            $arr['f'][]=0;
            $arr['q'][]=0;
            $ii = 1;
            foreach (Cache::get('xhao', '') as $k => $v) {
                $arr['f'][$ii] = $k;
                $arr['q'][$k] = $ii;
                $ii++;
            }
            return $arr;
        });
        Cache::remember('wxid_id', function () {
            $ii = 0;
            $arr = [];
            foreach (Cache::get('wxid', '') as $k => $v) {
                $arr['f'][$ii] = $k;
                $arr['q'][$k] = $ii;
                $ii++;
            }

            return $arr;
        });
    }

    /**
     * 获取筛选条件
     * @return array
     */
    final protected function getMap()
    {
        $search_field = input('param.search_field/s', '', 'trim');
        $keyword = input('param.keyword/s', '', 'trim');
        $filter = input('param._filter/s', '', 'trim');
        $filter_content = input('param._filter_content/s', '', 'trim');
        $filter_time = input('param._filter_time/s', '', 'trim');
        $filter_time_from = input('param._filter_time_from/s', '', 'trim');
        $filter_time_to = input('param._filter_time_to/s', '', 'trim');
        $select_field = input('param._select_field/s', '', 'trim');
        $select_value = input('param._select_value/s', '', 'trim');
        $search_area = input('param._s', '', 'trim');
        $search_area_op = input('param._o', '', 'trim');
        //替换字符串 防止数据库直接暴露
        $filter = str_replace('t1', 'sn_hou', $filter);
        $filter = str_replace('t2', 'wx_ztai_id', $filter);
        $filter = str_replace('t3', 'chuhuo', $filter);

        //反转数组 不让别任直接盗用字符串
        $fenlei = array_filter(explode('|', $filter_content), 'strlen');
        $filter_content = '';
        end($fenlei);
        foreach ($fenlei as $k => $v) {
            $fenlei2 = array_filter(explode(',', $v), 'strlen');
            end($fenlei2);
            foreach ($fenlei2 as $kk => $vv) {
                $ha = array_filter(explode('_', $vv), 'strlen');
                if ($ha[0] == 't1') {
                    $fanzhuanid = Cache::get('xhao_id', '');
                } elseif ($ha[0] == 't2') {
                    $fanzhuanid = Cache::get('wxid_id', '');
                }
                if (isset($fanzhuanid['f'][$ha[1]])) {
                    $filter_content .= $fanzhuanid['f'][$ha[1]];
                } else {
                    $filter_content .= $ha[1];
                }
                if ($kk != key($fenlei2)) {
                    $filter_content .= ',';
                }

            }
            //判断是否数组最后一个 不是就添加个
            if ($k != key($fenlei)) {
                $filter_content .= '|';
            }

        }
        //反转数组完毕

        $map = [];

        // 搜索框搜索
        if ($search_field != '' && $keyword !== '') {
            $map[] = [$search_field, 'like', "%$keyword%"];
        }

        // 下拉筛选
        if ($select_field != '') {
            $select_field = array_filter(explode('|', $select_field), 'strlen');
            $select_value = array_filter(explode('|', $select_value), 'strlen');
            foreach ($select_field as $key => $item) {
                if ($select_value[$key] != '_all') {
                    $map[] = [$item, '=', $select_value[$key]];
                }
            }
        }

        // 时间段搜索
        if ($filter_time != '' && $filter_time_from != '' && $filter_time_to != '') {
            $map[] = [$filter_time, 'between time', [$filter_time_from . ' 00:00:00', $filter_time_to . ' 23:59:59']];
        }

        // 表头筛选
        if ($filter != '') {
            $filter = array_filter(explode('|', $filter), 'strlen');
            $filter_content = array_filter(explode('|', $filter_content), 'strlen');
            foreach ($filter as $key => $item) {
                if (isset($filter_content[$key])) {
                    $map[] = [$item, 'in', $filter_content[$key]];
                }
            }
        }

        // 搜索区域
        if ($search_area != '') {
            $search_area = explode('|', $search_area);
            $search_area_op = explode('|', $search_area_op);
            foreach ($search_area as $key => $item) {
                list($field, $value) = explode('=', $item);
                $value = trim($value);
                $op = explode('=', $search_area_op[$key]);
                if ($value != '') {
                    switch ($op[1]) {
                        case 'like':
                            $map[] = [$field, 'like', "%$value%"];
                            break;
                        case 'between time':
                        case 'not between time':
                            $value = explode(' - ', $value);
                            if ($value[0] == $value[1]) {
                                $value[0] = date('Y-m-d', strtotime($value[0])) . ' 00:00:00';
                                $value[1] = date('Y-m-d', strtotime($value[1])) . ' 23:59:59';
                            }
                        default:
                            $map[] = [$field, $op[1], $value];
                    }
                }
            }
        }
        return $map;
    }

    /**
     * 获取字段排序
     * @param string $extra_order 额外的排序字段
     * @param bool $before 额外排序字段是否前置
     * @return string
     */
    final protected function getOrder($extra_order = '', $before = false)
    {
        $order = input('param._order/s', '');
        $by = input('param._by/s', '');
        if ($order == '' || $by == '') {
            return $extra_order;
        }
        if ($extra_order == '') {
            return $order . ' ' . $by;
        }
        if ($before) {
            return $extra_order . ',' . $order . ' ' . $by;
        } else {
            return $order . ' ' . $by . ',' . $extra_order;
        }
    }
/**
 * 设置各类错误
 * @param [type] $name 错误名称
 * @param [type] $type 错误类型
 *  @param [type] $tips 错误内容
 * @return void
 * @作者 Jun
 * @since
 */
    final protected function getsnLog($name, $type, $tips)
    {
        if(!isset($name) ||$name==0||!isset($type) )return;
     
        //检查是否已经存在
        if (!Db::name('sn_log')->where('name', $name)->where('type', $type)->find()) {
            Db::name('sn_log')->strict(false)->insert(['name' => $name, 'type' => $type, 'tips' => $tips, 'create_time' => time()]);
        }
    }

     /**
     * 会员函数 判断是否能监控
     */
    final protected  function Getjiankong($jkarr,$jkztai)
    {
        $user = Db::name('admin_user')->where('id', UID)->find();
        if ($user['openid'] == null)
        {
            $this->error("监控需要绑定微信！");
        }
        $jkcount = count($jkarr);
        //查询监控中的数量
        $jiankongzhong_count = Db::name('sn_listsn')->where('uid', UID)->where('id', 'not in', $jkarr)->where('jkztai', 1)->count();
        switch ($user['vip'])
        {
            case 1:
                $jknum = 100;
                break;
            case 2:
                $jknum = 200;
                break;
            case 3:
                $jknum = 300;
                break;
            case 4:
                $jknum = 400;
                break;
            case 9:
                $jknum = 10000;
                break;
            default:
                $jknum = 50;
                break;
        }
        //判断是否超过监控数量 
        if($jkztai=='true'){
            //这个可能算法有点错误吧 小BUG 应该问题不大
            if (($jknum - ($jkcount + $jiankongzhong_count)) < 0){
                $this->error("您的账号最多监控[<code>$jknum</code>]台！");
            }
            $jkztai=1;  
            $tishi='【成功监控】';
        }elseif($jkztai=='false'){
            $jkztai=0;  
            $tishi='【取消监控】';
        }else
        $this->error("监控类型错误，请重新设置！");

        //开始编辑监控
        if ($tjnum = Db::name('sn_listsn')->where('uid', UID)->where('id', 'in', $jkarr)->update(['jktime' => time(), 'jkztai' => $jkztai])) //->where('did', $did)
        {
            $jiankongzhong_count = Db::name('sn_listsn')->where('uid', UID)->where('jkztai', 1)->count();
            $this->success($tishi. $jkcount . "个当前加入的监控 " . ($jiankongzhong_count) . " 台");
        } else
            $this->error($tishi."失败，稍后重试...");
  
    }
}
