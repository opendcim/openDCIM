<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

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
<div id="header"></div>
<div class="page dcstats" id="mapadjust">
<?php
include( "sidebar.inc.php" );

if ( $config->ParameterArray["mUnits"] == "english" ) {
    $vol = __("Square Feet");
    $density = __("Watts per Square Foot");
} else {
    $vol = __("Square Meters");
    $density = __("Watts per Square Meter" );
}

echo '<div class="main">
<div class="heading">
  <div>
	<h2>',$config->ParameterArray["OrgName"],'</h2>
	<h3>',__("Data Centers Statistics"),'</h3>
  </div>
</div>
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
	<div>',sprintf(__("Total U")." %5d",$cStats["TotalU"]),'</div>
	<div>',sprintf("%3d",$cStats["Infrastructure"]),'</div>
	<div>',sprintf("%3d",$cStats["Occupied"]),'</div>
	<div>',sprintf("%3d",$cStats["Allocated"]),'</div>
	<div>',sprintf("%3d",$cStats["Available"]),'</div>
  </div>
  <div>
	<div>',__("Percentage"),'</div>
	<div>',(($cStats["TotalU"])?sprintf("%3.1f%%",$cStats["Infrastructure"]/$cStats["TotalU"]*100):"0"),'</div>
	<div>',(($cStats["TotalU"])?sprintf("%3.1f%%",$cStats["Occupied"]/$cStats["TotalU"]*100):"0"),'</div>
	<div>',(($cStats["TotalU"])?sprintf("%3.1f%%",$cStats["Allocated"]/$cStats["TotalU"]*100):"0"),'</div>
	<div>',(($cStats["TotalU"])?sprintf("%3.1f%%",$cStats["Available"]/$cStats["TotalU"]*100):"0"),'</div>
  </div>
  </div> <!-- END div.table -->
  <div class="table border">
  <div>
        <div>',__("Data Centers"),'</div>
        <div>',sprintf("%s ",number_format($cStats["DCs"],0, ",", ".")),'</div>
  </div>
  <div>
        <div>',__("Computed Wattage"),'</div>
        <div>',sprintf("%7d %s", $cStats["ComputedWatts"], __("Watts")),'</div>
  </div>
  <div>
		<div>',__("Measured Wattage"), '</div>
		<div>',sprintf("%7d %s", $cStats["MeasuredWatts"], __("Watts")),'</div>
  </div>
    <div>
		<div>',__("Design Maximum (kW)"),'</div>
		<div>',sprintf("%s kW",number_format($cStats["MaxkW"],0, ",", ".") ),'</div>
  </div>
  <div>
        <div>',__("BTU Computation from Watts"),'</div>
        <div>',sprintf("%s ".__("BTU"),number_format($cStats["ComputedWatts"]*3.412,0, ",", ".") ),'</div>
  </div>
  <div>
        <div>',__("Data Center Size"),'</div>
        <div>',sprintf("%s ".$vol,number_format($cStats["SquareFootage"],0, ",", ".")),'</div>
  </div>
  <div>
        <div>',$density,'</div>
        <div>',(($cStats["SquareFootage"]>0)?sprintf("%s ".__("Watts"),number_format($cStats["ComputedWatts"]/$cStats["SquareFootage"],0, ",", ".")):"0 ".__("Watts")),'</div>
  </div>
  <div>
        <div>',__("Minimum Cooling Tonnage Required"),'</div>
        <div>',sprintf("%s ".__("Tons"),number_format($cStats["ComputedWatts"]*3.412*1.15/12000,0, ",", ".")),'</div>
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
</body>
</html>
