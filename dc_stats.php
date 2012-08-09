<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights($facDB);

	if(!$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

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
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Data Center Information Management</title>
  <!--[if lte IE 8]>
    <link rel="stylesheet"  href="css/ie.css" type="text/css">
    <?php if(isset($ie8fix)){print $ie8fix;} ?>
    <script src="scripts/excanvas.js"></script>
  <![endif]-->
  <?php if(isset($screenadjustment)){print $screenadjustment;} ?>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <?php print $dc->DrawCanvas($facDB);?>
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
</head>
<body onload="loadCanvas(),uselessie()">
<div id="header"></div>
<div class="page dcstats" id="mapadjust">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main">
<div class="heading">
  <div>
	<h2><?php print $config->ParameterArray["OrgName"]; ?></h2>
	<h3>Data Center Statistics</h3>
  </div>
  <div>
	<button onclick="loadCanvas()">Overview</button>
	<button onclick="space()">Space</button>
	<button onclick="weight()">Weight</button>
	<button onclick="power()">Power</button>
  </div>
</div>
<div class="center"><div>
<div class="centermargin" id="dcstats">
<div class="table border">
  <div class="title">
	<?php print $dc->Name; ?>
  </div>
  <div>
	<div></div>
	<div>Infrastructure</div>
	<div>Occupied</div>
	<div>Allocated</div>
	<div>Available</div>
  </div>
  <div>
	<div><?php printf( "Total U %5d", $dcStats["TotalU"] ); ?></div>
	<div><?php printf( "%3d", $dcStats["Infrastructure"] ); ?></div>
	<div><?php printf( "%3d", $dcStats["Occupied"] ); ?></div>
	<div><?php printf( "%3d", $dcStats["Allocated"] ); ?></div>
	<div><?php printf( "%3d", $dcStats["Available"] ); ?></div>
  </div>
  <div>
	<div>Percentage</div>
	<div><?php @printf( "%3.1f%%", $dcStats["Infrastructure"] / $dcStats["TotalU"] * 100 ); ?></div>
	<div><?php @printf( "%3.1f%%", $dcStats["Occupied"] / $dcStats["TotalU"] * 100 ); ?></div>
	<div><?php @printf( "%3.1f%%", $dcStats["Allocated"] / $dcStats["TotalU"] * 100 ); ?></div>
	<div><?php @printf( "%3.1f%%", $dcStats["Available"] / $dcStats["TotalU"] * 100 ); ?></div>
  </div>
  </div> <!-- END div.table -->
  <div class="table border">
  <div>
        <div>Raw Wattage</div>
        <div><?php printf( "%7d Watts", $dcStats["TotalWatts"] ); ?></div>
  </div>
  <div>
        <div>BTU Computation from Watts</div>
        <div><?php printf( "%8d BTU", $dcStats["TotalWatts"] * 3.412 ); ?></div>
  </div>
  <div>
        <div>Data Center Size</div>
        <div><?php printf( "%8d Square Feet", $dc->SquareFootage ); ?></div>
  </div>
  <div>
        <div>Watts per Square Foot</div>
        <div><?php printf( "%8d Watts", $dcStats["TotalWatts"] / $dc->SquareFootage ); ?></div>
  </div>
  <div>
        <div>Minimum Cooling Tonnage Required</div>
        <div><?php printf( "%7d Tons", $dcStats["TotalWatts"] * 3.412  * 1.15 / 12000 ); ?></div>
  </div>
</div> <!-- END div.table -->
</div>
<?php
  print $dc->MakeImageMap( $facDB );
?>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
