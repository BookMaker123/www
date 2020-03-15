<?php
namespace fast;
/*
 * author: china_skag
 * time: 2014-07-08
 * 发博时间计算(年，月，日，时，分，秒)
 * $createtime 可以是当前时间
 * $gettime 你要传进来的时间
 */

class Mygettime{
    function  __construct($createtime,$gettime) {
        $this->createtime = $createtime;
        $this->gettime = $gettime;
    }

    function getSeconds()
    {
        return $this->createtime-$this->gettime;
    }

    function getMinutes()
    {
        return ($this->createtime-$this->gettime)/(60);
    }

    function getHours()
    {
        return ($this->createtime-$this->gettime)/(60*60);
    }

    function getDay()
    {
        return ($this->createtime-$this->gettime)/(60*60*24);
    }

    function getMonth()
    {
        return ($this->createtime-$this->gettime)/(60*60*24*30);
    }

    function getYear()
    {
        return ($this->createtime-$this->gettime)/(60*60*24*30*12);
    }
    
    function index()
    {
        if($this->getYear() > 1)
        {
            if($this->getYear() > 2)
            {
                return date("Y-m-d",$this->gettime);
                exit();
            }
            return intval($this->getYear())." 年前";
            exit();
        }
        if($this->getMonth() > 1)
        {
            return intval($this->getMonth())." 月前";
            exit();
        }
        if($this->getDay() > 1)
        {
            return intval($this->getDay())." 天前";
            exit();
        }
        if($this->getHours() > 1)
        {
            return intval($this->getHours())." 小时前";
            exit();
        }
        if($this->getMinutes() > 1)
        {
            return intval($this->getMinutes())." 分钟前";
            exit();
        }
        if($this->getSeconds() > 1)
        {
            return intval($this->getSeconds()-1)." 秒前";
            exit();
        }
    }

    // 英文版
    function index_en()
    {
        if($this->getYear() > 1)
        {
            if($this->getYear() > 2)
            {
                return date("Y-m-d",$this->gettime);
                exit();
            }
            return intval($this->getYear())." last year";
            exit();
        }
        if($this->getMonth() > 1)
        {
            return intval($this->getMonth())." months ago";
            exit();
        }
        if($this->getDay() > 1)
        {
            return intval($this->getDay())." days ago";
            exit();
        }
        if($this->getHours() > 1)
        {
            return intval($this->getHours())." hours ago";
            exit();
        }
        if($this->getMinutes() > 1)
        {
            return intval($this->getMinutes())." minutes ago";
            exit();
        }
        if($this->getSeconds() > 1)
        {
            return intval($this->getSeconds()-1)." seconds ago";
            exit();
        }
    }
}

?>