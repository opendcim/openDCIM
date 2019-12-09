<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$subheader=__("Data Center Statistics");

	if(!isset($_GET["container"])){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$c=New Container();
	
	$c->ContainerID=$_GET["container"];
	$c->GetContainer();
	$cStats=$c->GetContainerStatistics();

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Information Management</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lte IE 8]>
    <link rel="stylesheet"  href="css/ie.css" type="text/css">
    <script src="scripts/excanvas.js"></script>
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page dcstats" id="mapadjust">
<?php
include( "sidebar.inc.php" );

if ( $config->ParameterArray["mUnits"] == "english" ) {
    $vol = __("Square Feet");
    $volunit = "ft&sup2;";
    $density = __("Watts per Square Foot");
    $densunit = "W/ft&sup2;";	
} else {
    $vol = __("Square Meters");
    $volunit = "m&sup2;";
    $density = __("Watts per Square Meter");
    $densunit = "W/m&sup2;";
}

echo '<div class="main">
<div class="center"><div>
<div class="centermargin" id="dcstats">
<div class="table border">
  <div class="title">',$c->Name,'</div>
  <div>
	<div></div>
	<div>',__("Infrastructure"),'</div>
	<div>',__("Occupied"),'</div>
	<div>',__("Allocated"),'</div>
	<div>',__("Available"),'</div>
  </div>
  <div>
	<div>',sprintf(__("Total U")." %s",number_format($cStats["TotalU"])),'</div>
	<div align="right">',sprintf("%s",number_format($cStats["Infrastructure"])),'</div>
	<div align="right">',sprintf("%s",number_format($cStats["Occupied"])),'</div>
	<div align="right">',sprintf("%s",number_format($cStats["Allocated"])),'</div>
	<div align="right">',sprintf("%s",number_format($cStats["Available"])),'</div>
  </div>
  <div>
	<div>',__("Percentage"),'</div>
	<div align="right">',(($cStats["TotalU"])?sprintf("%3.1f%%",$cStats["Infrastructure"]/$cStats["TotalU"]*100):"0"),'</div>
	<div align="right">',(($cStats["TotalU"])?sprintf("%3.1f%%",$cStats["Occupied"]/$cStats["TotalU"]*100):"0"),'</div>
	<div align="right">',(($cStats["TotalU"])?sprintf("%3.1f%%",$cStats["Allocated"]/$cStats["TotalU"]*100):"0"),'</div>
	<div align="right">',(($cStats["TotalU"])?sprintf("%3.1f%%",$cStats["Available"]/$cStats["TotalU"]*100):"0"),'</div>
  </div>
  </div> <!-- END div.table -->
  <div class="table border">
  <div>
        <div>',__("Data Centers"),'</div>
        <div>',sprintf("%s ",number_format($cStats["DCs"])),'</div>
  </div>
  <div>
        <div>',__("Computed Wattage"),'</div>
        <div>',sprintf("%s",number_format($cStats["ComputedWatts"]/1000)),' kW</div>
  </div>
  <div>
		<div>',__("Measured Wattage"), '</div>
		<div>',sprintf("%s",number_format($cStats["MeasuredWatts"]/1000)),' kW</div>
  </div>
    <div>
		<div>',__("Design Maximum"),'</div>
		<div>',sprintf("%s",number_format($cStats["MaxkW"])),' kW</div>
  </div>
  <div>
        <div>',__("BTU Computation from Watts"),'</div>
        <div>',sprintf("%s",number_format($cStats["ComputedWatts"]*3.412/1000)),' kBTU</div>
  </div>
  <div>
        <div>',__("Data Center Size"),'</div>
        <div>',sprintf("%s ".$volunit,number_format($cStats["SquareFootage"])),'</div>
  </div>
  <div>
        <div>',$density,'</div>
        <div>',(($cStats["SquareFootage"]>0)?sprintf("%s ".$densunit,number_format($cStats["ComputedWatts"]/$cStats["SquareFootage"])):"0 ").'</div>
  </div>
  <div>
        <div>',__("Minimum Cooling Tonnage Required"),'</div>
        <div>',sprintf("%s ".__("Tons"),number_format($cStats["ComputedWatts"]*3.412*1.15/12000)),'</div>
  </div>
  <div>
    <div>',__("Total Cabinets"),'</div>
    <div>',sprintf( "%s", number_format($cStats["TotalCabinets"])),'</div>
  </div>
</div> <!-- END div.table -->
</div>

<br>
<div class="JMGA" style="center width: 1200px; overflow: hidden">';

  print $c->MakeContainerImage();
?>
</div></div>
</div><!-- END div.JMGA -->
</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
	$(document).ready(function() {
		// Hard set widths to stop IE from being retarded
		$('#mapCanvas').css('width', $('.canvas > img[alt="clearmap over canvas"]').width()+'px');
		$('#mapCanvas').parent('.canvas').css('width', $('.canvas > img[alt="clearmap over canvas"]').width()+'px');

		// Don't attempt to open the datacenter tree until it is loaded
		function opentree(){
			if($('#datacenters .bullet').length==0){
				setTimeout(function(){
					opentree();
				},500);
			}else{
				var firstcabinet=$('#c<?php echo $c->ContainerID;?> > ul > li:first-child').attr('id');
				// If we have no children,
				if (typeof firstcabinet == 'undefined') {
					// use the 1st-born child of our parent: may, or may not, be us
					firstcabinet=$('#c<?php echo $c->ContainerID;?> ').attr('id');
				}
				expandToItem('datacenters',firstcabinet);
			}
		}

		// Bind tooltips, highlight functions to the map
		opentree();
	});
</script>
</body>
</html>
