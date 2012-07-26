<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights($facDB);

	if(!$user->WriteAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$dept=new Department();
	$bin=new SupplyBin();
	$sup=new Supplies();
	$bc=new BinContents();
	$sb=new SupplyBin();
	
	$binList=$sb->GetBinList();
	$supplyList=$sup->GetSuppliesList($facDB);
	$deptList=$dept->GetDepartmentList($facDB);


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Stockroom Supplies</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript">
	$(document).ready(function() {
		$('#newline').click(function (){
			$(this).parent().prev().clone().insertBefore($(this).parent());
			$(this).parent().prev().children('div:first-child').html('<img src="images/del.gif">');
			$(this).parent().prev().children('div:first-child').click(function() {
				$(this).parent().remove();
			});
//			$(this).parent().prev($(this).children('div').addClass('innertest')).addClass('test');
		});
	});
  </script>
</head>
<body>
<div id="header"></div>
<div class="page supply">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main">
<h2><?php echo $config->ParameterArray["OrgName"]; ?></h2>
<h3>Data Center Stockroom Supplies</h3>
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
<?php
	foreach($deptList as $deptRow){
		echo "			<option value=\"$deptRow->DeptID\"";
		if($dept->DeptID == $deptRow->DeptID){echo ' selected';}
		echo ">$deptRow->Name</option>\n";
	}

	echo '		</select></div>
		<div><select name="supplyid[]" id="supplyid">
';

	foreach($supplyList as $supplyRow){
		echo "			<option value=\"$supplyRow->SupplyID\"";
		if($sup->SupplyID == $supplyRow->SupplyID){echo " selected";}
		echo ">$supplyRow->PartNum ($supplyRow->PartName)</option>\n";
	}
?>
		</select></div>
		<div><input type="text" name="quantity[]" id="quantity" size=5 maxlength=5></div>
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
