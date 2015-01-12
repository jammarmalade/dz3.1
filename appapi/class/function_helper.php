<?php  
/*
*	公用函数
*/
define('ERROR_CODE',-1);
define('SUCCESS_CODE',1);
//中文json
function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
{
	static $recursive_counter = 0;
	if (++$recursive_counter > 1000) {
		die('possible deep recursion attack');
	}
	foreach ($array as $key => $value) {
		if (is_array($value)) {
			arrayRecursive($array[$key], $function, $apply_to_keys_also);
		} else {
		//	$value=gbk2utf8($value);
			$find=array(
				"/ |\s|\[|\]|\\\/i",
				"/&#[a-z0-9]+;/i",
				"/&nbsp;/i",
				"/\"/i",
				"/&amp;/i",
				'/[\x00-\x20]|\x7f/i',
			);
			$replace=array("","","|space|","&quot;","&",'');
			$value=preg_replace($find,$replace,trim(strip_tags($value)));
			$array[$key] = $function($value);
		}
 
		if ($apply_to_keys_also && is_string($key)) {
			$new_key = $function($key);
			if ($new_key != $key) {
				$array[$new_key] = $array[$key];
				unset($array[$key]);
			}
		}
	}
	$recursive_counter--;
}

function JSON($array) {
	arrayRecursive($array, 'urlencode', true);
	$json = json_encode($array);
	return urldecode($json);
}
function gbk2utf8($str){
	return iconv('GBK','UTF-8',$str);
}
function utf82gbk($str){
	return iconv('UTF-8','GBK',$str);
}
function printarr($arr,$isexit=NULL){
	echo "<pre>";
	print_r($arr);
	echo "<pre>";
	if($isexit){
		exit();
	}
}

function file_put($contents){
	file_put_contents('d:/log.log',$contents.PHP_EOL,FILE_APPEND);
}
function errormsg($str){
	$res['status']=ERROR_CODE;
	$res['errormsg']=$str;
	header("Content-type:application/json;charset=UTF-8");
	echo json_encode($res);
	exit(); 
}

function curl_phone($curlPost,$url){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
		$return_str = curl_exec($curl);
		curl_close($curl);
		return $return_str;
}
function xml_to_array($xml){
	$reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
	if(preg_match_all($reg, $xml, $matches)){
		$count = count($matches[0]);
		for($i = 0; $i < $count; $i++){
			$subxml= $matches[2][$i];
			$key = $matches[1][$i];
			if(preg_match( $reg, $subxml )){
				$arr[$key] = xml_to_array( $subxml );
			}else{
				$arr[$key] = $subxml;
			}
		}
	}
	return $arr;
}

?>