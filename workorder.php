<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');
	
	if ( ! isset( $_COOKIE["workOrder"] ) || ( isset( $_COOKIE["workOrder"] ) && $_COOKIE["workOrder"]=="" )) {
		header( "Location: " . redirect() );
		exit;
	}
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
  <script type="text/javascript" src="scripts/jquery.cookie.js"></script>
<script>
	function deleteCookie() {
		document.cookie = encodeURIComponent("workOrder") + "=; expired=" + new Date(0).toUTCString();
		location.href="index.php";
	}
</script>
</head>
<body>
<div id="header"></div>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h2><?php print __("Data Center Operations Work Order Builder"); ?></h2>
<div class="center"><div>
<!-- CONTENT GOES HERE -->
<?php
	printf( "<h2>%s</h2>", __("Work Order Contents" ));

	$devList = array();
	$woList = json_decode( $_COOKIE["workOrder"] );
	foreach ( $woList as $woDev ) {
		if ( $woDev > 1 ) {
			$n = sizeof( $devList );
			$devList[$n] = new Device();
			$devList[$n]->DeviceID = $woDev;
			$devList[$n]->GetDevice();
		}
	}
	
	print "<div class=\"table\">\n";
	printf( "<div><div>%s</div><div>%s</div><div>%s</div><div>%s</div></div>\n", __("Cabinet"), __("Position"), __("Label"), __("Image"));
	
	$devTmpl = new DeviceTemplate();
	$cab = new Cabinet();
	
	foreach ( $devList as $dev ) {
		$cab->CabinetID=$dev->Cabinet;
		$cab->GetCabinet();
		
		$devTmpl->TemplateID = $dev->TemplateID;
		$devTmpl->GetTemplateByID();
		
		printf( "<div><div>%s</div><div>%s</div><div>%s</div><div><img src=\"pictures/%s\" width=\"300\"></div></div>\n", $cab->Location, $dev->Height==1 ? $dev->Position : $dev->Position."-".($dev->Position+$dev->Height-1), $dev->Label, $devTmpl->FrontPictureFile ? $devTmpl->FrontPictureFile :  "P_ERROR.png");
	}
	
	print "</div>\n";

	print '<a href="export_port_connections.php?deviceid=wo"><button type="button">' . __("Export Connections") . '</button></a>';
?>

<a href="javascript:deleteCookie()"><button type="button"><?php print __("Clear"); ?></button></a>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
