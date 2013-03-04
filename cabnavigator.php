<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights($facDB);

	if((isset($_REQUEST["cabinetid"]) && ($_REQUEST["cabinetid"]=="" || $_REQUEST["cabinetid"]==null)) || !isset($_REQUEST["cabinetid"]) || !$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	// If you're deleting the cabinet, no need to pull in the rest of the information, so get it out of the way
	if(isset($_POST["delete"]) && $_POST["delete"]=="yes" && $user->SiteAdmin){
		$cab->DeleteCabinet($facDB);
		$url=redirect("dc_stats.php?dc=$dcID");
		header("Location: $url");
		exit;
	}
	
	if(isset($_POST['tooltip'])){
		if($config->ParameterArray["ToolTips"]=='enabled'){
			$dev=new Device();
			$dev->DeviceID=$_POST['tooltip'];
			$dev->GetDevice($facDB);

			$tooltip="";
			$ttconfig=mysql_query("SELECT * FROM fac_CabinetToolTip WHERE Enabled=1 ORDER BY SortOrder ASC, Enabled DESC, Label ASC;");
			while($row=mysql_fetch_assoc($ttconfig)){
				switch($row["Field"]){
					case "SNMPCommunity":
						if($dev->ESX){
							$tooltip.=__($row["Label"]).": ".$dev->$row["Field"]."<br>\n";
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
						$esc->GetEscalation($facDB);
						$tooltip.=__($row["Label"]).": $esc->Details<br>\n";
						break;
					case "EscalationTimeID":
						$escTime=new EscalationTimes();
						$escTime->EscalationTimeID=$dev->$row["Field"];
						$escTime->GetEscalationTime($facDB);
						$tooltip.=__($row["Label"]).": $escTime->TimePeriod<br>\n";
						break;
					case "Owner":
						$dept=new Department();
						$dept->DeptID=$dev->Owner;
						$dept->GetDeptByID($facDB);
						$tooltip.=__($row["Label"]).": $dept->Name<br>\n";
						break;
					case "TemplateID":
						$tmpl=new DeviceTemplate();
						$tmpl->TemplateID=$dev->TemplateID;
						$tmpl->GetTemplateByID($facDB);
						$man=new Manufacturer();
						$man->ManufacturerID=$tmpl->ManufacturerID;
						$man->GetManufacturerByID($facDB);
						$tooltip.=__($row["Label"]).": [$man->Name] $tmpl->Model<br>\n";
						break;
					case "ChassisSlots":
						if($dev->DeviceType=='Chassis'){
							$tooltip.=__($row["Label"])." ".$dev->$row["Field"]."<br>\n";
						}
						break;
					case "DeviceType":
						// if this is a chassis device display the number of blades?
					default:
						$tooltip.=__($row["Label"]).": ".$dev->$row["Field"]."<br>\n";
				}
			}
			if($tooltip==""){$tooltip=__("Tooltips are enabled with no options selected.");}
			$tooltip="<div>$tooltip</div>";
			print $tooltip;
		}
		exit;
	}


	$head=$legend=$zeroheight=$body=$deptcolor="";
	$cab=new Cabinet();
	$audit=new CabinetAudit();
	$pdu=new PowerDistribution();
	$pan = new PowerPanel();
	$dev=new Device();
	$templ=new DeviceTemplate();
	$tempPDU=new PowerDistribution();
	$tempDept=new Department();
	$dc=new DataCenter();

	// Even if we're deleting the cabinet, it's helpful to know which data center to go back to displaying afterwards
	$cab->CabinetID=$_REQUEST["cabinetid"];
	$cab->GetCabinet($facDB);

	if(is_null($cab->CabinetID)){
		header('Location: '.redirect());
		exit;
	}

	$dcID=$cab->DataCenterID;
	$dc->DataCenterID=$dcID;
	$dc->GetDataCenterbyID($facDB);

	$audit->CabinetID=$cab->CabinetID;

	// You just have WriteAccess in order to perform/certify a rack audit 
	if(isset($_REQUEST["audit"]) && $_REQUEST["audit"]=="yes" && $user->WriteAccess){
		$audit->UserID=$user->UserID;
		$audit->CertifyAudit($facDB);
	}

	$audit->AuditStamp="Never";
	$audit->GetLastAudit($facDB);
	if($audit->UserID!=""){
		$tmpUser=new User();
		$tmpUser->UserID=$audit->UserID;
		$tmpUser->GetUserRights($facDB);
		$AuditorName=$tmpUser->Name;
	}else{
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
	<tr><th colspan=2>".__("Cabinet")." $cab->Location</th></tr>
	<tr><td>".__("Pos")."</td><td>".__("Device")."</td></tr>\n";

	$deptswithcolor=array();
	$heighterr="";
	while(list($devID,$device)=each($devList)){
		$devTop=$device->Position + $device->Height - 1;
		
		$templ->TemplateID=$device->TemplateID;
		$templ->GetTemplateByID($facDB);

		$tempDept->DeptID=$device->Owner;
		$tempDept->GetDeptByID($facDB);

		// If a dept has been changed from white then it needs to be added to the stylesheet, legend, and device
		if(!$device->Reservation && strtoupper($tempDept->DeptColor)!="#FFFFFF"){
			// Fill array with deptid and color so we can process the list once for the legend and style information
			$deptswithcolor[$device->Owner]["color"]=$tempDept->DeptColor;
			$deptswithcolor[$device->Owner]["name"]=$tempDept->Name;
		}

		$highlight="<blink><font color=red>";
		if($device->TemplateID==0){$highlight.="(T)";}
		if($device->Owner==0){$highlight.="(O)";}
		$highlight.= "</font></blink>";

		if($device->NominalWatts >0){
			$totalWatts+=$device->NominalWatts;
		}elseif($device->TemplateID!=0 && $templ->Wattage>0){
			$totalWatts+=$templ->Wattage;
		}
		
		if($device->TemplateID!=0){
			$totalWeight+=$templ->Weight;
			$totalMoment+=($templ->Weight*($device->Position+($device->Height/2)));
		}

		if($device->Height<1){
			$zeroheight.="				<a href=\"devices.php?deviceid=$devID\">$highlight $device->Label</a>\n";
		}
		// Chassis devices shouldn't ever be 0u in height
		if($device->DeviceType=="Chassis"){
			$childList=$device->GetDeviceChildren($facDB);
			$childTempl=newDeviceTemplate();
			foreach($childList as $childDev){
				$childTempl->TemplateID=$childDev->TemplateID;
				$childTempl->GetTemplateByID($facDB);
				if($childDev->NominalWatts>0){
					$totalWatts+=$childDev->NominalWatts;
				} elseif($childDev->TemplateID!=0 && $childTempl->Wattage>0){
					$totalWatts+=$childTempl->Wattage;
				}
				if($childDev->TemplateID!=0){
					$totalWeight+=$childTempl->Weight;
					//Child device's position is parent's position
					$totalMoment+=($childTempl->Weight*($device->Position+($device->Height/2)));
				}
			}
		}
		$reserved=($device->Reservation==false)?"":" reserved";
		if($devTop<$currentHeight){
			for($i=$currentHeight;$i>$devTop;$i--){
				$errclass=($i>$cab->CabinetHeight)?' class="error"':'';
				if($errclass!=''){$heighterr="yup";}
				if($i==$currentHeight){
					$blankHeight=$currentHeight-$devTop;
					$body.="<tr><td$errclass>$i</td><td class=\"freespace\" rowspan=$blankHeight>&nbsp;</td></tr>\n";
				} else {
					$body.="<tr><td$errclass>$i</td></tr>\n";
				}
			}
		}
		for($i=$devTop;$i>=$device->Position;$i--){
			$errclass=($i>$cab->CabinetHeight)?' class="error"':'';
			if($errclass!=''){$heighterr="yup";}
			if($i==$devTop){
				$body.="<tr><td$errclass>$i</td><td class=\"device$reserved dept$device->Owner\" rowspan=$device->Height data=$devID><a href=\"devices.php?deviceid=$devID\">$highlight $device->Label</a></td></tr>\n";
			}else{
				$body.="<tr><td$errclass>$i</td></tr>\n";
			}
		}
		$currentHeight=$device->Position - 1;
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

	if($heighterr!=''){$legend.='<p>* - '.__("Above defined rack height").'</p>';}

	$CenterofGravity=@round($totalMoment/$totalWeight);

	$used=$cab->CabinetOccupancy($cab->CabinetID,$facDB);
	$SpacePercent=number_format($used/$cab->CabinetHeight*100,0);
	@$WeightPercent=number_format($totalWeight/$cab->MaxWeight*100,0);
	@$PowerPercent=number_format(($totalWatts/1000)/$cab->MaxKW*100,0);
	$CriticalColor=$config->ParameterArray["CriticalColor"];
	$CautionColor=$config->ParameterArray["CautionColor"];
	$GoodColor=$config->ParameterArray["GoodColor"];
	
	if($SpacePercent>100){$SpacePercent=100;}
	if($WeightPercent>100){$WeightPercent=100;}
	if($PowerPercent>100){$PowerPercent=100;}

	$SpaceColor=($SpacePercent>intval($config->ParameterArray["SpaceRed"])?$CriticalColor:($SpacePercent >intval($config->ParameterArray["SpaceYellow"])?$CautionColor:$GoodColor));
	$WeightColor=($WeightPercent>intval($config->ParameterArray["WeightRed"])?$CriticalColor:($WeightPercent>intval($config->ParameterArray["WeightYellow"])?$CautionColor:$GoodColor));
	$PowerColor=($PowerPercent>intval($config->ParameterArray["PowerRed"])?$CriticalColor:($PowerPercent>intval($config->ParameterArray["PowerYellow"])?$CautionColor:$GoodColor));

	// I don't feel like fixing the check properly to not add in a dept with id of 0 so just remove it at the last second
	// 0 is when a dept owner hasn't been assigned, just for the record
	if(isset($deptswithcolor[0])){unset($deptswithcolor[0]);}

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

$body.='</table>
</div>
<div id="infopanel">
	<fieldset>
		<legend>'.__("Markup Key").'</legend>
		<p><font color=red>(O)</font> - '.__("Owner Unassigned").'</p>
		<p><font color=red>(T)</font> - '.__("Template Unassigned").'</p>
'.$legend.'
	</fieldset>
	<fieldset>
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
			<td>'.__("Weight").'
				<div class="meter-wrap">
					<div class="meter-value" style="background-color: '.$WeightColor.'; width: '.$WeightPercent.'%;">
						<div class="meter-text">'.$WeightPercent.'%</div>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<td>'.__("Power").'
				<div class="meter-wrap">
					<div class="meter-value" style="background-color: '.$PowerColor.'; width: '.$PowerPercent.'%;">
						<div class="meter-text">'; $body.=sprintf("%d kW / %d kW",round($totalWatts/1000),$cab->MaxKW);$body.='</div>
					</div>
				</div>
			</td>
		</tr>
		</table>
		<p>'.__("Approximate Center of Gravity").': '.$CenterofGravity.' U</p>
	</fieldset>
	<fieldset>
		<legend>'.__("Key/Lock Information").'</legend>
		<div id="keylock">
			'.$cab->Keylock.'
		</div>
	</fieldset>';

	if($zeroheight!=""){
		$body.='	<fieldset>
		<legend>'.__("Zero-U Devices").'</legend>
		<div id="zerou">
			'.$zeroheight.'
		</div>
	</fieldset>';
	}
	$body.='	<fieldset>
		<legend>'.__("Power Distribution").'</legend>';

	foreach($PDUList as $PDUdev){
		if($PDUdev->IPAddress<>""){
			$pduDraw=$PDUdev->GetWattage($facDB);
		}else{
			$pduDraw=0;
		}

		$pan->PanelID=$PDUdev->PanelID;
		$pan->GetPanel($facDB);
		
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
	
	if($user->WriteAccess){
		$body.="			<ul class=\"nav\"><a href=\"power_pdu.php?pduid=0&cabinetid=$cab->CabinetID\"><li>".__("Add CDU")."</li></a></ul>\n";
	}

	$body.="	</fieldset>
<fieldset>
	<p>".__("Last Audit").": $audit->AuditStamp<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;($AuditorName)</p>
	<ul class=\"nav\">\n";
	if($user->WriteAccess){
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

	$title=($cab->Location!='')?"$cab->Location :: $dc->Name":'Facilities Cabinet Maintenance';

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
';
if($config->ParameterArray["ToolTips"]=='enabled'){
?>
		var n=0; // silly counter
		$('.cabinet td.device').mouseenter(function(){
			n++;
			var pos=$(this).offset();
			var tooltip=$('<div />').css({
				'left':pos.left+$(this).outerWidth()+15+'px',
				'top':pos.top+($(this).outerHeight()/2)-15+'px'
			}).addClass('arrow_left border cabnavigator tooltip').attr('id','tt'+n);
			$.post('',{tooltip: $(this).attr('data')}, function(data){
				tooltip.append(data);
			});
			$('body').append(tooltip);
			$(this).mouseleave(function(){
				$('#tt'+n).remove();
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
	// wait half a second after the page loads then open the tree
	setTimeout(function(){
		expandToItem('datacenters','cab<?php echo $cab->CabinetID;?>');
	},500);
});
</script>
</body>
</html>
