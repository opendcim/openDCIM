<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$subheader=__("Storage Room Maintenance");

	if(!$person->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$dev=new Device();

	if ( isset( $_POST["submit"]) && isset( $_POST["deviceid"]) ) {
		$dispID = $_POST["dispositionid"];
		$dList = Disposition::getDisposition( $dispID );
		if ( count( $dList ) == 1 ) {
			$devList = $_POST["deviceid"];

			foreach( $devList as $d ) {
				$dev->DeviceID = $d;
				$dev->GetDevice();
				$dev->Dispose( $dispID );
			}
		}
	}

	$dc=new DataCenter();

	$dList = Disposition::getDisposition();

	// Cabinet -1 is the Storage Area
	$dev->Cabinet=-1;
	$dc->DataCenterID=0;
	
	if (isset($_GET['dc'])){
		$dev->Position=$_GET['dc'];
		$dc->DataCenterID=$_GET['dc'];
		$dc->GetDataCenter();
		$srname=sprintf(__("%s Storage Room"), $dc->Name);
	}else{
		$dev->Position=0;
		$srname=__("General Storage Room");
	}
	$devList=$dev->ViewDevicesByCabinet();
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title><?php echo __("Storage Room Maintenance");?></title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page storage">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<?php echo '
<div class="center"><div>
<form method="POST">
<div class="table">
	<div class="title" id="title">',$srname,'</div>
	<div>
		<div>',__("Device"),'</div>
		<div>',__("Asset Tag"),'</div>
		<div>',__("Serial No."),'</div>
		<div></div>
	</div>';
	while(list($devID,$device)=each($devList)){
		// filter the list of devices in storage rooms to only show the devices for this room
		if($device->Position==$dc->DataCenterID){
			echo "<div><div><a href=\"devices.php?DeviceID=$device->DeviceID\">$device->Label</a></div><div>$device->AssetTag</div><div>$device->SerialNo</div><div><input type=\"checkbox\" name=\"deviceid[]\" value=\"$device->DeviceID\"></div></div>\n";
		}
	}

	print "<div><div>";
	print __("Dispose of selected devices to:");
	print "</div><div><select name=\"dispositionid\">";
	foreach( $dList as $disp ) {
		if ( $disp->Status == "Active" ) {
			print "<option value=$disp->DispositionID>$disp->Name</option>";
		}
	}
	print "</select></div><div><input type=\"submit\" name=\"submit\" value=\"Go\"></div>";
	print "</div></div>";
?>
</form>
</div> <!-- END div.table -->
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
