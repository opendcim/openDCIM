<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$cab=new Cabinet();
	$dept=new Department();

	$taginsert="";
	$status="";

	// AJAX Requests
	// END - AJAX Requests

	$write=($person->WriteAccess)?true:false;

	if(isset($_REQUEST['cabinetid'])){
		$cab->CabinetID=(isset($_POST['cabinetid'])?$_POST['cabinetid']:$_GET['cabinetid']);
		$cab->GetCabinet();
		$write=($person->canWrite($cab->AssignedTo))?true:$write;
	}

	// If you're deleting the cabinet, no need to pull in the rest of the information, so get it out of the way
	// Only a site administrator can create or delete a cabinet
	if(isset($_POST["delete"]) && $_POST["delete"]=="yes" && $person->SiteAdmin ) {
		$cab->DeleteCabinet();
		$status['code']=200;
		$status['msg']=redirect("dc_stats.php?dc=$cab->DataCenterID");
		header('Content-Type: application/json');
		echo json_encode($status);
		exit;
	}

	// this will allow a user to modify a rack but not create a new one
	// creation is still limited to global write priviledges
	if(!$write){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$tagarray=array();
	if(isset($_POST['tags'])){
		$tagarray=json_decode($_POST['tags']);
	}
	if(isset($_POST['action'])){
		$cab->DataCenterID=$_POST['datacenterid'];
		$cab->Location=trim($_POST['location']);
		$cab->AssignedTo=$_POST['assignedto'];
		$cab->ZoneID=$_POST['zoneid'];
		$cab->CabRowID=$_POST['cabrowid'];
		$cab->CabinetHeight=$_POST['cabinetheight'];
		$cab->Model=$_POST['model'];
		$cab->Keylock=$_POST['keylock'];
		$cab->MaxKW=$_POST['maxkw'];
		$cab->MaxWeight=$_POST['maxweight'];
		$cab->InstallationDate=$_POST['installationdate'];
		$cab->Notes=trim($_POST['notes']);
		$cab->Notes=($cab->Notes=="<br>")?"":$cab->Notes;
		$cab->U1Position=$_POST['u1position'];

		if ( $cab->U1Position == "Default" ) {
			$dc = new DataCenter();
			$dc->DataCenterID = $cab->DataCenterID;
			$dc->GetDataCenter();
			if ( $dc->U1Position == "Top" ) {
				$cab->U1Position = "Top";
			} elseif ( $dc->U1Position == "Default" ) {
				$cab->U1Position = $config->ParameterArray["U1Position"];
			} else {
				$cab->U1Position = "Bottom";
			}
		}
		
		if($cab->Location!=""){
			if(($cab->CabinetID >0)&&($_POST['action']=='Update')){
				$status=__("Updated");
				$cab->UpdateCabinet();
			}elseif($_POST['action']=='Create'){
				$cab->CreateCabinet();
			}

			if($cab->CabinetID > 0) {
				$cab->SetTags($tagarray);
			}
		}
	}elseif($cab->CabinetID >0){
		$cab->GetCabinet();
	}else{
		$cab->CabinetID=null;
		//Set DataCenterID to first DC in dcList for getting zoneList
		$dc=new DataCenter();
		$dcList=$dc->GetDCList();
		$keys=array_keys($dcList);
		$cab->DataCenterID=(isset($_GET['dcid']))?intval($_GET['dcid']):$keys[0];
		$cab->Location=null;
		$cab->ZoneID=(isset($_GET['zoneid']))?intval($_GET['zoneid']):null;
		$cab->CabRowID=(isset($_GET['cabrowid']))?intval($_GET['cabrowid']):null;
		$cab->CabinetHeight=null;
		$cab->Model=null;
		$cab->Keylock=null;
		$cab->MaxKW=null;
		$cab->MaxWeight=null;
		$cab->InstallationDate=date('Y-m-d');
	}

	$deptList=$dept->GetDepartmentList();
	$cabList=$cab->ListCabinets();
	$sensorList = SensorTemplate::getTemplates();

	if($cab->CabinetID > 0) {
		// Get any tags associated with this device
		$tags=$cab->GetTags();
		if(count($tags>0)){
			// We have some tags so build the javascript elements we need to create the tags themselves
			$taginsert="\t\ttags: {items: ".json_encode($tags)."},\n";
		}
	}

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>Facilities Cabinet Maintenance</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <link rel="stylesheet" href="css/jHtmlArea.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript" src="scripts/jHtmlArea-0.8.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.textext.js"></script>
  <script type="text/javascript" src="scripts/common.js"></script>

  <script type="text/javascript">
	$(document).ready(function() {
		$('select[name=cabinetid]').change(function(e){
			location.href='cabinets.php?cabinetid='+this.value;
		});

		$('#datacenterid').change(function(){
			//store the value of the zone id prior to changing the list, we might need it
			var ov=$('#zoneid').val();
			$('#zoneid').html('');
			// Add the option for no zone
			$.get('api/v1/zone?DataCenterID='+$('#datacenterid').val()).done(function(data){
				$('#zoneid').append($('<option>').val(0).text('None'));
				if(!data.error){
					for(var x in data.zone){
						var opt=$('<option>').val(data.zone[x].ZoneID).text(data.zone[x].Description);
						$('#zoneid').append(opt);
					}
				}
			}).then(function(e){
				// Attempt to set the original value of zoneid back after we've updated the options
				$('#zoneid').val(ov);
				// if the original value is no longer valid this will reset it to none
				if($.isEmptyObject($('#zoneid').val())){
					$('#zoneid').val(0);
				}
				$('#zoneid').change();
			});
		});
		$('#zoneid').change(function(){
			//store the value of the zone id prior to changing the list, we might need it
			var ov=$('#cabrowid').val();
			$('#cabrowid').html('');
			// Add the option for no row
			$('#cabrowid').append($('<option>').val(0).text('None'));
			var zonelimit=($('#zoneid').val()!=0)?'&ZoneID='+$('#zoneid').val():'';
			$.get('api/v1/cabrow?DataCenterID='+$('#datacenterid').val()+zonelimit).done(function(data){
				if(!data.error){
					$('#cabrowid').data('cabrow',data.cabrow);
					for(var x in data.cabrow){
						var opt=$('<option>').val(data.cabrow[x].CabRowID).text(data.cabrow[x].Name);
						$('#cabrowid').append(opt);
					}
				}
			}).then(function(e){
				// Attempt to set the original value of zoneid back after we've updated the options
				$('#cabrowid').val(ov);
				// if the original value is no longer valid this will reset it to none
				if($.isEmptyObject($('#cabrowid').val())){
					$('#cabrowid').val(0);
				}
			});
		});
		$('#cabrowid').change(function(e){
			if($('#cabrowid').val()!=0){
				$('#zoneid').val($('#cabrowid').data('cabrow')[$('#cabrowid').val()].ZoneID);
				$('#zoneid').trigger('change');
			}
		});

		// Init form
		$('#datacenterid').trigger('change');

		$("#cabinetid").combobox();
		$("#datacenterid").combobox();
		$("#assignedto").combobox();
		$("#zoneid").combobox();
		$("#cabrowid").combobox();

		$('span.custom-combobox').width($('span.custom-combobox').width()+2);

		$('#rackform').validationEngine({});
		$('input[name="installationdate"]').datepicker({dateFormat: "yy-mm-dd"});
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
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',__("Data Center Cabinet Inventory"),'</h3>
<h3>',$status,'</h3>
<div class="center"><div>
<form id="rackform" method="POST">
<div class="table">
<div>
   <div>',__("Cabinet"),'</div>
   <div><select name="cabinetid" id="cabinetid">
   <option value=0>',__("New Cabinet"),'</option>';

	foreach($cabList as $cabRow){
		$selected=($cabRow->CabinetID==$cab->CabinetID)?' selected':'';
		print "<option value=\"$cabRow->CabinetID\"$selected>$cabRow->Location</option>\n";
	}

echo '   </select></div>
</div>
<div>
   <div>',__("Data Center"),'</div>
   <div>
		<select name="datacenterid" id="datacenterid">
';

	foreach(DataCenter::GetDCList() as $dc){
		$selected=($dc->DataCenterID==$cab->DataCenterID)?' selected':'';
		print "\t\t\t<option value=\"$dc->DataCenterID\"$selected>$dc->Name</option>\n";
	}

echo '		</select>
	</div>
</div>
<div>
   <div>',__("Location"),'</div>
   <div><input type="text" class="validate[required,minSize[1],maxSize[20]]" name="location" size=10 maxlength=20 value="',$cab->Location,'"></div>
</div>
<div>
  <div>',__("Assigned To"),':</div>
  <div><select name="assignedto" id="assignedto">
    <option value=0>',__("General Use"),'</option>';

	foreach($deptList as $deptRow){
		if($deptRow->DeptID==$cab->AssignedTo){$selected=' selected';}else{$selected="";}
		print "<option value=\"$deptRow->DeptID\"$selected>$deptRow->Name</option>\n";
	}

echo '  </select>
  </div>
</div>
<div>
   <div>',__("Zone"),'</div>
   <div>',$cab->GetZoneSelectList(),'</div>
</div>
<div>
   <div>',__("Cabinet Row"),'</div>
   <div>',$cab->GetCabRowSelectList(),'</div>
</div>
<div>
   <div>',__("Cabinet Height"),' (U)</div>
   <div><input type="text" class="validate[optional,custom[onlyNumberSp]]" name="cabinetheight" size=4 maxlength=4 value="',$cab->CabinetHeight,'"></div>
</div>
<div>
   <div>',__("U1 Position"),'</div>
   <div><select name="u1position">';

$posarray=array('Bottom' => __("Bottom"),'Top' => __("Top"),'Default' => __("Default"));
foreach($posarray as $pos => $translation){
	$selected=($cab->U1Position==$pos)?' selected':'';
	print "      <option value=\"$pos\"$selected>$translation</option>\n";
}
   
echo '</select></div>
</div>
<div>
   <div>',__("Model"),'</div>
   <div><input type="text" name="model" size=30 maxlength=80 value="',$cab->Model,'"></div>
</div>
<div>
   <div>',__("Key/Lock Information"),'</div>
   <div><input type="text" name="keylock" size=30 maxlength=30 value="',$cab->Keylock,'"></div>
</div>
<div>
   <div>',__("Maximum"),' kW</div>
   <div><input type="text" class="validate[optional,custom[number]]" name="maxkw" size=30 maxlength=11 value="',$cab->MaxKW,'"></div>
</div>
<div>
   <div>',__("Maximum Weight"),'</div>
   <div><input type="text" class="validate[optional,custom[onlyNumberSp]]" name="maxweight" size=30 maxlength=11 value="',$cab->MaxWeight,'"></div>
</div>
<div>
   <div>',__("Date of Installation"),'</div>
   <div><input type="text" name="installationdate" size=15 value="',date('Y-m-d', strtotime($cab->InstallationDate)),'"></div>
</div>
<div>
	<div><label for="tags">',__("Tags"),'</label></div>
	<div><textarea type="text" name="tags" id="tags" rows="1"></textarea></div>
</div>
</div> <!-- END div.table -->
<div class="table">
	<div>
	  <div><label for="notes">',__("Notes"),'</label></div>
	  <div><textarea name="notes" id="notes" cols="40" rows="8">',$cab->Notes,'</textarea></div>
	</div>
<div class="caption">';

	if($cab->CabinetID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>
	<button type="button" name="action" value="Delete">',__("Delete"),'</button>
	<button type="button" value="AuditReport">',__("Audit Report"),'</button>
	<button type="button" value="MapCoordinates">',__("Map Coordinates"),'</button>';
	}else{
		echo '   <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
?>
</div>		
</div> <!-- END div.table -->
</form>
</div></div>
<?php if($cab->CabinetID >0){
		echo '<a href="cabnavigator.php?cabinetid=',$cab->CabinetID,'">[ ',__("Return to Navigator"),' ]</a>'; 
	}else{ 
		echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>';
	}

echo '
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Cabinet delete confirmation"),'" id="deletemodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this cabinet and all the devices in it?<br><br><b>THERE IS NO UNDO</b>"),'
		</div>
	</div>
</div>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
$('button[value=AuditReport]').click(function(){
	window.location.assign('cabaudit.php?cabinetid='+$('select[name=cabinetid]').val());
});
$('button[value=MapCoordinates]').click(function(){
	window.location.assign('mapmaker.php?cabinetid='+$('select[name=cabinetid]').val());
});
$('button[value=Delete]').click(function(){
	var defaultbutton={
		"<?php echo __("Yes"); ?>": function(){
			$.post('', {cabinetid: $('select[name=cabinetid]').val(),delete: 'yes' }, function(data){
				if(data.code==200){
					window.location.assign(data.msg);
				}else{
					alert("Danger, Will Robinson! DANGER!  Something didn't go as planned.");
				}
			});
		}
	}
	var cancelbutton={
		"<?php echo __("No"); ?>": function(){
			$(this).dialog("destroy");
		}
	}
	var modal=$('#deletemodal').dialog({
		dialogClass: 'no-close',
		modal: true,
		width: 'auto',
		buttons: $.extend({}, defaultbutton, cancelbutton)
	});
});

</script>
</body>
</html>
