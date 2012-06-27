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
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Inventory Reporting</title>
  <link rel="stylesheet" href="css/inventory.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
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
<h2>openDCIM</h2>
<h3>Inventory Reports</h3>
<div class="center"><div id="reports">
<table bgcolor="white" border="1" align="center">
  <tr>
	<td align="center" bgcolor="#7f7f7f"><font color="white"><b>Asset Reports</b></font></td>
  </tr>
  <tr>
    <td><a href="contact_report.php">Contact and Asset Report by Owner</a></td>
  </tr>
  <tr>
    <td><a href="asset_report.php">Data Center Asset Report</a></td>
  </tr>
  <tr>
    <td><a href="cost_report.php">Data Center Asset and Contact Report</a></td>
  </tr>
  <tr>
    <td><a href="assets_by_department.php">Data Center Assets By Department</a></td>
  </tr>
  
</table>
<p>
<table bgcolor="white" border="1" align="center">
  <tr>
	<td align="center" bgcolor="#7f7f7f"><font color="white"><b>Operational Reports</b></font></td>
  </tr>
  <tr>
    <td><a href="exception_report.php">Data Exceptions Report</a></td>
  </tr>
  <tr>
    <td><a href="diverse_power_exceptions_report.php">Diverse Power Exceptions Report</a></td>
  </tr>  
  <tr>
    <td><a href="outage_simulator.php">Simulated Power Outage Report</a></td>
  </tr>  
  <tr>
    <td><a href="power_distribution_report.php">Power Distribution by Data Center</a></td>
  </tr>
  <tr>
    <td><a href="power_utilization_report.php">Server Tier Classification Report</a></td>
  </tr>
  <tr>
    <td><a href="aging_report.php">Asset Aging Report</a></td>
  </tr>
</table>

</div></div>

</div>
<div class="clear"></div>
</div>


</body>
</html>
