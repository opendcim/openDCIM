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
					$dev->ChassisSlots=$_REQUEST['chassisslots'];
					$dev->ParentDevice=$_REQUEST['parentdevice'];
					$dev->MfgDate=date('Y-m-d',strtotime($_REQUEST['mfgdate']));
					$dev->InstallDate=date('Y-m-d',strtotime($_REQUEST['installdate']));
					$dev->WarrantyCo=$_REQUEST['warrantyco'];
					$dev->WarrantyExpire=date('Y-m-d',strtotime($_REQUEST['warrantyexpire']));
					$dev->Notes=$_REQUEST['notes'];
					$dev->PrimaryIP=(isset($_REQUEST['primaryip']))?$_REQUEST['primaryip']:"";
					$dev->SNMPCommunity=(isset($_REQUEST['snmpcommunity']))?$_REQUEST['snmpcommunity']:"";
					$dev->ESX=(isset($_REQUEST['esx']))?1:0;
					$dev->Reservation=(isset($_REQUEST['reservation']))?1:0;
					$dev->NominalWatts=$_REQUEST['nominalwatts'];

					if (( $dev->TemplateID > 0 ) && ( intval( $dev->NominalWatts == 0 )))
						$dev->UpdateWattageFromTemplate($facDB);
			
					if($dev->Cabinet <0){
						$dev->MoveToStorage($facDB);
					}else{
						$dev->UpdateDevice($facDB);
					}
				}elseif($user->WriteAccess&&($_REQUEST['action']=='Create')){
					$dev->Label=$_REQUEST['label'];
					$dev->SerialNo=$_REQUEST['serialno'];
					$dev->AssetTag=$_REQUEST['assettag'];
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
					$dev->ChassisSlots=$_REQUEST['chassisslots'];
					$dev->ParentDevice=$_REQUEST['parentdevice'];
					$dev->MfgDate=date('Y-m-d',strtotime($_REQUEST['mfgdate']));
					$dev->InstallDate=date('Y-m-d',strtotime($_REQUEST['installdate']));
					$dev->WarrantyCo=$_REQUEST['warrantyco'];
					$dev->WarrantyExpire=date('Y-m-d',strtotime($_REQUEST['warrantyexpire']));
					$dev->Notes=$_REQUEST['notes'];
					$dev->PrimaryIP=(isset($_REQUEST['primaryip']))?$_REQUEST['primaryip']:"";
					$dev->SNMPCommunity=(isset($_REQUEST['snmpcommunity']))?$_REQUEST['snmpcommunity']:"";
					$dev->ESX=(isset($_REQUEST['esx']))?1:0;
					$dev->Reservation=(isset($_REQUEST['reservation']))?1:0;
					$dev->CreateDevice($facDB);
				}elseif($user->DeleteAccess&&($_REQUEST['action']=='Delete')){
					$dev->GetDevice($facDB);
					$dev->DeleteDevice($facDB);
					header('Location: '.redirect("cabnavigator.php?cabinetid=$dev->Cabinet"));
					exit;
				}elseif($user->WriteAccess&&$_REQUEST['action']=='child'){
					if(isset($_REQUEST['parentdevice'])){
						$dev->DeviceID=null;
						$dev->ParentDevice=$_REQUEST["parentdevice"];
					}
					// sets install date to today when a new device is being created
					$dev->InstallDate=date("m/d/Y");
				}
			}

			// Finished updating devices or creating them.  Refresh the object with data from the DB
			$dev->GetDevice($facDB);

			// Since a device exists we're gonna need some additional info
			$pwrConnection=new PowerConnection();
			$pdu=new PowerDistribution();
			$panel=new PowerPanel();
			$networkPatches=new SwitchConnection();


			$pwrConnection->DeviceID=($dev->ParentDevice>0)?$dev->ParentDevice:$dev->DeviceID;
			$pwrCords=$pwrConnection->GetConnectionsByDevice($facDB);

			if($dev->DeviceType=='Switch'){
				$networkPatches->SwitchDeviceID=$dev->DeviceID;
				$patchList=$networkPatches->GetSwitchConnections($facDB);
			}else{
				$networkPatches->EndpointDeviceID=($dev->ParentDevice>0)?$dev->ParentDevice:$dev->DeviceID;
				$patchList=$networkPatches->GetEndpointConnections($facDB);
			}
		}
		$cab->CabinetID=$dev->Cabinet;
	} else {
		// sets install date to today when a new device is being created
		$dev->InstallDate=date("m/d/Y");
	}
	
	if ( $dev->ParentDevice > 0 ) {
		$pDev = new Device();
		$pDev->DeviceID = $dev->ParentDevice;
		$pDev->GetDevice( $facDB );
		
		$parentList = $pDev->GetParentDevices( $facDB );
		
		$cab->CabinetID = $pDev->Cabinet;
		$cab->GetCabinet( $facDB );
	}
	
	$childList=array();
	if($dev->ChassisSlots>0){
		$childList=$dev->GetDeviceChildren($facDB);
	}

/*	
/ uncomment if you want empty dates to be filled with today's date instead of epoch
/
/ unset mfgdates will show up on the asset aging report as unknown
/*

/*	
	if($dev->MfgDate <= "1970-01-01"){
		$dev->MfgDate=date("Y-m-d");
	}
		
	if($dev->WarrantyExpire <= "1970-01-01"){
		$dev->WarrantyExpire= date("Y-m-d");
	}
*/

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
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
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
/* 
IE work around
http://stackoverflow.com/questions/5227088/creating-style-node-adding-innerhtml-add-to-dom-and-ie-headaches
*/

function setCookie(c_name, value) {
	var exdate=new Date();
	exdate.setDate(exdate.getDate() + 365);
	var c_value=escape(value) + ";expires="+exdate.toUTCString();
	document.cookie=c_name + "=" + c_value;
}
function swaplayout(){
	var sheet = document.createElement('style');
	sheet.type = 'text/css';
	if (sheet.styleSheet) { // IE
		sheet.styleSheet.cssText = ".device div.left { display: block; }";
		document.getElementById('layout').innerHTML = "Landscape";
	} else {
		sheet.innerHTML = ".device div.left { display: block; }";
		document.getElementById('layout').innerHTML = "Landscape";
	}
	var s = document.getElementsByTagName('style')[0];
	if (s.innerHTML == sheet.innerHTML){
		if (sheet.styleSheet){ //IE
			document.getElementsByTagName('style')[0].styleSheet.cssText = "";
			document.getElementById('layout').innerHTML = "Portrait";
		}else{
			document.getElementsByTagName('style')[0].innerHTML = "";
			document.getElementById('layout').innerHTML = "Portrait";
		}
		setCookie("layout","Landscape");
	}else{
		s.parentNode.insertBefore(sheet, s);
		setCookie("layout","Portrait");
	}
}
$(document).ready(function() {
	$('#deviceform').validationEngine({});
	$('#mfgdate').datepicker({});
	$('#installdate').datepicker({});
	$('#warrantyexpire').datepicker({});
	$('#owner').next('button').click(function(){
		window.open('contactpopup.php?deptid='+$('#owner').val(), 'Contacts Lookup', 'width=800, height=700, resizable=no, toolbar=no');
		return false;
	});
	$('#adddevice').click(function() {
		$(":input").attr("disabled","disabled");
		$('#parentdevice').removeAttr("disabled");
		$('#adddevice').removeAttr("disabled");
		$(this).submit();
	});
	$('#templateid').change( function(){
		$.get('scripts/ajax_template.php?q='+$(this).val(), function(data) {
			$('#height').val(data['Height']);
			$('#ports').val(data['NumPorts']);
			$('#nominalwatts').val(data['Wattage']);
			$('#powersupplycount').val(data['PSCount']);
			$('select[name=devicetype]').val(data['DeviceType']);
		});
	});
<?php
	// hide cabinet slot picker from child devices
	if($dev->ParentDevice==0){
?>
	$('#position').focus(function()	{
		var cab=$("select#cabinetid").val();
		$.getJSON('scripts/ajax_cabinetuse.php?cabinet='+cab+'&deviceid='+$("#deviceid").val(), function(data) {
			var ucount=0;
			$.each(data, function(i,inuse){
				ucount++;
			});
			var rackhtmlleft='';
			var rackhtmlright='';
			for(ucount=ucount; ucount>0; ucount--){
				if(data[ucount]){var cssclass='notavail'}else{var cssclass=''};
				rackhtmlleft+='<div>'+ucount+'</div>';
				rackhtmlright+='<div val='+ucount+' class="'+cssclass+'"></div>';
			}
			var rackhtml='<div class="table border positionselector"><div><div>'+rackhtmlleft+'</div><div>'+rackhtmlright+'</div></div></div>';
			$('#positionselector').html(rackhtml);
			setTimeout(function(){
				var divwidth=$('.positionselector').width();
				var divheight=$('.positionselector').height();
				$('#positionselector').width(divwidth);
				$('#height').focus(function(){$('#positionselector').css({'left': '-1000px'});});
				$('#positionselector').css({
					'left':(($('.right').position().left)-(divwidth+40)),
					'top':(($('.right').position().top))
				});
				$('#positionselector').mouseleave(function(){
					$('#positionselector').css({'left': '-1000px'});
				});
				$('.positionselector > div > div + div > div').mouseover(function(){
					$('.positionselector > div > div + div > div').each(function(){
						$(this).removeAttr('style');
					});
					var unum=$("#height").val();
					if(unum>=1 && $(this).attr('class')!='notavail'){
						var test='';
						var background='green';
						// check each element start with pointer
						for (var x=0; x<unum; x++){
							if(x!=0){
								test+='.prev()';
								eval("if($(this)"+test+".attr('class')=='notavail' || $(this)"+test+".length ==0){background='red';}");
								eval("console.log($(this)"+test+".attr('val')+' '+$(this)"+test+".attr('class'))");
							}else{
								if($(this).attr('class')=='notavail'){background='red';}
							}
						}
						test='';
						if(background=='red'){var pointer='default'}else{var pointer='pointer'}
						for (x=0; x<unum; x++){
							if(x!=0){
								test+='.prev()';
								eval("$(this)"+test+".css({'background-color': '"+background+"'})");
								eval("console.log($(this)"+test+".attr('val'))");
							}else{
								$(this).css({'background-color': background, 'cursor': pointer});
								if(background=='green'){
									$(this).click(function(){
										$('#position').val($(this).attr('val'));
										$('#positionselector').css({'left': '-1000px'});
									});
								}
							}
						}
					}
				});
			},100);
		}, 'json');
	});
<?php
	}
?>
});
	
function setPreferredLayout() {<?php if(isset($_COOKIE["layout"]) && strtolower($_COOKIE["layout"])==="portrait"){echo 'swaplayout();setCookie("layout","Portrait");';}else{echo 'setCookie("layout","Landscape");';} ?>}
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
<div id="positionselector"></div>
<form name="deviceform" id="deviceform" action="<?php echo $_SERVER['PHP_SELF']; if(isset($dev->DeviceID) && $dev->DeviceID>0){print "?deviceid=$dev->DeviceID";} ?>" method="POST">
<div class="left">
<fieldset>
	<legend>Asset Tracking</legend>
	<div class="table">
		<div>
		   <div>Device ID</div>
		   <div><input type="text" name="deviceid" id="deviceid" value="<?php echo $dev->DeviceID; ?>" size="6" readonly></div>
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
		   <div><input type="text" class="validate[optional,custom[date]] datepicker" name="mfgdate" id="mfgdate" value="<?php if ( $dev->MfgDate > '0000-00-00 00:00:00' ) echo date( 'm/d/Y', strtotime( $dev->MfgDate ) ); ?>">
		   </div>
		</div>
		<div>
		   <div><label for="installdate">Install Date</label></div>
		   <div><input type="text" class="validate[required,custom[date]] datepicker" name="installdate" id="installdate" value="<?php if ( $dev->InstallDate > '0000-00-00 00:00:00' ) echo date( 'm/d/Y', strtotime( $dev->InstallDate ) ); ?>"></div>
		</div>
		<div>
		   <div><label for="warrantyco">Warranty Company</label></div>
		   <div><input type="text" name="warrantyco" id="warrantyco" value="<?php printf( "%s", $dev->WarrantyCo ); ?>"></div>
		</div>
		<div>
		   <div><label for="installdate">Warranty Expiration</label></div>
		   <div><input type="text" class="validate[custom[date]] datepicker" name="warrantyexpire" id="warrantyexpire" value="<?php printf( "%s", date( 'm/d/Y', strtotime( $dev->WarrantyExpire ))); ?>"></div>
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
			<button type="button">Show Contacts</button>
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

<?php
	if ( $dev->ParentDevice == 0 ) {
		printf( "\t\t\t<div>%s</div>\n", $cab->GetCabinetSelectList( $facDB ) );
		} else {
			print "\t\t\t<div>$cab->Location<input type=\"hidden\" name=\"cabinetid\" value=\"0\"></div>\n\t\t</div>\t\t<div>\t\t\t<div><label for=\"parentdevice\">Parent Device</label></div>\t\t\t<div><select name=\"parentdevice\">\n";
			
			foreach ( $parentList as $parDev ) {
				if ( $pDev->DeviceID == $parDev->DeviceID )
					$selected = "SELECTED";
				else
					$selected = "";
				
				printf( "<option value=\"%d\" %s>%s</option>\n", $parDev->DeviceID, $selected, $parDev->Label );
			}
			
			printf( "\t\t\t</select></div>\n" );
		}
	?>
			</div>
		<div>
			<div><label for="templateid">Device Class</label></div>
			<div><select name="templateid" id="templateid">
			<option value=0>Select a template...</option>
<?php
			foreach($templateList as $tempRow) {
				if ( $dev->TemplateID == $tempRow->TemplateID )
					$selected = "SELECTED";
				else
					$selected = "";

				$mfg->ManufacturerID=$tempRow->ManufacturerID;
				$mfg->GetManufacturerByID( $facDB );
	
				printf( "\t\t\t<option value=\"%d\" %s>%s - %s</option>\n", $tempRow->TemplateID, $selected, $mfg->Name, $tempRow->Model );
			}
?>
			</select>
			</div>
		</div>
		<div>
		   <div><label for="position">Position</label></div>
		   <div><input type="number" class="required,validate[custom[onlyNumberSp],min[1]]" name="position" id="position" size="4" value="<?php echo $dev->Position; ?>"></div>
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
			
		foreach(array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure') as $devType){
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
	//
	// Do not display the chassis contents block if this is a child device (ParentDevice > 0)
	//
	if($dev->DeviceType=='Chassis'){
?>
<fieldset class="chassis">
	<legend>Chassis Contents</legend>
	<div class="table">
		<div>
			<div><label for="chassisslots">Number of Slots in Chassis:</label></div>
			<div><input type="text" id="chassisslots" class="optional,validate[custom[onlyNumberSp]]" name="chassisslots" size="4" value="<?php print $dev->ChassisSlots; ?>"></div>
		</div>
	</div>
	<div class="table">
		<div>
			<div>Slot #</div>
			<div>Height</div>
			<div>Device Name</div>
			<div>Device Type</div>
		</div>
<?php
	foreach($childList as $chDev){
		print "\t<div>
		<div>$chDev->Position</div>
		<div>$chDev->Height</div>
		<div><a href=\"devices.php?deviceid=$chDev->DeviceID\">$chDev->Label</a></div>
		<div>$chDev->DeviceType</div>
	</div>\n";
	}
	
	if($dev->ChassisSlots >0){
?>
		<div class="caption">
			<button type="submit" id="adddevice" value="child" name="action">Add Device</button>
			<input type="hidden" id="parentdevice" name="parentdevice" disabled value="<?php print $dev->DeviceID; ?>">
		</div>
<?php
	}
?>
	</div>
</fieldset>
<?php
	}else{
		echo '<input type="hidden" name="chassisslots" value=0>';
	}
	
	// Do not display ESX block if device isn't a virtual server and the user doesn't have write access
//	if($user->WriteAccess || $dev->ESX){
	if(($user->WriteAccess || $dev->ESX) && ($dev->DeviceType=="Server" || $dev->DeviceType=="")){
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
	// If $pwrCords is null then we're creating a device record. Skip power checking.
	if(!is_null($pwrCords)&&((isset($_POST['action'])&&$_POST['action']!='child')||!isset($_POST['action']))){
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
			foreach($patchList as $patchConn){
				$tmpDev=new Device();
				$tmpDev->DeviceID=$patchConn->EndpointDeviceID;
				$tmpDev->GetDevice($facDB);
				
				print "\t\t\t\t<div><div><a href=\"changepatch.php?switchid=$patchConn->SwitchDeviceID&portid=$patchConn->SwitchPortNumber\">$patchConn->SwitchPortNumber</a></div><div><a href=\"devices.php?deviceid=$patchConn->EndpointDeviceID\">$tmpDev->Label</a></div><div>$patchConn->EndpointPort</div><div>$patchConn->Notes</div></div>\n";
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
	if ( $dev->ParentDevice > 0 ) {
		printf( "<a href=\"devices.php?deviceid=%d\">[Return to Parent Device]</a>\n", $pDev->DeviceID );
	} elseif ($dev->Cabinet >0) {
		echo "   <a href=\"cabnavigator.php?cabinetid=$cab->CabinetID\">[Return to Navigator]</a>";
	} else {
		echo '   <div><a href="storageroom.php">[Return to Navigator]</a></div>';
	}
?>
</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
	$(document).ready(function() {
		// wait half a second after the page loads then open the tree
		setTimeout(function(){
			expandToItem('datacenters','cab<?php echo $cab->CabinetID;?>');
		},500);
	});
</script>

</body>
</html>
