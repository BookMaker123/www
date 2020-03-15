<?php
/**

 *	@author Dhru.com

 *	@APi kit version 2.0 March 01, 2012

 *	@Copyleft GPL 2001-2011, Dhru.com

 *  @本文件为查询国别接口curl

 **/
if (!extension_loaded('curl'))
{
    trigger_error('cURL extension not installed', E_USER_ERROR);
}
class DhruFusion
{
    var $xmlData;
    var $xmlResult;
    var $debug;
    var $action;
    function __construct()
    {
        $this->xmlData = new DOMDocument();
    }
    function getResult()
    {
        return $this->xmlResult;
    }
    function action($action, $arr = array())
    {
        if (is_string($action))
        {
            if (is_array($arr))
            {
                if (count($arr))
                {
                    $request = $this->xmlData->createElement("PARAMETERS");
                    $this->xmlData->appendChild($request);
                    foreach ($arr as $key => $val)
                    {
                        $key = strtoupper($key);
                        $request->appendChild($this->xmlData->createElement($key, $val));
                    }
                }
                $posted = array(
                    'username' => 'longtengycg',
                    'apiaccesskey' => '158-I1G-3DL-74I-LYE-R8H-AE1-PW', // 、、158-I1G-3DL-74I-LYE-R8H-AE1-PW     U2C-2LJ-CJY-J7-D1U-KJQ-9MR-H41
                    'action' => $action,
                    'requestformat' => 'JSON',
                    'parameters' => $this->xmlData->saveHTML());
//                $posted = array(
//                    'username' => USERNAME,
//                    'apiaccesskey' => API_ACCESS_KEY,
//                    'action' => $action,
//                    'requestformat' => 'JSON',
//                    'parameters' => $this->xmlData->saveHTML());
                $crul = curl_init();
                curl_setopt($crul, CURLOPT_HEADER, false);
                curl_setopt($crul, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
                //curl_setopt($crul, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($crul, CURLOPT_RETURNTRANSFER, true);
//                curl_setopt($crul, CURLOPT_URL, DHRUFUSION_URL.'/api/index.php');
                curl_setopt($crul, CURLOPT_URL, 'http://i-imei.com/api/index.php');
                curl_setopt($crul, CURLOPT_POST, true);
                curl_setopt($crul, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($crul, CURLOPT_POSTFIELDS, $posted);
                $response = curl_exec($crul);
                if (curl_errno($crul) != CURLE_OK)
                {
                    echo curl_error($crul);
                    curl_close($crul);
                }
                else
                {
                    curl_close($crul);
                    // $response = XMLtoARRAY(trim($response));
                    if ($this->debug)
                    {
                        echo "<textarea rows='20' cols='200'> ";
                        print_r($response);
                        echo "</textarea>";
                    }
                    return (json_decode($response, true));
                }
            }
        }
        return false;
    }

    /**
     * unlock 接口
     * @param $action
     * @param array $arr
     * @return bool|mixed
     */
    function general_action($action, $arr = array(),$config = array())
    {
        if (is_string($action))
        {
            if (is_array($arr))
            {
                if (count($arr))
                {
                    $request = $this->xmlData->createElement("PARAMETERS");
                    $this->xmlData->appendChild($request);
                    foreach ($arr as $key => $val)
                    {
                        $key = strtoupper($key);
                        $request->appendChild($this->xmlData->createElement($key, $val));
                    }
                }
                $posted = array(
                    'username' => $config['username'],//'longtengycg'
                    'apiaccesskey' => $config['key'],//''
                    'action' => $action,
                    'requestformat' => 'JSON',
                    'parameters' => $this->xmlData->saveHTML());
                $crul = curl_init();
                curl_setopt($crul, CURLOPT_HEADER, false);
                curl_setopt($crul, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
                //curl_setopt($crul, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($crul, CURLOPT_RETURNTRANSFER, true);
//                curl_setopt($crul, CURLOPT_URL, DHRUFUSION_URL.'/api/index.php');
                curl_setopt($crul, CURLOPT_URL, $config['url']);//''
                curl_setopt($crul, CURLOPT_POST, true);
                curl_setopt($crul, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($crul, CURLOPT_POSTFIELDS, $posted);
                $response = curl_exec($crul);
                if (curl_errno($crul) != CURLE_OK)
                {
                    echo curl_error($crul);
                    curl_close($crul);
                }
                else
                {
                    curl_close($crul);
                    // $response = XMLtoARRAY(trim($response));
                    if ($this->debug)
                    {
                        echo "<textarea rows='20' cols='200'> ";
                        print_r($response);
                        echo "</textarea>";
                    }
                    return (json_decode($response, true));
                }
            }
        }
        return false;
    }

}
function XMLtoARRAY($rawxml)
{
    $xml_parser = xml_parser_create();
    xml_parse_into_struct($xml_parser, $rawxml, $vals, $index);
    xml_parser_free($xml_parser);
    $params = array();
    $level = array();
    $alreadyused = array();
    $x = 0;
    foreach ($vals as $xml_elem)
    {
        if ($xml_elem['type'] == 'open')
        {
            if (in_array($xml_elem['tag'], $alreadyused))
            {
                ++$x;
                $xml_elem['tag'] = $xml_elem['tag'].$x;
            }
            $level[$xml_elem['level']] = $xml_elem['tag'];
            $alreadyused[] = $xml_elem['tag'];
        }
        if ($xml_elem['type'] == 'complete')
        {
            $start_level = 1;
            $php_stmt = '$params';
            while ($start_level < $xml_elem['level'])
            {
                $php_stmt .= '[$level['.$start_level.']]';
                ++$start_level;
            }
            $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
            eval($php_stmt);
            continue;
        }
    }
    return $params;
}

?>


