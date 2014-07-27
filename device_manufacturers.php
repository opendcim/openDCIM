<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$subheader=__("Data Center Manufacturer Listing");

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$mfg=new Manufacturer();

	if(isset($_REQUEST["manufacturerid"]) && $_REQUEST["manufacturerid"] >0){
		$mfg->ManufacturerID=(isset($_POST['manufacturerid']) ? $_POST['manufacturerid'] : $_GET['manufacturerid']);
		$mfg->GetManufacturerByID();
	}

	$status="";
	if(isset($_POST["action"])&&(($_POST["action"]=="Create")||($_POST["action"]=="Update"))){
		$mfg->ManufacturerID=$_POST["manufacturerid"];
		$mfg->Name=trim($_POST["name"]);

		if($mfg->Name != null && $mfg->Name != ""){
			if($_POST["action"]=="Create"){
					$mfg->AddManufacturer();
			}else{
				$status="Updated";
				$mfg->UpdateManufacturer();
			}
		}
		//We either just created a manufacturer or updated it so reload from the db
		$mfg->GetManufacturerByID();
	}
	$mfgList=$mfg->GetManufacturerList();
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Device Class Templates</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>

  <script type="text/javascript">
	$(document).ready(function() {
		$('#mform').validationEngine({});
	});
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<h3>',$status,'</h3>
<div class="center"><div>
<form id="mform" action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div><label for="manufacturerid">',__("Manufacturer"),'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="manufacturerid" id="manufacturerid" onChange="form.submit()">
   <option value=0>',__("New Manufacturer"),'</option>';

	foreach($mfgList as $mfgRow){
		if($mfg->ManufacturerID==$mfgRow->ManufacturerID){$selected=" selected";}else{$selected="";}
		echo "<option value=\"$mfgRow->ManufacturerID\"$selected>$mfgRow->Name</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="name">',__("Name"),'</label></div>
   <div><input type="text" class="validate[required,minSize[1],maxSize[40]]" name="name" id="name" maxlength="40" value="',$mfg->Name,'"></div>
</div>
<div class="caption">';

	if($mfg->ManufacturerID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>';
	}else{
		echo '   <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
?>
</div>
</div><!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
