<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$cabinetuse=array();
	// if user has read rights then return a search if not return blank
	$cab=new Cabinet();
	$dev=new Device();
	
	if ( isset( $_REQUEST["deviceid"] ) )
		$dev->DeviceID = $_REQUEST["deviceid"];

	$cab->CabinetID=$dev->Cabinet=intval($_REQUEST['cabinet']);
	$devList=$dev->ViewDevicesByCabinet();
	$cab->GetCabinet();
	
	$dev->BackSide=(isset($_REQUEST['backside']))?($_REQUEST['backside']=='true'?true:false):false;
	$dev->HalfDepth=(isset($_REQUEST['halfdepth']))?($_REQUEST['halfdepth']=='true'?true:false):false;
	
	if (!$dev->BackSide){
		// Build array of each position used
		foreach($devList as $key => $device) {
			// Only count space occupied by devices other than the current one
			if ( $dev->DeviceID != $device->DeviceID && (!$device->BackSide || !$device->HalfDepth || !$dev->HalfDepth)) {
				if($device->Height > 0){
					$i=$device->Height;
					while($i>0){
						$i--;
						if(!$device->HalfDepth){
							$cabinetuse[$device->Position+$i]=true;
						} else {
							$cabinetuse[$device->Position+$i]=(!$device->BackSide || !$dev->HalfDepth);
						}
					}
				}
			}
		}
	} else {
		// Build array of each position used
		foreach($devList as $key => $device) {
			// Only count space occupied by devices other than the current one
			if ( $dev->DeviceID != $device->DeviceID && ($device->BackSide || !$device->HalfDepth || !$dev->HalfDepth)) {
				if($device->Height > 0){
					$i=$device->Height;
					while($i>0){
						$i--;
						if(!$device->HalfDepth){
							$cabinetuse[$device->Position+$i]=true;
						} else {
							$cabinetuse[$device->Position+$i]=($device->BackSide || !$dev->HalfDepth);
						}
					}
				}
			}
		}
	}
	$i=$cab->CabinetHeight;
	// Fill in unused rack positions for true/false checks
	while($i>0){
		if(!isset($cabinetuse[$i])){
			$cabinetuse[$i]=false;
		}
		$i--;
	}
	// Reverse sort by rack position
	krsort($cabinetuse);
	header('Content-Type: application/json');
	echo json_encode($cabinetuse);
?>
