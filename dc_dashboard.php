<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$cab=new Cabinet();
	$dc=new DataCenter();

	$dc->DataCenterID=$_REQUEST["dc"];
	$dcStats=$dc->GetDCStatistics($facDB);

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
</script>
<style type=\"text/css\">
.mapCanvasiefix {
	    width: {$width}px !important;
}
</style>";
		}
	}
	$height+=60; //Offset for text on header
	$width+=10; //Don't remember why I need this

	// Necessary for IE layout bug where it wants to make the mapsize $width * 10 for whatever crazy reason
	// Base sizes for calculations
	// 95px for mode buttons
	// 691px for header 
	// 1030px for page
	if($width>800){
		$offset=($width-800);
		$screenadjustment="<style type=\"text/css\">div.center > div{width:".($offset+800)."px;} div#mapadjust{width:".($offset+1030)."px;} #mapadjust div.heading > div{width:".($offset+691)."px;} #mapadjust div.heading > div + div{width:95px;}</style>\n";
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
    <?php if(isset($ie8fix)){print $ie8fix;} ?>
    <script src="scripts/excanvas.js"></script>
  <![endif]-->
  <?php if(isset($screenadjustment)){print $screenadjustment;} ?>
  <?php print $dc->DrawCanvas($facDB);?>
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body onload="loadCanvas(),uselessie()">
<div id="header"></div>
<div class="page dcstats" id="mapadjust">
<?php

echo '<div class="main">
<div class="heading">
  <div>
	<h2>',$config->ParameterArray["OrgName"],'</h2>
	<h3>',__("Data Center Statistics"),'</h3>
  </div>
  <div>
	<button onclick="loadCanvas()">',__("Overview"),'</button>
	<button onclick="space()">',__("Space"),'</button>
	<button onclick="weight()">',__("Weight"),'</button>
	<button onclick="power()">',__("Power"),'</button>
  </div>
</div>
<div class="center"><div>
<div class="centermargin" id="dcstats">
<div class="table border">
  <div class="title">',$dc->Name,'</div>
  <div>
	<div></div>
	<div>',__("Infrastructure"),'</div>
	<div>',__("Occupied"),'</div>
	<div>',__("Allocated"),'</div>
	<div>',__("Available"),'</div>
  </div>
  <div>
	<div>',sprintf(__("Total U")." %5d",$dcStats["TotalU"]),'</div>
	<div>',sprintf("%3d",$dcStats["Infrastructure"]),'</div>
	<div>',sprintf("%3d",$dcStats["Occupied"]),'</div>
	<div>',sprintf("%3d",$dcStats["Allocated"]),'</div>
	<div>',sprintf("%3d",$dcStats["Available"]),'</div>
  </div>
  <div>
	<div>',__("Percentage"),'</div>
	<div>',(($dcStats["TotalU"])?sprintf("%3.1f%%",$dcStats["Infrastructure"]/$dcStats["TotalU"]*100):"0"),'</div>
	<div>',(($dcStats["TotalU"])?sprintf("%3.1f%%",$dcStats["Occupied"]/$dcStats["TotalU"]*100):"0"),'</div>
	<div>',(($dcStats["TotalU"])?sprintf("%3.1f%%",$dcStats["Allocated"]/$dcStats["TotalU"]*100):"0"),'</div>
	<div>',(($dcStats["TotalU"])?sprintf("%3.1f%%",$dcStats["Available"]/$dcStats["TotalU"]*100):"0"),'</div>
  </div>
  </div> <!-- END div.table -->
  <div class="table border">
  <div>
        <div>',__("Raw Wattage"),'</div>
        <div>',sprintf("%7d ".__("Watts"),$dcStats["TotalWatts"]),'</div>
  </div>
  <div>
        <div>',__("BTU Computation from Watts"),'</div>
        <div>',sprintf("%8d ".__("BTU"),$dcStats["TotalWatts"]*3.412 ),'</div>
  </div>
  <div>
        <div>',__("Data Center Size"),'</div>
        <div>', sprintf("%8d %s", $dc->SquareFootage, $vol), '</div>
  </div>
  <div>
        <div>', $density,'</div>
        <div>',(($dc->SquareFootage)?sprintf("%8d ".__("Watts"),$dcStats["TotalWatts"]/$dc->SquareFootage):"0 ".__("Watts")),'</div>
  </div>
  <div>
        <div>',__("Minimum Cooling Tonnage Required"),'</div>
        <div>',sprintf("%7d ".__("Tons"),$dcStats["TotalWatts"]*3.412*1.15/12000),'</div>
  </div>
</div> <!-- END div.table -->
</div>';

  print $dc->MakeImageMapNoLinks( $facDB );
?>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
