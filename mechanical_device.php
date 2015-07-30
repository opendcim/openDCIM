<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Data Center Mechanical Device Detail");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$mech=new MechanicalDevice();
	
	// AJAX

	if(isset($_POST['deletemechanicaldevice'])){
		$mech->MechID=$_POST["mechid"];
		$return='no';
		if($mech->GetMech()){
			$mech->DeleteMechDevice();
			$return='ok';
		}
		echo $return;
		exit;
	}

	if(isset($_REQUEST['action']) && (($_REQUEST['action']=='Create')||($_REQUEST['action']=='Update'))){
		$mech->MechID=$_REQUEST['mechid'];
		$mech->Label=$_REQUEST['label'];
		$mech->DataCenterID=$_REQUEST['datacenterid'];
		$mech->ZoneID=$_REQUEST['zoneid'];
		$mech->PanelID=$_REQUEST['panelid'];
		$mech->BreakerSize=$_REQUEST['breakersize'];
		$mech->PanelPole=$_REQUEST['panelpole'];
		$mech->PanelID2=$_REQUEST['panelid2'];
		$mech->PanelPole2=$_REQUEST['panelpole2'];
		$mech->IPAddress=$_REQUEST['ipaddress'];
		$mech->SNMPVersion=$_REQUEST['snmpversion'];
		$mech->SNMPCommunity=$_REQUEST['snmpcommunity'];
		$mech->LoadOID=$_REQUEST['loadoid'];
		
		if($_REQUEST['action']=='Create'){
			if($mech->CreateMechDevice())
				header('Location: '.redirect("mechanical_device.php?mechid=$mech->MechID"));
		}else{
			$mech->UpdateMechDevice();
		}
	}

	if(isset($_REQUEST['mechid']) && $_REQUEST['mechid'] >0){
		$mech->MechID=$_REQUEST['mechid'];
		$mech->GetMech();
	}
	$mechList=$mech->GetMechList();

	if(isset($_POST["action"]) && $_POST["action"]=="Create_mp") {
		$class = SNMP.MeasurePoint::$TypeTab[$_POST["mp_type"]]."MeasurePoint";
		$newMP = new $class;
		$newMP->Label = $_POST["mp_label"];
		$newMP->Type = $_POST["mp_type"];
		$newMP->EquipmentType = "MechanicalDevice";
		$newMP->EquipmentID = $mech->MechID;
		$newMP->CreateMP();
	}

	$dc=new DataCenter();
	$dcList=$dc->GetDCList();

	$zone=new Zone();
	$zoneList=$zone->GetZoneList();

	$panel=new PowerPanel();
	$panelList=$panel->GetPanelList();
	
	$mpList = new MeasurePoint();
        $mpList->EquipmentType = "MechanicalDevice";
        $mpList->EquipmentID = $mech->MechID;
        $mpList = $mpList->GetMPByEquipment();
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
	<script type="text/javascript" src="scripts/jquery.min.js"></script>
	<script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
	<script type="text/javascript">
        	$(document).ready(function(){
                	$('#mechid').change(function(e){
                        	location.href='mechanical_device.php?mechid='+this.value;
                	});
        	});
	</script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
	<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '		<div class="main">
			<div class="center"><div>
				<form action="',$_SERVER['PHP_SELF'],'" method="POST" name="formMech">
					<div class="table">
						<div>
							<div><label for="mechid">',__("Mechanical Device ID"),'</label></div>
							<div><select name="mechid" id="mechid">
								<option value="0">',__("New Mechanical Device"),'</option>';

	foreach($mechList as $mechRow){
		if($mechRow->MechID==$mech->MechID){$selected=' selected';}else{$selected='';}
		print "\t\t\t\t\t\t\t\t<option value=\"$mechRow->MechID\"$selected>$mechRow->Label</option>\n";
	}

echo '							</select></div>
						</div>
						<div>
							<div><label for="label">',__("Label"),'</label></div>
							<div><input type="text" name="label" id="label" value="',$mech->Label,'"></div>
						</div>
						<div>
							<div><label for="datacenterid">',__("Data Center"),'</label></div>
							<div><select name="datacenterid" id="datacenterid">';

	foreach($dcList as $dcRow){
		if($dcRow->DataCenterID==$mech->DataCenterID){$selected=' selected';}else{$selected='';}
		print "\t\t\t\t\t\t\t\t<option value=\"$dcRow->DataCenterID\"$selected>$dcRow->Name</option>\n";
	}

echo '							</select></div>
						</div>
						<div>
							<div><label for="zoneid">',__("Zone"),'</label></div>
							<div><select name="zoneid" id="zoneid">';

	foreach($zoneList as $zoneRow){
		if($zoneRow->ZoneID==$mech->ZoneID){$selected=' selected';}else{$selected='';}
		print "\t\t\t\t\t\t\t\t<option value=\"$zoneRow->ZoneID\"$selected>$zoneRow->Description</option>\n";
	}

echo '							</select></div>
						</div>
						<div>
							<div><label for="panelid">',__("Source Panel"),'</label></div>
							<div><select name="panelid" id="panelid">
								<option value="0">'.__("None").'</option>';
	foreach($panelList as $panelRow){
		if($panelRow->PanelID==$mech->PanelID){$selected=' selected';}else{$selected='';}
		print "\t\t\t\t\t\t\t\t<option value=\"$panelRow->PanelID\"$selected>$panelRow->PanelLabel</option>\n";
	}

echo '							</select></div>
						</div>
						<div>
							<div><label for="breakersize">',__("Breaker Size (# of Poles)"),'</label></div>
							<div><select name="breakersize" id="breakersize">';
	for($n=1; $n<4; $n++) {
		if($n==$mech->BreakerSize){$selected=" selected";}else{$selected="";}
		print "<option value=\"$n\"$selected>$n</option>";
	}
echo '							</select></div>
						</div>
						<div>
							<div><label for="panelpole">',__("Panel Pole Number"),'</label></div>
							<div><input type="text" name="panelpole" id="panelpole" size="5" value="',$mech->PanelPole,'"></div>
						</div>
						<div>
							<div><label for="panelid2">',__("Source Panel (Secondary Source)"),'</label></div>
							<div><select name="panelid2" id="panelid2">
								<option value="0">'.__("None").'</option>';
	foreach($panelList as $panelRow){
		if($panelRow->PanelID==$mech->PanelID2){$selected=' selected';}else{$selected='';}
		print "\t\t\t\t\t\t\t\t<option value=\"$panelRow->PanelID\"$selected>$panelRow->PanelLabel</option>\n";
	}

echo '							</select></div>
						</div>
						<div>
							<div><label for="panelpole2">',__("Panel Pole Number (Secondary Source)"),'</label></div>
							<div><input type="text" name="panelpole2" id="panelpole2" size="5" value="',$mech->PanelPole2,'"></div>
						</div>
						<div>
							<div><label for="ipaddress">',__("IP Address"),'</label></div>
							<div><input type="text" name="ipaddress" id="ipaddress" value="',$mech->IPAddress,'"></div>
						</div>
						<div>
							<div><label for="snmpversion">',__("SNMP Version"),'</label></div>
							<div><select name="snmpversion" id="snmpversion">';
	$versions=array("1", "2c");
	foreach($versions as $val){
		if($val==$mech->SNMPVersion){$selected=' selected';}else{$selected='';}
		print "\t\t\t\t\t\t\t\t<option value=\"$val\"$selected>$val</option>\n";
	}

echo '							</select></div>
						</div>
						<div>
							<div><label for="snmpcommunity">',__("SNMP Community"),'</label></div>
							<div><input type="text" name="snmpcommunity" id="snmpcommunity" value="',$mech->SNMPCommunity,'"></div>
						</div>
						<div>
							<div><label for="loadoid">',__("Load OID"),'</label></div>
							<div><input type="text" name="loadoid" id="loadoid" size="40" value="',$mech->LoadOID,'"></div>
						</div>
						<div class="caption">';

	if($mech->MechID >0){
		echo '							<button type="submit" name="action" value="Update">',__("Update"),'</button>
										<button type="button" name="action" value="Delete">',__("Delete"),'</button>';
	} else {
		echo '							<button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
echo '						</div>
					</div>';

if($mech->MechID >0) {
	//display linked MeasurePoints

	$mpOptions="";
	foreach($mpList as $mp) {
		if($_POST["mp_mpid"] == $mp->MPID) {
			$selected = " selected";
			$selectedMP = $mp;
		} else {
			$selected = "";
		}
		$mpOptions .= "<option value=\"$mp->MPID\"$selected>[".MeasurePoint::$TypeTab[$mp->Type]."] $mp->Label</option>";
	}

	$typeOptions="";
	foreach(MeasurePoint::$TypeTab as $key => $type) {
		$typeOptions .= "<option value=\"$key\">$type</option>";
	}
	echo '<br>
        	<center>
                        <h2>'.__("Measure Points").'</h2>
                        <div class="table">
                                <div>
                                        <div><label for="mp_mpid">'.__("Measure Point ID").'</label></div>
                                        <div><select name="mp_mpid" id="mp_mpid" onChange="form.submit();">
                                                <option value="0">'.__("New Measure Point").'</option>
                                                '.$mpOptions.'
                                        </select></div>
                                </div>';
	if($_POST["mp_mpid"] == 0) {
		echo '  	<div>
                                        <div><label for="mp_label">'.__("Label").'</label></div>
                                        <div><input type="text" name="mp_label" id="mp_label"></div>
                                </div>
                                <div>
                                        <div><label for="mp_type">'.__("Type").'</label></div>
                                        <div><select name="mp_type">
                                        '.$typeOptions.'
                                        </select></div>
                                </div>
                                <div class="caption">
                                        <button type="submit" name="action" value="Create_mp">',__("Create Measure Point"),'</button>';
        } else {
		echo '		<div class="caption">
                                        <a href="measure_point.php?mpid='.$selectedMP->MPID.'">[ '.__("Edit Measure Point").' ]</a>';
        }
        echo '          	</div>
                        </div>
         	</center>       
	</form>';
}

?>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Mechanical device delete confirmation"),'" id="deletemodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this mechanical device?"),'
		</div>
	</div>
</div>'; ?>
		</div>
	</div>

<script type="text/javascript">
$('button[value=Delete]').click(function(){
	var defaultbutton={
		"<?php echo __("Yes"); ?>": function(){
			$.post('', {mechid: $('#mechid').val(),deletemechanicaldevice: '' }, function(data){
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
