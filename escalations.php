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
						$status=_('Updated');
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
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>openDCIM Data Center Inventory</title>
  
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
<div class="page">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',_("Data Center Escalation Rules"),'</h3>
<h3>',$status,'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div><label for="escalationid">',_("Escalation Rule"),'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="escalationid" id="escalationid" onChange="form.submit()">
   <option value=0>',_("New Escalation Rule"),'</option>';

	foreach( $escList as $escRow ) {
		if($esc->EscalationID == $escRow->EscalationID){$selected=" selected";}else{$selected="";}
		print "<option value=\"$escRow->EscalationID\"$selected>$escRow->Details</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="details">',_("Details"),'</label></div>
   <div><input type="text" name="details" id="details" size="80" value="',$esc->Details,'"></div>
</div>
<div class="caption">';

	if($esc->EscalationID >0){
		echo '   <button type="submit" name="action" value="Update">',_("Update"),'</button>
	 <button type="submit" name="action" value="Delete">',_("Delete"),'</button>';
	}else{
		echo '	 <button type="submit" name="action" value="Create">',_("Create"),'</button>';
	}
?>
</div>
</div><!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',_("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
