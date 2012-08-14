<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );

	if(!$user->WriteAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$cab=new Cabinet();
	$dept=new Department();

	if(isset($_REQUEST['cabinetid'])){
		$cab->CabinetID=$_REQUEST['cabinetid'];
		$cab->GetCabinet($facDB);
	}

	if(isset($_REQUEST['action'])){
		$cab->DataCenterID=$_REQUEST['datacenterid'];
		$cab->Location=$_REQUEST['location'];
		$cab->AssignedTo=$_REQUEST['assignedto'];
		$cab->CabinetHeight=$_REQUEST['cabinetheight'];
		$cab->Model=$_REQUEST['model'];
		$cab->Keylock=$_REQUEST['keylock'];
		$cab->MaxKW=$_REQUEST['maxkw'];
		$cab->MaxWeight=$_REQUEST['maxweight'];
		$cab->InstallationDate=$_REQUEST['installationdate'];

		if(($cab->CabinetID >0)&&($_REQUEST['action']=='Update')){
			$cab->UpdateCabinet($facDB);
		}elseif($_REQUEST['action']=='Create'){
			$cab->CreateCabinet($facDB);
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
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>Facilities Cabinet Maintenance</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
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
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div>',_("Cabinet"),'</div>
   <div><select name="cabinetid" onChange="form.submit()">
   <option value=0>',_("New Cabinet"),'</option>';

	foreach($cabList as $cabRow){
		if($cabRow->CabinetID == $cab->CabinetID){$select=' selected';}else{$selected="";}
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
   <div><input type="text" name="location" size=8 value="',$cab->Location,'"></div>
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
   <div><input type="text" name="cabinetheight" size=4 value="',$cab->CabinetHeight,'"></div>
</div>
<div>
   <div>',_("Model"),'</div>
   <div><input type="text" name="model" size=30 value="',$cab->Model,'"></div>
</div>
<div>
   <div>',_("Key/Lock Information"),'</div>
   <div><input type="text" name="keylock" size=30 value="',$cab->Keylock,'"></div>
</div>
<div>
   <div>',_("Maximum"),' kW</div>
   <div><input type="text" name="maxkw" size=30 value="',$cab->MaxKW,'"></div>
</div>
<div>
   <div>',_("Maximum Weight"),'</div>
   <div><input type="text" name="maxweight" size=30 value="',$cab->MaxWeight,'"></div>
</div>
<div>
   <div>',_("Date of Installation"),'</div>
   <div><input type="text" name="installationdate" size=15 value="',date('m/d/Y', strtotime($cab->InstallationDate)),'"></div>
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
