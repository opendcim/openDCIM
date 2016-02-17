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
	$deviceMinPos=$cab->StartUNum;
	$deviceMaxPos=$cab->CabinetHeight+$cab->StartUNum-1;
	$cabRowsMax=$cab->CabinetHeight+$cab->StartUNum-1;
	$cabRowsMin=$cab->StartUNum;
	$currentDevList=$devList;

	foreach ($currentDevList as $currentDevice) {
		if (($currentDevice->Position-$currentDevice->Height+1) < $deviceMinPos){
			$cabRowsMin=($cabRowsMin>$currentDevice->Position-$currentDevice->Height+1)?$currentDevice->Position-$currentDevice->Height+1:$cabRowsMin;
		} elseif ($currentDevice->Position+$currentDevice->Height-1 > $deviceMaxPos) {
			$cabRowsMax=($cabRowsMax<$currentDevice->Position+$currentDevice->Height-1)?$currentDevice->Position+$currentDevice->Height-1:$cabRowsMax;
		}
	}

	function MarkUsed($dev,&$cabarray,$cabRowsMax,$cabRowsMin){
		for ($i=$dev->Height; $i>0; $i--) {
			$u=$dev->Position-$i+1;
			if($u<=max(array_keys($cabarray)) && $u>=$cabRowsMin){
				$cabarray[$u]=$dev->DeviceID;
			}
		}
	}
	
	// Fill in rack positions for true/false checks
	$cabinetuse=array();
	for ($i=$cabRowsMax; $i>=$cabRowsMin; $i--) {
		$cabinetuse[$i]=false;

	}

	$dev->BackSide=(isset($_GET['BackSide']) && $_GET['BackSide']=='true')?true:false;
	$dev->HalfDepth=(isset($_GET['HalfDepth']) && $_GET['HalfDepth']=='true')?true:false;
	$count = 0;
	// Build array of each position used
	foreach($devList as $key => $device) {
		// Only count space occupied by devices other than the current one
		if(($dev->DeviceID!=$device->DeviceID || $count==0) && $device->Height >0){
			$count++;
			// If we're dealing with a half depth device then we care to about similar
			if($dev->HalfDepth){
				// Since we're dealing with a half depth then we care about a particular rack face
				if($dev->BackSide){
					// half and full depth devices on the back of the rack
					if(!$device->HalfDepth || ($device->HalfDepth && $device->BackSide)){
						MarkUsed($device,$cabinetuse,$cabRowsMax,$cabRowsMin);
					}
				}else{
					// half and full depth devices on the front of the rack
					if(!$device->HalfDepth || ($device->HalfDepth && !$device->BackSide)){
						MarkUsed($device,$cabinetuse,$cabRowsMax,$cabRowsMin);
					}
				}
			}else{
				MarkUsed($device,$cabinetuse,$cabRowsMax,$cabRowsMin);
			}
		}
	}

	// Reverse sort by rack position
	krsort($cabinetuse);
	$cabinetuse['UStart']=$cab->StartUNum;
	$cabinetuse['UStop']=$cab->CabinetHeight+$cab->StartUNum-1;
	$cabinetuse['U1Pos']=$cab->U1Position;
	header('Content-Type: application/json');
	echo json_encode($cabinetuse);
?>
