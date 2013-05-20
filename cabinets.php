<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights();

	if(!$user->WriteAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$cab=new Cabinet();
	$dept=new Department();
	$taginsert="";

	if(isset($_REQUEST['cabinetid'])){
		$cab->CabinetID=(isset($_POST['cabinetid'])?$_POST['cabinetid']:$_GET['cabinetid']);
		$cab->GetCabinet($facDB);
	}
	$tagarray=array();
	if(isset($_POST['tags'])){
		$tagarray=json_decode($_POST['tags']);
	}
	if(isset($_POST['action'])){
		$cab->DataCenterID=$_POST['datacenterid'];
		$cab->Location=trim($_POST['location']);
		$cab->AssignedTo=$_POST['assignedto'];
		$cab->CabinetHeight=$_POST['cabinetheight'];
		$cab->Model=$_POST['model'];
		$cab->Keylock=$_POST['keylock'];
		$cab->MaxKW=$_POST['maxkw'];
		$cab->MaxWeight=$_POST['maxweight'];
		$cab->InstallationDate=$_POST['installationdate'];
		$cab->SensorIPAddress=$_POST['sensoripaddress'];
		$cab->SensorCommunity=$_POST['sensorcommunity'];
		$cab->TempSensorOID=$_POST['tempsensoroid'];
		$cab->HumiditySensorOID=$_POST['humiditysensoroid'];
		$cab->Notes=trim($_POST['notes']);
		$cab->Notes=($cab->Notes=="<br>")?"":$cab->Notes;
		$cab->SetTags($tagarray);

		if($cab->Location!=""){
			if(($cab->CabinetID >0)&&($_POST['action']=='Update')){
				$cab->UpdateCabinet();
			}elseif($_POST['action']=='Create'){
				$cab->CreateCabinet();
			}
		}
	}

	if($cab->CabinetID >0){
		$cab->GetCabinet();

		// Get any tags associated with this device
		$tags=$cab->GetTags();
		if(count($tags>0)){
			// We have some tags so build the javascript elements we need to create the tags themselves
			$taginsert="\t\ttags: {items: ".json_encode($tags)."},\n";
		}
	}else{
		$cab->CabinetID=null;
		$cab->DataCenterID=null;
		$cab->Location=null;
		$cab->CabinetHeight=null;
		$cab->Model=null;
		$cab->Keylock=null;
		$cab->MaxKW=null;
		$cab->MaxWeight=null;
		$cab->InstallationDate=date('m/d/Y');
	}


	$deptList=$dept->GetDepartmentList($facDB);
	$cabList=$cab->ListCabinets();
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
  <script type="text/javascript" src="scripts/jquery-migrate-1.0.0.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript" src="scripts/jHtmlArea-0.7.5.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.textext.js"></script>

  <script type="text/javascript">
	$(document).ready(function() {
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
   <div>',$cab->GetDCSelectList($facDB),'</div>
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
	<div>',__("Temperature Sensor OID"),'</div>
	<div><input type="text" name="tempsensoroid" size=30 value="',$cab->TempSensorOID,'"></div>
</div>
<div>
	<div>',__("Humidity Sensor OID"),'</div>
	<div><input type="text" name="humiditysensoroid" size=30 value="',$cab->HumiditySensorOID,'"></div>
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
