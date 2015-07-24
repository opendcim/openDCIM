<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Data Center Air Measure Point Detail");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
	
	// AJAX

	$mp = new MeasurePoint();

	if(isset($_POST['deletemeasurepoint'])){
		$return='no';
		$mp->MPID = $_REQUEST["mpid"];
		if($mp = $mp->GetMP()){
			$mp->DeleteMP();
			$return='ok';
		}
		echo $return;
		exit;
	}

	if(isset($_REQUEST['action']) && (($_REQUEST['action']=='Create')||($_REQUEST['action']=='Update'))){
		if($_REQUEST['connectiontype'] == "SNMP") {
			$mp = new SNMPAirMeasurePoint();
			$mp->SNMPCommunity=$_REQUEST['snmpcommunity'];
			$mp->SNMPVersion=$_REQUEST['snmpversion'];
			$mp->TemperatureOID=$_REQUEST['temperatureoid'];
			$mp->HumidityOID=$_REQUEST['humidityoid'];
		} else if($_REQUEST['connectiontype'] == "Modbus") {
			$mp = new ModbusAirMeasurePoint();
			$mp->UnitID=$_REQUEST['unitid'];
			$mp->NbWords=$_REQUEST['nbwords'];
			$mp->TemperatureRegister=$_REQUEST['temperatureregister'];
			$mp->HumidityRegister=$_REQUEST['humidityregister'];
		}	
		$mp->MPID=$_REQUEST['mpid'];
		$mp->Label=$_REQUEST['label'];
		$mp->EquipmentType=$_REQUEST['equipmenttype'];
		$mp->EquipmentID=$_REQUEST['equipmentid'];
		$mp->IPAddress=$_REQUEST['ipaddress'];
		$mp->Type='air';
		$mp->ConnectionType=$_REQUEST['connectiontype'];
		if($_REQUEST['action']=='Create'){
			$mp->CreateMP();
		}else{
			$mp->UpdateMP();
		}
	}

	if(isset($_REQUEST['mpid']) && $_REQUEST['mpid'] >0){
		$mp->MPID = $_REQUEST['mpid'];
		$mp = $mp->GetMP();

		if(isset($_FILES['importfile'])) {
                	$importError = $mp->ImportMeasures($_FILES['importfile']['tmp_name']);
        	}
	}

	
	$mpList = new AirMeasurePoint();
	$mpList=$mpList->GetMPList();

	$devList = new Device();
	$devList = $devList->GetDeviceList();

	$powerPanelList = new PowerPanel();
	$powerPanelList = $powerPanelList->GetPanelList();
	
	$mechList = new MechanicalDevice();
	$mechList = $mechList->GetMechList();
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

</head>
<body>
<?php include( 'header.inc.php' ); ?>
	<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '		<div class="main">
			<div class="center"><div>
				<form action="',$_SERVER['PHP_SELF'],'" method="POST" name="form1">
					<div class="table">
						<div>
							<div><label for="mpid">',__("Measure Point ID"),'</label></div>
							<div><select name="mpid" id="mpid" onChange="form.submit()">
								<option value="0">',__("New Measure Point"),'</option>';

	foreach($mpList as $mpRow){
		if($mpRow->MPID==$mp->MPID){$selected=' selected';}else{$selected='';}
		print "\t\t\t\t\t\t\t\t<option value=\"$mpRow->MPID\"$selected>$mpRow->Label</option>\n";
	}

echo '							</select></div>
						</div>
						<div>
							<div><label for="label">',__("Label"),'</label></div>
							<div><input type="text" name="label" id="label" value="',$mp->Label,'"></div>
						</div>
                                                <div>
                                                        <div><label for="equipmenttype">',__("Equipment Type"),'</label></div>
                                                        <div><select name="equipmenttype" id="equipmenttype" onChange="OnEquipmentTypeChange()">';
        $eqTypes = array("None" => __("None"),
			"PowerDistribution" => __("PDU"),
			"PowerPanel" => __("Power Panel"),
			"MechanicalDevice" => __("Mechanical Device"),
			"Sensor" => __("Sensor"));
        foreach($eqTypes as $t => $label) {
                if($t == $mp->EquipmentType)
                        $selected=' selected';
                else
                        $selected='';
                print "\t\t\t\t\t\t\t\t<option value=\"$t\"$selected>$label</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
						<div id="equipmentid_div">
							<div><label for="equipmentid">',__("Equipment ID"),'</label></div>
							<div><select name="equipmentid" id="equipmentid">
							</select></div>
						</div>
						<div>
							<div><label for="ipaddress">',__("IP Address / Host Name"),'</label></div>
							<div><input type="text" name="ipaddress" id="ipaddress" size="20" value="',$mp->IPAddress,'"></div>
						</div>
						<div>
							<div><label for="connectiontype">',__("Connection Type"),'</label></div>
							<div><select name="connectiontype" id="connectiontype" onChange="OnConnectionTypeChange()">';
	$coTypes = array("SNMP", "Modbus");
	foreach($coTypes as $t) {
		if($t == $mp->ConnectionType)
			$selected=' selected';
		else
			$selected='';	
		print "\t\t\t\t\t\t\t\t<option value=\"$t\"$selected>$t</option>\n";
	}
echo '							</select></div>
						</div>
						<div id="snmp_com">
							<div><label for="snmpcommunity">',__("SNMP Community"),'</label></div>
							<div><input type="text" name="snmpcommunity" id="snmpcommunity" value=',($mp->ConnectionType=="SNMP")?$mp->SNMPCommunity:"",'></div>
						</div>
						<div id="snmp_version">
							<div><label for="snmpversion">',__("SNMP Version"),'</label></div>
							<div><select name="snmpversion" id="snmpversion">';
	$versionList = array('1','2c','3');
	foreach($versionList as $v) {
		if($v == $mp->SNMPVersion)
			$selected = ' selected';
		else
			$selected = '';
		print "\t\t\t\t\t\t\t\t\t<option value=\"$v\"$selected>$v</option>\n";
	}
echo '							</select></div>
						</div>
						<div id="snmp_temperatureoid">
							<div><label for="temperatureoid">',__("Temperature OID"),'</label></div>
							<div><input type="text" name="temperatureoid" id="temperatureoid" value=',($mp->ConnectionType=="SNMP")?$mp->TemperatureOID:"",'></div>
						</div>
						<div id="snmp_humidityoid">
							<div><label for="humidityoid">',__("Humidity OID"),'</label></div>
							<div><input type="text" name="humidityoid" id="humidityoid" value=',($mp->ConnectionType=="SNMP")?$mp->HumidityOID:"",'></div>
						</div>
						<div id="modbus_unit">
							<div><label for="unitid">',__("Unit ID"),'</label></div>
							<div><input type="text" name="unitid" id="unitid" value=',($mp->ConnectionType=="Modbus")?$mp->UnitID:"",'></div>
						</div>
						<div id="modbus_nbwords">
							<div><label for="nbwords">',__("Number of words"),'</label></div>
							<div><input type="text" name="nbwords" id="nbwords" value=',($mp->ConnectionType=="Modbus")?$mp->NbWords:"",'></div>
						</div>
						<div id="modbus_temperatureregister">
							<div><label for="temperatureregister">',__("Temperature Register"),'</label></div>
							<div><input type="text" name="temperatureregister" id="temperatureregister" value=',($mp->ConnectionType=="Modbus")?$mp->TemperatureRegister:"",'></div>
						</div>
						<div id="modbus_humidityregister">
							<div><label for="humidityregister">',__("Humidity Register"),'</label></div>
							<div><input type="text" name="humidityregister" id="humidityregister" value=',($mp->ConnectionType=="Modbus")?$mp->HumidityRegister:"",'></div>
						</div>
						<div class="caption">';

	if($mp->MPID >0){
		echo '							<button type="submit" name="action" value="Update">',__("Update"),'</button>
									<button type="button" name="action" value="Delete">',__("Delete"),'</button>
 									<button type="button" name="importButton" id="importButton" value="Import">',__("Import"),'</button>';
	} else {
		echo '							<button type="submit" name="action" value="Create">',__("Create"),'</button>';
}
echo '						</div>
					</div> <!-- END div.table -->
				</form>';

?>
			</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Measure Point delete confirmation"),'" id="deletemodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this air measure point?"),'
		</div>
	</div>
</div>'; ?>
		</div><!-- END div.main -->
	</div><!-- END div.page -->

<div id="dlg_importfile" style="display:none;" title="<?php echo __("Import Measure Point From File");?>">
        <br>
        <form enctype="multipart/form-data" name="frmImport" id="frmImport" method="POST">
                <input type="hidden" name="mpid" value="<?php echo $mp->MPID;  ?>" />
                <input type="file" size="60" id="importfile" name="importfile" />
        </form>
</div>

<div id="dlg_import_err" style="display:none;" title="<?php echo __("Import log");?>">
<?php
if (isset($importError)){
        print $importError;
}
?>
</div>


<script type="text/javascript">
$('button[value=Delete]').click(function(){
	var defaultbutton={
		"<?php echo __("Yes"); ?>": function(){
			$.post('', {mpid: $('#mpid').val(),deletemeasurepoint: '' }, function(data){
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

var powerDistribution = {<?php	$n=0;
				foreach($devList as $dev) {
					if($dev->DeviceType == "CDU") {
						if($n == 0)
							echo $dev->DeviceID.': "'.$dev->Label.'"';
						else
							echo ', '.$dev->DeviceID.': "'.$dev->Label.'"';
						$n++;
					}
				}
			?>};

var powerPanel = {<?php	$n=0;
			foreach($powerPanelList as $powerPanel) {
				if($n == 0)
					echo $powerPanel->PanelID.': "'.$powerPanel->PanelLabel.'"';
				else
					echo ', '.$powerPanel->PanelID.': "'.$powerPanel->PanelLabel.'"';
				$n++;
			}
		?>};

var mechanicalDevice = {<?php	$n=0;
				foreach($mechList as $mech) {
					if($n == 0)
						echo $mech->MechID.': "'.$mech->Label.'"';
					else
						echo ', '.$mech->MechID.': "'.$mech->Label.'"';
					$n++;
				}
			?>};

var sensor = {<?php	$n=0;
			foreach($devList as $dev) {
				if($dev->DeviceType == "Sensor") {
					if($n == 0)
						echo $dev->DeviceID.': "'.$dev->Label.'"';
					else
						echo ', '.$dev->DeviceID.': "'.$dev->Label.'"';
					$n++;
				}
			}
		?>};

var loadedEquipmentType = "<?php echo $mp->EquipmentType; ?>";
var loadedEquipmentID = "<?php echo $mp->EquipmentID; ?>";

function OnConnectionTypeChange() {
	var snmp = ['com', 'version', 'temperatureoid', 'humidityoid'];
	var modbus = ['unit', 'nbwords', 'temperatureregister', 'humidityregister'];

	if(document.form1.connectiontype.options[document.form1.connectiontype.selectedIndex].value == 'Modbus') {
		var snmpval="none";
		var modbusval="";
	} else {
		var snmpval="";
		var modbusval="none";
	}
	for(var n=0; n<snmp.length; n++) {
		document.getElementById('snmp_'+snmp[n]).style.display=snmpval;
	}
	for(var n=0; n<modbus.length; n++) {
		document.getElementById('modbus_'+modbus[n]).style.display=modbusval;
	}
}

function OnEquipmentTypeChange() {
	var typeSelect = document.getElementById("equipmenttype");
	var idSelect = document.getElementById("equipmentid");
	var idDiv = document.getElementById("equipmentid_div");
	var equipmentType = typeSelect.options[typeSelect.selectedIndex].value;
	var newOpt;

	switch(equipmentType) {
		case "None":
			idDiv.style.display = "none";
			for(var n=0; n<idSelect.options.length; n++)
				idSelect.remove(n);

			newOpt = document.createElement("option");
			newOpt.text = "None";
			newOpt.value = "None";
			idSelect.add(newOpt);
			break;
		case "PowerDistribution":
			idDiv.style.display = "";
			changeOptions(idSelect, powerDistribution);
			break;
		case "PowerPanel":
			idDiv.style.display = "";
                        changeOptions(idSelect, powerPanel);
                        break;
		case "MechanicalDevice":
                        idDiv.style.display = "";
                        changeOptions(idSelect, mechanicalDevice);
                        break;
		case "Sensor":
			idDiv.style.display = "";
                        changeOptions(idSelect, sensor); 
			break;
		default:
			alert("Something's wrong with your equipment type.");
	}
	if(equipmentType == loadedEquipmentType)
		for(var n in idSelect.options)
			if(idSelect.options[n].value == loadedEquipmentID)
				idSelect.selectedIndex = n;
}

function changeOptions(selectBox, newOptions) {
	var newOpt;

	for(var n=selectBox.options.length-1; n>=0; n--)
		selectBox.remove(n);

	for(var n in newOptions) {
		newOpt = document.createElement("option");
		newOpt.text = newOptions[n];
		newOpt.value = n;
		selectBox.add(newOpt);
	}
}

$(document).ready(function() {
	OnConnectionTypeChange();
	OnEquipmentTypeChange();
	 <?php
                if(isset($importError))
                        echo "alert('".$importError."');";
        ?>
        $('#mpid').change(function(e) {
                location.href='measure_point_air.php?mpid='+this.value;
        });
	$( "#importButton" ).click(function() {
		$("#dlg_importfile").dialog({
			resizable: false,
			width: 400,
			height: 200,
			modal: true,                                    
			buttons: {      
				<?php echo __("Import");?>: function() {                        
					$('#frmImport').submit();
				},
				<?php echo __("Cancel");?>: function() {                                                                                               
				    $("#dlg_importfile").dialog("close");
				}
			}
		});
	});
});

</script>
</body>
</html>
