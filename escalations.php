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
	$status="";

	if(isset($_REQUEST['escalationid'])){
		$esc->EscalationID=$_REQUEST['escalationid'];
		if(isset($_POST['action'])){
			if($_POST['details']!=null && $_POST['details']!=''){
				switch($_POST['action']){
					case 'Create':
						$esc->Details=$_POST['details'];
						$esc->CreateEscalation($facDB);
						break;
					case 'Update':
						$esc->Details=$_POST['details'];
						$status='Updated';
						$esc->UpdateEscalation($facDB);
						break;
					case 'Delete':
						$esc->DeleteEscalation($facDB);
						header('Location: '.redirect("escalations.php"));
						exit;
				}
			}
		}
		$esc->GetEscalation($facDB);
	}
	$escList=$esc->GetEscalationList($facDB);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <title>openDCIM Data Center Inventory</title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
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
		if($esc->EscalationID == $escRow->EscalationID){$selected=" selected";}else{$selected="";}
		print "<option value=\"$escRow->EscalationID\"$selected>$escRow->Details</option>\n";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="details">Details</label></div>
   <div><input type="text" name="details" id="details" size="80" value="<?php echo $esc->Details; ?>"></div>
</div>
<div class="caption">
<?php
	if($esc->EscalationID >0){
		echo '   <input type="submit" name="action" value="Update">
	 <input type="submit" name="action" value="Delete">';
	}else{
		echo '   <input type="submit" name="action" value="Create">';
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
