<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$dev=new Device();

	// Cabinet -1 is the Storage Area
	$dev->Cabinet=-1;
	$devList=$dev->ViewDevicesByCabinet($facDB);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>Facilities Cabinet Maintenance</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page storage">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<?php echo '
<h2>',$config->ParameterArray['OrgName'],'</h2>
<h3>',__("Data Center Cabinet Inventory"),'</h3>
<div class="center"><div>
<div class="table">
	<div class="title" id="title">Storage Room</div>
	<div>
		<div>',__("Device"),'</div>
		<div>',__("Asset Tag"),'</div>
		<div>',__("Serial No."),'</div>
		<div></div>
	</div>';
	while(list($devID,$device)=each($devList)){
		echo "<div><div><a href=\"devices.php?deviceid=$devID\">$device->Label</a></div><div>$device->AssetTag</div><div>$device->SerialNo</div><div><a href=\"surplus.php?deviceid=$devID\">Surplus</a></div></div>\n";
	}
?>
</div> <!-- END div.table -->
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
