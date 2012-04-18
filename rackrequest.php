<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );
	require_once( 'Rmail/Rmail.php' );
	
	$dev = new Device();
	$req = new RackRequest();
	$user = new User();
	
	$user->UserID = $_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );
	
	if(!$user->RackRequest){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$Dept = new Department();
	$cab = new Cabinet();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Facilities Cabinet Maintenance</title>
  <link rel="stylesheet" href="css/inventory.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript">
function checkdata(formname) {
  if ( formname.label.value.length < 3 ) {
    alert( "You must specify a name of at least 3 chars for this device to continue." );
    formname.elements['label'].focus();
    return false;
  }
  
  if ( formname.serialno.value.length < 1 ) {
    alert( "You must enter the serial number of the device to continue." );
    formname.elements['serialno'].focus();
    return false;
  }
  
  if ( formname.owner.options[0].selected ) {
    alert( "You must select a departmental owner for this device to continue." );
    formname.elements['owner'].focus();
    return false;
  }
    
  if ( formname.deviceheight.value < 1 ) {
    alert( "You may not have a device height of less than 1U." );
    formname.elements['height'].focus();
    return false;
  }
  
  if ( ( formname.ethernetcount.value > 1 ) && ( formname.vlanlist.value.length < 1 ) ) {
    alert( "You must specify the VLAN information for the ethernet connections." );
    formname.elements['vlanlist'].focus();
    return false;
  }
  
  if ( ( formname.sancount.value > 1 ) && ( formname.sanlist.value.length < 1 ) ) {
    alert( "You must specify the SAN port information to continue." );
    formname.elements['vlanlist'].focus();
    return false;
  }
   
  if ( formname.devicetype.options[0].selected ) {
    alert( "You must select a device type to continue." );
    formname.elements['devicetype'].focus();
    return false;
  }
  
  if ( formname.currentlocation.value.length < 1 ) {
    alert( "You must specify the current location of the equipment." );
    formname.elements['currentlocation'].focus();
    return false;
  }
  
  // formname.submit();
  return true;
}
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
<h3>Data Center Rack Request</h3>
<div class="center"><div>
<?php
	$message = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=windows-1252\">
<title>ORNL ITS Data Center Inventory</title>
<body>
<div style=\"height: 66px;\" id=\"header\"><img src='masthead3.png'></div>
<div style=\"position: absolute; top: 100px; left: 3px; width: 200px; height: auto;\">
<p>";

	$message .= '<h3>ITS Facilities Rack Request</h3>';

  $mail = new Rmail();
  $mail->setSMTPParams( 'smtp.vanderbilt.edu', 25, 'its.vanderbilt.edu' );
  $mail->setFrom( 'ITS Network Operations Center <noc@vanderbilt.edu>' );
  $mail->setSubject( 'ITS Facilities Rack Request' );

	$tmpUser = new User();
	
	if(isset($_REQUEST['action'])&& ($user->WriteAccess && ($_REQUEST['action'] == 'Create'))){
  	$req->RequestorID = $user->UserID;
    $req->Label = $_REQUEST['label'];
    $req->SerialNo = $_REQUEST['serialno'];
    $req->MfgDate = $_REQUEST['mfgdate'];
    $req->AssetTag = $_REQUEST['assettag'];
    $req->ESX = $_REQUEST['esx'];
    $req->Owner = $_REQUEST['owner'];
    $req->DeviceHeight = $_REQUEST['deviceheight'];
    $req->EthernetCount = $_REQUEST['ethernetcount'];
    $req->VLANList = $_REQUEST['vlanlist'];
    $req->SANCount = $_REQUEST['sancount'];
    $req->SANList = $_REQUEST['sanlist'];
    $req->DeviceClass = $_REQUEST['deviceclass'];
    $req->DeviceType = $_REQUEST['devicetype'];
    $req->LabelColor = $_REQUEST['labelcolor'];
    $req->CurrentLocation = $_REQUEST['currentlocation'];
    $req->SpecialInstructions = $_REQUEST['specialinstructions'];
    
    $req->CreateRequest( $facDB );
    
    $message .= '<p>Your request for racking up the device labeled ' . $req->Label . ' has been received.
        The Network Operations Center will examine the request and contact you if more information is needed
        before the request can be processed.  You will receive a notice when this request has been completed.
				Please allow up to 2 business days for requests to be completed.</p>';

		$message .= '<p>Your Request ID is ' . $req->RequestID . ' and you may view the request online at
				<a href=\'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . '?requestid=' . $req->RequestID . '\'>
				this link</a>.';
    
    $tmpUser->UserID = $req->RequestorID;
    $email = $tmpUser->GetBCA( $facDB );

  	$mail->setHtml( $message );
    $mail->send( array( $email, 'noc@vanderbilt.edu' ), 'smtp' );

  }elseif(isset($_REQUEST['action']) && ($user->WriteAccess && ($_REQUEST['action'] == 'Update Request'))){
	  $req->RequestID = $_REQUEST['requestid'];
	  
    $req->Label = $_REQUEST['label'];
    $req->SerialNo = $_REQUEST['serialno'];
    $req->MfgDate = $_REQUEST['mfgdate'];
    $req->AssetTag = $_REQUEST['assettag'];
    $req->ESX = $_REQUEST['esx'];
    $req->Owner = $_REQUEST['owner'];
    $req->DeviceHeight = $_REQUEST['deviceheight'];
    $req->EthernetCount = $_REQUEST['ethernetcount'];
    $req->VLANList = $_REQUEST['vlanlist'];
    $req->SANCount = $_REQUEST['sancount'];
    $req->SANList = $_REQUEST['sanlist'];
    $req->DeviceClass = $_REQUEST['deviceclass'];
    $req->DeviceType = $_REQUEST['devicetype'];
    $req->LabelColor = $_REQUEST['labelcolor'];
    $req->CurrentLocation = $_REQUEST['currentlocation'];
    $req->SpecialInstructions = $_REQUEST['specialinstructions'];

    $req->UpdateRequest( $facDB );
	}elseif(isset($_REQUEST['action']) && ($user->RackAdmin && ($_REQUEST['action'] == 'Move to Rack'))){
		$req->RequestID = $_REQUEST['requestid'];
		$req->GetRequest( $facDB );
		
		$req->CompleteRequest( $facDB );
		
		$dev->Label = $req->Label;
		$dev->SerialNo = $req->SerialNo;
		if ( $req->MfgDate > '0000-00-00 00:00:00' )
		  $dev->MfgDate = $req->MfgDate;
		
		$dev->InstallDate = date( 'Y-m-d' );
		$dev->AssetTag = $req->AssetTag;
		$dev->ESX = $req->ESX;
		$dev->Owner = $req->Owner;
		$dev->Cabinet = $_REQUEST['cabinetid'];
		$dev->Position = $_REQUEST['position'];
		$dev->Height = $req->DeviceHeight;
		$dev->Ports = $req->EthernetCount;
		$dev->DeviceType = $req->DeviceType;
		$dev->TemplateID = $req->DeviceClass;
		
		$dev->UpdateWattageFromTemplate( $facDB );

		$dev->CreateDevice( $facDB );
		
    $message .= '<p>Your request for racking up the device labeled ' . $req->Label . ' has been completed.</p>';

		$message .= '<p>To view your device in its final location
				<a href=\'https://' . $_SERVER['SERVER_NAME'] . '/devices.php?deviceid=' . $dev->DeviceID . '\'>
				this link</a>.';

    $tmpUser->UserID = $req->RequestorID;
    $email = $tmpUser->GetBCA( $facDB );

  	$mail->setHtml( $message, dirname(__FILE__) . '/css/' );
    $mail->send( array( $email ), 'smtp' );

		printf( "<meta http-equiv=\"refresh\" content=\"0; url=devices.php?deviceid=%d\">\n", $dev->DeviceID );
		
		exit;
  }elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'Delete Request' )){
    $req->RequestID = $_REQUEST['requestid'];
    $req->GetRequest( $facDB );
    
    if ( $user->UserID == $req->RequestorID ) {
      $req->DeleteRequest( $facDB );
		}

		printf( "<meta http-equiv=\"refresh\" content=\"0; url=index.php\">\n" );
	}
	
  if ( @$_REQUEST['requestid'] > 0 ) {
    $req->RequestID = $_REQUEST['requestid'];
    $req->GetRequest( $facDB );
  }
  
	echo "<form name=\"deviceform\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
	echo "<input type=\"hidden\" name=\"requestid\" value=\"$req->RequestID\">\n";
?>
<div class="table">
<div>
   <div><label for="requestor">Requestor</label></div>
   <div><input type="text" name="requestor" id="requestor" size="50" value="<?php echo $user->Name; ?>" readonly></div>
</div>
<div>
   <div><label for="label">Label</label></div>
   <div><input type="text" name="label" id="label" size="50" value="<?php echo $req->Label; ?>"></div>
</div>
<div>
   <div><label for="labelcolor">Label Color</label></div>
   <div>
      <select name="labelcolor" id="labelcolor">
<?php
  foreach ( array( 'Black', 'Yellow', 'Orange' ) as $colorCode ) {
    if ( $req->LabelColor == $colorCode )
      $selected = 'SELECTED';
    else
      $selected = '';
    
    printf( "<option value=\"%s\" %s>%s</option>\n", $colorCode, $selected, $colorCode );
  }
?>
  </select>
   </div>
</div>
<div>
   <div><label for="serialno">Serial Number</label></div>
   <div><input type="text" name="serialno" id="serialno" size="50" value="<?php echo $req->SerialNo; ?>"></div>
</div>
<div>
   <div><label for="mfgdate">Manufacture Date</label></div>
   <div><input type="text" name="mfgdate" id="mfgdate" size="20" value="<?php echo date( 'm/d/Y', strtotime( $req->MfgDate ) ); ?>"></div>
</div>
<div>
   <div><label for="assettag">Asset Tag</label></div>
   <div><input type="text" name="assettag" id="assettag" size="20" value="<?php echo $req->AssetTag; ?>"></div>
</div>
<div>
   <div><label for="esx">ESX Server?</label></div>
   <div><select name="esx" id="esx"><option value="1" <?php echo ($req->ESX == 1) ? 'SELECTED' : ''; ?>>True</option><option value="0" <?php echo ($dev->ESX == 0) ? 'SELECTED' : ''; ?>>False</option></select></div>
</div>
<div>
   <div><label for="owner">Departmental Owner</label></div>
   <div><select name="owner" id="owner">
	<option value=0>Unassigned</option>
<?php

	$deptList = $Dept->GetDepartmentList( $facDB );

	foreach( $deptList as $deptRow ) {
		if ( $req->Owner == $deptRow->DeptID )
			$selected = 'selected';
		else
			$selected = '';
	
			printf( "<option value=\"%2d\" %s>%s</option>\n", $deptRow->DeptID, $selected, $deptRow->Name );
	}
?>
	</select>
   </div>
</div>
<div>
   <div><label for="deviceheight">Height</label></div>
   <div><input type="text" name="deviceheight" id="deviceheight" size="15" value="<?php echo $req->DeviceHeight; ?>"></div>
</div>
<div>
   <div><label for="ethernetcount">Number of Ethernet Connections</label></div>
   <div><input type="text" name="ethernetcount" id="ethernetcount" size="15" value="<?php echo $req->EthernetCount; ?>"></div>
</div>
<div>
   <div><label for="vlanlist">VLAN Settings<span>(ie - eth0 on 973, eth1 on 600)</span></label></div>
   <div><input type="text" name="vlanlist" id="vlanlist" size="50" value="<?php echo $req->VLANList; ?>"></div>
</div>
<div>
   <div><label for="sancount">Number of SAN Connections</label></div>
   <div><input type="text" name="sancount" id="sancount" size="15" value="<?php echo $req->SANCount; ?>"></div>
</div>
<div>
   <div><label for="sanlist">SAN Port Assignments</label></div>
   <div><input type="text" name="sanlist" id="sanlist" size="50" value="<?php echo $req->SANList; ?>"></div>
</div>
<div>
   <div><label for="deviceclass">Device Class</label></div>
   <div><select name="deviceclass" id="deviceclass">
    <option value=0>Select a template...</option>
<?php
  $templ = new DeviceTemplate();
  $templateList = $templ->GetTemplateList( $facDB );
  $mfg = new Manufacturer();
  
  foreach ( $templateList as $tempRow ) {
    if ( $req->DeviceClass == $tempRow->TemplateID )
      $selected = 'selected';
    else
      $selected = '';
      
    $mfg->ManufacturerID = $tempRow->ManufacturerID;
    $mfg->GetManufacturerByID( $facDB );
    
    printf( "<option value=\"%s\" %s>%s - %s</option>\n", $tempRow->TemplateID, $selected, $mfg->Name, $tempRow->Model );
  }
?>
    </select>
   </div>
</div>
<div>
   <div><label for="devicetype">Device Type</label></div>
   <div><select name="devicetype" id="devicetype">
<?php
  printf( "<option value=0>Select...</option>\n" );
    
  foreach ( array( 'Server', 'Appliance', 'Storage Array', 'Switch', 'Routing Chassis', 'Patch Panel', 'Physical Infrastructure' ) as $devType ) {
    if ( $devType == $req->DeviceType )
      $selected = 'SELECTED';
    else
      $selected = '';
      
    printf( "<option value=\"%s\" %s>%s</option>\n", $devType, $selected, $devType );
  }
?>
  </select></div>
</div>
<div>
   <div><label for="currentlocation">Current Location</label></div>
   <div><input type="text" name="currentlocation" id="currentlocation" size="50" value="<?php echo $req->CurrentLocation; ?>"></div>
</div>
<div>
   <div><label for="specialinstructions">Special Instructions</label></div>
   <div><textarea name="specialinstructions" id="specialinstructions" cols=50 rows=5><?php echo $req->SpecialInstructions; ?></textarea></div>
</div>
<?php
	if($user->RackAdmin && ($req->RequestID>0)){
		$rackHTML = $cab->GetCabinetSelectList( $facDB ) . " Position: <input type=\"text\" name=\"position\" size=5>";
		echo "<div><div><label for=\"cabinetid\">Select Rack Location:</label></div><div>$rackHTML</div></div>\n";
	}
?>
<div class="caption">
<?php
	if ( $user->WriteAccess ) {
		if ( $req->RequestID > 0 ) {
			if($user->RackAdmin && ($req->RequestID>0)){
				echo '<input type="submit" name="action" value="Move to Rack">';
			}
			echo '<input type="submit" name="action" value="Update Request" onclick="return(checkdata(deviceform))">';
			if ( $user->DeleteAccess ) {
				echo '<input type="submit" name="action" value="Delete Request" onclick="deviceform.submit()">';
			}
		}else{
			echo '<input type="submit" name="action" value="Create" onclick="return(checkdata(deviceform))">';
		}
	}
	echo '</div>';
?>
</div> <!-- END div.table -->
</form>
</div></div>
<a href="index.php">[ Return to Main Menu ]</a>
</div> <!-- END div.main -->
</div> <!-- END div.page -->
</body>
</html>
