<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );
	
	$user = new User();
	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights();

	if(!$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	
	$datacenter = new DataCenter();
	$dcList = $datacenter->GetDCList();
	
	$cab = new Cabinet();
	$dev = new Device();
	
	if (!isset($_REQUEST['action'])) {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>openDCIM Inventory Reporting</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>

</head>
<body>
<div style="height: 66px;" id="header"></div>
<?php
	include( 'sidebar.inc.php' );
	
?>
</div>
<div class="main">
<h2>openDCIM</h2>
<h3><?php __("XML Output for CFD Simulation"); ?></h3>
<form action="<?php printf( "%s", $_SERVER['PHP_SELF'] ); ?>" method="post">
<table align="center" border=0>
<?php
	if ( @$_REQUEST['datacenterid'] == 0 ) {
		printf( "<tr><td>%s:</td><td>\n", __("Data Center") );
		printf( "<select name=\"datacenterid\" onChange=\"form.submit()\">\n" );
		printf( "<option value=\"\">%s</option>\n", __("Select data center") );
		
		foreach ( $dcList as $dc )
			printf( "<option value=\"%d\">%s</option>\n", $dc->DataCenterID, $dc->Name );
		
		printf( "</td></tr>" );
		
	} else {
		$datacenter->DataCenterID = $_REQUEST['datacenterid'];
		$datacenter->GetDataCenter();

		printf( "<h3>%s : %s</h3>\n", __("Data Center"), $datacenter->Name );
		
		printf( "<input type=\"hidden\" name=\"datacenterid\" value=\"%d\">\n", $datacenter->DataCenterID );
		
		printf( "<input type=submit name=\"action\" value=\"%s\"><br>\n", __("Generate") );
	}

?>
</table>
</form>
<?php
} else {
	$datacenter->DataCenterID = $_REQUEST["datacenterid"];
	$datacenter->GetDataCenter();
	
	header("Content-type: text/xml");
	header("Cache-Control: no-store, no-cache");
	header('Content-Disposition: attachment; filename="opendcim.xml"');
	
	print "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	print "<datacenter>\n";
	printf( "\t<ID>%d</ID>\n", $datacenter->DataCenterID );
	printf( "\t<Name>%s</Name>\n", $datacenter->Name );
	
	$cab->DataCenterID = $datacenter->DataCenterID;
	$cabList = $cab->ListCabinetsByDC();
	
	foreach ( $cabList as $cabRow ) {
		print "\t<cabinet>\n";
		printf( "\t\t<ID>%d</ID>\n", $cabRow->CabinetID );
		printf( "\t\t<Location>%s</Location>\n", $cabRow->Location );
		printf( "\t\t<Height>%d</Height>\n", $cabRow->CabinetHeight );
		printf( "\t\t<MapX1>%d</MapX1>\n", $cabRow->MapX1 );
		printf( "\t\t<MapY1>%d</MapY1>\n", $cabRow->MapY1 );
		printf( "\t\t<MapX2>%d</MapX2>\n", $cabRow->MapX2 );
		printf( "\t\t<MapY2>%d</MapY2>\n", $cabRow->MapY2 );
		
		$dev->Cabinet = $cabRow->CabinetID;
		$devList = $dev->ViewDevicesByCabinet( false );
		
		$totalWatts = 0;
		
		foreach ( $devList as $devRow ) {
			print "\t\t<Device>\n";
			printf( "\t\t\t<ID>%d</ID>\n", $devRow->DeviceID );
			printf( "\t\t\t<Label>%s</Label>\n", $devRow->Label );
			printf( "\t\t\t<Position>%d</Position>\n", $devRow->Position );
			printf( "\t\t\t<Height>%d</Height>\n", $devRow->Height );
			printf( "\t\t\t<Watts>%d</Watts>\n", $devRow->GetDeviceTotalPower() );
			print "\t\t</Device>\n";
			
			$totalWatts += $devRow->GetDeviceTotalPower();
		}
		
		printf( "\t\t<TotalWatts>%d</TotalWatts>\n", $totalWatts );
		print "\t</cabinet>\n";
	}

	print "</datacenter>\n";
	print "</xml>\n";
}
?>