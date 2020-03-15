<?php

namespace app\wechatapp\model;
use think\Model;



//全部获取列表的查询都写到这里来 就好看了
class Weixin_user extends Model
{

    /**
     * 绑定数据表
     * @var string
     */
    protected $name = 'weixin_user';
   // protected $autoWriteTimestamp = true;//create_time和update_time 开启自动写入时间戳字段


    //自动转换格式
    protected $type = [
        'time' => 'timestamp:Y-m-d', //自动转换时间timestamp:Y-m-d
        //还可以自动保存json
    ];

}
