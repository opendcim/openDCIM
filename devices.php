<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$dev=new Device();
	$dev=new Device();
	$cab=new Cabinet();
	$user=new User();
	$contact=new Contact();
	
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights();

	$taginsert="";

	// Ajax functions and we only want these exposed to people with write access
	if($user->WriteAccess){
		if(isset($_POST['cab'])){
			$cab->CabinetID=$_POST['cab'];
			$cab->GetCabinet();
			echo $cab->CabinetHeight;
			exit;
		}
		if(isset($_POST['fp'])){
			$dev->DeviceID=$_POST['devid'];
			$dev->GetDevice();
			if($_POST['fp']==''){ // querying possible first ports
				$portCandidates=SwitchInfo::findFirstPort($dev->DeviceID);
				if(count($portCandidates>0)){
					foreach($portCandidates as $id => $portdesc){
						$checked=($id==$dev->FirstPortNum)?' checked':'';
						print '<input type="radio" name="firstportnum" id="fp'.$id.'" value="'.$id.'"'.$checked.'><label for="fp'.$id.'">'.$portdesc.'</label><br>';
					}
				}
			}else{ // setting first port
				$dev->FirstPortNum=$_POST['fp'];
				if($dev->UpdateDevice()){
					echo 'Updated';
				}else{
					echo 'Failure';
				}
			}
			exit;
		};
		if(isset($_POST['swdev'])){ 
			if(isset($_POST['getport'])){
				$dp=new DevicePorts();
				$dp->DeviceID=$_POST['swdev'];
				$dp->PortNumber=$_POST['pnum'];
				$dp->getPort();
				$mt=MediaTypes::GetMediaTypeList();
				$cc=ColorCoding::GetCodeList();
				$dp->MediaName=(isset($mt[$dp->MediaID]))?$mt[$dp->MediaID]->MediaType:'';
				$dp->ColorName=(isset($cc[$dp->ColorID]))?$cc[$dp->ColorID]->Name:'';
				$dev->DeviceID=$dp->ConnectedDeviceID;
				$dp->ConnectedDeviceLabel=($dev->GetDevice())?stripslashes($dev->Label):'';
				header('Content-Type: application/json');
				echo json_encode($dp);
				exit;
			}
			if(isset($_POST['saveport'])){
				$dp=new DevicePorts();
				$dp->DeviceID=$_POST['swdev'];
				$dp->PortNumber=$_POST['pnum'];
				$dp->Label=$_POST['pname'];
				$dp->MediaID=$_POST['porttype'];
				$dp->ColorID=$_POST['portcolor'];
				$dp->Notes=$_POST['cnotes'];
				$dp->ConnectedDeviceID=$_POST['cdevice'];
				$dp->ConnectedPort=$_POST['cdeviceport'];

				if($dp->updatePort()){
					echo 1;
				}else{
					echo 0;
				}
				exit;
			}
			if(isset($_POST['delport'])){
				$dp=new DevicePorts();
				$dp->DeviceID=$_POST['swdev'];
				$dp->PortNumber=$_POST['pnum'];
				$ports=end($dp->getPorts());
				function updatedevice($devid){
					$dev=new Device();
					$dev->DeviceID=$devid;
					$dev->GetDevice();
					$dev->Ports=$dev->Ports-1;
					$dev->UpdateDevice();
				}
				// remove the selected port then shuffle the data to fill the hole if needed
				if($ports->PortNumber!=$dp->PortNumber){
					foreach($ports as $i=>$prop){
						if($i!="PortNumber"){
							$dp->$i=$prop;
						}
					}
					if($dp->updatePort()){
						if($ports->removePort()){
							updatedevice($dp->DeviceID);
							echo 1;
							exit;
						}
					}
					echo 0;
				}else{ // Last available port. just delete it.
					if($dp->removePort()){
						updatedevice($dp->DeviceID);
						echo 1;
					}else{
						echo 0;
					}
				}
				exit;
			}
			$list='';
			if(isset($_POST['listports'])){
				$dp=new DevicePorts();
				$dp->DeviceID=$_POST['thisdev'];
				$list=$dp->getPorts();
				if($config->ParameterArray["MediaEnforce"]=='enabled'){
					$dp->DeviceID=$_POST['thisdev'];
					$dp->PortNumber=$_POST['pn'];
					$dp->getPort();
					foreach($list as $key => $port){
						if($port['MediaID']!=$dp->MediaID){
							unset($list[$key]); // remove the nonmatching ports	
						}
					}
				}
				foreach($list as $key => $port){
					if(!is_null($port->ConnectedDeviceID)){
						if($port->ConnectedDeviceID==$_POST['swdev'] && $port->ConnectedPort==$_POST['pn']){
							// This is what is currently connected so leave it in the list
						}else{
							// Remove any other ports that already have connections
							unset($list[$key]);
						}
					}
				}
			}else{
				$patchpanels=(isset($_POST['rear']))?"true":null;
				$list=DevicePorts::getPatchCandidates($_POST['swdev'],$_POST['pn'],null,$patchpanels);
			}
			header('Content-Type: application/json');
			echo json_encode($list);
			exit;
		}
		if(isset($_GET['cc'])){
			header('Content-Type: application/json');
			echo json_encode(ColorCoding::GetCodeList());
			exit;
		}
		if(isset($_GET['mt'])){
			header('Content-Type: application/json');
			echo json_encode(MediaTypes::GetMediaTypeList());
			exit;
		}
		if(isset($_POST['esxrefresh'])){
			ESX::RefreshInventory($_POST['esxrefresh']);
			buildesxtable($_POST['esxrefresh']);
			exit;
		}
		if(isset($_POST['refreshswitch'])){
			header('Content-Type: application/json');
			if(isset($_POST['names'])){
				foreach(SwitchInfo::getPortNames($_POST['refreshswitch']) as $PortNumber => $Label){
					$port=new DevicePorts();
					$port->DeviceID=$_POST['refreshswitch'];
					$port->PortNumber=$PortNumber;
					$port->Label=$Label;
					$port->updateLabel();
				}
				echo json_encode(SwitchInfo::getPortNames($_POST['refreshswitch']));
			}else{
				echo json_encode(SwitchInfo::getPortStatus($_POST['refreshswitch']));
			}
			exit;
		}

	}
	// END AJAX


	// These objects are used no matter what operation we're performing
	$templ=new DeviceTemplate();
	$mfg=new Manufacturer();
	$esc=new Escalations();
	$escTime=new EscalationTimes();
	$contactList=$contact->GetContactList();
	$Dept=new Department();
	$pwrCords=null;
	$chassis="";
	$copy = false;
	$copyerr=__("This device is a copy of an existing device.  Remember to set the new location before saving.");

	// This page was called from somewhere so let's do stuff.
	// If this page wasn't called then present a blank record for device creation.
	if(isset($_REQUEST['action'])||isset($_REQUEST['deviceid'])){
		if(isset($_REQUEST['action'])&&$_REQUEST['action']=='new'){
			// sets install date to today when a new device is being created
			$dev->InstallDate=date("m/d/Y");
			// Some fields are pre-populated when you click "Add device to this cabinet"
			if(isset($_REQUEST['cabinet'])){
				$dev->Cabinet = intval($_REQUEST['cabinet']);
				$cab->CabinetID = $dev->Cabinet;
				$cab->GetCabinet();
				
				// If you are adding a device that is assined to a specific customer, assume that device is also owned by that customer
				if($cab->AssignedTo >0){
					$dev->Owner=$cab->AssignedTo;
				}
			}
		}
		
		// if no device id requested then we must be making a new device so skip all data lookups.
		if(isset($_REQUEST['deviceid'])){
			$dev->DeviceID=intval($_REQUEST['deviceid']);
			// If no action is requested then we must be just querying a device info.
			// Skip all modification checks
			$tagarray=array();
			if(isset($_POST['tags'])){
				$tagarray=json_decode($_POST['tags']);
			}
			if(isset($_POST['action'])){
				if($user->WriteAccess&&(($dev->DeviceID >0)&&($_POST['action']=='Update'))){
					// User has changed the device type from chassis to something else and has said yes
					// that they want to remove the dependant child devices
					if(isset($_POST['killthechildren'])){
						$childList=$dev->GetDeviceChildren();
						foreach($childList as $childDev){
							$childDev->DeleteDevice();
						}
					}
					$dev->Label=$_POST['label'];
					$dev->SerialNo=$_POST['serialno'];
					$dev->AssetTag=$_POST['assettag'];
					$dev->Owner=$_POST['owner'];
					$dev->EscalationTimeID=$_POST['escalationtimeid'];
					$dev->EscalationID=$_POST['escalationid'];
					$dev->PrimaryContact=$_POST['primarycontact'];
					$dev->Cabinet=$_POST['cabinetid'];
					$dev->Position=$_POST['position'];
					$dev->Height=$_POST['height'];
					$dev->TemplateID=$_POST['templateid'];
					$dev->DeviceType=$_POST['devicetype'];
					$dev->MfgDate=date('Y-m-d',strtotime($_POST['mfgdate']));
					$dev->InstallDate=date('Y-m-d',strtotime($_POST['installdate']));
					$dev->WarrantyCo=$_POST['warrantyco'];
					$dev->WarrantyExpire=date('Y-m-d',strtotime($_POST['warrantyexpire']));
					$dev->Notes=trim($_POST['notes']);
					$dev->Notes=($dev->Notes=="<br>")?"":$dev->Notes;
					$dev->FirstPortNum=$_POST['firstportnum'];
					// All of the values below here are optional based on the type of device being dealt with
					$dev->ChassisSlots=(isset($_POST['chassisslots']))?$_POST['chassisslots']:0;
					$dev->RearChassisSlots=(isset($_POST['rearchassisslots']))?$_POST['rearchassisslots']:0;
					$dev->Ports=(isset($_POST['ports']))?$_POST['ports']:"";
					$dev->PowerSupplyCount=(isset($_POST['powersupplycount']))?$_POST['powersupplycount']:"";
					$dev->ParentDevice=(isset($_POST['parentdevice']))?$_POST['parentdevice']:"";
					$dev->PrimaryIP=(isset($_POST['primaryip']))?$_POST['primaryip']:"";
					$dev->SNMPCommunity=(isset($_POST['snmpcommunity']))?$_POST['snmpcommunity']:"";
					$dev->ESX=(isset($_POST['esx']))?$_POST['esx']:0;
					$dev->Reservation=(isset($_POST['reservation']))?($_POST['reservation']=="on")?1:0:0;
					$dev->NominalWatts=$_POST['nominalwatts'];

					if(($dev->TemplateID >0)&&(intval($dev->NominalWatts==0))){$dev->UpdateWattageFromTemplate();}
			
					$dev->SetTags($tagarray);
					if($dev->Cabinet <0){
						$dev->MoveToStorage();
					}else{
						$dev->UpdateDevice();
					}
				}elseif($user->WriteAccess&&($_POST['action']=='Create')){
					$dev->Label=$_POST['label'];
					$dev->SerialNo=$_POST['serialno'];
					$dev->AssetTag=$_POST['assettag'];
					$dev->Owner=$_POST['owner'];
					$dev->EscalationTimeID=$_POST['escalationtimeid'];
					$dev->EscalationID=$_POST['escalationid'];
					$dev->PrimaryContact=$_POST['primarycontact'];
					$dev->Cabinet=$_POST['cabinetid'];
					$dev->Position=$_POST['position'];
					$dev->Height=$_POST['height'];
					$dev->Ports=$_POST['ports'];
					$dev->TemplateID=$_POST['templateid'];
					$dev->DeviceType=$_POST['devicetype'];
					$dev->MfgDate=date('Y-m-d',strtotime($_POST['mfgdate']));
					$dev->InstallDate=date('Y-m-d',strtotime($_POST['installdate']));
					$dev->WarrantyCo=$_POST['warrantyco'];
					$dev->WarrantyExpire=date('Y-m-d',strtotime($_POST['warrantyexpire']));
					$dev->Notes=trim($_POST['notes']);
					$dev->Notes=($dev->Notes=="<br>")?"":$dev->Notes;
					$dev->FirstPortNum=$_POST['firstportnum'];
					// All of the values below here are optional based on the type of device being dealt with
					$dev->ChassisSlots=(isset($_POST['chassisslots']))?$_POST['chassisslots']:0;
					$dev->RearChassisSlots=(isset($_POST['rearchassisslots']))?$_POST['rearchassisslots']:0;
					$dev->Ports=(isset($_POST['ports']))?$_POST['ports']:"";
					$dev->PowerSupplyCount=(isset($_POST['powersupplycount']))?$_POST['powersupplycount']:"";
					$dev->ParentDevice=(isset($_POST['parentdevice']))?$_POST['parentdevice']:"";
					$dev->PrimaryIP=(isset($_POST['primaryip']))?$_POST['primaryip']:"";
					$dev->SNMPCommunity=(isset($_POST['snmpcommunity']))?$_POST['snmpcommunity']:"";
					$dev->ESX=(isset($_POST['esx']))?$_POST['esx']:0;
					$dev->Reservation=(isset($_POST['reservation']))?($_POST['reservation']=="on")?1:0:0;
					$dev->NominalWatts=$_POST['nominalwatts'];

					if( ($dev->TemplateID>0) && (intval($dev->NominalWatts==0)) ){
						$dev->UpdateWattageFromTemplate();
					}
					$dev->CreateDevice();
					$dev->SetTags($tagarray);
				}elseif($user->DeleteAccess && ($_REQUEST['action']=='Delete')){
					$dev->GetDevice();
					$dev->DeleteDevice();
					header('Location: '.redirect("cabnavigator.php?cabinetid=$dev->Cabinet"));
					exit;
				} elseif ( $user->WriteAccess && $_REQUEST["action"] == "Copy" ) {
					$copy=true;
					if(!$dev->CopyDevice()){
						$copyerr=__("Device did not copy.  Error.");
					}
				} elseif($user->WriteAccess&&$_REQUEST['action']=='child') {
					if(isset($_REQUEST['parentdevice'])){
						$dev->DeviceID=null;
						$dev->ParentDevice=$_REQUEST["parentdevice"];
					}
					// sets install date to today when a new device is being created
					$dev->InstallDate=date("m/d/Y");
				}
			}

			// Finished updating devices or creating them.  Refresh the object with data from the DB
			$dev->GetDevice();

			// Get any tags associated with this device
			$tags=$dev->GetTags();
			if(count($tags)>0){
				// We have some tags so build the javascript elements we need to create the tags themselves
				$taginsert="\t\ttags: {items: ".json_encode($tags)."},\n";
			}

			// Since a device exists we're gonna need some additional info, but only if it's not a copy
			if(!$copy){
				$pwrConnection=new PowerConnection();
				$pdu=new PowerDistribution();
				$panel=new PowerPanel();

				$pwrConnection->DeviceID=($dev->ParentDevice>0)?$dev->ParentDevice:$dev->DeviceID;
				$pwrCords=$pwrConnection->GetConnectionsByDevice();

				if($dev->DeviceType=='Switch'){
					$linkList = SwitchInfo::getPortStatus( $dev->DeviceID );
				}
			}else{
				// These are going to be empty however we'll generate an error if they aren't set.
				$patchList=array();
				$panelList=array();
			}

		}
		$cab->CabinetID=$dev->Cabinet;
		$cab->GetCabinet();
	}else{
		// sets install date to today when a new device is being created
		$dev->InstallDate=date("m/d/Y");
	}

	if(!$user->canRead($dev->Owner)){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
	
	if($dev->ParentDevice >0){
		$pDev=new Device();
		$pDev->DeviceID=$dev->ParentDevice;
		$pDev->GetDevice();
		
		$parentList=$pDev->GetParentDevices();
		
		$cab->CabinetID=$pDev->Cabinet;
		$cab->GetCabinet();
		$chassis="Chassis";

		// This is a child device and if the action of new is set let's assume the departmental owner, primary contact, etc are the same as the parent
		if(isset($_REQUEST['action'])&&$_REQUEST['action']=='child'){
			$dev->Owner=$pDev->Owner;
			$dev->EscalationTimeID=$pDev->EscalationTimeID;
			$dev->EscalationID=$pDev->EscalationID;
			$dev->PrimaryContact=$pDev->PrimaryContact;
		}
	}
	
	$childList=array();
	if($dev->ChassisSlots>0 || $dev->RearChassisSlots>0){
		$childList=$dev->GetDeviceChildren();
	}

	if($config->ParameterArray["mDate"]=="now"){
		if($dev->MfgDate <= "1970-01-01"){
			$dev->MfgDate=date("Y-m-d");
		}
	}
		
	if($config->ParameterArray["wDate"]=="now"){
		if($dev->WarrantyExpire <= "1970-01-01"){
			$dev->WarrantyExpire=date("Y-m-d");
		}
	}

	$portList=DevicePorts::getPortList($dev->DeviceID);
	$mediaTypes=MediaTypes::GetMediaTypeList();
	$colorCodes=ColorCoding::GetCodeList();
	$templateList=$templ->GetTemplateList();
	$escTimeList=$escTime->GetEscalationTimeList();
	$escList=$esc->GetEscalationList();
	$deptList=$Dept->GetDepartmentList(); 

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
	$title=($dev->Label!='')?"$dev->Label :: $dev->DeviceID":"openDCIM Device Maintenance";

	function buildesxtable($deviceid){
		$esx=new ESX();
		$esx->DeviceID=$deviceid;
		$vmList=$esx->GetDeviceInventory();

		print "\n<div class=\"table border\"><div><div>".__('VM Name')."</div><div>".__('Status')."</div><div>".__('Owner')."</div><div>".__('Last Updated')."</div></div>\n";
		foreach($vmList as $vmRow){
			if($vmRow->vmState=='poweredOff'){
				$statColor='red';
			}else{
				$statColor='green';
			}
			$Dept=new Department();
			$Dept->DeptID=$vmRow->Owner;
			if($Dept->DeptID >0){
				$Dept->GetDeptByID();
			}else{
				$Dept->Name=__('Unknown');
			}
			print "<div><div>$vmRow->vmName</div><div><font color=$statColor>$vmRow->vmState</font></div><div><a href=\"updatevmowner.php?vmindex=$vmRow->VMIndex\">$Dept->Name</a></div><div>$vmRow->LastUpdated</div></div>\n";
		}
		echo '</div> <!-- END div.table -->';
	}


?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title><?php echo $title; ?></title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <link rel="stylesheet" href="css/jHtmlArea.css" type="text/css">
  <style type="text/css"></style>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-migrate-1.0.0.js"></script>
  <script type="text/javascript" src="scripts/mdetect.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript" src="scripts/jHtmlArea-0.7.5.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.textext.js"></script>

<SCRIPT type="text/javascript" >
var nextField;
function getScan(fieldName){
    var href=window.location.href;
    var ptr=href.lastIndexOf("#");
    if(ptr>0){
        href=href.substr(0,ptr);
    }
	nextField=fieldName;
    window.location.href="zxing://scan/?ret="+escape(href+"#{CODE}");
}
var changingHash=false;
function getHash(){
	if ( !changingHash ) {
		changingHash=true;
		var hash=window.location.hash.substr(1);
		switch (nextField) {
			case "serialno":
				$('#serialno').val(unescape(hash));
				break;
			case "assettag":
				$('#assettag').val(unescape(hash));
				break;
			default:
				break;
		}
		// window.location.hash="";
		changingHash=false;
	}
}
</SCRIPT>

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
<?php echo '		document.getElementById(\'layout\').innerHTML = "',__("Landscape"),'";
	} else {
		sheet.innerHTML = ".device div.left { display: block; }";
		document.getElementById(\'layout\').innerHTML = "',__("Landscape"),'";'; ?>
	}
	var s = document.getElementsByTagName('style')[0];
	if (s.innerHTML == sheet.innerHTML){
		if (sheet.styleSheet){ //IE
			document.getElementsByTagName('style')[0].styleSheet.cssText = "";
<?php echo '			document.getElementById(\'layout\').innerHTML = "',__("Portrait"),'";
		}else{
			document.getElementsByTagName(\'style\')[0].innerHTML = "";
			document.getElementById(\'layout\').innerHTML = "',__("Portrait"),'";'; ?>
		}
		setCookie("layout","Landscape");
	}else{
		s.parentNode.insertBefore(sheet, s);
		setCookie("layout","Portrait");
	}
}
$(document).ready(function() {
	var isMobile = {
		Android: function() {
			return navigator.userAgent.match(/Android/i);
		},
		BlackBerry: function() {
			return navigator.userAgent.match(/BlackBerry/i);
		},
		iOS: function() {
			return navigator.userAgent.match(/iPhone|iPad|iPod/i);
		},
		Opera: function() {
			return navigator.userAgent.match(/Opera Mini/i);
		},
		Windows: function() {
			return navigator.userAgent.match(/IEMobile/i);
		},
		any: function() {
			return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
		}
	};
	// Mobile devices get a scan barcode button and an accordian interface
	if(isMobile.any()){
		$('.main button').each(function(){
			if($(this).text()=='Scan Barcode'){
				$(this).css('display', 'inline');
			}
		});
		$('.left > fieldset ~ .table').each(function(){
<?php print "			$(this).before($('<h3><a href=\"#\">".__('Notes')."</a></h3>'));"; ?>
		});
		$('.right').contents().appendTo($('.left'));
<?php print "		$('.left').append('<h3><a href=\"#\">".__('Network & Power')."</a></h3>');"; ?>
		$('.right').next('div.table').appendTo($('.left'));
		$('.left legend').each(function(){
			$(this).parent('fieldset').before($('<h3><a href="#">'+$(this).text()+'</a></h3>'));
			$(this).remove();
		});
		$('.left > h3 ~ fieldset').each(function(){
			$a=$(this).children('.table');
			$($a.parent()).before($a);
			$(this).remove();
		});
		$('.table + .table').each(function(){
			$(this).prev().wrap($('<div />'));
			$(this).appendTo($(this).prev());
		});
		$('input[name="chassisslots"]').filter($('[type="hidden"]')).insertAfter($('.left'));
		$('.device .table').css('width', 'auto');
		$('.left').after($('<div class="table" id="target" style="width: 100%"></div>'));
		$('.caption').appendTo($('#target'));
		$('.left').accordion({
			autoHeight: false,
			collapsible: true
		}).removeClass('left');  
	}

	// add the current ports value to the document data store
	$(document).data('ports',$('#ports').val());
	$(document).data('devicetype', $('select[name="devicetype"]').val());

	$('#notes').each(function(){
		$(this).before('<button type="button" id="editbtn"></button>');
		if($(this).val()!=''){
			rendernotes($('#editbtn'));
		}else{
			editnotes($('#editbtn'));
		}
	});
	function editnotes(button){
		button.val('preview').text('<?php echo __("Preview");?>');
		var a=button.next('div');
		button.next('div').remove();
		button.next('textarea').htmlarea({
			toolbar: [
			"link", "unlink", "image"
			],
			css: 'css/jHtmlArea.Editor.css'
		});
		$('.jHtmlArea div iframe').height(a.innerHeight());
	}

	function rendernotes(button){
		button.val('edit').text('<?php echo __("Edit");?>');
		var w=button.next('div').outerWidth();
		var h=$('.jHtmlArea').outerHeight();
		if(h>0){
			h=h+'px';
		}else{
			h="auto";
		}
		$('#notes').htmlarea('dispose');
		button.after('<div id="preview">'+$('#notes').val()+'</div>');
		button.next('div').css({'width': w+'px', 'height' : h}).find('a').each(function(){
			$(this).attr('target', '_new');
		});
		$('#notes').html($('#notes').val()).hide(); // we still need this field to submit it with the form
		h=0; // recalculate height in case they added an image that is gonna hork the layout
		// need a slight delay here to allow the load of large images before the height calculations are done
		setTimeout(function(){
			$('#preview').find("*").each(function(){
				h+=$(this).outerHeight();
			});
			$('#preview').height(h);
		},2000);
	}
	$('#editbtn').click(function(){
		var button=$(this);
		if($(this).val()=='edit'){
			editnotes(button);
		}else{
			rendernotes(button);
		}
	});

	$('#deviceform').validationEngine();
	$('#mfgdate').datepicker();
	$('#installdate').datepicker();
	$('#warrantyexpire').datepicker();
	$('#owner').next('button').click(function(){
		window.open('contactpopup.php?deptid='+$('#owner').val(), 'Contacts Lookup', 'width=800, height=700, resizable=no, toolbar=no');
		return false;
	});
	// Make SNMP community visible
	$('#snmpcommunity').focus(function(){$(this).attr('type','text');});
	$('#snmpcommunity').blur(function(){$(this).attr('type','password');});


	// Add in refresh functions for virtual machines
	var esxtable=$('<div>').addClass('table border').append('<div><div>VM Name</div><div>Status</div><div>Owner</div><div>Last Updated</div></div>');
	var esxbutton=$('<button>',{'type':'button'}).css({'position':'absolute','top':'10px','right':'2px'}).text('Refresh');
	esxbutton.click(esxrefresh);
	if($('#esx').val()==1){
		$('#esxframe').css('position','relative').append(esxbutton);
	}
	function esxrefresh(){
		$.post('',{esxrefresh: $('#deviceid').val()}).done(function(data){
			$('#esxframe .table ~ .table').replaceWith(data);
		});
	}	

	// This is for adding blades to chassis devices
	$('#adddevice').click(function() {
		$(":input").attr("disabled","disabled");
		$('#parentdevice').removeAttr("disabled");
		$('#adddevice').removeAttr("disabled");
		$(this).submit();
		$(":input").removeAttr("disabled"); // if they hit back it makes sure the fields aren't disabled
	});
	// Auto-Populate fields based on device templates
	$('#templateid').change( function(){
		$.get('scripts/ajax_template.php?q='+$(this).val(), function(data) {
			$('#height').val(data['Height']);
			$('#ports').val(data['NumPorts']);
			$('#nominalwatts').val(data['Wattage']);
			$('#powersupplycount').val(data['PSCount']);
			$('select[name=devicetype]').val(data['DeviceType']).trigger('change');
			$('#height').trigger('change');
		});
	});

	if($('select[name=devicetype]').val()=='Switch'){$('#firstport').show();}
	$('select[name=devicetype]').change(function(){
		if($(this).val()=='Switch'){
			if($(document).data('devicetype')!='Switch'){
				$('#firstport button:not([name="firstport"])').hide();
			}
			$('#firstport').show();
			$('.switch div[id^="st"]').show();
		}else{
			$('#firstport').hide();
			$('.switch div[id^="st"]').hide();
		}
		if($(this).val()=='Server'){
			$('#esxframe').show();
		}else{
			$('#esxframe').hide();
		}
	});
	$('#firstport button[name=firstport]').click(function(){
		var modal=$('<div />', {id: 'modal', title: 'Select switch first port'}).html('<div id="modaltext"></div><br><div id="modalstatus" class="warning"></div>').dialog({
			appendTo: 'body',
			modal: true,
			close: function(){$(this).dialog('destroy');}
		});
		$.post('',{fp: '', devid: $('#deviceid').val()}).done(function(data){
			$('#modaltext').html(data);
			$('#modaltext input').change(function(){
				var fpnum=$(this).val();
				$.post('',{fp: fpnum, devid: $('#deviceid').val()}).done(function(data){
					$('input[name=firstportnum]').val(fpnum);
					$('#modalstatus').html(data);
					$('#modal').dialog('destroy');
				}).then(refreshswitch($('#deviceid').val(),true));
			});
		});
	});
	$('#firstport button[name=refresh]').click(function(){
		refreshswitch($('#deviceid').val());
	});
	$('#firstport button[name=name]').click(function(){
		refreshswitch($('#deviceid').val(),true);
	});
	function refreshswitch(devid,names){
		if(names){
			var modal=$('<div />', {id: 'modal', title: 'Please wait...'}).html('<div id="modaltext"><img src="images/animatedswitch.gif" style="width: 100%;"><br>Polling device...</div><br><div id="modalstatus" class="warning"></div>').dialog({
				appendTo: 'body',
				minWidth: 500,
				closeOnEscape: false,
				dialogClass: "no-close",
				modal: true
			});
			$.post('',{refreshswitch: devid, names: names}).done(function(data){
				$.each(data, function(i,label){
					if(label){
						$('#spn'+i).text(label);
					}else{
						$('#spn'+i).text('');
					}
				});
				modal.dialog('destroy');
			});
		}else{
			$.post('',{refreshswitch: devid}).done(function(data){
				$.each(data, function(i,portstatus){
					$('#st'+i).html($('<span>').addClass('ui-icon').addClass('status').addClass(portstatus));
				});
			});
		}
	}
<?php
	// hide all the js functions if they don't have write permissions
	if($user->WriteAccess){
		// if they switch device type to switch for a child blade add the dataports field
		if($dev->ParentDevice>0){
?>
		$('select[name=devicetype]').change(function(){
<?php echo '		var dphtml=\'<div id="dphtml"><div><label for="ports">',__("Number of Data Ports"),'</label></div><div><input class="optional,validate[custom[onlyNumberSp]]" name="ports" id="ports" size="4" value="" type="number"></div></div>\';'; ?>
			if($(this).val()=='Switch' && $('#dphtml').length==0){
				$('#nominalwatts').parent().parent().before(dphtml);
			}else{
				$('#dphtml').remove();
			}
		});
<?php
		}
		// Add an extra alert warning about child devices in chassis
		if($dev->DeviceType=='Chassis'){
?>
		$('select[name=devicetype]').change(function(){
			var form=$(this).parents('form');
			var btn=$(this);
			if($(this).val()!='Chassis'){
<?php echo '				$(\'#dialog-confirm\').html(\'<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>',__("If this device has blades installed they will be deleted and there is no undo. Are you sure?"),'</p>\');'; ?>
				$('#dialog-confirm').dialog({
					resizable: false,
					modal: true,
					dialogClass: "no-close",
					buttons: {
						"Yes": function(){
							$(this).dialog("destroy");
							form.append('<input type="hidden" class="killthechildren" name="killthechildren" value="yes">');
						},
						"No": function(){
							$('.killthechildren').remove();
							$('select[name=devicetype]').val('Chassis');
							$(this).dialog("destroy");
						}
					}
				});
			}else{
				$('.killthechildren').remove();
			}
		});
<?php
		}
		
		// hide cabinet slot picker from child devices
		if($dev->ParentDevice==0){
?>
		$('#cabinetid').change(function(){
			$.post('', {cab: $("select#cabinetid").val()}, function(data){
				var posclass=$('#position').attr('class');
				$('#position').attr('class',posclass.replace(/max\[([1-9]).*?\]/gi,"max["+data.trim()+"]")).trigger('focusout');
			});
		});
		$('#height').change(function(){
			if($(this).val()==0){
				$('#position').attr('disabled', 'true');
				$(this).parents('form').append('<input class="tmpposition" type="hidden" name="position" value="0">');
			}else{
				$('#position').removeAttr('disabled');
				$('.tmpposition').remove();
			}
		});
		$('#height').trigger('change');
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
								}else{
									$(this).css({'background-color': background, 'cursor': pointer});
									if(background=='green'){
										$(this).click(function(){
											$('#position').val($(this).attr('val')).trigger('focusout');
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

		$('#ports').change(function(){
			// not sure why .data() is turning an int into a string parseInt is fixing that
			if($(this).val() > parseInt($(document).data('ports'))){
				//make more ports and add the rows below
				$('button[value="Update"]').click();
			}else if($(this).val()==$(document).data('ports')){
				// this is the I changed my mind condition.
				$('div[id^="kp"]').remove();
			}else{
				var dt=['Switch','Patch Panel','Physical Infrastructure'];
				if($.inArray($(document).data('devicetype'),dt)){
					//S.U.T. present options to remove ports
					var row=$('.switch > div:first-child > div:first-child').parent('div');
					var icon=$('<span>').addClass('ui-icon').addClass('status').addClass('down');
					$('<div>',{'id': 'kp'}).prependTo(row);
					$('.switch div:first-child[id^=sp]').each(function(){
						row=$(this).parent('div');
						// this idea is crap and needs to be thought out better but it's time to go home so i'm submitting since it won't break anything
						$('<div>',{'id': 'kp'+$(this).text()}).append(icon.clone()).click({row: row},deletethis).prependTo(row);
					});
				}
			}
		});
		function deletethis(e){
			var lastport=$('.switch > div:last-child div[id^="sp"]:not([id^="spn"])').text();
			var portnum=e.data.row.find('div[id^="sp"]:not([id^="spn"])').text();
			$.post('',{delport: '',swdev: $('#deviceid').val(),pnum: portnum}).done(function(data){
				if(data.trim()==1){
					if($(document).data('ports')>$('#ports').val()){
						// port was removed and last port shuffled into this one's place
						$(document).data('ports',$(document).data('ports')-1);
						$('#ports').val();
						var row=$('#sp'+portnum).parent('div');
						var swaprow=$('#sp'+lastport).parent('div');
						for(var i=2;i<$(swaprow).children('div').length;i++){
							row.children('div').eq(i).html(swaprow.children('div').eq(i).html());
						}
						swaprow.remove();
						if($(document).data('ports')==$('#ports').val()){$('#ports').change();}
					}else{
						$('#ports').change();
					}
				}
			});
		}
		$('#reservation').change(function(){
			if(!$(this).prop("checked")){
				var d=new Date();
				$('#installdate').datepicker("setDate",d);
			}
		});
		// Make connections to other devices
		$('.switch.table > div ~ div').each(function(){
			var row=$(this);
			row.find('div:first-child').click(function(){
				if(!row.data('edit')){
					row.data('edit',true);
					var portnum=$(this).text();
					var portname=$('#spn'+portnum);
					var cdevice=$('#d'+portnum);
					var cdeviceport=$('#dp'+portnum);
					var cnotes=$('#n'+portnum);
					var porttype=$('#mt'+portnum);
					var portcolor=$('#cc'+portnum);
					function getports(devid,portnum){
						var cdeviceport=$('#dp'+portnum);
						$.post('',{swdev: $('#deviceid').val(),pn: portnum,thisdev: devid,listports: ''}).done(function(data){
							var portlist=$("<select>");
							$.each(data, function(key,port){
								var pn=port.PortNumber;
								// only allow positive values
								if(pn>0){
									portlist.append('<option value='+pn+'>'+pn+'</option>');
									portlist.data(pn, {MediaID: port.MediaID, ColorID: port.ColorID});
								}
							});
							portlist.change(function(){
								//Match media type and color on incoming port
								porttype.children('select').val($(this).data($(this).val()).MediaID);
								portcolor.children('select').val($(this).data($(this).val()).ColorID);
							});
							cdeviceport.html(portlist).find('select').val(cdeviceport.data('default'));
						});
					}
					function getmediatypes(portnum){
						$.get('',{mt:''}).done(function(data){
							var mlist=$("<select>").append('<option value=0></option>');
							$.each(data, function(key,mt){
								var option=$("<option>",({'value':mt.MediaID})).append(mt.MediaType);
								mlist.append(option).data(mt.MediaID,mt.ColorID);
							});
							mlist.change(function(){
								// default color is associated with this type so set it
								if($(this).data($(this).val())!=""){
									portcolor.children('select').val($(this).data($(this).val()));
								}
							});
							porttype.html(mlist).find('select').val(porttype.data('default'));
						});
					}
					function getcolortypes(portnum){
						$.get('',{cc:''}).done(function(data){
							var clist=$("<select>").append('<option value=0></option>');
							$.each(data, function(key,cc){
								var option=$("<option>",({'value':cc.ColorID})).append(cc.Name);
								clist.append(option).data(cc.ColorID,cc.DefaultNote);
							});
							clist.change(function(){
								// default note is associated with this color so set it
								if($(this).data($(this).val())!=""){
									cnotes.children('input').val($(this).data($(this).val()));
								}
							});
							portcolor.html(clist).find('select').val(portcolor.data('default'));
						});
					}
					$.post('',{swdev: $('#deviceid').val(),pn: portnum}).done(function(data){
						var devlist=$("<select>").append('<option value=0></option>');
						devlist.change(function(){
							getports($(this).val(),portnum);
						});
						
						$.each(data, function(devid,device){
							devlist.append('<option value='+device.DeviceID+'>'+device.Label+'</option>');
						});
						cdevice.html(devlist).find('select').val(cdevice.data('default'));
						devlist.change();
						cnotes.html('<input type="text" value="'+cnotes.text()+'">');
						portname.html('<input type="text" value="'+portname.text()+'">');
						getmediatypes(portnum);
						getcolortypes(portnum);
					});
					function save(){
						$.post('',{
							saveport: '',
							swdev: $('#deviceid').val(),
							pnum: portnum,
							pname: portname.children('input').val(),
							cdevice: cdevice.children('select').val(),
							cdeviceport: cdeviceport.children('select').val(),
							cnotes: cnotes.children('input').val(),
							porttype: porttype.children('select').val(),
							portcolor: portcolor.children('select').val()
						}).done(function(data){
							if(data.trim()==1){
								redrawrow();
							}else{
								// something broke
							}
						});
					}
					function clear(){
						cdevice.children('select').val(0);
						cdeviceport.children('select').val(0);
						cnotes.children('input').val('');
						save();
					}
					function redrawrow(){
						$.post('',{getport: '',swdev: $('#deviceid').val(),pnum: portnum}).done(function(data){
							portname.html(data.Label).data('default',data.Label);
							cdevice.html('<a href="devices.php?deviceid='+data.ConnectedDeviceID+'">'+data.ConnectedDeviceLabel+'</a>').data('default',data.ConnectedDeviceID);
							cdeviceport.html(data.ConnectedPort).data('default',data.ConnectedPort);
							cnotes.html(data.Notes).data('default',data.Notes);
							porttype.html(data.MediaName).data('default',data.MediaID);
							portcolor.html(data.ColorName).data('default',data.ColorID);
							$('#controls'+portnum).remove();
							row.children('div ~ div').removeAttr('style');
							row.data('edit',false);
						});
					}
					var controls=$('<div>',({'id':'controls'+portnum}));
					var savebtn=$('<button>',{'type':'button'}).append('<?php echo __("Save"); ?>').click(save);
					var cancelbtn=$('<button>',{'type':'button'}).append('<?php echo __("Cancel"); ?>').click(redrawrow);
					var deletebtn=$('<button>',{'type':'button'}).append('<?php echo __("Delete"); ?>').click(clear);
					controls.append(savebtn).append(cancelbtn).append(deletebtn);
					var minwidth=0;
					portcolor.after(controls);
					controls.children('button').each(function(){
						minwidth+=$(this).outerWidth()+14; // 14 padding and border
					});
					controls.css('min-width',minwidth);
					row.children('div ~ div:not([id^=st])').css({'padding': '0px', 'background-color': 'transparent'});
					setTimeout(function() {
						resize();
					},200);
				}
			}).css({'cursor': 'pointer','text-decoration': 'underline'});
		});
		$('.patchpanel > div ~ div').each(function(){
			var row=$(this);
			// create empty row below the current
			var btnrow=$('<div>');
			var i=row.children('div').length;
			for(var a=0; a < i; a++){
				btnrow.append($('<div>'));
			}
			var frontbtn=btnrow.find('div:first-child'); // front table cell for buttons
			var rearbtn=btnrow.find('div:nth-child('+(Math.ceil(i/2)+1)+')'); // rear table cell for buttons
			var portnum=row.data('port');
			var frontdev=$('#fd'+portnum);
			var frontport=$('#fp'+portnum);
			var frontnotes=$('#fn'+portnum);
			var patchport=$('#pp'+portnum);
			var reardev=$('#rd'+portnum);
			var rearport=$('#rp'+portnum);
			var rearnotes=$('#rn'+portnum);
			function clear(e){
				var rear=($(e.currentTarget).parent().data('rear'));
				var fr=(rear)?'r':'f';
				$('#'+fr+'d'+portnum+' select').val('');
				$('#'+fr+'p'+portnum+' select').val('');
				save(e);
			}
			function save(e){
				var rear=($(e.currentTarget).parent().data('rear'));
				var fr=(rear)?'r':'f';
				var cdevice=$('#'+fr+'d'+portnum+' select').val();
				var cdeviceport=$('#'+fr+'p'+portnum+' select').val();
				var porttype=$('#'+fr+'p'+portnum+' select').data(cdeviceport).MediaID;
				var portcolor=$('#'+fr+'p'+portnum+' select').data(cdeviceport).ColorID;
				var cnotes=$('#'+fr+'n'+portnum+' input').val();
				var p=(rear)?portnum*-1:portnum;
				porttype=(porttype===undefined)?0:porttype;
				portcolor=(portcolor===undefined)?0:portcolor;
				$.post('',{saveport: '', swdev: $('#deviceid').val(), pnum: p,
							pname: '', cdevice: cdevice, cdeviceport: cdeviceport,
							cnotes: cnotes, porttype: porttype,
							portcolor: portcolor}).done(function(data){
					if(data.trim()==1){
						redrawrow(e);
					}else{
						// something broke
					}
				});
			}
			function devicelist(jsonObject){
				var devlist=$("<select>").append('<option value=0></option>');
				$.each(jsonObject, function(devid,device){
					devlist.append('<option value='+device.DeviceID+'>'+device.Label+'</option>');
				});
				devlist.change(function(e){
					var rear=($(e.currentTarget).parent().data('rear'));
					getavailports($(this).val(),portnum,rear);
				});
				return devlist;
			}
			function getavailports(devid,portnum,rear){
				var p=(rear)?portnum*-1:portnum;
				$.post('',{swdev: $('#deviceid').val(),pn: p,thisdev: devid,listports: ''}).done(function(data){
					var portlist=$("<select>");
					$.each(data, function(key,port){
						var pn=port.PortNumber;
						if(rear){
							if(pn<0){
								portlist.prepend('<option value='+pn+'>'+(pn*-1)+'</option>');
								portlist.data(pn, {MediaID: port.MediaID, ColorID: port.ColorID});
							}
						}else{
							if(pn>0){
								portlist.append('<option value='+pn+'>'+pn+'</option>');
								portlist.data(pn, {MediaID: port.MediaID, ColorID: port.ColorID});
							}
						}
					});
					if(rear){
						rearport.html(portlist.val(rearport.data('default')));
					}else{
						frontport.html(portlist.val(frontport.data('default')));
					}
				});
			}
			function redrawrow(e){
				var rear=($(e.currentTarget).parent().data('rear'));
				var p=(rear)?portnum*-1:portnum;
				$.post('',{getport: '',swdev: $('#deviceid').val(),pnum: p}).done(function(data){
					if(rear){
						reardev.html('<a href="devices.php?deviceid='+data.ConnectedDeviceID+'">'+data.ConnectedDeviceLabel+'</a>').data('default',data.ConnectedDeviceID);
						var cp=(data.ConnectedPort<0)?data.ConnectedPort*-1:'';
						rearport.html(cp).data('default',data.ConnectedPort);
						rearnotes.html(data.Notes).data('default',data.Notes);
						row.children('div[id^=r]').removeAttr('style');
					}else{
						frontdev.html('<a href="devices.php?deviceid='+data.ConnectedDeviceID+'">'+data.ConnectedDeviceLabel+'</a>').data('default',data.ConnectedDeviceID);
						frontport.html(data.ConnectedPort).data('default',data.ConnectedPort);
						frontnotes.html(data.Notes).data('default',data.Notes);
						row.children('div[id^=f]').removeAttr('style');
					}
					$(e.currentTarget).parent().remove();
				});
				if(btnrow.find('.controls').length===1){
					row.data('edit',false);
					btnrow.remove();
				}
			}
			var controls=$('<div>',({'class':'controls'})).css({'padding': 0, 'border': 0});
			var savebtn=$('<button>',{'type':'button'}).append('<?php echo __("Save"); ?>').click(save);
			var cancelbtn=$('<button>',{'type':'button'}).append('<?php echo __("Cancel"); ?>').click(redrawrow);
			var deletebtn=$('<button>',{'type':'button'}).append('<?php echo __("Delete"); ?>').click(clear);
			controls.append(savebtn).append(deletebtn).append(cancelbtn);
			patchport.click(function(){
				if(!row.data('edit')==true){
					row.after(btnrow);
					row.data('edit',true);
<?php
		if($user->SiteAdmin){
?>
// Rear panel controls
					rearbtn.append(controls.clone(true).data('rear',true));
					rearbtn.css({'padding': 0, 'border': 0}).attr('data', 'rear');;
					$.post('', {pn: $(this).text(), swdev: $('#deviceid').val(), rear: ''}, function(data){
						reardev.html(devicelist(data).val(reardev.data('default'))).data('rear',true).children().change();
						rearnotes.html($('<input>').val(rearnotes.data('default')));
					}).then(resize());
<?php
		}
?>
// Front panel controls
					frontbtn.append(controls.clone(true).data('rear',false));
					frontbtn.css({'padding': 0, 'border': 0}).attr('data', 'front');
					$.post('', {pn: $(this).text(), swdev: $('#deviceid').val()}, function(data){
						frontdev.html(devicelist(data).val(frontdev.data('default'))).children().change();
						frontnotes.html($('<input>').val(frontnotes.text()));
					}).then(resize()).then(resize());
					row.children('div:not([id^=pp])').css({'padding': 0, 'border': 0});
				}
			}).css({'cursor': 'pointer','text-decoration': 'underline'});
		});
		// Delete device confirmation dialog
		$('button[value="Delete"]').click(function(e){
			var form=$(this).parents('form');
			var btn=$(this);
<?php echo '			$(\'#dialog-confirm\').html(\'<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>',__("This device will be deleted and there is no undo. Are you sure?"),'</p>\');'; ?>
			$('#dialog-confirm').dialog({
				resizable: false,
				modal: true,
				buttons: {
					"Yes": function(){
						$(this).dialog("destroy");
						form.append('<input type="hidden" name="'+btn.attr("name")+'" value="'+btn.val()+'">');
						form.submit();
					},
					"No": function(){
						$(this).dialog("destroy");
					}
				}
			});
		});
<?php 
	} // end of javascript editing functions
?>
	function setPreferredLayout() {<?php if(isset($_COOKIE["layout"]) && strtolower($_COOKIE["layout"])==="portrait"){echo 'swaplayout();setCookie("layout","Portrait");';}else{echo 'setCookie("layout","Landscape");';} ?>}
	setPreferredLayout();
	$('#tags').width($('#tags').parent('div').parent('div').innerWidth()-$('#tags').parent('div').prev('div').outerWidth()-5);
	
	$('#tags').textext({
		plugins : 'autocomplete tags ajax arrow prompt focus',
<?php echo $taginsert; ?>
		ajax : {
			url : 'scripts/ajax_tags.php',
			dataType : 'json'
		}
	});
});
	
</script>

</head>
<body onhashchange="getHash()">
<div id="header"></div>
<div class="page device">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<button id="layout" onClick="swaplayout()">'.__("Portrait").'</button>
<h2>'.$config->ParameterArray['OrgName'].'</h2>
<h3>'.__("Data Center Device Detail").'</h3>';
echo($copy)?"<h3>$copyerr</h3>":'';
echo '<div class="center"><div>
<div id="positionselector"></div>
<form name="deviceform" id="deviceform" action="'.$_SERVER['PHP_SELF'].((isset($dev->DeviceID) && $dev->DeviceID>0)?"?deviceid=$dev->DeviceID":"").'" method="POST">
<div class="left">
<fieldset>
	<legend>'.__("Asset Tracking").'</legend>
	<div class="table">
		<div>
		   <div>'.__("Device ID").'</div>
		   <div><input type="text" name="deviceid" id="deviceid" value="'.$dev->DeviceID.'" size="6" readonly></div>
		</div>
		<div>
			<div><label for="reservation">'.__("Reservation?").'</label></div>
			<div><input type="checkbox" name="reservation" id="reservation"'.((($dev->Reservation) || $copy )?" checked":"").'></div>
		</div>
		<div>
		   <div><label for="label">'.__("Label").'</label></div>
		   <div><input type="text" class="validate[required,minSize[3],maxSize[50]]" name="label" id="label" size="40" value="'.$dev->Label.'"></div>
		</div>
		<div>
		   <div><label for="serialno">'.__("Serial Number").'</label></div>
		   <div><input type="text" name="serialno" id="serialno" size="40" value="'.$dev->SerialNo.'">
		   <button class="hide" type="button" onclick="getScan(\'serialno\')">',__("Scan Barcode"),'</button></div>
		</div>
		<div>
		   <div><label for="assettag">'.__("Asset Tag").'</label></div>
		   <div><input type="text" name="assettag" id="assettag" size="20" value="'.$dev->AssetTag.'">
		   <button class="hide" type="button" onclick="getScan(\'assettag\')">',__("Scan Barcode"),'</button></div>
		</div>
		<div>
		  <div><label for="primaryip">'.__("Primary IP").'</label></div>
		  <div><input type="text" name="primaryip" id="primaryip" size="20" value="'.$dev->PrimaryIP.'">
				<input type="hidden" name="firstportnum" value="'.$dev->FirstPortNum.'"></div>
		</div>
		<div>
		  <div><label for="snmpcommunity">'.__("SNMP Read Only Community").'</label></div>
		  <div><input type="password" name="snmpcommunity" id="snmpcommunity" size="40" value="'.$dev->SNMPCommunity.'"></div>
		</div>
		<div>
		   <div><label for="mfgdate">'.__("Manufacture Date").'</label></div>
		   <div><input type="text" class="validate[optional,custom[date]] datepicker" name="mfgdate" id="mfgdate" value="'.(($dev->MfgDate>'0000-00-00 00:00:00')?date('m/d/Y',strtotime($dev->MfgDate)):"").'">
		   </div>
		</div>
		<div>
		   <div><label for="installdate">'.__("Install Date").'</label></div>
		   <div><input type="text" class="validate[required,custom[date]] datepicker" name="installdate" id="installdate" value="'.(($dev->InstallDate>'0000-00-00 00:00:00')?date('m/d/Y',strtotime($dev->InstallDate)):"").'"></div>
		</div>
		<div>
		   <div><label for="warrantyco">'.__("Warranty Company").'</label></div>
		   <div><input type="text" name="warrantyco" id="warrantyco" value="'.$dev->WarrantyCo.'"></div>
		</div>
		<div>
		   <div><label for="installdate">'.__("Warranty Expiration").'</label></div>
		   <div><input type="text" class="validate[custom[date]] datepicker" name="warrantyexpire" id="warrantyexpire" value="'.date('m/d/Y',strtotime($dev->WarrantyExpire)).'"></div>
		</div>		
		<div>
		   <div><label for="owner">'.__("Departmental Owner").'</label></div>
		   <div>
			<select name="owner" id="owner">
				<option value=0>'.__("Unassigned").'</option>';

			foreach($deptList as $deptRow){
				if($dev->Owner==$deptRow->DeptID){$selected=" selected";}else{$selected="";}
				print "\t\t\t\t<option value=\"$deptRow->DeptID\"$selected>$deptRow->Name</option>\n";
			}

echo '			</select>
			<button type="button">',__("Show Contacts"),'</button>
		   </div>
		</div>
		<div>
		   <div>&nbsp;</div>
		   <div><fieldset>
		   <legend>',__("Escalation Information"),'</legend>
		   <div class="table">
			<div>
				<div><label for="escaltationtimeid">',__("Time Period"),'</label></div>
				<div><select name="escalationtimeid" id="escalationtimeid">
					<option value="">',__("Select..."),'</option>';

				foreach($escTimeList as $escTime){
					if($escTime->EscalationTimeID==$dev->EscalationTimeID){$selected=" selected";}else{$selected="";}
					print "\t\t\t\t\t<option value=\"$escTime->EscalationTimeID\"$selected>$escTime->TimePeriod</option>\n";
				}

echo '				</select></div>
			</div>
			<div>
				<div><label for="escalationid">',__("Details"),'</label></div>
				<div><select name="escalationid" id="escalationid">
					<option value="">',__("Select..."),'</option>';

				foreach($escList as $esc){
					if($esc->EscalationID==$dev->EscalationID){$selected=" selected";}else{$selected="";}
					print "\t\t\t\t\t<option value=\"$esc->EscalationID\"$selected>$esc->Details</option>\n";
				}

echo '				</select></div>
			</div>
		   </div> <!-- END div.table -->
		   </fieldset></div>
		</div>
		<div>
		   <div><label for="primarycontact">',__("Primary Contact"),'</label></div>
		   <div><select name="primarycontact" id="primarycontact">
				<option value=0>',__("Unassigned"),'</option>';

			foreach($contactList as $contactRow){
				if($contactRow->ContactID==$dev->PrimaryContact){$contactUserID=$contactRow->UserID;$selected=" selected";}else{$selected="";}
				print "\t\t\t\t<option value=\"$contactRow->ContactID\"$selected>$contactRow->LastName, $contactRow->FirstName</option>\n";
			}
			
			print "\t\t\t</select>\n";

			if(isset($config->ParameterArray['UserLookupURL']) && isValidURL($config->ParameterArray['UserLookupURL']) && isset($contactUserID)){
				print "<button type=\"button\" onclick=\"window.open( '".$config->ParameterArray["UserLookupURL"]."$contactUserID', 'UserLookup')\">".__('Contact Lookup')."</button>\n";
			}

echo '		   </div>
		</div>
		<div>
			<div><label for="tags">',__("Tags"),'</label></div>
			<div><textarea type="text" name="tags" id="tags" rows="1"></textarea></div>
		</div>
	</div> <!-- END div.table -->
</fieldset>	
	<div class="table">
		<div>
		  <div><label for="notes">',__("Notes"),'</label></div>
		  <div><textarea name="notes" id="notes" cols="40" rows="8">',$dev->Notes,'</textarea></div>
		</div>
	</div> <!-- END div.table -->
</div><!-- END div.left -->
<div class="right">
<fieldset>
	<legend>',__("Physical Infrastructure"),'</legend>
	<div class="table">
		<div>
			<div><label for="cabinet">',__("Cabinet"),'</label></div>';

		if($dev->ParentDevice==0){
			print "\t\t\t<div>".$cab->GetCabinetSelectList()."</div>\n";
		}else{
			print "\t\t\t<div>$cab->Location<input type=\"hidden\" name=\"cabinetid\" value=\"0\"></div>
		</div>
		<div>
			<div><label for=\"parentdevice\">".__('Parent Device')."</label></div>
			<div><select name=\"parentdevice\">\n";
			
			foreach($parentList as $parDev){
				if($pDev->DeviceID==$parDev->DeviceID){$selected=" selected";}else{$selected="";}
				print "\t\t\t\t<option value=\"$parDev->DeviceID\"$selected>$parDev->Label</option>\n";
			}
			print "\t\t\t</select></div>\n";
		}

echo '		</div>
		<div>
			<div><label for="templateid">',__("Device Class"),'</label></div>
			<div><select name="templateid" id="templateid">
				<option value=0>',__("Select a template..."),'</option>';

			foreach($templateList as $tempRow){
				if($dev->TemplateID==$tempRow->TemplateID){$selected=" selected";}else{$selected="";}
				$mfg->ManufacturerID=$tempRow->ManufacturerID;
				$mfg->GetManufacturerByID();
				print "\t\t\t\t<option value=\"$tempRow->TemplateID\"$selected>$mfg->Name - $tempRow->Model</option>\n";
			}

echo '			</select>
			</div>
		</div>
		<div>
		   <div><label for="position">',__("Position"),'</label></div>
		   <div><input type="number" class="required,validate[custom[onlyNumberSp],min[1],max[',$cab->CabinetHeight,']]" name="position" id="position" size="4" value="',$dev->Position,'"></div>
		</div>
		<div>
		   <div><label for="height">',__("Height"),'</label></div>
		   <div><input type="number" class="required,validate[custom[onlyNumberSp]]" name="height" id="height" size="4" value="',$dev->Height,'"></div>
		</div>';

		// Blade devices don't have data ports unless they're a switch
		if($dev->ParentDevice==0||($dev->ParentDevice>0&&$dev->DeviceType=='Switch')){
			echo '		<div id="dphtml">
		   <div><label for="ports">',__("Number of Data Ports"),'</label></div>
		   <div><input type="number" class="optional,validate[custom[onlyNumberSp]]" name="ports" id="ports" size="4" value="',$dev->Ports,'"></div>
		</div>';
		}

echo '		<div>
		   <div><label for="nominalwatts">',__("Nominal Draw (Watts)"),'</label></div>
		   <div><input type="text" class="optional,validate[custom[onlyNumberSp]]" name="nominalwatts" id="nominalwatts" size=6 value="',$dev->NominalWatts,'"></div>
		</div>';

		// Blade devices don't have power supplies but they do have a front or back designation
		if($dev->ParentDevice==0){
			echo '		<div>
		   <div><label for="powersupplycount">',__("Number of Power Supplies"),'</label></div>
		   <div><input type="number" class="optional,validate[custom[onlyNumberSp]]" name="powersupplycount" id="powersupplycount" size=4 value="',$dev->PowerSupplyCount,'"></div>
		</div>';
		}else{
			echo '		<div>
			<div><label for="powersupplycount">',__("Front / Rear"),'</label></div>
			<div><select id="chassisslots" name="chassisslots">
		   		<option value=0'.(($dev->ChassisSlots==0)?' selected':'').'>',__("Front"),'</option>
				<option value=1'.(($dev->ChassisSlots==1)?' selected':'').'>',__("Rear"),'</option>
			</select></div>
		</div>';
		}

echo '		<div>
		   <div>',__("Device Type"),'</div>
		   <div><select name="devicetype">
			<option value=0>',__("Select..."),'</option>';

		// We don't want someone accidentally adding a chassis device inside of a chassis slot.
		if($dev->ParentDevice>0){
			$devarray=array('Server' => __("Server"),
							'Appliance' => __("Appliance"),
							'Storage Array' => __("Storage Array"),
							'Switch' => __("Switch"));
		}else{
			$devarray=array('Server' => __("Server"),
							'Appliance' => __("Appliance"),
							'Storage Array' => __("Storage Array"),
							'Switch' => __("Switch"),
							'Chassis' => __("Chassis"),
							'Patch Panel' => __("Patch Panel"),
							'Physical Infrastructure' => __("Physical Infrastructure"));
		}

		foreach($devarray as $devType => $translation){
			if($devType==$dev->DeviceType){$selected=" selected";}else{$selected="";}
			print "\t\t\t<option value=\"$devType\"$selected>$translation</option>\n";  
		}
?>
		   </select></div>
		</div>
	</div> <!-- END div.table -->
</fieldset>
<fieldset id="firstport" class="hide">
<?php echo '	<legend>'.__("Switch SNMP").'</legend>
	<div><p>'.__("Use these buttons to set the first port for the switch, check the status of the ports again, or attempt to load the Port Name labels from the switch device.").'</p><button type="button" name="firstport">'.__("Set First Port").'</button><button type="button" name="refresh">'.__("Refresh Status").'</button><button type="button" name="name">'.__("Refresh Port Names").'</button></div>
</fieldset>';

	//
	// Do not display the chassis contents block if this is a child device (ParentDevice > 0)
	//
	if($dev->DeviceType=='Chassis'){

echo '<fieldset class="chassis">
	<legend>',__("Chassis Contents"),'</legend>
	<div class="table">
		<div>
			<div>&nbsp;</div>
			<div>',__("Front"),'</div>
			<div class="greybg">',__("Rear"),'</div>
		</div>
		<div>
			<div><label for="chassisslots">',__("Number of Slots in Chassis:"),'</label></div>
			<div><input type="text" id="chassisslots" class="optional,validate[custom[onlyNumberSp]]" name="chassisslots" size="4" value="',$dev->ChassisSlots,'"></div>
			<div class="greybg"><input type="text" id="rearchassisslots" class="optional,validate[custom[onlyNumberSp]]" name="rearchassisslots" size="4" value="',$dev->RearChassisSlots,'"></div>
		</div>';
	
	if($dev->ChassisSlots >0){

echo '	</div>
	<div class="table">
		<div>
			<div>',__("Slot #"),'</div>
			<div>',__("Height"),'</div>
			<div>',__("Device Name"),'</div>
			<div>',__("Device Type"),'</div>
		</div>';

	foreach($childList as $chDev){
		print "\t\t<div".(($chDev->ChassisSlots)?' class="greybg"':'').">
			<div>$chDev->Position</div>
			<div>$chDev->Height</div>
			<div><a href=\"devices.php?deviceid=$chDev->DeviceID\">$chDev->Label</a></div>
			<div>$chDev->DeviceType</div>
		</div>\n";
	}
echo '		<div class="caption">
			<button type="submit" id="adddevice" value="child" name="action">',__("Add Device"),'</button>
			<input type="hidden" id="parentdevice" name="parentdevice" disabled value="',$dev->DeviceID,'">
		</div>';
	}else{
echo '		<div class="caption">
			',__("You must first define how many slots are in the chassis before you can add devices."),'
		</div>';
	}
?>
	</div>
</fieldset>
<?php
	}
	
	// Do not display ESX block if device isn't a virtual server and the user doesn't have write access
	if(($user->WriteAccess || $dev->ESX) && ($dev->DeviceType=="Server" || $dev->DeviceType=="")){
		echo '<fieldset id="esxframe">	<legend>',__("VMWare ESX Server Information"),'</legend>';
	// If the user doesn't have write access display the list of VMs but not the configuration information.
		if($user->WriteAccess){

echo '	<div class="table">
		<div>
		   <div><label for="esx">'.__("ESX Server?").'</label></div>
		   <div><select name="esx" id="esx"><option value="1"'.(($dev->ESX==1)?" selected":"").'>'.__("True").'</option><option value="0"'.(($dev->ESX==0)?" selected":"").'>'.__("False").'</option></select></div>
		</div>
	</div><!-- END div.table -->';

		}
		if($dev->ESX){
			buildesxtable($dev->DeviceID);
		}
		print "</fieldset>\n";
	}
?>
</div><!-- END div.right -->
<div class="table" id="pandn">
<div><div>
<div class="table style">
<?php
	//HTML content condensed for PHP logic clarity.
	// If $pwrCords is null then we're creating a device record. Skip power checking.
	if(!is_null($pwrCords)&&((isset($_POST['action'])&&$_POST['action']!='child')||!isset($_POST['action']))){
		if(count($pwrCords)==0){
			// We have no power information. Display links to PDU's in cabinet?
			echo '	<div>		<div><a name="power"></a></div>		<div>',__("No power connections defined.  You can add connections from the power strip screen."),'</div></div><div><div>&nbsp;</div><div></div></div>';
		}else{
			print "		<div>\n		  <div><a name=\"power\">$chassis ".__('Power Connections')."</a></div>\n		  <div><div class=\"table border\">\n			<div><div>".__('Panel')."</div><div>".__('Power Strip')."</div><div>".__('Plug #')."</div><div>".__('Power Supply')."</div></div>";
			foreach($pwrCords as $cord){
				$pdu->PDUID=$cord->PDUID;
				$pdu->GetPDU();
				$panel->PanelID=$pdu->PanelID;
				$panel->GetPanel();
				print "			<div><div><a href=\"power_panel.php?panelid=$pdu->PanelID\">$panel->PanelLabel</a></div><div><a href=\"power_pdu.php?pduid=$pdu->PDUID\">$pdu->Label</a></div><div>$cord->PDUPosition</div><div>$cord->DeviceConnNumber</div></div>\n";
			}
			print "			</div><!-- END div.table --></div>\n		</div>\n		<div>\n			<div>&nbsp;</div><div></div>\n		</div>\n";
		}
	}

	// New simplified model will apply to all devices except for patch panels and physical infrastructure
	if(!in_array($dev->DeviceType,array('Physical Infrastructure','Patch Panel')) && !empty($portList) ){
		print "		<div>\n		  <div><a name=\"net\">".__('Connections')."</a></div>\n		  <div>\n			<div class=\"table border switch\">\n				<div>
				<div>#</div>
				<div>".__('Port Name')."</div>
				<div>".__('Device')."</div>
				<div>".__('Device Port')."</div>
				<div>".__('Notes')."</div>";
		if($dev->DeviceType=='Switch'){print "\t\t\t\t<div id=\"st\">".__("Status")."</div>";}
		print "\t\t\t\t<div>".__("Media Type")."</div>
			<div>".__("Color Code")."</div>
			</div>\n";

		foreach($portList as $i => $port){
			$tmpDev=new Device();
			$tmpDev->DeviceID=$port->ConnectedDeviceID;
			$tmpDev->GetDevice();

			$mt=(isset($mediaTypes[$port->MediaID]))?$mediaTypes[$port->MediaID]->MediaType:'';
			$cc=(isset($colorCodes[$port->ColorID]))?$colorCodes[$port->ColorID]->Name:'';

			// the data attribute is used to store the previous value of the connection
			print "\t\t\t\t<div>
					<div id=\"sp$i\">$i</div>
					<div id=\"spn$i\">{$port->Label}</div>
					<div id=\"d$i\" data-default=\"{$port->ConnectedDeviceID}\"><a href=\"devices.php?deviceid={$port->ConnectedDeviceID}\">$tmpDev->Label</a></div>
					<div id=\"dp$i\" data-default=\"{$port->ConnectedPort}\">{$port->ConnectedPort}</div>
					<div id=\"n$i\" data-default=\"{$port->Notes}\">{$port->Notes}</div>";
			if($dev->DeviceType=='Switch'){print "\t\t\t\t<div id=\"st$i\"><span class=\"ui-icon status {$linkList[$i]}\"></span></div>";}
			print "\t\t\t\t<div id=\"mt$i\">$mt</div>
					<div id=\"cc$i\">$cc</div>
				</div>\n";
		}
		echo "			</div><!-- END div.table -->\n		  </div>\n		</div>";
	}

	if($dev->DeviceType=='Patch Panel'){
		print "\n\t<div>\n\t\t<div><a name=\"net\">".__('Connections')."</a></div>\n\t\t<div>\n\t\t\t<div class=\"table border patchpanel\">\n\t\t\t\t<div><div>".__('Front')."</div><div>Device Port</div><div>".__('Notes')."</div><div>".__('Patch Port')."</div><div>".__('Back')."</div><div>Device Port</div><div>".__('Notes')."</div></div>\n";
		for($n=0; $n< sizeof($portList)/2; $n++){
			$i = $n + 1;	// The "port number" starting at 1
			$frontDev=new Device();
			$rearDev=new Device();
			$frontDev->DeviceID=$portList[$i]->ConnectedDeviceID;
			$rearDev->DeviceID=$portList[-$i]->ConnectedDeviceID;
			$frontDev->GetDevice();
			$rearDev->GetDevice();
			$rp=($portList[-$i]->ConnectedPort!='')?$portList[-$i]->ConnectedPort*-1:'';
			print "\n\t\t\t\t<div data-port=$i>
					<div id=\"fd$i\" data-default=$frontDev->DeviceID><a href=\"devices.php?deviceid=$frontDev->DeviceID\">$frontDev->Label</a></div>
					<div id=\"fp$i\" data-default={$portList[$i]->ConnectedPort}>{$portList[$i]->ConnectedPort}</div>
					<div id=\"fn$i\" data-default=\"{$portList[$i]->Notes}\">{$portList[$i]->Notes}</div>
					<div id=\"pp$i\">$i</div>
					<div id=\"rd$i\" data-default=$rearDev->DeviceID><a href=\"devices.php?deviceid=$rearDev->DeviceID\">$rearDev->Label</a></div>
					<div id=\"rp$i\" data-default={$portList[-$i]->ConnectedPort}>$rp</div>
					<div id=\"rn$i\" data-default=\"{$portList[-$i]->Notes}\">{$portList[-$i]->Notes}</div>
				</div>";
		}
		print "\t\t\t</div><!-- END div.table -->\n\t\t</div>\n\t</div>\n";
	}
?>
		<div class="caption">
<?php
	if($user->WriteAccess){
		if($dev->DeviceID >0){
			echo '			<button type="submit" name="action" value="Update">',__("Update"),'</button>
			<button type="submit" name="action" value="Copy">', __("Copy"), '</button>';
		} else {
			echo '			<button type="submit" name="action" value="Create">',__("Create"),'</button>';
		}
	}
	// Delete rights are seperate from write rights
	if($user->DeleteAccess && $dev->DeviceID >0){
		echo '		<button type="button" name="action" value="Delete">',__("Delete"),'</button>';
	}
?>

		</div>
	</div> <!-- END div.table -->
</div></div>
</div> <!-- END div.table -->
</form>
</div></div>
<?php
	if($dev->ParentDevice >0){
		print "   <a href=\"devices.php?deviceid=$pDev->DeviceID\">[ ".__('Return to Parent Device')." ]</a>\n";
	}elseif($dev->Cabinet >0){
		print "   <a href=\"cabnavigator.php?cabinetid=$cab->CabinetID\">[ ".__('Return to Navigator')." ]</a>";
	}else{
		echo '   <div><a href="storageroom.php">[ ',__("Return to Navigator"),' ]</a></div>';
	}
?>
<div id="dialog-confirm" title="Verify Delete Device" class="hide"></div>

</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
	$(document).ready(function() {
		// Don't attempt to open the datacenter tree until it is loaded
		function opentree(){
			if($('#datacenters .bullet').length==0){
				setTimeout(function(){
					opentree();
				},500);
			}else{
				expandToItem('datacenters','cab<?php echo $cab->CabinetID;?>');
			}
		}
		opentree();
	});
</script>

</body>
</html>
