<?php
class member{
	/*用户登录  123456 e10adc3949ba59abbe56e057f20f883e
	*	$user		用户名/邮箱
	*	$pwd		密码
	* http://127.0.0.1/dz/appapi/appapi.php?appkey=123456&mod=member&do=user_login&user=cs1@qq.com&pwd=123456
	*/
	function user_login(){
		global $apptype;
		
		$user=$_GET['user'];
		$pwd=$_GET['pwd'];
		if(strlen($user) > 6 && strlen($user) <= 32 && preg_match("/^([A-Za-z0-9\-_.+]+)@([A-Za-z0-9\-]+[.][A-Za-z0-9\-.]+)$/", $user)){
			$uc=DB::fetch_first('SELECT uid,email,username,password,salt FROM %t WHERE '.DB::field("email",$user),array('ucenter_members'));
		}else{
		//	$user=utf82gbk($user);
			$uc=DB::fetch_first('SELECT uid,email,username,password,salt FROM %t WHERE '.DB::field("username",$user),array('ucenter_members'));
		}
		
		$pwd=md5($pwd.$uc['salt']);
		$return=array();
		
		if($pwd===$uc['password']){
			$userinfo=getuserbyuid($uc['uid'], 1);
			if($userinfo['uid']>0 && $userinfo['status']==0){
				if($userinfo['_inarchive']) {
					C::t('common_member_archive')->move_to_master($userinfo['uid']);
				}
				$usergroup=DB::fetch_first('SELECT grouptitle FROM '.DB::table('common_usergroup').' WHERE '.DB::field("groupid",$userinfo['groupid']));
				$userinfo['grouptitle']=$usergroup['grouptitle'];
				$return['status']=SUCCESS_CODE;
				$return['userinfo']['uid']=$userinfo['uid'];
				$return['userinfo']['email']=$userinfo['email'];
				$return['userinfo']['username']=$userinfo['username'];
				$return['userinfo']['emailstatus']=$userinfo['emailstatus'];
				$return['userinfo']['regdate']=$userinfo['regdate'];
				$return['userinfo']['avatarurl']=avatar($userinfo['uid'],'middle',true);
				$return['userinfo']['newpm']=$userinfo['newpm'];
				$return['userinfo']['newprompt']=$userinfo['newprompt'];
				$return['userinfo']['credits']=$userinfo['credits']; 
				$return['userinfo']['groupid']=$userinfo['groupid'];
				$return['userinfo']['grouptitle']=$userinfo['grouptitle'];
				$return['userinfo']['conisbind']=$userinfo['conisbind'];
				
			}else{
				$return['status']=ERROR_CODE;
				$return['errormsg']='user is delete';
			}
		}else{
			$return['status']=ERROR_CODE;
			$return['errormsg']='pwd error';
		}
		if($return['status']<0){
			return $return;
		}
		C::t('common_member_status')->update($return['userinfo']['uid'], array('lastip' =>$_G['clientip'], 'lastvisit' =>TIMESTAMP, 'lastactivity' =>TIMESTAMP));
		return $return;
	}

	/*	用户注册
	*	$username		注册昵称
	*	$password		注册密码
	*	$email			邮箱
	*	$regip			注册ip
	* http://127.0.0.1/dz/appapi/appapi.php?appkey=123456&mod=member&do=user_reg&username=%E6%B5%8B%E8%AF%953&password=123456&email=cs3@qq.com
	*/
	function user_reg(){
		global $apptype,$_G;
		loaducenter();
		
		$username=utf82gbk($_GET['username']);
		$regip=!empty($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'127.0.0.1';

		$uid = uc_user_register(addslashes($username), $_GET['password'], $_GET['email'], NULL, NULL, $regip);
		$return['status']=SUCCESS_CODE;
		$return['uid']=$uid;
		if($uid <= 0) {
			$return['status']=ERROR_CODE;
			if($uid == -1) {
				$return['errormsg']='用户名包含敏感字符';
			} elseif($uid == -2) {
				$return['errormsg']='用户名包含被系统屏蔽的字符';
			} elseif($uid == -3) {
				$return['errormsg']='该用户名已被注册';
			} elseif($uid == -4) {
				$return['errormsg']='Email 地址无效';
			} elseif($uid == -5) {
				$return['errormsg']='抱歉，Email 包含不可使用的邮箱域名';
			} elseif($uid == -6) {
				$return['errormsg']='该 Email 地址已被注册';
			} else {
				$return['errormsg']='未定义操作';
			}
			return $return;
		}

		$init_arr = array('credits' => explode(',', $_G['setting']['initcredits']), 'emailstatus' => 0);
		$dzpassword = md5(random(10));
		C::t('common_member')->insert($uid, $username, $dzpassword, $_GET['email'], $regip, $_G['setting']['newusergroupid'], $init_arr);
		
		if($_G['setting']['welcomemsg']==1){
			require_once libfile('function/member');
			$welcomemsgtxt = replacesitevar($_G['setting']['welcomemsgtxt']);
			$welcomemsgtxt = nl2br(str_replace(':', '&#58;', $welcomemsgtxt));
			notification_add($uid, 'system', $welcomemsgtxt, array('from_id' => 0, 'from_idtype' => 'welcomemsg'), 1);
		}

		$return['newusergroupid']=$_G['setting']['newusergroupid'];
		$return['avatarurl']=avatar($uid,'middle',true);
		$userinfo=DB::fetch_first('SELECT * FROM '.DB::table('common_member').' WHERE '.DB::field("uid",$uid));
		$return['userinfo']=$userinfo;

		return $return;
	}

	/*
	*	手机号注册
	*/
	function check_phone(){
		global $_G;
		$phoneN=trim($_GET['phone']);
		$return['status']=SUCCESS_CODE;
		if($_GET['type']=='send'){//发送
			if(DB::result_first('SELECT uid FROM %t WHERE '.DB::field("phone",$phoneN),array('ucenter_members'))){
				$return['status']=ERROR_CODE;
				$return['errormsg']='该手机已注册过';
			}
			$authinfo=DB::fetch_all("SELECT * FROM %t WHERE phone='%i' or ip='%i' ORDER BY dateline DESC",array('common_member_authphone',$phoneN,$_G['clientip']));
			$authinfo_phone=$authinfo_ip=array();
			foreach($authinfo as $k=>$v){
				if($v['phone']==$phoneN){
					$authinfo_phone[]=$v;
				}
				if($v['ip']==$_G['clientip']){
					$authinfo_ip[]=$v;
				}
			}
			//验证手机号
			if($authinfo_phone){
				if(count($authinfo_phone)>=5){
					$return['status']=ERROR_CODE;
					$return['errormsg']='本日发送手机验证码次数已达上限';
				}else{
					$cha = intval(TIMESTAMP) - intval($authinfo_phone[0]['dateline']);
					if($cha<=60){
						$return['status']=ERROR_CODE;
						$return['errormsg']=(60-$cha).' 秒后才能再次发送';
					}
				}
			}
			//验证ip
			if($authinfo_ip){
				if(count($authinfo_ip)>=5){
					$return['status']=ERROR_CODE;
					$return['errormsg']='本日发送手机验证码次数已达上限';
				}else{
					$cha = intval(TIMESTAMP) - intval($authinfo_ip[0]['dateline']);
					if($cha<=60){
						$return['status']=ERROR_CODE;
						$return['errormsg']=(60-$cha).' 秒后才能再次发送';
					}
				}
			}
			if($return['status']==SUCCESS_CODE){
				$target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
				$authstr=random(6,1);
				$post_user='cf_582996689';
				$post_pwd=md5('lxg0012070621');
				$post_data = 'account='.$post_user.'&password='.$post_pwd.'&mobile='.$phoneN.'&content='.rawurlencode('您的验证码是：'.$authstr.'。请不要把验证码泄露给其他人。');

			//	$res =  xml_to_array(curl_phone($post_data, $target));
				$res['SubmitResult']['code']=2;//测试
				if($res['SubmitResult']['code']==2){
					DB::insert('common_member_authphone',array(
						'phone'=>$phoneN,
						'authstr'=>$authstr,
						'ip'=>$_G['clientip'],
						'dateline'=>TIMESTAMP
					));
				}else{
					$return['status']==ERROR_CODE;
					$return['errormsg']='发送失败';
				}
			}
		}else{//验证
			$authinfo=DB::fetch_first("SELECT * FROM %t WHERE phone='%i' ORDER BY dateline DESC",array('common_member_authphone',$phoneN));
			if($authinfo){
				if($authinfo['status']){
					$return['status']==ERROR_CODE;
					$return['errormsg']='验证码错误';
				}else{
					if($authinfo['authstr']!=trim($_GET['authstr'])){
						$return['status']==ERROR_CODE;
						$return['errormsg']='验证码错误';
					}else{
						DB::update('common_member_authphone',array('status'=>1),'id='.$authinfo['id']);
					}
				}
			}else{
				$return['status']==ERROR_CODE;
				$return['errormsg']='验证码错误';
			}
		}
		return $return;
	}

}
?>