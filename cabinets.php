<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->WriteAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$cab=new Cabinet();
	$dept=new Department();

	if(isset($_REQUEST['cabinetid'])){
		$cab->CabinetID=(isset($_POST['cabinetid'])?$_POST['cabinetid']:$_GET['cabinetid']);
		$cab->GetCabinet($facDB);
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
		$cab->SensorOID=$_POST['sensoroid'];

		if($cab->Location!=""){
			if(($cab->CabinetID >0)&&($_POST['action']=='Update')){
				$cab->UpdateCabinet($facDB);
			}elseif($_POST['action']=='Create'){
				$cab->CreateCabinet($facDB);
			}
		}
	}

	if($cab->CabinetID >0){
		$cab->GetCabinet($facDB);
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
	$cabList=$cab->ListCabinets($facDB);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>Facilities Cabinet Maintenance</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui-1.8.18.custom.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui-1.8.18.custom.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>

  <script type="text/javascript">
	$(document).ready(function() {
		$('#rackform').validationEngine({});
		$('input[name="installationdate"]').datepicker({});
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
<h3>',_("Data Center Cabinet Inventory"),'</h3>
<div class="center"><div>
<form id="rackform" action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div>',_("Cabinet"),'</div>
   <div><select name="cabinetid" onChange="form.submit()">
   <option value=0>',_("New Cabinet"),'</option>';

	foreach($cabList as $cabRow){
		if($cabRow->CabinetID == $cab->CabinetID){$selected=' selected';}else{$selected="";}
		print "<option value=\"$cabRow->CabinetID\"$selected>$cabRow->Location</option>\n";
	}

echo '   </select></div>
</div>
<div>
   <div>',_("Data Center"),'</div>
   <div>',$cab->GetDCSelectList($facDB),'</div>
</div>
<div>
   <div>',_("Location"),'</div>
   <div><input type="text" class="validate[required,minSize[1],maxSize[20]]" name="location" size=10 maxlength=20 value="',$cab->Location,'"></div>
</div>
<div>
  <div>',_("Assigned To"),':</div>
  <div><select name="assignedto">
    <option value=0>',_("General Use"),'</option>';

	foreach($deptList as $deptRow){
		if($deptRow->DeptID==$cab->AssignedTo){$selected=' selected';}else{$selected="";}
		print "<option value=\"$deptRow->DeptID\"$selected>$deptRow->Name</option>\n";
	}

echo '  </select>
  </div>
</div>
<div>
   <div>',_("Cabinet Height"),' (U)</div>
   <div><input type="text" class="validate[optional,custom[onlyNumberSp]]" name="cabinetheight" size=4 maxlength=4 value="',$cab->CabinetHeight,'"></div>
</div>
<div>
   <div>',_("Model"),'</div>
   <div><input type="text" name="model" size=30 maxlength=80 value="',$cab->Model,'"></div>
</div>
<div>
   <div>',_("Key/Lock Information"),'</div>
   <div><input type="text" name="keylock" size=30 maxlength=30 value="',$cab->Keylock,'"></div>
</div>
<div>
   <div>',_("Maximum"),' kW</div>
   <div><input type="text" class="validate[optional,custom[number]]" name="maxkw" size=30 maxlength=11 value="',$cab->MaxKW,'"></div>
</div>
<div>
   <div>',_("Maximum Weight"),'</div>
   <div><input type="text" class="validate[optional,custom[onlyNumberSp]]" name="maxweight" size=30 maxlength=11 value="',$cab->MaxWeight,'"></div>
</div>
<div>
   <div>',_("Date of Installation"),'</div>
   <div><input type="text" name="installationdate" size=15 value="',date('m/d/Y', strtotime($cab->InstallationDate)),'"></div>
</div>
<div>
	<div>',_("Sensor IP Address"),'</div>
	<div><input type="text" name="sensoripaddress" size=15 value="',$cab->SensorIPAddress,'"></div>
</div>
<div>
	<div>',_("Sensor SNMP Community"),'</div>
	<div><input type="text" name="sensorcommunity" size=30 value="',$cab->SensorCommunity,'"></div>
</div>
<div>
	<div>',_("Temperature Sensor OID"),'</div>
	<div><input type="text" name="sensoroid" size=30 value="',$cab->SensorOID,'"></div>
</div>
<div class="caption">';

	if($cab->CabinetID >0){
		echo '   <button type="submit" name="action" value="Update">',_("Update"),'</button>';
	}else{
		echo '   <button type="submit" name="action" value="Create">',_("Create"),'</button>';
	}
?>
</div>		
</div> <!-- END div.table -->
</form>
</div></div>
<?php if($cab->CabinetID >0){
		echo '<a href="cabnavigator.php?cabinetid=',$cab->CabinetID,'">[ ',_("Return to Navigator"),' ]</a>'; 
	}else{ 
		echo '<a href="index.php">[ ',_("Return to Main Menu"),' ]</a>';
	}
?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
