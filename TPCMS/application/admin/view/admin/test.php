<?php 
namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\model\Admin as AdminModel;
use app\admin\model\Attachment as AttachmentModel;
use app\admin\model\AuthGroup as AuthGroupModel;
use app\admin\model\AuthGroupAccess;
use think\Db;
use think\facade\Request;
use fast\Random;


class Index extends Base
{
public function get_rand($rewardList, $SumRate=0) {
    $result = '';
    //概率数组的总概率精度
//     $proSum = array_sum($proArr);
    if (!$SumRate) {
        foreach ($rewardList as $r) {
            $SumRate += ($r['rate']>0?intval($r['rate']):0);
        }
    }
    $SumRate = $SumRate>1?$SumRate:1;
    //概率数组循环
    foreach ($rewardList as $k => $v) {
        $randNum = mt_rand(1, $SumRate);
        if ($randNum <= $v['rate']) {
//             dump($randNum);
            $result = $v;
            break;
        } else {
            $SumRate -= $v['rate'];
        }
    }
    unset ($rewardList);

    return $result;
 }

public function test2564() {
            $levelnum1 = 100;
            $levelnum2 = 80;
            $levelnum3 = 60;
            $levelnum4 = 40;
            $levelnum5 = 20;
            $rewardList = array(
                array('rate'=>90000-$levelnum1,'money'=>1),
                array('rate'=>1000-$levelnum2,'money'=>1.88),
                array('rate'=>400-$levelnum3,'money'=>2.88),
                array('rate'=>200-$levelnum4,'money'=>8.88),
                array('rate'=>10-$levelnum5,'money'=>88),
            );
            $rand_reward = $this->get_rand($rewardList);
            $age=array("Bill"=>"60","Steve"=>"56","mark"=>"31");
            ksort($age);
            dump($age);
             $colorarr=array(1=>"009688",3=>"5FB878",10=>"393D49",20=>"1E9FFF",23=>"FFB800"); //颜色数组
             $rand = array_rand($colorarr,1);
             dump($rand);
             dump($colorarr[$rand]);
    
         }
}

?>