<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	// if user has read rights then return a search if not return blank
	$cab=new Cabinet();
	$dev=new Device();
	
	if(isset($_GET["DeviceID"])){
		$dev->DeviceID=$_GET["DeviceID"];
	}

	$cab->CabinetID=$dev->Cabinet=intval($_GET['cabinet']);
	$devList=$dev->ViewDevicesByCabinet();
	$cab->GetCabinet();

	function MarkUsed($dev,&$cabarray){
		$i=$dev->Height;

		while($i>0){
			$i--;
			if($cabarray[0]=='Bottom'){
				$u=$dev->Position+$i;
			}else{
				// upside down racks need to go the other direction
				$u=$dev->Position-$i;
			}
			//constraight the rack usage to just what is a valid rack position
			if($u<=max(array_keys($cabarray)) && $u>=1){
				$cabarray[$u]=true;
			}
		}
	}
	
	// Fill in rack positions for true/false checks
	$cabinetuse=array();
	$i=$cab->CabinetHeight;
	while($i>0){
		$cabinetuse[$i]=false;
		$i--;
	}

	// Add in the rack orientation
	$cabinetuse[0]=$cab->U1Position;
 
	$dev->BackSide=(isset($_GET['BackSide']) && $_GET['BackSide']=='true')?true:false;
	$dev->HalfDepth=(isset($_GET['HalfDepth']) && $_GET['HalfDepth']=='true')?true:false;
	
	// Build array of each position used
	foreach($devList as $key => $device) {
		// Only count space occupied by devices other than the current one
		if($dev->DeviceID!=$device->DeviceID && $device->Height >0){ 
			// If we're dealing with a half depth device then we care to about similar
			if($dev->HalfDepth){
				// Since we're dealing with a half depth then we care about a particular rack face
				if($dev->BackSide){
					// half and full depth devices on the back of the rack
					if(!$device->HalfDepth || ($device->HalfDepth && $device->BackSide)){
						MarkUsed($device,$cabinetuse);
					}
				}else{
					// half and full depth devices on the front of the rack
					if(!$device->HalfDepth || ($device->HalfDepth && !$device->BackSide)){
						MarkUsed($device,$cabinetuse);
					}
				}
			}else{
				MarkUsed($device,$cabinetuse);
			}
		}
	}

	// Reverse sort by rack position
	krsort($cabinetuse);
	header('Content-Type: application/json');
	echo json_encode($cabinetuse);
?>
