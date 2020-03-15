<?php
// +----------------------------------------------------------------------
// | 邮箱模型
// +----------------------------------------------------------------------
namespace app\admin\model;

use \think\Model; 

class Ems extends Model
{
    protected $table = 'yp_ems';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $auto = ['ip'];

    public function setIpAttr($value)
    {
        return request()->ip();
    }

    protected $expire = 120;
    protected $maxCheckNums = 10;

    public static function get($email, $event = 'default')
    {
        $ems = self::where(['email' => $email, 'event' => $event])
            ->order('id', 'DESC')
            ->find();
        hook('emsGet', $ems);
        return $ems ? $ems : null;
    }

    public static function send($email='', $code = '', $event = 'default',$urls='')
    {
        $code = is_null($code) ? mt_rand(1000, 9999) : $code;
        $time = time();
        $ems = self::create(['event' => $event, 'email' => $email, 'code' => $code, 'urls' => $urls, 'create_time' => $time]);

        return $ems;
    }

    public static function checks($email='', $code='', $event = '')
    {
        $ems = self::where(['email' => $email,'code'=>$code,'event' => $event])
            ->order('id', 'DESC')
            ->find();
        if ($ems) {
           self::where(['id' => $ems['id']])->delete();
            return $ems;
        }else{
            return false;
        }
        
    }

    public static function flush($email='', $event = 'default')
    {
        self::where(['email' => $email, 'event' => $event])->delete();
        return true;
    }

}
