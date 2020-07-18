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
 * @param CabinetAudit $audit
 * @param string $AuditorName
 */
function renderCabinetProps($cab, $audit, $AuditorName){
	$tmpDC=new DataCenter();
	$tmpDC->DataCenterID=$cab->DataCenterID;
	$tmpDC->GetDataCenter();
	$AuditorName=($AuditorName!='')?"<br>$AuditorName":"";

	$renderedHTML="\t\t<table id=\"cabprop\">
	\t\t<tr><td>".__("Last Audit").":</td><td id=\"lastaudit\">$audit->AuditStamp$AuditorName</td></tr>
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
	if(!$person->SiteAdmin && !$person->ReadAccess && $cab->Rights=='None' && !array_intersect($person->isMemberOf(),Cabinet::GetOccupants($cab->CabinetID))){
		// This cabinet belongs to a department you don't have affiliation with, so no viewing at all
		header('Location: '.redirect());
		exit;
	}

	// If you're deleting the cabinet, no need to pull in the rest of the information, so get it out of the way
	// Only a site administrator can create or delete a cabinet
	if(isset($_POST["delete"]) && $_POST["delete"]=="yes" && $person->SiteAdmin ) {
		$cab->DeleteCabinet();
		header('Content-Type: application/json');
		echo json_encode(array('url' => redirect("dc_stats.php?dc=$cab->DataCenterID")));
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
	if(isset($_POST["audit"]) && $_POST["audit"]=="yes" && $person->CanWrite($cab->AssignedTo)){
		$audit->Comments=sanitize($_POST["comments"]);
		// Log the response
		$audit->CertifyAudit();

		// I'm lazy as fuck so retrieve what we just wrong
		$audit->GetLastAudit();

		$tmpUser=new People();
		$tmpUser->UserID=$audit->UserID;
		$tmpUser->GetUserRights();
		$audit->UserID=($tmpUser->FirstName=="" && $tmpUser->LastName=="")?$audit->UserID:$tmpUser->FirstName." ".$tmpUser->LastName;
		// Give it back to the user to update the page
		header('Content-Type: application/json');
		echo json_encode($audit);
		exit;
	}

	$audit->AuditStamp=__("Never");
	$audit->GetLastAudit();
	if($audit->UserID!=""){
		$tmpUser=new People();
		$tmpUser->UserID=$audit->UserID;
		$tmpUser->GetUserRights();
		$AuditorName=$tmpUser->FirstName." ".$tmpUser->LastName;
	}

	$pdu->CabinetID=$cab->CabinetID;
	$PDUList=$pdu->GetPDUbyCabinet();

	$dev->Cabinet=$cab->CabinetID;
	$devList=$dev->ViewDevicesByCabinet();

	$search=new Device();
	$search->Cabinet=$cab->CabinetID;
	$search->DeviceType="Sensor";
	$SensorList=$search->Search();

	$stats=$cab->getStats($cab->CabinetID);

	$totalWatts=$stats->Wattage;
	$totalWeight=$stats->Weight;

	$legend.='<div class="legenditem hide"><span style="background-color:'.$config->ParameterArray['CriticalColor'].'; text-align:center" class="error colorbox border">*</span> - '.__("Above defined rack height").'</div>'."\n";

	// Set up the classes for color coding based upon status
	$dsList=DeviceStatus::getStatusList();

	$head.="        <style type=\"text/css\">
			.freespace {background-color: {$config->ParameterArray['FreeSpaceColor']};}\n";

	if($config->ParameterArray["FreeSpaceColor"] != "#FFFFFF"){
		$legend.='<div class="legenditem"><span class="freespace colorbox border"></span> - '.__("Free Space").'</div>'."\n";
	}

	foreach($dsList as $stat){
		if($stat->ColorCode != "#FFFFFF"){
			$stName=str_replace(' ','_',$stat->Status);

			$head.="\t\t\t.$stName {background-color: {$stat->ColorCode} !important;}\n";
			$legend.="\t\t<div class=\"legenditem hide\"><span class=\"$stName colorbox border\"></span> - $stat->Status</div>\n";
		}
	}

	$noOwnerFlag=false;
	$noTemplFlag=false;
	$noReservationFlag=false;
	$backside=true;

	// This function with no argument will build the front cabinet face. Specify
	// rear and it will build the back.

	// Generate rack view
	$body.=BuildCabinet($cab->CabinetID);
	// Generate rear rack view if needed
	$body.=($backside)?BuildCabinet($cab->CabinetID,'rear'):'';

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

	if($cab->MaxWeight==0){
		$cab->MaxWeight=$WeightPercent=__("Maximum Weight Not Set");
		$WeightColor=$CriticalColor;
	}

	foreach(Department::GetDepartmentListIndexedbyID() as $deptid => $d){
        // Add a style container for these
        $head=($head=="")?"\t\t<style type=\"text/css\">\n":$head;
		$head.="\t\t\t.dept$deptid {background-color:$d->DeptColor;}\n";
        $legend.="\t\t<div class=\"legenditem hide\"><span class=\"border colorbox dept$deptid\"></span> - <span>$d->Name</span></div>\n";
	}

	// add legend for the flags which actually are used in the cabinet
	$legend.="\t\t<div class=\"legenditem hide\"><span class=\"hlight owner\">(O)</span> - ".__("Owner Unassigned")."</div>\n";
	$legend.="\t\t<div class=\"legenditem hide\"><span class=\"hlight template\">(T)</span> - ".__("Template Unassigned")."</div>\n";

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
						<div class="meter-text">'; $body.=sprintf("%.2f kW / %d kW",$totalWatts/1000,$cab->MaxKW);$body.='</div>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<td>'.__("Measured Watts").'
				<div class="meter-wrap">
					<div class="meter-value" style="background-color: '.$MeasuredColor.'; width: '.$MeasuredPercent.'%;">
						<div class="meter-text">'; $body.=sprintf("%.2f kW / %d kW",$measuredWatts/1000,$cab->MaxKW);$body.='</div>
					</div>
				</div>
			</td>
		</tr>
		</table>
		<p>'.__("Approximate Center of Gravity").': <span id="tippingpoint"></span></p>
	</fieldset>
	<fieldset id="keylock">
		<legend>'.__("Key/Lock Information").'</legend>
		<div>
			'.$cab->Keylock.'
		</div>
	</fieldset>
	<fieldset id="zerou" class="hide">
		<legend>'.__("Zero-U Devices").'</legend>
		<div>
		</div>
	</fieldset>
	<fieldset name="pdu">
		<legend>'.__("Power Distribution").'</legend>
		<div>';

	foreach($PDUList as $PDUdev){
		$lastreading=$PDUdev->GetLastReading();
		$pduDraw=($lastreading)?$lastreading->Wattage:0;

		$pan->PanelID=$PDUdev->PanelID;
		$pan->getPanel();

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

		$body.=sprintf("\n\t\t\t<a href=\"devices.php?DeviceID=%d\">CDU %s</a><br>(%.2f kW) / (%.2f kW Max)<br>\n", $PDUdev->PDUID, $PDUdev->Label, $pduDraw / 1000, $maxDraw / 1000 );
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

	if($person->CanWrite($cab->AssignedTo)){
		$body.="\n\t\t<ul class=\"nav\"><a href=\"devices.php?action=new&CabinetID=$cab->CabinetID&DeviceType=CDU\"><li>".__("Add CDU")."</li></a></ul>\n";
	}

	$body.="\t\t</div>\n\t</fieldset>\n";

	$body.='	<fieldset id="sensors">
		<legend>'.__("Environmental Sensors").'</legend>
		<div>';

	foreach($SensorList as $Sensor){
		$body.="\t\t<a href=\"devices.php?DeviceID=$Sensor->DeviceID\">$Sensor->Label</a><br>\n";
	}

	if($person->CanWrite($cab->AssignedTo)){
		$body.="\n\t\t<ul class=\"nav\"><a href=\"devices.php?action=new&CabinetID=$cab->CabinetID&DeviceType=Sensor\"><li>".__("Add Sensor")."</li></a></ul>\n";
	}

	$body.="\t\t</div>\n\t</fieldset>\n";


	if ($person->CanWrite($cab->AssignedTo) || $person->SiteAdmin) {
	    $body.="\t<fieldset>\n";
        if ($person->CanWrite($cab->AssignedTo) ) {
            $body .= renderCabinetProps($cab, $audit, $AuditorName);
        }
	    $body.="\t\t<ul class=\"nav\">";
        if($person->CanWrite($cab->AssignedTo)){
            $body.="
			<a href=\"#\" id=\"verifyaudit\"><li>".__("Certify Audit")."</li></a>
			<a href=\"devices.php?action=new&CabinetID=$cab->CabinetID\"><li>".__("Add Device")."</li></a>
			<a href=\"cabaudit.php?cabinetid=$cab->CabinetID\"><li>".__("Audit Report")."</li></a>
			<a href=\"mapmaker.php?cabinetid=$cab->CabinetID\"><li>".__("Map Coordinates")."</li></a>
			<a href=\"cabinets.php?cabinetid=$cab->CabinetID\"><li>".__("Edit Cabinet")."</li></a>\n";
        }
		if($person->SiteAdmin){
		    $body.="\t\t\t<a href=\"#\" id=\"verifydelete\"><li>".__("Delete Cabinet")."</li></a>";
		}
	    $body.="\n\t\t</ul>\n    </fieldset>";
	}
	$body.='
	<fieldset id="cabnotes">
		<legend>'.__("Cabinet Notes").'</legend>
		<div>
			'.$cab->Notes.'
		</div>
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
  <script type="text/javascript" src="scripts/jquery.cookie.js"></script>
  <script type="text/javascript" src="scripts/jquery-json.min.js"></script>
  <script type="text/javascript" src="scripts/common.js?v',filemtime('scripts/common.js'),'"></script>
  <script type="text/javascript" src="scripts/masonry.pkgd.min.js"></script>
  <script type="text/javascript">
	window.weight=',$totalWeight,';
	var form=$("<form>").attr({ method: "post", action: "cabnavigator.php" });
	$("<input>").attr({ type: "hidden", name: "cabinetid", value: "',$cab->CabinetID,'"}).appendTo(form);

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
		if($("#cabnotes div").text().trim()==""){$("#cabnotes").hide();}
		if($("#sensors div").text().trim()==""){$("#sensors").hide();}
		if($("fieldset[name=pdu] div").text().trim()==""){$("fieldset[name=pdu]").hide();}

		$("#verifyaudit").click(function(e){
			e.preventDefault();
			$("#auditmodal").dialog({
				width: 600,
				modal: true,
				buttons: {
					Yes: function(e){
						$.post("",{audit: "yes", cabinetid: ',$cab->CabinetID,', comments: $("#comments").val()}).done(function(data){
							$("#lastaudit").html(data.AuditStamp+"<br>"+data.UserID).effect("highlight", {color: "lightgreen"}, 2500);
							$("#auditmodal").dialog("destroy");
						});
					},
					No: function(e){
						$("#auditmodal").dialog("destroy");
					}
				},
				close: function(){
					$(this).dialog("destroy");
				}
			});
		});

		$("#verifydelete").click(function(e){
			e.preventDefault();
			$("#deletemodal").dialog({
				width: 600,
				modal: true,
				draggable: false,
				buttons: {
					Yes: function(e){
						$("#doublecheck").dialog({
							width: 600,
							modal: true,
							draggable: false,
							buttons: {
								Yes: function(e){
									// they are really sure they want to delete so do it
									$.post("",{delete: "yes", cabinetid: ',$cab->CabinetID,'}).done(function(data){
										location.href=data.url;
									});
								},
								No: function(e){
									$("#doublecheck").dialog("destroy");
									$("#deletemodal").dialog("destroy");
								}
							},
							close: function(){
								$(this).dialog("destroy");
								$("#deletemodal").dialog("destroy");
							}
						});
					},
					No: function(e){
						$(this).dialog("destroy");
					}
				},
				close: function(){
					$(this).dialog("destroy");
				}
			});
		});
';
if( $config->ParameterArray["ToolTips"]=='enabled' ){
?>
		$('.cabinet div[id^="servercontainer"], #infopanel').on('mouseenter', 'div > div.genericdevice, div > div a > img, #zerou div > a', function(){
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


if($config->ParameterArray["CDUToolTips"]=='enabled'){
?>
		$('fieldset[name="pdu"] div > a').mouseenter(function(){
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
		// This is gonna confuse the fuck out of me when I see this again
		$('fieldset').wrap($('<div>').addClass('item').css('width','235px'));
		$('#cabnotes').parent('div').css('width','470px');
		$('#infopanel').css({'max-width':'480px','width':'480px'}).masonry();
		$('#infopanel').masonry('option', { columnWidth: 240, itemSelector: '.item'});
		$('#infopanel').masonry('layout');
		$('#cabnotes > div').html($('#cabnotes > div').text());

		// Add sensor data to the page
		$('#sensors a:not([href$=Sensor])').each(function(){
			var link=this;
			$.get('api/v1/device/'+link.href.split('=').pop()+'/getsensorreadings',function(data){
				if(!data.error){
					$(link).after('<br>Temp:&nbsp;'+data.sensor.Temperature+'&deg;&nbsp;&nbsp;Humidity:&nbsp;'+data.sensor.Humidity+'<br>');
				}else{
					$(link).after('<br>'+data.message+'<br>');
				}
				// When we add data to the box it grows so we need to adjust the bricks
				$('#infopanel').masonry('layout');
			});
		});
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
if($cab->ZoneID){
	$zone=new Zone();
	$zone->ZoneID=$cab->ZoneID;
	$zone->GetZone();
	echo '<a href="zone_stats.php?zone=',$zone->ZoneID,'">[ ',sprintf(__("Return to Zone %s"),$zone->Description),' ]</a><br>';
}
if ($cab->CabRowID) {
	$cabrow=new CabRow();
	$cabrow->CabRowID=$cab->CabRowID;
	$cabrow->GetCabRow();
	echo '<a href="rowview.php?row=',$cabrow->CabRowID,'">[ ',sprintf(__("Return to Row %s"),$cabrow->Name),' ]</a><br>';
}
?>

<?php echo '<a href="dc_stats.php?dc=',$dcID,'">[ ',sprintf(__("Return to %s"),$dc->Name),' ]</a>
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Audit Confirmation"),'" id="auditmodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Do you certify that you have completed an audit of this cabinet?"),'
		<br><br><br>
		<label for="comments">Comments:</label>
		<br><br>
		<textarea name="comments" id="comments" rows=8 cols=80></textarea>
		</div>
	</div>
	<div title="',__("Cabinet delete confirmation"),'" id="deletemodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this cabinet and all the devices in it?"),'
		<br><br>
		</div>
	</div>
	<div title="',__("Are you REALLY sure?"),'" id="doublecheck">
		<div id="modaltext" class="warning"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure REALLY sure?  There is no undo!!"),'
		<br><br>
		</div>
	</div>
</div>'; ?>
</div>  <!-- END div.main -->

<?php
if($person->CanWrite($cab->AssignedTo) || $person->SiteAdmin) {
    echo '<div><input type="hidden" name="cabinetdraggable" id="cabinetdraggable" value="yes"></div>';
}
?>


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
		// Damn translators not using abreviations
		// This will lock the cabinet into the correct size
		$('.cabinet #cabid').parent('tr').next('tr').find('.cabpos').css('padding','0px').wrapInner($('<div>').css({'overflow':'hidden','width':'30px'}));
	});

<?php
if ( $config->ParameterArray["WorkOrderBuilder"]=='enabled' ) {
?>

	var workOrder = $.fn.cookieList("workOrder");

	// This is a shitty hack and we should do something better
	if(!$.cookie('workOrder')){
		workOrder.add(0);
	}

	function bindworkorder(obj){
        obj.find('div.genericdevice, div a > img, .picture a > div').each(function(){
			var devid=$(this).data('deviceid');
			var target=(this.nodeName=="IMG"||this.parentNode.parentNode.parentNode.nodeName=="DIV")?this.parentElement.parentElement:this;
			var clickpos=(this.parentNode.parentNode.className=="rotar_d")?' left: 0;':' right: 0;';
			var style=(this.nodeName=="IMG")?'position: absolute; top: 0; background-color: white; z-index: 99;'+clickpos:'position: absolute; top: 2px; right: -4px; background-color: white; z-index: 99;';
			// nested children needed a slight nudge for positions
			if($('.picture').find('div[data-deviceid='+devid+']').length>0){
				style=style.replace('2px','0px').replace('-4px','2px');
			}
			// move the chassis position to the opposite side, this looks dumb
			if($(target).find('div:not(.label):not(.label > div)').length>0){
				style=style.replace('right','left');
			}

			// Make a point for us to click to add to this nonsense
			var span=$('<span>').attr('style',style).addClass('ui-icon');
			span.addClass(($.parseJSON($.cookie('workOrder')).indexOf(devid)==-1)?'ui-icon-circle-plus':'ui-icon-circle-check');

			// Bind the click action
			span.on('click', function(){
				sneaky.sneak();
				flippyfloppy();
				// reload the page to show the workorder button if it isn't showing
				if(workOrder.items().length>1){
					$('a[href="workorder.php"]').removeClass('hide');
				}else{
					$('a[href="workorder.php"]').addClass('hide');
				}
			});

			function findall(devid){
				var objects=[];
				$('*[data-deviceid='+devid+']').each(function(){
					if(this.nodeName=="IMG" || this.classList.contains('noimage')){
						objects.push($(this).parent().parent().find('> span'));
					}else{
						objects.push($(this).find('span:not(.hlight)'));
					}
				});
				return objects;
			}

			// Set the sign on the element according to if it is in the array or not
			function flippyfloppy(){
				if($.parseJSON($.cookie('workOrder')).indexOf(devid)==-1){
					workOrder.add(devid);
					var objects=findall(devid);
					for(var i in objects){
						objects[i].addClass('ui-icon-circle-check').removeClass('ui-icon-circle-plus');
					}
				}else{
					workOrder.remove(devid);
					var objects=findall(devid);
					for(var i in objects){
						objects[i].removeClass('ui-icon-circle-check').addClass('ui-icon-circle-plus');
					}
				}
			}

			// Add the click target to the page
			$(target).append(span);
		});
	}
<?php
}
?>
</script>
</body>
</html>
