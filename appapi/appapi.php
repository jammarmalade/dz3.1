<?php

require '../source/class/class_core.php';
C::app()->init();
require './class/function_helper.php';

$modarray = array('member'
);

$mod=$_GET['mod'];
if(!in_array($mod,$modarray)){
	errormsg('Undefined request');
}
global $apptype,$ios_version,$android_version;
if($_GET['appkey']=='123456'){
	$apptype='ios';
	$ios_version=!empty($_GET['version']) ? $_GET['version'] : 0;
}elseif($_GET['appkey']=='654321'){
	$apptype='android';
	$android_version=!empty($_GET['version']) ? $_GET['version'] : 0;
}elseif($_GET['appkey']=='456789'){
	$apptype='extend';
}else{
	errormsg('illegal request');
}
require 'api_'.$mod.'.php';
$res=JSON($res);
header("Content-type:application/json;charset=UTF-8");

echo $res;

?>