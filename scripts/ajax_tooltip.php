<?php
	require_once("../db.inc.php");
	require_once("../facilities.inc.php");
/*
 * All tooltips will come through this file from here out.
 *
 * Submit the id of the object you want a tooltip for as tooltip.
 *
 * $_POST['tooltip'] = id for object of tooltip - REQUIRED
 * $_POST['cab'] = Required for cabinet tooltips
 * $_POST['cdu'] = Required for cdu tooltips
 * $_POST['dev'] = Required for device tooltips
 */

// Use the global configuration
global $config;

// We're gonna use this as an intval wherever anyhow so just get it done.
$object=(isset($_POST['tooltip']))?intval($_POST['tooltip']):0;

// Default tooltip 
$tooltip=__('Error');

// Init Objects
$cab=new Cabinet();
$dev=new Device();
$dep=new Department();

if($config->ParameterArray["mUnits"]=="english"){
	$weightunit="lbs";
}else{
	$weightunit="Kg";
}

// If the object id isn't set then don't bother with anything else.
if($object>0){
	// Cabinet
	if(isset($_POST['cab'])){
		
		$sql="SELECT C.*, T.Temp, T.Humidity, P.RealPower, T.LastRead, PLR.RPLastRead 
			FROM ((fac_Cabinet C LEFT JOIN fac_CabinetTemps T ON C.CabinetID = T.CabinetID) 
				LEFT JOIN (SELECT CabinetID, SUM(Wattage) RealPower FROM fac_PowerDistribution PD 
				LEFT JOIN fac_PDUStats PS ON PD.PDUID=PS.PDUID GROUP BY CabinetID) P ON C.CabinetID = P.CabinetID) 
			LEFT JOIN (SELECT CabinetID, MAX(LastRead) RPLastRead FROM fac_PowerDistribution PD 
				LEFT JOIN fac_PDUStats PS ON PD.PDUID=PS.PDUID GROUP BY CabinetID) PLR 
				ON C.CabinetID = PLR.CabinetID WHERE C.CabinetID=$object;";

		if($cabRow=$dbh->query($sql)->fetch()){
			$cab->CabinetID=$cabRow["CabinetID"];
			$cab->GetCabinet();
			$dep->DeptID = $cab->AssignedTo;
			if ( $dep->DeptID > 0 ) {
				$dep->GetDeptByID();
			} else {
				$dep->Name = __("General Use");
			}
			$dev->Cabinet=$cab->CabinetID;
			$devList=$dev->ViewDevicesByCabinet();
			$curHeight = $cab->CabinetHeight;
			$totalWatts = $totalWeight = $totalMoment =0;
			$curTemp=$cabRow["Temp"];
			$curHum=$cabRow["Humidity"];
			$curRealPower=$cabRow["RealPower"];
			$lastRead=(!is_null($cabRow["LastRead"]))?strftime('%c',strtotime(($cabRow["LastRead"]))):0;
			$RPlastRead=(!is_null($cabRow["RPLastRead"]))?strftime('%c',strtotime(($cabRow["RPLastRead"]))):0;
			$rs='red';
			$ys='yellow';
			$gs='green';
			$us='wtf';
			
			// get all limits for use with loop below
			$SpaceRed=intval($config->ParameterArray["SpaceRed"]);
			$SpaceYellow=intval($config->ParameterArray["SpaceYellow"]);
			$WeightRed=intval($config->ParameterArray["WeightRed"]);
			$WeightYellow=intval($config->ParameterArray["WeightYellow"]);
			$PowerRed=intval($config->ParameterArray["PowerRed"]);
			$PowerYellow=intval($config->ParameterArray["PowerYellow"]);
			$RealPowerRed=intval($config->ParameterArray["PowerRed"]);
			$RealPowerYellow=intval($config->ParameterArray["PowerYellow"]);
			
			// Temperature 
			$TempYellow=intval($config->ParameterArray["TemperatureYellow"]);
			$TempRed=intval($config->ParameterArray["TemperatureRed"]);
			
			// Humidity
			$HumMin=intval($config->ParameterArray["HumidityRedLow"]);
			$HumMedMin=intval($config->ParameterArray["HumidityYellowLow"]);			
			$HumMedMax=intval($config->ParameterArray["HumidityYellowHigh"]);				
			$HumMax=intval($config->ParameterArray["HumidityRedHigh"]);
						
			
			while(list($devID,$device)=each($devList)){
				$totalWatts+=$device->GetDeviceTotalPower();
				$DeviceTotalWeight=$device->GetDeviceTotalWeight();
				$totalWeight+=$DeviceTotalWeight;
				$totalMoment+=($DeviceTotalWeight*($device->Position+($device->Height/2)));
			}
				
			$used=$cab->CabinetOccupancy($cab->CabinetID);
			// check to make sure the cabinet height is set to keep errors out of the logs
			if(!isset($cab->CabinetHeight)||$cab->CabinetHeight==0){$SpacePercent=100;}else{$SpacePercent=locale_number($used /$cab->CabinetHeight *100,0);}
			// check to make sure there is a weight limit set to keep errors out of logs
			if(!isset($cab->MaxWeight)||$cab->MaxWeight==0){$WeightPercent=0;}else{$WeightPercent=locale_number($totalWeight /$cab->MaxWeight *100,0);}
			// check to make sure there is a kilowatt limit set to keep errors out of logs
			if(!isset($cab->MaxKW)||$cab->MaxKW==0){$PowerPercent=0;}else{$PowerPercent=locale_number(($totalWatts /1000 ) /$cab->MaxKW *100,0);}
			if(!isset($cab->MaxKW)||$cab->MaxKW==0){$RealPowerPercent=0;}else{$RealPowerPercent=locale_number(($curRealPower /1000 ) /$cab->MaxKW *100,0);}
		
			//Decide which color to paint on the canvas depending on the thresholds
			if($SpacePercent>$SpaceRed){$scolor=$rs;}elseif($SpacePercent>$SpaceYellow){$scolor=$ys;}else{$scolor=$gs;}
			if($WeightPercent>$WeightRed){$wcolor=$rs;}elseif($WeightPercent>$WeightYellow){$wcolor=$ys;}else{$wcolor=$gs;}
			if($PowerPercent>$PowerRed){$pcolor=$rs;}elseif($PowerPercent>$PowerYellow){$pcolor=$ys;}else{$pcolor=$gs;}
			if($RPlastRead=='0'){$rpcolor=$us;}elseif($RealPowerPercent>$RealPowerRed){$rpcolor=$rs;}elseif($RealPowerPercent>$RealPowerYellow){$rpcolor=$ys;}else{$rpcolor=$gs;}
			if($curTemp==0){$tcolor=$us;}elseif($curTemp>$TempRed){$tcolor=$rs;}elseif($curTemp>$TempYellow){$tcolor=$ys;}else{$tcolor=$gs;}
			if($curHum==0){$hcolor=$us;}elseif($curHum>$HumMax || $curHum<$HumMin){$hcolor=$rs;}elseif($curHum>$HumMedMax || $curHum<$HumMedMin){$hcolor=$ys;}else{$hcolor=$gs;}
				
			$labelsp=locale_number($used,0)." / $cab->CabinetHeight U";
			$labelwe=locale_number($totalWeight,0)." / $cab->MaxWeight $weightunit";
			$labelpo=locale_number($totalWatts/1000,2)." / $cab->MaxKW kW";
			$labelte=(($curTemp>0)?locale_number($curTemp,0)."&deg; ($lastRead)":__("no data"));
			$labelhu=(($curHum>0)?locale_number($curHum,0)." % ($lastRead)":__("no data"));
			$labelrp=(($RPlastRead!='0')?locale_number($curRealPower/1000,2)." / $cab->MaxKW kW ($RPlastRead)":__("no data"));
			
			$tooltip="<span>$cab->Location</span><ul>\n";
			$tooltip.="<li>".__("Owner").": $dep->Name</li>\n";
			$tooltip.="<li class=\"$scolor\">".__("Space").": $labelsp</li>\n";
			$tooltip.="<li class=\"$wcolor\">".__("Weight").": $labelwe</li>\n";
			$tooltip.="<li class=\"$pcolor\">".__("Calculated Power").": $labelpo</li>\n";
			$tooltip.="<li class=\"$rpcolor\">".__("Measured Power").": $labelrp</li>\n";
			$tooltip.="<li class=\"$tcolor\">".__("Temperature").": $labelte</li>\n";
			$tooltip.="<li class=\"$hcolor\">".__("Humidity").": $labelhu</li></ul>\n";

		}
	}elseif(isset($_POST['cdu']) || isset($_POST['dev'])){
		if(isset($_POST['cdu']) && $config->ParameterArray["CDUToolTips"]=='enabled'){
			$pdu=new PowerDistribution();
			$pdu->PDUID=$object;
			$pdu->GetPDU();
			$ttconfig=$dbh->query("SELECT * FROM fac_CDUToolTip WHERE Enabled=1 ORDER BY SortOrder ASC, Enabled DESC, Label ASC;");
			$tooltip = __("Name") . ": " . $pdu->Label . "<br>\n";
		}elseif($config->ParameterArray["ToolTips"]=='enabled'){
			$dev=new Device();
			$dev->DeviceID=$object;
			$dev->GetDevice();

			if($dev->Rights=='None'){
				print __("Details Restricted");
				exit;
			}
			$ttconfig=$dbh->query("SELECT * FROM fac_CabinetToolTip WHERE Enabled=1 ORDER BY SortOrder ASC, Enabled DESC, Label ASC;");
			$tooltip = __("Name") . ": " . $dev->Label . "<br>\n";
		}

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
	}
}

$tooltip="<div>$tooltip</div>";
print $tooltip;
exit;

?>
