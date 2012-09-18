<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user = new User();

	$user->UserID = $_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );

	if(!$user->ContactAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$period=new EscalationTimes();
	$status='';

	if(isset($_REQUEST['escalationtimeid'])){
		$period->EscalationTimeID=$_REQUEST['escalationtimeid'];
		if(isset($_POST['action'])){
			if($_POST['timeperiod']!=null && $_POST['timeperiod']!=''){
				switch($_POST['action']){
					case 'Create':
						$period->TimePeriod=$_POST['timeperiod'];
						$period->CreatePeriod($facDB);
						break;
					case 'Update':
						$period->TimePeriod=$_POST['timeperiod'];
						$status=_('Updated');
						$period->UpdatePeriod($facDB);
						break;
					case 'Delete':
						$period->DeletePeriod($facDB);
						header('Location: '.redirect("timeperiods.php"));
						exit;
				}
			}
		}
		$period->GetEscalationTime($facDB);
	}
	$periodList=$period->GetEscalationTimeList($facDB);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui-1.8.18.custom.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui-1.8.18.custom.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',_("Data Center Time Periods Listing"),'</h3>
<h3>',$status,'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div><label for="escalationtimeid">',_("Escalation Time Period"),'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="escalationtimeid" id="escalationtimeid" onChange="form.submit()">
   <option value=0>',_("New Time Period"),'</option>';

	foreach($periodList as $periodRow){
		if($period->EscalationTimeID==$periodRow->EscalationTimeID){$selected=' selected';}else{$selected="";}
		print "<option value=\"$periodRow->EscalationTimeID\"$selected>$periodRow->TimePeriod</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="timeperiod">',_("Description"),'</label></div>
   <div><input type="text" name="timeperiod" id="timeperiod" size="80" value="',$period->TimePeriod,'"></div>
</div>
<div class="caption">';

	if($period->EscalationTimeID >0){
		echo '   <button type="submit" name="action" value="Update">',_("Update"),'</button>
	 <button type="submit" name="action" value="Delete">',_("Delete"),'</button>';
	}else{
		echo '	 <button type="submit" name="action" value="Create">',_("Create"),'</button>';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',_("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
