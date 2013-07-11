<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights();

	// Get the list of departments that this user is a member of
	$viewList = $user->isMemberOf();

/**
 * Determines ownership of the cabinet and returns the CSS class in case a
 * color unequal white is assigned to the owner
 *
 * @param 	Cabinet 	$cabinet
 * @param 	array 		&$deptswithcolor
 * @return 	string		CSS class or empty string
 */
function get_cabinet_owner_color($cabinet, &$deptswithcolor) {
  $cab_color = '';
  if ($cabinet->AssignedTo != 0) {
    $tempDept = new Department();
    $tempDept->DeptID = $cabinet->AssignedTo;
    $deptid = $tempDept->DeptID;
    $tempDept->GetDeptByID();
    if (strtoupper($tempDept->DeptColor) != "#FFFFFF") {
       $deptswithcolor[$cabinet->AssignedTo]["color"] = $tempDept->DeptColor;
       $deptswithcolor[$cabinet->AssignedTo]["name"]= $tempDept->Name;
       $cab_color = "class=\"dept$deptid\"";
    }
  }
  return $cab_color;
}

	$cab=new Cabinet();

	if(isset($_POST['tooltip'])){
		if(isset($_POST['cdu']) && $config->ParameterArray["CDUToolTips"]=='enabled'){
			$pdu=new PowerDistribution();
			$pdu->PDUID=intval($_POST['tooltip']);
			$pdu->GetPDU();
			$ttconfig=mysql_query("SELECT * FROM fac_CDUToolTip WHERE Enabled=1 ORDER BY SortOrder ASC, Enabled DESC, Label ASC;");
		}elseif($config->ParameterArray["ToolTips"]=='enabled'){
			$dev=new Device();
			$dev->DeviceID=intval($_POST['tooltip']);
			$dev->GetDevice();
			
			if(!in_array($dev->Owner,$viewList) && !$user->ReadAccess){
				print "Details Restricted";
				exit;
			}
			$ttconfig=mysql_query("SELECT * FROM fac_CabinetToolTip WHERE Enabled=1 ORDER BY SortOrder ASC, Enabled DESC, Label ASC;");
		}

		$tooltip="";
		while($row=mysql_fetch_assoc($ttconfig)){
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
				case "Model":
					$template=new CDUTemplate();
					$manufacturer=new Manufacturer();

					$template->TemplateID=$pdu->TemplateID;
					$template->GetTemplate($facDB);

					$manufacturer->ManufacturerID=$template->ManufacturerID;
					$manufacturer->GetManufacturerByID($facDB);
					$tooltip.=__($row["Label"]).": [$manufacturer->Name] $template->Model<br>\n";
					break;
				case "NumOutlets":
					$template=new CDUTemplate();
					$powerConn=new PowerConnection();

					$template->TemplateID=$pdu->TemplateID;
					$template->GetTemplate($facDB);

					$powerConn->PDUID=$pdu->PDUID;
					$connList=$powerConn->GetConnectionsByPDU($facDB);

					$tooltip.=__($row["Label"]).": ".count($connList)."/".($template->NumOutlets+1)."<br>\n";
					break;
				case "Uptime":
					$tooltip.=__($row["Label"]).": ".$pdu->GetSmartCDUUptime()."<br>\n";
					break;
				case "PanelID":
					$pan=new PowerPanel();
					$pan->PanelID=$pdu->PanelID;
					$pan->GetPanel($facDB);
					$tooltip.=__($row["Label"]).": $pan->PanelLabel<br>\n";
					break;
				case "PanelVoltage":
					$pan=new PowerPanel();
					$pan->PanelID=$pdu->PanelID;
					$pan->GetPanel($facDB);

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


	$head=$legend=$zeroheight=$body=$deptcolor="";
	$deptswithcolor=array();
	$dev=new Device();
	$pan=new PowerPanel();
	$templ=new DeviceTemplate();
	$tempPDU=new PowerDistribution();
	$tempDept=new Department();
	$dc=new DataCenter();
	$cabrow=new CabRow();

	$cabrow->CabRowID=$_REQUEST['row'];
	$cabrow->GetCabRow();
	$cab->CabRowID=$cabrow->CabRowID;
	$cabinets=$cab->GetCabinetByRow();

//start loop to parse all cabinets in the row
foreach($cabinets as $cabid => $cabinet){

		$cab->CabinetID=$cabid;
		$cab->GetCabinet();
	

		$dev->Cabinet=$cabid;
		$devList=$dev->ViewDevicesByCabinet();

		$currentHeight=$cab->CabinetHeight;

		$cab_color = get_cabinet_owner_color($cab, $deptswithcolor);

		if($config->ParameterArray["ReservedColor"] != "#FFFFFF" || $config->ParameterArray["FreeSpaceColor"] != "#FFFFFF"){
			$head.="		<style type=\"text/css\">
				.reserved{background-color: {$config->ParameterArray['ReservedColor']};}
				.freespace{background-color: {$config->ParameterArray['FreeSpaceColor']};}\n";
		}

		$body.="<div class=\"cabinet\">
	<table>
		<tr><th colspan=2 $cab_color ><a href=\"cabnavigator.php?cabinetid=$cab->CabinetID\">".__("Cabinet")." $cab->Location</a></th></tr>
		<tr><td>".__("Pos")."</td><td>".__("Device")."</td></tr>\n";

		$heighterr="";
		$ownership_unassigned = false;
		$template_unassigned = false;
		while(list($devID,$device)=each($devList)){
			$devTop=$device->Position + $device->Height - 1;
			
			$templ->TemplateID=$device->TemplateID;
			$templ->GetTemplateByID($facDB);

			$tempDept->DeptID=$device->Owner;
			$tempDept->GetDeptByID();

			// If a dept has been changed from white then it needs to be added to the stylesheet, legend, and device
			if(!$device->Reservation && strtoupper($tempDept->DeptColor)!="#FFFFFF"){
				// Fill array with deptid and color so we can process the list once for the legend and style information
				$deptswithcolor[$device->Owner]["color"]=$tempDept->DeptColor;
				$deptswithcolor[$device->Owner]["name"]=$tempDept->Name;
			}

			$highlight="<blink><font color=red>";
			if($device->TemplateID==0){$highlight.="(T)";
					  $template_unassigned = true;}
			if($device->Owner==0){$highlight.="(O)";
					  $ownership_unassigned = true;}
			$highlight.= "</font></blink>";

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
					if ( in_array( $device->Owner, $viewList ) || $user->ReadAccess ) {
						$body.="<tr><td$errclass>$i</td><td class=\"device$reserved dept$device->Owner\" rowspan=$device->Height data=$devID><a href=\"devices.php?deviceid=$devID\">$highlight $device->Label</a></td></tr>\n";
					} else {
						$body.="<tr><td$errclass>$i</td><td class=\"device$reserved dept$device->Owner\" rowspan=$device->Height data=$devID>$highlight $device->Label</td></tr>\n";
					}
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

		// I don't feel like fixing the check properly to not add in a dept with id of 0 so just remove it at the last second
		// 0 is when a dept owner hasn't been assigned, just for the record
		if(isset($deptswithcolor[0])){unset($deptswithcolor[0]);}

		// We're done processing devices so build the legend and style blocks
		if(!empty($deptswithcolor)){
			foreach($deptswithcolor as $deptid => $row){
				// If head is empty then we don't have any custom colors defined above so add a style container for these
				if($head==""){$head.="        <style type=\"text/css\">\n";}
				$head.="			.dept$deptid{background-color: {$row['color']};}\n";
			}
		}

	$body.='</table>
	</div>';


}

	$dcID=$cab->DataCenterID;
	$dc->DataCenterID=$dcID;
	$dc->GetDataCenterbyID($facDB);

	// If $head isn't empty then we must have added some style information so close the tag up.
	if($head!=""){
		$head.='		</style>';
	}

	$title=($cabrow->Name!='')?__("Row")." $cabrow->Name :: ".count($cabinets)." ".__("Cabinets")." :: $dc->Name":'Facilities Cabinet Maintenance';

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
			}).addClass('arrow_left border cabnavigator tooltip').attr('id','tt'+n).append('<span class="ui-icon ui-icon-refresh rotate"></span>');
			$.post('',{tooltip: $(this).attr('data')}, function(data){
				tooltip.html(data);
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
		print "	<br><br><br><a href=\"dc_stats.php?dc=$dcID\">[ ".__('Return to')." $dc->Name ]</a>";
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
	});
</script>
</body>
</html>
