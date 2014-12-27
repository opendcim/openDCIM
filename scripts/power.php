<?php
	require_once( '../db.inc.php' );
	require_once( '../facilities.inc.php' );

/*
	This will be replaced by the api calls later.
	Don't want to bloat out the devices.php more
	for this addition.
*/

header('Content-Type: application/json');

if(isset($_GET['deviceid'])){
	$targets=array();

	$dev=new Device();
	$dev->DeviceID=$_GET['deviceid'];
	$dev->GetDevice();

	// If we get a second device id we're looking for the ports on that device
	if(isset($_GET['thisdev'])){
		$cdevice=new PowerPorts();
		$cdevice->DeviceID=$_GET['thisdev'];
		$targets=$cdevice->GetPorts();
	}elseif(isset($_GET['getport'])){
		$pp=new PowerPorts();
		$pp->DeviceID=$_GET['deviceid'];
		$pp->PortNumber=$_GET['pn'];
		$pp->getPort();
		$pp->ConnectedDeviceLabel=null;
		$pp->ConnectedPortLabel=null;

		if(!is_null($pp->ConnectedDeviceID) && $pp->ConnectedDeviceID>0){
			$opp=new PowerPorts();
			$opp->DeviceID=$pp->ConnectedDeviceID;
			$opp->PortNumber=$pp->ConnectedPort;
			$opp->getPort();
			$pp->ConnectedPortLabel=$opp->Label;
			$tempdev=new Device();
			$tempdev->DeviceID=$opp->DeviceID;
			$tempdev->GetDevice();
			$pp->ConnectedDeviceLabel=$tempdev->Label;
		}
		$targets=$pp;
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

if(isset($_POST['saveport'])){
	$pp=new PowerPorts();
	$pp->DeviceID=$_POST['deviceid'];
	$pp->PortNumber=$_POST['pnum'];
	$pp->Label=($_POST['pname']=='')?$pp->PortNumber:$_POST['pname'];
	$pp->ConnectedDeviceID=($_POST['cdevice']=='')?null:$_POST['cdevice'];
	$pp->ConnectedPort=($_POST['cdeviceport']=='')?null:$_POST['cdeviceport'];
	$pp->Notes=$_POST['cnotes'];

	$outcome=($pp->updatePort())?1:0;

	echo json_encode($outcome);
	exit;
}

?>
