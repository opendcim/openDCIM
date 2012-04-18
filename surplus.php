<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');
  
	$dev=new Device();
	$dev->DeviceID=$_REQUEST['deviceid'];
	$user=new User();
	
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );

	if($dev->DeviceID < 1 || !$user->WriteAccess){
		header('Location: '.redirect());
		die();
	}
  
	$dev->Surplus($facDB);
	header('Location: '.redirect('storageroom.php'));
?>
