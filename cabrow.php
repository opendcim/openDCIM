<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights($facDB);

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$cabrow=new CabRow();
	$zone=new Zone();
	$DC=new DataCenter();
	
	$zoneList=$zone->GetZoneList($facDB);
	$formpatch="";
	$status="";

	if(isset($_REQUEST["cabrowid"])) {
		$cabrow->ZoneID=(isset($_POST['cabrowid'])?$_POST['cabrowid']:$_GET['cabrowid']);
		$cabrow->GetCabRow($facDB);
		
		if(isset($_POST["action"]) && (($_POST["action"]=="Create") || ($_POST["action"]=="Update"))){
			$cabrow->Name=$_POST["name"];
			$cabrow->ZoneID=$_POST["zoneid"];
			
			if($_POST["action"]=="Create"){
				$cabrow->CreateCabRow($facDB);
			}else{
				$status=__("Updated");
				$cabrow->UpdateCabRow($facDB);
			}
		}
		$formpatch="?cabrowid={$_REQUEST['cabrowid']}";
	}
	
	$cabrowList=$cabrow->GetCabRowList($facDB);

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Rows of Cabinets</title>

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
<div class="page cabrow">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',__("Rows of Cabinets"),'</h3>
<h3>',$status,'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"].$formpatch,'" method="POST">
<div class="table">
<div>
   <div><label for="cabrowid">',__("Row"),'</label></div>
   <div><input type="hidden" name="action" value="query">
   <select name="cabrowid" id="cabrowid" onChange="form.submit()">
   <option value=0>',__("New Row"),'</option>';

	foreach($cabrowList as $cabrowRow){
		if($cabrow->CabRowID==$cabrowRow->CabRowID){$selected=" selected";}else{$selected="";}
		$zone->ZoneID=$cabrowRow->ZoneID;
		$zone->GetZone($facDB);
		$DC->DataCenterID=$zone->DataCenterID;
		$DC->GetDataCenter($facDB);
		print "<option value=\"$cabrowRow->CabRowID\"$selected>[".$DC->Name."/".$zone->Description."] ".$cabrowRow->Name."</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="name">',__("Name"),'</label></div>
   <div><input type="text" size="50" name="name" id="name" value="',$cabrow->Name,'"></div>
</div>
<div>
   <div><label for="zoneid">',__("Data Center Zone"),'</label></div>
   <div><select name="zoneid" id="zoneid">';

	foreach($zoneList as $zoneRow){
		if($cabrow->ZoneID==$zoneRow->ZoneID){$selected=" selected";}else{$selected="";}
		$DC->DataCenterID=$zoneRow->DataCenterID;
		$DC->GetDataCenter($facDB);
		print "<option value=\"$zoneRow->ZoneID\"$selected>[".$DC->Name."] ".$zoneRow->Description."</option>\n";
	}

echo '	</select></div>
</div>';

	if($cabrow->CabRowID==0){
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
