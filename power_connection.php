<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user = new User();
	$user->UserID = $_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );

	// There is no need to access this screen if you don't have at least write rights.
	if(!isset($_REQUEST['pdu']) && !isset($_REQUEST['conn']) && !$user->WriteAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$pduID = $_REQUEST['pdu'];
	$connID = $_REQUEST['conn'];
  
	$dev=new Device();
	$pdu=new PowerDistribution();
	$connection=new PowerConnection();
  
	$pdu->PDUID=$pduID;
	$pdu->GetPDU($facDB);
  
	$connection->PDUID=$pdu->PDUID;
	$connection->PDUPosition=$connID;

	if(isset($_REQUEST['action'])){
		if($_REQUEST['action']=='Save'){
			$connection->DeviceID = $_REQUEST['deviceid'];
			$connection->DeviceConnNumber = $_REQUEST['inputnum'];
			$connection->CreateConnection( $facDB );
			
			$url=redirect("pduinfo.php?pduid=$connection->PDUID");
			header("Location: $url");
			exit;
		}elseif($_REQUEST['action']=='Delete'){
			$connection->RemoveConnection($facDB);
			$url=redirect("pduinfo.php?pduid=$connection->PDUID");
			header("Location: $url");
			exit;
		}
	}
  
	$connection->GetPDUConnectionByPosition($facDB);
	$dev->Cabinet=$pdu->CabinetID;
	$devList=$dev->ViewDevicesByCabinet($facDB);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Power Connection Management</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<h2>openDCIM</h2>
<h3>PDU Connection</h3>
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<input type="hidden" name="pdu" value="<?php echo $pduID; ?>">
<input type="hidden" name="connid" value="<?php echo $connID; ?>">
<div class="table">
<div>
   <div><label for="conn">Output Number</label></div>
   <div><input type="text" name="conn" id="conn" value="<?php echo $connID; ?>" size="3" readonly></div>
</div>
<div>
    <div><label for="deviceid">Device Attached</label></div>
    <div><select name="deviceid" id="deviceid"><option value=0>No Connection</option>
<?php
	foreach($devList as $key=>$devRow){
		print "<option value=\"$devRow->DeviceID\"";
	    if($connection->DeviceID==$devRow->DeviceID){ echo ' selected="selected"';}
		print ">$devRow->Label</option>\n"; 
	}
?>
    </select></div>
</div>
<div>
  <div><label for="inputnum">Power Input on Device</label></div>
  <div><input type="text" name="inputnum" id="inputnum" value="<?php echo $connection->DeviceConnNumber; ?>"></div>
</div>
<div class="caption">
  <input name="action" type="submit" value="Save">
<?php
	if($user->SiteAdmin){
		echo '	<input name="action" type="submit" value="Delete">';
	}
?>
	<button type="reset" onclick="document.location.href='pduinfo.php?pduid=<?php echo $connection->PDUID; ?>'; return false;">Cancel</button>
</div>
</div><!-- END div.table -->
</form>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
