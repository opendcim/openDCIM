<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	if(!isset($_GET["zone"])){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$cab=new Cabinet();
	$zone=new Zone();
	$dc=new DataCenter();

	$zone->ZoneID=$_GET["zone"];
	if (!$zone->GetZone()){
		header('Location: '.redirect());
		exit;
	}
	$zoneStats=$zone->GetZoneStatistics();
	$dc->DataCenterID=$zone->DataCenterID;
	$dc->GetDataCenterbyID();
	
	$height=0;
	$width=0;
	$ie8fix="";
	if(strlen($dc->DrawingFileName) >0){
		$mapfile="drawings/$dc->DrawingFileName";
		if(file_exists($mapfile)){
			list($width, $height, $type, $attr)=getimagesize($mapfile);
			// There is a bug in the excanvas shim that can set the width of the canvas to 10x the width of the image
			$ie8fix="
<script type=\"text/javascript\">
	function uselessie(){
		document.getElementById(\'mapCanvas\').className = \"mapCanvasiefix\";
	}
$(document).ready(function() {
	uselessie();
});
</script>
<style type=\"text/css\">
.mapCanvasiefix {
	    width: {$width}px !important;
}
</style>";
		}
	}
	// If no mapfile is set then we don't need the buttons to control drawing the map.  Adjust the CSS to hide them and make the heading centered
	if(strlen($dc->DrawingFileName) <1 || !file_exists("drawings/$dc->DrawingFileName")){
		$screenadjustment="<style type=\"text/css\">.dcstats .heading > div { width: 100% !important;} .dcstats .heading > div + div { display: none; }</style>";
	}
		
	if ( $config->ParameterArray["mUnits"] == "english" ) {
		$vol = __("Square Feet");
		$density = __("Watts per Square Foot");
	} else {
		$vol = __("Square Meters");
		$density = __("Watts per Square Meter" );
	}
	//aproximate proportion between zone/DC 
	$prop_zone_dc=($zone->MapX2-$zone->MapX1)*($zone->MapY2-$zone->MapY1)/$width/$height;
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Information Management</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lte IE 8]>
    <link rel="stylesheet"  href="css/ie.css" type="text/css">
    <?php if(isset($ie8fix)){print $ie8fix;} ?>
    <script src="scripts/excanvas.js"></script>
  <![endif]-->
  <?php print $zone->DrawCanvas();?>
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript">
	$(document).ready(function(){
		$('#mapCanvas').css('width', $('.canvas > img[alt="clearmap over canvas"]').width()+'px');
		$('#mapCanvas').parent('.canvas').css('width', $('.canvas > img[alt="clearmap over canvas"]').width()+'px');
	});
  </script>
</head>
<body>
<div id="header"></div>
<div class="page dcstats" id="mapadjust">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<div class="heading">
  <div>
	<h2>',$config->ParameterArray["OrgName"],'</h2>
	<h3>',__("Zone Statistics"),'</h3>
  </div>
  <div class="nav">
	<button onclick="loadCanvas()">',__("Overview"),'</button>
	<button onclick="space()">',__("Space"),'</button>
	<button onclick="weight()">',__("Weight"),'</button>
	<button onclick="power()">',__("Power"),'</button>
  </div>
</div>
<div class="center">
<div class="centermargin" id="dcstats">
<div class="table border">
  <div class="title">',$zone->Description,' (',$dc->Name,')</div>
  <div>
	<div></div>
	<div>',__("Infrastructure"),'</div>
	<div>',__("Occupied"),'</div>
	<div>',__("Allocated"),'</div>
	<div>',__("Available"),'</div>
  </div>
  <div>
	<div>',sprintf(__("Total U")." %5d",$zoneStats["TotalU"]),'</div>
	<div>',sprintf("%3d",$zoneStats["Infrastructure"]),'</div>
	<div>',sprintf("%3d",$zoneStats["Occupied"]),'</div>
	<div>',sprintf("%3d",$zoneStats["Allocated"]),'</div>
	<div>',sprintf("%3d",$zoneStats["Available"]),'</div>
  </div>
  <div>
	<div>',__("Percentage"),'</div>
	<div>',(($zoneStats["TotalU"])?sprintf("%3.1f%%",$zoneStats["Infrastructure"]/$zoneStats["TotalU"]*100):"0"),'</div>
	<div>',(($zoneStats["TotalU"])?sprintf("%3.1f%%",$zoneStats["Occupied"]/$zoneStats["TotalU"]*100):"0"),'</div>
	<div>',(($zoneStats["TotalU"])?sprintf("%3.1f%%",$zoneStats["Allocated"]/$zoneStats["TotalU"]*100):"0"),'</div>
	<div>',(($zoneStats["TotalU"])?sprintf("%3.1f%%",$zoneStats["Available"]/$zoneStats["TotalU"]*100):"0"),'</div>
  </div>
  </div> <!-- END div.table -->
  <div class="table border">
  <div>
        <div>',__("Computed Wattage"),'</div>
        <div>',sprintf("%7d %s", $zoneStats["ComputedWatts"], __("Watts")),'</div>
  </div>
  <div>
		<div>',__("Measured Wattage"), '</div>
		<div>',sprintf("%7d %s", $zoneStats["MeasuredWatts"], __("Watts")),'</div>
  </div>
  <div>
        <div>',__("BTU Computation from Computed Watts"),'</div>
        <div>',sprintf("%8d ".__("BTU"),$zoneStats["ComputedWatts"]*3.412 ),'</div>
  </div>
  <div>
        <div>',__("Zone Size (approximate)"),'</div>
        <div>',sprintf("%8d %s",$dc->SquareFootage*$prop_zone_dc, $vol),'</div>
  </div>
  <div>
        <div>',$density,' (',__("approximate"),')</div>
        <div>',(($dc->SquareFootage)?sprintf("%8d ".__("Watts"),$zoneStats["ComputedWatts"]/$dc->SquareFootage/$prop_zone_dc):"0 ".__("Watts")),'</div>
  </div>
  <div>
        <div>',__("Minimum Cooling Tonnage (Based on Computed Watts)"),'</div>
        <div>',sprintf("%7d ".__("Tons"),$zoneStats["ComputedWatts"]*3.412*1.15/12000),'</div>
  </div>
</div> <!-- END div.table -->
</div>';

  print $zone->MakeImageMap();
?>
</div>
</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
	$(document).ready(function() {
		var firstcabinet=$('#dc<?php echo $dc->DataCenterID;?> > ul > li:first-child').attr('id');
		// Don't attempt to open the datacenter tree until it is loaded
		function opentree(){
			if($('#datacenters .bullet').length==0){
				setTimeout(function(){
					opentree();
				},500);
			}else{
				expandToItem('datacenters',firstcabinet);
			}
		}
		loadCanvas();
		opentree();
	});
</script>
</body>
</html>
