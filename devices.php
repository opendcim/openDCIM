<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$dev=new Device();
	$cab=new Cabinet();
	$user=new User();
	$contact=new Contact();
	
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );

	if(!$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	// These objects are used no matter what operation we're performing
	$templ=new DeviceTemplate();
	$mfg=new Manufacturer();
	$esc=new Escalations();
	$escTime=new EscalationTimes();
	$contactList=$contact->GetContactList($facDB);
	$Dept=new Department();
	$pwrCords=null;

	// This page was called from somewhere so let's do stuff.
	// If this page wasn't called then present a blank record for device creation.
	if(isset($_REQUEST['action'])||isset($_REQUEST['deviceid'])){
		if(isset($_REQUEST['action'])&&$_REQUEST['action']=='new'){
			// Some fields are pre-populated when you click "Add device to this cabinet"
			if(isset($_REQUEST['cabinet'])){
				$dev->Cabinet = $_REQUEST['cabinet'];
			}
		}
		// if no device id requested then we must be making a new device so skip all data lookups.
		if(isset($_REQUEST['deviceid'])){
			$dev->DeviceID=$_REQUEST['deviceid'];
			// If no action is requested then we must be just querying a device info.
			// Skip all modification checks
			if(isset($_REQUEST['action'])){
				if($user->WriteAccess&&(($dev->DeviceID >0)&&($_REQUEST['action']=='Update'))){
					$dev->Label=$_REQUEST['label'];
					$dev->SerialNo=$_REQUEST['serialno'];
					$dev->AssetTag=$_REQUEST['assettag'];
					$dev->PrimaryIP=$_REQUEST['primaryip'];
					$dev->SNMPCommunity=$_REQUEST['snmpcommunity'];
					$dev->ESX=$_REQUEST['esx'];
					$dev->Owner=$_REQUEST['owner'];
					$dev->EscalationTimeID=$_REQUEST['escalationtimeid'];
					$dev->EscalationID=$_REQUEST['escalationid'];
					$dev->PrimaryContact=$_REQUEST['primarycontact'];
					$dev->Cabinet=$_REQUEST['cabinetid'];
					$dev->Position=$_REQUEST['position'];
					$dev->Height=$_REQUEST['height'];
					$dev->Ports=$_REQUEST['ports'];
					$dev->TemplateID=$_REQUEST['templateid'];
					$dev->PowerSupplyCount=$_REQUEST['powersupplycount'];
					$dev->DeviceType=$_REQUEST['devicetype'];
					$dev->MfgDate=date('Y-m-d',strtotime($_REQUEST['mfgdate']));
					$dev->InstallDate=date('Y-m-d',strtotime($_REQUEST['installdate']));
					$dev->Notes=$_REQUEST['notes'];
					$dev->Reservation = ( $_REQUEST['reservation'] == "on" ) ? 1 : 0;

					if($dev->TemplateID >0){
						$dev->UpdateWattageFromTemplate($facDB);
					}else{
						$dev->NominalWatts=$_REQUEST['nominalwatts'];
					}
			
					if($dev->Cabinet <0){
						$dev->MoveToStorage($facDB);
					}else{
						$dev->UpdateDevice($facDB);
					}
				}elseif($user->WriteAccess&&($_REQUEST['action']=='Create')){
					$dev->Label=$_REQUEST['label'];
					$dev->SerialNo=$_REQUEST['serialno'];
					$dev->AssetTag=$_REQUEST['assettag'];
					$dev->PrimaryIP=$_REQUEST['primaryip'];
					$dev->SNMPCommunity=$_REQUEST['snmpcommunity'];
					$dev->ESX=$_REQUEST['esx'];
					$dev->Owner=$_REQUEST['owner'];
					$dev->EscalationTimeID=$_REQUEST['escalationtimeid'];
					$dev->EscalationID=$_REQUEST['escalationid'];
					$dev->PrimaryContact=$_REQUEST['primarycontact'];
					$dev->Cabinet=$_REQUEST['cabinetid'];
					$dev->Position=$_REQUEST['position'];
					$dev->Height=$_REQUEST['height'];
					$dev->Ports=$_REQUEST['ports'];
					$dev->TemplateID=$_REQUEST['templateid'];
					$dev->PowerSupplyCount=$_REQUEST['powersupplycount'];
					$dev->DeviceType=$_REQUEST['devicetype'];
					$dev->MfgDate=date('Y-m-d',strtotime($_REQUEST['mfgdate']));
					$dev->InstallDate=date('Y-m-d',strtotime($_REQUEST['installdate']));
					$dev->Notes=$_REQUEST['notes'];
					$dev->Reservation = ( $_REQUEST['reservation'] == "on" ) ? 1 : 0;
					$dev->CreateDevice($facDB);
				}elseif($user->DeleteAccess&&($_REQUEST['action']=='Delete')){
					$dev->GetDevice($facDB);
					$dev->DeleteDevice($facDB);
					header('Location: '.redirect("cabnavigator.php?cabinetid=$dev->Cabinet"));
					exit;
				}
			}

			// Finished updating devices or creating them.  Refresh the object with data from the DB
			$dev->GetDevice($facDB);

			// Since a device exists we're gonna need some additional info
			$pwrConnection = new PowerConnection();
			$pdu = new PowerDistribution();
			$panel = new PowerPanel();
			$networkPatches = new SwitchConnection();

			$pwrConnection->DeviceID=$dev->DeviceID;
			$pwrCords=$pwrConnection->GetConnectionsByDevice($facDB);

			if($dev->DeviceType=='Switch'){
				$networkPatches->SwitchDeviceID=$dev->DeviceID;
				$patchList=$networkPatches->GetSwitchConnections($facDB);
			}else{
				$networkPatches->EndpointDeviceID=$dev->DeviceID;
				$patchList=$networkPatches->GetEndpointConnections($facDB);
			}
		}
		$cab->CabinetID=$dev->Cabinet;
	}

	$templateList=$templ->GetTemplateList($facDB);
	$escTimeList=$escTime->GetEscalationTimeList($facDB);
	$escList=$esc->GetEscalationList($facDB);
	$deptList=$Dept->GetDepartmentList($facDB); 

	// We have a slight issue with width if we get a really long escalation name
	$widthfix=0;
	foreach($escList as $tmp){
		if(strlen($tmp->Details)>30){
			if(strlen($tmp->Details)>$widthfix){
				$widthfix=strlen($tmp->Details);
			}
		}
	}
	foreach($deptList as $tmp){
		if(strlen($tmp->Name)>30){
			if(strlen($tmp->Name)>$widthfix){
				$widthfix=strlen($tmp->Name);
			}
		}
	}
	// 1150 default width 
	// add 10px per character over 30
	// 10px with a base font of 12px is 0.833em
	if($widthfix>0){$widthfix2=(($widthfix)*0.75);
	$widthfix=(($widthfix2*2)+18);
	$css="<style type=\"text/css\">div.page.device {min-width:{$widthfix}em;}.device div.left, .device div.right {max-width:{$widthfix2}em;}</style>\n";}else{$css="";}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Device Maintenance</title>
  <link rel="stylesheet" href="css/inventory.css" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui-1.8.18.custom.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <?php echo $css; ?>
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui-1.8.18.custom.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.timepicker.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>

<script type="text/javascript">
function showDeptContacts(formname) {
	var deptid=formname.elements['owner'];
	var popurl="contactpopup.php?deptid="+deptid.value;
	window.open( popurl, "window", "width=800, height=700, resizable=no, toolbar=no" );
}

function updateFromTemplate(formname) {
	var tmplHeight=new Array();
<?php
	foreach($templateList as $tmpl){
		print "	tmplHeight[$tmpl->TemplateID]=$tmpl->Height;";
	}
?>	
	var sel=formname.elements['templateid'];
	formname.elements['height'].value=tmplHeight[sel.options[sel.selectedIndex].value];
}

$(function(){
	$('#deviceform').validationEngine({});
	$('#mfgdate').datepicker({});
	$('#installdate').datepicker({});
});

</script>
<script type="text/javascript">
/* 
IE work around
http://stackoverflow.com/questions/5227088/creating-style-node-adding-innerhtml-add-to-dom-and-ie-headaches
*/

function setCookie(c_name, value, exdays) {
	var exdate=new Date();
	exdate.setDate(exdate.getDate() + exdays);
	var c_value=escape(value) + ((exdays==null) ? "" : ";expires="+exdate.toUTCString());
	document.cookie=c_name + "=" + c_value;
}

function getCookie(c_name) {
	var i,x,y,ARRcookies=document.cookie.split(";");
	for (i=0; i<ARRcookies.length; i++) {
		x=ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
		y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
		x=x.replace(/^s+|\s+$/g,"");
		if (x==c_name) {
			return unescape(y);
		}
	}
}

function swaplayout(){
	var sheet = document.createElement('style');
	sheet.type = 'text/css';
	if (sheet.styleSheet) { // IE
		sheet.styleSheet.cssText = ".device div.left { display: block; }";
		document.getElementById('layout').innerHTML = "Landscape";
		setCookie("layout","Portrait",365);
	} else {
		sheet.innerHTML = ".device div.left { display: block; }";
		document.getElementById('layout').innerHTML = "Landscape";
		setCookie("layout","Portrait",365);
	}
	var s = document.getElementsByTagName('style')[0];
	if (s.innerHTML == sheet.innerHTML){
		if (sheet.styleSheet){ //IE
			document.getElementsByTagName('style')[0].styleSheet.cssText = "";
			document.getElementById('layout').innerHTML = "Portrait";
			setCookie("layout","Landscape",365);
		}else{
			document.getElementsByTagName('style')[0].innerHTML = "";
			document.getElementById('layout').innerHTML = "Portrait";
			setCookie("layout","Landscape",365);
		}
	}else{
		s.parentNode.insertBefore(sheet, s);
	}
}
	
function setPreferredLayout() {
	var p=getCookie("layout");
	if (p=="Portrait") {
		swaplayout();
	}
	
	/* Renew the cookie, no matter which preference is chosen */
	setCookie("layout",p,365);
}
</script>

</head>
<body onLoad="setPreferredLayout()">
<div id="header"></div>
<div class="page device">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<button id="layout" onClick="swaplayout()">Portrait</button>
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Device Detail</h3>
<div class="center"><div>
<form name="deviceform" id="deviceform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<div class="left">
<fieldset>
	<legend>Asset Tracking</legend>
	<div class="table">
		<div>
		   <div>Device ID</div>
		   <div><input type="text" name="deviceid" value="<?php echo $dev->DeviceID; ?>" size="6" readonly></div>
		</div>
		<div>
			<div><label for="reservation">Reservation?</label></div>
			<div><input type="checkbox" name="reservation" id="reservation"<?php if($dev->Reservation){echo " checked";}?>></div>
		</div>
		<div>
		   <div><label for="label">Label</label></div>
		   <div><input type="text" class="validate[required,minSize[3],maxSize[50]]" name="label" id="label" size="40" value="<?php echo $dev->Label; ?>"></div>
		</div>
		<div>
		   <div><label for="serialno">Serial Number</label></div>
		   <div><input type="text" name="serialno" id="serialno" size="40" value="<?php echo $dev->SerialNo; ?>"></div>
		</div>
		<div>
		   <div><label for="assettag">Asset Tag</label></div>
		   <div><input type="text" name="assettag" id="assettag" size="20" value="<?php echo $dev->AssetTag; ?>"></div>
		</div>
		<div>
		   <div><label for="mfgdate">Manufacture Date</label></div>
		   <div><input type="date" class="validate[optional,custom[date]] datepicker" name="mfgdate" id="mfgdate" value="<?php if ( $dev->MfgDate > '0000-00-00 00:00:00' ) echo date( 'm/d/Y', strtotime( $dev->MfgDate ) ); ?>">
		   </div>
		</div>
		<div>
		   <div><label for="installdate">Install Date</label></div>
		   <div><input type="date" class="validate[required,custom[date]] datepicker" name="installdate" id="installdate" value="<?php if ( $dev->InstallDate > '0000-00-00 00:00:00' ) echo date( 'm/d/Y', strtotime( $dev->InstallDate ) ); ?>"></div>
		</div>
		<div>
		   <div><label for="owner">Departmental Owner</label></div>
		   <div>
			<select name="owner" id="owner">
			<option value=0>Unassigned</option>
<?php
			foreach($deptList as $deptRow){
				echo "			<option value=\"$deptRow->DeptID\"";
				if($dev->Owner == $deptRow->DeptID){echo ' selected="selected"';}
				echo ">$deptRow->Name</option>\n";
			}
?>
			</select>
<!--			<input type="button" value="Show Contacts" onclick="showDeptContacts(this.form);"> -->
			<button type="button" onclick="showDeptContacts(this.form);">Show Contacts</button>
		   </div>
		</div>
		<div>
		   <div>&nbsp;</div>
		   <div><fieldset>
		   <legend>Escalation Information</legend>
		   <div class="table">
			<div>
				<div><label for="escaltationtimeid">Time Period</label></div>
				<div><select name="escalationtimeid" id="escalationtimeid">
				<option value="">Select...</option>
<?php
				foreach($escTimeList as $escTime){
					print "				<option value=\"$escTime->EscalationTimeID\"";
					if($escTime->EscalationTimeID==$dev->EscalationTimeID){	echo ' selected="selected"';}
					print ">$escTime->TimePeriod</option>\n";
				}
?>
				</select></div>
			</div>
			<div>
				<div><label for="escalationid">Details</label></div>
				<div><select name="escalationid" id="escalationid">
				<option value="">Select...</option>
<?php
				foreach($escList as $esc){
					print "				<option value=\"$esc->EscalationID\"";
					if($esc->EscalationID==$dev->EscalationID){	echo ' selected="selected"';}
					print ">$esc->Details</option>\n";
				}
?>
				</select></div>
			</div>
		   </div> <!-- END div.table -->
		   </fieldset></div>
		</div>
		<div>
		   <div><label for="primarycontact">Primary Contact</label></div>
		   <div><select name="primarycontact" id="primarycontact">
			<option value=0>Unassigned</option>
<?php
			foreach($contactList as $contactRow){
				print "			<option value=\"$contactRow->ContactID\"";
				if($contactRow->ContactID==$dev->PrimaryContact){$contactUserID=$contactRow->UserID;echo ' selected="selected"';}
				print ">$contactRow->LastName, $contactRow->FirstName</option>\n";
			}
			
			echo '			</select>';

			if(isset($config->ParameterArray['UserLookupURL']) && eregi($urlregex, $config->ParameterArray['UserLookupURL']) && isset($contactUserID)){
				echo "<input type=\"button\" value=\"Contact Lookup\" onclick=\"window.open( '".$config->ParameterArray["UserLookupURL"]."$contactUserID', 'UserLookup')\">\n";
			}
?>
		   </div>
		</div>	
	</div> <!-- END div.table -->
</fieldset>	
	<div class="table">
		<div>
		  <div><label for="notes">Notes</label></div>
		  <div><textarea name="notes" id="notes" cols="40" rows="8"><?php echo $dev->Notes; ?></textarea></div>
		</div>
	</div> <!-- END div.table -->
</div><!-- END div.left -->
<div class="right">
<fieldset>
	<legend>Physical Infrastructure</legend>
	<div class="table">
		<div>
			<div><label for="cabinet">Cabinet</label></div>
			<div><?php echo $cab->GetCabinetSelectList($facDB);?></div>
		</div>
		<div>
			<div><label for="templateid">Device Class</label></div>
			<div><select name="templateid" id="templateid" onchange="updateFromTemplate(deviceform)">
			<option value=0>Select a template...</option>
<?php
			foreach($templateList as $tempRow){
				print "			<option value=\"$tempRow->TemplateID\"";
				if($dev->TemplateID==$tempRow->TemplateID){echo ' selected="selected"';}
				$mfg->ManufacturerID=$tempRow->ManufacturerID;
				$mfg->GetManufacturerByID( $facDB );
				print ">$mfg->Name - $tempRow->Model</option>";
			}
?>
			</select>
			</div>
		</div>
		<div>
		   <div><label for="position">Position</label></div>
		   <div><input type="number" class="required,validate[custom[onlyNumberSp]]" name="position" id="position" size="4" value="<?php echo $dev->Position; ?>"></div>
		</div>
		<div>
		   <div><label for="height">Height</label></div>
		   <div><input type="number" class="required,validate[custom[onlyNumberSp]]" name="height" id="height" size="4" value="<?php echo $dev->Height; ?>"></div>
		</div>
		<div>
		   <div><label for="ports">Number of Data Ports</label></div>
		   <div><input type="number" class="optional,validate[custom[onlyNumberSp]]" name="ports" id="ports" size="4" value="<?php echo $dev->Ports; ?>"></div>
		</div>
		<div>
		   <div><label for="nominalwatts">Nominal Draw (Watts)</label></div>
		   <div><input type="text" class="optional,validate[custom[onlyNumberSp]]" name="nominalwatts" id="nominalwatts" size=6 value="<?php echo $dev->NominalWatts; ?>"></div>
		</div>
		<div>
		   <div><label for="powersupplycount">Number of Power Supplies</label></div>
		   <div><input type="number" class="optional,validate[custom[onlyNumberSp]]" name="powersupplycount" id="powersupplycount" size=4 value="<?php echo $dev->PowerSupplyCount; ?>"></div>
		</div>
		<div>
		   <div>Device Type</div>
		   <div><select name="devicetype">
			<option value=0>Select...</option>
<?php
			
		foreach(array('Server','Appliance','Storage Array','Switch','Routing Chassis','Patch Panel','Physical Infrastructure') as $devType){
			echo "			<option value=\"$devType\"";
			if($devType==$dev->DeviceType){
				echo ' selected="selected"';
			}
			echo ">$devType</option>\n";  
		}
?>
		   </select></div>
		</div>
	</div> <!-- END div.table -->
</fieldset>
<?php
	// Do not display ESX block if device isn't a virtual server and the user doesn't have write access
	if($user->WriteAccess || $dev->ESX){
		echo "<fieldset>\n	<legend>VMWare ESX Server Information</legend>";
	// If the user doesn't have write access display the list of VMs but not the configuration information.
		if($user->WriteAccess){
?>
	<div class="table">
		<div>
		   <div><label for="esx">ESX Server?</label></div>
		   <div><select name="esx" id="esx"><option value="1"<?php if($dev->ESX==1){echo' selected="selected"';}?>>True</option><option value="0" <?php if($dev->ESX==0){echo' selected="selected"';}?>>False</option></select></div>
		</div>
		<div>
		  <div><label for="primaryip">Primary IP</label></div>
		  <div><input type="text" name="primaryip" id="primaryip" size="20" value="<?php echo $dev->PrimaryIP; ?>"></div>
		</div>
		<div>
		  <div><label for="snmpcommunity">SNMP Read Only Community</label></div>
		  <div><input type="text" name="snmpcommunity" id="snmpcommunity" size="40" value="<?php echo $dev->SNMPCommunity; ?>"></div>
		</div>
	</div><!-- END div.table -->
<?php
		}
		if($dev->ESX){
			$esx=new ESX();
			$esx->DeviceID=$dev->DeviceID;
			$vmList=$esx->GetDeviceInventory($facDB);
    
			echo "\n<div class=\"table border\"><div><div>VM Name</div><div>Status</div><div>Owner</div><div>Last Updated</div></div>\n";
			foreach($vmList as $vmRow){
				if($vmRow->vmState=='poweredOff'){
					$statColor='red';
				}else{
					$statColor='green';
				}
				$Dept->DeptID=$vmRow->Owner;
				if($Dept->DeptID >0){
					$Dept->GetDeptByID($facDB);
				}else{
					$Dept->Name='Unknown';
				}
				echo "<div><div>$vmRow->vmName</div><div><font color=$statColor>$vmRow->vmState</font></div><div><a href=\"updatevmowner.php?vmindex=$vmRow->VMIndex\">$Dept->Name</a></div><div>$vmRow->LastUpdated</div></div>\n";
			}
			echo '</div> <!-- END div.table -->';
		}
		echo '</fieldset>'."\n";
	}
?>
</div><!-- END div.right -->
<div class="table">
<div><div>
<div class="table style">
<?php
	//HTML content condensed for PHP logic clarity.
	if(!is_null($pwrCords)){
		// If $pwrCords is null then we're creating a device record. Skip power checking.
		if(count($pwrCords)==0){
			// We have no power information. Display links to PDU's in cabinet?
			echo '		<div>		<div><a name="power"></a></div>		<div>No power connections defined.  You can add connections from the power strip screen.</div></div><div><div>&nbsp;</div><div></div></div>';
		}else{
			echo "		<div>\n		  <div><a name=\"power\">Power Connections</a></div>\n		  <div><div class=\"table border\">\n			<div><div>Panel</div><div>Power Strip</div><div>Plug #</div><div>Power Supply</div></div>";
			foreach($pwrCords as $cord){
				$pdu->PDUID=$cord->PDUID;
				$pdu->GetPDU($facDB);
				$panel->PanelID=$pdu->PanelID;
				$panel->GetPanel($facDB);
				echo "			<div><div><a href=\"panelmgr.php?panelid=$pdu->PanelID\">$panel->PanelLabel</a></div><div><a href=\"pduinfo.php?pduid=$pdu->PDUID\">$pdu->Label</a></div><div><a href=\"power_connection.php?pdu=$pdu->PDUID&conn=$cord->PDUPosition\">$cord->PDUPosition</a></div><div>$cord->DeviceConnNumber</div></div>\n";
			}
			echo "			</div><!-- END div.table --></div>\n		</div>\n		<div>\n			<div>&nbsp;</div><div></div>\n		</div>\n";
		}
	}

	// If device is s switch or appliance show what the heck it is connected to.
	if($dev->DeviceType=='Server' || $dev->DeviceType=='Appliance'){
		if(count($patchList)==0){
			// We have no network information. Display links to switches in cabinet?
			echo '		<div>		<div><a name="power"></a></div>		<div>No network connections defined.  You can add connections from a switch device.</div></div>';
		}else{
			echo "		<div>\n		  <div><a name=\"net\">Connections</a><br>(Managed at Switch)</div>\n		  <div><div class=\"table border\"><div><div>Device Port</div><div>Switch</div><div>Switch Port</div><div>Notes</div></div>\n";
			$tmpDev = new Device();
			foreach($patchList as $patchConn){
				$tmpDev->DeviceID = $patchConn->SwitchDeviceID;
				$tmpDev->GetDevice( $facDB );
				echo "			<div><div>$patchConn->EndpointPort</div><div><a href=\"devices.php?deviceid=$patchConn->SwitchDeviceID#net\">$tmpDev->Label</a></div><div><a href=\"changepatch.php?switchid=$patchConn->SwitchDeviceID&portid=$patchConn->SwitchPortNumber\">$patchConn->SwitchPortNumber</a></div><div>$patchConn->Notes</div></div>\n";
			}
			echo "			</div><!-- END div.table -->\n		  </div>\n		</div>\n";
		}      
	}
		  
	if($dev->DeviceType=='Switch'){
		echo "		<div>\n		  <div><a name=\"net\">Connections</a></div>\n		  <div>\n			<div class=\"table border\">\n				<div><div>Switch Port</div><div>Device</div><div>Device Port</div><div>Notes</div></div>\n";
		if(sizeof($patchList) >0){
			$tmpDev=new Device();
			  
			foreach($patchList as $patchConn){
				$tmpDev->DeviceID = $patchConn->EndpointDeviceID;
				$tmpDev->GetDevice( $facDB );
				
				printf( "				<div><div><a href=\"changepatch.php?switchid=%s&portid=%s\">%s</a></div><div><a href=\"devices.php?deviceid=%s\">%s</a></div><div>%s</div><div>%s</div></div>\n", $patchConn->SwitchDeviceID, $patchConn->SwitchPortNumber, $patchConn->SwitchPortNumber, $patchConn->EndpointDeviceID, $tmpDev->Label, $patchConn->EndpointPort, $patchConn->Notes );
			}
		}      
		echo "			</div><!-- END div.table -->\n		  </div>\n		</div>";
	}
?>
		<div class="caption">
<?php
	if($user->WriteAccess){
		if($dev->DeviceID >0){
			echo "		  <input type=\"submit\" name=\"action\" value=\"Update\" default>\n";
		}else{
			echo '		  <input type="submit" name="action" value="Create">';
		}
	}
	// Delete rights are seperate from write rights
	if($user->DeleteAccess && $dev->DeviceID >0){
		echo "		  <input type=\"submit\" name=\"action\" value=\"Delete\">\n";
	}
?>
		</div>
</div> <!-- END div.table -->
</div></div>
</div> <!-- END div.table -->
</form>
</div></div>
<?php
	if($dev->Cabinet >0){
		echo "   <a href=\"cabnavigator.php?cabinetid=$cab->CabinetID\">[Return to Navigator]</a>";
	}else{
		echo '   <div><a href="storageroom.php">[Return to Navigator]</a></div>';
	}
?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
