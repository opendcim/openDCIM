<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user=new User();

	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->WriteAccess && ((!isset($_REQUEST['switchid']) && !isset($_REQUEST['portid'])))){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$switchDev=new Device();
	$switchDev->DeviceID=$_REQUEST['switchid'];
	$switchDev->GetDevice($facDB);

	$connect=new SwitchConnection();
	$connect->SwitchDeviceID=$switchDev->DeviceID;
	$connect->SwitchPortNumber=$_REQUEST['portid'];
	  
	$connect->GetSwitchPortConnector($facDB);
	
	if(isset($_REQUEST['action'])){
		$connect->SwitchDeviceID=$_REQUEST['switchid'];
		$connect->SwitchPortNumber=$_REQUEST['portid'];
		if($_REQUEST['action']=="Save"){
			$connect->EndpointDeviceID=$_REQUEST['endpointdeviceid'];
			$connect->EndpointPort=$_REQUEST['endpointport'];
			$connect->Notes=$_REQUEST['notes'];
			
			if($connect->EndpointDeviceID==-1){
				$connect->RemoveConnection($facDB);
			}elseif($_REQUEST['state']=="new"){
				$connect->CreateConnection($facDB);
			}else{
				$connect->UpdateConnection($facDB);
			}
		}elseif($_REQUEST['action']=="Delete"){
			$connect->RemoveConnection($facDB);
		}
		header('Location: '.redirect("devices.php?deviceid=$connect->SwitchDeviceID"));
		exit;
	}

	$patchCandidates=$switchDev->CreatePatchCandidateList($facDB);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Network Patch</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui-1.8.18.custom.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui-1.8.18.custom.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript">
	$(function(){
		$('#patchform').validationEngine({});
	});
  </script>
</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Network Patch Connection</h3>
<div class="center"><div>
<form id="patchform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="switchid" value="<?php echo $connect->SwitchDeviceID; ?>">
<input type="hidden" name="portid" value="<?php echo $connect->SwitchPortNumber; ?>">

<?php
	if($connect->EndpointDeviceID==0){
		echo '<input type="hidden" name="state" value="new">';
	}
?>

<div class="table">
	<div>
		<div><label for="endpointdeviceid">Device Attached</label></div>
		<div><select name="endpointdeviceid" id="endpointdeviceid"><option value=-1>No Connection</option>
<?php
		foreach($patchCandidates as $key=>$devRow){
			if($connect->EndpointDeviceID==$devRow->DeviceID){$selected=" selected";}else{$selected="";}
			// Don't allow a child switch device to connect a patch to the parent chassis
			if($switchDev->ParentDevice>0 && $switchDev->ParentDevice==$devRow->DeviceID){$selected=" disabled";}
			print "		<option value=$devRow->DeviceID$selected>$devRow->Label</option>\n";
		}
?>
		</select></div>
	</div>
	<div>
		<div><label for="endpointport">Port on Device</label></div>
		<div><input type="text" class="optional,validate[custom[onlyNumberSp]]" name="endpointport" id="endpointport" value="<?php echo $connect->EndpointPort; ?>"></div>
	</div>
	<div>
		<div><label for="notes">Notes</label></div>
		<div><input type="text" name="notes" id="notes" value="<?php echo $connect->Notes; ?>"></div>
	</div>
	<div class="caption">
		<input type="submit" name="action" value="Save"><button type="button" onClick="location='<?php print redirect();?>'" value="Cancel">Cancel</button>
		<button type="submit" name="action" value="Delete">Delete</button>
	</div>
</div><!-- END div.table -->
</form>
</div></div>
<?php print "<br>\n   <a href=\"".redirect()."\">[Return to Device]</a>";?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
