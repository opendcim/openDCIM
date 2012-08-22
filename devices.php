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
	$chassis="";

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
					$dev->TemplateID=$_REQUEST['templateid'];
					$dev->DeviceType=$_REQUEST['devicetype'];
					$dev->MfgDate=date('Y-m-d',strtotime($_REQUEST['mfgdate']));
					$dev->InstallDate=date('Y-m-d',strtotime($_REQUEST['installdate']));
					$dev->WarrantyCo=$_REQUEST['warrantyco'];
					$dev->WarrantyExpire=date('Y-m-d',strtotime($_REQUEST['warrantyexpire']));
					$dev->Notes=$_REQUEST['notes'];
					// All of the values below here are optional based on the type of device being dealt with
					$dev->ChassisSlots=(isset($_REQUEST['chassisslots']))?$_REQUEST['chassisslots']:0;
					$dev->RearChassisSlots=(isset($_REQUEST['rearchassisslots']))?$_REQUEST['rearchassisslots']:0;
					$dev->Ports=(isset($_REQUEST['ports']))?$_REQUEST['ports']:"";
					$dev->PowerSupplyCount=(isset($_REQUEST['powersupplycount']))?$_REQUEST['powersupplycount']:"";
					$dev->ParentDevice=(isset($_REQUEST['parentdevice']))?$_REQUEST['parentdevice']:"";
					$dev->PrimaryIP=(isset($_REQUEST['primaryip']))?$_REQUEST['primaryip']:"";
					$dev->SNMPCommunity=(isset($_REQUEST['snmpcommunity']))?$_REQUEST['snmpcommunity']:"";
					$dev->ESX=(isset($_REQUEST['esx']))?1:0;
					$dev->Reservation=(isset($_REQUEST['reservation']))?1:0;
					$dev->NominalWatts=$_REQUEST['nominalwatts'];

					if(($dev->TemplateID >0)&&(intval($dev->NominalWatts==0))){$dev->UpdateWattageFromTemplate($facDB);}
			
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
					$dev->DeviceType=$_REQUEST['devicetype'];
					$dev->MfgDate=date('Y-m-d',strtotime($_REQUEST['mfgdate']));
					$dev->InstallDate=date('Y-m-d',strtotime($_REQUEST['installdate']));
					$dev->WarrantyCo=$_REQUEST['warrantyco'];
					$dev->WarrantyExpire=date('Y-m-d',strtotime($_REQUEST['warrantyexpire']));
					$dev->Notes=$_REQUEST['notes'];
					// All of the values below here are optional based on the type of device being dealt with
					$dev->ChassisSlots=(isset($_REQUEST['chassisslots']))?$_REQUEST['chassisslots']:0;
					$dev->RearChassisSlots=(isset($_REQUEST['rearchassisslots']))?$_REQUEST['rearchassisslots']:0;
					$dev->Ports=(isset($_REQUEST['ports']))?$_REQUEST['ports']:"";
					$dev->PowerSupplyCount=(isset($_REQUEST['powersupplycount']))?$_REQUEST['powersupplycount']:"";
					$dev->ParentDevice=(isset($_REQUEST['parentdevice']))?$_REQUEST['parentdevice']:"";
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
<!doctype html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
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
	 * jQuery.browser.mobile will be true if the browser is a mobile device
	 **/
	(function(a){jQuery.browser.mobile=/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))})(navigator.userAgent||navigator.vendor||window.opera);
	if(jQuery.browser.mobile){
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
		$(":input").removeAttr("disabled"); // if they hit back it makes sure the fields aren't disabled
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
});
	
function setPreferredLayout() {<?php if(isset($_COOKIE["layout"]) && strtolower($_COOKIE["layout"])==="portrait"){echo 'swaplayout();setCookie("layout","Portrait");';}else{echo 'setCookie("layout","Landscape");';} ?>}
</script>

</head>
<body onLoad="setPreferredLayout()">
<div id="header"></div>
<div class="page device">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<button id="layout" onClick="swaplayout()">'._("Portrait").'</button>
<h2>'.$config->ParameterArray['OrgName'].'</h2>
<h3>'._("Data Center Device Detail").'</h3>
<div class="center"><div>
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
			<div><input type="checkbox" name="reservation" id="reservation"'.(($dev->Reservation)?" checked":"").'></div>
		</div>
		<div>
		   <div><label for="label">'._("Label").'</label></div>
		   <div><input type="text" class="validate[required,minSize[3],maxSize[50]]" name="label" id="label" size="40" value="'.$dev->Label.'"></div>
		</div>
		<div>
		   <div><label for="serialno">'._("Serial Number").'</label></div>
		   <div><input type="text" name="serialno" id="serialno" size="40" value="'.$dev->SerialNo.'"></div>
		</div>
		<div>
		   <div><label for="assettag">'._("Asset Tag").'</label></div>
		   <div><input type="text" name="assettag" id="assettag" size="20" value="'.$dev->AssetTag.'"></div>
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
			<div><label for="powersupplycount">',_("Front / Back"),'</label></div>
			<div><select id="chassisslots" name="chassisslots">
		   		<option value=0'.(($dev->ChassisSlots==0)?' selected':'').'>',_("Front"),'</option>
				<option value=1'.(($dev->ChassisSlots==1)?' selected':'').'>',_("Back"),'</option>
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
			<div>',_("Rear"),'</div>
		</div>
		<div>
			<div><label for="chassisslots">',_("Number of Slots in Chassis:"),'</label></div>
			<div><input type="text" id="chassisslots" class="optional,validate[custom[onlyNumberSp]]" name="chassisslots" size="4" value="',$dev->ChassisSlots,'"></div>
			<div><input type="text" id="rearchassisslots" class="optional,validate[custom[onlyNumberSp]]" name="rearchassisslots" size="4" value="',$dev->RearChassisSlots,'"></div>
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
		print "\t\t<div>
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
		echo '<fieldset>\n	<legend>',_("VMWare ESX Server Information"),'</legend>';
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
			echo '		<div>		<div><a name="power"></a></div>		<div>',_("No power connections defined.  You can add connections from the power strip screen."),'</div></div><div><div>&nbsp;</div><div></div></div>';
		}else{
			print "		<div>\n		  <div><a name=\"power\">$chassis "._('Power Connections')."</a></div>\n		  <div><div class=\"table border\">\n			<div><div>"._('Panel')."</div><div>"._('Power Strip')."</div><div>"._('Plug #')."</div><div>"._('Power Supply')."</div></div>";
			foreach($pwrCords as $cord){
				$pdu->PDUID=$cord->PDUID;
				$pdu->GetPDU($facDB);
				$panel->PanelID=$pdu->PanelID;
				$panel->GetPanel($facDB);
				print "			<div><div><a href=\"panelmgr.php?panelid=$pdu->PanelID\">$panel->PanelLabel</a></div><div><a href=\"pduinfo.php?pduid=$pdu->PDUID\">$pdu->Label</a></div><div><a href=\"power_connection.php?pdu=$pdu->PDUID&conn=$cord->PDUPosition\">$cord->PDUPosition</a></div><div>$cord->DeviceConnNumber</div></div>\n";
			}
			print "			</div><!-- END div.table --></div>\n		</div>\n		<div>\n			<div>&nbsp;</div><div></div>\n		</div>\n";
		}
	}

	// If device is s switch or appliance show what the heck it is connected to.
	if($dev->DeviceType=='Server' || $dev->DeviceType=='Appliance' || $dev->DeviceType=='Chassis'){
		if(count($patchList)==0){
			// We have no network information. Display links to switches in cabinet?
			echo '		<div>		<div><a name="power"></a></div>		<div>',("No network connections defined.  You can add connections from a switch device."),'</div></div>';
		}else{
			print "		<div>\n		  <div><a name=\"net\">$chassis "._('Connections')."</a><br>("._('Managed at Switch').")</div>\n		  <div><div class=\"table border\"><div><div>"._('Device Port')."</div><div>"._('Switch')."</div><div>"._('Switch Port')."</div><div>"._('Notes')."</div></div>\n";
			$tmpDev = new Device();
			foreach($patchList as $patchConn){
				$tmpDev->DeviceID = $patchConn->SwitchDeviceID;
				$tmpDev->GetDevice( $facDB );
				print "			<div><div>$patchConn->EndpointPort</div><div><a href=\"devices.php?deviceid=$patchConn->SwitchDeviceID#net\">$tmpDev->Label</a></div><div><a href=\"changepatch.php?switchid=$patchConn->SwitchDeviceID&portid=$patchConn->SwitchPortNumber\">$patchConn->SwitchPortNumber</a></div><div>$patchConn->Notes</div></div>\n";
			}
			print "			</div><!-- END div.table -->\n		  </div>\n		</div>\n";
		}      
	}
		  
	if($dev->DeviceType=='Switch'){
		print "		<div>\n		  <div><a name=\"net\">"._('Connections')."</a></div>\n		  <div>\n			<div class=\"table border\">\n				<div><div>"._('Switch Port')."</div><div>"._('Device')."</div><div>"._('Device Port')."</div><div>"._('Notes')."</div></div>\n";
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
			echo '		  <button type="submit" name="action" value="Update">',_("Update"),'</button>';
		}else{
			echo '		  <button type="submit" name="action" value="Create">',_("Create"),'</button>';
		}
	}
	// Delete rights are seperate from write rights
	if($user->DeleteAccess && $dev->DeviceID >0){
		echo '		  <button type="submit" name="action" value="Delete">',_("Delete"),'</button>';
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
