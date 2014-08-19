<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Data Center Operations Work Order Builder");
	
	if(!isset($_COOKIE["workOrder"]) || (isset($_COOKIE["workOrder"]) && $_COOKIE["workOrder"]=="" )){
		header("Location: ".redirect());
		exit;
	}

	$devList=array();
	$woList=json_decode($_COOKIE["workOrder"]);
	foreach($woList as $woDev){
		$dev=new Device();
		$dev->DeviceID=$woDev;
		if($dev->GetDevice()){
			$devList[]=$dev;
		}
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
	$(document).ready(function(){
		$('#clear').click(function(){
			$.removeCookie('workOrder');
			location.href="index.php";
		});
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
<!-- CONTENT GOES HERE -->
<?php
	print "<h2>".__("Work Order Contents")."</h2>
<div class=\"table\">
	<div><div>".__("Cabinet")."</div><div>".__("Position")."</div><div>".__("Label")."</div><div>".__("Image")."</div></div>\n";
	
	foreach($devList as $dev){
		// including the $cab and $devTempl in here so it gets reset each time and there 
		// is no chance for phantom data
		$cab=new Cabinet();
		if($dev->ParentDevice>0){
			$pdev=new Device();
			$pdev->DeviceID=$dev->GetRootDeviceID();
			$pdev->GetDevice();
			$cab->CabinetID=$pdev->Cabinet;
		}else{
			$cab->CabinetID=$dev->Cabinet;
		}
		$cab->GetCabinet();
		
		$devTmpl=new DeviceTemplate();
		$devTmpl->TemplateID=$dev->TemplateID;
		$devTmpl->GetTemplateByID();

		$position=($dev->Height==1)?$dev->Position:$dev->Position."-".($dev->Position+$dev->Height-1);

		print "<div><div>$cab->Location</div><div>$position</div><div>$dev->Label</div><div>".$dev->GetDevicePicture('','','nolinks')."</div></div>\n";
	}
	
	print '</div>
<a href="export_port_connections.php?deviceid=wo"><button type="button">'.__("Export Connections").'</button></a>';
?>

<button type="button" id="clear"><?php print __("Clear"); ?></button>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
