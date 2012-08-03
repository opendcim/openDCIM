<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$template=new DeviceTemplate();
	$manufacturer=new Manufacturer();

	if(isset($_REQUEST['templateid']) && $_REQUEST['templateid'] >0){
		$template->TemplateID=$_REQUEST['templateid'];
		$template->GetTemplateByID($facDB);
	}

	$status='';
	if(isset($_REQUEST['action'])&&(($_REQUEST['action']=='Create')||($_REQUEST['action']=='Update'))){
		$template->ManufacturerID = $_REQUEST['manufacturerid'];
		$template->Model = strtoupper( $_REQUEST['model'] );
		$template->Height = $_REQUEST['height'];
		$template->Weight = $_REQUEST['weight'];
		$template->Wattage = $_REQUEST['wattage'];
		$template->DeviceType = $_REQUEST['devicetype'];
		$template->PSCount = $_REQUEST['pscount'];
		$template->NumPorts = $_REQUEST['numports'];

		if ( $_REQUEST['action']=='Create' ) {
			$template->CreateTemplate($facDB);
		} else {
			$status='Updated';
			$template->UpdateTemplate($facDB);
		}
	}

	$templateList=$template->GetTemplateList($facDB);
	$ManufacturerList=$manufacturer->GetManufacturerList($facDB);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
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
	include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Device Templates</h3>
<h3><?php echo $status; ?></h3>
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<div class="table">
	<div>
		<div><label for="templateid">Template</label></div>
		<div><input type="hidden" name="action" value="query"><select name="templateid" id="templateid" onChange="form.submit()">
		<option value=0>New Template</option>
<?php
	foreach($templateList as $templateRow){
		$manufacturer->ManufacturerID=$templateRow->ManufacturerID;
		$manufacturer->GetManufacturerByID($facDB);
		print "<option value=\"$templateRow->TemplateID\"";
		if($template->TemplateID==$templateRow->TemplateID){echo ' selected="selected"';}
		print ">[$manufacturer->Name] $templateRow->Model</option>\n";
	}
?>
	</select></div>
</div>
<div>
	<div><label for="manufacturerid">Manufacturer</label></div>
	<div><select name="manufacturerid" id="manufacturerid">
<?php
	foreach($ManufacturerList as $ManufacturerRow){
		print "<option value=\"$ManufacturerRow->ManufacturerID\"";
		if($template->ManufacturerID==$ManufacturerRow->ManufacturerID){echo ' selected="selected"';}
		print ">$ManufacturerRow->Name</option>\n";
	}
?>
    </select>    
   </div>
</div>
<div>
   <div><label for="model">Model</label></div>
   <div><input type="text" name="model" id="model" value="<?php echo $template->Model; ?>"></div>
</div>
<div>
   <div><label for="height">Height</label></div>
   <div><input type="text" name="height" id="height" value="<?php echo $template->Height; ?>"></div>
</div>
<div>
   <div><label for="weight">Weight</label></div>
   <div><input type="text" name="weight" id="weight" value="<?php echo $template->Weight; ?>"></div>
</div>
<div>
   <div><label for="wattage">Wattage</label></div>
   <div><input type="text" name="wattage" id="wattage" value="<?php echo $template->Wattage; ?>"></div>
</div>
<div>
   <div><label for="devicetype">Device Type</label></div>
   <div><select name="devicetype" id="select">
<?php
	foreach ( array( 'Server', 'Appliance', 'Storage Array', 'Switch', 'Chassis', 'Patch Panel', 'Physical Infrastructure' ) as $DevType ) {
		if ( $DevType == $template->DeviceType )
			$selected = "SELECTED";
		else
			$selected = "";
			
		printf( "<option value=\"%s\" %s>%s</option>\n", $DevType, $selected, $DevType );
	}
?>
	</select>
   </div>
</div>
<div>
   <div><label for="pscount">No. Power Supplies</label></div>
   <div><input type="text" name="pscount" id="pscount" value="<?php echo $template->PSCount; ?>"></div>
</div>
<div>
   <div><label for="numports">No. Ports</label></div>
   <div><input type="text" name="numports" id="numports" value="<?php echo $template->NumPorts; ?>"></div>
</div>
<div class="caption">
<?php
	if($template->TemplateID >0){
		echo '   <input type="submit" name="action" value="Update">';
	}
?>
   <input type="submit" name="action" value="Create">
</div>
</div><!-- END div.table -->
</form>
</div></div>
<a href="index.php">[ Return to Main Menu ]</a>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
