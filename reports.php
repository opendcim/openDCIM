<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$user = new User();
	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	if(!$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Inventory Reporting</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page reports">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',_("Inventory Reports"),'</h3>
<div class="center"><div id="reports">
<div>
<fieldset>
	<legend>',_("Contact Reports"),'</legend>
		<a href="report_department.php">',_("Department/Contact Report"),'</a>
</fieldset>
<fieldset>
<legend>Asset Reports</legend>
	<a href="report_contact.php">',_("Asset Report by Owner"),'</a>
	<a href="report_asset.php">',_("Data Center Asset Report"),'</a>
	<a href="report_cost.php">',_("Data Center Asset Costing Report"),'</a>
	<a href="report_aging.php">',_("Asset Aging Report"),'</a>
	<a href="report_vm_by_department.php">',_("Virtual Machines by Department"),'</a>
</fieldset>
</div>

<div>
<fieldset>
<legend>Operational Reports</legend>
	<a href="report_exception.php">',_("Data Exceptions Report"),'</a>
	<a href="report_diverse_power_exceptions.php">',_("Diverse Power Exceptions Report"),'</a>
	<a href="report_outage_simulator.php">',_("Simulated Power Outage Report"),'</a>
	<a href="report_power_distribution.php">',_("Power Distribution by Data Center"),'</a>
	<a href="report_power_utilization.php">',_("Server Tier Classification Report"),'</a>
</fieldset>
<fieldset>
	<legend>Auditing Reports</legend>
		<a href="report_audit.php">',_("Cabinet Audit Logs"),'</a>
		<a href="report_audit_frequency.php">',_("Cabinet Audit Frequency"),'</a>
		<a href="report_surplus.php">',_("Surplus/Salvage Audit Report"),'</a>
</fieldset>
</div>';

?>


</div></div>

</div>
<div class="clear"></div>
</div>


</body>
</html>
