<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$subheader=__("Data Center Stockroom Supplies");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$sup=new Supplies();
	$bc=new BinContents();

	$inventory=array();	
	if(isset($_REQUEST["supplyid"]) && $_REQUEST["supplyid"]>0) {
		$sup->SupplyID=$_REQUEST["supplyid"];
		$sup->GetSupplies();
		
		$bc->SupplyID = $sup->SupplyID;
		$inventory = $bc->FindSupplies();
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
  				$sup->CreateSupplies();
			}
		}else{
			$status="Updated";
			$sup->UpdateSupplies();
		}
	}
	
	$supplyList=$sup->GetSuppliesList();

	$supplytable='';
	if(sizeof($inventory) >0){
		$sb=new SupplyBin();

		$supplytable='<div class="table border">
	<div>
		<div>'.__("Bin ID").'</div>
		<div>'.__("Count").'</div>
	</div>';
		foreach($inventory as $binContent){
			$sb->BinID=$binContent->BinID;
			$sb->GetBin();
			$supplytable.="\t<div>\t\t<div><a href=\"supplybin.php?binid=$sb->BinID\">$sb->Location</a></div>\n\t\t<div>$binContent->Count</div>\n\t</div>\n";
		}
		$supplytable.='</div>';
	}	// endif of sizeof( $inventory ) > 0 block
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
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page supply">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<h3>'.$status.'</h3>
<div class="center"><div>
<form method="POST">
<div class="table">
<div>
   <div><label for="supplyid">'.__("Part Number").'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="supplyid" id="supplyid" onChange="form.submit()">
   <option value=0>'.__("New Supplies").'</option>';

	foreach($supplyList as $supplyRow){
		$selected=($sup->SupplyID==$supplyRow->SupplyID)?" selected":"";
		print "\t\t<option value=\"$supplyRow->SupplyID\"$selected>($supplyRow->PartNum) $supplyRow->PartName</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="partnum">'.__("Part Number").'</label></div>
   <div><input type="text" name="partnum" id="partnum" value="'.$sup->PartNum.'"></div>
</div>
<div>
   <div><label for="partname">'.__("Part Name").'</label></div>
   <div><input type="text" name="partname" id="partname" value="'.$sup->PartName.'"></div>
</div>
<div>
   <div><label for="minqty">'.__("Min Qty").'</label></div>
   <div><input type="text" name="minqty" id="minqty" value='.$sup->MinQty.'></div>
</div>
<div>
   <div><label for="maxqty">'.__("Max Qty").'</label></div>
   <div><input type="text" name="maxqty" id="maxqty" value='.$sup->MaxQty.'></div>
</div>
<div class="caption">
   <input type="submit" name="action" value="Create">';
	if($sup->SupplyID >0){
		echo '   <input type="submit" name="action" value="Update">';
	}
echo '
</div>
</div><!-- END div.table -->
'.$supplytable.'
</form>
</div>
</div>
<a href="index.php">[ '.__("Return to Main Menu").' ]</a>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>';
?>
