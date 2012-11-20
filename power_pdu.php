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
	$template = new CDUTemplate();
	$templateList = $template->GetTemplateList( $facDB );
	$manufacturer = new Manufacturer();

	// Ajax actions
	if(isset($_REQUEST['d']) || isset($_REQUEST['c']) || isset($_REQUEST['pid'])){
		// Build drop down list of devices for this cabinet
		if(isset($_REQUEST['c'])){
			$connDev->Cabinet=$_REQUEST['c'];
			$devlist=$connDev->ViewDevicesByCabinet($facDB);
			echo '<select name="d"><option value=""></option>';
			foreach($devlist as $device){
				echo '<option value="',$device->DeviceID,'"',((isset($_REQUEST['d'])&&$_REQUEST['d']==$device->DeviceID)?" selected":""),'>',$device->Label,'</option>';
			}
			echo '</select>';
		}elseif(isset($_REQUEST['pid'])){
			$powerConn->PDUID=$_REQUEST['pid'];
			$powerConn->PDUPosition=$_REQUEST['output'];
			if((isset($_REQUEST['d']) && ($_REQUEST['d']!="" || $_REQUEST['d']!="undefined")) || (isset($_REQUEST['devinput']) && ($_REQUEST['devinput']!="" || $_REQUEST['devinput']!="undefined" ))){
				$powerConn->DeviceID=$_REQUEST['d'];
				$powerConn->DeviceConnNumber=$_REQUEST['devinput'];
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

	if(isset($_POST['test'])){
		$pdu->PDUID=$_POST["test"];
		$pdu->GetPDU($facDB);
		
		$template=new CDUTemplate();
		$template->TemplateID=$pdu->TemplateID;
		$template->GetTemplate($facDB);
		
		printf( "<p>%s %s.<br>\n", _("Testing SNMP communication to CDU"), $pdu->Label );
		printf( "%s %s.<br>\n", _("Connecting to IP address"), $pdu->IPAddress );
		printf( "%s %s.</p>\n", _("Using SNMP Community string"), $pdu->SNMPCommunity );
		
		print "<div id=\"infopanel\"><fieldset><legend>"._("Results")."</legend>\n";
		
		$command="/usr/bin/snmpget";
		
		$upTime=$pdu->GetSmartCDUUptime($facDB);
		if($upTime!=""){
			printf("<p>%s: %s</p>\n", _("SNMP Uptime"),$upTime);
		}else{
			print "<p>"._("SNMP Uptime did not return a valid value.")."</p>\n";
		}
		
		$pollCommand=sprintf( "%s -v 2c -c %s %s %s | /bin/cut -d: -f4", $command, $pdu->SNMPCommunity, $pdu->IPAddress, $template->VersionOID );
		exec($pollCommand,$verOutput);
		
		if(count($verOutput) >0){
			printf( "<p>%s %s.  %s</p>\n", _("VersionOID returned a value of"), $verOutput[0], _("Please check to see if it makes sense.") );
		}else{
			print "<p>"._("The OID for Firmware Version did not return a value.  Please check your MIB table.")."</p>\n";
		}
		
		$OIDString=$template->OID1." ".$template->OID2." ".$template->OID3;
		$pollCommand=sprintf( "%s -v 2c -c %s %s %s | /bin/cut -d: -f4", $command, $pdu->SNMPCommunity, $pdu->IPAddress, $OIDString );
		
		exec($pollCommand,$statsOutput);
		
		if(count($statsOutput) >0){
			if($statsOutput[0]!=""){
				printf( "<p>%s %s.  %s</p>\n", _("OID1 returned a value of"), $statsOutput[0], _("Please check to see if it makes sense.") );
			}else{
				print "<p>"._("OID1 did not return any data.  Please check your MIB table.")."</p>\n";
			}
			
			if((strlen($template->OID2) >0)&&(strlen($statsOutput[1]) >0)){
				printf( "<p>%s %s.  %s</p>\n", _("OID2 returned a value of"), $statsOutput[1], _("Please check to see if it makes sense.") );
			}elseif(strlen($template->OID2) >0){
				print "<p>"._("OID2 did not return any data.  Please check your MIB table.")."</p>\n";
			}

			if((strlen($template->OID3) >0)&&(strlen($statsOutput[2]) >0)){
				printf( "<p>%s %s.  %s</p>\n", _("OID3 returned a value of"), $statsOutput[2], _("Please check to see if it makes sense.") );
			}elseif(strlen($template->OID3)){
				print "<p>"._("OID3 did not return any data.  Please check your MIB table.")."</p>\n";
			}
			
			switch($template->ProcessingProfile){
				case "SingleOIDAmperes":
					$amps=intval(@$statsOutput[0])/intval($template->Multiplier);
					$watts=$amps*intval($template->Voltage);
					break;
				case "Combine3OIDAmperes":
					$amps=(intval(@$statsOutput[0])+intval(@$statsOutput[1])+intval(@$statsOutput[2]))/intval($template->Multiplier);
					$watts=$amps*intval($template->Voltage);
					break;
				case "Convert3PhAmperes":
					$amps=(intval(@$statsOutput[0])+intval(@$statsOutput[1])+intval(@$statsOutput[2]))/intval($template->Multiplier)/3;
					$watts=$amps*1.732*intval($template->Voltage);
					break;
				case "Combine3OIDWatts":
					$watts=(intval(@$statsOutput[0])+intval(@$statsOutput[1])+intval(@$statsOutput[2]))/intval($template->Multiplier);
				default:
					$watts=intval(@$statsOutput[0])/intval($template->Multiplier);
					break;
			}
			
			printf("<p>%s %.2f kW</p>", _("Resulting kW from this test is"),$watts/1000);
		}
		echo '	</fieldset></div>';
		exit;
	}



	if(isset($_REQUEST['pduid'])){
		$pdu->PDUID=(isset($_REQUEST['pduid']) ? $_REQUEST['pduid'] : $_GET['pduid']);
	}else{
		echo 'Do not call this file directly';
		exit;
	}
	if(isset($_REQUEST['action']) && (($_REQUEST['action']=='Create') || ($_REQUEST['action']=='Update')) && $user->WriteAccess) {
		$pdu->Label=$_REQUEST['label'];
		$pdu->CabinetID=$_REQUEST['cabinetid'];
		$pdu->TemplateID=$_REQUEST['templateid'];
		$pdu->IPAddress=$_REQUEST['ipaddress'];
		$pdu->SNMPCommunity=$_REQUEST['snmpcommunity'];
		$pdu->PanelID=$_REQUEST['panelid'];
		$pdu->BreakerSize=$_REQUEST['breakersize'];
		$pdu->PanelPole=$_REQUEST['panelpole'];
		$pdu->InputAmperage=$_REQUEST['inputamperage'];
		// If failsafe is unset clear auto transfer switch panel information
		if(isset($_REQUEST['failsafe'])){
			$pdu->FailSafe=1;
			$pdu->PanelID2=$_REQUEST['panelid2'];
			$pdu->PanelPole2=$_REQUEST['panelpole2'];
		}else{
			$pdu->FailSafe=0;
			$pdu->PanelID2="";
			$pdu->PanelPole2="";
		}

		if($_REQUEST['action']=='Create'){
			$ret=$pdu->CreatePDU($facDB);
		}else{
			$pdu->PDUID = $_REQUEST['pduid'];
			$pdu->UpdatePDU( $facDB );
		}
	}

	if($pdu->PDUID >0){
		$pdu->GetPDU($facDB);
		$upTime=$pdu->GetSmartCDUUptime($facDB);
		
		$template->TemplateID = $pdu->TemplateID;
		$template->GetTemplate( $facDB );
	} else {
		$pdu->CabinetID=$_GET['cabinetid'];
	}

	$cab->CabinetID=$pdu->CabinetID;
	$cab->GetCabinet($facDB);
	
	$Panel=new PowerPanel();
	$PanelList=$Panel->GetPanelList($facDB);
	/* For strict panel selection, comment out the line above and uncomment the following line */
	// $PanelList = $Panel->GetPanelsByDataCenter( $cab->DataCenterID, $facDB );

	$powerConn->PDUID=$pdu->PDUID;
	$connList=$powerConn->GetConnectionsByPDU($facDB);

	$title=($pdu->Label!='')?"$pdu->Label :: $pdu->PDUID":'Data Center PDU Detail';

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title><?php echo $title; ?></title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->

  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript">

	$(document).ready(function() {
		$('#panelid').change( function(){
			$.get('scripts/ajax_panel.php?q='+$(this).val(), function(data) {
				$('#voltage').html(data['PanelVoltage'] +'/'+ Math.floor(data['PanelVoltage']/1.73));
			});
		});
		$('#pdutestlink').click(function(e){
			e.preventDefault();
			$.post('power_pdu.php', {test: $('#pduid').val()}, function(data){
				$('#pdutest').html(data);
			});
			$('#pdutest').dialog({minWidth: 450, maxWidth: 450, closeOnEscape: true });
		});
		$('.center > div + div > .table > div:first-child ~ div').each(function(){
			var row=$(this);
			var pduid=$('#pduid');
			var cabid=$('#cabinetid').val();
			row.find('div:first-child').click(function(){
				if($(this).attr('edit')=='yes'){

				}else{
					$(this).attr('edit', 'yes');
					var output=$(this).text();
					var device=$(this).next();
					var devid=device.attr('alt');
					if(devid!=""){var selected='&d='+devid;}else{var selected='';}
					var devinput=device.next();
					var width=devinput.width();
					var height=devinput.innerHeight();
					$.ajax({
						type: 'POST',
						url: 'power_pdu.php',
						data: 'c='+cabid+selected,
						success: function(data){
							device.html(data).css('padding', '0px');
							devinput.html('<input name="DeviceConnNumber" value="'+devinput.text()+'"></input>').css('padding', '0px');
							devinput.children('input').css({'width': width+'px', 'text-align': 'center'});
<?php echo '							row.append(\'<div style="padding: 0px;"><button name="delete">',_("Delete"),'</button><button name="cancel">',_("Cancel"),'</button></div>\');'; ?>
							row.find('div > button').css({'height': height+'px', 'line-height': '1'});
							row.find('div > button').each(function(){
								var a=devinput.find('input');
								var b=device.find('select');
								if($(this).attr('name')=="delete"){
									$(this).click(function(){
										b.val("");
										a.val("");
										a.focus();
										b.focus();
									});
								}else if($(this).attr('name')=="cancel"){
									$(this).click(function(){
										b.val(device.attr('data'));
										a.val(devinput.attr('data'));
										a.focus();
										b.focus();
									});
								}
							});
						}
					});
				}
			}).css({'cursor': 'pointer', 'text-decoration': 'underline'});
			row.find('div:nth-child(2) > select, div:nth-child(3) > input').live('focusout', function(){
				var device=$(this).parent('div').parent('div').children('div > div:nth-child(2)');
				var output=device.prev().text();
				var devinput=device.next();
				var devid=device.find('select').val();
				var psnum=devinput.find('input').val();
				device.attr('alt', devid);
				var link='<a href="devices.php?deviceid='+devid+'">'+device.find('option:selected').text()+'</a>';
				if(device.find('select').val()!="" && devinput.find('input').val()!=""){
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
								row.find('div:first-child').removeAttr('edit');
								row.find('div:last-child').remove();
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
				}else if(device.find('select').val()=="" && devinput.find('input').val()==""){
					$.post('power_pdu.php', {pid: pduid.val(), output: output});
					device.html('').removeAttr('style');
					devinput.html('').removeAttr('style');

					device.css('background-color', 'lightgreen');
					devinput.css('background-color', 'lightgreen');
					setTimeout(function() {
						device.css('background-color', 'white');
						devinput.css('background-color', 'white');
					},1500);
					row.find('div:first-child').removeAttr('edit');
					row.find('div:last-child').remove();
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
  <div><input type="text" name="panelpole" id="panelpole" size=5 value="',$pdu->PanelPole,'"></div>
</div>
<div>
   <div><label for="inputamperage">',_("Input Amperage"),'</label></div>
   <div><input type="text" name="inputamperage" id="inputamperage" size=5 value="',$pdu->InputAmperage,'"></div>
</div>
<div>
	<div><label for="templateid">',_("CDU Template"),'</label></div>
	<div><select name="templateid" id="templateid">';

	foreach($templateList as $templateRow){
		$manufacturer->ManufacturerID=$templateRow->ManufacturerID;
		$manufacturer->GetManufacturerByID($facDB);
		
		$selected=($pdu->TemplateID==$templateRow->TemplateID)?" selected":"";		
		print "		<option value=$templateRow->TemplateID$selected>[$manufacturer->Name] $templateRow->Model</option>\n";
	}
	
echo '   </select></div>
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
   <div><input type="text" name="snmpcommunity" id="snmpcommunity" size=15 value="',$pdu->SNMPCommunity,'"><a id="pdutestlink" href="#">', _("Test Communications"), '</a></div>
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

	for($connNumber=1; $connNumber<$template->NumOutlets+1; $connNumber++){
		if(isset($connList[$connNumber])){
			$connDev->DeviceID=$connList[$connNumber]->DeviceID;
			$connDev->GetDevice($facDB);
			print "	<div>\n		<div>$connNumber</div>\n		<div alt=\"{$connList[$connNumber]->DeviceID}\" data=\"{$connList[$connNumber]->DeviceID}\"><a href=\"devices.php?deviceid={$connList[$connNumber]->DeviceID}\">$connDev->Label</a></div>\n		<div data=\"{$connList[$connNumber]->DeviceConnNumber}\">{$connList[$connNumber]->DeviceConnNumber}</div>\n	</div>\n";
		}else{
			print "	<div>\n		<div>$connNumber</div>\n		<div alt=\"\"></div>\n		<div></div>\n	</div>\n";
		}
	}
	
	// If there are any connections > NumOutlets, print them as ghosts
	foreach ( $connList as $ghostConnection ) {
		if ( $ghostConnection->PDUPosition > $template->NumOutlets ) {
			$connDev->DeviceID=$ghostConnection->DeviceID;
			$connDev->GetDevice($facDB);
			print "	<div>\n		<div>$ghostConnection->PDUPosition</div>\n		<div alt=\"{$ghostConnection->DeviceID}\" data=\"{$ghostConnection->DeviceID}\"><a href=\"devices.php?deviceid={$ghostConnection->DeviceID}\">$connDev->Label</a></div>\n		<div data=\"{$ghostConnection->DeviceConnNumber}\">{$ghostConnection->DeviceConnNumber}</div>\n	</div>\n";
		}
	}
?>  
    </div> <!-- END div.table -->
</div></div>

<div id="pdutest" title="Testing SNMP Communications"></div>

<?php echo '<a href="cabnavigator.php?cabinetid=',$cab->CabinetID,'">[ ',_("Return to Navigator"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
