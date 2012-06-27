<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights($facDB);

	if(!$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$head="";
	$cab=new Cabinet();
	$audit=new CabinetAudit();
	$pdu=new PowerDistribution();
	$dev=new Device();
	$templ=new DeviceTemplate();
	$tempPDU=new PowerDistribution();

	if(!isset($_REQUEST["cabinetid"])){
		// Not sure how you got here without a cabinet id set
		// GTFO!
		header("Location: ".redirect());
		exit;
	}

	// Even if we're deleting the cabinet, it's helpful to know which data center to go back to displaying afterwards
	$cab->CabinetID=$_REQUEST["cabinetid"];
	$cab->GetCabinet($facDB);
	
	$dcID = $cab->DataCenterID;

	// If you're deleting the cabinet, no need to pull in the rest of the information, so get it out of the way //
	if(isset($_REQUEST["delete"]) && $_REQUEST["delete"]=="yes" && $user->SiteAdmin){
		$cab->DeleteCabinet($facDB);
		$url=redirect("dc_stats.php?dc=$dcID");
		header("Location: $url");
	}

	
	$audit->CabinetID=$cab->CabinetID;

	// Checking for site admin rights here ensures that they didn't submit this from someplace else.
	if ( isset($_REQUEST["audit"]) && $_REQUEST["audit"]=="yes" && $user->SiteAdmin) {
		$audit->UserID=$user->UserID;
		$audit->CertifyAudit($facDB);
	}

	$audit->AuditStamp="Never";
	$audit->GetLastAudit($facDB);
	if($audit->UserID != ""){
		$tmpUser=new User();
		$tmpUser->UserID=$audit->UserID;
		$tmpUser->GetUserRights($facDB);
		$AuditorName=$tmpUser->Name;
	} else {
		//If no audit has been completed $AuditorName will return an error
		$AuditorName="";
	}

	$pdu->CabinetID=$cab->CabinetID;
	$PDUList=$pdu->GetPDUbyCabinet($facDB);

	$dev->Cabinet=$cab->CabinetID;
	$devList=$dev->ViewDevicesByCabinet($facDB);

	$currentHeight=$cab->CabinetHeight;
	$totalWatts=0;
	$totalWeight=0;
	$totalMoment=0;

	if($config->ParameterArray["ReservedColor"] != "#FFFFFF"){
		$head.="<style type=\"text/css\">.reserved{background-color: {$config->ParameterArray['ReservedColor']};</style>";
	}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Facilities Cabinet Maintenance</title>
  <link rel="stylesheet" href="css/inventory.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
 
<?php echo $head ?>

  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript">
	function verifyAudit(formname){
		if(confirm("Do you certify that you have completed an audit of the selected cabinet?"))
			formname.submit();
	}
	
	function verifyDelete(formname) {
		if ( confirm( "Are you sure that you want to delete this cabinet, including all devices, power strips, and connections?\nTHIS ACTION CAN NOT BE UNDONE!" ) ) {
			formname.delete.value="yes";
			formname.submit();
		}
	}
  </script>
</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main">
<h2><?php print $config->ParameterArray["OrgName"]; ?></h2>
<h3>Data Center Cabinet Inventory</h3>
<div class="center"><div>
<div id="centeriehack">
<div class="cabinet">
<table>
	<tr><th colspan=2>Cabinet <?php print $cab->Location; ?></th></tr>
	<tr><td width="10%">Pos</td><td width="90%">Device</td></tr>
<?php
	while ( list( $devID, $device ) = each( $devList ) ) {
		$devTop = $device->Position + $device->Height - 1;
		
		$templ->TemplateID = $device->TemplateID;
		$templ->GetTemplateByID( $facDB );

		if ( $device->NominalWatts > 0 )
			$totalWatts += $device->NominalWatts;
		else
			$totalWatts += $templ->Wattage;

		$totalWeight += $templ->Weight;
		$totalMoment += ( $templ->Weight * ( $device->Position + ( $device->Height / 2 ) ) );
		
		if($device->Reservation==false){
			$reserved="";
		}else{
			$reserved="reserved";
		}

		if($devTop < $currentHeight){
			for($i=$currentHeight;$i > $devTop;$i--){
				if($i==$currentHeight){
					$blankHeight=$currentHeight-$devTop;
					print "<tr><td>$i</td><td rowspan=$blankHeight>&nbsp;</td></tr>\n";
				} else {
					print "<tr><td>$i</td></tr>\n";
				}
			}
		} 

		for($i = $devTop; $i >= $device->Position; $i--){
			if($i==$devTop){
				$highlight="<blink><font color=red>";
				if ($device->TemplateID==0) {
					$highlight.="(T)";
				}
				if ($device->Owner==0) {
					$highlight.="(O)";
				}
				
				$highlight .= "</font></blink>";
				print "<tr><td>$i</td><td class=\"device $reserved\" rowspan=$device->Height><a href=\"devices.php?deviceid=$devID\">$highlight $device->Label</a></td></tr>\n";
			}else{
				print "<tr><td>$i</td></tr>\n";
			}
		}

		$currentHeight = $device->Position - 1;
	}

	// Fill in to the bottom
	for ( $i = $currentHeight; $i > 0; $i-- ) {
		if ( $i == $currentHeight ) {
			$blankHeight = $currentHeight + 1;

			print "<tr><td>$i</td><td rowspan=$blankHeight>&nbsp;</td></tr>\n";
		} else {
			print "<tr><td>$i</td></tr>\n";
		}
	}

	$CenterofGravity = @round( $totalMoment / $totalWeight );

	$used = $cab->CabinetOccupancy( $cab->CabinetID, $facDB );
	$SpacePercent = number_format( $used / $cab->CabinetHeight * 100, 0 );
	@$WeightPercent = number_format( $totalWeight / $cab->MaxWeight * 100, 0 );
	@$PowerPercent = number_format( ( $totalWatts / 1000 ) / $cab->MaxKW * 100, 0 );
	$CriticalColor=$config->ParameterArray["CriticalColor"];
	$CautionColor=$config->ParameterArray["CautionColor"];
	$GoodColor=$config->ParameterArray["GoodColor"];
	
	if($SpacePercent>100){$SpacePercent=100;}
	if($WeightPercent>100){$WeightPercent=100;}
	if($PowerPercent>100){$PowerPercent=100;}

	$SpaceColor=($SpacePercent>intval($config->ParameterArray["SpaceRed"])?$CriticalColor:($SpacePercent >intval($config->ParameterArray["SpaceYellow"])?$CautionColor:$GoodColor));
	$WeightColor=($WeightPercent>intval($config->ParameterArray["WeightRed"])?$CriticalColor:($WeightPercent>intval($config->ParameterArray["WeightYellow"])?$CautionColor:$GoodColor));
	$PowerColor=($PowerPercent>intval($config->ParameterArray["PowerRed"])?$CriticalColor:($PowerPercent>intval($config->ParameterArray["PowerYellow"])?$CautionColor:$GoodColor));

?>
</table>
</div>
<div id="infopanel">
	<fieldset>
		<legend>Markup Key</legend>
		<p><font color=red>(O)</font> - Owner Unassigned</p>
		<p><font color=red>(T)</font> - Template Unassigned</p>
<?php
	if($config->ParameterArray["ReservedColor"] != "#FFFFFF"){
		echo '		<p><span class="reserved border">&nbsp;&nbsp;&nbsp;&nbsp;</span> - Reservation</p>
		<p><span class="border">&nbsp;&nbsp;&nbsp;&nbsp;</span> - Normal</p>';
	}
?>
	</fieldset>
	<fieldset>
		<legend>Cabinet Metrics</legend>
		<table style="background: white;" border=1>
		<tr>
			<td>Space
				<div class="meter-wrap">
					<div class="meter-value" style="<?php print "background-color: $SpaceColor; width: $SpacePercent%;";?>">
						<div class="meter-text"><?php print $SpacePercent; ?>%</div>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<td>Weight
				<div class="meter-wrap">
					<div class="meter-value" style="<?php print "background-color: $WeightColor; width: $WeightPercent%;";?>">
						<div class="meter-text"><?php print $WeightPercent; ?>%</div>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<td>Power
				<div class="meter-wrap">
					<div class="meter-value" style="<?php print "background-color: $PowerColor; width: $PowerPercent%;";?>">
						<div class="meter-text"><?php printf("%d kW / %d kW",round($totalWatts/1000),$cab->MaxKW);?></div>
					</div>
				</div>
			</td>
		</tr>
		</table>
		<p>Approximate Center of Gravity: <?php print $CenterofGravity; ?> U</p>
	</fieldset>
	<fieldset>
		<legend>Power Distribution</legend>
<?php
	foreach ( $PDUList as $PDUdev ) {
		if( $PDUdev->IPAddress != "" ) {
			$pduDraw = $PDUdev->GetAmperage( $facDB );
		} else {
			$pduDraw=0;
		}
		
		printf( "			<a href=\"pduinfo.php?pduid=%d\">CDU %s (%d A)</font></a><br>\n", $PDUdev->PDUID, $PDUdev->Label, $pduDraw );
	}
	
	if($user->WriteAccess){
		print "			<br><br><input type=\"button\" value=\"Add CDU\" onclick=\"location='pduinfo.php?pduid=0&cabinetid=$cab->CabinetID'\">\n";
	}
?>
	</fieldset>
<fieldset>
<?php
	print "	<form method=\"post\" action=\"{$_SERVER['PHP_SELF']}\">
	<input type=\"hidden\" name=\"cabinetid\" value=\"$cab->CabinetID\">
	<input type=\"hidden\" name=\"audit\" value=\"yes\">
	<input type=\"hidden\" name=\"delete\" value=\"no\">
	<p>Last Audit: $audit->AuditStamp ($AuditorName)</p>\n";
	
	if ( $user->SiteAdmin ) {
		print "<input type=\"button\" value=\"Certify Audit\" style=\"width:120;\" onclick=\"javascript:verifyAudit(this.form)\"><br>
		<input type=\"button\" value=\"Add Device\" style=\"width:120;\" onclick=\"location='devices.php?action=new&cabinet=$cab->CabinetID'\"><br>
		<input type=\"button\" value=\"Audit Report\" style=\"width:120;\" onclick=\"location='audit_report.php?cabinetid=$cab->CabinetID'\"><br>
		<input type=\"button\" value=\"Map Coordinates\" style=\"width:120;\" onclick=\"location='mapmaker.php?cabinetid=$cab->CabinetID'\"><br>
		<input type=\"button\" value=\"Edit Cabinet\" style=\"width:120;\" onclick=\"location='cabinets.php?cabinetid=$cab->CabinetID'\"><br>
		<input type=\"button\" value=\"Delete Cabinet\" style=\"width:120;\" onclick=\"javascript:verifyDelete(this.form)\"<br>\n";
	}
?>
	</form>
</fieldset>

</div> <!-- END div#infopanel -->
</div> <!-- END div#centeriehack -->
</div></div>
</div>  <!-- END div.main -->

<div class="clear"></div>
</div>
<script type="text/javascript">
$(document).ready(function() {
	// wait half a second after the page loads then open the tree
	setTimeout(function(){
		expandToItem('datacenters','cab<?php echo $cab->CabinetID;?>');
	},500);
});
</script>
</body>
</html>
