<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

if(!$person->SiteAdmin){
    // No soup for you.
    header('Location: '.redirect());
    exit;
}

	$subheader=__("XML Output for CFD Simulation");
	
	$datacenter=new DataCenter();
	$dcList=$datacenter->GetDCList();
	
	if(!isset($_REQUEST['datacenterid'])){
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
<?php include( 'header.inc.php' ); ?>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );

echo '
<div class="main">
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

}else{
	$cab=new Cabinet();
	$dev=new Device();
	
	$datacenter->DataCenterID=$_REQUEST["datacenterid"];
	$datacenter->GetDataCenter();
	$cab->DataCenterID=$datacenter->DataCenterID;
	$cabList=$cab->ListCabinetsByDC();
	
	header('Content-type: text/xml');
	header('Cache-Control: no-store, no-cache');
	header('Content-Disposition: attachment; filename="opendcim.xml"');
	
	print "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<datacenter>\n
	<DataCenterID>$datacenter->DataCenterID</DataCenterID>
	<Name>$datacenter->Name</Name>
	<Size>$datacenter->SquareFootage</Size>\n";
	
	foreach($cabList as $cabRow){
		print "\t<cabinet>
		<CabinetID>$cabRow->CabinetID</CabinetID>
		<Location>$cabRow->Location</Location>
		<Height>$cabRow->CabinetHeight</Height>
		<FrontEdge>$cabRow->FrontEdge</FrontEdge>
		<MapX1>$cabRow->MapX1</MapX1>
		<MapY1>$cabRow->MapY1</MapY1>
		<MapX2>$cabRow->MapX2</MapX2>
		<MapY2>$cabRow->MapY2</MapY2>\n";
		
		$dev->Cabinet=$cabRow->CabinetID;
		$devList=$dev->ViewDevicesByCabinet();
		
		$totalWatts=0;
		
		foreach($devList as $devRow){
			$power=$devRow->GetDeviceTotalPower();
			$totalWatts+=$power;
			print "\t\t<Device>
			<DeviceID>$devRow->DeviceID</DeviceID>
			<Label>$devRow->Label</Label>
			<Position>$devRow->Position</Position>
			<Height>$devRow->Height</Height>
			<Watts>$power</Watts>
		</Device>\n";
			
		}
		
		print "\t\t<TotalWatts>$totalWatts</TotalWatts>\n\t</cabinet>\n";
	}

	print "</datacenter>\n";
}
?>
