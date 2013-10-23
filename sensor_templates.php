<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$template=new SensorTemplate();
	$manufacturer=new Manufacturer();

	if(isset($_REQUEST['templateid']) && $_REQUEST['templateid'] >0){
		$template = SensorTemplate::getTemplate( $_REQUEST["templateid"] );
	}

	$status='';
	if(isset($_REQUEST['action'])&&(($_REQUEST['action']=='Create')||($_REQUEST['action']=='Update'))){
		$template->ManufacturerID = $_REQUEST['manufacturerid'];
		$template->Name = transform( $_REQUEST['name'] );
		$template->SNMPVersion = $_REQUEST['snmpversion'];
		$template->TemperatureOID = $_REQUEST['temperatureoid'];
		$template->HumidityOID = $_REQUEST['humidityoid'];
		$template->TempMultiplier = $_REQUEST['multiplier'];
		$template->HumidityMultiplier = $_REQUEST['humiditymultiplier'];

		if ( $_REQUEST['action']=='Create' ) {
			$template->CreateTemplate();
		} else {
			$status=__('Updated');
			$template->UpdateTemplate();
		}
	}

	$templateList=$template->getTemplate();
	$ManufacturerList=$manufacturer->GetManufacturerList();

	// Set the default multipliers for new templates
	if ( $template->TempMultiplier == 0 ) {
		$template->TempMultiplier = 1;
	}
	
	if ( $template->HumidityMultiplier == 0 ) {
		$template->HumidityMultiplier = 1;
	}
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title><?php echo __("openDCIM Cabinet Temperature/Humidity Sensor Templates"); ?></title>
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
<h3>',__("openDCIM Cabinet Temperature/Humidity Sensor Templates"),'</h3>
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
		$manufacturer->GetManufacturerByID();
		$selected=($template->TemplateID==$templateRow->TemplateID)?' selected':'';
		print "		<option value=\"$templateRow->TemplateID\"$selected>[$manufacturer->Name] $templateRow->Name</option>\n";
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
   <div><label for="name">',__("Name"),'</label></div>
   <div><input type="text" name="name" id="name" value="',$template->Name,'" size=40></div>
</div>
<div>
	<div><label for="snmpversion">',__("SNMP Version"),'</label></div>
	<div><select name="snmpversion" id="snmpversion">';

	$snmpv = array( "1", "2c" );
	foreach ( $snmpv as $unit ) {
		$selected = ( $unit == $template->SNMPVersion ) ? 'selected':'';
		print "\t\t<option value=\"$unit\" $selected>$unit</option>\n";
	}
	
echo '</select>	
	</div>
</div>
<div>
   <div><label for="temperatureoid">',__("Temperature OID"),'</label></div>
   <div><input type="text" name="temperatureoid" id="temperatureoid" value="',$template->TemperatureOID,'" size=40></div>
</div>
<div>
   <div><label for="humidityoid">',__("Humidity OID"),'</label></div>
   <div><input type="text" name="humidityoid" id="humidityoid" value="',$template->HumidityOID,'" size=40></div>
</div>
<div>
   <div><label for="tempmultiplier">',__("Temperature Multiplier"),'</label></div>
   <div><select name="tempmultiplier" id="tempmultiplier">';

	foreach ( array( "0.1", "1", "10", "100" ) as $unit ) {
		$selected = ($unit==$template->TempMultiplier)?' selected' : '';
		print "\t\t<option value=\"$unit\"$selected>$unit</option>\n";
	}
	
echo '   </select>
   </div>
</div>
<div>
   <div><label for="humiditymultiplier">',__("Humidity Multiplier"),'</label></div>
   <div><select name="humiditymultiplier" id="humiditymultiplier">';

	foreach ( array( "0.1", "1", "10", "100" ) as $unit ) {
		$selected = ($unit==$template->HumidityMultiplier)?' selected' : '';
		print "\t\t<option value=\"$unit\"$selected>$unit</option>\n";
	}
	
echo '   </select>
   </div>
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
