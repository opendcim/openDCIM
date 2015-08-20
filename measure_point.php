<?php
        require_once('db.inc.php');
        require_once('facilities.inc.php');

        $subheader=__("Data Center Measure Point Detail");

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

        if(isset($_POST['deletemeasures'])){
                $return='no';
                $mp->MPID = $_REQUEST["mpid"];
                if($mp = $mp->GetMP()){
                        $mp->DeleteMeasures();
                        $return='ok';
                }
                echo $return;
                exit;
        }

	if(isset($_POST["manualentry"]) && isset($_POST["mpid"]) && $_POST["mpid"] > 0) {
                $mp = new MeasurePoint();
                $mp->MPID = $_POST["mpid"];
                $ret=array();
                if($mp = $mp->GetMP()) {
                        switch($mp->Type) {
                                case "elec":
                                        $measure = new ElectricalMeasure();
                                        $measure->MPID = $mp->MPID;
                                        $measure->Wattage1 = $_POST["wattage1"];
                                        $measure->Wattage2 = $_POST["wattage2"];
                                        $measure->Wattage3 = $_POST["wattage3"];
                                        $measure->Energy = $_POST["energy"];
                                        $measure->Date = date("Y-m-d H:i:s");
                                        $measure->CreateMeasure();

                                        $ret["wattage1"] = $measure->Wattage1;
                                        $ret["wattage2"] = $measure->Wattage2;
                                        $ret["wattage3"] = $measure->Wattage3;
                                        $ret["energy"] = $measure->Energy;
                                        $ret["date"] = $measure->Date;
                                        break;
                                case "cooling":
                                        $measure = new CoolingMeasure();
                                        $measure->MPID = $mp->MPID;
                                        $measure->FanSpeed = $_POST["fanspeed"];
                                        $measure->Cooling = $_POST["cooling"];
                                        $measure->Date = date("Y-m-d H:i:s");
                                        $measure->CreateMeasure();

                                        $ret["fanspeed"] = $measure->FanSpeed;
                                        $ret["cooling"] = $measure->Cooling;
                                        $ret["date"] = $measure->Date;
                                        break;
                                case "air":
                                        $measure = new AirMeasure();
                                        $measure->MPID = $mp->MPID;
                                        $measure->Temperature = $_POST["temperature"];
                                        $measure->Humidity = $_POST["humidity"];
                                        $measure->Date = date("Y-m-d H:i:s");
                                        $measure->CreateMeasure();

                                        $ret["temperature"] = $measure->Temperature;
                                        $ret["humidity"] = $measure->Humidity;
                                        $ret["date"] = $measure->Date;
                                        break;
                                default:
                                        break;
                        }
			$ret["nbmeasure"] = $mp->GetNbMeasures();
                }
                header('Content-Type: application/json');
                echo json_encode($ret);
                exit;
        }

	if(isset($_REQUEST['action']) && (($_REQUEST['action']=='Create')||($_REQUEST['action']=='Update'))){
		$class = $_REQUEST['connectiontype'].MeasurePoint::$TypeTab[$_REQUEST['type']]."MeasurePoint";
		$mp = new $class;

                $mp->MPID=$_REQUEST['mpid'];
                $mp->Label=$_REQUEST['label'];
                $mp->EquipmentType=$_REQUEST['equipmenttype'];
                $mp->EquipmentID=$_REQUEST['equipmentid'];
                $mp->IPAddress=$_REQUEST['ipaddress'];
                $mp->Type=$_REQUEST['type'];
                $mp->ConnectionType=$_REQUEST['connectiontype'];

		switch($mp->Type) {
			case "elec":
				$mp->DataCenterID=$_REQUEST['datacenterid'];
				$mp->EnergyTypeID=$_REQUEST['energytypeid'];
				$mp->Category=$_REQUEST['category'];
				$mp->UPSPowered=($_REQUEST['upspowered'] == "on")?1:0;
				$mp->PowerMultiplier=$_REQUEST['powermultiplier'];
				$mp->EnergyMultiplier=$_REQUEST['energymultiplier'];
				switch($mp->ConnectionType) {
					case "SNMP":
						$mp->OID1=$_REQUEST['oid1'];
						$mp->OID2=$_REQUEST['oid2'];
						$mp->OID3=$_REQUEST['oid3'];
						$mp->OIDEnergy=$_REQUEST['oidenergy'];
						break;
					case "Modbus":
						$mp->Register1=$_REQUEST['register1'];
						$mp->Register2=$_REQUEST['register2'];
						$mp->Register3=$_REQUEST['register3'];
						$mp->RegisterEnergy=$_REQUEST['registerenergy'];
						break;
					case "IPMI":
						$mp->Sensor1=$_REQUEST['sensor1'];
						$mp->Sensor2=$_REQUEST['sensor2'];
						$mp->Sensor3=$_REQUEST['sensor3'];
						$mp->SensorEnergy=$_REQUEST['sensorenergy'];
						break;
				}
				break;
			case "cooling":
				$mp->FanSpeedMultiplier=$_REQUEST['fanspeedmultiplier'];
		                $mp->CoolingMultiplier=$_REQUEST['coolingmultiplier'];
				switch($mp->ConnectionType) {
					case "SNMP":
						$mp->FanSpeedOID=$_REQUEST['fanspeedoid'];
						$mp->CoolingOID=$_REQUEST['coolingoid'];
						break;
					case "Modbus":
						$mp->FanSpeedRegister=$_REQUEST['fanspeedregister'];
						$mp->CoolingRegister=$_REQUEST['coolingregister'];
						break;
					case "IPMI":
						$mp->FanSpeedSensor=$_REQUEST['fanspeedsensor'];
						$mp->CoolingSensor=$_REQUEST['coolingsensor'];
						break;
				}
				break;
			case "air":
				$mp->TemperatureMultiplier=$_REQUEST["temperaturemultiplier"];
		                $mp->HumidityMultiplier=$_REQUEST["humiditymultiplier"];
				switch($mp->ConnectionType) {
					case "SNMP":
						$mp->TemperatureOID=$_REQUEST['temperatureoid'];
						$mp->HumidityOID=$_REQUEST['humidityoid'];
						break;
					case "Modbus":
						$mp->TemperatureRegister=$_REQUEST['temperatureregister'];
						$mp->HumidityRegister=$_REQUEST['humidityregister'];
						break;
					case "IPMI":
						$mp->TemperatureSensor=$_REQUEST['temperaturesensor'];
						$mp->HumiditySensor=$_REQUEST['humiditysensor'];
						break;
				}
				break;
		}
		switch($mp->ConnectionType) {
			case "SNMP":
				$mp->SNMPCommunity=($_REQUEST['snmpcommunity']=="")?$config->ParameterArray["SNMPCommunity"]:$_REQUEST['snmpcommunity'];
				$mp->SNMPVersion=($_REQUEST['snmpversion']=="")?$config->ParameterArray["SNMPVersion"]:$_REQUEST['snmpversion'];
				if($mp->SNMPVersion == "3") {
					$mp->v3SecurityLevel=($_REQUEST['v3securitylevel']=="")?$config->ParameterArray["v3SecurityLevel"]:$_REQUEST['v3securitylevel'];
					$mp->v3AuthProtocol=($_REQUEST['v3authprotocol']=="")?$config->ParameterArray["v3AuthProtocol"]:$_REQUEST['v3authprotocol'];
					$mp->v3AuthPassphrase=($_REQUEST['v3authpassphrase']=="")?$config->ParameterArray["v3AuthPassphrase"]:$_REQUEST['v3authpassphrase'];
					$mp->v3PrivProtocol=($_REQUEST['v3privprotocol']=="")?$config->ParameterArray["v3PrivProtocol"]:$_REQUEST['v3privprotocol'];
					$mp->v3PrivPassphrase=($_REQUEST['v3privpassphrase']=="")?$config->ParameterArray["v3PrivPassphrase"]:$_REQUEST['v3privpassphrase'];
				}
				break;
			case "Modbus":
				$mp->UnitID=$_REQUEST['unitid'];
				$mp->NbWords=$_REQUEST['nbwords'];
				break;
			case "IPMI":
				$mp->UserName=$_REQUEST['username'];
				$mp->Password=$_REQUEST['password'];
				$mp->Interface=$_REQUEST['interface'];
				break;
		}
                if($_REQUEST['action']=='Create'){
                        if($mp->CreateMP())
                                header('Location: '.redirect("measure_point.php?mpid=$mp->MPID"));
                }else{
                        $mp->UpdateMP();
                }
        }

	if(isset($_REQUEST['mpid']) && $_REQUEST['mpid'] >0){
                $mp->MPID = $_REQUEST['mpid'];
                $mp = $mp->GetMP();

		$class = MeasurePoint::$TypeTab[$mp->Type]."Measure";

		$lastMeasure = new $class;
		$lastMeasure->MPID = $mp->MPID;
		$lastMeasure = $lastMeasure->GetLastMeasure();

                if(isset($_FILES['importfile'])) {
                        $importError = $mp->ImportMeasures($_FILES['importfile']['tmp_name']);
                }
        }

	$nbMeasures = $mp->GetNbMeasures();

	$mpList = new MeasurePoint();
        $mpList=$mpList->GetMPList();

	$dcList = new DataCenter();
        $dcList = $dcList->GetDCList();

        $energytypeList = new EnergyType();
        $energytypeList = $energytypeList->GetEnergyTypeList();

        $devList = new Device();
        $devList = $devList->GetDeviceList();

        $powerPanelList = new PowerPanel();
        $powerPanelList = $powerPanelList->GetPanelList();

        $mechList = new MechanicalDevice();
        $mechList = $mechList->GetMechList();

        $eqTypes = array("None" => __("None"),
                        "Device" => __("Device"),
                        "PowerPanel" => __("Power Panel"),
                        "MechanicalDevice" => __("Mechanical Device"));

        $coTypes = array("SNMP", "Modbus", "IPMI");

        $categories = array("none", "IT", "Cooling", "Other Mechanical", "UPS Input", "UPS Output", "Energy Reuse", "Renewable Energy");

        $multiplierList = array('0.01', '0.1', '1', '10', '100');

        $versionList = array('1','2c','3');

	$securityLevelList = array('noAuthNoPriv','authNoPriv','authPriv');

	$authProtocolList = array('MD5','SHA');

	$privProtocolList = array('DES','AES');
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
		var device = {<?php  $n=0;
				foreach($devList as $dev) {
					if($n == 0)
						echo $dev->DeviceID.': "'.$dev->Label.'"';
					else
						echo ', '.$dev->DeviceID.': "'.$dev->Label.'"';
					$n++;
				}
			?>};

		var powerPanel = {<?php $n=0;
					foreach($powerPanelList as $powerPanel) {
						if($n == 0)
							echo $powerPanel->PanelID.': "'.$powerPanel->PanelLabel.'"';
						else
							echo ', '.$powerPanel->PanelID.': "'.$powerPanel->PanelLabel.'"';
						$n++;
					}
				?>};

		var mechanicalDevice = {<?php   $n=0;
						foreach($mechList as $mech) {
							if($n == 0)
								echo $mech->MechID.': "'.$mech->Label.'"';
							else
								echo ', '.$mech->MechID.': "'.$mech->Label.'"';
							$n++;
						}
					?>};

		var loadedEquipmentType = "<?php echo $mp->EquipmentType; ?>";
		var loadedEquipmentID = "<?php echo $mp->EquipmentID; ?>";

		$(document).ready(function() {
			OnTypeChange();
			OnConnectionTypeChange();
			OnEquipmentTypeChange();

			$('#mpid').change(function(e) {
				location.href='measure_point.php?mpid='+this.value;
			});

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

			$('button[value=DeleteMeasures]').click(function(){
				var defaultbutton={
					"<?php echo __("Yes"); ?>": function(){
						$.post('', {mpid: $('#mpid').val(),deletemeasures: '' }, function(data){
							if(data.trim()=='ok'){
								$('#deletemeasuresmodal').dialog("destroy");
								alert("<?php echo __("Measures deleted."); ?>");
								window.location.reload();
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
				var modal=$('#deletemeasuresmodal').dialog({
					dialogClass: 'no-close',
					modal: true,
					width: 'auto',
					buttons: $.extend({}, defaultbutton, cancelbutton)
				});
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

			$('#measure_edit').on('click', function(e){
                                var btn=$(e.currentTarget);
                                var targetTab=document.getElementsByClassName("measure_field");
                                if(btn.val()=='edit'){
                                        for(var n=0; n<targetTab.length; n++)
						targetTab[n].readOnly = false;
					targetTab[0].select();
                                        btn.val('submit').text(btn.data('submit')).css('height','2em');
                                }else{
                                        var args = new Object();
					var type = document.getElementById("type");

                                        btn.val('edit').text(btn.data('edit')).css('height','');
                                        args["mpid"] = $('#mpid').val();
                                        args["manualentry"] = "true";

					switch(type.options[type.selectedIndex].value) {
						case "elec":
							args["wattage1"] = document.getElementById("measure_wattage1").value;
							args["wattage2"] = document.getElementById("measure_wattage2").value;
							args["wattage3"] = document.getElementById("measure_wattage3").value;
							args["energy"] = document.getElementById("measure_energy").value;
							break;
						case "cooling":
							args["fanspeed"] = document.getElementById("measure_fanspeed").value;
							args["cooling"] = document.getElementById("measure_cooling").value;
							break;
						case "air":
							args["temperature"] = document.getElementById("measure_temperature").value;
							args["humidity"] = document.getElementById("measure_humidity").value;
							break;
						default:
							break;
					}

                                        $.post('', args).done(function(data){
                                                var targetTab=document.getElementsByClassName("measure_field");
                                                for(var n=0; n<targetTab.length; n++)
							targetTab[n].readOnly = true;
						switch(type.options[type.selectedIndex].value) {
							case "elec":
								 document.getElementById("measure_wattage1").value = data["wattage1"];
								 document.getElementById("measure_wattage2").value = data["wattage2"];
								 document.getElementById("measure_wattage3").value = data["wattage3"];
								 document.getElementById("measure_energy").value = data["energy"];
								break;
							case "cooling":
								 document.getElementById("measure_fanspeed").value = data["fanspeed"];
								 document.getElementById("measure_cooling").value = data["cooling"];
								break;
							case "air":
								 document.getElementById("measure_temperature").value = data["temperature"];
								 document.getElementById("measure_humidity").value = data["humidity"];
								break;
							default:
								break;
						}
                                                $('#lastread').text(data["date"]);
						$('#nbmeasure').text(data["nbmeasure"]);
                                        });
                                }
                        });

			$('#snmpcommunity,#v3authpassphrase,#v3privpassphrase')
				.focus(function(){$(this).attr('type','text');})
                		.blur(function(){$(this).attr('type','password');});
			
			OnCategoryChange();
		});

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
				case "Device":
					idDiv.style.display = "";
					changeOptions(idSelect, device);
					break;
				case "PowerPanel":
					idDiv.style.display = "";
					changeOptions(idSelect, powerPanel);
					break;
				case "MechanicalDevice":
					idDiv.style.display = "";
					changeOptions(idSelect, mechanicalDevice);
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

		function OnTypeChange() {
			var type = document.getElementById("type").value;
			var typeList = document.getElementsByClassName("mp_type");

			for(var n=0; n<typeList.length; n++) {
				typeList[n].style.display = "none";
			}
			switch(type) {
				case "elec":
					var elecList = document.getElementsByClassName("mp_elec");
					for(var n=0; n<elecList.length; n++)
						elecList[n].style.display = "";
					break;
				case "cooling":
					var coolingList = document.getElementsByClassName("mp_cooling");
					for(var n=0; n<coolingList.length; n++)
						coolingList[n].style.display = "";
					break;
				case "air":
					var airList = document.getElementsByClassName("mp_air");
					for(var n=0; n<airList.length; n++)
						airList[n].style.display = "";
					break;
			}
			OnConnectionTypeChange();
		}

		function OnConnectionTypeChange() {
			var connectionType = document.getElementById("connectiontype").value;
			var cotypeList = document.getElementsByClassName("mp_cotype");
			var type = document.getElementById("type").value;
		
			for(var n=0; n<cotypeList.length; n++) {
				cotypeList[n].style.display = "none";
			}

			switch(connectionType) {
				case 'SNMP':
					var snmpList = document.getElementsByClassName("mp_SNMP");
					for(var n=0; n<snmpList.length; n++)
						snmpList[n].style.display = "";
					var snmpTypeList = document.getElementsByClassName("mp_SNMP"+type);
					for(var n=0; n<snmpTypeList.length; n++)
						snmpTypeList[n].style.display = "";
					break;
				case 'Modbus':
					var modbusList = document.getElementsByClassName("mp_Modbus");
					for(var n=0; n<modbusList.length; n++)
						modbusList[n].style.display = "";
					var modbusTypeList = document.getElementsByClassName("mp_Modbus"+type);
					for(var n=0; n<modbusTypeList.length; n++)
						modbusTypeList[n].style.display = "";
					break;
				case 'IPMI':
					var ipmiList = document.getElementsByClassName("mp_IPMI");
					for(var n=0; n<ipmiList.length; n++)
						ipmiList[n].style.display = "";
					var ipmiTypeList = document.getElementsByClassName("mp_IPMI"+type);
					for(var n=0; n<ipmiTypeList.length; n++)
						ipmiTypeList[n].style.display = "";
					break;
			}
			OnSNMPVersionChange();
		}

		function OnSNMPVersionChange() {
			var v3List = document.getElementsByClassName("SNMPv3");
			var snmpversion = document.getElementById("snmpversion").value;
			var cotype = document.getElementById("connectiontype").value;

			if(snmpversion == "3" && cotype == "SNMP")
				var val = "";
			else
				var val = "none";

			for(var n=0; n<v3List.length; n++) {
				v3List[n].style.display = val;
			}
		}

		function OnCategoryChange() {
			var category = document.getElementById("category").value;
			if(category == "UPS Input" || category == "UPS Output")
				document.getElementById("div_upspowered").style.display = "none";
			else
				document.getElementById("div_upspowered").style.display = "";
		}
	</script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
        <div class="page">
<?php
        include( 'sidebar.inc.php' );



echo '          <div class="main">
                        <div class="center"><div>
                                <form action="',$_SERVER['PHP_SELF'],'" method="POST">
					<div class="table">
						<div>
							<div><label for="mpid">',__("Measure Point ID"),'</label></div>
                                                        <div><select name="mpid" id="mpid">
                                                                <option value="0">',__("New Measure Point"),'</option>';

        foreach($mpList as $mpRow){
                if($mpRow->MPID==$mp->MPID){$selected=' selected';}else{$selected='';}
                print "\t\t\t\t\t\t\t\t<option value=\"$mpRow->MPID\"$selected>$mpRow->Label</option>\n";
        }

echo '                                                  </select></div>
                                                </div>
						<div>
                                                        <div><label for="label">',__("Label"),'</label></div>
                                                        <div><input type="text" name="label" id="label" value="',$mp->Label,'"></div>
                                                </div>
						<div>
                                                        <div><label for="equipmenttype">',__("Equipment Type"),'</label></div>
                                                        <div><select name="equipmenttype" id="equipmenttype" title="'.__("Type of equipment linked to this measure point").'" onChange="OnEquipmentTypeChange()">';
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
                                                        <div><select name="equipmentid" title="'.__("Equipment linked to this measure point").'" id="equipmentid">
                                                        </select>';
if($mp->EquipmentID > 0) {
        switch($mp->EquipmentType) {
                case "Device":
                        $eqPage = "devices.php?DeviceID=".$mp->EquipmentID;
                        break;
                case "PowerPanel":
                        $eqPage = "power_panel.php?PanelID=".$mp->EquipmentID;
                        break;
                case "MechanicalDevice":
                        $eqPage = "mechanical_device.php?mechid=".$mp->EquipmentID;
                        break;
                default:
                        $eqPage = false;
                        break;
        }
        if($eqPage) {
                echo'                                   <a href="'.$eqPage.'">['.__("Edit Equipment").']</a>';
        }
}
echo '                                                  </div>
                                                </div>
                                                <div>
                                                        <div><label for="ipaddress">',__("IP Address / Host Name"),'</label></div>
                                                        <div><input type="text" name="ipaddress" id="ipaddress" size="20" value="',$mp->IPAddress,'"></div>
                                                </div>';
if($mp->MPID == 0) {
	$disabled = '';
} else {
	$disabled = 'disabled';
	echo '<input type="text" name="type" hidden value="'.$mp->Type.'">';
}
echo '						<div>
							<div><label for="type">',__("Type"),'</label></div>
							<div><select name="type" id="type" '.$disabled.' onChange="OnTypeChange();">';
	foreach(MeasurePoint::$TypeTab as $key => $val) {
		if($key == $mp->Type)
			$selected=' selected';
		else
			$selected='';
		print "\t\t\t\t\t\t\t\t<option value=\"$key\"$selected>".__($val)."</option>\n";
	}
echo '							</select></div>
						</div>
						<div>
                                                        <div><label for="connectiontype">',__("Connection Type"),'</label></div>
                                                        <div><select name="connectiontype" id="connectiontype" onChange="OnConnectionTypeChange()">';
        foreach($coTypes as $t) {
                if($t == $mp->ConnectionType)
                        $selected=' selected';
                else
                        $selected='';
                print "\t\t\t\t\t\t\t\t<option value=\"$t\"$selected>$t</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
						<div>
							<div>&nbsp;</div>
						</div>
						<div class="mp_type mp_elec">
                                                        <div><label for="datacenterid">',__("Data Center ID"),'</label></div>
                                                        <div><select name="datacenterid">
                                                                <option value="0">'.__("None").'</option>';
        foreach($dcList as $dc) {
                if($dc->DataCenterID == $mp->DataCenterID)
                        print "\t\t\t\t\t\t\t\t<option value=\"$dc->DataCenterID\" selected>$dc->Name</option>\n";
                else
                        print "\t\t\t\t\t\t\t\t<option value=\"$dc->DataCenterID\">$dc->Name</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
						<div class="mp_type mp_elec">
                                                        <div><label for="energytypeid">',__("Energy Purchased"),'</label></div>
                                                        <div><select name="energytypeid">';
        foreach($energytypeList as $key => $obj) {
                if($obj->EnergyTypeID == $mp->EnergyTypeID)
                        print "\t\t\t\t\t\t\t\t<option value=\"$obj->EnergyTypeID\" selected>$obj->Name</option>\n";
                else
                        print "\t\t\t\t\t\t\t\t<option value=\"$obj->EnergyTypeID\">$obj->Name</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
						<div class="mp_type mp_elec">
                                                        <div><label for="category">',__("Category"),'</label></div>
                                                        <div><select name="category" id="category" onChange="OnCategoryChange();">';
        foreach($categories as $c) {
                if($c == $mp->Category)
                        $selected = ' selected';
                else
                        $selected = '';
                print "\t\t\t\t\t\t\t\t<option value=\"$c\"$selected>$c</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
						<div class="mp_type mp_elec" id="div_upspowered">
                                                        <div><label for="upspowered">',__("UPS Powered"),'</label></div>';
if($mp->Type == "elec")
        $checked = ($mp->UPSPowered)?"checked":"";
echo '
                                                        <div><input type="checkbox" name="upspowered" id="upspowered" title="'.__("Is this measure point powered by an UPS?").'" ',$checked,'></div>
                                                </div>
                                                <div class="mp_type mp_elec">
                                                        <div><label for="powermultiplier">',__("Power Multiplier"),'</label></div>
                                                        <div><select name="powermultiplier" id="powermultiplier">';
        foreach($multiplierList as $m) {
                if($m == $mp->PowerMultiplier || (is_null($mp->PowerMultiplier) && $m == '1'))
                        $selected=' selected';
                else
                        $selected='';
                print "\t\t\t\t\t\t\t\t<option value=\"$m\"$selected>$m</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
                                                <div class="mp_type mp_elec">
                                                        <div><label for="energymultiplier">',__("Energy Multiplier"),'</label></div>
                                                        <div><select name="energymultiplier" id="energymultiplier">';
        foreach($multiplierList as $m) {
                if($m == $mp->EnergyMultiplier || (is_null($mp->EnergyMultiplier)&& $m == '1'))
                        $selected=' selected';
                else
                        $selected='';
                print "\t\t\t\t\t\t\t\t<option value=\"$m\"$selected>$m</option>\n";
        }
echo '                                  		</select></div>
						</div>
						<div class="mp_type mp_cooling">
							<div><label for="fanspeedmultiplier">'.__("Fan Speed Multiplier").'</label></div>
                                                        <div><select name="fanspeedmultiplier">';
        foreach($multiplierList as $m) {
                if($m == $mp->FanSpeedMultiplier || (is_null($mp->FanSpeedMultiplier) && $m == '1'))
                        $selected=' selected';
                else
                        $selected='';
                print "\t\t\t\t\t\t\t\t<option value=\"$m\"$selected>$m</option>\n";
        }
echo '                                                  </select></div>
						</div>
						<div class="mp_type mp_cooling">
                                                        <div><label for="coolingmultiplier">',__("Compressor Usage Multiplier"),'</label></div>
                                                        <div><select name="coolingmultiplier" id="coolingmultiplier">';
        foreach($multiplierList as $m) {
                if($m == $mp->CoolingMultiplier || (is_null($mp->CoolingMultiplier)&& $m == '1'))
                        $selected=' selected';
                else
                        $selected='';
                print "\t\t\t\t\t\t\t\t<option value=\"$m\"$selected>$m</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
						<div class="mp_type mp_air">
                                                        <div><label for="temperaturemultiplier">'.__("Temperature Multiplier").'</label></div>
                                                        <div><select name="temperaturemultiplier">';
        foreach($multiplierList as $m) {
                if($m == $mp->TemperatureMultiplier || (is_null($mp->TemperatureMultiplier) && $m == '1'))
                        $selected=' selected';
                else
                        $selected='';
                print "\t\t\t\t\t\t\t\t<option value=\"$m\"$selected>$m</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
                                                <div class="mp_type mp_air">
                                                        <div><label for="humiditymultiplier">',__("Humidity Multiplier"),'</label></div>
                                                        <div><select name="humiditymultiplier" id="humiditymultiplier">';
        foreach($multiplierList as $m) {
                if($m == $mp->HumidityMultiplier || (is_null($mp->HumidityMultiplier)&& $m == '1'))
                        $selected=' selected';
                else
                        $selected='';
                print "\t\t\t\t\t\t\t\t<option value=\"$m\"$selected>$m</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
						<div>
							<div>&nbsp;</div>
						</div>
						<div class="mp_cotype mp_SNMP">
                                                        <div><label for="air_snmpcommunity">',__("SNMP Community"),'</label></div>
                                                        <div><input type="text" name="snmpcommunity" id="snmpcommunity" value="',($mp->ConnectionType=="SNMP")?$mp->SNMPCommunity:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_SNMP">
                                                        <div><label for="air_snmpversion">',__("SNMP Version"),'</label></div>
                                                        <div><select name="snmpversion" id="snmpversion" onChange="OnSNMPVersionChange();">';
        foreach($versionList as $v) {
                if($v == $mp->SNMPVersion)
                        $selected = ' selected';
                else
                        $selected = '';
                print "\t\t\t\t\t\t\t\t\t<option value=\"$v\"$selected>$v</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
						<div class="mp_cotype mp_SNMP SNMPv3">
                                                        <div><label for="v3securitylevel">',__("SNMPv3 Security Level"),'</label></div>
                                                        <div><select name="v3securitylevel">';
        foreach($securityLevelList as $v) {
                if($v == $mp->v3SecurityLevel)
                        $selected = ' selected';
                else
                        $selected = '';
                print "\t\t\t\t\t\t\t\t\t<option value=\"$v\"$selected>$v</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
                                                <div class="mp_cotype mp_SNMP SNMPv3">
                                                        <div><label for="v3authprotocol">',__("SNMPv3 AuthProtocol"),'</label></div>
                                                        <div><select name="v3authprotocol">';
        foreach($authProtocolList as $v) {
                if($v == $mp->v3AuthProtocol)
                        $selected = ' selected';
                else
                        $selected = '';
                print "\t\t\t\t\t\t\t\t\t<option value=\"$v\"$selected>$v</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
                                                <div class="mp_cotype mp_SNMP SNMPv3">
                                                        <div><label for="v3authpassphrase">',__("SNMPv3 AuthPassphrase"),'</label></div>
                                                        <div><input type="password" name="v3authpassphrase" id="v3authpassphrase" value="',($mp->SNMPVersion=="3")?$mp->v3AuthPassphrase:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_SNMP SNMPv3">
                                                        <div><label for="v3privprotocol">',__("SNMPv3 PrivProtocol"),'</label></div>
                                                        <div><select name="v3privprotocol">';
        foreach($privProtocolList as $v) {
                if($v == $mp->v3PrivProtocol)
                        $selected = ' selected';
                else
                        $selected = '';
                print "\t\t\t\t\t\t\t\t\t<option value=\"$v\"$selected>$v</option>\n";
        }
echo '                                                  </select></div>
                                                </div>
                                                <div class="mp_cotype mp_SNMP SNMPv3">
                                                        <div><label for="v3privpassphrase">',__("SNMPv3 PrivPassphrase"),'</label></div>
                                                        <div><input type="password" name="v3privpassphrase" id="v3privpassphrase" value="',($mp->SNMPVersion=="3")?$mp->v3PrivPassphrase:"",'"></div>
                                                </div>
						<div class="mp_cotype mp_Modbus">
                                                        <div><label for="unitid">',__("Unit ID"),'</label></div>
                                                        <div><input type="text" name="unitid" value=',($mp->ConnectionType=="Modbus")?$mp->UnitID:"",'></div>
                                                </div>
                                                <div class="mp_cotype mp_Modbus">
                                                        <div><label for="nbwords">',__("Number of words"),'</label></div>
                                                        <div><input type="text" name="nbwords" value=',($mp->ConnectionType=="Modbus")?$mp->NbWords:"",'></div>
                                                </div>
                                                <div class="mp_cotype mp_IPMI">
                                                        <div><label for="username">',__("User Name"),'</label></div>
                                                        <div><input type="text" name="username" value="',($mp->ConnectionType=="IPMI")?$mp->UserName:"",'"></div>
                                                </div>
						<div class="mp_cotype mp_IPMI">
                                                        <div><label for="password">',__("Password"),'</label></div>
                                                        <div><input type="password" name="password" value="',($mp->ConnectionType=="IPMI")?$mp->Password:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_IPMI">
                                                        <div><label for="interface">',__("Interface"),'</label></div>
                                                        <div><input type="text" name="interface" value=',($mp->ConnectionType=="IPMI")?$mp->Interface:"",'></div>
                                                </div>
						<div>
							<div>&nbsp;</div>
						</div>
                                                <div class="mp_cotype mp_SNMPelec">
                                                        <div><label for="oid1">',__("OID 1"),'</label></div>
                                                        <div><input type="text" name="oid1" value="',($mp->ConnectionType=="SNMP")?$mp->OID1:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_SNMPelec">
                                                        <div><label for="oid2">',__("OID 2"),'</label></div>
                                                        <div><input type="text" name="oid2" value="',($mp->ConnectionType=="SNMP")?$mp->OID2:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_SNMPelec">
                                                        <div><label for="oid3">',__("OID 3"),'</label></div>
                                                        <div><input type="text" name="oid3" value="',($mp->ConnectionType=="SNMP")?$mp->OID3:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_SNMPelec">
                                                        <div><label for="oidenergy">',__("OID Energy"),'</label></div>
                                                        <div><input type="text" name="oidenergy" value="',($mp->ConnectionType=="SNMP")?$mp->OIDEnergy:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_SNMPcooling">
                                                        <div><label for="fanspeedoid">',__("Fan Speed OID"),'</label></div>
                                                        <div><input type="text" name="fanspeedoid" id="fanspeedoid" value="',($mp->ConnectionType=="SNMP")?$mp->FanSpeedOID:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_SNMPcooling">
                                                        <div><label for="coolingoid">',__("Cooling OID"),'</label></div>
                                                        <div><input type="text" name="coolingoid" id="coolingoid" value="',($mp->ConnectionType=="SNMP")?$mp->CoolingOID:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_SNMPair">
                                                        <div><label for="temperatureoid">',__("Temperature OID"),'</label></div>
                                                        <div><input type="text" name="temperatureoid" id="temperatureoid" value="',($mp->ConnectionType=="SNMP")?$mp->TemperatureOID:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_SNMPair">
                                                        <div><label for="humidityoid">',__("Humidity OID"),'</label></div>
                                                        <div><input type="text" name="humidityoid" id="humidityoid" value="',($mp->ConnectionType=="SNMP")?$mp->HumidityOID:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_Modbuselec">
                                                        <div><label for="register1">',__("Register 1"),'</label></div>
                                                        <div><input type="text" name="register1" id="register1" value=',($mp->ConnectionType=="Modbus")?$mp->Register1:"",'></div>
                                                </div>
                                                <div class="mp_cotype mp_Modbuselec">
                                                        <div><label for="register2">',__("Register 2"),'</label></div>
                                                        <div><input type="text" name="register2" id="register2" value=',($mp->ConnectionType=="Modbus")?$mp->Register2:"",'></div>
                                                </div>
                                                <div class="mp_cotype mp_Modbuselec">
                                                        <div><label for="register3">',__("Register 3"),'</label></div>
                                                        <div><input type="text" name="register3" id="register3" value=',($mp->ConnectionType=="Modbus")?$mp->Register3:"",'></div>
                                                </div>
                                                <div class="mp_cotype mp_Modbuselec">
                                                        <div><label for="registerenergy">',__("Register Energy"),'</label></div>
                                                        <div><input type="text" name="registerenergy" id="registerenergy" value=',($mp->ConnectionType=="Modbus")?$mp->RegisterEnergy:"",'></div>
                                                </div>
                                                <div class="mp_cotype mp_Modbuscooling">
                                                        <div><label for="fanspeedregister">',__("Fan Speed Register"),'</label></div>
                                                        <div><input type="text" name="fanspeedregister" id="fanspeedregister" value=',($mp->ConnectionType=="Modbus")?$mp->FanSpeedRegister:"",'></div>
                                                </div>
                                                <div class="mp_cotype mp_Modbuscooling">
                                                        <div><label for="coolingregister">',__("Cooling Register"),'</label></div>
                                                        <div><input type="text" name="coolingregister" id="coolingregister" value=',($mp->ConnectionType=="Modbus")?$mp->CoolingRegister:"",'></div>
                                                </div>
                                                <div class="mp_cotype mp_Modbusair">
                                                        <div><label for="temperatureregister">',__("Temperature Register"),'</label></div>
                                                        <div><input type="text" name="temperatureregister" id="temperatureregister" value=',($mp->ConnectionType=="Modbus")?$mp->TemperatureRegister:"",'></div>
                                                </div>
                                                <div class="mp_cotype mp_Modbusair">
                                                        <div><label for="humidityregister">',__("Humidity Register"),'</label></div>
                                                        <div><input type="text" name="humidityregister" id="humidityregister" value=',($mp->ConnectionType=="Modbus")?$mp->HumidityRegister:"",'></div>
                                                </div>
                                                <div class="mp_cotype mp_IPMIelec">
                                                        <div><label for="sensor1">',__("Sensor 1"),'</label></div>
                                                        <div><input type="text" name="sensor1" id="sensor1" value="',($mp->ConnectionType=="IPMI")?$mp->Sensor1:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_IPMIelec">
                                                        <div><label for="sensor2">',__("Sensor 2"),'</label></div>
                                                        <div><input type="text" name="sensor2" id="sensor2" value="',($mp->ConnectionType=="IPMI")?$mp->Sensor2:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_IPMIelec">
                                                        <div><label for="sensor3">',__("Sensor 3"),'</label></div>
                                                        <div><input type="text" name="sensor3" id="sensor3" value="',($mp->ConnectionType=="IPMI")?$mp->Sensor3:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_IPMIelec">
                                                        <div><label for="sensorenergy">',__("Sensor Energy"),'</label></div>
                                                        <div><input type="text" name="sensorenergy" id="sensorenergy" value="',($mp->ConnectionType=="IPMI")?$mp->SensorEnergy:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_IPMIcooling">
                                                        <div><label for="fanspeedsensor">',__("Fan Speed Sensor"),'</label></div>
                                                        <div><input type="text" name="fanspeedsensor" id="fanspeedsensor" value="',($mp->ConnectionType=="IPMI")?$mp->FanSpeedSensor:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_IPMIcooling">
                                                        <div><label for="coolingsensor">',__("Cooling Sensor"),'</label></div>
                                                        <div><input type="text" name="coolingsensor" id="coolingsensor" value="',($mp->ConnectionType=="IPMI")?$mp->CoolingSensor:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_IPMIair">
                                                        <div><label for="temperaturesensor">',__("Temperature Sensor"),'</label></div>
                                                        <div><input type="text" name="temperaturesensor" id="temperaturesensor" value="',($mp->ConnectionType=="IPMI")?$mp->TemperatureSensor:"",'"></div>
                                                </div>
                                                <div class="mp_cotype mp_IPMIair">
                                                        <div><label for="humiditysensor">',__("Humidity Sensor"),'</label></div>
                                                        <div><input type="text" name="humiditysensor" id="humiditysensor" value="',($mp->ConnectionType=="IPMI")?$mp->HumiditySensor:"",'"></div>
                                                </div>
						<div class="caption">';
if($mp->MPID > 0) {
	echo '						<button type="submit" name="action" value="Update">',__("Update"),'</button>
							<button type="button" name="action" value="Delete">',__("Delete"),'</button>
							<button type="button" name="importButton" id="importButton" value="Import">',__("Import"),'</button>
							<button type="button" name="action" value="DeleteMeasures">',__("Delete Measures"),'</button>';
} else {
	echo '                                          <button type="submit" name="action" value="Create">',__("Create"),'</button>';
}
echo '						</div>
					</div> <!-- end table measure point -->';
if($mp->MPID != 0) {
	echo '				<br>
					<h2>'.__("Measures").'</h2>
					<br>
					<div class="table">';
	switch($mp->Type) {
		case "elec":
			echo '			<div>
							<div><label for="measure_wattage1">'.__("Wattage 1").'</label></div>
                                       			<div><input class="measure_field" type="number" id="measure_wattage1" value="'.$lastMeasure->Wattage1.'" readOnly></div>
						</div>
						<div>
							<div><label for="measure_wattage2">'.__("Wattage 2").'</label></div>
                                       			<div><input class="measure_field" type="number" id="measure_wattage2" value="'.$lastMeasure->Wattage2.'" readOnly></div>
						</div>
						<div>
							<div><label for="measure_wattage3">'.__("Wattage 3").'</label></div>
                                       			<div><input class="measure_field" type="number" id="measure_wattage3" value="'.$lastMeasure->Wattage3.'" readOnly></div>
						</div>
						<div>
							<div><label for="measure_energy">'.__("Energy").'</label></div>
                                       			<div><input class="measure_field" type="number" id="measure_energy" value="'.$lastMeasure->Energy.'" readOnly></div>
						</div>';
			break;
		case "cooling":
			echo '			<div>
							<div><label for="measure_fanspeed">'.__("Fan Speed").'</label></div>
                                       			<div><input class="measure_field" type="number" id="measure_fanspeed" value="'.$lastMeasure->FanSpeed.'" readOnly></div>
						</div>
						<div>
							<div><label for="measure_cooling">'.__("Compressor Usage").'</label></div>
                                       			<div><input class="measure_field" type="number" id="measure_cooling" value="'.$lastMeasure->Cooling.'" readOnly></div>
						</div>';
			break;
		case "air":
			echo '			<div>
							<div><label for="measure_temperature">'.__("Temperature").'</label></div>
                                       			<div><input class="measure_field" type="number" id="measure_temperature" value="'.$lastMeasure->Temperature.'" readOnly></div>
						</div>
						<div>
							<div><label for="measure_humidity">'.__("Humidity").'</label></div>
                                       			<div><input class="measure_field" type="number" id="measure_humidity" value="'.$lastMeasure->Humidity.'" readOnly></div>
						</div>';
			break;
		default:
			break;
	}
	 
	echo '					<div>
                                        		<div><label>'.__("Last Update").' : </label></div>
                                        		<div id="lastread">'.(!is_null($lastMeasure->Date)?$lastMeasure->Date:__("None")).'</div>
                                		</div>
						<div>
							<div><label>',__("Number of Measures"),' : </label></div>
							<div id="nbmeasure">',$nbMeasures,'</div>
						</div>
						<div class="caption">
							<button type="button" id="measure_edit" value="edit" data-edit="',__("Manual Entry"),'" data-submit="',__("Submit"),'">',__("Manual Entry"),'</button><br>
						</div>
					</div> <!-- end table measures -->';
}
echo '				</form>
			</div></div>
			<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
			<!-- hiding modal dialogs here so they can be translated easily -->
			<div class="hide">
				<div title="',__("Measure Point delete confirmation"),'" id="deletemodal">
					<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this measure point?"),'
					</div>
				</div>
			</div>
			<div class="hide">
				<div title="',__("Measures delete confirmation"),'" id="deletemeasuresmodal">
					<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete measures contained in this measure point?"),'
					</div>
				</div>
			</div>
		</div> <!-- end main -->';
?>
	</div>
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
</body>
</html>

