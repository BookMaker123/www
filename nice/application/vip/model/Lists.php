<?php

namespace app\vip\model;

use think\Model;

/**
 * 列表模型
 * Class Lists
 * @package app\vip\model
 */

//全部获取列表的查询都写到这里来 就好看了
class Lists extends Model
{

    /**
     * 绑定数据表
     * @var string
     */
    protected $name = 'sn_lists';

    //自动转换格式
    protected $type = [

        'ftime' => 'timestamp:Y/m/d', //自动转换时间timestamp:Y-m-d

        //还可以自动保存json

    ];

    /**
     * 新建列表
     * @return mixed
     */
    public function add($post)
    {
        $baoarr['uid'] = UID;
        $baoarr['ftime'] = $post['ftime'];
        $baoarr['dname'] = $post['dname'];
        $baoarr['create_time'] = time();
        $save = $this->save($baoarr);
        return $this->id;
    }
    /**
     * 编辑列表
     * @return mixed
     */
    public function edit($post)
    {
        $baoarr['id'] = $post['editid'];
        $baoarr['uid'] = UID;
      //  $baoarr['ftime'] = $post['ftime'];
        $baoarr['dname'] = $post['dname'];
        $baoarr['update_time'] = time();
        $save = $this->save($baoarr,true);
        return $this->id;
    }



}
