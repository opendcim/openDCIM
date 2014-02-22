<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );
	
	$datacenter = new DataCenter();
	$dcList = $datacenter->GetDCList();
	
	$cab = new Cabinet();
	$dev = new Device();
	
	if (!isset($_REQUEST['datacenterid'])) {
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>

  <script type="text/javascript">
	$(document).ready(function(){
		$('#generate').hide();
		$('select[name="datacenterid"]').change(function(){
			if($(this).val()!=""){
				$('#generate').show();
			}else{
				$('#generate').hide();
			}
		});
	});
  </script>
</head>
<body>
<div id="header"></div>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );

echo '
<div class="main">
<h2>'.$config->ParameterArray['OrgName'].'</h2>
<h3>'.__("XML Output for CFD Simulation").'</h3>
<div class="center"><div>
<form method="post">
<div class="table">
	<div>
		<div>'.__("Data Center").'</div>
		<div>
			<select name="datacenterid">
				<option value="">'.__("Select Data Center").'</option>';
				foreach($dcList as $dc){
					print "\t\t\t\t<option value=\"$dc->DataCenterID\">$dc->Name</option>\n";
				}
echo '			</select>
		</div>
		<div>
			<button id="generate" type="submit">'.__("Generate").'</button>
		</div>
	</div>
</div>		
</form>
</div> <!-- END .center -->
</div> <!-- END .main -->
</div> <!-- END .page -->
</body>
</html>';


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
	printf( "\t<Size>%d</Size>\n", $datacenter->SquareFootage );
	
	$cab->DataCenterID = $datacenter->DataCenterID;
	$cabList = $cab->ListCabinetsByDC();
	
	foreach ( $cabList as $cabRow ) {
		print "\t<cabinet>\n";
		printf( "\t\t<ID>%d</ID>\n", $cabRow->CabinetID );
		printf( "\t\t<Location>%s</Location>\n", $cabRow->Location );
		printf( "\t\t<Height>%d</Height>\n", $cabRow->CabinetHeight );
		printf( "\t\t<FrontEdge>%s</FrontEdge>\n", $cabRow->FrontEdge );
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
