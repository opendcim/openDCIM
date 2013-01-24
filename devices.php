<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$dev=new Device();
	$cab=new Cabinet();
	$user=new User();
	$contact=new Contact();
	
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );

	$taginsert="";

	if(!$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
	// Ajax functions and we only want these exposed to people with write access
	if($user->WriteAccess){
		if(isset($_POST['swdev'])){
			$dev->DeviceID=$_POST['swdev'];
			$dev->GetDevice($facDB);
			$patchCandidates=$dev->CreatePatchCandidateList($facDB);
			$connect=new SwitchConnection();
			$connect->SwitchDeviceID=$dev->DeviceID;
			$connect->SwitchPortNumber=$_POST['sp'];
			if(isset($_POST['devid'])){
				$connect->EndpointDeviceID=$_POST['devid'];
				$connect->EndpointPort=$_POST['dp'];
				$connect->Notes=$_POST['n'];
				if($connect->EndpointDeviceID==-1){
					echo $connect->RemoveConnection($facDB,true);
				}else{
					// Since I can't be bothered to put in an actual update, just remove the existing connection first.
					$connect->RemoveConnection($facDB,true);
					// should kick back -1 if the insert fails and 1 if it is successful
					echo $connect->CreateConnection($facDB);
				}
			}else{
				$connect->GetSwitchPortConnector($facDB);
				echo '<select name="endpointdeviceid" id="endpointdeviceid"><option value=-1>No Connection</option>';
				foreach($patchCandidates as $key=>$devRow){
					if($connect->EndpointDeviceID==$devRow->DeviceID){$selected=" selected";}else{$selected="";}
					if($dev->ParentDevice>0 && $dev->ParentDevice==$devRow->DeviceID){$selected=" disabled";}
					print "<option value=$devRow->DeviceID$selected>$devRow->Label</option>\n";
				}
				echo '</select>';
			}
			exit;
		}
		if(isset($_POST['pdev'])){
			$patchConnect=new PatchConnection();
			$patchConnect->PanelDeviceID=$_POST['pdev'];
			if(isset($_POST['pdel'])){
				$patchConnect->PanelPortNumber=$_POST['pdel'];
				if($_POST['side']=='front'){
					echo ($patchConnect->RemoveFrontConnection($facDB))?1:0;
				}elseif($_POST['side']=='rear'){
					echo ($patchConnect->RemoveRearConnection($facDB))?1:0;
				}else{
					echo '0';
				}
				exit;
			}
			if(isset($_POST['pget'])){
				$patchConnect->PanelPortNumber=intval($_POST['pget']);
				$patchConnect->GetConnectionRecord($facDB);
				$frontdev=new Device();
				$frontdev->DeviceID=$patchConnect->FrontEndpointDeviceID;
				$frontdev->GetDevice($facDB);
				$dev->DeviceID=$patchConnect->RearEndpointDeviceID;
				$dev->GetDevice($facDB);
				$rowarray=array();
				$rowarray[1]="<a href=\"devices.php?deviceid=$frontdev->DeviceID\">$frontdev->Label</a>";
				$rowarray[2]="$patchConnect->FrontEndpointPort";
				$rowarray[3]="$patchConnect->FrontNotes";
				$rowarray[4]="$patchConnect->PanelPortNumber";
				$rowarray[5]="<a href=\"devices.php?deviceid=$dev->DeviceID\">$dev->Label</a>";
				$rowarray[6]="$patchConnect->RearEndpointPort";
				$rowarray[7]="$patchConnect->RearNotes";
				echo json_encode($rowarray);
				exit;
			}
			if(isset($_POST['psav'])){
				$patchConnect->PanelPortNumber=$_POST['psav'];
				if(isset($_POST['fdev'])){ // if set then we're dealing with a front connection
					$patchConnect->FrontEndpointDeviceID=$_POST['fdev'];
					$patchConnect->FrontEndpointPort=$_POST['fport'];
					$patchConnect->FrontNotes=$_POST['fn'];
					if($_POST['fdev']==-1){ // connection was saved as remove front half
						if(!$patchConnect->RemoveFrontConnection($facDB)){ // something broke return an error
							echo '0';
							exit;
						}
					}else{
						if(!$patchConnect->MakeFrontConnection($facDB)){
							echo 0;
							exit;
						}
					}
				}elseif(isset($_POST['rdev'])){ // if set then we're dealing with a rear connection
					$patchConnect->RearEndpointDeviceID=$_POST['rdev'];
					$patchConnect->RearEndpointPort=$_POST['rport'];
					$patchConnect->RearNotes=$_POST['rn'];
					if($_POST['rdev']==-1){ // connection was saved as remove rear half
						if(!$patchConnect->RemoveRearConnection($facDB)){
							echo 0;
							exit;
						}
					}elseif($_POST['rdev']=='note'){ // connection was saved as note only
						$patchConnect->RearEndpointDeviceID=null;
						$patchConnect->RearEndpointPort=null;
						$patchConnect->RearNotes=$_POST['rn'];
						if(!$patchConnect->MakeRearConnection($facDB)){
							echo 0;
							exit;
						}
					}else{
						if(!$patchConnect->MakeRearConnection($facDB)){
							echo 0;
							exit;
						}
					}
				}else{ // neither was set so I don't know wtf happened
					echo 0;
					exit;
				}
				$frontdev=new Device();
				$frontdev->DeviceID=$patchConnect->FrontEndpointDeviceID;
				$frontdev->GetDevice($facDB);
				$dev->DeviceID=$patchConnect->RearEndpointDeviceID;
				$dev->GetDevice($facDB);
				$rowarray=array();
				$rowarray[1]="<a href=\"devices.php?deviceid=$frontdev->DeviceID\">$frontdev->Label</a>";
				$rowarray[2]="$patchConnect->FrontEndpointPort";
				$rowarray[3]="$patchConnect->FrontNotes";
				$rowarray[4]="$patchConnect->PanelPortNumber";
				$rowarray[5]="<a href=\"devices.php?deviceid=$dev->DeviceID\">$dev->Label</a>";
				$rowarray[6]="$patchConnect->RearEndpointPort";
				$rowarray[7]="$patchConnect->RearNotes";
				echo json_encode($rowarray);
				exit;
			}
			$patchList=$dev::GetPatchPanels($facDB);
			echo '<select name="devid"><option value=-1>No Connection</option><option value="note">Note Only</option>';
			foreach($patchList as $devid=>$devRow){
				$selected=($_POST['pdev']==$devid)?" disabled":"";
				print "<option value=$devRow->DeviceID$selected>$devRow->Label</option>\n";
			}
			echo '</select>';
			exit;
		}
	}

	// These objects are used no matter what operation we're performing
	$templ=new DeviceTemplate();
	$mfg=new Manufacturer();
	$esc=new Escalations();
	$escTime=new EscalationTimes();
	$contactList=$contact->GetContactList($facDB);
	$Dept=new Department();
	$pwrCords=null;
	$chassis="";
	$copy = false;

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
				$cab->GetCabinet( $facDB );
				
				// If you are adding a device that is assined to a specific customer, assume that device is also owned by that customer
				if ( $cab->AssignedTo > 0 )
					$dev->Owner = $cab->AssignedTo;
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

					if(($dev->TemplateID >0)&&(intval($dev->NominalWatts==0))){$dev->UpdateWattageFromTemplate($facDB);}
			
					$dev->SetTags($tagarray);
					if($dev->Cabinet <0){
						$dev->MoveToStorage($facDB);
					}else{
						$dev->UpdateDevice($facDB);
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
					$dev->CreateDevice($facDB);
					$dev->SetTags($tagarray);
				}elseif($user->DeleteAccess&&($_REQUEST['action']=='Delete')){
					$dev->GetDevice($facDB);
					$dev->DeleteDevice($facDB);
					header('Location: '.redirect("cabnavigator.php?cabinetid=$dev->Cabinet"));
					exit;
				} elseif ( $user->WriteAccess && $_REQUEST["action"] == "Copy" ) {
					$dev->CopyDevice( $facDB );
					$copy = true;
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
			$dev->GetDevice($facDB);

			// Get any tags associated with this device
			$tags=$dev->GetTags();
			if(count($tags>0)){
				// We have some tags so build the javascript elements we need to create the tags themselves
				$taginsert="\t\ttags: {items: ".json_encode($tags)."},\n";
			}

			// Since a device exists we're gonna need some additional info, but only if it's not a copy
			if ( ! $copy ) {
				$pwrConnection=new PowerConnection();
				$pdu=new PowerDistribution();
				$panel=new PowerPanel();
				$networkPatches=new SwitchConnection();
				$patchPanel=new PatchConnection();

				$pwrConnection->DeviceID=($dev->ParentDevice>0)?$dev->ParentDevice:$dev->DeviceID;
				$pwrCords=$pwrConnection->GetConnectionsByDevice($facDB);

				if($dev->DeviceType=='Switch'){
					$networkPatches->SwitchDeviceID=$dev->DeviceID;
					$patchList=$networkPatches->GetSwitchConnections($facDB);
				}elseif($dev->DeviceType=='Patch Panel'){
					$patchPanel->PanelDeviceID=$dev->DeviceID;
					$patchList=$patchPanel->GetPanelConnections($facDB);
				}else{
					$networkPatches->EndpointDeviceID= ( $dev->ParentDevice>0 )?$dev->ParentDevice:$dev->DeviceID;
					$patchList=$networkPatches->GetEndpointConnections($facDB);
					$patchPanel->FrontEndpointDeviceID = ( $dev->ParentDevice>0 )?$dev->ParentDevice:$dev->DeviceID;
					$panelList = $patchPanel->GetEndpointConnections( $facDB );
				}
			}
		}
		$cab->CabinetID=$dev->Cabinet;
	}else{
		// sets install date to today when a new device is being created
		$dev->InstallDate=date("m/d/Y");
	}
	
	if($dev->ParentDevice >0){
		$pDev=new Device();
		$pDev->DeviceID=$dev->ParentDevice;
		$pDev->GetDevice($facDB);
		
		$parentList=$pDev->GetParentDevices($facDB);
		
		$cab->CabinetID=$pDev->Cabinet;
		$cab->GetCabinet($facDB);
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
		$childList=$dev->GetDeviceChildren($facDB);
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


	$title=($dev->Label!='')?"$dev->Label :: $dev->DeviceID":"openDCIM Device Maintenance";
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
  <?php echo $css; ?>
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-migrate-1.0.0.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript" src="scripts/jHtmlArea-0.7.5.min.js"></script>
  <!--[if gt IE 8]>
  <script type="text/javascript" src="scripts/jquery.textext.js"></script>
  <![endif]-->
  <!--[if !IE]> -->
  <script type="text/javascript" src="scripts/jquery.textext.js"></script>
  <!-- <![endif]-->

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
<?php echo '		document.getElementById(\'layout\').innerHTML = "',_("Landscape"),'";
	} else {
		sheet.innerHTML = ".device div.left { display: block; }";
		document.getElementById(\'layout\').innerHTML = "',_("Landscape"),'";'; ?>
	}
	var s = document.getElementsByTagName('style')[0];
	if (s.innerHTML == sheet.innerHTML){
		if (sheet.styleSheet){ //IE
			document.getElementsByTagName('style')[0].styleSheet.cssText = "";
<?php echo '			document.getElementById(\'layout\').innerHTML = "',_("Portrait"),'";
		}else{
			document.getElementsByTagName(\'style\')[0].innerHTML = "";
			document.getElementById(\'layout\').innerHTML = "',_("Portrait"),'";'; ?>
		}
		setCookie("layout","Landscape");
	}else{
		s.parentNode.insertBefore(sheet, s);
		setCookie("layout","Portrait");
	}
}
$(document).ready(function() {
	/**
	 * jQuery.browser.mobile (http://detectmobilebrowser.com/)
	 *
	 * jQuery.browser.mobile will be true if the browser is a mobile device
	 *
	**/
	(function(a){jQuery.browser.mobile=/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|meego.+mobile|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))})(navigator.userAgent||navigator.vendor||window.opera);
	// Mobile devices get a scan barcode button and an accordian interface
	if(jQuery.browser.mobile){
		$('.main button').each(function(){
			if($(this).text()=='Scan Barcode'){
				$(this).css('display', 'inline');
			}
		});
		$('.left > fieldset ~ .table').each(function(){
<?php print "			$(this).before($('<h3><a href=\"#\">"._('Notes')."</a></h3>'));"; ?>
		});
		$('.right').contents().appendTo($('.left'));
<?php print "		$('.left').append('<h3><a href=\"#\">"._('Network & Power')."</a></h3>');"; ?>
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

	$('#notes').each(function(){
		$(this).before('<button type="button" id="editbtn"></button>');
		if($(this).val()!=''){
			rendernotes($('#editbtn'));
		}else{
			editnotes($('#editbtn'));
		}
	});
	function editnotes(button){
		button.val('preview').text('Preview');
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
		button.val('edit').text('Edit');
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
			$('select[name=devicetype]').val(data['DeviceType']);
		});
	});
<?php
	// hide all the js functions if they don't have write permissions
	if($user->WriteAccess){

		// if they switch device type to switch for a child blade add the dataports field
		if($dev->ParentDevice>0){
?>
		$('select[name=devicetype]').change(function(){
<?php echo '		var dphtml=\'<div id="dphtml"><div><label for="ports">',_("Number of Data Ports"),'</label></div><div><input class="optional,validate[custom[onlyNumberSp]]" name="ports" id="ports" size="4" value="" type="number"></div></div>\';'; ?>
			if($(this).val()=='Switch' && $('#dphtml').length==0){
				$('#nominalwatts').parent().parent().before(dphtml);
			}else{
				$('#dphtml').remove();
			}
		});
<?php
		}
		
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
		$('.switch > div:first-child ~ div').each(function(){
			var row=$(this);
			$(this).find('div:first-child').click(function(){
				$(this).css({'min-width': '35px','width': '35px'});
				var swdev=$('#deviceid').val();
				var sp=$(this).text();
				if($(this).attr('edit')=='yes'){

				}else{
					$(this).attr('edit','yes');
					var device=$(this).next();
					var devid=device.attr('alt');
					var devport=device.next();
					devport.css({'min-width': '35px','width': '35px'});
					var devportwidth=devport.width();
					var notes=devport.next();
					$.ajax({
						type: 'POST',
						url: 'devices.php',
						data: 'swdev='+swdev+'&sp='+sp,
						success: function(data){
							var height=row.height();
							device.html(data).css({'width': device.children('select').width()+'px', 'padding': '0px'});
							device.children('select').css({'background-color': 'transparent'});
							devport.html('<input type="text" name="dp" value="'+devport.text()+'">').css({'width': devportwidth+'px', 'padding': '0px'}).children('input').css({'width': devportwidth+'px', 'background-color': 'transparent'});
							notes.html('<input type="text" name="n" value="'+notes.text()+'">').css({'width': notes.width()+'px', 'padding': '0px'}).children('input').css({'width': notes.width()+'px', 'background-color': 'transparent'});
<?php echo '							row.append(\'<div style="padding: 0px;"><button type="button" value="save">',_("Save"),'</button><button type="button" value="delete">',_("Delete"),'</button><button type="button" value="cancel">',_("Cancel"),'</button></div>\');'; ?>
							row.find('div > button').css({'height': height+'px', 'line-height': '1'});
							var buttonwidth=15;
							row.find('div > button').each(function(){
								buttonwidth+=$(this).outerWidth();
								var a=device.find('select');
								var b=devport.find('input');
								var c=notes.find('input');
								if($(this).val()=="delete"){
									$(this).click(function(e){
										a.val("");
										b.val("");
										c.val("");
										row.find('div > button[value="save"]').focus();
										row.find('div > button[value="save"]').click();
									});
								}else if($(this).val()=="cancel"){
									$(this).click(function(){
										a.val(device.attr('alt'));
										b.val(devport.attr('data'));
										c.val(notes.attr('data'));
										row.find('div > button[value="save"]').focus();
										row.find('div > button[value="save"]').click();
									});
								}else if($(this).val()=="save"){
									$(this).click(function(){
										$.ajax({
											type: 'POST',
											url: 'devices.php',
											data: 'swdev='+swdev+'&sp='+sp+'&devid='+a.val()+'&dp='+b.val()+'&n='+c.val(),
											success: function(data){
												if(data.trim()=="-1"){
													//error
													device.css('background-color', 'salmon');
													devport.css('background-color', 'salmon');
													notes.css('background-color', 'salmon');
													setTimeout(function() {
														device.css('background-color', 'white');
														devport.css('background-color', 'white');
														notes.css('background-color', 'white');
													},1500);
												}else if(data.trim()=="1"){
													row.find('div:first-child').removeAttr('edit').css('width','auto');
													// set the fields back to table cells
													row.find('div:last-child').remove();
													if(a.val()!='-1'){
														device.html('<a href="devices.php?deviceid='+a.val()+'">'+a.find('option:selected').text()+'</a>').removeAttr('style');
													}else{
														device.html('').removeAttr('style');
													}
													devport.html(b.val()).removeAttr('style');
													notes.html(c.val()).removeAttr('style');
													device.css('background-color', 'lightgreen');
													devport.css('background-color', 'lightgreen');
													notes.css('background-color', 'lightgreen');
													setTimeout(function() {
														device.css('background-color', 'white');
														devport.css('background-color', 'white');
														notes.css('background-color', 'white');
													},1500);
												}else{
													// something unexpected has happened
												}
											}
										});
									});
								}
								$(this).parent('div').css({'width': buttonwidth+'px', 'text-align': 'center'});
							});
							$('div.page').css('width', ($('#deviceform').outerWidth(true)+$('#sidebar').outerWidth(true)+22)+'px');
						}
					});
				}
			}).css({'cursor': 'pointer','text-decoration': 'underline'});
		});
		$('.patchpanel > div:first-child ~ div').each(function(){
			var row=$(this);
			$(this).click(function(){
				var frontdev=row.find('div:first-child');
				var frontport=row.find('div:nth-child(2)');
				var frontnotes=row.find('div:nth-child(3)');
				var patchport=row.find('div:nth-child(4)');
				var reardev=row.find('div:nth-child(5)');
				var rearport=row.find('div:nth-child(6)');
				var rearnotes=row.find('div:nth-child(7)');
				if($(this).attr('edit')=='yes'){

				}else{
					// create empty row below the current
					$(this).after('<div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>');
					var btnrow=$(this).next(); // name it for easy reference
					var frontbtn=btnrow.find('div:first-child'); // front table cell for buttons
					var rearbtn=btnrow.find('div:nth-child(5)'); // rear table cell for buttons
					$(this).attr('edit','yes');
					function fixwidth(test){
						setTimeout(function() {
							$('.page').width($('.main').outerWidth()+$('#sidebar').outerWidth()+50);
						},1000);
					}
<?php
		if($user->SiteAdmin){
?>
// Rear panel controls
<?php echo '							rearbtn.append(\'<div style="padding: 0px; border: 0px;"><button type="button" value="save">',_("Save"),'</button><button type="button" value="delete">',_("Delete"),'</button><button type="button" value="cancel">',_("Cancel"),'</button></div>\');'; ?>
					rearbtn.css({'padding': 0, 'border': 0}).attr('data', 'rear');;
					$.post('', {pdev: $('#deviceid').val()}, function(data){
						var rdev=reardev.text();
						reardev.html(data).css({'padding': 0});
						reardev.find('select option').each(function(){
							if($(this).text()==rdev){
								$(this).attr('selected','selected');
							}else if(rearnotes.text()!='' && rdev==''){
								$(this).parent('select').val('note');
							}
						});					
						rearport.html('<input type="text" value="'+rearport.text()+'">').css({'padding': 0});
						rearnotes.html('<input type="text" value="'+rearnotes.text()+'">').css({'padding': 0}); // weird data will break the crap out of this.  fix later.
					}).then(fixwidth());
<?php
		}
?>
// Front panel controls
<?php echo '							frontbtn.append(\'<div style="padding: 0px; border: 0px;"><button type="button" value="save">',_("Save"),'</button><button type="button" value="delete">',_("Delete"),'</button><button type="button" value="cancel">',_("Cancel"),'</button></div>\');'; ?>
					frontbtn.css({'padding': 0, 'border': 0}).attr('data', 'front');
					$.post('', {sp: '0', swdev: $('#deviceid').val()}, function(data){
						var fdev=frontdev.text();
						frontdev.html(data).css({'padding': 0});
						frontdev.find('select option').each(function(){
							if($(this).text()==fdev){
								$(this).attr('selected','selected');
							}
						});					
						frontport.html('<input type="text" value="'+frontport.text()+'">').css({'padding': 0});
						frontnotes.html('<input type="text" value="'+frontnotes.text()+'">').css({'padding': 0});
					}).then(fixwidth());
	// remove new row and set crap back to normal.
					function btncleanup(e){
						if(frontbtn.text()=='' && rearbtn.text()==''){ btnrow.remove(); row.removeAttr('edit');	}
					}
	// button functions
					btnrow.find('div > button').each(function(){
						var buttondiv=$(this).parent('div');
						var side=buttondiv.parent('div').attr('data');
						if($(this).val()=="delete"){
							$(this).click(function(){
								$.post('', {pdev: $('#deviceid').val(), pdel: patchport.text(), side: side}, function(data){
									if(data!='1'){
										alert('error, error, error');
									}else{
										buttondiv.remove();
										if(side=='front'){
											frontdev.html('');
											frontport.html('');
											frontnotes.html('');
										}else{
											reardev.html('');
											rearport.html('');
											rearnotes.html('');
										}
										btncleanup();
									}
								});
							});
						}else if($(this).val()=="cancel"){
							// pull record from db and set back to original values
							$(this).click(function(){
								$.post('', {pdev: $('#deviceid').val(), pget: patchport.text()}, function(data){
									var darray=$.parseJSON(data);
									if(side=='front'){
										frontdev.html((darray[1]!='NULL')?darray[1]:'');
										frontport.html((darray[2]!='NULL')?darray[2]:'');
										frontnotes.html((darray[3]!='NULL')?darray[3]:'');
									}else{
										reardev.html((darray[5]!='NULL')?darray[5]:'');
										rearport.html((darray[6]!='NULL')?darray[6]:'');
										rearnotes.html((darray[7]!='NULL')?darray[7]:'');
									}
									buttondiv.remove();
									btncleanup();
								});
							});
						}else if($(this).val()=="save"){
							$(this).click(function(){
								if(side=='front'){
									$.post('', {pdev: $('#deviceid').val(), psav: patchport.text(), fdev:frontdev.find('select').val(), fport:frontport.find('input').val(), fn:frontnotes.find('input').val()}, function(data){
										var darray=$.parseJSON(data);
										frontdev.html(darray[1]);
										frontport.html(darray[2]);
										frontnotes.html(darray[3]);
									});
								}else{
									$.post('', {pdev: $('#deviceid').val(), psav: patchport.text(), rdev:reardev.find('select').val(), rport:rearport.find('input').val(), rn:rearnotes.find('input').val()}, function(data){
										var darray=$.parseJSON(data);
										reardev.html(darray[5]);
										rearport.html(darray[6]);
										rearnotes.html(darray[7]);
									});
								}
								buttondiv.remove();
								btncleanup();
							});
						}
					});
				}
			});
		}).css({'cursor': 'pointer','text-decoration': 'underline'});

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
<button id="layout" onClick="swaplayout()">'._("Portrait").'</button>
<h2>'.$config->ParameterArray['OrgName'].'</h2>
<h3>'._("Data Center Device Detail").'</h3>';
echo($copy)?'<h3>'._("This device is a copy of an existing device.  Remember to set the new location before saving.").'</h3>':'';
echo '<div class="center"><div>
<div id="positionselector"></div>
<form name="deviceform" id="deviceform" action="'.$_SERVER['PHP_SELF'].((isset($dev->DeviceID) && $dev->DeviceID>0)?"?deviceid=$dev->DeviceID":"").'" method="POST">
<div class="left">
<fieldset>
	<legend>'._("Asset Tracking").'</legend>
	<div class="table">
		<div>
		   <div>'._("Device ID").'</div>
		   <div><input type="text" name="deviceid" id="deviceid" value="'.$dev->DeviceID.'" size="6" readonly></div>
		</div>
		<div>
			<div><label for="reservation">'._("Reservation?").'</label></div>
			<div><input type="checkbox" name="reservation" id="reservation"'.((($dev->Reservation) || $copy )?" checked":"").'></div>
		</div>
		<div>
		   <div><label for="label">'._("Label").'</label></div>
		   <div><input type="text" class="validate[required,minSize[3],maxSize[50]]" name="label" id="label" size="40" value="'.$dev->Label.'"></div>
		</div>
		<div>
		   <div><label for="serialno">'._("Serial Number").'</label></div>
		   <div><input type="text" name="serialno" id="serialno" size="40" value="'.$dev->SerialNo.'">
		   <button class="hide" type="button" onclick="getScan(\'serialno\')">',_("Scan Barcode"),'</button></div>
		</div>
		<div>
		   <div><label for="assettag">'._("Asset Tag").'</label></div>
		   <div><input type="text" name="assettag" id="assettag" size="20" value="'.$dev->AssetTag.'">
		   <button class="hide" type="button" onclick="getScan(\'assettag\')">',_("Scan Barcode"),'</button></div>
		</div>
		<div>
		   <div><label for="mfgdate">'._("Manufacture Date").'</label></div>
		   <div><input type="text" class="validate[optional,custom[date]] datepicker" name="mfgdate" id="mfgdate" value="'.(($dev->MfgDate>'0000-00-00 00:00:00')?date('m/d/Y',strtotime($dev->MfgDate)):"").'">
		   </div>
		</div>
		<div>
		   <div><label for="installdate">'._("Install Date").'</label></div>
		   <div><input type="text" class="validate[required,custom[date]] datepicker" name="installdate" id="installdate" value="'.(($dev->InstallDate>'0000-00-00 00:00:00')?date('m/d/Y',strtotime($dev->InstallDate)):"").'"></div>
		</div>
		<div>
		   <div><label for="warrantyco">'._("Warranty Company").'</label></div>
		   <div><input type="text" name="warrantyco" id="warrantyco" value="'.$dev->WarrantyCo.'"></div>
		</div>
		<div>
		   <div><label for="installdate">'._("Warranty Expiration").'</label></div>
		   <div><input type="text" class="validate[custom[date]] datepicker" name="warrantyexpire" id="warrantyexpire" value="'.date('m/d/Y',strtotime($dev->WarrantyExpire)).'"></div>
		</div>		
		<div>
		   <div><label for="owner">'._("Departmental Owner").'</label></div>
		   <div>
			<select name="owner" id="owner">
				<option value=0>'._("Unassigned").'</option>';

			foreach($deptList as $deptRow){
				if($dev->Owner==$deptRow->DeptID){$selected=" selected";}else{$selected="";}
				print "\t\t\t\t<option value=\"$deptRow->DeptID\"$selected>$deptRow->Name</option>\n";
			}

echo '			</select>
			<button type="button">',_("Show Contacts"),'</button>
		   </div>
		</div>
		<div>
		   <div>&nbsp;</div>
		   <div><fieldset>
		   <legend>',_("Escalation Information"),'</legend>
		   <div class="table">
			<div>
				<div><label for="escaltationtimeid">',_("Time Period"),'</label></div>
				<div><select name="escalationtimeid" id="escalationtimeid">
					<option value="">',_("Select..."),'</option>';

				foreach($escTimeList as $escTime){
					if($escTime->EscalationTimeID==$dev->EscalationTimeID){$selected=" selected";}else{$selected="";}
					print "\t\t\t\t\t<option value=\"$escTime->EscalationTimeID\"$selected>$escTime->TimePeriod</option>\n";
				}

echo '				</select></div>
			</div>
			<div>
				<div><label for="escalationid">',_("Details"),'</label></div>
				<div><select name="escalationid" id="escalationid">
					<option value="">',_("Select..."),'</option>';

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
		   <div><label for="primarycontact">',_("Primary Contact"),'</label></div>
		   <div><select name="primarycontact" id="primarycontact">
				<option value=0>',_("Unassigned"),'</option>';

			foreach($contactList as $contactRow){
				if($contactRow->ContactID==$dev->PrimaryContact){$contactUserID=$contactRow->UserID;$selected=" selected";}else{$selected="";}
				print "\t\t\t\t<option value=\"$contactRow->ContactID\"$selected>$contactRow->LastName, $contactRow->FirstName</option>\n";
			}
			
			print "\t\t\t</select>\n";

			if(isset($config->ParameterArray['UserLookupURL']) && isValidURL($config->ParameterArray['UserLookupURL']) && isset($contactUserID)){
				print "<button type=\"button\" onclick=\"window.open( '".$config->ParameterArray["UserLookupURL"]."$contactUserID', 'UserLookup')\">"._('Contact Lookup')."</button>\n";
			}

echo '		   </div>
		</div>
		<div>
			<div><label for="tags">',_("Tags"),'</label></div>
			<div><textarea type="text" name="tags" id="tags" rows="1"></textarea></div>
		</div>
	</div> <!-- END div.table -->
</fieldset>	
	<div class="table">
		<div>
		  <div><label for="notes">',_("Notes"),'</label></div>
		  <div><textarea name="notes" id="notes" cols="40" rows="8">',$dev->Notes,'</textarea></div>
		</div>
	</div> <!-- END div.table -->
</div><!-- END div.left -->
<div class="right">
<fieldset>
	<legend>',_("Physical Infrastructure"),'</legend>
	<div class="table">
		<div>
			<div><label for="cabinet">',_("Cabinet"),'</label></div>';

	if($dev->ParentDevice==0){
		print "\t\t\t<div>".$cab->GetCabinetSelectList($facDB)."</div>\n";
		}else{
			print "\t\t\t<div>$cab->Location<input type=\"hidden\" name=\"cabinetid\" value=\"0\"></div>
		</div>
		<div>
			<div><label for=\"parentdevice\">"._('Parent Device')."</label></div>
			<div><select name=\"parentdevice\">\n";
			
			foreach($parentList as $parDev){
				if($pDev->DeviceID==$parDev->DeviceID){$selected=" selected";}else{$selected="";}
				print "\t\t\t\t<option value=\"$parDev->DeviceID\"$selected>$parDev->Label</option>\n";
			}
			print "\t\t\t</select></div>\n";
		}

echo '		</div>
		<div>
			<div><label for="templateid">',_("Device Class"),'</label></div>
			<div><select name="templateid" id="templateid">
				<option value=0>',_("Select a template..."),'</option>';

			foreach($templateList as $tempRow){
				if($dev->TemplateID==$tempRow->TemplateID){$selected=" selected";}else{$selected="";}
				$mfg->ManufacturerID=$tempRow->ManufacturerID;
				$mfg->GetManufacturerByID($facDB);
				print "\t\t\t\t<option value=\"$tempRow->TemplateID\"$selected>$mfg->Name - $tempRow->Model</option>\n";
			}

echo '			</select>
			</div>
		</div>
		<div>
		   <div><label for="position">',_("Position"),'</label></div>
		   <div><input type="number" class="required,validate[custom[onlyNumberSp],min[1]]" name="position" id="position" size="4" value="',$dev->Position,'"></div>
		</div>
		<div>
		   <div><label for="height">',_("Height"),'</label></div>
		   <div><input type="number" class="required,validate[custom[onlyNumberSp]]" name="height" id="height" size="4" value="',$dev->Height,'"></div>
		</div>';

		// Blade devices don't have data ports unless they're a switch
		if($dev->ParentDevice==0||($dev->ParentDevice>0&&$dev->DeviceType=='Switch')){
			echo '		<div id="dphtml">
		   <div><label for="ports">',_("Number of Data Ports"),'</label></div>
		   <div><input type="number" class="optional,validate[custom[onlyNumberSp]]" name="ports" id="ports" size="4" value="',$dev->Ports,'"></div>
		</div>';
		}

echo '		<div>
		   <div><label for="nominalwatts">',_("Nominal Draw (Watts)"),'</label></div>
		   <div><input type="text" class="optional,validate[custom[onlyNumberSp]]" name="nominalwatts" id="nominalwatts" size=6 value="',$dev->NominalWatts,'"></div>
		</div>';

		// Blade devices don't have power supplies but they do have a front or back designation
		if($dev->ParentDevice==0){
			echo '		<div>
		   <div><label for="powersupplycount">',_("Number of Power Supplies"),'</label></div>
		   <div><input type="number" class="optional,validate[custom[onlyNumberSp]]" name="powersupplycount" id="powersupplycount" size=4 value="',$dev->PowerSupplyCount,'"></div>
		</div>';
		}else{
			echo '		<div>
			<div><label for="powersupplycount">',_("Front / Rear"),'</label></div>
			<div><select id="chassisslots" name="chassisslots">
		   		<option value=0'.(($dev->ChassisSlots==0)?' selected':'').'>',_("Front"),'</option>
				<option value=1'.(($dev->ChassisSlots==1)?' selected':'').'>',_("Rear"),'</option>
			</select></div>
		</div>';
		}

echo '		<div>
		   <div>',_("Device Type"),'</div>
		   <div><select name="devicetype">
			<option value=0>',_("Select..."),'</option>';

		// We don't want someone accidentally adding a chassis device inside of a chassis slot.
		if($dev->ParentDevice>0){
			$devarray=array('Server' => _("Server"),
							'Appliance' => _("Appliance"),
							'Storage Array' => _("Storage Array"),
							'Switch' => _("Switch"));
		}else{
			$devarray=array('Server' => _("Server"),
							'Appliance' => _("Appliance"),
							'Storage Array' => _("Storage Array"),
							'Switch' => _("Switch"),
							'Chassis' => _("Chassis"),
							'Patch Panel' => _("Patch Panel"),
							'Physical Infrastructure' => _("Physical Infrastructure"));
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
<?php
	//
	// Do not display the chassis contents block if this is a child device (ParentDevice > 0)
	//
	if($dev->DeviceType=='Chassis'){

echo '<fieldset class="chassis">
	<legend>',_("Chassis Contents"),'</legend>
	<div class="table">
		<div>
			<div>&nbsp;</div>
			<div>',_("Front"),'</div>
			<div class="greybg">',_("Rear"),'</div>
		</div>
		<div>
			<div><label for="chassisslots">',_("Number of Slots in Chassis:"),'</label></div>
			<div><input type="text" id="chassisslots" class="optional,validate[custom[onlyNumberSp]]" name="chassisslots" size="4" value="',$dev->ChassisSlots,'"></div>
			<div class="greybg"><input type="text" id="rearchassisslots" class="optional,validate[custom[onlyNumberSp]]" name="rearchassisslots" size="4" value="',$dev->RearChassisSlots,'"></div>
		</div>
	</div>
	<div class="table">
		<div>
			<div>',_("Slot #"),'</div>
			<div>',_("Height"),'</div>
			<div>',_("Device Name"),'</div>
			<div>',_("Device Type"),'</div>
		</div>';

	foreach($childList as $chDev){
		print "\t\t<div".(($chDev->ChassisSlots)?' class="greybg"':'').">
			<div>$chDev->Position</div>
			<div>$chDev->Height</div>
			<div><a href=\"devices.php?deviceid=$chDev->DeviceID\">$chDev->Label</a></div>
			<div>$chDev->DeviceType</div>
		</div>\n";
	}
	
	if($dev->ChassisSlots >0){

echo '		<div class="caption">
			<button type="submit" id="adddevice" value="child" name="action">',_("Add Device"),'</button>
			<input type="hidden" id="parentdevice" name="parentdevice" disabled value="',$dev->DeviceID,'">
		</div>';
	}
?>
	</div>
</fieldset>
<?php
	}
	
	// Do not display ESX block if device isn't a virtual server and the user doesn't have write access
	if(($user->WriteAccess || $dev->ESX) && ($dev->DeviceType=="Server" || $dev->DeviceType=="")){
		echo '<fieldset>	<legend>',_("VMWare ESX Server Information"),'</legend>';
	// If the user doesn't have write access display the list of VMs but not the configuration information.
		if($user->WriteAccess){

echo '	<div class="table">
		<div>
		   <div><label for="esx">'._("ESX Server?").'</label></div>
		   <div><select name="esx" id="esx"><option value="1"'.(($dev->ESX==1)?" selected":"").'>'._("True").'</option><option value="0"'.(($dev->ESX==0)?" selected":"").'>'._("False").'</option></select></div>
		</div>
		<div>
		  <div><label for="primaryip">'._("Primary IP").'</label></div>
		  <div><input type="text" name="primaryip" id="primaryip" size="20" value="'.$dev->PrimaryIP.'"></div>
		</div>
		<div>
		  <div><label for="snmpcommunity">'._("SNMP Read Only Community").'</label></div>
		  <div><input type="text" name="snmpcommunity" id="snmpcommunity" size="40" value="'.$dev->SNMPCommunity.'"></div>
		</div>
	</div><!-- END div.table -->';

		}
		if($dev->ESX){
			$esx=new ESX();
			$esx->DeviceID=$dev->DeviceID;
			$vmList=$esx->GetDeviceInventory($facDB);
    
			print "\n<div class=\"table border\"><div><div>"._('VM Name')."</div><div>"._('Status')."</div><div>"._('Owner')."</div><div>"._('Last Updated')."</div></div>\n";
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
					$Dept->Name=_('Unknown');
				}
				print "<div><div>$vmRow->vmName</div><div><font color=$statColor>$vmRow->vmState</font></div><div><a href=\"updatevmowner.php?vmindex=$vmRow->VMIndex\">$Dept->Name</a></div><div>$vmRow->LastUpdated</div></div>\n";
			}
			echo '</div> <!-- END div.table -->';
		}
		print "</fieldset>\n";
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
			echo '	<div>		<div><a name="power"></a></div>		<div>',_("No power connections defined.  You can add connections from the power strip screen."),'</div></div><div><div>&nbsp;</div><div></div></div>';
		}else{
			print "		<div>\n		  <div><a name=\"power\">$chassis "._('Power Connections')."</a></div>\n		  <div><div class=\"table border\">\n			<div><div>"._('Panel')."</div><div>"._('Power Strip')."</div><div>"._('Plug #')."</div><div>"._('Power Supply')."</div></div>";
			foreach($pwrCords as $cord){
				$pdu->PDUID=$cord->PDUID;
				$pdu->GetPDU($facDB);
				$panel->PanelID=$pdu->PanelID;
				$panel->GetPanel($facDB);
				print "			<div><div><a href=\"power_panel.php?panelid=$pdu->PanelID\">$panel->PanelLabel</a></div><div><a href=\"power_pdu.php?pduid=$pdu->PDUID\">$pdu->Label</a></div><div>$cord->PDUPosition</div><div>$cord->DeviceConnNumber</div></div>\n";
			}
			print "			</div><!-- END div.table --></div>\n		</div>\n		<div>\n			<div>&nbsp;</div><div></div>\n		</div>\n";
		}
	}

	// If device is s switch or appliance show what the heck it is connected to.
	if($dev->DeviceType=='Server' || $dev->DeviceType=='Appliance' || $dev->DeviceType=='Chassis'){
		if((count($patchList)+count($panelList))==0){
			// We have no network information. Display links to switches in cabinet?
			echo '		<div>		<div><a name="power"></a></div>		<div>',("No network connections defined.  You can add connections from a switch device."),'</div></div>';
		}else{
			print "		<div>\n		  <div><a name=\"net\">$chassis "._('Connections')."</a><br>("._('Managed at Switch').")</div>\n		  <div><div class=\"table border\"><div><div>"._('Switch')."</div><div>"._('Switch Port')."</div><div>"._('Device Port')."</div><div>"._('Notes')."</div></div>\n";
			$tmpDev=new Device();
			foreach($patchList as $patchConn){
				$tmpDev->DeviceID=$patchConn->SwitchDeviceID;
				$tmpDev->GetDevice($facDB);
				print "			<div><div><a href=\"devices.php?deviceid=$patchConn->SwitchDeviceID#net\">$tmpDev->Label</a></div><div>$patchConn->SwitchPortNumber</div><div>$patchConn->EndpointPort</div><div>$patchConn->Notes</div></div>\n";
			}
			print "			</div><!-- END div.table -->\n		  </div>\n		</div>\n";

			if(count($panelList)>0){
				print "\t\t<div>\n\t\t\t<div>&nbsp;</div><div></div>\n\t\t</div>\n";				
				print "\t\t<div>\n\t\t\t<div><a name=\"net\">$chassis "._('Patches')."</a><br>("._('Managed at Panel').")</div>\n\t\t\t<div>\n\t\t\t\t<div class=\"table border\">\n\t\t\t\t\t<div><div>"._('Panel')."</div><div>"._('Panel Port')."</div><div>"._('Device Port')."</div><div>"._('Notes')."</div></div>\n";
				
				if(is_array($panelList)){
					foreach($panelList as $panelConn){
						$tmpDev->DeviceID=$panelConn->PanelDeviceID;
						$tmpDev->GetDevice($facDB);
						print "\t\t\t\t\t<div><div><a href=\"devices.php?deviceid=$panelConn->PanelDeviceID#net\">$tmpDev->Label</a></div><div>$panelConn->PanelPortNumber</div><div>$panelConn->FrontEndpointPort</div><div>$panelConn->FrontNotes</div></div>\n";
					}
					print "\t\t\t\t</div><!-- END div.table -->\n\t\t\t</div>\n\t\t</div>\n";
				}
			}
		}      
	}
		  
	if($dev->DeviceType=='Switch'){
		print "		<div>\n		  <div><a name=\"net\">"._('Connections')."</a></div>\n		  <div>\n			<div class=\"table border switch\">\n				<div><div>"._('Switch Port')."</div><div>"._('Device')."</div><div>"._('Device Port')."</div><div>"._('Notes')."</div></div>\n";
		if(sizeof($patchList) >0){
			foreach($patchList as $patchConn){
				$tmpDev=new Device();
				$tmpDev->DeviceID=$patchConn->EndpointDeviceID;
				$tmpDev->GetDevice($facDB);
				
				print "\t\t\t\t<div><div>$patchConn->SwitchPortNumber</div><div alt=\"$patchConn->EndpointDeviceID\"><a href=\"devices.php?deviceid=$patchConn->EndpointDeviceID\">$tmpDev->Label</a></div><div data=\"$patchConn->EndpointPort\">$patchConn->EndpointPort</div><div data=\"$patchConn->Notes\">$patchConn->Notes</div></div>\n";
			}
		}      
		echo "			</div><!-- END div.table -->\n		  </div>\n		</div>";
	}

	if($dev->DeviceType=='Patch Panel'){
		print "\n\t<div>\n\t\t<div><a name=\"net\">"._('Connections')."</a></div>\n\t\t<div>\n\t\t\t<div class=\"table border patchpanel\">\n\t\t\t\t<div><div>"._('Front')."</div><div>Device Port</div><div>"._('Notes')."</div><div>"._('Patch Port')."</div><div>"._('Back')."</div><div>Device Port</div><div>"._('Notes')."</div></div>\n";
		if(sizeof($patchList) >0){
			foreach($patchList as $patchConn){
				$frontDev=new Device();
				$rearDev=new Device();
				$frontDev->DeviceID=$patchConn->FrontEndpointDeviceID;
				$rearDev->DeviceID=$patchConn->RearEndpointDeviceID;
				$frontDev->GetDevice($facDB);
				$rearDev->GetDevice($facDB);
				print "\n\t\t\t\t<div><div>$frontDev->Label</div><div>$patchConn->FrontEndpointPort</div><div>$patchConn->FrontNotes</div><div>$patchConn->PanelPortNumber</div><div>$rearDev->Label</div><div>$patchConn->RearEndpointPort</div><div>".htmlentities($patchConn->RearNotes)."</div></div>";
			}
		}
		print "\t\t\t</div><!-- END div.table -->\n\t\t</div>\n\t</div>\n";
	}
?>
		<div class="caption">
<?php
	if($user->WriteAccess){
		if($dev->DeviceID >0){
			echo '			<button type="submit" name="action" value="Update">',_("Update"),'</button>
			<button type="submit" name="action" value="Copy">', _("Copy"), '</button>';
		} else {
			echo '			<button type="submit" name="action" value="Create">',_("Create"),'</button>';
		}
	}
	// Delete rights are seperate from write rights
	if($user->DeleteAccess && $dev->DeviceID >0){
		echo '		<button type="button" name="action" value="Delete">',_("Delete"),'</button>';
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
		print "   <a href=\"devices.php?deviceid=$pDev->DeviceID\">[ "._('Return to Parent Device')." ]</a>\n";
	}elseif($dev->Cabinet >0){
		print "   <a href=\"cabnavigator.php?cabinetid=$cab->CabinetID\">[ "._('Return to Navigator')." ]</a>";
	}else{
		echo '   <div><a href="storageroom.php">[ ',_("Return to Navigator"),' ]</a></div>';
	}
?>
<div id="dialog-confirm" title="Verify Delete Device" class="hide">
	<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>This device will be deleted and there is no undo. Are you sure?</p>
</div>

</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
	$(document).ready(function() {
		// wait half a second after the page loads then open the tree
		setTimeout(function(){
			expandToItem('datacenters','cab<?php echo $cab->CabinetID;?>');
		},500);
		$('button[value="Delete"]').click(function(e){
			var form=$(this).parents('form');
			var btn=$(this);
			$( "#dialog-confirm" ).dialog({
				resizable: false,
				modal: true,
				buttons: {
					"Yes": function() {
						$( this ).dialog( "close" );
						form.append('<input type="hidden" name="'+btn.attr("name")+'" value="'+btn.val()+'">');
						form.submit();
					},
					"No": function() {
						$( this ).dialog( "close" );
					}
				}
			});
		});
	});
</script>

</body>
</html>
