<?php
namespace app\vip\model;

use think\Model;

/**
 * 后台配置模型
 * @package app\vip\model
 */
class Tongji extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'sn_tongji';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    public function profile()
    {
        return $this->hasOne('sn_tongji','uid');
    }

}
