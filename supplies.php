<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights($facDB);

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$sup=new Supplies();
	$bc=new BinContents();
	
	if(isset($_REQUEST["supplyid"]) && $_REQUEST["supplyid"]>0) {
		$sup->SupplyID=$_REQUEST["supplyid"];
		$sup->GetSupplies($facDB);
	}

	$status="";
	if(isset($_POST["action"])&&(($_POST["action"]=="Create")||($_POST["action"]=="Update"))){
		$sup->SupplyID=$_REQUEST["supplyid"];
		$sup->PartNum=$_REQUEST["partnum"];
		$sup->PartName=$_REQUEST["partname"];
		$sup->MinQty=$_REQUEST["minqty"];
		$sup->MaxQty=$_REQUEST["maxqty"];

		if($_REQUEST["action"]=="Create"){
			if($sup->PartNum!=null && $sup->PartNum!=""){
  				$sup->AddSupplies($facDB);
			}
		}else{
			$status="Updated";
			$sup->UpdateSupplies($facDB);
		}
	}
	
	$supplyList=$sup->GetSuppliesList($facDB);

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Stockroom Supplies</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui-1.8.18.custom.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui-1.8.18.custom.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main">
<h2><?php echo $config->ParameterArray["OrgName"]; ?></h2>
<h3>Data Center Stockroom Supplies</h3>
<h3><?php echo $status; ?></h3>
<div class="center"><div>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
<div class="table">
<div>
   <div><label for="supplyid">Part Number</label></div>
   <div><input type="hidden" name="action" value="query"><select name="supplyid" id="supplyid" onChange="form.submit()">
   <option value=0>New Supplies</option>
<?php
	foreach ( $supplyList as $supplyRow ) {
		echo "<option value=\"$supplyRow->SupplyID\"";
		if($sup->SupplyID == $supplyRow->SupplyID){echo " selected";}
		echo ">$supplyRow->PartNum</option>\n";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="partnum">Part Number</label></div>
   <div><input type="text" name="partnum" id="partnum" value="<?php echo $sup->PartNum; ?>"></div>
</div>
<div>
   <div><label for="partname">Part Name</label></div>
   <div><input type="text" name="partname" id="partname" value="<?php echo $sup->PartName; ?>"></div>
</div>
<div>
   <div><label for="minqty">Min Qty</label></div>
   <div><input type="text" name="minqty" id="minqty" value="<?php echo $sup->MinQty; ?>"></div>
</div>
<div>
   <div><label for="maxqty">Max Qty</label></div>
   <div><input type="text" name="maxqty" id="maxqty" value="<?php echo $sup->MaxQty; ?>"></div>
</div>
<div class="caption">
   <input type="submit" name="action" value="Create">
<?php
	if($sup->SupplyID >0){
		echo '   <input type="submit" name="action" value="Update">';
	}
?>
</div>
</div><!-- END div.table -->
</form>
</div></div>
<a href="index.php">[ Return to Main Menu ]</a>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
