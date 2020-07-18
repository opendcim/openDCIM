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
	$cab=new Cabinet();
	$tmpl = new CDUTemplate();
	$tmpList = $tmpl->GetTemplateList();
	$dcList = DataCenter::GetDCList();
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
  
	if(isset($_POST["action"])&&(($_POST["action"]=="Create")||($_POST["action"]=="Update")||($_POST["action"]=="Map"))){
		foreach($panel as $prop => $val){
			$panel->$prop=trim($_POST[$prop]);
		}
		// Coordinates aren't displayed on this page and the loop above is looking 
		// for every attribute on the panel model.  This will load the original object 
		// and pull over the coordinates so they don't get wiped. 
		if($_POST["action"]!="Create"){
			$pan=new PowerPanel();
			$pan->PanelID=$panel->PanelID;
			$pan->getPanel();
			$panel->MapX1=$pan->MapX1;
			$panel->MapX2=$pan->MapX2;
			$panel->MapY1=$pan->MapY1;
			$panel->MapY2=$pan->MapY2;
		}
		
		if($_POST["action"]=="Create"){
			if($panel->createPanel()){
				header('Location: '.redirect("power_panel.php?PanelID=$panel->PanelID"));
			}
		} else {
			$panel->updatePanel();
			if($_POST["action"]=="Map" && $panel->MapDataCenterID>0){
				header('Location: '.redirect("mapmaker.php?panelid=$panel->PanelID"));
			}
		}
	}

	if(isset($_REQUEST["PanelID"])&&($_REQUEST["PanelID"] >0)){
		$panel->PanelID=(isset($_POST['PanelID']) ? $_POST['PanelID'] : $_GET['PanelID']);
		$panel->getPanel();
		$pdu->PanelID = $panel->PanelID;
		$pduList=$pdu->GetPDUbyPanel();

		$panelCap = $panel->PanelVoltage * $panel->MainBreakerSize * sqrt(3);
		
		$decimalplaces=0;
		function FindTicks(&$decimalplaces,$panelCap,&$dataMajorTicks){
			$err=false;
			if($panelCap==0){
				$panelCap=1;
			}
			for ( $i = 0; ($i - $panelCap) < 1; $i+=( $panelCap / 10 ) ) {
				$tick = sprintf( "%.0${decimalplaces}lf ", $i / 1000 );
				if(preg_match("/$tick/",$dataMajorTicks)){
					$err=true;
					break;
				}
				$dataMajorTicks .= $tick;
			}
			return $err;
		}	
		while(FindTicks($decimalplaces,$panelCap,$dataMajorTicks)){
			$decimalplaces++;
			$dataMajorTicks = "";
		}
		
		$dataMaxValue = sprintf( "%.0${decimalplaces}lf", $panelCap / 1000 );
		
		$dataHighlights = sprintf( "0 %d #eee, %d %d #fffacd, %d %d #eaa", $panelCap / 1000 * .6, $panelCap / 1000 * .6, $panelCap / 1000 * .8, $panelCap / 1000 * .8, $panelCap / 1000);

		$mtarray=implode(",",explode(" ",$dataMajorTicks));
		$hilights = sprintf( "{from: 0, to: %.0${decimalplaces}lf, color: '#eee'}, {from: %.0${decimalplaces}lf, to: %.0${decimalplaces}lf, color: '#fffacd'}, {from: %.0${decimalplaces}lf, to: %.0${decimalplaces}lf, color: '#eaa'}", $panelCap / 1000 * .6, $panelCap / 1000 * .6, $panelCap / 1000 * .8, $panelCap / 1000 * .8, $panelCap / 1000);
		
		$panelLoad = sprintf( "%.0${decimalplaces}lf", PowerPanel::getInheritedLoad($panel->PanelID) / 1000 );
		$msrLoad = sprintf( "%.0${decimalplaces}lf", $panel->getPanelLoad() / 1000 );
		$estLoad = sprintf( "%.0${decimalplaces}lf", PowerPanel::getEstimatedLoad($panel->PanelID) / 1000 );

		// Generate JS for load display

		$inheritTitle = __("Inherited Meter Load");
		$estimateTitle = __("Estimated Load");
		$measuredTitle = __("Panel Meter Load");
		
		$script="
	var pwrGauge=new Gauge({
		height: 175,
		width: 175,
		renderTo: 'power-inherited',
		type: 'canv-gauge',
		title: '$inheritTitle',
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
	pwrGauge.draw().setValue($panelLoad);

	var estGauge=new Gauge({
		height: 175,
		width: 175,
		renderTo: 'power-estimate',
		type: 'canv-gauge',
		title: '$estimateTitle',
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
	estGauge.draw().setValue($estLoad);

	var msrGauge=new Gauge({
		height: 175,
		width: 175,
		renderTo: 'power-measured',
		type: 'canv-gauge',
		title: '$measuredTitle',
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
	msrGauge.draw().setValue($msrLoad);
	";


/*
  Example for updating the gauge later.  This will start an endless loop that will update
  the gauge once a second.

    setInterval( function() {
        gauge.setValue($.get('api/v1/power/whateverhere'));
    }, 1000);
*/
	}

	$panelList=$panel->getPanelList();
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
   <div><input type="number" name="NumberOfPoles" id="NumberOfPoles" size="3" value="',$panel->NumberOfPoles,'" min="0"></div>
</div>
<div>
   <div><label for="MainBreakerSize">',__("Main Breaker Amperage"),'</label></div>
   <div><input type="number" name="MainBreakerSize" id="MainBreakerSize" size="4" value="',$panel->MainBreakerSize,'" min="0"></div>
</div>
<div>
   <div><label for="PanelVoltage">',__("Panel Voltage"),'</label></div>
   <div><input type="number" name="PanelVoltage" id="PanelVoltage" size="4" value="',$panel->PanelVoltage,'" min="0"></div>
</div>
<div>
   <div><label for="NumberScheme">',__("Numbering Scheme"),'</label></div>
   <div><select name="NumberScheme" id="NumberScheme">';

// This is messy but since we are actually storing this value in the db and we use it elsewhere this
// worked out best
    $panelType = array( "Odd/Even", "Sequential", "Busway" );

   	foreach ( $panelType as $pType ) {
   		if ( $pType == $panel->NumberScheme ) {
   			$pTypeSelect = "SELECTED";
   		} else {
   			$pTypeSelect = "";
   		}

   		print "<option value=\"$pType\" $pTypeSelect>$pType</option>\n";
 	}
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
echo '

		</select>
	</div>
</div>
<div>
	<div><label for="MapDataCenterID">',__("Data Center (for Mapping)"),'</label></div>
	<div>
		<select name="MapDataCenterID" id="MapDataCenterID">
			<option value=0>Do not map</option>';

			foreach( $dcList as $dc) {
				$selected=($panel->MapDataCenterID==$dc->DataCenterID)?'selected':'';
				print "\n\t\t\t<option value=$dc->DataCenterID $selected>$dc->Name</option>";
			}
echo '		</select>
	</div>
</div>
<div class="caption">';

	if($panel->PanelID >0){
		echo '	<button type="submit" name="action" value="Update">',__("Update"),'</button>
	<button type="button" name="action" value="Delete">',__("Delete"),'</button>
	<button type="submit" name="action" value="Map">',__("Map Coordinates"),'</button>';
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
		echo '
<div class="pwr_gauge"><canvas id="power-measured" width="150" height="150"></canvas></div>
<div class="pwr_gauge"><canvas id="power-inherited" width="150" height="150"></canvas></div>
<div class="pwr_gauge"><canvas id="power-estimate" width="150" height="150"></canvas></div>

';
	
		$panelSchedule=$panel->getPanelSchedule();
		print "<center><h2>".__("Panel Schedule")."</h2></center>\n<table>";

		if($panel->NumberScheme=="Odd/Even") {

			print "<table>";
			for($count=1; $count<=$panel->NumberOfPoles; $count++) {
				if(($count % 2) == 0) {
					print "<td class=\"polenumber\">$count</td>";
					print $panel->getPanelScheduleLabelHtml($panelSchedule["panelSchedule"], $count, "panelright", false);
					print "</tr>";
				} else {
					print "<tr><td class=\"polenumber\">$count</td>";
					print $panel->getPanelScheduleLabelHtml($panelSchedule["panelSchedule"], $count, "panelleft", false);
				}
			}
			print "</table>";
		} elseif ($panel->NumberScheme=="Sequential") {
			print "<table>";
			for($count=1; $count<=$panel->NumberOfPoles; $count++) {
				print "<tr><td class=\"polenumber\">$count</td>";
				print $panel->getPanelScheduleLabelHtml($panelSchedule["panelSchedule"], $count, "panelleft", false);
				print "</tr>";
			}
			print "</table>";
		} else {
			print "<table>";
			foreach( $panelSchedule["panelSchedule"] as $panKey=>$panItem ) {
				print "<tr><td class=\"polenumber\">$panKey</td>";
				print $panel->getPanelScheduleLabelHtml($panelSchedule["panelSchedule"], $panKey, "panelleft", false );
				print "</tr>";
			}
			print "</table>";
		}
	}

	// there are panels/pdus without a pole defined, print those out
	if(isset($panelSchedule["unscheduled"]) && !empty($panelSchedule["unscheduled"])){
		print "<div class=\"table error\">\n	<div>\n		<div>\n			<fieldset>\n				<legend>".__("Unknown Pole")."</legend>\n				<div class=\"table\">\n";
		foreach($panelSchedule["unscheduled"] as $err){
			$errData = $panel->getScheduleItemHtml($err, 0, false);	
			print "					<div><div>";
			print $errData["html"];
			print "</div></div>\n";
		}
		print "				</div><!-- END div.table -->\n			</fieldset>\n		</div>\n		<div>".__("PDUs and Panels displayed here could not be drawn on the panel because no circuit ID was defined. Please check the pole positions on the panels again.")."</div>\n	</div>\n</div><!-- END div.table -->\n";
	}
	"<br>";
	// Okay so someone didn't get correct information from the breaker panel
	if(isset($panelSchedule["errors"]) && !empty($panelSchedule["errors"])){
		print "<div class=\"table error\">\n	<div>\n		<div>\n			<fieldset>\n				<legend>".__("Errors")."</legend>\n				<div class=\"table\">\n";
		foreach($panelSchedule["errors"] as $err){
			$errData = $panel->getScheduleItemHtml($err, 0, false);	
			print "					<div><div>";
			print $errData["html"];
			print "</div></div>\n";
		}
		print "				</div><!-- END div.table -->\n			</fieldset>\n		</div>\n		<div>".__("PDUs and Panels displayed here could not be drawn on the panel because of an overlapping circuit ID assignment. Please check the pole positions on the panels again.")."</div>\n	</div>\n</div><!-- END div.table -->\n";
	}
?>
</div></div>

<?php
	if ($panel->ParentPanelID) {
		echo '<a href="power_panel.php?PanelID=', $panel->ParentPanelID, '">[ ',__("Return to Parent Panel"),' ]</a><br>';
	}
	foreach($dcList as $dc) {
		if ($panel->MapDataCenterID and $panel->MapDataCenterID==$dc->DataCenterID) {
			echo '<a href="dc_stats.php?dc=', $dc->DataCenterID, '">[ ',__("Return to"),' ',$dc->Name.' ]</a><br>'; 
		}
	}

echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
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
