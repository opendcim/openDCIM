<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$cab=new Cabinet();
	$dept=new Department();

	$taginsert="";
	$status="";

	// AJAX Requests
		//Zone Lists
		if(isset($_POST['zonelist'])){
			$cab->DataCenterID=$_POST['zonelist'];
			echo $cab->GetZoneSelectList();
			exit;
		}
		if(isset($_POST['rowlist'])){
			$cab->ZoneID=$_POST['rowlist'];
			echo $cab->GetCabRowSelectList();
			exit;
		}
	
		//Row Lists

	// END - AJAX Requests

	$write=($user->WriteAccess)?true:false;

	if(isset($_REQUEST['cabinetid'])){
		$cab->CabinetID=(isset($_POST['cabinetid'])?$_POST['cabinetid']:$_GET['cabinetid']);
		$cab->GetCabinet();
		$write=($user->canWrite($cab->AssignedTo))?true:$write;
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
		$cab->SensorIPAddress=$_POST['sensoripaddress'];
		$cab->SensorCommunity=$_POST['sensorcommunity'];
		$cab->SensorTemplateID=$_POST['sensortemplateid'];
		$cab->Notes=trim($_POST['notes']);
		$cab->Notes=($cab->Notes=="<br>")?"":$cab->Notes;
		$cab->SetTags($tagarray);

		if($cab->Location!=""){
			if(($cab->CabinetID >0)&&($_POST['action']=='Update')){
				$status=__("Updated");
				$cab->UpdateCabinet();
			}elseif($_POST['action']=='Create'){
				$cab->CreateCabinet();
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
		$cab->DataCenterID=$keys[0];
		$cab->Location=null;
		$cab->ZoneID=null;
		$cab->CabRowID=null;
		$cab->CabinetHeight=null;
		$cab->Model=null;
		$cab->Keylock=null;
		$cab->MaxKW=null;
		$cab->MaxWeight=null;
		$cab->InstallationDate=date('m/d/Y');
	}


	$deptList=$dept->GetDepartmentList();
	$cabList=$cab->ListCabinets();
	$sensorList = SensorTemplate::getTemplate();

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
		$('#datacenterid').change(function(){
			$.post('',{zonelist: $(this).val()}).done(function(data){
				$('#zoneid').html('');
				$(data).find('option').each(function(){
					$('#zoneid').append($(this));	
				});
				$('#zoneid').val(0);
				$('#zoneid').change();
			});
		});
		$('#zoneid').change(function(){
			$.post('',{rowlist: $(this).val()}).done(function(data){
				$('#cabrowid').html('');
				$(data).find('option').each(function(){
					$('#cabrowid').append($(this));	
				});
				$('#cabrowid').val(0);
			});
		});

		// Init form
		if($('#zoneid').val()==0 && $('#cabrowid').val()==0){
			$('#datacenterid').trigger('change');
		}

		$('#rackform').validationEngine({});
		$('input[name="installationdate"]').datepicker({});
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
<div id="header"></div>
<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',__("Data Center Cabinet Inventory"),'</h3>
<h3>',$status,'</h3>
<div class="center"><div>
<form id="rackform" action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div>',__("Cabinet"),'</div>
   <div><select name="cabinetid" onChange="form.submit()">
   <option value=0>',__("New Cabinet"),'</option>';

	foreach($cabList as $cabRow){
		if($cabRow->CabinetID == $cab->CabinetID){$selected=' selected';}else{$selected="";}
		print "<option value=\"$cabRow->CabinetID\"$selected>$cabRow->Location</option>\n";
	}

echo '   </select></div>
</div>
<div>
   <div>',__("Data Center"),'</div>
   <div>',$cab->GetDCSelectList(),'</div>
</div>
<div>
   <div>',__("Location"),'</div>
   <div><input type="text" class="validate[required,minSize[1],maxSize[20]]" name="location" size=10 maxlength=20 value="',$cab->Location,'"></div>
</div>
<div>
  <div>',__("Assigned To"),':</div>
  <div><select name="assignedto">
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
   <div><input type="text" name="installationdate" size=15 value="',date('m/d/Y', strtotime($cab->InstallationDate)),'"></div>
</div>
<div>
	<div>',__("Sensor IP Address"),'</div>
	<div><input type="text" name="sensoripaddress" size=15 value="',$cab->SensorIPAddress,'"></div>
</div>
<div>
	<div>',__("Sensor SNMP Community"),'</div>
	<div><input type="text" name="sensorcommunity" size=30 value="',$cab->SensorCommunity,'"></div>
</div>
<div>
	<div>',__("Sensor Template"),':</div>
	<div><select name=sensortemplateid>
		<option value=0>Select a template</option>';
	foreach ( $sensorList as $template ) {
		if ( $template->TemplateID == $cab->SensorTemplateID ) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		printf( "<option value=%d %s>%s</option>\n", $template->TemplateID, $selected, $template->Name );
	}
	
	echo '</select></div>
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
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>';
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
?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
