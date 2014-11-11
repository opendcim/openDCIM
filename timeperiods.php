<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$subheader=__("Data Center Time Periods Listing");

	if(!$person->ContactAdmin){
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
						$period->CreatePeriod();
						break;
					case 'Update':
						$period->TimePeriod=$_POST['timeperiod'];
						$status=__("Updated");
						$period->UpdatePeriod();
						break;
					case 'Delete':
						$period->DeletePeriod();
						header('Location: '.redirect("timeperiods.php"));
						exit;
				}
			}
		}
		$period->GetEscalationTime();
	}
	$periodList=$period->GetEscalationTimeList();
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
	include( 'sidebar.inc.php' );

echo '<div class="main">
<h3>',$status,'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div><label for="escalationtimeid">',__("Escalation Time Period"),'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="escalationtimeid" id="escalationtimeid" onChange="form.submit()">
   <option value=0>',__("New Time Period"),'</option>';

	foreach($periodList as $periodRow){
		if($period->EscalationTimeID==$periodRow->EscalationTimeID){$selected=' selected';}else{$selected="";}
		print "<option value=\"$periodRow->EscalationTimeID\"$selected>$periodRow->TimePeriod</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="timeperiod">',__("Description"),'</label></div>
   <div><input type="text" name="timeperiod" id="timeperiod" size="80" value="',$period->TimePeriod,'"></div>
</div>
<div class="caption">';

	if($period->EscalationTimeID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>
	 <button type="submit" name="action" value="Delete">',__("Delete"),'</button>';
	}else{
		echo '	 <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
