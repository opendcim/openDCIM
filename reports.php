<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$subheader=__("Inventory Reports");
	
	if(!$person->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

  <title>openDCIM Inventory Reporting</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page reports">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<div class="center"><div id="reports">
<div>
<fieldset>
	<legend>',__("Contact Reports"),'</legend>
		<a href="report_department.php">',__("Department/Contact Report"),'</a>
</fieldset>
<fieldset>
<legend>',__("Asset Reports"),'</legend>
	<a href="search_export.php">',__("Search/Export by Data Center"),'</a>
	<a href="search_export_storage_room.php">',__("Storage Room Search/Export by Data Center"),'</a>
	<a href="report_xml_CFD.php">',__("Export Data Center for CFD (XML)"),'</a>
	<a href="report_contact.php">',__("Asset Report by Owner"),'</a>
	<a href="report_asset.php">',__("Data Center Asset Report"),'</a>
    <a href="report_asset_Excel.php">',__("Data Center Asset Report [Excel]"),'</a>
	<a href="report_cost.php">',__("Data Center Asset Costing Report"),'</a>
	<a href="report_aging.php">',__("Asset Aging Report"),'</a>
	<a href="report_projects.php">',__("Project Asset Report"),'</a>
    <a href="report_warranty.php">',__("Warranty Expiration Report"),'</a>
	<a href="report_vm_by_department.php">',__("Virtual Machines by Department"),'</a>
	<a href="report_network_map.php">',__("Network Map"),'</a>
	<a href="report_vendor_model.php">', __("Vendor/Model Report"),'</a>
</fieldset>
</div>

<div>
<fieldset>
<legend>',__("Operational Reports"),'</legend>
	<a href="report_exception.php">',__("Data Exceptions Report"),'</a>
	<a href="report_diverse_power_exceptions.php">',__("Diverse Power Exceptions Report"),'</a>
	<a href="report_outage_simulator.php">',__("Simulated Power Outage Report"),'</a>
	<a href="report_project_outage_simulator.php">',__("Project Power Outage Report"),'</a>
	<a href="report_power_distribution.php">',__("Power Distribution by Data Center"),'</a>
	<a href="report_power_utilization.php">',__("Server Tier Classification Report"),'</a>
    <a href="report_panel_schedule.php">',__("Power Panel Schedule Report"),'</a>
    <a href="report_cabinets.php">',__("Cabinet List"),'</a>
</fieldset>
<fieldset>
	<legend>',__("Auditing Reports"),'</legend>
		<a href="report_audit.php">',__("Cabinet Audit Logs"),'</a>
		<a href="report_audit_frequency.php">',__("Cabinet Audit Frequency"),'</a>
		<a href="report_surplus.php">',__("Surplus/Salvage Audit Report"),'</a>
		<a href="report_supply_status.php">',__("Supplies Status Report"),'</a>
		<a href="report_logging.php">',__("Actions Log"),'</a>
</fieldset>
</div>';

if ( file_exists( $config->ParameterArray["reportspath"] . "localreports.json" ) ) {
	// There's a defined file for local reports, so read and display.
	
	echo '
<div>
<fieldset>
<legend>',__("Local Reports"),'</legend>';

	$reportRaw = file_get_contents( $config->ParameterArray["reportspath"] . "localreports.json" );
	$reportJSON = json_decode( $reportRaw, true );

	$array = json_decode($reportRaw,true);

	foreach($array as $k=>$val) {
		// Look for a parameter and trim it off in the file_exists check
                if ( strpos( $val["FileName"], "?" ) > 0 ) {
                        $checkFile = substr( $val["FileName"], 0, strpos( $val["FileName"], "?" ));
                } else {
                        $checkFile = $val["FileName"];
                }

		if ( isset( $val["DisplayName"] ) && isset( $val["FileName"] ) && file_exists( $config->ParameterArray["reportspath"] . $checkFile ) ) {
			echo '		<a href="',$config->ParameterArray["reportspath"] . $val["FileName"],'">',$val["DisplayName"],'</a>';
		}
	}
	
	echo '
</fieldset>
</div>';

}

?>


</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div>
<div class="clear"></div>
</div>


</body>
</html>
