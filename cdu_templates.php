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

	$template=new CDUTemplate();
	$manufacturer=new Manufacturer();

	if(isset($_REQUEST['templateid']) && $_REQUEST['templateid'] >0){
		$template->TemplateID=$_REQUEST['templateid'];
		$template->GetTemplate($facDB);
	}

	$status='';
	if(isset($_REQUEST['action'])&&(($_REQUEST['action']=='Create')||($_REQUEST['action']=='Update'))){
		$template->ManufacturerID = $_REQUEST['manufacturerid'];
		$template->Model = transform( $_REQUEST['model'] );
		$template->Managed = isset($_REQUEST['managed'])?1:0;
		$template->VersionOID = $_REQUEST['versionoid'];
		$template->Multiplier = $_REQUEST['multiplier'];
		$template->OID1 = $_REQUEST['oid1'];
		$template->OID2 = $_REQUEST['oid2'];
		$template->OID3 = $_REQUEST['oid3'];
		$template->ProcessingProfile = $_REQUEST['processingprofile'];
		$template->Voltage = $_REQUEST["voltage"];
		$template->Amperage = $_REQUEST["amperage"];
		$template->NumOutlets = $_REQUEST["numoutlets"];

		if ( $_REQUEST['action']=='Create' ) {
			$template->CreateTemplate($facDB);
		} else {
			$status=__('Updated');
			$template->UpdateTemplate($facDB);
		}
	}

	$templateList=$template->GetTemplateList($facDB);
	$ManufacturerList=$manufacturer->GetManufacturerList($facDB);

	$checked=($template->Managed)?" checked":"";
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Cabinet Distribution Unit Templates</title>
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
<h3>',__("Data Center Cabinet Distribution Unit Templates"),'</h3>
<h3>',$status,'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
	<div>
		<div><label for="templateid">',__("Template"),'</label></div>
		<div><input type="hidden" name="action" value="query"><select name="templateid" id="templateid" onChange="form.submit()">
		<option value=0>',__("New Template"),'</option>';

	foreach($templateList as $templateRow){
		$manufacturer->ManufacturerID=$templateRow->ManufacturerID;
		$manufacturer->GetManufacturerByID($facDB);
		$selected=($template->TemplateID==$templateRow->TemplateID)?' selected':'';
		print "		<option value=\"$templateRow->TemplateID\"$selected>[$manufacturer->Name] $templateRow->Model</option>\n";
	}

echo '	</select></div>
</div>
<div>
	<div><label for="manufacturerid">',__("Manufacturer"),'</label></div>
	<div><select name="manufacturerid" id="manufacturerid">';

	foreach($ManufacturerList as $ManufacturerRow){
		$selected=($template->ManufacturerID==$ManufacturerRow->ManufacturerID)?' selected':'';
		print "		<option value=\"$ManufacturerRow->ManufacturerID\"$selected>$ManufacturerRow->Name</option>\n";
	}

echo '    </select>    
   </div>
</div>
<div>
   <div><label for="model">',__("Model"),'</label></div>
   <div><input type="text" name="model" id="model" value="',$template->Model,'" size=40></div>
</div>
<div>
   <div><label for="managed">',__("Managed"),'</label></div>
   <div>
		<input type="checkbox" name="managed" id="managed"',$checked,'>
   </div>
</div>
<div>
	<div><label for="versionoid">',__("Firmware Version OID"),'</label></div>
	<div><input type="text" name="versionoid" id="versionoid" value="',$template->VersionOID,'" size=40></div>
</div>
<div>
   <div><label for="multiplier">',__("Multiplier"),'</label></div>
   <div><select name="multiplier" id="multiplier">';
   
	$Multi=array("0.1", "1","10","100");
        $mult = 1;

        // Loop to find the template default multiplier, if any
        foreach($Multi as $unit) {
            //$selected = ($unit==$template->Multiplier)?' selected' : '';
            if ($unit == $template->Multiplier) {
                $mult = $template->Multiplier;
                break;
            }
	}
        // Set the "selected" option, using $mult as set above
	foreach($Multi as $unit){
            $selected = ( $unit == $mult ) ? ' selected' : '';
            print "\t\t<option value=\"$unit\"$selected>$unit</option>\n";
        }
	
echo '   </select>
   </div>
</div>
<div>
   <div><label for="oid1">',__("OID1"),'</label></div>
   <div><input type="text" name="oid1" id="oid1" value="',$template->OID1,'" size=40></div>
</div>
<div>
   <div><label for="oid2">',__("OID2"),'</label></div>
   <div><input type="text" name="oid2" id="oid2" value="',$template->OID2,'" size=40></div>
</div>
<div>
   <div><label for="oid3">',__("OID3"),'</label></div>
   <div><input type="text" name="oid3" id="oid3" value="',$template->OID3,'" size=40></div>
</div>
<div>
   <div><label for="processingprofile">',__("Processing Scheme"),'</label></div>
   <div><select name="processingprofile" id="processingprofile">';

	$ProfileList=array("SingleOIDWatts","SingleOIDAmperes","Combine3OIDWatts","Combine3OIDAmperes","Convert3PhAmperes");
	foreach($ProfileList as $prof){
		$selected=($prof == $template->ProcessingProfile)?' selected':'';
		print "<option value=\"$prof\"$selected>$prof</option>";
	}
	
echo '   </select></div>
</div>
<div>
   <div><label for="voltage">',__("Voltage"),'</label></div>
   <div><input type="text" name="voltage" id="voltage" value="',$template->Voltage,'"></div>
</div>
<div>
   <div><label for="amperage">',__("Amperage"),'</label></div>
   <div><input type="text" name="amperage" id="amperage" value="',$template->Amperage,'"></div>
</div>
<div>
   <div><label for="numoutlets">',__("No. of Outlets"),'</label></div>
   <div><input type="text" name="numoutlets" id="numoutlets" value="',$template->NumOutlets,'"></div>
</div>
<div class="caption">';

	if($template->TemplateID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>';
	}else{
		echo '	 <button type="submit" name="action" value="Create">',__("Create"),'</button>';
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
