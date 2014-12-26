<?php
	require_once( '../db.inc.php' );
	require_once( '../facilities.inc.php' );

/*
	This will be replaced by the api calls later.
	Don't want to bloat out the devices.php more
	for this addition.
*/

header('Content-Type: application/json');

if(isset($_POST['deviceid'])){
	$targets=array();

	$dev=new Device();
	$dev->DeviceID=$_POST['deviceid'];
	$dev->GetDevice();

	// If we get a second device id we're looking for the ports on that device
	if(isset($_POST['thisdev'])){
		$cdevice=new PowerPorts();
		$cdevice->DeviceID=$_POST['thisdev'];
		$targets=$cdevice->GetPorts();
	}else{
		// Default action :: get list of devices
		$sqladdon=($dev->DeviceType!="CDU")?" AND DeviceType=\"CDU\" ":" AND DeviceType!=\"Physical Infrastructure\" AND DeviceType!=\"Patch Panel\" ";

		$sql="SELECT DeviceID, Label, Cabinet FROM fac_Device WHERE DeviceID!=$dev->DeviceID$sqladdon;";
		foreach($dbh->query($sql) as $row){
			$d=new stdClass();
			$d->DeviceID=$row['DeviceID'];
			$d->Label=$row['Label'];
			$d->CabinetID=$row['Cabinet'];
			$targets[]=$d;
		}
	}
	echo json_encode($targets);
	exit;
}
?>
