<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$doarr=array('actview');
if(!in_array($_GET['do'],$doarr)){
	showmessage(lang('forum/template', 'map_undefined_action'));
}
if(!$_G['tid'] || $_G['forum_thread']['special']!=4){
	showmessage(lang('forum/template', 'map_not_activity'));
}
$navigation = '<a href="forum.php?mod=viewthread&tid='.$_G['forum_thread']['tid'].'&extra=page%3D1">'.$_G['forum_thread']['subject'].'</a>';
$lbs=dunserialize($_G['setting']['lbs']);
$activity = C::t('forum_activity')->fetch($_G['tid']);

include template('forum/map');
?>