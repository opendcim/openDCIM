<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights();
	
	if(!$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$c=New Container();
	
	$c->ContainerID=$_REQUEST["container"];
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

echo '<div class="main">
<div class="heading">
  <div>
	<h2>',$config->ParameterArray["OrgName"],'</h2>
	<h3>',_("Data Centers Statistics"),'</h3>
  </div>
</div>
<div class="center"><div>
<div class="centermargin" id="dcstats">
<div class="table border">
  <div class="title">',$c->Name,'</div>
  <div>
	<div></div>
	<div>',_("Infrastructure"),'</div>
	<div>',_("Occupied"),'</div>
	<div>',_("Allocated"),'</div>
	<div>',_("Available"),'</div>
  </div>
  <div>
	<div>',sprintf(_("Total U")." %5d",$cStats["TotalU"]),'</div>
	<div>',sprintf("%3d",$cStats["Infrastructure"]),'</div>
	<div>',sprintf("%3d",$cStats["Occupied"]),'</div>
	<div>',sprintf("%3d",$cStats["Allocated"]),'</div>
	<div>',sprintf("%3d",$cStats["Available"]),'</div>
  </div>
  <div>
	<div>',_("Percentage"),'</div>
	<div>',(($cStats["TotalU"])?sprintf("%3.1f%%",$cStats["Infrastructure"]/$cStats["TotalU"]*100):"0"),'</div>
	<div>',(($cStats["TotalU"])?sprintf("%3.1f%%",$cStats["Occupied"]/$cStats["TotalU"]*100):"0"),'</div>
	<div>',(($cStats["TotalU"])?sprintf("%3.1f%%",$cStats["Allocated"]/$cStats["TotalU"]*100):"0"),'</div>
	<div>',(($cStats["TotalU"])?sprintf("%3.1f%%",$cStats["Available"]/$cStats["TotalU"]*100):"0"),'</div>
  </div>
  </div> <!-- END div.table -->
  <div class="table border">
  <div>
        <div>',_("Data Centers"),'</div>
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
		<div>',_("Design Maximum (kW)"),'</div>
		<div>',sprintf("%s kW",number_format($cStats["MaxkW"],0, ",", ".") ),'</div>
  </div>
  <div>
        <div>',_("BTU Computation from Watts"),'</div>
        <div>',sprintf("%s "._("BTU"),number_format($cStats["ComputedWatts"]*3.412,0, ",", ".") ),'</div>
  </div>
  <div>
        <div>',_("Data Center Size"),'</div>
        <div>',sprintf("%s "._("Square Feet"),number_format($cStats["SquareFootage"],0, ",", ".")),'</div>
  </div>
  <div>
        <div>',_("Watts per Square Foot"),'</div>
        <div>',(($cStats["SquareFootage"]>0)?sprintf("%s "._("Watts"),number_format($cStats["ComputedWatts"]/$cStats["SquareFootage"],0, ",", ".")):"0 "._("Watts")),'</div>
  </div>
  <div>
        <div>',_("Minimum Cooling Tonnage Required"),'</div>
        <div>',sprintf("%s "._("Tons"),number_format($cStats["ComputedWatts"]*3.412*1.15/12000,0, ",", ".")),'</div>
  </div>
</div> <!-- END div.table -->
</div>

<br>
<div class="JMGA" style="center width: 1200px; overflow: auto">';

  //print $c->MakeImageMap();
  print $c->MakeContainerImage();
?>
</div></div>
</div><!-- END div.JMGA -->
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
