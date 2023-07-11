<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
	$subheader=__("Data Center Operations Metrics");

$dc=new DataCenter();
$dcList=$dc->search(true);

$cabList=Cabinet::ListCabinets(false, true);

$dev=new Device();
$dev->DeviceType="Sensor";
$devList=$dev->search();

$sr=new SensorReadings();
$sensorReadings=$sr->search(true);

$tableheader="\t<thead>
		<tr>
			<th>".__("Data Center")."</th>
			<th>".__("Location")."</th>
			<th>".__("Device")."</th>
			<th>".__("Temperature")."</th>
			<th>".__("Humidity")."</th>
			<th>".__("Last Read")."</th>
		</tr>
	</thead>
	<tbody>\n";

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.dataTables.min.css" type="text/css">
  <style type="text/css">
    div.dt-buttons { float: left; }
    #export_filter { float: left; margin-left: 25px; }
  </style>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="scripts/pdfmake.min.js"></script>
  <script type="text/javascript" src="scripts/vfs_fonts.js"></script>
  <script type="text/javascript">
	$(document).ready(function(){
		function dt(){
			var title='sensors export';
			$('#sensors').dataTable({
				"drawCallback": function( settings ) {
					redraw();resize();
				},
				dom: 'B<"clear">lfrtip',
				buttons:{
					buttons: [
						'copy',
						{
							extend: 'excel',
							title: title
						},
						{
							extend: 'pdf',
							title: title
						},'csv', 'colvis', 'print'
					]
				}
			});
		}
		function redraw(){
			if(($('#sensors').outerWidth()+$('#sidebar').outerWidth()+10)<$('.page').innerWidth()){
				$('.main').width($('#header').innerWidth()-$('#sidebar').outerWidth()-16);
			}else{
				$('.main').width($('#sensors').outerWidth()+40);
			}
			$('.page').width($('.main').outerWidth()+$('#sidebar').outerWidth()+10);
		}
		dt();
	});
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<div class="center"><div>



<table id="sensors">
<?php
print ("$tableheader");
foreach( $devList as $d ) {
	print ("\t\t<tr>
			<td>{$dcList[$cabList[$d->Cabinet]->DataCenterID]->Name}</td>
			<td>{$cabList[$d->Cabinet]->Location}</td>
			<td>{$d->Label}</td>
			<td>{$sensorReadings[$d->DeviceID]->Temperature}</td>
			<td>{$sensorReadings[$d->DeviceID]->Humidity}</td>
			<td>{$sensorReadings[$d->DeviceID]->LastRead}</td>
		</tr>\n");
}
?>
	</tbody>
</table>

</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
