<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

    $user = new User();
	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$panel=new PowerPanel();
	$pdu=new PowerDistribution();
	$cab = new Cabinet();
	
	// Set a default panel voltage based upon the configuration screen
	$panel->PanelVoltage = $config->ParameterArray["DefaultPanelVoltage"];
  
	if(isset($_REQUEST["action"]) && (($_REQUEST["action"]=="Create")||($_REQUEST["action"]=="Update"))){
		$panel->PanelID = $_REQUEST["panelid"];
		$panel->PowerSourceID = $_REQUEST["powersourceid"];
		$panel->PanelLabel = $_REQUEST["panellabel"];
		$panel->NumberOfPoles = $_REQUEST["numberofpoles"];
		$panel->MainBreakerSize = $_REQUEST["mainbreakersize"];
		$panel->PanelVoltage = $_REQUEST["panelvoltage"];
		$panel->NumberScheme = $_REQUEST["numberscheme"];
		
		if($_REQUEST["action"]=="Create"){
			$panel->CreatePanel($facDB);
		} else {
			$panel->UpdatePanel($facDB);
		}
	}

	if(isset($_REQUEST["panelid"]) && ($_REQUEST["panelid"] >0)){
		$panel->PanelID = $_REQUEST["panelid"];
		$panel->GetPanel($facDB);
		$pdu->PanelID = $panel->PanelID;
		$pduList = $pdu->GetPDUbyPanel($facDB);
	}else{
		//WTF?  Don't call this page directly.  Send them back from whence they came.
	}
	$panelList = $panel->GetPanelList($facDB);
	$ps = new PowerSource();
  	$psList = $ps->GetPSList($facDB);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Data Center Management</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page panelmgr">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main">
<h2><?php echo $config->ParameterArray["OrgName"]; ?> Power Panels</h2>
<h3>Data Center Detail</h3>
<div class="center"><div>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
<div class="table">
<div>
   <div><label for="panelid">Power Panel ID</label></div>
   <div><select name="panelid" id="panelid" onChange="form.submit()">
	<option value="0">New Panel</option>
<?php
	foreach($panelList as $panelRow){
		print "<option value=\"$panelRow->PanelID\"";
		if($panelRow->PanelID == $panel->PanelID){
			echo ' selected="selected"';
		}
		print ">$panelRow->PanelLabel</option>\n";
	}
?>
	</select>
   </div>
</div>
<div>
   <div><label for="powersourceid">Power Source</label></div>
   <div><select name="powersourceid" id="powersourceid">
<?php
	foreach($psList as $psRow){
		print "<option value=\"$psRow->PowerSourceID\"";
		if($psRow->PowerSourceID == $panel->PowerSourceID){
			echo ' selected="selected"';
		}
		print ">$psRow->SourceName</option>\n";
      }
?>
</select></div>
</div>
<div>
   <div><label for="panellabel">Panel Name</label></div>
   <div><input type="text" size="40" name="panellabel" id="panellabel" value="<?php echo $panel->PanelLabel; ?>"></div>
</div>
<div>
   <div><label for="numberofpoles">Number of Poles</label></div>
   <div><input type="number" name="numberofpoles" id="numberofpoles" size="3" value="<?php echo $panel->NumberOfPoles; ?>"></div>
</div>
<div>
   <div><label for="mainbreakersize">Main Breaker Amperage</label></div>
   <div><input type="number" name="mainbreakersize" id="mainbreakersize" size="4" value="<?php echo $panel->MainBreakerSize; ?>"></div>
</div>
<div>
   <div><label for="panelvoltage">Panel Voltage</label></div>
   <div><input type="number" name="panelvoltage" id="panelvoltage" size="4" value="<?php echo $panel->PanelVoltage; ?>"></div>
</div>
<div>
   <div><label for="numberscheme">Numbering Scheme</label></div>
   <div><select name="numberscheme" id="numberscheme">
<?php
	$schemes = array( "Odd/Even", "Sequential" );

	foreach($schemes as $schemeRow){
		print "<option value=\"$schemeRow\"";
		if($panel->NumberScheme == $schemeRow){
			echo ' selected="selected"';
		}
		print ">$schemeRow</option>\n";
	}
?>
   </select>
   </div>
</div>
<div class="caption">
<?php
	if($panel->PanelID >0){
		echo '   <input type="submit" name="action" value="Update">';
	} else {
		echo '   <input type="submit" name="action" value="Create">';
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
		print "<center><h2>Panel Schedule</h2></center>\n<table>";

		$nextPole = 1;
		$odd=$even=0;
		
		if($panel->NumberScheme=="Sequential"){
			while($nextPole <= $panel->NumberOfPoles){
				print "<tr><td class=\"polenumber\">$nextPole</td>";
				// Someone input a pole number wrong and this one would have been skipped
				// store the value and deal with it later.
				if(isset($pduarray[$nextPole])&&$odd!=0){
					foreach($pduarray[$nextPole] as $pduvar){
					$errors[]="<a href=\"pduinfo.php?pduid=$pduvar->PDUID\">$pduvar->Label</a>";
					}
				}
				// Get info for pdu on this pole if it is populated.
				if($odd==0){
					if(isset($pduarray[$nextPole])){
						$pn="";
						foreach($pduarray[$nextPole] as $pduvar) {
							$cab->CabinetID = $pduvar->CabinetID;
							$cab->GetCabinet( $facDB );
							
							$pn.="<a href=\"pduinfo.php?pduid=$pduvar->PDUID\">" . $cab->Location . " / " . $pduvar->Label . "</a>";
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
					$errors[]="<a href=\"pduinfo.php?pduid=$pduvar->PDUID\">$pduvar->Label</a>";
					}
				}
				// Get info for pdu on this pole if it is populated.
				if($odd==0){
					if(isset($pduarray[$nextPole])){
						$pn="";
						foreach($pduarray[$nextPole] as $pduvar) {
							$cab->CabinetID = $pduvar->CabinetID;
							$cab->GetCabinet( $facDB );
							
							$pn.="<a href=\"pduinfo.php?pduid=$pduvar->PDUID\">" . $cab->Location . " / " . $pduvar->Label . "</a>";
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
					$errors[]="<a href=\"pduinfo.php?pduid=".$pduvar->PDUID."\">".$pduvar->Label."</a>";
					}
				}
				if($even==0){
					if(isset($pduarray[$nextPole])){
						$pn="";
						foreach($pduarray[$nextPole] as $pduvar) {
							$cab->CabinetID = $pduvar->CabinetID;
							$cab->GetCabinet( $facDB );
							
							$pn.="<a href=\"pduinfo.php?pduid=".$pduvar->PDUID."\">".$cab->Location." / ".$pduvar->Label."</a>";
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
		print "<div class=\"table error\">\n	<div>\n		<div>\n			<fieldset>\n				<legend>Errors</legend>\n				<div class=\"table\">\n";
		foreach($errors as $err){
			print "					<div><div>$err</div></div>\n";
		}
		print "				</div><!-- END div.table -->\n			</fieldset>\n		</div>\n		<div>PDUs displayed here could not be drawn on the panel because of an overlapping circuit ID assignment. Please check the pole positions on the panels again.</div>\n	</div>\n</div><!-- END div.table -->\n";
	}
?>
</div></div>
<a href="index.php">[ Return to Main Menu ]</a>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
