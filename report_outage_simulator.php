<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	if(!$person->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
	
if (!isset($_REQUEST['action'])){
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>openDCIM Inventory Reporting</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>

</head>
<body>
<?php include( 'header.inc.php' ); ?>
<?php
	include( 'sidebar.inc.php' );
	
	$datacenter = new DataCenter();
	$dcList = $datacenter->GetDCList();
	
	$pwrPanel = new PowerPanel();
	$cab = new Cabinet();
	
?>
</div>
<div class="main">
<h2>openDCIM</h2>
<h3>Outage Impact Simulation</h3>
<form action="<?php printf( "%s", $_SERVER['PHP_SELF'] ); ?>" method="post">
<table align="center" border=0>
<?php
	if ( @$_REQUEST['datacenterid'] == 0 ) {
		printf( "<tr><td>%s:</td><td>\n", __("Data Center") );
		printf( "<select name=\"datacenterid\" onChange=\"form.submit()\">\n" );
		printf( "<option value=\"\">%s</option>\n", __("Select data center") );
		printf( "<option value=\"-1\">%s</option>\n", __("All Data Centers"));
		foreach ( $dcList as $dc )
			printf( "<option value=\"%d\">%s</option>\n", $dc->DataCenterID, $dc->Name );
		
		printf( "</td></tr>" );
	} else {
		if ( $_REQUEST['datacenterid'] > 0 ) {
			/* If the datacenterid > 0, then it's a single data center */
			$datacenter->DataCenterID = $_REQUEST['datacenterid'];
			$datacenter->GetDataCenter();
			
			$sourceList = $pwrPanel->getSourcesByDataCenter( $datacenter->DataCenterID );
		} else {
			/*	All data centers were selected, so get ALL sources */
			$sourceList = $pwrPanel->GetSources();
		}
		printf( "<input type=\"hidden\" name=\"datacenterid\" value=\"%d\">\n", $datacenter->DataCenterID );
		
		printf( "<h3>%s: %s</h3>", __("Choose either power sources or panels to simulate for Data Center"), $datacenter->Name );
		
		printf( "<input type=submit name=\"action\" value=\"%s\"><br>\n", __("Generate") );
		
		printf( "<input type=checkbox name=\"skipnormal\">%s<br>\n", __("Only show down/unknown devices") );
		
		printf( "<table border=1 align=center>\n" );
		printf( "<tr><th>%s</th><th>%s</th></tr>\n", __("Power Source"), __("Power Panel") );
		
		foreach ( $sourceList as $source ) {
			$pwrPanel->ParentPanelID = $source->PanelID;
			$panelList = $pwrPanel->getPanelListBySource();
			
			printf( "<tr><td><input type=\"checkbox\" name=\"sourceid[]\" value=\"%d\">%s</td>\n", $source->PanelID, $source->PanelLabel );
			
			printf( "<td><table>\n" );
			
			foreach ( $panelList as $panel )
				printf( "<tr><td><input type=\"checkbox\" name=\"panelid[]\" value=\"%d\">%s</td></tr>\n", $panel->PanelID, $panel->PanelLabel );
			
			printf( "</table></td></tr>\n" );
		}
		
		printf( "<div><label>%s</label><input type='text' size='50' name='tags' id='tags'></div>\n", __("Tags (example: +Linux -Development)") );
	}
?>
</table>
</form>
<?php
} else {
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.dataTables.css" type="text/css">
  <link rel="stylesheet" href="css/ColVis.css" type="text/css">
  <link rel="stylesheet" href="css/TableTools.css" type="text/css">
  <style type="text/css"></style>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="scripts/ColVis.min.js"></script>
  <script type="text/javascript" src="scripts/TableTools.min.js"></script>
  
  
  <script type="text/javascript">
	$(document).ready(function(){
		var rows;
		function dt(){
			$('#report').dataTable({
				"iDisplayLength": 25,
				"sDom": 'CT<"clear">lfrtip',
				"oTableTools": {
					"sSwfPath": "scripts/copy_csv_xls.swf",
					"aButtons": ["copy","csv","xls","print"]
				}
			});
			redraw();
		}
		function redraw(){
			if(($('#report').outerWidth()+$('#sidebar').outerWidth()+10)<$('.page').innerWidth()){
				$('.main').width($('#header').innerWidth()-$('#sidebar').outerWidth()-16);
			}else{
				$('.main').width($('#report').outerWidth()+40);
			}
			$('.page').width($('.main').outerWidth()+$('#sidebar').outerWidth()+10);
		}
		dt();
	});
  </script>

</head>
<body>
<?php include( 'header.inc.php' ); ?>
	<div class="page">
<?php
	include('sidebar.inc.php');
echo '		<div class="main">
			<div class="center">
				<div id="tablecontainer">';

	//
	//
	//	Begin Report Generation
	//
	//

	$pan = new PowerPanel();
	$pdu = new PowerDistribution();
	$dev = new Device();
	$cab = new Cabinet();
	$tmpPerson = new People();
	$dept = new Department();
	$dc = new DataCenter();
	
	// Make some quick user defined sort comparisons for this report only
	
	function compareCab( $a, $b ) {
		if ( $a->Location == $b->Location )
			return 0;
		
		return ( $a->Location > $b->Location ) ? +1 : -1;
	}
	
	$dc->DataCenterID = intval( $_REQUEST['datacenterid'] );
	$dc->GetDataCenter();
	
	$skipNormal = false;

	if (isset( $_REQUEST["skipnormal"] ) ) {
		$skipNormal = $_REQUEST["skipnormal"];
	}
	
	if(isset($_POST['sourceid'])){
		$srcArray=$_POST['sourceid'];
	}
	if(isset($_POST['panelid'])){
		$pnlArray=$_POST['panelid'];
	}
	
	if ( isset( $_POST['tags'] ) ) {
		$tagList = preg_split( "/([\+\-])/", $_POST['tags'], -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
		$includeTags = array();
		$excludeTags = array();
		
		foreach ( $tagList as $t ) {
			if ( $t == '+' ) {
				$tInclude = true;
			} elseif ( $t == '-' ) {
				$tInclude = false;
			} elseif ( $tInclude ) {
				$includeTags[] = $t;
			} else {
				$excludeTags[] = $t;
			}
		}
	}
	
	if ( count( $srcArray ) > 0 ) {
		// Build an array of the Panels affected when the entire source goes down.
		// This will allow us to use one section of code to calculate effects of panels going down and use it for both cases.
		
		$pnlList = array();
		
		foreach ( $srcArray as $srcID ) {
			$pan->ParentPanelID = $srcID;
			
			$pnlList = array_merge( $pnlList, $pan->getPanelListBySource() );
		}
	} else {
		// Need to build an array of Panel Objects (what we got from input was just the IDs)
		$pnlList = array();
		
		foreach ( $pnlArray as $pnlID ) {
			$pnlCount = count( $pnlList );
			$pnlList[$pnlCount] = new PowerPanel();
			$pnlList[$pnlCount]->PanelID = $pnlID;
			$pnlList[$pnlCount]->GetPanel();
		}
	}
	
	// Now that we have a complete list of the panels, we need a list of the CDUs affected by the outage
	
	$pduList = array();
	
	// Rebuild an array of just the Panel ID values
	$pnlArray = array();
	
	foreach ( $pnlList as $pnlDown ) {
		$pdu->PanelID = $pnlDown->PanelID;
		
		$pduList = array_merge( $pduList, $pdu->GetPDUbyPanel());
		
		array_push( $pnlArray, $pnlDown->PanelID );
	}

	// And finally, build a list of cabinets that have at least one circuit from the affected panels
	
	$cabIDList = array();
	$cabList = array();
	
	// Also need to build a unique list of all PDU ID's included in outage
	$pduArray = array();
	$fsArray = array();

	foreach ( $pduList as $outagePDU ) {
		if ( array_search( $outagePDU->CabinetID, $cabIDList ) === false ) {
			array_push( $cabIDList, $outagePDU->CabinetID );
			
			$cabCount = count( $cabList );
			
			$cabList[$cabCount] = new Cabinet();
			$cabList[$cabCount]->CabinetID = $outagePDU->CabinetID;
			$cabList[$cabCount]->GetCabinet();
		}
			
		if ( $outagePDU->FailSafe ) {
			// Check both inputs on a FailSafe PDU
			if ( in_array( $outagePDU->PanelID, $pnlArray ) && in_array( $outagePDU->PanelID2, $pnlArray ) ) {
				array_push( $pduArray, $outagePDU->PDUID );
			} else {
				if ( in_array( $outagePDU->PanelID, $pnlArray ) || in_array( $outagePDU->PanelID2, $pnlArray ) ) {
					array_push( $fsArray, $outagePDU->PDUID );
				}
			}
		} else {
			array_push( $pduArray, $outagePDU->PDUID );
		}
	}
		
	usort( $cabList, 'compareCab' );
	
	// error_log( "Cabinet List:" . print_r( $cabList, true ));

	printf( "<h2>%s</h2>", __("Power Outage Simulation Report") );
	
	if ( $skipNormal )  {
		printf( "<h3>%s</h3>\n", __("Only listing systems which are down or unknown.") );
	}
	
	echo "<table id=\"report\" class=\"display\">\n<thead>\n";
	foreach ( array( __("Cabinet"), __("Device Name"), __("Status"), __("Position"), __("Primary Contact"), __("Owner") ) as $header )
		printf( "<th>%s</th>\n", $header );
	echo "</thead>\n<tbody>\n";
		
	foreach ( $cabList as $cabRow ) {
		$dev->Cabinet = $cabRow->CabinetID;
		
		// Check to see if all circuits to the cabinet are from the outage list - if so, the whole cabinet goes down
		$pdu->CabinetID = $cabRow->CabinetID;
		$cabPDUList = $pdu->GetPDUbyCabinet();

		$diversity = false;

		// If you can find one CDU for the Cabinet that is not in the list of down CDUs, then you have at least some diversity
		foreach ( $cabPDUList as $cabPDU ) {
			if ( ! objArraySearch( $pduList, "DeviceID", $cabPDU->PDUID)) {
				$diversity = true;
				break;
			}
		}

		// Basic device selection based on the CabinetID
		// Filter out all reservations, devices with no power supplies, power strips, and chassis slot cards
		$sql = "SELECT * FROM fac_Device WHERE Reservation=0 AND PowerSupplyCount>0 AND DeviceType not in ('CDU','Patch Panel','Physical Infrastructure') AND ParentDevice=0 AND Cabinet=" . intval( $cabRow->CabinetID );

		// If tags were added, only include devices with tags that are in the Include array
		if ( sizeof( $includeTags ) > 0 ) {
			$sql .= " AND DeviceID in (select DeviceID from fac_DeviceTags a, fac_Tags b where a.TagID=b.TagID and b.Name in (";
			for ( $n = 0; $n < sizeof( $includeTags ); $n++ ) {
				if ( $n > 0 ) {
					$sql .= ", ";
				}
				$sql .= "\"" . sanitize($includeTags[$n]) . "\"";
			}
			$sql .= ")) ";
		}
		
		// If tags were added, only include devices that don't have tags in the Exclude array
		if ( sizeof( $excludeTags ) > 0 ) {
			$sql .= " AND DeviceID not in (select DeviceID from fac_DeviceTags a, fac_Tags b where a.TagID=b.TagID and b.Name in (";
			for ( $n = 0; $n < sizeof( $excludeTags ); $n++ ) {
				if ( $n > 0 ) {
					$sql .= ", ";
				}
				$sql .= "\"" . sanitize( $excludeTags[$n] ) . "\"";
			}
			$sql .= ")) ";
		}
		
		$st = $dbh->prepare( $sql );
		$st->execute();
		$st->setFetchMode( PDO::FETCH_CLASS, "Device" );
		$devList = array();
		while ( $row = $st->fetch() ) {
			$devList[] = $row;
		}
		
		if ( sizeof( $devList ) > 0 ) {
			foreach ( $devList as $devRow ) {
				$downPanels = "";
				$outageStatus = __("Down");
				
				// If there is not a circuit to the cabinet that is unaffected, no need to even check
				if ( $diversity ) {
					// If a circuit was entered with no panel ID, or a device has no connections documented, mark it as unknown
					// The only way to be sure a device will stay up is if we have a connection to an unaffected circuit,
					// or to a failsafe switch (ATS) connected to at least one unaffected circuit.
					$outageStatus = __("Down");
					
					$connList = PowerPorts::getConnectedPortList( $devRow->DeviceID );
					
					$devPDUList = array();
					$fsDiverse = false;
					
					if ( count( $connList ) == 0 ) {
						$outageStatus = __("Undocumented");
					}

					foreach ( $connList as $connection ) {
						// If the connection is to a PDU that is NOT in the affected PDU list, and is not already in the diversity list, add it

						if ( ! in_array( $connection->ConnectedDeviceID, $pduArray ) ) {
							if ( ! in_array( $connection->ConnectedDeviceID, $devPDUList ) )
								array_push( $devPDUList, $connection->ConnectedDeviceID );

						}

						if ( in_array( $connection->ConnectedDeviceID, $fsArray ) ) {
							$fsDiverse = true;
						}
					}
					
					if ( count( $devPDUList ) > 0 ) {
						if ( count( $devPDUList ) < $devRow->PowerSupplyCount )
							$outageStatus = __("Degraded");
						elseif ( $fsDiverse )
							$outageStatus = __("Degraded/Fail-Safe");
						else
							$outageStatus = __("Normal");
					}
				}

				if ( ! $skipNormal || ( $skipNormal && ( $outageStatus == __("Down") || $outageStatus == __("Undocumented") ) ) ) {
					echo "<tr>\n";
					printf( "<td>%s</td>\n", $cabRow->Location );
					printf( "<td>%s</td>\n", $devRow->Label );
					printf( "<td>%s</td>\n", $outageStatus );
					printf( "<td>%s</td>\n", $devRow->Position );

					$tmpPerson->PersonID = $devRow->PrimaryContact;
					$tmpPerson->GetPerson();

					printf( "<td>%s</td>\n", $tmpPerson->Email );

					$dept->DeptID = $devRow->Owner;
					$dept->GetDeptByID();

					printf( "<td>%s</td>\n", $dept->Name );
					
					echo "</tr>\n";
				}

				if ( $devRow->ChassisSlots>0 || $devRow->RearChassisSlots>0 ) {
					$kidList = $devRow->GetDeviceChildren();
					foreach ( $kidList as $k ) {
						$tmpPerson->PersonID = $devRow->PrimaryContact;
						$tmpPerson->GetPerson();
						echo "<tr>\n";
						printf( "<td>%s</td>\n", $cabRow->Location );
						printf( "<td>%s</td>\n", $k->Label );
						printf( "<td>%s</td>\n", $outageStatus );
						printf( "<td>%s</td>\n", __("Child"));
						printf( "<td>%s</td>\n", $tmpPerson->Email );

						$dept->DeptID = $k->Owner;
						$dept->GetDeptByID();

						printf( "<td>%s</td>\n", $dept->Name );
						
						echo "</tr>\n";

					}
				}
			}
		}
	  
	}    	

?>
					</tbody>
					</table>
				</div>
			</div>
		</div><!-- END div.main -->
	</div><!-- END div.page -->
</body>
</html>
<?php
}
?>
