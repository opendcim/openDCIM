<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$user=new User();
	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	if(!$user->ContactAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$esc=new Escalations();

	if(isset($_REQUEST["escalationid"]) && $_REQUEST["escalationid"] >0){
		$esc->EscalationID = $_REQUEST["escalationid"];
		$esc->GetEscalation($facDB);
	}

	$status="";
	if(isset($_REQUEST["action"])&&(($_REQUEST["action"]=="Create")||($_REQUEST["action"]=="Update"))){
		$esc->EscalationID = $_REQUEST["escalationid"];
		$esc->Details = $_REQUEST["details"];

		if($_REQUEST["action"] == "Create"){
		  if($esc->Details != null && $esc->Details != "")
  			$esc->CreateEscalation($facDB);
		}else{
			$status="Updated";
			$esc->UpdateEscalation($facDB);
		}
	}
	$escList = $esc->GetEscalationList( $facDB );
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <title>openDCIM Data Center Inventory</title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
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
	include( "sidebar.inc.php" );
?>
<div class="main">
<h2><?php echo $config->ParameterArray["OrgName"]; ?></h2>
<h3>Data Center Escalation Rules</h3>
<h3><?php echo $status; ?></h3>
<div class="center"><div>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
<div class="table">
<div>
   <div><label for="escalationid">Escalation Rule</label></div>
   <div><input type="hidden" name="action" value="query"><select name="escalationid" id="escalationid" onChange="form.submit()">
   <option value=0>New Escalation Rule</option>
<?php
	foreach( $escList as $escRow ) {
		echo "<option value=\"$escRow->EscalationID\"";
		if($esc->EscalationID == $escRow->EscalationID){
			echo " selected";
		}
		echo ">$escRow->Details</option>\n";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="details">Details</label></div>
   <div><input type="text" name="details" id="details" size="80" value="<?php echo $esc->Details; ?>"></div>
</div>
<div class="caption">
   <input type="submit" name="action" value="Create">
<?php
	if($esc->EscalationID >0){
		echo '   <input type="submit" name="action" value="Update">';
	}
?>
</div>
</div><!-- END div.table -->
</form>
</div></div>
<a href="index.php">[ Return to Main Menu ]</a>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
