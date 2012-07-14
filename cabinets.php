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

	if(isset($_REQUEST['action']) && $user->WriteAccess){
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
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
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Cabinet Inventory</h3>
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<div class="table">
<div>
   <div>Cabinet</div>
   <div><select name="cabinetid" onChange="form.submit()">
   <option value=0>New Cabinet</option>
<?php
	foreach($cabList as $cabRow){
		print "<option value=\"$cabRow->CabinetID\"";
		if($cabRow->CabinetID == $cab->CabinetID){
			echo ' selected';
		}
		print ">$cabRow->Location</option>\n";
	}
?>
   </select></div>
</div>
<div>
   <div>Data Center</div>
   <div><?php echo $cab->GetDCSelectList($facDB); ?></div>
</div>
<div>
   <div>Location</div>
   <div><input type="text" name="location" size=8 value="<?php echo $cab->Location; ?>"></div>
</div>
<div>
  <div>Assigned To:</div>
  <div><select name="assignedto">
    <option value=0>General Use</option>
<?php
	foreach($deptList as $deptRow){
		print "<option value=\"$deptRow->DeptID\"";
		if($deptRow->DeptID == $cab->AssignedTo){echo ' selected=\'selected\'';}
		print ">$deptRow->Name</option>\n";
	}
?>
  </select>
  </div>
</div>
<div>
   <div>Cabinet Height (U)</div>
   <div><input type="text" name="cabinetheight" size=4 value="<?php echo $cab->CabinetHeight; ?>"></div>
</div>
<div>
   <div>Model</div>
   <div><input type="text" name="model" size=30 value="<?php echo $cab->Model; ?>"></div>
</div>
<div>
   <div>Key/Lock Information</div>
   <div><input type="text" name="keylock" size=30 value="<?php echo $cab->Keylock; ?>"></div>
</div>
<div>
   <div>Maximum kW</div>
   <div><input type="text" name="maxkw" size=30 value="<?php echo $cab->MaxKW; ?>"></div>
</div>
<div>
   <div>Maximum Weight</div>
   <div><input type="text" name="maxweight" size=30 value="<?php echo $cab->MaxWeight; ?>"></div>
</div>
<div>
   <div>Date of Installation</div>
   <div><input type="text" name="installationdate" size=15 value="<?php echo date('m/d/Y', strtotime($cab->InstallationDate)); ?>"></div>
</div>
<?php
	if($user->WriteAccess){
		echo '<div class="caption">';
		if($cab->CabinetID >0){
			echo '   <input type="submit" name="action" value="Update">';
		}else{
			echo '   <input type="submit" name="action" value="Create">';
		}
		echo '</div>';		
	}
?>
</div> <!-- END div.table -->
</form>
</div></div>
<?php if($cab->CabinetID >0){
		print "<a href=\"cabnavigator.php?cabinetid=$cab->CabinetID\">[ Return to Navigator ]</a>"; 
	}else{ 
		echo '<a href="index.php">[ Return to Main Menu ]</a>';
	}
?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
