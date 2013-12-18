<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	// Get the list of departments that this user is a member of
	$viewList = $user->isMemberOf();

	// Ajax
		if(isset($_POST['FlipDirection'])){
			$cabrow=New CabRow();
			$cabrow->CabRowID=$_POST['row'];
			$cabrow->GetCabRow();
			$cabrow->CabOrder=($cabrow->CabOrder=='ASC')?'DESC':'ASC';
			$cabrow->SetDirection();

			// no need to load the rest of the page
			exit;
		}
	// Ajax - END

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

		$body.="<div class=\"cabinet\">
	<table>
		<tr><th id=\"cabid$cabinet->CabinetID\" data-cabinetid=$cabinet->CabinetID colspan=2 $cab_color ><a href=\"cabnavigator.php?cabinetid=$cabinet->CabinetID\">".__("Cabinet")." $cabinet->Location</a></th></tr>
		<tr><td>".__("Pos")."</td><td>".__("Device")."</td></tr>\n";

		$heighterr="";
		$ownership_unassigned = false;
		$template_unassigned = false;
		while(list($devID,$device)=each($devList)){
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
			if($device->TemplateID==0){$highlight.="(T)";
					  $template_unassigned = true;}
			if($device->Owner==0){$highlight.="(O)";
					  $ownership_unassigned = true;}
			$highlight.= "</font></blink>";

			$reserved=($device->Reservation==false)?"":" reserved";
			if($devTop<$currentHeight && $currentHeight>0){
				for($i=$currentHeight;($i>$devTop);$i--){
					$errclass=($i>$cabinet->CabinetHeight)?' class="error"':'';
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
				$errclass=($i>$cabinet->CabinetHeight)?' class="error"':'';
				if($errclass!=''){$heighterr="yup";}
				if($i==$devTop){
					if ( in_array( $device->Owner, $viewList ) || $user->ReadAccess ) {
						$body.="<tr><td$errclass>$i</td><td class=\"device$reserved dept$device->Owner\" rowspan=$device->Height data-deviceid=$device->DeviceID><a href=\"devices.php?deviceid=$device->DeviceID\">$highlight $device->Label</a></td></tr>\n";
					} else {
						$body.="<tr><td$errclass>$i</td><td class=\"device$reserved dept$device->Owner\" rowspan=$device->Height>$highlight $device->Label</td></tr>\n";
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
	$dc->GetDataCenterbyID();

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
		function FlipItGood(){
			var container=$("#centeriehack");
			container.children().each(function(i,cab){container.prepend(cab)});
			resize();
		}
		$("<button>",{id: "reverse", type: "button"}).text("Reverse Cabinet Order").click(function(){
			$.post("",{FlipDirection: "", row: '.$cabrow->CabRowID.'}).done(FlipItGood);
		}).prependTo($(".main"));
';
if($config->ParameterArray["ToolTips"]=='enabled'){
?>
		$('.cabinet td.device:has("a")').mouseenter(function(){
			var pos=$(this).offset();
			var tooltip=$('<div />').css({
				'left':pos.left+$(this).outerWidth()+15+'px',
				'top':pos.top+($(this).outerHeight()/2)-15+'px'
			}).addClass('arrow_left border cabnavigator tooltip').append('<span class="ui-icon ui-icon-refresh rotate"></span>');
			$.post('cabnavigator.php',{tooltip: $(this).data('deviceid'), cabinetid: 1}, function(data){
				tooltip.html(data);
			});
			$('body').append(tooltip);
			$(this).mouseleave(function(){
				tooltip.remove();
			});
		});
<?php
}
if($cabrow->CabOrder=="DESC"){echo '		FlipItGood();';}
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
