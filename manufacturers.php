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

	$mfg = new Manufacturer();

	if(isset($_REQUEST["manufacturerid"]) && $_REQUEST["manufacturerid"] >0){
		$mfg->ManufacturerID = $_REQUEST["manufacturerid"];
		$mfg->GetManufacturerByID( $facDB );
	}

	$status="";
	if(isset($_REQUEST["action"])&&(($_REQUEST["action"]=="Create")||($_REQUEST["action"]=="Update"))){
		$mfg->ManufacturerID=$_REQUEST["manufacturerid"];
		$mfg->Name=$_REQUEST["name"];

		if($_REQUEST["action"]=="Create"){
			if($mfg->Name != null && $mfg->Name != ""){
  				$mfg->AddManufacturer($facDB);
			}
		}else{
			$status="Updated";
			$mfg->UpdateManufacturer($facDB);
		}
	}
	$mfgList = $mfg->GetManufacturerList($facDB);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Device Class Templates</title>
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
<h3>Data Center Manufacturer Listing</h3>
<h3><?php echo $status; ?></h3>
<div class="center"><div>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
<div class="table">
<div>
   <div><label for="manufacturerid">Manufacturer</label></div>
   <div><input type="hidden" name="action" value="query"><select name="manufacturerid" id="manufacturerid" onChange="form.submit()">
   <option value=0>New Manufacturer</option>
<?php
	foreach($mfgList as $mfgRow){
		echo "<option value=\"$mfgRow->ManufacturerID\"";
		if($mfg->ManufacturerID == $mfgRow->ManufacturerID){echo " selected";}
		echo ">$mfgRow->Name</option>\n";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="name">Name</label></div>
   <div><input type="text" name="name" id="name" value="<?php echo $mfg->Name; ?>"></div>
</div>
<div class="caption">
   <input type="submit" name="action" value="Create">
<?php
	if($mfg->ManufacturerID >0){
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
