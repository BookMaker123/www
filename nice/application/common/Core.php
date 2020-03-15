<?php

namespace app\common;
use think\Db;
use think\facade\Cache;

class Core{
    // 通过sn 得到 该手机信息
    public static function getModelBySn4(string $sn){
        //获取sn后四位
        $sn4 = substr(strtoupper($sn),-4,4);
        // 获取缓存数据
        if ($data = cache('phone_model_information')){

        }else{
            $cdata = Db::name('admin_config')->where('name',"xhao")->find();
            $data = explode("#	型号	内存	颜色	网络型号	网络类型", $cdata["value"]);
            // 设置缓存数据
            cache('phone_model_information', $data, 0);
        }

        $final_data = [];
        foreach($data as $v){
            $c1 = explode("\r\n", $v);
            foreach($c1 as $item){
                $c2 = explode("\t", $item);
                if (count($c2) == 6){
                    $final_data[$c2[0]] = $c2;
                };
            }
        }

        //当数据库里的model为空，$row中的model有值时更新数据库
        if(!empty($final_data[$sn4])){
//            $mp_model = $final_data[$sn4][1];
//            $mp_net = $final_data[$sn4][4];
//            $mp_color = $final_data[$sn4][3];
//            $mp_rongliang = $final_data[$sn4][2];
//           // $sql = "update nb_cx_order_phone set mp_model = '$mp_model' , mp_net = '$mp_net' , mp_color = '$mp_color' , mp_rongliang = '$mp_rongliang'
////                    where right(mp_sn = '$sn' and mp_model is null or trim(mp_model)='' ";
////            Db::execute($sql);
//////            Db::name('cx_order_phone')->where("mp_sn = '$sn' and mp_model is null trim(mp_model)=" . '')->update([
//////                'mp_model'      => $final_data[$sn4][1],
//////                'mp_net'        => $final_data[$sn4][4],
//////                'mp_color'      => $final_data[$sn4][3],
//////                'mp_rongliang'  => $final_data[$sn4][2],
////            ]);
//          //  exit;
        }
        return @$final_data[$sn4];

    }

    public static function getIntervalDayNum(string $startdatestr, string $enddatestr)
    {
        $startdate=strtotime($startdatestr);

        $enddate=strtotime($enddatestr);
    
        $days=round(($enddate-$startdate)/3600/24) ;
    
        return $days; //days为得到的天数;
    }

    public static function post($url, $data, $headers=[],$cookies=[], $proxy=[],$if_json=false){
        $timeout = 15;
        $cookie_str = "";
        foreach($cookies as $k=>$v) {
            $cookie_str .= $k."=".$v.";";
        }
        $cookie_str = substr($cookie_str,0,-1);
        // 头部heander
        if(count($headers) == 0){
            $headers['user-agent'] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36";
        }
        // create curl resource 
        $ch = curl_init(); 

        // set url 
        curl_setopt($ch, CURLOPT_URL, $url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT , $timeout);//curl请求过程的时间
        if (!empty($proxy)){
//            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1); //代理服务器地址
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
            curl_setopt($ch, CURLOPT_PROXY, $proxy['url']); //代理服务器地址
            curl_setopt($ch,CURLOPT_PROXYUSERPWD, $proxy['username'] . ':' . $proxy['password']);//设置账号密码 "[username]:[password]"格式的字符串。
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS , http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_COOKIE,$cookie_str);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // 获取头部信息
        curl_setopt($ch, CURLOPT_HEADER, 1);
        // 返回原生的（Raw）输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS,1);
//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
        // $output contains the output string 
        $output = curl_exec($ch);
        if(!$output){
            return ['status'=>$output, 'data'=>'', 'header'=>''];
        }
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//        // 解析COOKIE
//        preg_match("/set\-cookie:([^\r\n]*)/i", $header, $matches);

        if ($proxy != ""){
            list($proxy_header, $header, $body) = explode("\r\n\r\n", $output);
        }else{
            list($header, $body) = explode("\r\n\r\n", $output);
        }
        if($if_json){
            $body = json_decode($body);
        }

        curl_close($ch);

        //解析COOKIE
        //preg_match("/set\-cookie:([^\r\n]*)/i", $header, $matches);

        return ['status'=>$httpcode, 'data'=>$body, 'header'=>$header];
    }
    public static function get($url,$headers=[],$cookies=[],$proxy=[],$if_json=false){
        $cookie_str = "";
        foreach($cookies as $k=>$v) {
            $cookie_str .= $k."=".$v.";";
        }
        $cookie_str = substr($cookie_str,0,-1);
        // 头部heander
        if( count($headers) == 0){
            $headers['user-agent'] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36";
        }
        // create curl resource
         $ch = curl_init(); 

         // set url 
         curl_setopt($ch, CURLOPT_URL, $url);
        if (count($proxy) > 0 ){
//            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1); //代理服务器地址
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
            curl_setopt($ch, CURLOPT_PROXY, $proxy['url']); //代理服务器地址
            curl_setopt($ch,CURLOPT_PROXYUSERPWD, $proxy['username'] . ':' . $proxy['password']);//设置账号密码 "[username]:[password]"格式的字符串。
        }
         // 获取头部信息
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_COOKIE,$cookie_str);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 15);//连接服务器前请求的时间
        curl_setopt($ch, CURLOPT_TIMEOUT , 15);//curl请求过程的时间
         // 返回原生的（Raw）输出
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         // $output contains the output string 
         $output = curl_exec($ch);

        if(!$output){
            return ['status'=>$output, 'data'=>'', 'header'=>''];
        }
        if (!empty($proxy) && $proxy['proxytype'] == 'zday'){//站大爷  当代理不为空且代理为站大爷时进入这里
            list($proxy_header, $header, $body) = explode("\r\n\r\n", $output);
        }else{
            list($header, $body) = explode("\r\n\r\n", $output);
        }

        if($if_json){
            $body = json_decode($body);
        }

         $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        //解析COOKIE
        //preg_match("/set\-cookie:([^\r\n]*)/i", $header, $matches);

        return ['status'=>$httpcode, 'data'=>$body, 'header'=>$header];

    }

    // 从Redis中随机获取一个可用IP
    public static function getIpProxyByRandom(){
        // 获取Redis对象 进行额外方法调用
        $handler = Cache::store('redis')->handler();
        // 选中第二个表
        $handler->select(2);
        // 随机获取ip-》key
        $key = $handler->randomkey();
        if ($key != ""){
            // 得到uri
            $ip = $key.":".$handler->get($key);
        }else{
            $ip = "";
        }
        return $ip;
    }
    // 数据库日志调试功能
    public static function logToDb($data,$location="",$remark=""){
        if(is_array($data)){
            $msg = json_encode($data);
        }else{
            $msg = $data;
        }
        $arr = ['msg'=>$msg,'c_time'=>date("Y-m-d h:i:sa"),"location"=>$location,"remark"=>$remark];
        Db::name("log")->insert($arr);
    }

    public static function getIpProxyByLum(){
        $url = "http://lum-customer-hl_d7719b11-zone-static-ip-213.182.204.9:s6e05p94uqwq@zproxy.lum-superproxy.io:22225";
        $ip = @Db::name('ip_us')->where('ztai',1)->orderRaw('rand()')->paginate(1)[0]['ip'];//随机获取ztai为1的Ip
        $proxy = array(
            'proxytype' => 'lum',
            'username'  => 'lum-customer-hl_d7719b11-zone-static-ip-' . $ip,
            'password'  => 's6e05p94uqwq',
            'url'       => 'zproxy.lum-superproxy.io:22225',
        );
        return $proxy;
    }


    function post_zjb($url,$post_data,$location = 0,$reffer = null,$origin = null,$host = null){

        $post_data = is_array($post_data)?http_build_query($post_data):$post_data;
        //产生一个urlencode之后的请求字符串，因为我们post，传送给网页的数据都是经过处理，一般是urlencode编码后才发送的

        $header = array( //头部信息，上面的函数已说明
            'Accept:*/*',
            'Accept-Charset:text/html,application/xhtml+xml,application/xml;q=0.7,*;q=0.3',
            'Accept-Encoding:gzip,deflate,sdch',
            'Accept-Language:zh-CN,zh;q=0.8',
            'Connection:keep-alive',
            'Content-Type:application/x-www-form-urlencoded',
            //'CLIENT-IP:'.$ip,
            //'X-FORWARDED-FOR:'.$ip,
        );

        //下面的都是头部信息的设置，请根据他们的变量名字，对应上面函数所说明

        if($origin){
            $header = array_merge_recursive($header,array("Origin:".$origin));
        }
        else{
            $header = array_merge_recursive($header,array("Origin:".$url));
        }
        if($reffer){
            $header = array_merge_recursive($header,array("Referer:".$reffer));
        }
        else{
            $header = array_merge_recursive($header,array("Referer:".$url));
        }

        $curl = curl_init();  //这里并没有带参数初始化

        curl_setopt($curl, CURLOPT_URL, $url);//这里传入url

        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);//对认证证书来源的检查，不开启次功能

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);//从证书中检测 SSL 加密算法

        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)');
        //模拟用户使用的浏览器，自己设置，我的是"Mozilla/5.0 (Windows NT 6.1; WOW64; rv:23.0) Gecko/20100101 Firefox/23.0"
       // curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $location);

        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);//自动设置referer

        curl_setopt($curl, CURLOPT_POST, 1);//开启post

        curl_setopt($curl, CURLOPT_ENCODING, "gzip" );
        //HTTP请求头中"Accept-Encoding: "的值。支持的编码有"identity"，"deflate"和"gzip"。如果为空字符串""，请求头会发送所有支持的编码类型。
        //我上面设置的是*/*

        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);//要传送的数据

        //curl_setopt($curl, CURLOPT_COOKIE, $this->cookies);//以变量形式发送cookie，我这里没用它，文件保险点

        curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookie.txt');    //存cookie的文件名，

        curl_setopt($curl, CURLOPT_COOKIEFILE, 'cookie.txt');  //发送

        curl_setopt($curl, CURLOPT_TIMEOUT, 30);//设置超时限制，防止死循环

        curl_setopt($curl, CURLOPT_HEADER, 1);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $tmpInfo = curl_exec($curl);
        if (curl_errno($curl)) {
            echo  'Curl error: ' . curl_error ( $curl );exit();
        }

        curl_close($curl);
        list($header, $body) = explode("\r\n\r\n", $tmpInfo, 2);//分割出网页源代码的头和bode

        return array("header"=>$header,"body"=>$body,"content"=>$tmpInfo);
    }

    //获取网页COOKIE
    function get_URL_cookie($url_,$params_,$referer_){

        if($url_==null){echo "get_cookie_url_null";exit;}
        if($params_==null){echo "get_params_null";exit;}
        if($referer_==null){echo "get_referer-null";exit;}
        $this_header = array("content-type: application/x-www-form-urlencoded; charset=UTF-8");//访问链接时要发送的头信息

        $ch = curl_init($url_);//这里是初始化一个访问对话，并且传入url，这要个必须有

        //curl_setopt就是设置一些选项为以后发起请求服务的


        curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);//一个用来设置HTTP头字段的数组。使用如下的形式的数组进行设置： array('Content-type: text/plain', 'Content-length: 100')
        curl_setopt($ch, CURLOPT_HEADER,1);//如果你想把一个头包含在输出中，设置这个选项为一个非零值，我这里是要输出，所以为 1

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。设置为0是直接输出

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//设置跟踪页面的跳转，有时候你打开一个链接，在它内部又会跳到另外一个，就是这样理解

        curl_setopt($ch,CURLOPT_POST,1);//开启post数据的功能，这个是为了在访问链接的同时向网页发送数据，一般数urlencode码

        curl_setopt($ch,CURLOPT_POSTFIELDS,$params_); //把你要提交的数据放这

        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');//获取的cookie 保存到指定的 文件路径，我这里是相对路径，可以是$变量


        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER ,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST ,FALSE);

        //curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');//要发送的cookie文件，注意这里是文件，还一个是变量形式发送

        //curl_setopt($curl, CURLOPT_COOKIE, $this->cookies);//例如这句就是设置以变量的形式发送cookie，注意，这里的cookie变量是要先获取的，见下面获取方式

        curl_setopt ($ch, CURLOPT_REFERER,$referer_); //在HTTP请求中包含一个'referer'头的字符串。告诉服务器我是从哪个页面链接过来的，服务器籍此可以获得一些信息用于处理。

        $content=curl_exec($ch);     //重点来了，上面的众多设置都是为了这个，进行url访问，带着上面的所有设置

        if(curl_errno($ch)){
            echo 'Curl error: '.curl_error($ch);exit(); //这里是设置个错误信息的反馈
        }

        if($content==false){
            echo "get_content_null";exit();
        }
        preg_match('/Set-Cookie:(.*);/iU',$content,$str); //这里采用正则匹配来获取cookie并且保存它到变量$str里，这就是为什么上面可以发送cookie变量的原因

        $cookie = $str[1]; //获得COOKIE（SESSIONID）

        curl_close($ch);//关闭会话

        return     $cookie;//返回cookie
    }




}
