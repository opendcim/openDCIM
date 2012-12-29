<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->WriteAccess){
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
		$template->Model = transform( $_REQUEST['model'] );
		$template->Height = $_REQUEST['height'];
		$template->Weight = $_REQUEST['weight'];
		$template->Wattage = $_REQUEST['wattage'];
		$template->DeviceType = $_REQUEST['devicetype'];
		$template->PSCount = $_REQUEST['pscount'];
		$template->NumPorts = $_REQUEST['numports'];

		if ( $_REQUEST['action']=='Create' ) {
			$template->CreateTemplate($facDB);
		} else {
			$status=_('Updated');
			$template->UpdateTemplate($facDB);
		}
	}

	$templateList=$template->GetTemplateList($facDB);
	$ManufacturerList=$manufacturer->GetManufacturerList($facDB);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Device Class Templates</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',_("Data Center Device Templates"),'</h3>
<h3>',$status,'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
	<div>
		<div><label for="templateid">',_("Template"),'</label></div>
		<div><input type="hidden" name="action" value="query"><select name="templateid" id="templateid" onChange="form.submit()">
		<option value=0>',_("New Template"),'</option>';

	foreach($templateList as $templateRow){
		$manufacturer->ManufacturerID=$templateRow->ManufacturerID;
		$manufacturer->GetManufacturerByID($facDB);
		if($template->TemplateID==$templateRow->TemplateID){$selected=" selected";}else{$selected="";}
		print "		<option value=\"$templateRow->TemplateID\"$selected>[$manufacturer->Name] $templateRow->Model</option>\n";
	}

echo '	</select></div>
</div>
<div>
	<div><label for="manufacturerid">',_("Manufacturer"),'</label></div>
	<div><select name="manufacturerid" id="manufacturerid">';

	foreach($ManufacturerList as $ManufacturerRow){
		if($template->ManufacturerID==$ManufacturerRow->ManufacturerID){$selected="selected";}else{$selected="";}
		print "		<option value=\"$ManufacturerRow->ManufacturerID\"$selected>$ManufacturerRow->Name</option>\n";
	}

echo '    </select>    
   </div>
</div>
<div>
   <div><label for="model">',_("Model"),'</label></div>
   <div><input type="text" name="model" id="model" value="',$template->Model,'"></div>
</div>
<div>
   <div><label for="height">',_("Height"),'</label></div>
   <div><input type="text" name="height" id="height" value="',$template->Height,'"></div>
</div>
<div>
   <div><label for="weight">',_("Weight"),'</label></div>
   <div><input type="text" name="weight" id="weight" value="',$template->Weight,'"></div>
</div>
<div>
   <div><label for="wattage">',_("Wattage"),'</label></div>
   <div><input type="text" name="wattage" id="wattage" value="',$template->Wattage,'"></div>
</div>
<div>
   <div><label for="devicetype">',_("Device Type"),'</label></div>
   <div><select name="devicetype" id="select">';

	foreach(array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure') as $DevType){
		if($DevType==$template->DeviceType){$selected=" selected";}else{$selected="";}
		print "		<option value=\"$DevType\"$selected>$DevType</option>\n";
	}

echo '	</select>
   </div>
</div>
<div>
   <div><label for="pscount">',_("No. Power Supplies"),'</label></div>
   <div><input type="text" name="pscount" id="pscount" value="',$template->PSCount,'"></div>
</div>
<div>
   <div><label for="numports">',_("No. Ports"),'</label></div>
   <div><input type="text" name="numports" id="numports" value="',$template->NumPorts,'"></div>
</div>
<div class="caption">';

	if($template->TemplateID >0){
		echo '   <button type="submit" name="action" value="Update">',_("Update"),'</button>';
	}else{
		echo '	 <button type="submit" name="action" value="Create">',_("Create"),'</button>';
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
