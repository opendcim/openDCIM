<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$subheader=__("Data Center Stockroom Supplies");

	if(!$person->WriteAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$dept=new Department();
	$sup=new Supplies();
	
	$supplyList=$sup->GetSuppliesList();
	$deptList=$dept->GetDepartmentList();

	// Check to make sure this was a form submission
	if(isset($_POST['action']) && $_POST['action']=="submit"){
		// The submission looks like our form and has something.
		if(isset($_POST['deptid']) && count($_POST['deptid']>0)){
			foreach($_POST['deptid'] as $key => $value){
				/* use the $key to pull the value from each line.
				   only process if there is some form of data in each field */
				if($_POST['supplyid'][$key]>0 && $_POST['quantity'][$key]>0 && $_POST['deptid'][$key]>0){
					// print is a placeholder to verify information while testing
					print "{$_POST['deptid'][$key]}\n";
					print "{$_POST['supplyid'][$key]}\n";
					print "{$_POST['quantity'][$key]}\n";

					// hand off dept, supplyid and amount to deduct to non-existent function that will log, bill, track, whatever
				}
			}
		}
	}

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Stockroom Supplies</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript">
	$(document).ready(function() {
		$('#newline').click(function (){
			$(this).parent().prev().clone().insertBefore($(this).parent()).children('div:first-child').html('<img src="images/del.gif">').click(function() {
				$(this).parent().remove();
			});
		});
	});
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page supply">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main">
<div class="center"><div>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
<div class="table">
	<div>
		<div></div>
		<div>Customer</div>
		<div>Part Number</div>
		<div>Quantity</div>
	</div>
	<div>
		<div></div>
		<div><select name="deptid[]" id="deptid">
			<option value="0" selected>Select Department...</option>
<?php
	foreach($deptList as $deptRow){
		echo "			<option value=\"$deptRow->DeptID\">$deptRow->Name</option>\n";
	}

	echo '		</select></div>
		<div><select name="supplyid[]" id="supplyid">
			<option value="0" selected>Select part...</option>
';

	foreach($supplyList as $supplyRow){
		echo "			<option value=\"$supplyRow->SupplyID\">$supplyRow->PartNum ($supplyRow->PartName)</option>\n";
	}
?>
		</select></div>
		<div><input type="text" name="quantity[]" class="quantity" size=5 maxlength=5></div>
	</div>
	<div>
		<div id="newline"><img src="images/add.gif" alt="add new row"></div>
		<div></div>
		<div></div>
		<div></div>
	</div>
	<div class="caption">
		<button type="submit" name="action" value="submit">Submit</button>
	</div>
</div><!-- END div.table -->
</form>
</div></div>
<a href="index.php">[ Return to Main Menu ]</a>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
