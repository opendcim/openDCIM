<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$ps=new PowerSource();
	
	// AJAX

	if(isset($_POST['deletepowersource'])){
		$ps->PowerSourceID=$_POST["powersourceid"];
		$return='no';
		if($ps->GetSource()){
			$ps->DeletePowerSource();
			$return='ok';
		}
		echo $return;
		exit;
	}

	if(isset($_REQUEST['action']) && (($_REQUEST['action']=='Create')||($_REQUEST['action']=='Update'))){
		$ps->PowerSourceID=$_REQUEST['powersourceid'];
		$ps->SourceName=$_REQUEST['sourcename'];
		$ps->DataCenterID=$_REQUEST['datacenterid'];
		$ps->IPAddress=$_REQUEST['ipaddress'];
		$ps->Community=$_REQUEST['community'];
		$ps->LoadOID=$_REQUEST['loadoid'];
		$ps->Capacity=$_REQUEST['capacity'];
		
		if($_REQUEST['action']=='Create'){
			$ps->CreatePowerSource();
		}else{
			$ps->UpdatePowerSource();
		}
	}

	if(isset($_REQUEST['powersourceid']) && $_REQUEST['powersourceid'] >0){
		$ps->PowerSourceID=$_REQUEST['powersourceid'];
		$ps->GetSource();
	}
	$psList=$ps->GetPSList();

	$dc=new DataCenter();
	$dcList=$dc->GetDCList();

?>
<!doctype html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	
	<title>openDCIM Data Center Mangement</title>
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
	include( 'sidebar.inc.php' );

echo '		<div class="main">
			<h2>',$config->ParameterArray['OrgName'],' Power Sources</h2>
			<h3>',__("Data Center Detail"),'</h3>
			<div class="center"><div>
				<form action="',$_SERVER['PHP_SELF'],'" method="POST">
					<div class="table">
						<div>
							<div><label for="powersourceid">',__("Power Source ID"),'</label></div>
							<div><select name="powersourceid" id="powersourceid" onChange="form.submit()">
								<option value="0">',__("New Power Source"),'</option>';

	foreach($psList as $psRow){
		if($psRow->PowerSourceID==$ps->PowerSourceID){$selected=' selected';}else{$selected='';}
		print "\t\t\t\t\t\t\t\t<option value=\"$psRow->PowerSourceID\"$selected>$psRow->SourceName</option>\n";
	}

echo '							</select></div>
						</div>
						<div>
							<div><label for="sourcename">'.__("Name"),'</label></div>
							<div><input type="text" name="sourcename" id="sourcename" size="50" value="',$ps->SourceName,'"></div>
						</div>
						<div>
							<div><label for="datacenterid">',__("Data Center"),'</label></div>
							<div><select name="datacenterid" id="datacenterid">';

	foreach($dcList as $dcRow){
		if($dcRow->DataCenterID==$ps->DataCenterID){$selected=' selected';}else{$selected='';}
		print "\t\t\t\t\t\t\t\t<option value=\"$dcRow->DataCenterID\"$selected>$dcRow->Name</option>\n";
	}

echo '							</select></div>
						</div>
						<div>
							<div><label for="ipaddress">',__("IP Address / Host Name"),'</label></div>
							<div><input type="text" name="ipaddress" id="ipaddress" size="20" value="',$ps->IPAddress,'"></div>
						</div>
						<div>
							<div><label for="community">',__("SNMP Community"),'</label></div>
							<div><input type="text" name="community" id="community" size=40 value="',$ps->Community,'"></div>
						</div>
						<div>
							<div><label for="loadoid">',__("Load OID"),'</label></div>
							<div><input type="text" name="loadoid" id="loadoid" size=60 value="',$ps->LoadOID,'"></div>
						</div>
						<div>
							<div><label for="capacity">',__("Capacity"),' (kW)</label></div>
							<div><input type="number" name="capacity" id="capacity" size=8 value="',$ps->Capacity,'"></div>
						</div>
						<div class="caption">';

	if($ps->PowerSourceID >0){
		echo '							<button type="submit" name="action" value="Update">',__("Update"),'</button>
										<button type="button" name="action" value="Delete">',__("Delete"),'</button>';
	} else {
		echo '							<button type="submit" name="action" value="Create">',__("Create"),'</button>';
}
?>

						</div>
					</div> <!-- END div.table -->
				</form>
			</div></div>
<?php echo '			<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Power Source delete confirmation"),'" id="deletemodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this power source and all panels fed from it?"),'
		</div>
	</div>
</div>'; ?>
		</div><!-- END div.main -->
	</div><!-- END div.page -->
<script type="text/javascript">
$('button[value=Delete]').click(function(){
	var defaultbutton={
		"<?php echo __("Yes"); ?>": function(){
			$.post('', {powersourceid: $('#powersourceid').val(),deletepowersource: '' }, function(data){
				if(data.trim()=='ok'){
					self.location=$('.main > a').last().attr('href');
					$(this).dialog("destroy");
				}else{
					alert("Danger, Will Robinson! DANGER!  Something didn't go as planned.");
				}
			});
		}
	}
	var cancelbutton={
		"<?php echo __("No"); ?>": function(){
			$(this).dialog("destroy");
		}
	}
	var modal=$('#deletemodal').dialog({
		dialogClass: 'no-close',
		modal: true,
		width: 'auto',
		buttons: $.extend({}, defaultbutton, cancelbutton)
	});
});

</script>
</body>
</html>
