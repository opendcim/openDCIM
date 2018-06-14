<?php
	require_once( 'db.inc.php' );
	$devMode=true;
	require_once( 'facilities.inc.php' );

#	$sql="Select DeviceID from fac_Device WHERE DeviceID>=5000;";
#	foreach($dbh->query($sql) as $dcvrow){
#		$dev=new Device($dcvrow['DeviceID']);
#		$dev->DeleteDevice();
#	}

global $sessID;

// everyone hates error_log spam
if(session_id()==""){
    session_start();
}
$sessID = session_id();
session_write_close();

if (php_sapi_name()!="cli" && !isset($_GET["stage"]) && !isset($_GET["gauge"])) {
	JobQueue::startJob( $sessID );
?>
<center>
<iframe src="?gauge" height="250" width="220" scrolling="no" style="border: 0px;"></iframe> 
<?php
}elseif (php_sapi_name()!="cli" && isset($_GET["gauge"]) && !isset($_GET["stage"])){
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <style type="text/css"></style>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/gauge.min.js"></script>

<SCRIPT type="text/javascript" >
var timer;
var gauge;

$(document).ready( function() {
	gauge=new Gauge({
		renderTo: 'power-gauge',
		type: 'canv-gauge',
		title: '% Complete',
		minValue: '0',
		maxValue: '100',
		majorTicks: [ 0,10,20,30,40,50,60,70,80,90,100 ],
		minorTicks: '2',
		strokeTicks: false,
		units: '%',
		valueFormat: { int : 3, dec : 0 },
		glow: false,
		animation: {
			delay: 10,
			duration: 200,
			fn: 'bounce'
			},
		colors: {
			needle: {start: '#000', end: '#000' },
			title: '#00f',
			},
		highlights: [ {from: 0, to: 50, color: '#eaa'}, {from: 50, to: 80, color: '#fffacd'}, {from: 80, to: 100, color: '#0a0'} ],
		});
	gauge.draw().setValue(0);
    timer = setInterval( function() {
        $.ajax({
            type: 'GET',
            url: 'scripts/ajax_progress.php',
            dataType: 'json',
            success: function(data) {
                $("#status").text(data.Status);
				gauge.draw().setValue(data.Percentage);
                if ( data.Percentage >= 100 ) {
                    clearInterval(timer);
                    // Reload with Stage 3 to send the file to the user
                }
            }
        })
    }, 1500 );

    init=$('<iframe/>', {'src':location.href+'&stage=2', height:'100px',width:'100px'}).appendTo('body');
});
</script>
</head>
<body>
<div><canvas id="power-gauge" width="200" height="200"></canvas><h3><?php echo __("Building device image cache");?></h3</div>
</body>
</html>



<?php
}else{
	$dev=new Device();
	$dev->ParentDevice=0;
	$payload=$dev->Search();

	foreach($payload as $i => $device){
		$device->UpdateDeviceCache();
		JobQueue::updatePercentage( $sessID, ceil(($i/count($payload)*100)) );
	}
}
