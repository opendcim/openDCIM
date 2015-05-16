<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$subheader=__("Data Center Cabinet Inventory");

	if((isset($_REQUEST["cabinetid"]) && (intval($_REQUEST["cabinetid"])==0)) || !isset($_REQUEST["cabinetid"])){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}


/**
 * Determines ownership of the cabinet and returns the CSS class in case a
 * color unequal white is assigned to the owner.
 *
 * @param Cabinet $cabinet
 * @param array $deptswithcolor
 * @return (string|array)[] CSS class or empty string
 */
function getColorofCabinetOwner($cabinet, $deptswithcolor)
{
	$cab_color = '';
	if ($cabinet->AssignedTo != 0) {
		$tempDept = new Department();
		$tempDept->DeptID = $cabinet->AssignedTo;
		$deptid = $tempDept->DeptID;
		if ($tempDept->GetDeptByID()) {
			if (strtoupper($tempDept->DeptColor) != '#FFFFFF') {
				$deptswithcolor[$cabinet->AssignedTo]['color'] =
				    $tempDept->DeptColor;
				$deptswithcolor[$cabinet->AssignedTo]['name'] = $tempDept->Name;
				$cab_color = "class=\"dept$deptid\"";
			}
		}
  	}
	return array($cab_color, $deptswithcolor);
}

/**
 * Merge the tags into one HTML string
 *
 * @param Device|Cabinet $dev
 * @return string
 *      a string of tag names where the tag names are embedded in span tags
 */
function renderTagsToString($obj)
{
    $tagsString = '';
    foreach ($obj->GetTags() as $tag)
    {
        $tagsString .= '<span class="text-label">' . $tag . '</span>';
    }
    return $tagsString;
}

/**
 * Render cabinet properties into this view.
 *
 * The cabinet properties zone, row, model, maximum weight and installation date
 * are rendered to be for this page. It checks if the user is allowed to see the
 * content of the cabinet and only if the user does the information is provided.
 *
 * @param Cabinet $cab
 */
function renderCabinetProps($cab){
	$tmpDC=new DataCenter();
	$tmpDC->DataCenterID=$cab->DataCenterID;
	$tmpDC->GetDataCenter();

	$renderedHTML="\t\t<table id=\"cabprop\">
	\t\t<tr><td>".__("Model").":</td><td>$cab->Model</td></tr>
	\t\t<tr><td>".__("Data Center").":</td><td>$tmpDC->Name</td></tr>
	\t\t<tr><td>".__("Install Date").":</td><td>$cab->InstallationDate</td></tr>\n";

	if($cab->ZoneID){
		$zone=new Zone();
		$zone->ZoneID=$cab->ZoneID;
		$zone->GetZone();
		$renderedHTML.="\t\t\t<tr><td>".__("Zone").":</td><td>$zone->Description</td></tr>\n";
	}
	if($cab->CabRowID){
		$cabrow=new CabRow();
		$cabrow->CabRowID=$cab->CabRowID;
		$cabrow->GetCabRow();
		$renderedHTML.="\t\t\t<tr><td>".__("Row").":</td><td>$cabrow->Name</td></tr>\n";
	}
	$renderedHTML.="\t\t\t<tr><td>".__("Tags").":</td><td>".renderTagsToString($cab)."</td></tr>\n";

	//   This is out of context here and makes the information confusing.
	//    $renderedHTML .= '			<tr><td class="left">' . __('Front Edge') . ':</td>';
	//    $renderedHTML .= "<td class=\"right\">$cab->FrontEdge </td></tr>\n";
	$renderedHTML.="\t\t</table>\n";

	return $renderedHTML;
}

/**
 * Render the indicator that a device has no ownership or template assigned.
 *
 * @param boolean $noTemplFlag flag indicating no template is assigned to device
 * @param boolean $noOwnerFlag flag indicating no ownership is assigned to device
 * @param Device $device
 * @return (boolean|boolean|string)[] CSS class or empty stringtype
 */
function renderUnassignedTemplateOwnership($noTemplFlag, $noOwnerFlag, $device) {
	$retstr=$noTemplate=$noOwnership='';
	if ($device->TemplateID == 0) {
		$noTemplate = '(T)';
		$noTemplFlag = true;
	}
	if ($device->Owner == 0) {
		$noOwnership = '(O)';
		$noOwnerFlag = true;
	}
	if ($noTemplFlag or $noOwnerFlag) {
		$retstr = '<span class="hlight">' . $noTemplate . $noOwnership . '</span>';
	}
	return array($noTemplFlag, $noOwnerFlag, $retstr);
}

	$cab=new Cabinet();
	$cab->CabinetID=$_REQUEST["cabinetid"];
	$cab->GetCabinet();

	// Check to see if this user is allowed to see anything in ihere
	if(! $user->SiteAdmin && ! $user->ReadAccess && $cab->AssignedTo >0 && !array_intersect($user->isMemberOf(),Cabinet::GetOccupants($cab->CabinetID))){
		// This cabinet belongs to a department you don't have affiliation with, so no viewing at all
		header('Location: '.redirect());
		exit;
	}

	// If you're deleting the cabinet, no need to pull in the rest of the information, so get it out of the way
	// Only a site administrator can create or delete a cabinet
	if(isset($_POST["delete"]) && $_POST["delete"]=="yes" && $user->SiteAdmin ) {
		$cab->DeleteCabinet();
		$url=redirect("dc_stats.php?dc=$dcID");
		header("Location: $url");
		exit;
	}

	$head=$legend=$zeroheight=$body=$deptcolor="";

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


	$pdu->CabinetID=$cab->CabinetID;
	$PDUList=$pdu->GetPDUbyCabinet();

	$dev->Cabinet=$cab->CabinetID;
	$devList=$dev->ViewDevicesByCabinet();

	$totalWatts=0;
	$totalWeight=0;
	$totalMoment=0;

	$deptswithcolor=array();
	list($cab_color, $deptswithcolor) = getColorofCabinetOwner($cab, $deptswithcolor);

	if($config->ParameterArray["ReservedColor"] != "#FFFFFF" || $config->ParameterArray["FreeSpaceColor"] != "#FFFFFF"){
		$head .= "		<style type=\"text/css\">
			.reserved {background-color: {$config->ParameterArray['ReservedColor']} !important;}
			.freespace {background-color: {$config->ParameterArray['FreeSpaceColor']};}\n";

		if($config->ParameterArray["FreeSpaceColor"] != "#FFFFFF"){
			$legend.='<div class="legenditem"><span class="freespace colorbox border"></span> - '.__("Free Space").'</div>'."\n";
		}
	}

	$noOwnerFlag=false;
	$noTemplFlag=false;
	$noReservationFlag=false;
	$backside=false;

	// This function with no argument will build the front cabinet face. Specify
	// rear and it will build the back.
	function BuildCabinet($rear=false){
		// This is fucking horrible, there has to be a better way to accomplish this.
		global $cab_color, $cab, $device, $body, $currentHeight, $heighterr,
				$devList, $templ, $tempDept, $backside, $deptswithcolor, $tempDept,
				$totalWeight, $totalWatts, $totalMoment, $zeroheight,
				$noTemplFlag, $noOwnerFlag, $noReservationFlag;

		$currentHeight=$cab->CabinetHeight;

		$body.="<div class=\"cabinet\">\n\t<table>
		<tr><th id=\"cabid\" data-cabinetid=$cab->CabinetID colspan=2 $cab_color>".__("Cabinet")." $cab->Location".($rear?" (".__("Rear").")":"")."</th></tr>
		<tr><td class=\"cabpos\">".__("Pos")."</td><td>".__("Device")."</td></tr>\n";

		$heighterr="";
		while(list($dev_index,$device)=each($devList)){
            list($noTemplFlag, $noOwnerFlag, $highlight) =
                renderUnassignedTemplateOwnership($noTemplFlag, $noOwnerFlag, $device);
			if($device->Height<1 && !$rear){
				if($device->Rights!="None"){
					$zeroheight.="\t\t\t<a href=\"devices.php?deviceid=$device->DeviceID\" data-deviceid=$device->DeviceID>$highlight $device->Label</a>\n";
				}else{
					// empty html anchor for a line break
					$zeroheight.="\t\t\t$highlight $device->Label\n<a></a>";
				}
			}

			// This entire function needs to be reworked.
			if($device->DeviceType=='Chassis' && $device->RearChassisSlots>0){
				$backside=true;
			}

			if ((!$device->HalfDepth || !$device->BackSide)&&!$rear || (!$device->HalfDepth || $device->BackSide)&&$rear){
				$backside=($device->HalfDepth || $device->BackSide)?true:$backside;
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

				//only computes this device if it is its front side
				if (!$device->BackSide && !$rear || $device->BackSide && $rear){
					$totalWatts+=$device->GetDeviceTotalPower();
					$DeviceTotalWeight=$device->GetDeviceTotalWeight();
					$totalWeight+=$DeviceTotalWeight;
					$totalMoment+=($DeviceTotalWeight*($device->Position+($device->Height/2)));
				}

				$reserved="";
				if($device->Reservation==true){
					$reserved=" reserved";
					$noReservationFlag=true;
				}
				if($devTop<$currentHeight && $currentHeight>0){
					for($i=$currentHeight;($i>$devTop);$i--){
						$errclass=($i>$cab->CabinetHeight)?' error':'';
						if($errclass!=''){$heighterr="yup";}
						if($i==$currentHeight && $i>1){
							$blankHeight=$currentHeight-$devTop;
							if($devTop==-1){--$blankHeight;}
							$body.="\t\t<tr><td class=\"cabpos freespace$errclass\">$i</td><td class=\"freespace\" rowspan=$blankHeight>&nbsp;</td></tr>\n";
						} else {
							$body.="\t\t<tr><td class=\"cabpos freespace$errclass\">$i</td></tr>\n";
							if($i==1){break;}
						}
					}
				}
				for($i=$devTop;$i>=$device->Position;$i--){
					$errclass=($i>$cab->CabinetHeight)?' error':'';
					if($errclass!=''){$heighterr="yup";}
					if($i==$devTop){
						// Create the filler for the rack either text or a picture
						$picture=(!$device->BackSide && !$rear || $device->BackSide && $rear)?$device->GetDevicePicture():$device->GetDevicePicture("rear");
						$devlabel=$device->Label.(((!$device->BackSide && $rear || $device->BackSide && !$rear) && !$device->HalfDepth)?"(".__("Rear").")":"");
						$text=($device->Rights!="None")?"<a href=\"devices.php?deviceid=$device->DeviceID\">$highlight $devlabel</a>":$devlabel;
						
						// Put the device in the rack
						$body.="\t\t<tr><td class=\"cabpos$reserved dept$device->Owner$errclass\">$i</td><td class=\"dept$device->Owner$reserved\" rowspan=$device->Height data-deviceid=$device->DeviceID>";
						$body.=($picture)?$picture:$text;
						$body.="</td></tr>\n";
					}else{
						$body.="\t\t<tr><td class=\"cabpos$reserved dept$device->Owner$errclass\">$i</td></tr>\n";
					}
				}
				$currentHeight=$device->Position - 1;
			}elseif(!$rear){
				$backside=true;
			}
		}

		// Fill in to the bottom
		for($i=$currentHeight;$i>0;$i--){
			if($i==$currentHeight){
				$blankHeight=$currentHeight;

				$body.="\t\t<tr><td class=\"cabpos freespace\">$i</td><td class=\"freespace\" rowspan=$blankHeight>&nbsp;</td></tr>\n";
			}else{
				$body.="\t\t<tr><td class=\"cabpos freespace\">$i</td></tr>\n";
			}
		}
		$body.="\t</table>\n</div>\n";
		reset($devList);
	}  //END OF BuildCabinet

	// Generate rack view
	BuildCabinet();
	// Generate rear rack view if needed
	($backside)?BuildCabinet('rear'):'';

	if($heighterr!=''){
        $legend.='<div class="legenditem"><span style="background-color:'
            . $config->ParameterArray['CriticalColor'] . '; text-align:center" class="error colorbox border">*</span> - '
            . __("Above defined rack height").'</div>'."\n";
    }

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
    if (!empty($deptswithcolor)) {
        foreach ($deptswithcolor as $deptid => $row) {
            // If head is empty then we don't have any custom colors defined above so add a style container for these
            if($head==""){
                $head.="\t\t<style type=\"text/css\">\n";
            }
            $head.="\t\t\t.dept$deptid {background-color: {$row['color']};}\n";
            $legend.="\t\t<div class=\"legenditem\"><span class=\"border colorbox dept$deptid\"></span> - <span>{$row['name']}</span></div>\n";
        }
    }

	// This will add an item to the legend for a white box. If we ever get a good name for it.
	if($legend!=""){
	//	$legend.='<div class="legenditem"><span class="colorbox border"></span> - Custom Color Not Assigned</div>';
	}

	// add legend for the flags which actually are used in the cabinet
	$legend=($noOwnerFlag)?"\t\t<div class=\"legenditem\"><span class=\"hlight\">(O)</span> - ".__("Owner Unassigned")."</div>\n".$legend:$legend;
	$legend=($noTemplFlag)?"\t\t<div class=\"legenditem\"><span class=\"hlight\">(T)</span> - ".__("Template Unassigned")."</div>\n".$legend:$legend;

	// Only show reserved in the legend if a device is set to reserved AND the color is something other than white
	if($config->ParameterArray["ReservedColor"] != "#FFFFFF" && $noReservationFlag){
		$legend.="\t\t<div class=\"legenditem\"><span class=\"reserved colorbox border\"></span> - ".__("Reservation")."</div>\n";
	}

$body.='<div id="infopanel">
	<fieldset id="legend">
		<legend>'.__("Markup Key")."</legend>\n"
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
		</tr>'.
//		<tr>
//			<td>'.__("Weight").' ['.$cab->MaxWeight.']
//				<div class="meter-wrap">
//					<div class="meter-value" style="background-color: '.$WeightColor.'; width: '.$WeightPercent.'%;">
//						<div class="meter-text">'.$WeightPercent.'%</div>
//					</div>
//				</div>
//			</td>
//		</tr>
		'<tr>
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
		</table>'.
		//<p>'.__("Approximate Center of Gravity").': '.$CenterofGravity.' U</p>
	'</fieldset>
	<fieldset id="keylock">
		<legend>'.__("Key/Lock Information").'</legend>
		<div>
			'.$cab->Keylock.'
		</div>
	</fieldset>
';

	if($zeroheight!=""){
		$body.='	<fieldset id="zerou">
		<legend>'.__("Zero-U Devices").'</legend>
		<div>
'.$zeroheight.'
		</div>
	</fieldset>
';
	}
	$body.='	<fieldset name="pdu">
		<legend>'.__("Power Distribution").'</legend>';

	foreach($PDUList as $PDUdev){
		$lastreading=$PDUdev->GetLastReading();
		$pduDraw=($lastreading)?$lastreading->Wattage:0;

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
			$PDUPercent=intval($pduDraw/$maxDraw*100);
		}else{
			$PDUPercent=0;
		}

		$PDUColor=($PDUPercent>intval($config->ParameterArray["PowerRed"])?$CriticalColor:($PDUPercent>intval($config->ParameterArray["PowerYellow"])?$CautionColor:$GoodColor));

		$body.=sprintf("\n\t\t\t<a href=\"power_pdu.php?pduid=%d\">CDU %s</a><br>(%.2f kW) / (%.2f kW Max)<br>\n", $PDUdev->PDUID, $PDUdev->Label, $pduDraw / 1000, $maxDraw / 1000 );
		$body.="\t\t\t\t<div class=\"meter-wrap\">\n\t\t\t\t\t<div class=\"meter-value\" style=\"background-color: $PDUColor; width: $PDUPercent%;\">\n\t\t\t\t\t\t<div class=\"meter-text\">$PDUPercent%</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t<br>\n";

		if ( $PDUdev->FailSafe ) {
			$tmpl = new CDUTemplate();
			$tmpl->TemplateID = $PDUdev->TemplateID;
			$tmpl->GetTemplate();

			$ATSStatus = $PDUdev->getATSStatus();

			if ( $ATSStatus == "" ) {
				$ATSColor = "rs.png";
				$ATSStatus = __("Unknown Status");
			} elseif ( $ATSStatus == $tmpl->ATSDesiredResult ) {
				$ATSColor = "gs.png";
				$ATSStatus = __("ATS Feeds OK");
			} else {
				$ATSColor = "ys.png";
				$ATSStatus = __("ATS Feeds Abnormal");
			}

			$body.="<div><img src=\"images/$ATSColor\">$ATSStatus</div>\n";
		}
	}

	if($user->CanWrite($cab->AssignedTo)){
		$body.="\n\t\t<ul class=\"nav\"><a href=\"power_pdu.php?pduid=0&cabinetid=$cab->CabinetID\"><li>".__("Add CDU")."</li></a></ul>\n";
	}

	$body.="\t</fieldset>\n";
	if ($user->CanWrite($cab->AssignedTo) || $user->SiteAdmin) {
	    $body.="\t<fieldset>\n";
        if ($user->CanWrite($cab->AssignedTo) ) {
            $body .= renderCabinetProps($cab);
        }
	    $body.="\t\t<ul class=\"nav\">";
        if($user->CanWrite($cab->AssignedTo)){
            $body.="
			<a href=\"devices.php?action=new&cabinet=$cab->CabinetID\"><li>".__("Add Device")."</li></a>
			<a href=\"mapmaker.php?cabinetid=$cab->CabinetID\"><li>".__("Map Coordinates")."</li></a>
			<a href=\"cabinets.php?cabinetid=$cab->CabinetID\"><li>".__("Edit Cabinet")."</li></a>\n";
        }
		if($user->SiteAdmin){
		    $body.="\t\t\t<a href=\"#\" onclick=\"javascript:verifyDelete(this.form)\"><li>".__("Delete Cabinet")."</li></a>";
		}
	    $body.="\n\t\t</ul>\n    </fieldset>";
	}
	$body.='
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
  <script type="text/javascript" src="scripts/jquery.cookie.js"></script>
  <script type="text/javascript" src="scripts/jquery-json.min.js"></script>
  <script type="text/javascript" src="scripts/common.js"></script>
  <script type="text/javascript">
	var form=$("<form>").attr({ method: "post", action: "cabnavigator.php" });
	$("<input>").attr({ type: "hidden", name: "cabinetid", value: "',$cab->CabinetID,'"}).appendTo(form);


	function verifyDelete(formname){
		if(confirm("',__("Are you sure that you want to delete this cabinet, including all devices, power strips, and connections?"),'\n',__("THIS ACTION CAN NOT BE UNDONE!"),'")){
			$("<input>").attr({ type: "hidden", name: "delete", value: "yes"}).appendTo(form);
			form.appendTo("body");
			form.submit();
		}
	}
	
	(function ($) {
		$.fn.extend({
			cookieList: function (cookieName, expireTime) {
				var cookie = $.cookie(cookieName);
				var items = cookie ? $.secureEvalJSON(cookie) : [];
				
				return {
					add: function (val) {
						var index = items.indexOf(val);
						if ( index == -1) {
							items.push(val);
							$.cookie(cookieName, $.toJSON(items), {expires: expireTime });
						}
					},
					remove: function (val) {
						var index = items.indexOf(val);
						if ( index != -1 ) {
							items.splice(index, 1);
							$.cookie(cookieName, $.toJSON(items), {expires: expireTime });
						}
					},
					indexOf: function(val) {
						return items.indexOf(val);
					},
					clear: function() {
						items = null;
						$.cookie(cookieName, null, { expires: expireTime });
					},
					items: function() {
						return items;
					},
					length: function() {
						return items.length;
					},
					join: function( separator ) {
						return items.join(separator);
					}
				};
			}
		});
	})(jQuery);
	
	$(document).ready(function() {
		$(".cabinet .error").append("*");
		if($("#legend *").length==1){$("#legend").hide();}
		if($("#keylock div").text().trim()==""){$("#keylock").hide();}
';
if( $config->ParameterArray["ToolTips"]=='enabled' ){
?>
		$('.cabinet td:has(a):not(:has(img)), #zerou div > a, .cabinet .picture a img, .cabinet .picture a > div').mouseenter(function(){
			var lblbtn=$('.cabinet tr:first-child button + button');
			var srctest=(typeof $(this).attr('src')==="undefined")?'css/blank.gif':$(this).attr('src');
			if(srctest!='css/blank.gif'){
				$(this).parents('div').children('div.label').hide();
			}
			var pos=$(this).offset();
			var tooltip=$('<div />').css({
				'left':pos.left+this.getBoundingClientRect().width+15+'px',
				'top':pos.top+(this.getBoundingClientRect().height/2)-15+'px'
			}).addClass('arrow_left border cabnavigator tooltip').attr('id','tt').append('<span class="ui-icon ui-icon-refresh rotate"></span>');
			$.post('scripts/ajax_tooltip.php',{tooltip: $(this).data('deviceid'), dev: 1}, function(data){
				tooltip.html(data);
			});
			$('body').append(tooltip);
			$(this).mouseleave(function(){
				if(!lblbtn.data('show')){
					$(this).parents('div').children('div.label').show();
				}
				tooltip.remove();
			});
		});

<?php
}

if ( $config->ParameterArray["WorkOrderBuilder"]=='enabled' ) {
?>
		var workOrder = $.fn.cookieList("workOrder");

		// This is a shitty hack and we should do something better
		if(!$.cookie('workOrder')){
			workOrder.add(0);
		}

		$('.cabinet td:has(a):not(:has(img)), #zerou div > a, .cabinet .picture a img, .cabinet	.picture a > div').each( function(){
			var devid=$(this).data('deviceid');
			var target=(this.nodeName=="IMG")?this.parentElement.parentElement:this;
			var clickpos=(this.parentNode.parentNode.className=="rotar_d")?' left: 0;':' right: 0;';
			var style=(this.nodeName=="IMG")?'position: absolute; top: 0; background-color: white;'+clickpos:'float: right;';

			// Make a point for us to click to add to this nonsense
			var span=$('<span>').attr('style',style).addClass('ui-icon');
			span.addClass(($.parseJSON($.cookie('workOrder')).indexOf(devid)==-1)?'ui-icon-circle-plus':'ui-icon-circle-check');

			// Bind the click action
			span.on('click', function(){
				location.reload();
				sneaky.sneak();
				flippyfloppy();
			});

			// Set the sign on the element according to if it is in the array or not
			function flippyfloppy(){
				if($.parseJSON($.cookie('workOrder')).indexOf(devid)==-1){
					workOrder.add(devid);
					span.removeClass('ui-icon-circle-plus').addClass('ui-icon-circle-plus');
				}else{
					workOrder.remove(devid);
					span.addClass('ui-icon-circle-plus').removeClass('ui-icon-circle-check');
				}
			}
			// Add the click target to the page
			$(target).append(span);
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
			$.post('scripts/ajax_tooltip.php',{tooltip: id, cdu: 1}, function(data){
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
  <style type="text/css">
	@page{size: <?php echo $config->ParameterArray['PageSize']; ?> portrait}
  </style>
</head>

<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main cabnavigator">
<div class="center"><div>
<div id="centeriehack">
<?php
	echo $body;
?>
</div> <!-- END div#centeriehack -->
</div></div>
<?php
	if($dcID>0){
		print "	<a href=\"dc_stats.php?dc=$dcID\">[ ".__("Return to")." $dc->Name ]</a>";
	}
?>
</div>  <!-- END div.main -->

<div class="clear"></div>
</div>
<script type="text/javascript">
	$(document).ready(function() {
		// Move the cabinet labels around
		$('.cabnavigator div.picture > div.label > div').each(function(){
			var offset=this.getBoundingClientRect().height;
			var container=$(this).parents('.picture')[0].getBoundingClientRect().height;
			$(this).parent('.label').css('top', (container-offset)/2/2);
		});

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
		// Add controls to the rack
		cabinetimagecontrols();
	});
</script>
</body>
</html>
