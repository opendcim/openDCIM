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
		$pdu->PDUID=(isset($_POST['pduid']) ? $_POST['pduid'] : $_GET['pduid']);
	}else{
		echo 'Do not call this file directly';
		exit;
	}
	if(isset($_POST['action']) && (($_POST['action']=='Create') || ($_POST['action']=='Update')) && $user->WriteAccess) {
		$pdu->Label=$_POST['label'];
		$pdu->CabinetID=$_POST['cabinetid'];
		$pdu->InputAmperage=$_POST['inputamperage'];
		$pdu->ManagementType=$_POST['managementtype'];
		$pdu->Model=$_POST['model'];
		$pdu->NumOutputs=$_POST['numoutputs'];
		$pdu->IPAddress=$_POST['ipaddress'];
		$pdu->SNMPCommunity=$_POST['snmpcommunity'];
		$pdu->FirmwareVersion=$_POST['firmwareversion'];
		$pdu->PanelID=$_POST['panelid'];
		$pdu->BreakerSize=$_POST['breakersize'];
		$pdu->PanelPole=$_POST['panelpole'];
		// If failsafe is unset clear auto transfer switch panel information
		if(isset($_POST['failsafe'])){
			$pdu->FailSafe=1;
			$pdu->PanelID2=$_POST['panelid2'];
			$pdu->PanelPole2=$_POST['panelpole2'];
		}else{
			$pdu->FailSafe=0;
			$pdu->PanelID2="";
			$pdu->PanelPole2="";
		}

		if($_POST['action']=='Create'){
			$ret=$pdu->CreatePDU($facDB);
		}else{
			$pdu->PDUID = $_POST['pduid'];
			$pdu->UpdatePDU( $facDB );
		}
	}

	if($pdu->PDUID >0){
		$pdu->GetPDU($facDB);
		$upTime=$pdu->GetSmartCDUUptime($facDB);
	} else {
		$pdu->CabinetID=$_POST['cabinetid'];
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
<!doctype html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>Facilities Cabinet Maintenance</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->

  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript">

	$(document).ready(function() {
		$('#panelid').change( function(){
			$.get('scripts/ajax_panel.php?q='+$(this).val(), function(data) {
				$('#voltage').html(data['PanelVoltage'] +'/'+ Math.floor(data['PanelVoltage']/1.73));
			});
		});
	});
  </script>


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
<form name="pduform" id="pduform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
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
   <div><select name="panelid" id="panelid" ><option value=0>Select Panel</option>
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
	<div><label for="voltage">Voltages:</label></div>
	<div id="voltage">
<?php
	if ( $pdu->PanelID > 0 ) {
		$pnl = new PowerPanel();
		$pnl->PanelID = $pdu->PanelID;
		$pnl->GetPanel( $facDB );
		
		printf( "%d / %d", $pnl->PanelVoltage, intval( $pnl->PanelVoltage / 1.73 ) );
	}
?>
	</div>
</div>
<div>
  <div><label for="breakersize">Breaker Size (# of Poles)</label></div>
  <div>
	<select name="breakersize">
<?php
	for ( $i = 1; $i < 4; $i++ ) {
		if ( $i == $pdu->BreakerSize )
			$selected = "SELECTED";
		else
			$selected = "";
			
		printf( "<option value=\"%d\" %s>%d</option>\n", $i, $selected, $i );
	}
?>
	</select>
  </div>
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
