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
					$dev->TemplateID=$_REQUEST['templateid'];
					$dev->DeviceType=$_REQUEST['devicetype'];
					$dev->ChassisSlots=$_REQUEST['chassisslots'];
					$dev->MfgDate=date('Y-m-d',strtotime($_REQUEST['mfgdate']));
					$dev->InstallDate=date('Y-m-d',strtotime($_REQUEST['installdate']));
					$dev->WarrantyCo=$_REQUEST['warrantyco'];
					$dev->WarrantyExpire=date('Y-m-d',strtotime($_REQUEST['warrantyexpire']));
					$dev->Notes=$_REQUEST['notes'];
					// All of the values below here are optional based on the type of device being dealt with
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
					$dev->ChassisSlots=$_REQUEST['chassisslots'];
					$dev->MfgDate=date('Y-m-d',strtotime($_REQUEST['mfgdate']));
					$dev->InstallDate=date('Y-m-d',strtotime($_REQUEST['installdate']));
					$dev->WarrantyCo=$_REQUEST['warrantyco'];
					$dev->WarrantyExpire=date('Y-m-d',strtotime($_REQUEST['warrantyexpire']));
					$dev->Notes=$_REQUEST['notes'];
					// All of the values below here are optional based on the type of device being dealt with
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

		// This is a child device and if the action of new is set let's assume the departmental owner, primary contact, etc are the same as the parent
		if(isset($_REQUEST['action'])&&$_REQUEST['action']=='child'){
			$dev->Owner=$pDev->Owner;
			$dev->EscalationTimeID=$pDev->EscalationTimeID;
			$dev->EscalationID=$pDev->EscalationID;
			$dev->PrimaryContact=$pDev->PrimaryContact;
		}
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
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
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
	/**
	 * jQuery.browser.mobile (http://detectmobilebrowser.com/)
	 * jQuery.browser.mobile will be true if the browser is a mobile device
	 **/
	(function(a){jQuery.browser.mobile=/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))})(navigator.userAgent||navigator.vendor||window.opera);
	if(jQuery.browser.mobile){
		$('.left > fieldset ~ .table').each(function(){
			$(this).before($('<h3><a href="#">Notes</a></h3>'));
		});
		$('.right').contents().appendTo($('.left'));
		$('.left').append('<h3><a href="#">Network & Power</a></h3>');
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
		var dphtml='<div id="dphtml"><div><label for="ports">Number of Data Ports</label></div><div><input class="optional,validate[custom[onlyNumberSp]]" name="ports" id="ports" size="4" value="" type="number"></div></div>';
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
				if($dev->Owner==$deptRow->DeptID){$selected=" selected";}else{$selected="";}
				print "\t\t\t\t<option value=\"$deptRow->DeptID\"$selected>$deptRow->Name</option>\n";
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
					if($escTime->EscalationTimeID==$dev->EscalationTimeID){$selected=" selected";}else{$selected="";}
					print "\t\t\t\t\t<option value=\"$escTime->EscalationTimeID\"$selected>$escTime->TimePeriod</option>\n";
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
					if($esc->EscalationID==$dev->EscalationID){$selected="selected";}else{$selected="";}
					print "\t\t\t\t\t<option value=\"$esc->EscalationID\"$selected>$esc->Details</option>\n";
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
				if($contactRow->ContactID==$dev->PrimaryContact){$contactUserID=$contactRow->UserID;$selected="selected";}else{$selected="";}
				print "\t\t\t\t<option value=\"$contactRow->ContactID\"$selected>$contactRow->LastName, $contactRow->FirstName</option>\n";
			}
			
			print "\t\t\t</select>\n";

			if(isset($config->ParameterArray['UserLookupURL']) && isValidURL($config->ParameterArray['UserLookupURL']) && isset($contactUserID)){
				print "<input type=\"button\" value=\"Contact Lookup\" onclick=\"window.open( '".$config->ParameterArray["UserLookupURL"]."$contactUserID', 'UserLookup')\">\n";
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
	if($dev->ParentDevice==0){
		print "\t\t\t<div>".$cab->GetCabinetSelectList($facDB)."</div>\n";
		}else{
			print "\t\t\t<div>$cab->Location<input type=\"hidden\" name=\"cabinetid\" value=\"0\"></div>
		</div>
		<div>
			<div><label for=\"parentdevice\">Parent Device</label></div>
			<div><select name=\"parentdevice\">\n";
			
			foreach($parentList as $parDev){
				if($pDev->DeviceID==$parDev->DeviceID){$selected=" selected";}else{$selected="";}
				print "\t\t\t\t<option value=\"$parDev->DeviceID\"$selected>$parDev->Label</option>\n";
			}
			print "\t\t\t</select></div>\n";
		}
?>
		</div>
		<div>
			<div><label for="templateid">Device Class</label></div>
			<div><select name="templateid" id="templateid">
				<option value=0>Select a template...</option>
<?php
			foreach($templateList as $tempRow){
				if($dev->TemplateID==$tempRow->TemplateID){$selected=" selected";}else{$selected="";}
				$mfg->ManufacturerID=$tempRow->ManufacturerID;
				$mfg->GetManufacturerByID($facDB);
				print "\t\t\t\t<option value=\"$tempRow->TemplateID\"$selected>$mfg->Name - $tempRow->Model</option>\n";
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
<?php
		// Blade devices don't have data ports unless they're a switch
		if($dev->ParentDevice==0||($dev->ParentDevice>0&&$dev->DeviceType=='Switch')){
			echo '		<div id="dphtml">
		   <div><label for="ports">Number of Data Ports</label></div>
		   <div><input type="number" class="optional,validate[custom[onlyNumberSp]]" name="ports" id="ports" size="4" value="',$dev->Ports,'"></div>
		</div>';
		}
?>
		<div>
		   <div><label for="nominalwatts">Nominal Draw (Watts)</label></div>
		   <div><input type="text" class="optional,validate[custom[onlyNumberSp]]" name="nominalwatts" id="nominalwatts" size=6 value="<?php echo $dev->NominalWatts; ?>"></div>
		</div>
<?php
		// Blade devices don't have power supplies
		if($dev->ParentDevice==0){
			echo '		<div>
		   <div><label for="powersupplycount">Number of Power Supplies</label></div>
		   <div><input type="number" class="optional,validate[custom[onlyNumberSp]]" name="powersupplycount" id="powersupplycount" size=4 value="',$dev->PowerSupplyCount,'"></div>
		</div>';
		}
?>
		<div>
		   <div>Device Type</div>
		   <div><select name="devicetype">
			<option value=0>Select...</option>
<?php
		// We don't want someone accidentally adding a chassis device inside of a chassis slot.
		if($dev->ParentDevice>0){
			$devarray=array('Server','Appliance','Storage Array','Switch');
		}else{
			$devarray=array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure');
		}

		foreach($devarray as $devType){
			if($devType==$dev->DeviceType){$selected=" selected";}else{$selected="";}
			print "\t\t\t<option value=\"$devType\"$selected>$devType</option>\n";  
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
		print "\t\t<div>
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
	if($dev->DeviceType=='Server' || $dev->DeviceType=='Appliance' || $dev->DeviceType=='Chassis'){
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
