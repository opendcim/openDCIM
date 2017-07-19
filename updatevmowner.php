<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$subheader=__("Data Center Virtual Machine Detail");

	if(!$person->WriteAccess || !isset($_REQUEST['vmindex'])){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$dept=new Department();
	$vm=new VM();
	$con = new People();
	$dev=new Device();

	if($_REQUEST['vmindex'] >0){
		$vm->VMIndex = $_REQUEST['vmindex'];
		$vm->GetVMbyIndex();
		$dev->DeviceID=$vm->DeviceID;
		$dev->GetDevice();
		if(isset($_REQUEST['action']) && $_REQUEST['action']=='Update'){
			$vm->Owner=$_REQUEST['owner'];
			$vm->PrimaryContact=$_REQUEST['contact'];
			$vm->UpdateVMOwner();
			header('Location: '.redirect("devices.php?DeviceID=$vm->DeviceID"));
		}
	}else{
		// How'd you get here without a valid vmindex?
		header('Location: '.redirect());
		exit;
	}

	$contactList = $con->GetUserList();
	$deptList=$dept->GetDepartmentList();
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Device Maintenance</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<div class="center"><div>

<form method="POST">
<div class="table">
	<div>
		<div>Owner (Department)</div>
		<div><select name="owner">
			<option value=0>Select the owner</option>
<?php
	foreach($deptList as $deptRow){
		print "			<option value=$deptRow->DeptID";
		if($vm->Owner==$deptRow->DeptID){echo ' selected="selected"';}
		print ">$deptRow->Name</option>\n";
	}
?>
		</select></div>
	</div>
	<div>
		<div>Primary Contact</div>
		<div><select name="contact">
			<option value=0>Select Primary Contact</option>
<?php
	foreach( $contactList as $conRow ) {
		print "			<option value=$conRow->PersonID";
		if ( $vm->PrimaryContact == $conRow->PersonID ) {echo ' selected';}
		print ">$conRow->LastName, $conRow->FirstName</option>\n";
	}
?>
		</select></div>
	</div>
	<div>
	   <div>VM Name</div>
	   <div><input type="text" size="50" name="vmname" value="<?php echo $vm->vmName; ?>" readonly></div>
	</div>
	<div>
	   <div>Current Server</div>
	   <div><input type="text" size="50" name="currserver" value="<?php echo $dev->Label; ?>" readonly></div>
	</div>
	<div class="caption">
	   <input type="hidden" name="vmindex" value="<?php echo $vm->VMIndex; ?>">
	   <input type="submit" name="action" value="Update" default>
	</div>
</div>
</form>

</div></div>
<a href="devices.php?DeviceID=<?php echo $dev->DeviceID; ?>">Return to Parent Device</a>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
