<?php
namespace app\vip\controller;

use app\common\Ztai;
use app\common\Jindu;
use app\common\Core;
use app\vip\model\Lists as ListsModel;
use app\vip\model\Listsn as ListsnModel;
use think\Controller;
use think\Db;
use think\facade\Cache;
use function GuzzleHttp\json_decode;
use app\vip\controller\Check;
use EasyWeChat\Factory;

class HandlePay extends AdminController
{
    /**
     * 初始化
     */
    protected $user = null;
    protected $weixin_user = null;
    public function __construct()
    {
        parent::__construct();
        //判断是否绑定微信
        $this->user = Db::name('admin_user')->where('id', UID)->find();
        if ($this->user['openid'] == null) {
            $this->error("请先绑定微信！");
        }
        $this->weixin_user = Db::name('weixin_user')->where('openid', $this->user['openid'])->find();
    }

    /**
     * 处理查询完毕扣款和退款
     */
    public function handle_pay()
    {
        //查所有完成的订单
        $arr = Db::name('v_complete_order_cx')->where('no_complete_server = 0   ')->select();
        //循环处理扣款和退款数据
        foreach ($arr as $v) {
                $id_arr[] = $v['id'];
                $shou_remark = [];
                $tui_remark = [];
                if (floatval($v['shou_amount']) > 0 ) {
                    $remark = '高级查询花费'.$v['shou_amount'].' 冻结扣除-'.$v['shou_amount'].'积分';
                    change_user_account($v['user_id'],0,'-'.$v['shou_amount'],$remark,2,intval($v['id']));
                    //扣冻结积分
                    Db::query("update nb_cx_server_info set pay_time =".time().",is_pay = case when status =1 then 2 else 3 end where  order_id  = ".intval($v['id']) );
                    $shou_remark = ['会员'.$this->user['username'].'使用高级查询花费'.$v['shou_amount'].'积分，冻结扣除'.$v['shou_amount'].'积分',"#5599FF"];
                }
                if (floatval($v['tui_amount'] > 0) ) {
                    $remark = '查询冻结积分退回'.$v['tui_amount'].'积分，积分增加'.$v['tui_amount'].'积分';
                    change_user_account($v['user_id'],$v['tui_amount'],'-'.$v['tui_amount'],$remark,2,intval($v['id']));
                    //退积分扣冻结积分
                    Db::query("update nb_cx_server_info set pay_time =".time().",is_pay = case when status =1 then 2 else 3 end where order_id  = ".intval($v['id'] ));
                    $tui_remark = ['会员'.$this->user['username'].'查询冻结积分退回'.$v['tui_amount'].'积分，积分增加'.$v['tui_amount'].'积分',"#5599FF"];
                }
                $content = ['高级查询结算',"#0000FF"];
                $remark = [$shou_remark."/n".$tui_remark,"#5599FF"];
                $url = url('/vip/chaxun_test/order_detail','id='.$v['id'],'html', 'https://'.$_SERVER['HTTP_HOST']);


                // 不通知
                //$temp_ID = 'QH4DC0rkbTlGtz-bAXLy_fuk2GVq_pc2ZpbBYXUeUgE';
                //wechat_sending($v['user_id'],$temp_ID,$url,$remark);
                //wechat_sending($v['user_id'],$remark,$url,$content);

        }
        //print_r($id_arr);

        if (!empty($id_arr)) {
            Db::name('cx_order_info')->whereIn('id', $id_arr)->update(['cx_server_count' => 0]);
        }
    }
}