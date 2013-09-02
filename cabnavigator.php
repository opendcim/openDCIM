<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	if((isset($_REQUEST["cabinetid"]) && (intval($_REQUEST["cabinetid"])==0)) || !isset($_REQUEST["cabinetid"])){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}


/**
 * Determines ownership of the cabinet and returns the CSS class in case a
 * color unequal white is assigned to the owner
 *
 * @param 	Cabinet 	$cabinet
 * @param 	array 		&$deptswithcolor
 * @return 	string		CSS class or empty string
 */
function get_cabinet_owner_color($cabinet, &$deptswithcolor) {
	$cab_color='';
	if ($cabinet->AssignedTo!= 0) 
	{
		$tempDept = new Department();
		$tempDept->DeptID = $cabinet->AssignedTo;
		$deptid=$tempDept->DeptID;
		if ($tempDept->GetDeptByID())
		{
			if (strtoupper($tempDept->DeptColor) != '#FFFFFF')
			{
				$deptswithcolor[$cabinet->AssignedTo]['color'] = 
				    $tempDept->DeptColor;
				$deptswithcolor[$cabinet->AssignedTo]['name'] = $tempDept->Name;
				$cab_color = "class=\"dept$deptid\"";
			}
		}
  	}
	return $cab_color;
}

	$cab=new Cabinet();
	$cab->CabinetID=$_REQUEST["cabinetid"];
	$cab->GetCabinet();

	if($cab->AssignedTo >0){
		// Check to see if this user is allowed to see anything in here
		if( ! $user->canRead($cab->AssignedTo) ){
			// This cabinet belongs to a department you don't have affiliation with, so no viewing at all
			header('Location: '.redirect());
			exit;
		}
	}
	
	// If you're deleting the cabinet, no need to pull in the rest of the information, so get it out of the way
	// Only a site administrator can create or delete a cabinet
	if(isset($_POST["delete"]) && $_POST["delete"]=="yes" && $user->SiteAdmin ) {
		$cab->DeleteCabinet();
		$url=redirect("dc_stats.php?dc=$dcID");
		header("Location: $url");
		exit;
	}
	
	if(isset($_POST['tooltip'])){
		if(isset($_POST['cdu']) && $config->ParameterArray["CDUToolTips"]=='enabled'){
			$pdu=new PowerDistribution();
			$pdu->PDUID=$_POST['tooltip'];
			$pdu->GetPDU();
			$ttconfig=$dbh->query("SELECT * FROM fac_CDUToolTip WHERE Enabled=1 ORDER BY SortOrder ASC, Enabled DESC, Label ASC;");
		}elseif($config->ParameterArray["ToolTips"]=='enabled'){
			$dev=new Device();
			$dev->DeviceID=$_POST['tooltip'];
			$dev->GetDevice();
			
			if($dev->Rights=='None'){
				print __("Details Restricted");
				exit;
			}
			$ttconfig=$dbh->query("SELECT * FROM fac_CabinetToolTip WHERE Enabled=1 ORDER BY SortOrder ASC, Enabled DESC, Label ASC;");
		}

		$tooltip="";
		foreach($ttconfig as $row){
			switch($row["Field"]){
				case "SNMPCommunity":
					if(isset($pdu->SNMPCommunity)){
						$tooltip.=__($row["Label"]).": ".$pdu->$row["Field"]."<br>\n";
					}else{
						if($dev->ESX){
							$tooltip.=__($row["Label"]).": ".$dev->$row["Field"]."<br>\n";
						}
					}
					break;
				case "ESX":
					if($dev->ESX){
						$tooltip.=__($row["Label"]).": ".$dev->$row["Field"]."<br>\n";
					}
					break;
				case "EscalationID":
					$esc=new Escalations();
					$esc->EscalationID=$dev->$row["Field"];
					$esc->GetEscalation();
					$tooltip.=__($row["Label"]).": $esc->Details<br>\n";
					break;
				case "EscalationTimeID":
					$escTime=new EscalationTimes();
					$escTime->EscalationTimeID=$dev->$row["Field"];
					$escTime->GetEscalationTime();
					$tooltip.=__($row["Label"]).": $escTime->TimePeriod<br>\n";
					break;
				case "Owner":
					$dept=new Department();
					$dept->DeptID=$dev->Owner;
					$dept->GetDeptByID();
					$tooltip.=__($row["Label"]).": $dept->Name<br>\n";
					break;
				case "TemplateID":
					$tmpl=new DeviceTemplate();
					$tmpl->TemplateID=$dev->TemplateID;
					$tmpl->GetTemplateByID();
					$man=new Manufacturer();
					$man->ManufacturerID=$tmpl->ManufacturerID;
					$man->GetManufacturerByID();
					$tooltip.=__($row["Label"]).": [$man->Name] $tmpl->Model<br>\n";
					break;
				case "ChassisSlots":
					if($dev->DeviceType=='Chassis'){
						$tooltip.=__($row["Label"])." ".$dev->$row["Field"]."<br>\n";
					}
					break;
				case "Model":
					$template=new CDUTemplate();
					$manufacturer=new Manufacturer();

					$template->TemplateID=$pdu->TemplateID;
					$template->GetTemplate();

					$manufacturer->ManufacturerID=$template->ManufacturerID;
					$manufacturer->GetManufacturerByID();
					$tooltip.=__($row["Label"]).": [$manufacturer->Name] $template->Model<br>\n";
					break;
				case "NumOutlets":
					$template=new CDUTemplate();
					$powerConn=new PowerConnection();

					$template->TemplateID=$pdu->TemplateID;
					$template->GetTemplate();

					$powerConn->PDUID=$pdu->PDUID;
					$connList=$powerConn->GetConnectionsByPDU();

					$tooltip.=__($row["Label"]).": ".count($connList)."/".($template->NumOutlets+1)."<br>\n";
					break;
				case "Uptime":
					$tooltip.=__($row["Label"]).": ".$pdu->GetSmartCDUUptime()."<br>\n";
					break;
				case "PanelID":
					$pan=new PowerPanel();
					$pan->PanelID=$pdu->PanelID;
					$pan->GetPanel();
					$tooltip.=__($row["Label"]).": $pan->PanelLabel<br>\n";
					break;
				case "PanelVoltage":
					$pan=new PowerPanel();
					$pan->PanelID=$pdu->PanelID;
					$pan->GetPanel();

					$tooltip.=__($row["Label"]).": ".$pan->PanelVoltage." / ".intval($pan->PanelVoltage/1.73)."<br>\n";
					break;
				case "DeviceType":
					// if this is a chassis device display the number of blades?
				default:
					if(isset($_POST['cdu'])){
						$tooltip.=__($row["Label"]).": ".$pdu->$row["Field"]."<br>\n";
					}else{
						$tooltip.=__($row["Label"]).": ".$dev->$row["Field"]."<br>\n";
					}
			}
		}
		if($tooltip==""){$tooltip=__("Tooltips are enabled with no options selected.");}
		$tooltip="<div>$tooltip</div>";
		print $tooltip;
		exit;
	}


	$head=$legend=$zeroheight=$body=$deptcolor=$AuditorName="";
	$audit=new CabinetAudit();
	$dev=new Device();
	$pdu=new PowerDistribution();
	$pan=new PowerPanel();
	$templ=new DeviceTemplate();
	$tempPDU=new PowerDistribution();
	$tempDept=new Department();
	$dc=new DataCenter();

	$dcID=$cab->DataCenterID;
	$dc->DataCenterID=$dcID;
	$dc->GetDataCenterbyID();

	$audit->CabinetID=$cab->CabinetID;

	// You just have WriteAccess in order to perform/certify a rack audit 
	if(isset($_REQUEST["audit"]) && $_REQUEST["audit"]=="yes" && $user->CanWrite($cab->AssignedTo)){
		$audit->UserID=$user->UserID;
		$audit->CertifyAudit();
	}

	$audit->AuditStamp=__("Never");
	$audit->GetLastAudit();
	if($audit->UserID!=""){
		$tmpUser=new User();
		$tmpUser->UserID=$audit->UserID;
		$tmpUser->GetUserRights();
		$AuditorName=$tmpUser->Name;
	}

	$pdu->CabinetID=$cab->CabinetID;
	$PDUList=$pdu->GetPDUbyCabinet();

	$dev->Cabinet=$cab->CabinetID;
	$devList=$dev->ViewDevicesByCabinet();

	$currentHeight=$cab->CabinetHeight;
	$totalWatts=0;
	$totalWeight=0;
	$totalMoment=0;

	$deptswithcolor=array();
	$cab_color = get_cabinet_owner_color($cab, $deptswithcolor);

	if($config->ParameterArray["ReservedColor"] != "#FFFFFF" || $config->ParameterArray["FreeSpaceColor"] != "#FFFFFF"){
		$head.="		<style type=\"text/css\">
			.reserved{background-color: {$config->ParameterArray['ReservedColor']};}
			.freespace{background-color: {$config->ParameterArray['FreeSpaceColor']};}\n";

		if($config->ParameterArray["ReservedColor"] != "#FFFFFF"){
			$legend.='<p><span class="reserved border">&nbsp;&nbsp;&nbsp;&nbsp;</span> - Reservation</p>';
		}
		if($config->ParameterArray["FreeSpaceColor"] != "#FFFFFF"){
			$legend.='<p><span class="freespace border">&nbsp;&nbsp;&nbsp;&nbsp;</span> - Free Space</p>';
		}
	}

	$body.="<div class=\"cabinet\">
<table>
	<tr><th id=\"cabid\" data-cabinetid=$cab->CabinetID colspan=2 $cab_color>".__("Cabinet")." $cab->Location</th></tr>
	<tr><td>".__("Pos")."</td><td>".__("Device")."</td></tr>\n";

	$heighterr="";
	$ownership_unassigned = false;
	$template_unassigned = false;
	$backside=false;
	while(list($dev_index,$device)=each($devList)){
		if($device->Height<1){
			if($device->Rights!="None"){
				$zeroheight.="				<a href=\"devices.php?deviceid=$device->DeviceID\" data-deviceid=$device->DeviceID>$highlight $device->Label</a>\n";
			}else{
				// empty html anchor for a line break
				$zeroheight.="              $highlight $device->Label\n<a></a>";
			}
		}
		
		//JMGA only fulldepth devices and front devices
		if (!$device->HalfDepth || !$device->BackSide){
			if ($device->HalfDepth) $backside=true;
			$devTop=$device->Position + $device->Height - 1;
			
			$templ->TemplateID=$device->TemplateID;
			$templ->GetTemplateByID();
	
			$tempDept->DeptID=$device->Owner;
			$tempDept->GetDeptByID();
	
			// If a dept has been changed from white then it needs to be added to the stylesheet, legend, and device
			if(!$device->Reservation && strtoupper($tempDept->DeptColor)!="#FFFFFF"){
				// Fill array with deptid and color so we can process the list once for the legend and style information
				$deptswithcolor[$device->Owner]["color"]=$tempDept->DeptColor;
				$deptswithcolor[$device->Owner]["name"]=$tempDept->Name;
			}
	
			$highlight="<blink><font color=red>";
			if($device->TemplateID==0){
				$highlight.="(T)";
	            $template_unassigned = true;
			}
			if($device->Owner==0){
				$highlight.="(O)";
	            $ownership_unassigned = true;
			}
			$highlight.= "</font></blink>";
			
			$totalWatts+=$device->GetDeviceTotalPower();
			$DeviceTotalWeight=$device->GetDeviceTotalWeight();
			$totalWeight+=$DeviceTotalWeight;
			$totalMoment+=($DeviceTotalWeight*($device->Position+($device->Height/2)));

			$reserved=($device->Reservation==false)?"":" reserved";
			if($devTop<$currentHeight && $currentHeight>0){
				for($i=$currentHeight;($i>$devTop);$i--){
					$errclass=($i>$cab->CabinetHeight)?' class="error"':'';
					if($errclass!=''){$heighterr="yup";}
					if($i==$currentHeight){
						$blankHeight=$currentHeight-$devTop;
						if($devTop==-1){--$blankHeight;}
						$body.="<tr><td$errclass>$i</td><td class=\"freespace\" rowspan=$blankHeight>&nbsp;</td></tr>\n";
					} else {
						$body.="<tr><td$errclass>$i</td></tr>\n";
						if($i==1){break;}
					}
				}
			}
			for($i=$devTop;$i>=$device->Position;$i--){
				$errclass=($i>$cab->CabinetHeight)?' class="error"':'';
				if($errclass!=''){$heighterr="yup";}
				if($i==$devTop){
					if($device->Rights!="None"){
						$body.="<tr><td$errclass>$i</td><td class=\"device$reserved dept$device->Owner\" rowspan=$device->Height data-deviceid=$device->DeviceID><a href=\"devices.php?deviceid=$device->DeviceID\">$highlight $device->Label</a></td></tr>\n";
					}else{
						$body.="<tr><td$errclass>$i</td><td class=\"device$reserved dept$device->Owner\" rowspan=$device->Height data-deviceid=$device->DeviceID>$highlight $device->Label</td></tr>\n";
					}
				}else{
 					$body.="<tr><td$errclass>$i</td></tr>\n";
				}
			}
			$currentHeight=$device->Position - 1;
		} else {
			$backside=true;
		}	
	}

	// Fill in to the bottom
	for($i=$currentHeight;$i>0;$i--){
		if($i==$currentHeight){
			$blankHeight=$currentHeight;

			$body.="<tr><td>$i</td><td class=\"freespace\" rowspan=$blankHeight>&nbsp;</td></tr>\n";
		}else{
			$body.="<tr><td>$i</td></tr>\n";
		}
	}
	
	if($backside){
		$currentHeight=$cab->CabinetHeight;
		reset($devList);
		$body.="</table></div><div class=\"cabinet\">
	<table>
		<tr><th colspan=2 $cab_color >".__("Cabinet")." $cab->Location (".__("Rear").")</th></tr>
		<tr><td>".__("Pos")."</td><td>".__("Device")."</td></tr>\n";
	
		while(list($dev_index,$device)=each($devList)){
			if (!$device->HalfDepth || $device->BackSide){
				$devTop=$device->Position + $device->Height - 1;
				
				$templ->TemplateID=$device->TemplateID;
				$templ->GetTemplateByID();
		
				$tempDept->DeptID=$device->Owner;
				$tempDept->GetDeptByID();
		
				// If a dept has been changed from white then it needs to be added to the stylesheet, legend, and device
				if(strtoupper($tempDept->DeptColor)!="#FFFFFF"){
					// Fill array with deptid and color so we can process the list once for the legend and style information
					$deptswithcolor[$device->Owner]["color"]=$tempDept->DeptColor;
					$deptswithcolor[$device->Owner]["name"]=$tempDept->Name;
				}
		
				$highlight="<blink><font color=red>";
				if($device->TemplateID==0){$highlight.="(T)";}
				if($device->Owner==0){$highlight.="(O)";}
				$highlight.= "</font></blink>";
		
				if ($device->HalfDepth) {
					// (if fulldepth device, already accounted in frontside)
					$totalWatts+=$device->GetDeviceTotalPower();
					$DeviceTotalWeight=$device->GetDeviceTotalWeight();
					$totalWeight+=$DeviceTotalWeight;
					$totalMoment+=($DeviceTotalWeight*($device->Position+($device->Height/2)));
				}
				
				$reserved=($device->Reservation==false)?"":" reserved";
				if($devTop<$currentHeight && $currentHeight>0){
					for($i=$currentHeight;$i>$devTop;$i--){
						$errclass=($i>$cab->CabinetHeight)?' class="error"':'';
						if($errclass!=''){$heighterr="yup";}
						if($i==$currentHeight){
							$blankHeight=$currentHeight-$devTop;
							if($devTop==-1){--$blankHeight;}
							$body.="<tr><td $errclass>$i</td><td class=\"freespace\" rowspan=$blankHeight>&nbsp;</td></tr>\n";
						} else {
							$body.="<tr><td $errclass>$i</td></tr>\n";
							if($i==1){break;}
						}
					}
				}
				for($i=$devTop;$i>=$device->Position;$i--){
					$errclass=($i>$cab->CabinetHeight)?' class="error"':'';
					if($errclass!=''){$heighterr="yup";}
					if($i==$devTop){
						if($device->Rights!="None"){
							$body.="<tr><td$errclass>$i</td><td class=\"device$reserved dept".
								"$device->Owner\" rowspan=$device->Height data-deviceid=$device->DeviceID><a".
								" href=\"devices.php?deviceid=$device->DeviceID\">$highlight $device->Label".
								(!$device->BackSide?" (".__("Rear").")":"")."</a></td></tr>\n";
						}else{
							$body.="<tr><td$errclass>$i</td><td class=\"device$reserved dept".
								"$device->Owner\" rowspan=$device->Height data-deviceid=$device->DeviceID>".
								"$highlight $device->Label".(!$device->BackSide?" (".__("Rear").")":"")."</td></tr>\n";
						}
					}else{
						$body.="<tr><td$errclass>$i</td></tr>\n";
					}
				}
				$currentHeight=$device->Position - 1;
			}
		}
		// Fill in to the bottom
		for($i=$currentHeight;$i>0;$i--){
			if($i==$currentHeight){
				$blankHeight=$currentHeight;
	
				$body.="<tr><td>$i</td><td class=\"freespace\" rowspan=$blankHeight>&nbsp;</td></tr>\n";
			}else{
				$body.="<tr><td>$i</td></tr>\n";
			}
		}
	}	
	
	if($heighterr!=''){$legend.='<p>* - '.__("Above defined rack height").'</p>';}

	$CenterofGravity=@round($totalMoment/$totalWeight);
	
	$used=$cab->CabinetOccupancy($cab->CabinetID);
	@$SpacePercent=($cab->CabinetHeight>0)?number_format($used/$cab->CabinetHeight*100,0):0;
	@$WeightPercent=number_format($totalWeight/$cab->MaxWeight*100,0);
	@$PowerPercent=number_format(($totalWatts/1000)/$cab->MaxKW*100,0);
	$measuredWatts = $pdu->GetWattageByCabinet( $cab->CabinetID );
	@$MeasuredPercent=number_format(($measuredWatts/1000)/$cab->MaxKW*100,0);
	$CriticalColor=$config->ParameterArray["CriticalColor"];
	$CautionColor=$config->ParameterArray["CautionColor"];
	$GoodColor=$config->ParameterArray["GoodColor"];
	
	if($SpacePercent>100){$SpacePercent=100;}
	if($WeightPercent>100){$WeightPercent=100;}
	if($PowerPercent>100){$PowerPercent=100;}
	if($MeasuredPercent>100){$MeasuredPercent=100;}

	$SpaceColor=($SpacePercent>intval($config->ParameterArray["SpaceRed"])?$CriticalColor:($SpacePercent >intval($config->ParameterArray["SpaceYellow"])?$CautionColor:$GoodColor));
	$WeightColor=($WeightPercent>intval($config->ParameterArray["WeightRed"])?$CriticalColor:($WeightPercent>intval($config->ParameterArray["WeightYellow"])?$CautionColor:$GoodColor));
	$PowerColor=($PowerPercent>intval($config->ParameterArray["PowerRed"])?$CriticalColor:($PowerPercent>intval($config->ParameterArray["PowerYellow"])?$CautionColor:$GoodColor));
	$MeasuredColor=($MeasuredPercent>intval($config->ParameterArray["PowerRed"])?$CriticalColor:($MeasuredPercent>intval($config->ParameterArray["PowerYellow"])?$CautionColor:$GoodColor));

	// I don't feel like fixing the check properly to not add in a dept with id of 0 so just remove it at the last second
	// 0 is when a dept owner hasn't been assigned, just for the record
	if(isset($deptswithcolor[0])){unset($deptswithcolor[0]);}
	if(isset($deptswithcolor[""])){unset($deptswithcolor[""]);}

	// We're done processing devices so build the legend and style blocks
	if(!empty($deptswithcolor)){
		foreach($deptswithcolor as $deptid => $row){
			// If head is empty then we don't have any custom colors defined above so add a style container for these
			if($head==""){$head.="        <style type=\"text/css\">\n";}
			$head.="			.dept$deptid{background-color: {$row['color']};}\n";
			$legend.="<p><span class=\"border dept$deptid\">&nbsp;&nbsp;&nbsp;&nbsp;</span> - <span>{$row['name']}</span></p>\n";
		}
	}

	// This will add an item to the legend for a white box. If we ever get a good name for it.
	if($legend!=""){
//		$legend.='<p><span class="border">&nbsp;&nbsp;&nbsp;&nbsp;</span> - Custom Color Not Assigned</p>';
	}
        // add legend for the flags which actually used in the cabinet
        $legend_flags = '';
        if ($ownership_unassigned) {
          $legend_flags.= '		<p><font color=red>(O)</font> - '.__("Owner Unassigned").'</p>';
        }
        if ($template_unassigned) {
          $legend_flags .= '		<p><font color=red>(T)</font> - '.__("Template Unassigned").'</p>';
        }
        

$body.='</table>
</div>
<div id="infopanel">
	<fieldset id="legend">
		<legend>'.__("Markup Key")."</legend>\n".$legend_flags."\n"
.$legend.'
	</fieldset>
	<fieldset id="metrics">
		<legend>'.__("Cabinet Metrics").'</legend>
		<table style="background: white;" border=1>
		<tr>
			<td>'.__("Space").'
				<div class="meter-wrap">
					<div class="meter-value" style="background-color: '.$SpaceColor.'; width: '.$SpacePercent.'%;">
						<div class="meter-text">'.$SpacePercent.'%</div>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<td>'.__("Weight").' ['.$cab->MaxWeight.']
				<div class="meter-wrap">
					<div class="meter-value" style="background-color: '.$WeightColor.'; width: '.$WeightPercent.'%;">
						<div class="meter-text">'.$WeightPercent.'%</div>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<td>'.__("Computed Watts").'
				<div class="meter-wrap">
					<div class="meter-value" style="background-color: '.$PowerColor.'; width: '.$PowerPercent.'%;">
						<div class="meter-text">'; $body.=sprintf("%d kW / %d kW",round($totalWatts/1000),$cab->MaxKW);$body.='</div>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<td>'.__("Measured Watts").'
				<div class="meter-wrap">
					<div class="meter-value" style="background-color: '.$MeasuredColor.'; width: '.$MeasuredPercent.'%;">
						<div class="meter-text">'; $body.=sprintf("%d kW / %d kW",round($measuredWatts/1000),$cab->MaxKW);$body.='</div>
					</div>
				</div>
			</td>
		</tr>
		</table>
		<p>'.__("Approximate Center of Gravity").': '.$CenterofGravity.' U</p>
	</fieldset>
	<fieldset id="keylock">
		<legend>'.__("Key/Lock Information").'</legend>
		<div>
			'.$cab->Keylock.'
		</div>
	</fieldset>';

	if($zeroheight!=""){
		$body.='	<fieldset id="zerou">
		<legend>'.__("Zero-U Devices").'</legend>
		<div>
			'.$zeroheight.'
		</div>
	</fieldset>';
	}
	$body.='	<fieldset name="pdu">
		<legend>'.__("Power Distribution").'</legend>';

	foreach($PDUList as $PDUdev){
		if($PDUdev->IPAddress<>""){
			$pduDraw=$PDUdev->GetWattage();
		}else{
			$pduDraw=0;
		}

		$pan->PanelID=$PDUdev->PanelID;
		$pan->GetPanel();
		
		if($PDUdev->BreakerSize==1){
			$maxDraw=$PDUdev->InputAmperage * $pan->PanelVoltage / 1.732;
		}elseif($PDUdev->BreakerSize==2){
			$maxDraw=$PDUdev->InputAmperage * $pan->PanelVoltage;
		}else{
			$maxDraw=$PDUdev->InputAmperage * $pan->PanelVoltage * 1.732;
		}

		// De-rate all breakers to 80% sustained load
		$maxDraw*=0.8;
		
		if($maxDraw>0){
			$PDUPercent=$pduDraw/$maxDraw*100;
		}else{
			$PDUPercent=0;
		}
			
		$PDUColor=($PDUPercent>intval($config->ParameterArray["PowerRed"])?$CriticalColor:($PDUPercent>intval($config->ParameterArray["PowerYellow"])?$CautionColor:$GoodColor));
		
		$body.=sprintf("			<a href=\"power_pdu.php?pduid=%d\">CDU %s</a><br>(%.2f kW) / (%.2f kW Max)</font><br>\n", $PDUdev->PDUID, $PDUdev->Label, $pduDraw / 1000, $maxDraw / 1000 );
		$body.=sprintf("				<div class=\"meter-wrap\">\n\t<div class=\"meter-value\" style=\"background-color: %s; width: %d%%;\">\n\t\t<div class=\"meter-text\">%d%%</div>\n\t</div>\n</div><br>", $PDUColor, $PDUPercent, $PDUPercent );
	}
	
	if($user->CanWrite($cab->AssignedTo)){
		$body.="			<ul class=\"nav\"><a href=\"power_pdu.php?pduid=0&cabinetid=$cab->CabinetID\"><li>".__("Add CDU")."</li></a></ul>\n";
	}

	$body.="	</fieldset>
<fieldset>
	<table id=\"cabprop\">
	<tr><td class=\"left\">".__("Last Audit").":</td>"
	    . "<td class=\"right\">".$audit->AuditStamp."<br>($AuditorName)</td></tr>
	<tr><td class=\"left\">".__("Model").":</td>"
	    . "<td class=\"right\">".$cab->Model."</td></tr>
	<tr><td class=\"left\">".__("Installation Date").":</td><td class=\"right\">".$cab->InstallationDate."</td></tr>";
	if ($cab->ZoneID)
	{
	    $zone = new Zone();
	    $zone->ZoneID = $cab->ZoneID;
	    $zone->GetZone();
	    $body.="<tr><td class=\"left\">".__("Zone").":</td>"
	        . "<td class=\"right\">".$zone->Description."</td></tr>";
	}
	if ($cab->CabRowID)
	{
	    $cabrow = new CabRow();
	    $cabrow->CabRowID = $cab->CabRowID;
	    $cabrow->GetCabRow();
	    $body.="<tr><td class=\"left\">".__("Row").":</td>"
	        . "<td class=\"right\">".$cabrow->Name."</td></tr>";
	}
	
	$body.="<tr><td class=\"left\">".__("Notes").":</td><td class=\"right\">".$cab->Notes."</td></tr>
	    </table>
	    <ul class=\"nav\">\n";
	if($user->CanWrite($cab->AssignedTo)){
		$body.="
		<a href=\"#\" onclick=\"javascript:verifyAudit(this.form)\"><li>".__("Certify Audit")."</li></a>
		<a href=\"devices.php?action=new&cabinet=$cab->CabinetID\"><li>".__("Add Device")."</li></a>
		<a href=\"cabaudit.php?cabinetid=$cab->CabinetID\"><li>".__("Audit Report")."</li></a>
		<a href=\"mapmaker.php?cabinetid=$cab->CabinetID\"><li>".__("Map Coordinates")."</li></a>
		<a href=\"cabinets.php?cabinetid=$cab->CabinetID\"><li>".__("Edit Cabinet")."</li></a>\n";
	}
	if($user->SiteAdmin){
		$body.="<a href=\"#\" onclick=\"javascript:verifyDelete(this.form)\"><li>".__("Delete Cabinet")."</li></a>";
	}

	$body.='	</ul>
</fieldset>

</div> <!-- END div#infopanel -->';

	// If $head isn't empty then we must have added some style information so close the tag up.
	if($head!=""){
		$head.='		</style>';
	}

	$title=($cab->Location!='')?"$cab->Location :: $dc->Name":__("Facilities Cabinet Maintenance");
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title><?php echo $title; ?></title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
 
<?php 

echo $head,'  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript">
	var form=$("<form>").attr({ method: "post", action: "cabnavigator.php" });
	$("<input>").attr({ type: "hidden", name: "cabinetid", value: "',$cab->CabinetID,'"}).appendTo(form);
	function verifyAudit(formname){
		if(confirm("',__("Do you certify that you have completed an audit of the selected cabinet?"),'")){
			$("<input>").attr({ type: "hidden", name: "audit", value: "yes"}).appendTo(form);
			form.appendTo("body");
			form.submit();
		}
	}
	
	function verifyDelete(formname){
		if(confirm("',__("Are you sure that you want to delete this cabinet, including all devices, power strips, and connections?"),'\n',__("THIS ACTION CAN NOT BE UNDONE!"),'")){
			$("<input>").attr({ type: "hidden", name: "delete", value: "yes"}).appendTo(form);
			form.appendTo("body");
			form.submit();
		}
	}
	$(document).ready(function() {
		$(".cabinet .error").append("*");
		if($("#legend *").length==1){$("#legend").hide();}
		if($("#keylock div").text().trim()==""){$("#keylock").hide();}
';
if($config->ParameterArray["ToolTips"]=='enabled'){
?>
		$('.cabinet td.device:has(a), #zerou div > a').mouseenter(function(){
			var pos=$(this).offset();
			var tooltip=$('<div />').css({
				'left':pos.left+$(this).outerWidth()+15+'px',
				'top':pos.top+($(this).outerHeight()/2)-15+'px'
			}).addClass('arrow_left border cabnavigator tooltip').attr('id','tt').append('<span class="ui-icon ui-icon-refresh rotate"></span>');
			$.post('',{tooltip: $(this).data('deviceid')}, function(data){
				tooltip.html(data);
			});
			$('body').append(tooltip);
			$(this).mouseleave(function(){
				tooltip.remove();
			});
		});
<?php
}
if($config->ParameterArray["CDUToolTips"]=='enabled'){
?>
		$('fieldset[name="pdu"] legend ~ a').mouseenter(function(){
			var pos=$(this).offset();
			var tooltip=$('<div />').css({
				'left':pos.left+$(this).outerWidth()+15+'px',
				'top':pos.top+($(this).outerHeight()/2)-15+'px'
			}).addClass('arrow_left border cabnavigator tooltip').attr('id','tt').append('<span class="ui-icon ui-icon-refresh rotate"></span>');
			var id=$(this).attr('href');
			id=id.substring(id.lastIndexOf('=')+1,id.length);
			$.post('',{tooltip: id, cdu: ''}, function(data){
				tooltip.html(data);
			});
			$('body').append(tooltip);
			$(this).mouseleave(function(){
				tooltip.remove();
			});
		});
<?php
}
?>
	});
  </script>
</head>

<body>
<div id="header"></div>
<div class="page">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main cabnavigator">
<h2><?php print $config->ParameterArray["OrgName"]; ?></h2>
<h3><?php print __("Data Center Cabinet Inventory"); ?></h3>
<div class="center"><div>
<div id="centeriehack">
<?php
	echo $body;
?>
</div> <!-- END div#centeriehack -->
</div></div>
<?php
	if($dcID>0){
		print "	<a href=\"dc_stats.php?dc=$dcID\">[ ".__('Return to')." $dc->Name ]</a>";
	}
?>
</div>  <!-- END div.main -->

<div class="clear"></div>
</div>
<script type="text/javascript">
	$(document).ready(function() {
		// Don't attempt to open the datacenter tree until it is loaded
		function opentree(){
			if($('#datacenters .bullet').length==0){
				setTimeout(function(){
					opentree();
				},500);
			}else{
				expandToItem('datacenters','cab<?php echo $cab->CabinetID;?>');
			}
		}
		opentree();

		// Combine the two racks into one table so the sizes are equal
		if($('.cabinet + .cabinet').length >0){
			var width=$('#centeriehack > .cabinet:first-child').width();
			$('.cabinet tr > td:first-child').addClass('pos');
			$('.cabinet + .cabinet').find('tr').each(function(i){
				$(this).prepend($('#centeriehack > .cabinet:first-child').find('tr').eq(i).find('td, th'));
			});
			$('.cabinet td').each(function(){
				$(this).css('width',($(this).hasClass('pos'))?'auto':'45%');
			});
			$('#centeriehack > .cabinet:first-child').remove();
			$('.cabinet').width(width*2).css('max-width',width*2+'px');
		}
	});
</script>
</body>
</html>
