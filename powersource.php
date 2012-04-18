<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user = new User();
	$user->UserID = $_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$ps = new PowerSource();
  
	if(isset($_REQUEST['action']) && (($_REQUEST['action']=='Create')||($_REQUEST['action']=='Update'))){
		$ps->PowerSourceID = $_REQUEST['powersourceid'];
		$ps->SourceName = $_REQUEST['sourcename'];
		$ps->DataCenterID = $_REQUEST['datacenterid'];
		$ps->IPAddress = $_REQUEST['ipaddress'];
		$ps->Community = $_REQUEST['community'];
		$ps->LoadOID = $_REQUEST['loadoid'];
		$ps->Capacity = $_REQUEST['capacity'];
		
		if($_REQUEST['action']=='Create'){
			$ps->CreatePowerSource($facDB);
		}else{
			$ps->UpdatePowerSource($facDB);
		}
	}

	if(isset($_REQUEST['powersourceid']) && $_REQUEST['powersourceid'] >0){
		$ps->PowerSourceID = $_REQUEST['powersourceid'];
		$ps->GetSource( $facDB );
	}
	$psList = $ps->GetPSList($facDB);

	$dc = new DataCenter();
	$dcList = $dc->GetDCList($facDB);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Facilities Cabinet Maintenance</title>
  <link rel="stylesheet" href="css/inventory.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?> Power Sources</h2>
<h3>Data Center Detail</h3>
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<div class="table">
<div>
   <div><label for="powersourceid">Power Source ID</label></div>
   <div><select name="powersourceid" id="powersourceid" onChange="form.submit()">
      <option value="0">New Power Source</option>
<?php
      foreach ( $psList as $psRow ) {
        if ( $psRow->PowerSourceID == $ps->PowerSourceID )
          $selected = 'selected';
        else
          $selected = '';

        printf( "<option value=\"%d\" %s>%s</option>\n", $psRow->PowerSourceID, $selected, $psRow->SourceName );
      }
?>
	</select></div>
</div>
<div>
   <div><label for="sourcename">Name</label></div>
   <div><input type="text" name="sourcename" id="sourcename" size="50" value="<?php echo $ps->SourceName; ?>"></div>
</div>
<div>
   <div><label for="datacenterid">Data Center</label></div>
   <div><select name="datacenterid" id="datacenterid">
	<?php
		foreach($dcList as $dcRow){
			echo "<option value=\"$dcRow->DataCenterID\"";
			if($dcRow->DataCenterID == $ps->DataCenterID){
				echo ' selected';
			}
			echo ">$dcRow->Name</option>\n";
		}
	?>
   </select></div>
</div>
<div>
   <div><label for="ipaddress">IP Address</label></div>
   <div><input type="text" name="ipaddress" id="ipaddress" size="20" value="<?php echo $ps->IPAddress; ?>"></div>
</div>
<div>
   <div><label for="community">SNMP Community</label></div>
   <div><input type="text" name="community" id="community" size=40 value="<?php echo $ps->Community; ?>"></div>
</div>
<div>
   <div><label for="loadoid">Load OID</label></div>
   <div><input type="text" name="loadoid" id="loadoid" size=60 value="<?php echo $ps->LoadOID; ?>"></div>
</div>
<div>
  <div><label for="capacity">Capacity (kW)</label></div>
  <div><input type="numeric" name="capacity" id="capacity" size=8 value="<?php echo $ps->Capacity; ?>"></div>
</div>
<div class="caption">
<?php
	if($ps->PowerSourceID >0){
		echo '   <input type="submit" name="action" value="Update">';
	} else {
		echo '   <input type="submit" name="action" value="Create">';
  }
?>
</div>
</div> <!-- END div.table -->
</form>
</div></div>
<a href="index.php">[ Return to Main Menu ]</a>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
