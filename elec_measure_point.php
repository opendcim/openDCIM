<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Data Center Electrical Measure Point Detail");

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
			$mp = new SNMPElectricalMeasurePoint();
			$mp->SNMPCommunity=$_REQUEST['snmpcommunity'];
			$mp->SNMPVersion=$_REQUEST['snmpversion'];
			$mp->OID1=$_REQUEST['oid1'];
			$mp->OID2=$_REQUEST['oid2'];
			$mp->OID3=$_REQUEST['oid3'];
			$mp->OIDEnergy=$_REQUEST['oidenergy'];
		} else if($_REQUEST['connectiontype'] == "Modbus") {
			$mp = new ModbusElectricalMeasurePoint();
			$mp->UnitID=$_REQUEST['unitid'];
			$mp->NbWords=$_REQUEST['nbwords'];
			$mp->Register1=$_REQUEST['register1'];
			$mp->Register2=$_REQUEST['register2'];
			$mp->Register3=$_REQUEST['register3'];
			$mp->RegisterEnergy=$_REQUEST['registerenergy'];
		}	
		$mp->MPID=$_REQUEST['mpid'];
		$mp->Label=$_REQUEST['label'];
		$mp->DataCenterID=$_REQUEST['datacenterid'];
		$mp->EnergyTypeID=$_REQUEST['energytypeid'];
		$mp->IPAddress=$_REQUEST['ipaddress'];
		$mp->Type="elec";
		$mp->ConnectionType=$_REQUEST['connectiontype'];
		$mp->Category=$_REQUEST['category'];
		$mp->UPSPowered=($_REQUEST['upspowered'] == "on")?1:0;
		$mp->PowerMultiplier=$_REQUEST['powermultiplier'];
		$mp->EnergyMultiplier=$_REQUEST['energymultiplier'];
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

	$mpList = new ElectricalMeasurePoint();
	$mpList=$mpList->GetMPList();

	$dcList = new DataCenter();
	$dcList = $dcList->GetDCList();

	$energytypeList = new EnergyType();
	$energytypeList = $energytypeList->GetEnergyTypeList();
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
							<div><label for="datacenterid">',__("Data Center ID"),'</label></div>
							<div><select name="datacenterid">
								<option value="0">'.__("None").'</option>';
	foreach($dcList as $dc) {
		if($dc->DataCenterID == $mp->DataCenterID)
			print "\t\t\t\t\t\t\t\t<option value=\"$dc->DataCenterID\" selected>$dc->Name</option>\n";
		else
			print "\t\t\t\t\t\t\t\t<option value=\"$dc->DataCenterID\">$dc->Name</option>\n";
	}
echo '							</select></div>
						</div>
						<div>
							<div><label for="energytypeid">',__("Energy Purchased"),'</label></div>
							<div><select name="energytypeid">';
	foreach($energytypeList as $key => $obj) {
		if($obj->EnergyTypeID == $mp->EnergyTypeID)
			print "\t\t\t\t\t\t\t\t<option value=\"$obj->EnergyTypeID\" selected>$obj->Name</option>\n";
		else
			print "\t\t\t\t\t\t\t\t<option value=\"$obj->EnergyTypeID\">$obj->Name</option>\n";
	}
echo '							</select></div>
						</div>
						<div>
							<div><label for="ipaddress">',__("IP Address / Host Name"),'</label></div>
							<div><input type="text" name="ipaddress" id="ipaddress" size="20" value="',$mp->IPAddress,'"></div>
						</div>
						<div>
							<div><label for="connectiontype">',__("Connection Type"),'</label></div>
							<div><select name="connectiontype" id="type" onChange="OnConnectionTypeChange()">';
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
						<div>
							<div><label for="category">',__("Category"),'</label></div>
							<div><select name="category" id="category" onChange="OnCategoryChange();">';
	$categories = array("none", "IT", "Cooling", "Other Mechanical", "UPS Input", "UPS Output", "Energy Reuse", "Renewable Energy");
	foreach($categories as $c) {
		if($c == $mp->Category)
			$selected = ' selected';
		else
			$selected = '';
		print "\t\t\t\t\t\t\t\t<option value=\"$c\"$selected>$c</option>\n";
	}
echo '							</select></div>
						</div>
						<div id="div_upspowered">
							<div><label for="upspowered">',__("UPS Powered"),'</label></div>';
	$checked = ($mp->UPSPowered)?"checked":"";
echo '
							<div><input type="checkbox" name="upspowered" id="upspowered" ',$checked,'></div>
						</div>
						<div>
							<div><label for="powermultiplier">',__("Power Multiplier"),'</label></div>
							<div><select name="powermultiplier" id="powermultiplier">';
	$multiplierList = array('0.1', '1', '10', '100');
	foreach($multiplierList as $m) {
		if($m == $mp->PowerMultiplier)
			$selected=' selected';
		else
			$selected='';	
		print "\t\t\t\t\t\t\t\t<option value=\"$m\"$selected>$m</option>\n";
	}
echo '							</select></div>
						</div>
						<div>
							<div><label for="energymultiplier">',__("Energy Multiplier"),'</label></div>
							<div><select name="energymultiplier" id="energymultiplier">';
	foreach($multiplierList as $m) {
		if($m == $mp->EnergyMultiplier)
			$selected=' selected';
		else
			$selected='';	
		print "\t\t\t\t\t\t\t\t<option value=\"$m\"$selected>$m</option>\n";
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
	$versionList = array('1','2c');
	foreach($versionList as $v) {
		if($v == $mp->SNMPVersion)
			$selected = ' selected';
		else
			$selected = '';
		print "\t\t\t\t\t\t\t\t\t<option value=\"$v\"$selected>$v</option>\n";
	}
echo '							</select></div>
						</div>
						<div id="snmp_oid1">
							<div><label for="oid1">',__("OID 1"),'</label></div>
							<div><input type="text" name="oid1" id="oid1" value=',($mp->ConnectionType=="SNMP")?$mp->OID1:"",'></div>
						</div>
						<div id="snmp_oid2">
							<div><label for="oid2">',__("OID 2"),'</label></div>
							<div><input type="text" name="oid2" id="oid2" value=',($mp->ConnectionType=="SNMP")?$mp->OID2:"",'></div>
						</div>
						<div id="snmp_oid3">
							<div><label for="oid3">',__("OID 3"),'</label></div>
							<div><input type="text" name="oid3" id="oid3" value=',($mp->ConnectionType=="SNMP")?$mp->OID3:"",'></div>
						</div>
						<div id="snmp_oidenergy">
							<div><label for="oidenergy">',__("OID Energy"),'</label></div>
							<div><input type="text" name="oidenergy" id="oidenergy" value=',($mp->ConnectionType=="SNMP")?$mp->OIDEnergy:"",'></div>
						</div>
						<div id="modbus_unit">
							<div><label for="unitid">',__("Unit ID"),'</label></div>
							<div><input type="text" name="unitid" id="unitid" value=',($mp->ConnectionType=="Modbus")?$mp->UnitID:"",'></div>
						</div>
						<div id="modbus_nbwords">
							<div><label for="nbwords">',__("Number of words"),'</label></div>
							<div><input type="text" name="nbwords" id="nbwords" value=',($mp->ConnectionType=="Modbus")?$mp->NbWords:"",'></div>
						</div>
						<div id="modbus_register1">
							<div><label for="register1">',__("Register 1"),'</label></div>
							<div><input type="text" name="register1" id="register1" value=',($mp->ConnectionType=="Modbus")?$mp->Register1:"",'></div>
						</div>
						<div id="modbus_register2">
							<div><label for="register2">',__("Register 2"),'</label></div>
							<div><input type="text" name="register2" id="register2" value=',($mp->ConnectionType=="Modbus")?$mp->Register2:"",'></div>
						</div>
						<div id="modbus_register3">
							<div><label for="register3">',__("Register 3"),'</label></div>
							<div><input type="text" name="register3" id="register3" value=',($mp->ConnectionType=="Modbus")?$mp->Register3:"",'></div>
						</div>
						<div id="modbus_registerenergy">
							<div><label for="registerenergy">',__("Register Energy"),'</label></div>
							<div><input type="text" name="registerenergy" id="registerenergy" value=',($mp->ConnectionType=="Modbus")?$mp->RegisterEnergy:"",'></div>
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
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this measure point?"),'
		</div>
	</div>
</div>'; ?>
		</div><!-- END div.main -->
	</div><!-- END div.page -->

<div id="dlg_importfile" style="display:none;" title="<?php echo __("Import Measure Point From File");?>">  
        <br>
        <form enctype="multipart/form-data" name="frmImport" id="frmImport" method="POST">
		<input type="hidden" id="mpid" name="mpid" value="<?php echo $mp->MPID;  ?>" />
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

function OnConnectionTypeChange() {
	var snmp = ['com', 'version', 'oid1', 'oid2', 'oid3', 'oidenergy'];
	var modbus = ['unit', 'nbwords', 'register1', 'register2', 'register3', 'registerenergy'];

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

function OnCategoryChange() {
	var category = document.form1.category.options[document.form1.category.selectedIndex].value;
	if( category == 'UPS Input' || category == 'UPS Output' || category == 'Renewable Energy') {
		document.getElementById("div_upspowered").style.display = "none";
	} else
		document.getElementById("div_upspowered").style.display = "";
}

function Load() {
	OnConnectionTypeChange();
	OnCategoryChange();
	<?php
		if(isset($importError))
			echo "alert('".$importError."');";
	?>
}

window.onload=Load;

</script>
</body>
</html>
