<?php
require('vendor/autoload.php');
date_default_timezone_set('Asia/Taipei');
session_start();

use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookSession;


if( isset($_GET['logout']) ){
	session_destroy();
	header('Location: ./');
}

FacebookSession::setDefaultApplication(FB_Group::FB_APPID, FB_Group::FB_APPSECRET);
if( isset($_GET['login']) ){
	$helper = new FacebookRedirectLoginHelper(Conf::LOGIN_URL);
	try {
		$session = $helper->getSessionFromRedirect();
		if( $session && $session->validate() ){
			//$_SESSION['fb_token'] = $session->getToken();
			$_SESSION['fb_session'] = $session;
			header('Location: ./');	
		}
	} catch( Exception $ex ) {}
	echo View::make('msg')->with('msg', 'Oops! Something error!');
	exit;
}

if(!$_SESSION['fb_session']) {
	$helper = new FacebookRedirectLoginHelper(Conf::LOGIN_URL);
	$loginUrl = $helper->getLoginUrl(array('user_groups'));
	$msg = "Need Facebook Authorize. Click Login Button.";
	echo View::make('msg')->with('loginUrl', $loginUrl)->with('msg', $msg);
	exit;
}


$FB_Group = new FB_Group($_SESSION['fb_session']);
$view = new View();
$view->with('userID', $FB_Group->getUserInfo()['id']);

echo 'If your can see this, means installtion successful';
/*
	if( !$FB_Group->checkisMember() ){
		$msg = 'You are not one of group member';
		echo $view->load('msg')->with('msg', $msg);
		exit;
	}

	//load posts data	
	$param = array(
		'since' => date('Y-m-d 00:00'),
		'until' => date('Y-m-d 23:59'),
		'filter' => null,
		'limit' => 10
	);
	$title = "Last 10 Photos";
	$cacheFile = 'top10.dat';
	$cacheTime = '2 hours';
	$tempCached = true;
	$posts = $FB_Group->getCacheFeed($param, $cacheFile, $cacheTime);

	if($tempCached){
		$updateTime = $posts['info']['cacheChangeTime'];
		if( $updateTime ){
			$title .= "<br><span class='badge'>Last Update: ".date('H:i', $updateTime)."</span>";
		}
	}
*/

//runtime infomation
$fbGraphTime = 0;
foreach($FB_Group->getDebug() as $row){
	if($row['type'] == 'getGraphResponse'){
		$fbGraphTime += $row['execTime'];
	}
}

//footer infomation
$runtime = array();
$runtime['fbGraphTime'] = $fbGraphTime;
$runtime['counter'] = $posts['info']['counter'];


if(0){
	echo '<pre>';
	print_r($posts);
	echo 'Debug:';
	print_r($FB_Group->getDebug());
	echo 'Error:';
	print_r($FB_Group->getError());
	echo '</pre>';
}

echo $view->load('posts')->with('posts', $posts)->with('title', $title)
		  ->with('runtime', $runtime);
exit;
?>