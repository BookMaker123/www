<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use app\admin\model\Attachment as AttachmentModel;
use PHPMailer\PHPMailer\PHPMailer; 
use PHPMailer\PHPMailer\Exception;  
use think\facade\Request;
use think\Db;

function tree($data,$pid = 0,$count = 0,$char="--") {
    static $treeList = []; 
    foreach ($data as $key => $value){
        if($value['pid']==$pid){
            $value['count'] = $count;
            $value['html'] = str_repeat($char,$count);
            $treeList []=$value;
            unset($data[$key]);
            tree($data,$value['id'],$count+1,$char);
        } 
    }
    return $treeList ;
}


function recursion ($result,$parentid=0,$format="|--")
{
	static $list=array();
 
	foreach ($result as $k => $v){
    	if($v['pid']==$parentid){
    		if($parentid!=0){
    			$v['name']=$format.$v['name'];
    		}
    		$list[]=$v;
    		recursion($result,$v['id'],"  ".$format);
    	}
   	}
 
   	return $list;
 
}

function recursion_menu ($result,$parentid=0,$format="|--")
{
    static $list=array();
 
    foreach ($result as $k => $v){
        if($v['pid']==$parentid){
            if($parentid!=0){
                $v['title']=$format.$v['title'];
            }
            $list[]=$v;
            recursion_menu($result,$v['id']," ".$format);
        }
    }
 
    return $list;
 
}

function recursion_channeltype ($result,$parentid=0,$format="|--")
{
    static $list=array();
 
    foreach ($result as $k => $v){
        if($v['pid']==$parentid){
            if($parentid!=0){
                $v['typename']=$format.$v['typename'];
            }
            $list[]=$v;
            recursion_channeltype($result,$v['id']," "." ".$format);
        }
    }
 
    return $list;
 
}

function digui($menu,$pid=0,$level=0)
{
    static $arr = array();
    foreach ($menu as $ke=>$val){
        if ($val['pid'] == $pid){
            $val['level'] = $level;
            $arr[] = $val;
            unset($menu[$ke]);
            digui($menu,$val['id'],$level+1);
        }
    }

    return $arr;
}

function upload($file=null)
{
    $info = $file->validate(['size'=>2048000,'ext'=>'jpg,png,gif,jpeg'])->move( '../public/static/uploads');
    
    if($info){
        $imgsrc = array();
        $imgsrc['imgsrc'] = '/static/uploads/'.str_replace("\\","/",$info->getSaveName()); 
        $date = [
            'url'         => $imgsrc['imgsrc'],
            'createtime'  => time(),
            'uploadtime'  => time(),
        ];
        if (Session::has('user_id')) {
            $date['user_id'] = Session::get('user_id');
        }
        if (Session::has('admin_id')) {
            $date['admin_id'] = Session::get('admin_id');
        }
        if (!$attachmentid = AttachmentModel::insertGetId($date)) {
            return $this->error('上传失败!');
        }
        return $imgsrc['imgsrc'];
    }else{

        return $this->error($file->getError());
    }
}

function updateimg($files=null)
{   
        if (Session::has('user_id')) {
            $date['user_id'] = Session::get('user_id');
        }
        if (Session::has('admin_id')) {
            $date['admin_id'] = Session::get('admin_id');
        }

    $imgid = '';
    foreach($files as $file){
        $info = $file->validate(['size'=>2048000,'ext'=>'jpg,png,gif,jpeg'])->move( '../public/static/uploads');

        if($info){
            $imgsrc = array();
            $imgsrc['imgsrc'] = '/static/uploads/'.str_replace("\\","/",$info->getSaveName()); 
            $date = [
                'url'         => $imgsrc['imgsrc'],
                'createtime'  => time(),
                'uploadtime'  => time(),
            ];

            if ($attachmentid = AttachmentModel::insertGetId($date)) {
                $imgid[] = $attachmentid;
            }else{
                return $this->error('上传失败!');
            }
        }else{

            return $this->error($file->getError());
        }   
    }

    return $imgid; 


}


function timediff($begin_time,$end_time)
{
    if($begin_time < $end_time){
        $starttime = $begin_time;
        $endtime = $end_time;
    }else{
        $starttime = $end_time;
        $endtime = $begin_time;
    }

    $timediff = $endtime-$starttime;
    $days = intval($timediff/86400);
    $remain = $timediff%86400;
    $hours = intval($remain/3600);
    $remain = $remain%3600;
    $mins = intval($remain/60);
    $secs = $remain%60;
    $res = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs);
    return $res;

}
function pingfen($numb='')
{
    $numb = $numb*10;
    return $numb;
}
function countWords($str){
  return (mb_strlen($str, 'utf8') + strlen($str))/2;
}
function title_url($titleUrl='')
{
    $titleUrl = explode(' ', ucwords($titleUrl));
    $titleUrl = implode('-', $titleUrl);
    return $titleUrl;
}

function send_mail($tomail, $name, $subject = '', $body = '', $attachment = null) {

    $mail = new PHPMailer();   
    $mail->CharSet = 'UTF-8'; 
    $mail->IsSMTP();       
    $mail->SMTPDebug = 0;              
    $mail->SMTPAuth = true;          
    $mail->SMTPSecure = 'ssl';         
    $mail->Host = "smtp.qq.com"; 
    $mail->Port = 465;            
    $mail->Username = "416900463@qq.com";  
    $mail->Password = "nfdgtlxcwwhfcafi";  
    $mail->SetFrom('tom@noveltom.com', 'tom@noveltom.com');
    $replyEmail = '';           
    $replyName = '';      
    $mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    $mail->AddAddress($tomail, $name);
    if (is_array($attachment)) { 
        foreach ($attachment as $file) {
            is_file($file) && $mail->AddAttachment($file);
        }
    }
    return $mail->Send() ? true : $mail->ErrorInfo;
}


function hook($hook, $params = [], $is_return = false, $once = false)
{
    if ($is_return == true) {
        return \think\facade\Hook::listen($hook, $params, $once);
    }
    \think\facade\Hook::listen($hook, $params, $once);
}


function think_encrypt($data, $key = 'adc', $expire = 0) {
    $key  = md5(empty($key));
    $data = base64_encode($data);
    $x    = 0;
    $len  = strlen($data);
    $l    = strlen($key);
    $char = '';
    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }
    $str = sprintf('%010d', $expire ? $expire + time():0);
    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
    }
    return str_replace(array('+','/','='),array('-','_',''),base64_encode($str));
}


function think_decrypt($data, $key = 'abc'){
    $key    = md5(empty($key));
    $data   = str_replace(array('-','_'),array('+','/'),$data);
    $mod4   = strlen($data) % 4;
    if ($mod4) {
       $data .= substr('====', $mod4);
    }
    $data   = base64_decode($data);
    $expire = substr($data,0,10);
    $data   = substr($data,10);
    if($expire > 0 && $expire < time()) {
        return '';
    }
    $x      = 0;
    $len    = strlen($data);
    $l      = strlen($key);
    $char   = $str = '';
    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }
    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1))<ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        }else{
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

function titles($titles='')
{
    $arr = [" ",".",",","!","?",":","'","&","+"];

    $titles = str_replace($arr,'-',$titles);
    $titles = rtrim($titles,'-');

    return $titles;
}

//清除缓存
function delete_dir_file($dir_name) {
    $result = false;
    if(is_dir($dir_name)){
        if ($handle = opendir($dir_name)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name . DIRECTORY_SEPARATOR . $item)) {
                        delete_dir_file($dir_name . DIRECTORY_SEPARATOR . $item);
                    } else {
                        unlink($dir_name . DIRECTORY_SEPARATOR . $item);
                    }
                }
            }
            closedir($handle);
            if (rmdir($dir_name)) {
                $result = true;
            }
        }
    }
    return $result;
}