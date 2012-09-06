<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user=new User();
	$user->UserID = $_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );

	if(!$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$pdu=new PowerDistribution();
	$cab=new Cabinet();
	$powerConn=new PowerConnection();
	$connDev=new Device();

	// Ajax actions
	if(isset($_POST['d']) || isset($_POST['c']) || isset($_POST['pid'])){
		// Build drop down list of devices for this cabinet
		if(isset($_POST['c'])){
			$connDev->Cabinet=$_POST['c'];
			$devlist=$connDev->ViewDevicesByCabinet($facDB);
			echo '<select name="d"><option value=""></option>';
			foreach($devlist as $device){
				echo '<option value="',$device->DeviceID,'"',((isset($_POST['d'])&&$_POST['d']==$device->DeviceID)?" selected":""),'>',$device->Label,'</option>';
			}
			echo '</select>';
		}elseif(isset($_POST['pid'])){
			$powerConn->PDUID=$_POST['pid'];
			$powerConn->PDUPosition=$_POST['output'];
			if((isset($_POST['d']) && ($_POST['d']!="" || $_POST['d']!="undefined")) || (isset($_POST['devinput']) && ($_POST['devinput']!="" || $_POST['devinput']!="undefined" ))){
				$powerConn->DeviceID=$_POST['d'];
				$powerConn->DeviceConnNumber=$_POST['devinput'];
				$check=$powerConn->CreateConnection($facDB);
				// check for valid creation 
				if($check==0){
					echo 'ok';
				}else{
					echo 'no';
				}
			}else{
				$powerConn->RemoveConnection($facDB);
			}
		}
		// This is for ajax actions so make sure not to call the rest of the page
		exit;
	}

	if(isset($_REQUEST['pduid'])){
		$pdu->PDUID=(isset($_POST['pduid']) ? $_POST['pduid'] : $_GET['pduid']);
	}else{
		echo 'Do not call this file directly';
		exit;
	}
	if(isset($_POST['action']) && (($_POST['action']=='Create') || ($_POST['action']=='Update')) && $user->WriteAccess) {
		$pdu->Label=$_POST['label'];
		$pdu->CabinetID=$_POST['cabinetid'];
		$pdu->InputAmperage=$_POST['inputamperage'];
		$pdu->ManagementType=$_POST['managementtype'];
		$pdu->Model=$_POST['model'];
		$pdu->NumOutputs=$_POST['numoutputs'];
		$pdu->IPAddress=$_POST['ipaddress'];
		$pdu->SNMPCommunity=$_POST['snmpcommunity'];
		$pdu->PanelID=$_POST['panelid'];
		$pdu->BreakerSize=$_POST['breakersize'];
		$pdu->PanelPole=$_POST['panelpole'];
		// If failsafe is unset clear auto transfer switch panel information
		if(isset($_POST['failsafe'])){
			$pdu->FailSafe=1;
			$pdu->PanelID2=$_POST['panelid2'];
			$pdu->PanelPole2=$_POST['panelpole2'];
		}else{
			$pdu->FailSafe=0;
			$pdu->PanelID2="";
			$pdu->PanelPole2="";
		}

		if($_POST['action']=='Create'){
			$ret=$pdu->CreatePDU($facDB);
		}else{
			$pdu->PDUID = $_POST['pduid'];
			$pdu->UpdatePDU( $facDB );
		}
	}

	if($pdu->PDUID >0){
		$pdu->GetPDU($facDB);
		$upTime=$pdu->GetSmartCDUUptime($facDB);
	} else {
		$pdu->CabinetID=$_POST['cabinetid'];
	}

	$cab->CabinetID=$pdu->CabinetID;
	$cab->GetCabinet($facDB);
	
	$Panel=new PowerPanel();
	$PanelList=$Panel->GetPanelList($facDB);
	/* For strict panel selection, comment out the line above and uncomment the following line */
	// $PanelList = $Panel->GetPanelsByDataCenter( $cab->DataCenterID, $facDB );

	$powerConn->PDUID=$pdu->PDUID;
	$connList=$powerConn->GetConnectionsByPDU($facDB);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>Facilities Cabinet Maintenance</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->

  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript">

	$(document).ready(function() {
		$('#panelid').change( function(){
			$.get('scripts/ajax_panel.php?q='+$(this).val(), function(data) {
				$('#voltage').html(data['PanelVoltage'] +'/'+ Math.floor(data['PanelVoltage']/1.73));
			});
		});
		$('.center > div + div > .table > div:first-child ~ div').each(function(){
			var row=$(this);
			var pduid=$('#pduid');
			var cabid=$('#cabinetid').val();
			row.find('div:first-child').click(function(){
				var output=$(this).text();
				var device=$(this).next();
				var devid=device.attr('alt');
				if(devid!=""){var selected='&d='+devid;}else{var selected='';}
				var devinput=device.next();
				var width=devinput.width();
				$.ajax({
					type: 'POST',
					url: 'power_pdu.php',
					data: 'c='+cabid+selected,
					success: function(data){
						device.html(data).css('padding', '0px');
						devinput.html('<input name="DeviceConnNumber" value="'+devinput.text()+'"></input>').css('padding', '0px');
						devinput.children('input').css({'width': width+'px', 'text-align': 'center'});
					}
				});
			}).css({'cursor': 'pointer', 'text-decoration': 'underline'});
			row.find('div:nth-child(2) > select, div:last-child > input').live('change', function(){
				var device=$(this).parent('div').parent('div').children('div > div:nth-child(2)');
				var output=device.prev().text();
				var devinput=device.next();
				var devid=device.find('select').val();
				var psnum=devinput.find('input').val();
				device.attr('alt', devid);
				var link='<a href="devices.php?deviceid='+devid+'">'+device.find('option:selected').text()+'</a>';
				if(device.find('select').val()!="" && devinput.find('input').val()!=""){
					console.log('first if(add connection) \nselect val: '+device.find('select').val()+' input val: '+devinput.find('input').val());
					$.ajax({
						type: 'POST',
						url: 'power_pdu.php',
						data: 'd='+devid+'&pid='+pduid.val()+'&output='+output+'&devinput='+psnum,
						success: function(data){
							if(data=='ok'){
								device.html(link).removeAttr('style');
								devinput.html(psnum).removeAttr('style');
								
								device.css('background-color', 'lightgreen');
								devinput.css('background-color', 'lightgreen');
								setTimeout(function() {
									device.css('background-color', 'white');
									devinput.css('background-color', 'white');
								},1500);
							}else{
								device.css('background-color', 'salmon');
								device.find('select').css('background-color', 'salmon');
								devinput.css('background-color', 'salmon');
								devinput.find('input').css('background-color', 'salmon');
								setTimeout(function() {
									device.css('background-color', 'white');
									device.find('select').css('background-color', 'white');
									devinput.css('background-color', 'white');
									devinput.find('input').css('background-color', 'white');
								},1500);

							}
						}
					});
					$(this).unbind('change');
				}else if(device.find('select').val()=="" && devinput.find('input').val()==""){
					console.log('second if(remove inputs)');
					device.html('').removeAttr('style');
					devinput.html('').removeAttr('style');

					device.css('background-color', 'lightgreen');
					devinput.css('background-color', 'lightgreen');
					setTimeout(function() {
						device.css('background-color', 'white');
						devinput.css('background-color', 'white');
					},1500);
					$(this).unbind('change');
				}else{
					console.log('third if(remove connection)');
					$.ajax({
						type: 'POST',
						url: 'power_pdu.php',
						data: 'pid='+pduid.val()+'&output='+output,
						success: function(data){

						}
					});
				}
			});
		});
	});
  </script>


</head>
<body>
<div id="header"></div>
<div class="page pdu">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',_("Data Center PDU Detail"),'</h3>
<div class="center"><div>
<form name="pduform" id="pduform" action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div><label for="pduid">',_("PDU ID"),'</label></div>
   <div><input type="text" name="pduid" id="pduid" value="',$pdu->PDUID,'" size="6" readonly></div>
</div>
<div>
   <div><label for="label">',_("Label"),'</label></div>
   <div><input type="text" name="label" id="label" size="50" value="',$pdu->Label,'"></div>
</div>
<div>
   <div><label for="cabinetid">',_("Cabinet"),'</label></div>
   <div>',$cab->GetCabinetSelectList($facDB),'</div>
</div>
<div>
   <div><label for="inputamperage">',_("Input Amperage"),'</label></div>
   <div><input type="text" name="inputamperage" id="inputamperage" size=5 value="',$pdu->InputAmperage,'"></div>
</div>
<div>
   <div><label for="managementtype">',_("ManagementType"),'</label></div>
   <div>',$pdu->GetManagementTypeSelectList($facDB),'</div>
</div>
<div>
   <div><label for="model">',_("Model"),'</label></div>
   <div><input type="text" name="model" id="model" size=25 value="',$pdu->Model,'"></div>
</div>
<div>
   <div><label for="numoutputs">',_("Number of Outputs"),'</label></div>
   <div><input type="text" name="numoutputs" id="numoutputs" size=5 value="',$pdu->NumOutputs,'"></div>
</div>
<div>
   <div><label for="ipaddress">',_("IP Address"),'</label></div>
   <div><input type="text" name="ipaddress" id="ipaddress" size=15 value="',$pdu->IPAddress,'">',((strlen($pdu->IPAddress)>0)?"<a href=\"http://$pdu->IPAddress\" target=\"new\">http://$pdu->IPAddress</a>":""),'</div>
</div>
<div>
    <div>',_("Uptime"),'</div>
    <div>',$upTime,'</div>
</div>
<div>
    <div>',_("Firmware Version"),'</div>
    <div>',$pdu->FirmwareVersion,'</div>
</div>
<div>
   <div><label for="snmpcommunity">',_("SNMP Community"),'</label></div>
   <div><input type="text" name="snmpcommunity" id="snmpcommunity" size=15 value="',$pdu->SNMPCommunity,'"></div>
</div>
<div>
   <div><label for="panelid">',_("Source Panel"),'</label></div>
   <div><select name="panelid" id="panelid" ><option value=0>',_("Select Panel"),'</option>';

foreach($PanelList as $key=>$value){
	if($value->PanelID == $pdu->PanelID){$selected=' selected';}else{$selected="";}
	print "<option value=\"$value->PanelID\"$selected>$value->PanelLabel</option>\n"; 
}

echo '   </select></div>
</div>
<div>
	<div><label for="voltage">',_("Voltages:"),'</label></div>
	<div id="voltage">';

	if($pdu->PanelID >0){
		$pnl=new PowerPanel();
		$pnl->PanelID=$pdu->PanelID;
		$pnl->GetPanel($facDB);
	
		print $pnl->PanelVoltage." / ".intval($pnl->PanelVoltage/1.73);
	}

echo '	</div>
</div>
<div>
  <div><label for="breakersize">',_("Breaker Size (# of Poles)"),'</label></div>
  <div>
	<select name="breakersize">';

	for($i=1;$i<4;$i++){
		if($i==$pdu->BreakerSize){$selected=" selected";}else{$selected="";}
		print "<option value=\"$i\"$selected>$i</option>";
	}

echo '	</select>
  </div>
</div>
<div>
  <div><label for="panelpole">',_("Panel Pole Number"),'</label></div>
  <div><input type="text" name="panelpole" id="panelpole" size=4 value="',$pdu->PanelPole,'"></div>
</div>
<div class="caption">
<h3>',_("Automatic Transfer Switch"),'</h3>
<fieldset id="powerinfo">
<div class="table centermargin border">
<div>
  <div><label for="failsafe">',_("Fail Safe Switch?"),'</label></div>
  <div><input type="checkbox" name="failsafe" id="failsafe"',(($pdu->FailSafe)?" checked":""),'></div>
</div>
<div>
   <div><label for="panelid2">',_("Source Panel (Secondary Source)"),'</label></div>
   <div><select name="panelid2" id="panelid2"><option value=0>',_("Select Panel"),'</option>';

	foreach($PanelList as $key=>$value){
		if($value->PanelID==$pdu->PanelID2){$selected=" selected";}else{$selected="";}
		print "		<option value=$value->PanelID$selected>$value->PanelLabel</option>\n";
	}

echo '   </select></div>
</div>
<div>
  <div><label for="panelpole2">',_("Panel Pole Number (Secondary Source)"),'</label></div>
  <div><input type="text" name="panelpole2" id="panelpole2" size=4 value="',$pdu->PanelPole2,'"></div>
</div>
<div class="caption">';

	if($user->WriteAccess){
		if($pdu->PDUID >0){
			echo '   <button type="submit" name="action" value="Update">',_("Update"),'</button>';
		} else {
			echo '   <button type="submit" name="action" value="Create">',_("Create"),'</button>';
		}
	}

echo '</div>
</div> <!-- END div.table -->
</div>
</div> <!-- END div.table -->
</form>

</div><div>

<div class="table border">
	<div>
		<div>',_("Output No."),'</div>
		<div>',_("Device Name"),'</div>
		<div>',_("Dev Input No"),'</div>
	</div>';

	for($connNumber=1;$connNumber<$pdu->NumOutputs+1;$connNumber++){
		if(isset($connList[$connNumber])){
			$connDev->DeviceID=$connList[$connNumber]->DeviceID;
			$connDev->GetDevice($facDB);
			print "	<div>\n		<div>$connNumber</div>\n		<div alt=\"{$connList[$connNumber]->DeviceID}\"><a href=\"devices.php?deviceid={$connList[$connNumber]->DeviceID}\">$connDev->Label</a></div>\n		<div>".$connList[$connNumber]->DeviceConnNumber."</div>\n	</div>\n";
		}else{
			print "	<div>\n		<div>$connNumber</div>\n		<div alt=\"\"></div>\n		<div></div>\n	</div>\n";
		}
	}
?>  
    </div> <!-- END div.table -->
</div></div>
<?php echo '<a href="cabnavigator.php?cabinetid=',$cab->CabinetID,'">[ ',_("Return to Navigator"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
