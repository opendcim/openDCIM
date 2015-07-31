<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$subheader=__("Data Center Detail");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$panel=new PowerPanel();
	$pdu=new PowerDistribution();
	$mech=new MechanicalDevice();
	$cab=new Cabinet();
	$tmpl = new CDUTemplate();
	$tmpList = $tmpl->GetTemplateList();
	$script="";

	// AJAX

	if(isset($_POST['deletepanel'])){
		$panel->PanelID=$_POST["PanelID"];
		$return='no';
		if($panel->getPanel()){
			$panel->deletePanel();
			$return='ok';
		}
		echo $return;
		exit;
	}
	
	// Set a default panel voltage based upon the configuration screen
	$panel->PanelVoltage=$config->ParameterArray["DefaultPanelVoltage"];
  
	if(isset($_POST["action"])&&(($_POST["action"]=="Create")||($_POST["action"]=="Update"))){
		foreach($panel as $prop => $val){
			$panel->$prop=trim($_POST[$prop]);
		}
		
		if($_POST["action"]=="Create"){
			if($panel->createPanel()){
				header('Location: '.redirect("power_panel.php?PanelID=$panel->PanelID"));
			}
		} else {
			$panel->updatePanel();
		}
	}

	if(isset($_REQUEST["PanelID"])&&($_REQUEST["PanelID"] >0)){
		$panel->PanelID=(isset($_POST['PanelID']) ? $_POST['PanelID'] : $_GET['PanelID']);
		$panel->getPanel();
		$pdu->PanelID = $panel->PanelID;
		$pduList=$pdu->GetPDUbyPanel();
		$mech->PanelID = $panel->PanelID;
		$mechList=$mech->GetMechByPanel();
		
		$panelLoad = sprintf( "%01.2f", $panel->GetWattage()->Wattage / 1000 );
		$panelCap = $panel->PanelVoltage * $panel->MainBreakerSize * sqrt(3);
		
		$dataMajorTicks = "";
		for ( $i = 0; $i < $panelCap; $i+=( $panelCap / 10 ) ) {
			$dataMajorTicks .= sprintf( "%d ", $i / 1000 );
		}
		$dataMajorTicks .= sprintf( "%d", $panelCap / 1000 );
		
		$dataMaxValue = sprintf( "%d", $panelCap / 1000 );
		
		$dataHighlights = sprintf( "0 %d #eee, %d %d #fffacd, %d %d #eaa", $panelCap / 1000 * .6, $panelCap / 1000 * .6, $panelCap / 1000 * .8, $panelCap / 1000 * .8, $panelCap / 1000);

		$mtarray=implode(",",explode(" ",$dataMajorTicks));
		$hilights = sprintf( "{from: 0, to: %d, color: '#eee'}, {from: %d, to: %d, color: '#fffacd'}, {from: %d, to: %d, color: '#eaa'}", $panelCap / 1000 * .6, $panelCap / 1000 * .6, $panelCap / 1000 * .8, $panelCap / 1000 * .8, $panelCap / 1000);
		// Generate JS for load display
		$script="
	var gauge=new Gauge({
		renderTo: 'power-gauge',
		type: 'canv-gauge',
		title: 'Load',
		minValue: '0',
		maxValue: '$dataMaxValue',
		majorTicks: [ $mtarray ],
		minorTicks: '2',
		strokeTicks: false,
		units: 'kW',
		valueFormat: { int : 3, dec : 2 },
		glow: false,
		animation: {
			delay: 10,
			duration: 200,
			fn: 'bounce'
			},
		colors: {
			needle: {start: '#f00', end: '#00f' },
			title: '#00f',
			},
		highlights: [ $hilights ],
		});
	gauge.draw().setValue($panelLoad);
";
/*
  Example for updating the gauge later.  This will start an endless loop that will update
  the gauge once a second.

    setInterval( function() {
        gauge.setValue($.get('api/v1/power/whateverhere'));
    }, 1000);
*/
	}

if(isset($_POST["action"]) && $_POST["action"]=="Create_mp") {
	$class = SNMP.MeasurePoint::$TypeTab[$_POST["mp_type"]]."MeasurePoint";
	$newMP = new $class;
	$newMP->Label = $_POST["mp_label"];
	$newMP->Type = $_POST["mp_type"];
	$newMP->EquipmentType = "PowerPanel";
	$newMP->EquipmentID = $panel->PanelID;
	$newMP->CreateMP();
}

	$panelList=$panel->getPanelList();

	$mpList = new MeasurePoint();
	$mpList->EquipmentType = "PowerPanel";
	$mpList->EquipmentID = $panel->PanelID;
	$mpList = $mpList->GetMPByEquipment();
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Management</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/gauge.min.js"></script>

  <script type="text/javascript">
	$(document).ready(function(){
		$('#PanelID').change(function(e){
			location.href='power_panel.php?PanelID='+this.value;
		});

		$('#mp_mpid').change(function(){
			if(this.value > 0) {
				document.getElementById("div_mp_label").style.display = "none";
				document.getElementById("div_mp_type").style.display = "none";
				document.getElementById("mp_create").style.display = "none";
				document.getElementById("mp_link").style.display = "";
				document.getElementById("mp_link").href="measure_point.php?mpid="+this.value;
			} else {
				document.getElementById("div_mp_label").style.display = "";
				document.getElementById("div_mp_type").style.display = "";
				document.getElementById("mp_create").style.display = "";
				document.getElementById("mp_link").style.display = "none";
			}
        	});
	});
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page panelmgr">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<div class="center"><div>
<form method="POST">
<div class="table">
<div>
   <div><label for="PanelID">',__("Power Panel ID"),'</label></div>
   <div><select name="PanelID" id="PanelID">
	<option value="0">',__("New Panel"),'</option>';

	foreach($panelList as $panelRow){
		if($panelRow->PanelID == $panel->PanelID){$selected=" selected";}else{$selected="";}
		print "	<option value=\"$panelRow->PanelID\"$selected>$panelRow->PanelLabel</option>\n";
	}

echo '	</select>
   </div>
</div>
<div>
   <div><label for="PanelLabel">',__("Panel Name"),'</label></div>
   <div><input type="text" size="40" name="PanelLabel" id="PanelLabel" value="',$panel->PanelLabel,'"></div>
</div>
<div>
   <div><label for="NumberOfPoles">',__("Number of Poles"),'</label></div>
   <div><input type="number" name="NumberOfPoles" id="NumberOfPoles" size="3" value="',$panel->NumberOfPoles,'"></div>
</div>
<div>
   <div><label for="MainBreakerSize">',__("Main Breaker Amperage"),'</label></div>
   <div><input type="number" name="MainBreakerSize" id="MainBreakerSize" size="4" value="',$panel->MainBreakerSize,'"></div>
</div>
<div>
   <div><label for="PanelVoltage">',__("Panel Voltage"),'</label></div>
   <div><input type="number" name="PanelVoltage" id="PanelVoltage" size="4" value="',$panel->PanelVoltage,'"></div>
</div>
<div>
   <div><label for="NumberScheme">',__("Numbering Scheme"),'</label></div>
   <div><select name="NumberScheme" id="NumberScheme">';

// This is messy but since we are actually storing this value in the db and we use it elsewhere this
// worked out best
	if($panel->NumberScheme=="Odd/Even"){$selected=" selected";}else{$selected="";}
	print "<option value=\"Odd/Even\"$selected>".__("Odd/Even")."</option>\n";

	if($panel->NumberScheme=="Sequential"){$selected=" selected";}else{$selected="";}
	print "<option value=\"Sequential\"$selected>".__("Sequential")."</option>\n";

?>
   </select>
   </div>
</div>
<div>
	<div><label for="ParentPanelID"><?php print __("Parent Panel"); ?></label></div>
	<div><select name="ParentPanelID" id="ParentPanelID">
		<option value=0></option>
<?php
	foreach($panelList as $pnl){
		$selected=($pnl->PanelID==$panel->ParentPanelID)?" selected":"";
		// Avoid making medieval royalty - panels that are children of themselves
		if($panel->PanelID!=$pnl->PanelID){
			print "\t\t<option value=$pnl->PanelID$selected>$pnl->PanelLabel</option>\n";
		}
	}

echo '	</select></div>
</div>
<div>
	<div><label for="ParentBreakerName">',__("Parent Breaker Name"),'</label></div>
	<div><input type="text" name="ParentBreakerName" id="ParentBreakerName" size="40" value="',$panel->ParentBreakerName,'"></div>
</div>
<div>
	<div><label for="PanelIPAddress">',__("Panel Meter IP Address"),'</label></div>
	<div><input type="text" name="PanelIPAddress" id="PanelIPAddress" size="30" value="',$panel->PanelIPAddress,'"></div>
</div>
<div>
	<div><label for="TemplateID">',__("CDU/Meter Template"),'</label></div>
	<div>
		<select name="TemplateID" id="TemplateID">
			<option value=0></option>';

			foreach($tmpList as $tmp){ 
				$selected=($panel->TemplateID==$tmp->TemplateID)?' selected':'';
				print "\n\t\t\t<option value=$tmp->TemplateID$selected>$tmp->Model</option>";
			}
?>

		</select>
	</div>
</div>
<div class="caption">
<?php
	if($panel->PanelID >0){
		echo '	<button type="submit" name="action" value="Update">',__("Update"),'</button>
	<button type="button" name="action" value="Delete">',__("Delete"),'</button>';
	}else{
		echo '	<button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
?>
</div>
</div><!-- END div.table -->
</form>
<?php
	// Build a panel schedule if this is not a new panel being created
	// Also show the power gauge
	if($panel->PanelID >0){
		echo '<div><canvas id="power-gauge" width="200" height="200"></canvas></div>';

		$mpOptions="";
		foreach($mpList as $mp) {
			$mpOptions .= "<option value=\"$mp->MPID\"$selected>[".__(MeasurePoint::$TypeTab[$mp->Type])."] $mp->Label</option>";
		}

		$typeOptions="";
		foreach(MeasurePoint::$TypeTab as $key => $type) {
			$typeOptions .= "<option value=\"$key\">$type</option>";
		}

		echo '<br>
	<center>
		<form method="POST">
			<h2>'.__("Measure Points").'</h2>
			<div class="table">
				<div>
					<div><label for="mp_mpid">'.__("Measure Point ID").'</label></div>
					<div><select name="mp_mpid" id="mp_mpid">
						<option value="0">'.__("New Measure Point").'</option>
						'.$mpOptions.'
					</select></div>
				</div>
				<div id="div_mp_label">
					<div><label for="mp_label">'.__("Label").'</label></div>
					<div><input type="text" name="mp_label" id="mp_label"></div>
				</div>
				<div id="div_mp_type">
					<div><label for="mp_type">'.__("Type").'</label></div>
					<div><select name="mp_type">
					'.$typeOptions.'
					</select></div>
				</div>
				<div class="caption">
					<button type="submit" name="action" id="mp_create" value="Create_mp">',__("Create Measure Point"),'</button>
					<a href="" id="mp_link" style="display: none;">[ '.__("Edit Measure Point").' ]</a>
				</div>
			</div>
		</form>
	</center>';
	
		/* Loop through PDUs and find all that are attached to this panel and build a temp array to hold them.
		   Array is indexed by circuit IDs.  Each ID is an array of objects that are connected there.  This 
		   allows for multiple PDUs to be connected to a single breaker.

			Structure:
				$pduarray[$panel->PanelPole]->ArrayofPDUs[]->PowerDistribution Object

		   */
		$eqarray=array();
		foreach($pduList as $pnlPDU){
			if($pnlPDU->PanelID == $panel->PanelID){
				$eqarray[$pnlPDU->PanelPole][]=$pnlPDU;
			}elseif($pnlPDU->PanelID2 == $panel->PanelID){
				$eqarray[$pnlPDU->PanelPole2][]=$pnlPDU;
			}
		}
                foreach($mechList as $pnlMech){
                        if($pnlMech->PanelID == $panel->PanelID){
                                $eqarray[$pnlMech->PanelPole][]=$pnlMech;
                        }elseif($pnlMech->PanelID2 == $panel->PanelID){
                                $eqarray[$pnlMech->PanelPole2][]=$pnlMech;
                        }
                }
		print "<center><h2>".__("Panel Schedule")."</h2></center>\n<table>";

		$nextPole=1;
		$odd=$even=0;
		
		if($panel->NumberScheme=="Sequential"){
			while($nextPole <= $panel->NumberOfPoles){
				print "<tr><td class=\"polenumber\">$nextPole</td>";
				// Someone input a pole number wrong and this one would have been skipped
				// store the value and deal with it later.
				if(isset($eqarray[$nextPole])&&$odd!=0){
					foreach($eqarray[$nextPole] as $var){
						if($var instanceof MechanicalDevice)
							$errors[]="<a href=\"mechanical_device.php?mechid=$var->MechID\">$var->Label</a>";
						else
							$errors[]="<a href=\"devices.php?DeviceID=$var->PDUID\">$pduvar->Label</a>";
					}
				}
				// Get info for pdu on this pole if it is populated.
				$lastCabinet=0;
				if($odd==0){
					if(isset($eqarray[$nextPole])){
						$pn="";
						foreach($eqarray[$nextPole] as $var) {
							if($var instanceof PowerDistribution) {
								$cab->CabinetID=$var->CabinetID;
								$cab->GetCabinet(  );
								
								if ($lastCabinet<>$var->CabinetID)
									$pn.="<a href=\"cabnavigator.php?cabinetid=$var->CabinetID\">$cab->Location</a>";
								$pn.="<a href=\"devices.php?DeviceID=$var->PDUID\"><span>$var->Label</span></a>";
								$lastCabinet=$var->CabinetID;
							} else {
                                                               $pn.="<a href=\"mechanical_device.php?mechid=$var->MechID\"><notooltip><span>$var->Label</span></notooltip></a>";
							}
							
							switch($var->BreakerSize){
								case '3': $odd=3; break;
								case '2': $odd=2; break;
								default: $odd=0;
							}
						}
					}else{
						$pn="";
					}
					if($odd==0){
						print "<td class=\"polelabel\">$pn</td></tr>";
					}else{
						print "<td class=\"polelabel\" rowspan=$odd>$pn</td></tr>";
						--$odd;
					}
				}else{ // we've already started to display a circuit.  no new circuits will be drawn til this count hits zero.
					--$odd;
				}
				++$nextPole;
			}
		}else{
			// Build single table with four colums to represent an odd/even panel layout
			// $odd and $even will be travel counters to ensure the table is built in a sane manner
			while($nextPole <= $panel->NumberOfPoles){
				print "<tr><td class=\"polenumber\">$nextPole</td>";
				// Someone input a pole number wrong and this one would have been skipped
				// store the value and deal with it later.
				if(isset($eqarray[$nextPole])&&$odd!=0){
					foreach($eqarray[$nextPole] as $var){
						if($var instanceof MechanicalDevice)
							$errors[]="<a href=\"mechanical_device.php?mechid=$var->MechID\">$var->Label</a>";
						else
							$errors[]="<a href=\"devices.php?DeviceID=$var->PDUID\">$var->Label</a>";
					}
				}
				// Get info for pdu on this pole if it is populated.
				$lastCabinet=0;
				if($odd==0){
					if(isset($eqarray[$nextPole])){
						$pn="";
						foreach($eqarray[$nextPole] as $var) {
							if($var instanceof PowerDistribution) {
								$cab->CabinetID=$var->CabinetID;
								$cab->GetCabinet(  );
								
								if ($lastCabinet<>$var->CabinetID)
									$pn.="<a href=\"cabnavigator.php?cabinetid=$var->CabinetID\">$cab->Location</a>";
								$pn.="<a href=\"devices.php?DeviceID=$var->PDUID\"><span>$var->Label</span></a>";
								$lastCabinet=$var->CabinetID;
							} else {
                                                                $pn.="<a href=\"mechanical_device.php?mechid=$var->MechID\"><notooltip><span>$var->Label</span></notooltip></a>";
							}

							switch($var->BreakerSize){
								case '3': $odd=3; break;
								case '2': $odd=2; break;
								default: $odd=0;
							}
						}
					}else{
						$pn="";
					}
					if($odd==0){
						print "<td class=\"polelabel\">$pn</td>";
					}else{
						print "<td class=\"polelabel\" rowspan=$odd>$pn</td>";
						--$odd;
					}
				}else{ // we've already started to display a circuit.  no new circuits will be drawn til this count hits zero.
					--$odd;
				}
				//Odd side done. Print even side circuit id then check for connected device.
				++$nextPole;
				print "<td class=\"polenumber\">$nextPole</td>";
				// Someone input a pole number wrong and this one would have been skipped
				// store the value and deal with it later.
				if(isset($eqarray[$nextPole])&&$even!=0){ 
					foreach($eqarray[$nextPole] as $var){
						if($var instanceof MechanicalDevice)
							$errors[]="<a href=\"mechanical_device.php?mechid=".$var->MechID."\">".$var->Label."</a>";
						else
							$errors[]="<a href=\"devices.php?DeviceID=".$var->PDUID."\">".$var->Label."</a>";
					}
				}
				if($even==0){
					if(isset($eqarray[$nextPole])){
						$pn="";
						foreach($eqarray[$nextPole] as $var) {
							if($var instanceof PowerDistribution) {
								$cab->CabinetID=$var->CabinetID;
								$cab->GetCabinet(  );
								
								if ($lastCabinet<>$var->CabinetID)
									$pn.="<a href=\"cabnavigator.php?cabinetid=$var->CabinetID\">$cab->Location</a>";
								$pn.="<a href=\"devices.php?DeviceID=$var->PDUID\"><span>$var->Label</span></a>";
								$lastCabinet=$var->CabinetID;
							} else {
                                                                $pn.="<a href=\"mechanical_device.php?mechid=$var->MechID\"><notooltip><span>$var->Label</span></notooltip></a>";
							}

                            				switch($pduvar->BreakerSize){
								case '3': $even=3; break;
								case '2': $even=2; break;
								default: $even=0;
							}
						}
					}else{
						$pn="";
					}
					if($even==0){
						print "<td class=\"polelabel\">$pn</td></tr>\n";
					}else{
						print "<td class=\"polelabel\" rowspan=$even>$pn</td>";
						--$even;
					}
				}else{ // we've already started to display a circuit.  no new circuits will be drawn til this count hits zero.
					--$even;
				}
				//Even side done. Incriment counter and restart loop for next row.
				++$nextPole;
			}
		}
	}
	print "</table>";
	// Okay so someone didn't get correct information from the breaker panel
	if(isset($errors)){
		print "<div class=\"table error\">\n	<div>\n		<div>\n			<fieldset>\n				<legend>".__("Errors")."</legend>\n				<div class=\"table\">\n";
		foreach($errors as $err){
			print "					<div><div>$err</div></div>\n";
		}
		print "				</div><!-- END div.table -->\n			</fieldset>\n		</div>\n		<div>".__("PDUs displayed here could not be drawn on the panel because of an overlapping circuit ID assignment. Please check the pole positions on the panels again.")."</div>\n	</div>\n</div><!-- END div.table -->\n";
	}
?>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Power panel delete confirmation"),'" id="deletemodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this power panel?"),'
		</div>
	</div>
</div>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
$('button[value=Delete]').click(function(){
	var defaultbutton={
		"<?php echo __("Yes"); ?>": function(){
			$.post('', {PanelID: $('#PanelID').val(),deletepanel: '' }, function(data){
				if(data.trim()=='ok'){
					self.location=$('.main > a').last().attr('href');
					$(this).dialog("destroy");
				}else{
					alert("Danger, Will Robinson! DANGER!  Something didn't go as planned.");
				}
			});
		}
	}
	var cancelbutton={
		"<?php echo __("No"); ?>": function(){
			$(this).dialog("destroy");
		}
	}
	var modal=$('#deletemodal').dialog({
		dialogClass: 'no-close',
		modal: true,
		width: 'auto',
		buttons: $.extend({}, defaultbutton, cancelbutton)
	});
});
<?php
if( $config->ParameterArray["ToolTips"]=='enabled' ){
?>
		$('.polelabel > a > span').mouseenter(function(){
			var pos=$(this).offset();
			var tooltip=$('<div />').css({
				'left':pos.left+this.getBoundingClientRect().width+15+'px',
				'top':pos.top+(this.getBoundingClientRect().height/2)-15+'px'
			}).addClass('arrow_left border cabnavigator tooltip').attr('id','tt').append('<span class="ui-icon ui-icon-refresh rotate"></span>');
			$.post('scripts/ajax_tooltip.php',{tooltip: this.parentNode.search.split('=').pop(), dev: 1}, function(data){
				tooltip.html(data);
			});
			$('body').append(tooltip);
			$(this).mouseleave(function(){
				tooltip.remove();
			});
		});
<?php
}
echo $script;
?>

</script>
</body>
</html>
