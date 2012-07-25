<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$user = new User();
	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$bin = new SupplyBin();
	$bc = new BinContents();
		
	if(isset($_REQUEST["binid"]) && $_REQUEST["binid"]>0) {
		$bin->BinID = $_REQUEST["binid"];
		$bin->GetBin( $facDB );
		
		$bc->BinID = $bin->BinID;
		$binContents = $bc->GetBinContents( $facDB );
	}

	$status="";
	if(isset($_REQUEST["action"])&&(($_REQUEST["action"]=="Create")||($_REQUEST["action"]=="Update"))){
		$bin->BinID=$_REQUEST["binid"];
		$bin->Location=$_REQUEST["location"];

		if($_REQUEST["action"]=="Create"){
			if($bin->Location != null && $bin->Location != "") {
  				$bin->AddBin($facDB);
			}
		}else{
			$status="Updated";
			$bin->UpdateBin($facDB);
		}
	}
	
	$binList = $bin->GetBinList( $facDB );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Stockroom Supply Bins</title>
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
	include( "sidebar.inc.php" );
?>
<div class="main">
<h2><?php echo $config->ParameterArray["OrgName"]; ?></h2>
<h3>Data Center Stockroom Supply Bins</h3>
<h3><?php echo $status; ?></h3>
<div class="center"><div>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
<div class="table">
<div>
   <div><label for="binid">Bin Location</label></div>
   <div><input type="hidden" name="action" value="query"><select name="binid" id="binid" onChange="form.submit()">
   <option value=0>New Bin</option>
<?php
	foreach ( $binList as $binRow ) {
		echo "<option value=\"$binRow->BinID\"";
		if($bin->BinID == $binRow->BinID){echo " selected";}
		echo ">$binRow->Location</option>\n";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="location">Location</label></div>
   <div><input type="text" name="location" id="location" value="<?php echo $bin->Location; ?>"></div>
</div>
<div class="caption">
   <input type="submit" name="action" value="Create">
<?php
	if($bin->BinID >0){
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
