<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$subheader=__("Data Center Escalation Rules");

	if(!$person->ContactAdmin){
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
						$esc->CreateEscalation();
						break;
					case 'Update':
						$esc->Details=$_POST['details'];
						$status=__("Updated");
						$esc->UpdateEscalation();
						break;
					case 'Delete':
						$esc->DeleteEscalation();
						header('Location: '.redirect("escalations.php"));
						exit;
				}
			}
		}
		$esc->GetEscalation();
	}
	$escList=$esc->GetEscalationList();
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
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<h3>',$status,'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div><label for="escalationid">',__("Escalation Rule"),'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="escalationid" id="escalationid" onChange="form.submit()">
   <option value=0>',__("New Escalation Rule"),'</option>';

	foreach( $escList as $escRow ) {
		if($esc->EscalationID == $escRow->EscalationID){$selected=" selected";}else{$selected="";}
		print "<option value=\"$escRow->EscalationID\"$selected>$escRow->Details</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="details">',__("Details"),'</label></div>
   <div><input type="text" name="details" id="details" size="80" value="',$esc->Details,'"></div>
</div>
<div class="caption">';

	if($esc->EscalationID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>
	 <button type="submit" name="action" value="Delete">',__("Delete"),'</button>';
	}else{
		echo '	 <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
?>
</div>
</div><!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
