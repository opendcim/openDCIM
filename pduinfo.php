<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user=new User();
	$user->UserID = $_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );

	if(!$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$pdu=new PowerDistribution();
	$cab=new Cabinet();
	$powerConn=new PowerConnection();
	$connDev=new Device();
	
	if(isset($_REQUEST['pduid'])){
		$pdu->PDUID = $_REQUEST['pduid'];
	}else{
		echo 'Do not call this file directly';
		exit;
	}
	if(isset($_REQUEST['action']) && (($_REQUEST['action']=='Create') || ($_REQUEST['action']=='Update')) && $user->WriteAccess) {
		$pdu->Label=$_REQUEST['label'];
		$pdu->CabinetID=$_REQUEST['cabinetid'];
		$pdu->InputVoltage=$_REQUEST['inputvoltage'];
		$pdu->InputAmperage=$_REQUEST['inputamperage'];
		$pdu->ManagementType=$_REQUEST['managementtype'];
		$pdu->Model=$_REQUEST['model'];
		$pdu->NumOutputs=$_REQUEST['numoutputs'];
		$pdu->IPAddress=$_REQUEST['ipaddress'];
		$pdu->SNMPCommunity=$_REQUEST['snmpcommunity'];
		$pdu->FirmwareVersion=$_REQUEST['firmwareversion'];
		$pdu->PanelID=$_REQUEST['panelid'];
		$pdu->PanelPole=$_REQUEST['panelpole'];
		// If failsafe is unset clear auto transfer switch panel information
		if(isset($_REQUEST['failsafe'])){
			$pdu->FailSafe=1;
			$pdu->PanelID2=$_REQUEST['panelid2'];
			$pdu->PanelPole2=$_REQUEST['panelpole2'];
		}else{
			$pdu->FailSafe=0;
			$pdu->PanelID2="";
			$pdu->PanelPole2="";
		}

		if($_REQUEST['action']=='Create'){
			$ret=$pdu->CreatePDU($facDB);
		}else{
			$pdu->PDUID = $_REQUEST['pduid'];
			$pdu->UpdatePDU( $facDB );
		}
	}

	if($pdu->PDUID >0){
		$pdu->GetPDU($facDB);
		$upTime=$pdu->GetSmartCDUUptime($facDB);
	} else {
		$pdu->CabinetID=$_REQUEST['cabinetid'];
	}

	$cab->CabinetID=$pdu->CabinetID;
	$cab->GetCabinet($facDB);
	
	$Panel=new PowerPanel();
	$PanelList=$Panel->GetPanelList($facDB);
	/* For strict panel selection, comment out the line above and uncomment the following line */
	// $PanelList = $Panel->GetPanelsByDataCenter( $cab->DataCenterID, $facDB );

	$powerConn->PDUID=$pdu->PDUID;
	$connList=$powerConn->GetConnectionsByPDU($facDB);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Facilities Cabinet Maintenance</title>
  <link rel="stylesheet" href="css/inventory.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page pdu">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center PDU Detail</h3>
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<div class="table">
<div>
   <div><label for="pduid">PDU ID</label></div>
   <div><input type="text" name="pduid" id="pduid" value="<?php echo $pdu->PDUID; ?>" size="6" readonly></div>
</div>
<div>
   <div><label for="label">Label</label></div>
   <div><input type="text" name="label" id="label" size="50" value="<?php echo $pdu->Label; ?>"></div>
</div>
<div>
   <div><label for="cabinetid">Cabinet</label></div>
   <div><?php echo $cab->GetCabinetSelectList( $facDB ); ?></div>
</div>
<div>
   <div><label for="inputvoltage">Input Voltage</label></div>
   <div><?php echo $pdu->BuildVoltageList(); ?></div>
</div>
<div>
   <div><label for="inputamperage">Input Amperage</label></div>
   <div><input type="text" name="inputamperage" id="inputamperage" size=5 value="<?php echo $pdu->InputAmperage; ?>"></div>
</div>
<div>
   <div><label for="managementtype">ManagementType</label></div>
   <div><?php echo $pdu->GetManagementTypeSelectList($facDB); ?></div>
</div>
<div>
   <div><label for="model">Model</label></div>
   <div><input type="text" name="model" id="model" size=25 value="<?php echo $pdu->Model; ?>"></div>
</div>
<div>
   <div><label for="numoutputs">Number of Outputs</label></div>
   <div><input type="text" name="numoutputs" id="numoutputs" size=5 value="<?php echo $pdu->NumOutputs; ?>"></div>
</div>
<div>
   <div><label for="ipaddress">IP Address</label></div>
   <div><input type="text" name="ipaddress" id="ipaddress" size=15 value="<?php echo $pdu->IPAddress; ?>"> <?php if ( strlen( $pdu->IPAddress ) > 0 ) printf( "<a href=\"http://%s\" target=\"new\">http://%s</a>", $pdu->IPAddress, $pdu->IPAddress ); ?></div>
</div>
<div>
    <div>Uptime</div>
    <div><?php echo $upTime; ?></div>
</div>
<div>
    <div>Firmware Version</div>
    <div><?php echo $pdu->FirmwareVersion; ?></div>
</div>
<div>
   <div><label for="snmpcommunity">SNMP Community</label></div>
   <div><input type="text" name="snmpcommunity" id="snmpcommunity" size=15 value="<?php echo $pdu->SNMPCommunity; ?>"></div>
</div>
<div>
   <div><label for="panelid">Source Panel</label></div>
   <div><select name="panelid" id="panelid"><option value=0>Select Panel</option>
<?php
foreach($PanelList as $key=>$value){
	echo "<option value=\"$value->PanelID\"";
	if($value->PanelID == $pdu->PanelID){ echo ' selected';}
	echo ">$value->PanelLabel</option>\n"; 
  }
?>
   </select></div>
</div>
<div>
  <div><label for="panelpole">Panel Pole Number</label></div>
  <div><input type="text" name="panelpole" id="panelpole" size=4 value="<?php echo $pdu->PanelPole; ?>"></div>
</div>
<div class="caption">


<h3>Automatic Transfer Switch</h3>
<fieldset id="powerinfo">
<div class="table centermargin border">
<div>
  <div><label for="failsafe">Fail Safe Switch?</label></div>
  <div><input type="checkbox" name="failsafe" id="failsafe" <?php if($pdu->FailSafe){echo ' checked';}?>></div>
</div>
<div>
   <div><label for="panelid2">Source Panel (Secondary Source)</label></div>
   <div><select name="panelid2" id="panelid2"><option value=0>Select Panel</option>
<?php
	foreach($PanelList as $key=>$value){
		print "		<option value=$value->PanelID";
		if($value->PanelID==$pdu->PanelID2){echo' selected="selected"';}
		print ">$value->PanelLabel</option>\n";
	}
?>
   </select></div>
</div>
<div>
  <div><label for="panelpole2">Panel Pole Number (Secondary Source)</label></div>
  <div><input type="text" name="panelpole2" id="panelpole2" size=4 value="<?php echo $pdu->PanelPole2; ?>"></div>
</div>
<div class="caption">
<?php
	if($user->WriteAccess){
		if($pdu->PDUID >0){
			echo '   <input type="submit" name="action" value="Update">';
		} else {
			echo '   <input type="submit" name="action" value="Create">';
		}
	}
?>
</div>
</div> <!-- END div.table -->
</div>
</div> <!-- END div.table -->
</form>

</div><div>

<div class="table border">
	<div>
		<div>Output No.</div>
		<div>Device Name</div>
		<div>Dev Input No</div>
	</div>
<?php
	for($connNumber=1;$connNumber<$pdu->NumOutputs+1;$connNumber++){
		if(isset($connList[$connNumber])){
			$connDev->DeviceID=$connList[$connNumber]->DeviceID;
			$connDev->GetDevice($facDB);
			print "	<div>\n		<div><a href=\"power_connection.php?pdu=$pdu->PDUID"."&conn=$connNumber\">$connNumber</a></div>\n		<div><a href=\"devices.php?deviceid=".$connList[$connNumber]->DeviceID."\">$connDev->Label</a></div>\n		<div>".$connList[$connNumber]->DeviceConnNumber."</div>\n	</div>\n";
		}else{
			print "	<div>\n		<div><a href=\"power_connection.php?pdu=$pdu->PDUID"."&conn=$connNumber\">$connNumber</a></div>\n		<div></div>\n		<div></div>\n	</div>\n";
		}
	}
?>  
    </div> <!-- END div.table -->
</div></div>
<a href="cabnavigator.php?cabinetid=<?php echo $cab->CabinetID; ?>">[ Return to Navigator ]</a>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
