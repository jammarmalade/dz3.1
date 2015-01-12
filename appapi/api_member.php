<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$doarr=array('user_login','user_reg','check_phone');

$do=$_GET['do'];
if(!in_array($do,$doarr)){
	errormsg('Undefined operation');
}


require_once "./class/member.class.php";
$member=new member();
//登录
switch($do){
	case 'user_login':
		if(!$_GET['user']){
			errormsg('no username');
		}
		if($_GET['pwd']){
			$_GET['pwd']=md5($_GET['pwd']);//测试时自动md5
			if(strlen($_GET['pwd'])!=32){
				errormsg('pwd error');
			}
		}else{
			errormsg('no pwd');
		}
		$return=$member->user_login();
		$res['userlogin']=$return;

		break;
	case 'user_reg':
		if(empty($_GET['username']) || empty($_GET['password']) || empty($_GET['email'])){
			errormsg('no parameter');
		}
		$res['register']=$member->user_reg();

		break;
	case 'check_phone':
		if(!$_GET['phone']){
			errormsg('no phone number');
		}
		if(!in_array(trim($_GET['type']),array('send','check'))){
			errormsg('no type');
		}
		if(trim($_GET['type'])=='check'){
			if(strlen(trim($_GET['authstr']))!=6){
				errormsg('验证码错误');
			}
		}
		if(!preg_match('/^1[358][0-9]{9}$/',trim($_GET['phone']))){
			errormsg('phone number error');
		}
		$res['phone']=$member->check_phone();

		break;
}

?>