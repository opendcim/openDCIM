<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights();

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$zone=new Zone();
	$DC=new DataCenter();
	
	$DCList=$DC->GetDCList($facDB);
	$formpatch="";
	$status="";


	if(isset($_REQUEST["zoneid"])) {
		$zone->ZoneID=(isset($_POST['zoneid'])?$_POST['zoneid']:$_GET['zoneid']);
		$zone->GetZone($facDB);
		
		if(isset($_POST["action"]) && (($_POST["action"]=="Create") || ($_POST["action"]=="Update"))){
			$zone->Description=$_POST["description"];
			$zone->DataCenterID=$_POST["datacenterid"];
			
			if($_POST["action"]=="Create"){
				$zone->CreateZone($facDB);
			}else{
				$status=__("Updated");
				$zone->UpdateZone($facDB);
			}
		}
		$formpatch="?zoneid={$_REQUEST['zoneid']}";
	}
	
	$zoneList=$zone->GetZoneList($facDB);

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Zones</title>

  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page zone">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',__("Data Center Zones"),'</h3>
<h3>',$status,'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"].$formpatch,'" method="POST">
<div class="table">
<div>
   <div><label for="zoneid">',__("Zone"),'</label></div>
   <div><input type="hidden" name="action" value="query">
   <select name="zoneid" id="zoneid" onChange="form.submit()">
   <option value=0>',__("New Zone"),'</option>';

	foreach($zoneList as $zoneRow){
		if($zone->ZoneID==$zoneRow->ZoneID){$selected=" selected";}else{$selected="";}
		$DC->DataCenterID=$zoneRow->DataCenterID;
		$DC->GetDataCenter($facDB);
		print "<option value=\"$zoneRow->ZoneID\"$selected>[".$DC->Name."] ".$zoneRow->Description."</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="description">',__("Description"),'</label></div>
   <div><input type="text" size="50" name="description" id="description" value="',$zone->Description,'"></div>
</div>
<div>
   <div><label for="datacenterid">',__("Data Center"),'</label></div>
   <div><select name="datacenterid" id="datacenterid" onChange="form.submit()">';

	foreach($DCList as $DCRow){
		if($zone->DataCenterID==$DCRow->DataCenterID){$selected=" selected";}else{$selected="";}
		print "<option value=\"$DCRow->DataCenterID\"$selected>$DCRow->Name</option>\n";
	}

echo '	</select></div>
</div>';

	if($zone->ZoneID==0){
		echo '<div><div>&nbsp;</div><div></div></div>
		<div class="caption"><button type="submit" name="action" value="Create">',__("Create"),'</button></div>';
	}
	else{
		echo '<div><div>&nbsp;</div><div></div></div>
		<div class="caption"><button type="submit" name="action" value="Update">',__("Update"),'</button></div>';
	}
?>
</div><!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
