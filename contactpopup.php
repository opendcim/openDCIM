<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );
	
	$dept=new Department();
	$contact=new Contact();
	
	if(!isset($_REQUEST['deptid'])){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$deptID=intval($_REQUEST['deptid']);
	$contactList=$contact->GetContactsForDepartment($deptID);
	$dept->DeptID=$deptID;
	$dept->GetDeptByID();

	if(isset($config->ParameterArray['UserLookupURL']) && isValidURL($config->ParameterArray['UserLookupURL'])){
		$el=1; //enable displaying lookup options
	}else{
		$el=0; //default to not showing lookup options
	}

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Device Maintenance</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
</head>
<body>
<div id="header"></div>
<div class="main">
<h3>Department Contact Listing</h3>
<h3><?php echo $dept->Name; ?></h3>
<div class="table border centermargin">
	<div>
		<div>Last Name</div>
		<div>First Name</div>
		<div>UserID</div>
<?php if($el){ echo '		<div>Lookup</div>';} ?>
	</div>
<?php
	foreach($contactList as $contactRow){
		print "<div>
		<div>$contactRow->LastName</div>
		<div>$contactRow->FirstName</div>
		<div>$contactRow->UserID</div>";
		if($el){
			print "		<div><input type=\"button\" value=\"Contact Lookup\" onclick=\"window.open( '{$config->ParameterArray["UserLookupURL"]}$contactRow->UserID', 'Lookup' );\"></div>";
		}
		print "	</div>\n";
	}
?>	
</div>
</body>
</html>
