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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
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
?>
<div class="main">
<h2><?php print $config->ParameterArray["OrgName"]; ?></h2>
<h3>Inventory Reports</h3>
<div class="center"><div id="reports">
<div>
<fieldset>
	<legend>Contact Reports</legend>
		<a href="department_report.php">Department/Contact Report</a>
</fieldset>
<fieldset>
<legend>Asset Reports</legend>
	<a href="contact_report.php">Asset Report by Owner</a>
	<a href="asset_report.php">Data Center Asset Report</a>
	<a href="cost_report.php">Data Center Asset Costing Report</a>
	<a href="assets_by_department.php">Data Center Assets By Department</a>
	<a href="aging_report.php">Asset Aging Report</a>
</fieldset>
</div>

<div>
<fieldset>
<legend>Operational Reports</legend>
	<a href="exception_report.php">Data Exceptions Report</a>
	<a href="diverse_power_exceptions_report.php">Diverse Power Exceptions Report</a>
	<a href="outage_simulator.php">Simulated Power Outage Report</a>
	<a href="power_distribution_report.php">Power Distribution by Data Center</a>
	<a href="power_utilization_report.php">Server Tier Classification Report</a>
</fieldset>
<fieldset>
	<legend>Auditing Reports</legend>
		<a href="audit_report.php">Cabinet Audit Logs</a>
		<a href="audit_frequency.php">Cabinet Audit Frequency</a>
		<a href="surplus_report.php">Surplus/Salvage Audit Report</a>
</fieldset>
</div>


</div></div>

</div>
<div class="clear"></div>
</div>


</body>
</html>
