<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$mfg=new Manufacturer();

	if(isset($_REQUEST["manufacturerid"]) && $_REQUEST["manufacturerid"] >0){
		$mfg->ManufacturerID=(isset($_POST['manufacturerid']) ? $_POST['manufacturerid'] : $_GET['manufacturerid']);
		$mfg->GetManufacturerByID($facDB);
	}

	$status="";
	if(isset($_POST["action"])&&(($_POST["action"]=="Create")||($_POST["action"]=="Update"))){
		$mfg->ManufacturerID=$_POST["manufacturerid"];
		$mfg->Name=$_POST["name"];

		if($_POST["action"]=="Create"){
			if($mfg->Name != null && $mfg->Name != ""){
  				$mfg->AddManufacturer($facDB);
			}
		}else{
			$status="Updated";
			$mfg->UpdateManufacturer($facDB);
		}
	}
	$mfgList=$mfg->GetManufacturerList($facDB);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
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

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',_("Data Center Manufacturer Listing"),'</h3>
<h3>',$status,'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div><label for="manufacturerid">',_("Manufacturer"),'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="manufacturerid" id="manufacturerid" onChange="form.submit()">
   <option value=0>',_("New Manufacturer"),'</option>';

	foreach($mfgList as $mfgRow){
		if($mfg->ManufacturerID==$mfgRow->ManufacturerID){$selected=" selected";}else{$selected="";}
		echo "<option value=\"$mfgRow->ManufacturerID\"$selected>$mfgRow->Name</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="name">',_("Name"),'</label></div>
   <div><input type="text" name="name" id="name" value="',$mfg->Name,'"></div>
</div>
<div class="caption">';

	if($mfg->ManufacturerID >0){
		echo '   <button type="submit" name="action" value="Update">',_("Update"),'</button>';
	}else{
		echo '   <button type="submit" name="action" value="Create">',_("Create"),'</button>';
	}
?>
</div>
</div><!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',_("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
