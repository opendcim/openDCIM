<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

#	$sql="Select DeviceID from fac_Device WHERE DeviceID>=5000;";
#	foreach($dbh->query($sql) as $dcvrow){
#		$dev=new Device($dcvrow['DeviceID']);
#		$dev->DeleteDevice();
#	}

$dev=new Device();
$dev->ParentDevice=0;
$payload=$dev->Search();


foreach($payload as $i => $device){
	$device->UpdateDeviceCache();
}
#function GetDevicePicture($rear=false,$targetWidth=220,$nolinks=false)
print_r($payload);
