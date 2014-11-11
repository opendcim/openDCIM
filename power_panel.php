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

	// AJAX

	if(isset($_POST['deletepanel'])){
		$panel->PanelID=$_POST["panelid"];
		$return='no';
		if($panel->GetPanel()){
			$panel->DeletePanel();
			$return='ok';
		}
		echo $return;
		exit;
	}
	
	// Set a default panel voltage based upon the configuration screen
	$panel->PanelVoltage=$config->ParameterArray["DefaultPanelVoltage"];
  
	if(isset($_POST["action"])&&(($_POST["action"]=="Create")||($_POST["action"]=="Update"))){
		$panel->PanelID=$_POST["panelid"];
		$panel->PowerSourceID=$_POST["powersourceid"];
		$panel->PanelLabel=trim($_POST["panellabel"]);
		$panel->NumberOfPoles=$_POST["numberofpoles"];
		$panel->MainBreakerSize=$_POST["mainbreakersize"];
		$panel->PanelVoltage=$_POST["panelvoltage"];
		$panel->NumberScheme=$_POST["numberscheme"];
		
		if($_POST["action"]=="Create"){
			$panel->CreatePanel();
		} else {
			$panel->UpdatePanel();
		}
	}

	if(isset($_REQUEST["panelid"])&&($_REQUEST["panelid"] >0)){
		$panel->PanelID=(isset($_POST['panelid']) ? $_POST['panelid'] : $_GET['panelid']);
		$panel->GetPanel();
		$pdu->PanelID = $panel->PanelID;
		$pduList=$pdu->GetPDUbyPanel();
	}

	$panelList=$panel->GetPanelList();
	$ps=new PowerSource();
  	$psList=$ps->GetPSList();
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
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page panelmgr">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div><label for="panelid">',__("Power Panel ID"),'</label></div>
   <div><select name="panelid" id="panelid" onChange="form.submit()">
	<option value="0">',__("New Panel"),'</option>';

	foreach($panelList as $panelRow){
		if($panelRow->PanelID == $panel->PanelID){$selected=" selected";}else{$selected="";}
		print "	<option value=\"$panelRow->PanelID\"$selected>$panelRow->PanelLabel</option>\n";
	}

echo '	</select>
   </div>
</div>
<div>
   <div><label for="powersourceid">',__("Power Source"),'</label></div>
   <div><select name="powersourceid" id="powersourceid">';

	foreach($psList as $psRow){
		print "<option value=\"$psRow->PowerSourceID\"";
		if($psRow->PowerSourceID == $panel->PowerSourceID){
			echo ' selected="selected"';
		}
		print ">$psRow->SourceName</option>\n";
      }

echo '</select></div>
</div>
<div>
   <div><label for="panellabel">',__("Panel Name"),'</label></div>
   <div><input type="text" size="40" name="panellabel" id="panellabel" value="',$panel->PanelLabel,'"></div>
</div>
<div>
   <div><label for="numberofpoles">',__("Number of Poles"),'</label></div>
   <div><input type="number" name="numberofpoles" id="numberofpoles" size="3" value="',$panel->NumberOfPoles,'"></div>
</div>
<div>
   <div><label for="mainbreakersize">',__("Main Breaker Amperage"),'</label></div>
   <div><input type="number" name="mainbreakersize" id="mainbreakersize" size="4" value="',$panel->MainBreakerSize,'"></div>
</div>
<div>
   <div><label for="panelvoltage">',__("Panel Voltage"),'</label></div>
   <div><input type="number" name="panelvoltage" id="panelvoltage" size="4" value="',$panel->PanelVoltage,'"></div>
</div>
<div>
   <div><label for="numberscheme">',__("Numbering Scheme"),'</label></div>
   <div><select name="numberscheme" id="numberscheme">';

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
<div class="caption">
<?php
	if($panel->PanelID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>
	<button type="button" name="action" value="Delete">',__("Delete"),'</button>';
	} else {
		echo '   <button type="submit" name="action" value="Create">',__("Create"),'</button>';
  }
?>
</div>
</div><!-- END div.table -->
</form>
<?php
	// Build a panel schedule if this is not a new panel being created
	if($panel->PanelID >0){
		/* Loop through PDUs and find all that are attached to this panel and build a temp array to hold them.
		   Array is indexed by circuit IDs.  Each ID is an array of objects that are connected there.  This 
		   allows for multiple PDUs to be connected to a single breaker.

			Structure:
				$pduarray[$panel->PanelPole]->ArrayofPDUs[]->PowerDistribution Object

		   */
		$pduarray=array();
		foreach($pduList as $pnlPDU){
			if($pnlPDU->PanelID == $panel->PanelID){
				$pduarray[$pnlPDU->PanelPole][]=$pnlPDU;
			}elseif($pnlPDU->PanelID2 == $panel->PanelID){
				$pduarray[$pnlPDU->PanelPole2][]=$pnlPDU;
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
				if(isset($pduarray[$nextPole])&&$odd!=0){
					foreach($pduarray[$nextPole] as $pduvar){
					$errors[]="<a href=\"power_pdu.php?pduid=$pduvar->PDUID\">$pduvar->Label</a>";
					}
				}
				// Get info for pdu on this pole if it is populated.
				$lastCabinet=0;
				if($odd==0){
					if(isset($pduarray[$nextPole])){
						$pn="";
						foreach($pduarray[$nextPole] as $pduvar) {
							$cab->CabinetID=$pduvar->CabinetID;
							$cab->GetCabinet(  );
							
							if ($lastCabinet<>$pduvar->CabinetID)
								$pn.="<a href=\"cabnavigator.php?cabinetid=$pduvar->CabinetID\">$cab->Location</a>";
                            $pn.="<a href=\"power_pdu.php?pduid=$pduvar->PDUID\"><span>$pduvar->Label</span></a>";
                            $lastCabinet=$pduvar->CabinetID;
							
							switch($pduvar->BreakerSize){
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
				if(isset($pduarray[$nextPole])&&$odd!=0){
					foreach($pduarray[$nextPole] as $pduvar){
					$errors[]="<a href=\"power_pdu.php?pduid=$pduvar->PDUID\">$pduvar->Label</a>";
					}
				}
				// Get info for pdu on this pole if it is populated.
				$lastCabinet=0;
				if($odd==0){
					if(isset($pduarray[$nextPole])){
						$pn="";
						foreach($pduarray[$nextPole] as $pduvar) {
							$cab->CabinetID=$pduvar->CabinetID;
							$cab->GetCabinet(  );
							
							if ($lastCabinet<>$pduvar->CabinetID)
								$pn.="<a href=\"cabnavigator.php?cabinetid=$pduvar->CabinetID\">$cab->Location</a>";
                            $pn.="<a href=\"power_pdu.php?pduid=$pduvar->PDUID\"><span>$pduvar->Label</span></a>";
                            $lastCabinet=$pduvar->CabinetID;
							
							switch($pduvar->BreakerSize){
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
				if(isset($pduarray[$nextPole])&&$even!=0){ 
					foreach($pduarray[$nextPole] as $pduvar){
					$errors[]="<a href=\"power_pdu.php?pduid=".$pduvar->PDUID."\">".$pduvar->Label."</a>";
					}
				}
				if($even==0){
					if(isset($pduarray[$nextPole])){
						$pn="";
						foreach($pduarray[$nextPole] as $pduvar) {
							$cab->CabinetID=$pduvar->CabinetID;
							$cab->GetCabinet(  );
							
							if ($lastCabinet<>$pduvar->CabinetID)
								$pn.="<a href=\"cabnavigator.php?cabinetid=$pduvar->CabinetID\">$cab->Location</a>";
                            $pn.="<a href=\"power_pdu.php?pduid=$pduvar->PDUID\"><span>$pduvar->Label</span></a>";
                            $lastCabinet=$pduvar->CabinetID;
							
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
			$.post('', {panelid: $('#panelid').val(),deletepanel: '' }, function(data){
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

</script>
</body>
</html>
