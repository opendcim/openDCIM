<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');
  
	$dev=new Device();
	$dev->DeviceID=$_REQUEST['deviceid'];
	
	if($dev->DeviceID < 1 || !$person->WriteAccess){
		header('Location: '.redirect());
		exit();
	}
  
	$dev->Surplus();
	header('Location: '.redirect('storageroom.php'));
?>
