<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Data Center Energy Type Detail");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$energy=new EnergyType();
	
	// AJAX

	if(isset($_POST['deleteenergytype'])){
		$energy->EnergyTypeID=$_POST["energytypeid"];
		$return='no';
		if($energy->GetEnergyType()){
			$energy->DeleteEnergyType();
			$return='ok';
		}
		echo $return;
		exit;
	}

	if(isset($_REQUEST['action']) && (($_REQUEST['action']=='Create')||($_REQUEST['action']=='Update'))){
		$energy->EnergyTypeID=$_REQUEST['energytypeid'];
		$energy->Name=$_REQUEST['name'];
		$energy->GasEmissionFactor=$_REQUEST['gasemissionfactor'];
		
		if($_REQUEST['action']=='Create'){
			$energy->CreateEnergyType();
		}else{
			$energy->UpdateEnergyType();
		}
	}

	if(isset($_REQUEST['energytypeid']) && $_REQUEST['energytypeid'] >0){
		$energy->EnergyTypeID=$_REQUEST['energytypeid'];
		$energy->GetEnergyType();
	}
	$energyList=$energy->GetEnergyTypeList();

?>
<!doctype html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	
	<title>openDCIM Data Center Management</title>
	<link rel="stylesheet" href="css/inventory.php" type="text/css">
	<link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
	<!--[if lt IE 9]>
	<link rel="stylesheet"  href="css/ie.css" type="text/css">
	<![endif]-->
	<style>
		sub
		{
			vertical-align: text-bottom;
			font-size: smaller;
		}
	</style>
	<script type="text/javascript" src="scripts/jquery.min.js"></script>
	<script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
	<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '		<div class="main">
			<div class="center"><div>
				<form action="',$_SERVER['PHP_SELF'],'" method="POST" name="formEnergy">
					<div class="table">
						<div>
							<div><label for="energytypeid">',__("Energy Type ID"),'</label></div>
							<div><select name="energytypeid" id="energytypeid" onChange="submit();">
								<option value="0">',__("New Energy Type"),'</option>';

	foreach($energyList as $energyRow){
		if($energyRow->EnergyTypeID==$energy->EnergyTypeID){$selected=' selected';}else{$selected='';}
		print "\t\t\t\t\t\t\t\t<option value=\"$energyRow->EnergyTypeID\"$selected>$energyRow->Name</option>\n";
	}

echo '							</select></div>
						</div>
						<div>
							<div><label for="name">',__("Name"),'</label></div>
							<div><input type="text" name="name" id="name" value="',$energy->Name,'"></div>
						</div>
						<div>
							<div><label for="gasemissionfactor">',__("Gas Emission Factor"),'</label></div>
							<div><input type="number" name="gasemissionfactor" id="gasemissionfactor" step="0.001" value="',$energy->GasEmissionFactor,'"> kgCO<sub>2</sub>e / kW.h</div>
						</div>
						<div class="caption">';

	if($energy->EnergyTypeID >0){
		echo '							<button type="submit" name="action" value="Update">',__("Update"),'</button>
										<button type="button" name="action" value="Delete">',__("Delete"),'</button>';
	} else {
		echo '							<button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
echo '						</div>
					</div>
				</form>
			</div></div>';

?>

<?php echo '			<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Energy Type delete confirmation"),'" id="deletemodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this Energy Type?"),'
		</div>
	</div>
</div>'; ?>
		</div>
	</div>

<script type="text/javascript">
$('button[value=Delete]').click(function(){
	var defaultbutton={
		"<?php echo __("Yes"); ?>": function(){
			$.post('', {mechid: $('#energytypeid').val(),deleteenergytype: '' }, function(data){
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
