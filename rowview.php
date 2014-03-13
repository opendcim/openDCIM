<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

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



// This function with no argument will build the front cabinet face. Specify
// rear and it will build the back.
function BuildCabinet($rear=false){
	// This is fucking horrible, there has to be a better way to accomplish this.
	global $cab_color, $cabinet, $device, $body, $currentHeight, $heighterr,
			$devList, $templ, $tempDept, $backside, $deptswithcolor, $tempDept,
			$totalWeight, $totalWatts, $totalMoment, $zeroheight,
			$noTemplFlag, $noOwnerFlag, $noReservationFlag;

	$currentHeight=$cabinet->CabinetHeight;

	$body.="<div class=\"cabinet\">\n\t<table>
	<tr><th id=\"cabid\" data-cabinetid=$cabinet->CabinetID colspan=2 $cab_color>".__("Cabinet")." $cabinet->Location".($rear?" (".__("Rear").")":"")."</th></tr>
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

		if ((!$device->HalfDepth || !$device->BackSide)&&!$rear || (!$device->HalfDepth || $device->BackSide)&&$rear){
			$backside=($device->HalfDepth)?true:$backside;
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
					$errclass=($i>$cabinet->CabinetHeight)?' error':'';
					if($errclass!=''){$heighterr="yup";}
					if($i==$currentHeight){
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
				$errclass=($i>$cabinet->CabinetHeight)?' error':'';
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
	$head=$legend=$zeroheight=$body=$deptcolor="";
	$deptswithcolor=array();
	$dev=new Device();
	$templ=new DeviceTemplate();
	$tempDept=new Department();
	$dc=new DataCenter();
	$cabrow=new CabRow();

	$cabrow->CabRowID=$_REQUEST['row'];
	$cabrow->GetCabRow();
	$cab->CabRowID=$cabrow->CabRowID;
	$cabinets=$cab->GetCabinetsByRow(isset($_GET["rear"])?"rear":"");
	$fe=$cabrow->GetCabRowFrontEdge();
	if (isset($_GET["rear"])){
		//opposite view
		$fe=($fe=="Right")?"Left":(($fe=="Left")?"Right":(($fe=="Top")?"Bottom":(($fe=="Bottom")?"Top":"")));
	}

	//start loop to parse all cabinets in the row
	foreach($cabinets as $index => $cabinet){
		$dev->Cabinet=$cabinet->CabinetID;
		$devList=$dev->ViewDevicesByCabinet();

		$currentHeight=$cabinet->CabinetHeight;

		$cab_color=get_cabinet_owner_color($cabinet, $deptswithcolor);

		if($config->ParameterArray["ReservedColor"] != "#FFFFFF" || $config->ParameterArray["FreeSpaceColor"] != "#FFFFFF"){
			$head.="		<style type=\"text/css\">
			.reserved{background-color: {$config->ParameterArray['ReservedColor']};}
			.freespace{background-color: {$config->ParameterArray['FreeSpaceColor']};}\n";
		}

		buildcabinet(($fe==$cabinet->FrontEdge)?"":"rear");
	}

	$dcID=$cabinets[0]->DataCenterID;
	$dc->DataCenterID=$dcID;
	$dc->GetDataCenterbyID();

	// If $head isn't empty then we must have added some style information so close the tag up.
	if($head!=""){
		$head.='		</style>';
	}

	$title=($cabrow->Name!='')?__("Row")." $cabrow->Name".(isset($_GET["rear"])?"(".__("Rear").")":"")." :: ".count($cabinets)." ".__("Cabinets")." :: $dc->Name":__('Facilities Cabinet Maintenance');

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
		function FlipItGood(){
			var container=$("#centeriehack");
			container.children().each(function(i,cab){container.prepend(cab)});
			resize();
		}
		$("<button>",{id: "reverse", type: "button"}).text("'.(isset($_GET["rear"])?__("Front View"):__("Rear View")).'").click(function(){
			document.location.href="rowview.php?row=',$cabrow->CabRowID.(isset($_GET["rear"])?"":"&rear"),'";
		}).prependTo($(".main"));
';
if($config->ParameterArray["ToolTips"]=='enabled'){
?>
		$('.cabinet td:has(a):not(:has(img)), #zerou div > a, .cabinet .picture a img, .cabinet .picture a div').mouseenter(function(){
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
		print "	<br><br><br><a href=\"dc_stats.php?dc=$dcID\">[ ".__('Return to')." $dc->Name ]</a>";
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
					resize();
				},500);
			}else{
				expandToItem('datacenters','cab<?php echo $cabinet->CabinetID;?>');
				resize();
			}
		}
		opentree();
	});
</script>
</body>
</html>
