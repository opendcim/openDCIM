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
<form method="post">
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
	$today = date( "Y-m-d" );

	$xl = new PHPExcel();
	
	$xl->getProperties()->setCreator("openDCIM");
	$xl->getProperties()->setLastModifiedBy("openDCIM");
	$xl->getProperties()->setTitle("Data Center Inventory");
	$xl->getProperties()->setSubject("Power Outage Simulation");
	$xl->getProperties()->setDescription("Simulation of power outage event based upon user specified criteria.");
	
	$xl->setActiveSheetIndex(0);

	$currSheet = $xl->getActiveSheet();
	$currSheet->SetCellValue( 'A1', 'Panel Name');
	$currSheet->setCellValue( 'B1', 'Panel Voltage' );
	$currSheet->setCellValue( 'C1', 'Main Breaker Size' );
	$currSheet->setTitle("Affected Panels");

	$pan = new PowerPanel();
	$pdu = new PowerDistribution();
	$pp = new PowerPorts();
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
	
	if ( @count( $srcArray ) > 0 ) {
		// Build an array of the Panels affected when the entire source goes down.
		// This will allow us to use one section of code to calculate effects of panels going down and use it for both cases.
		
		$pnlList = array();
		
		foreach ( $srcArray as $srcID ) {
			$pan->ParentPanelID = $srcID;
			
			$pnlList = array_merge( $pnlList, $pan->getPanelListBySource() );

					// Include the source, in case there are direct connections
			$tmpPnl = new PowerPanel();
			$tmpPnl->PanelID = $srcID;
			$tmpPnl->GetPanel();

			$pnlList[] = $tmpPnl;
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

	// List out the panels affected in the first sheet of the workbook
	
	$row = 2;
	foreach( $pnlList as $downPanel ) {
		$currSheet->setCellValue( 'A'.$row, $downPanel->PanelLabel );
		$currSheet->setCellValue( 'B'.$row, $downPanel->PanelVoltage );
		$currSheet->setCellValue( 'C'.$row, $downPanel->MainBreakerSize );

		$row++;
	}

	foreach( range( 'A', 'C' ) as $col ) {
		$currSheet->getColumnDimension($col)->setAutoSize( true );
	}
	$currSheet->calculateColumnWidths();

	// And finally, build a list of cabinets that have at least one circuit from the affected panels
	
	$cabIDList = array();
	$cabList = array();
	
	// Also need to build a unique list of all PDU ID's included in outage
	$pduArray = array();
	$fsArray = array();

	// Now that we have a complete list of the panels, we need a list of the CDUs affected by the outage
	
	$pduList = array();
	
	// Rebuild an array of just the Panel ID values
	$pnlArray = array();
	
	foreach ( $pnlList as $pnlDown ) {
		$pdu->PanelID = $pnlDown->PanelID;
		
		$pduList = array_merge( $pduList, $pdu->GetPDUbyPanel());
		
		array_push( $pnlArray, $pnlDown->PanelID );
	}

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

	// Now, print a list of the Cabinets that are affected by this outage
		
	usort( $cabList, 'compareCab' );

	$currSheet = $xl->createSheet();
	$currSheet->setTitle( "Affected Cabinets" );

	$currSheet->setCellValue( "A1", "Data Center" );
	$currSheet->setCellValue( "B1", "Location" );
	$currSheet->setCellValue( "C1", "Total Circuits" );
	$currSheet->setCellValue( "D1", "Circuits Affected" );

	$row = 2;

	foreach ( $cabList as $affectedCabinet ) {
		if ( $affectedCabinet->DataCenterID != $dc->DataCenterID ) {
			$dc->DataCenterID = $affectedCabinet->DataCenterID;
			$dc->getDataCenter();
		}
		$currSheet->setCellValue( 'A'.$row, $dc->Name );
		$currSheet->setCellValue( 'B'.$row, $affectedCabinet->Location );

		$pdu->CabinetID = $affectedCabinet->CabinetID;
		$cabPDUList = $pdu->GetPDUByCabinet();

		$currSheet->setCellValue( 'C'.$row, sizeof( $cabPDUList ));

		$downCount = 0;
		foreach( $cabPDUList as $p ) {
			if ( objArraySearch( $pduList, "PDUID", $p->PDUID )) {
				++$downCount;
			}
		}

		$currSheet->setCellValue( 'D'.$row, $downCount );

		if ( $downCount == sizeof( $cabPDUList )) {
			$currSheet->getStyle('A'.$row.':D'.$row)
				->applyFromArray(
					array(
							'fill' => array(
								'type' => PHPExcel_Style_Fill::FILL_SOLID,
								'color' => array( 'rgb' => 'F28A8C')
							)
					)
				);
		}

		$row++;
	}

	foreach( range( 'A', 'D' ) as $col ) {
		$currSheet->getColumnDimension($col)->setAutoSize( true );
	}
	$currSheet->calculateColumnWidths();

	/* Create third worksheet that shows detailed device listing for everything in the cabinets affected. */


	$currSheet = $xl->createSheet();
	$currSheet->setTitle( "Affected Devices" );

	$currSheet->setCellValue( "A1", "Data Center" );
	$currSheet->setCellValue( "B1", "Location" );
	$currSheet->setCellValue( "C1", "Device Label" );
	$currSheet->setCellValue( "D1", "Owner" );
	$currSheet->setCellValue( "E1", "No. Connections");
	$currSheet->setCellValue( "F1", "Documented Connections" );
	$currSheet->setCellValue( "G1", "Down Connections" );

	$row = 2;

	foreach( $cabList as $affectedCabinet ) {
		if ( $affectedCabinet->DataCenterID != $dc->DataCenterID ) {
			$dc->DataCenterID = $affectedCabinet->DataCenterID;
			$dc->getDataCenter();
		}

		$pdu->CabinetID = $affectedCabinet->CabinetID;
		$cabPDUList = $pdu->GetPDUByCabinet();

		$downCount = 0;
		foreach( $cabPDUList as $p ) {
			if ( objArraySearch( $pduList, "PDUID", $p->PDUID )) {
				++$downCount;
			}
		}

		if ( $downCount == sizeof( $cabPDUList )) {
			$allDown = true;
		} else {
			$allDown = false;
		}

		$dev->Cabinet = $affectedCabinet->CabinetID;
		$devList = $dev->ViewDevicesByCabinet( true );

		foreach( $devList as $affectedDevice ) {
			if ( $affectedDevice->DeviceType != "CDU" ) {
				if ( $dept->DeptID != $affectedDevice->Owner ) {
					$dept->DeptID = $affectedDevice->Owner;
					$dept->GetDeptByID();
				}

				$pp->DeviceID = $affectedDevice->DeviceID;
				$ppList = $pp->getPorts();

				$pduDown = 0;
				$docCount = 0;
				foreach ( $ppList as $checkPowerCon ) {
					if ( objArraySearch( $pduList, "PDUID", $checkPowerCon->ConnectedDeviceID )) {
						$pduDown++;
					error_log( "DevID:".$affectedDevice->DeviceID.", PDUID:".$checkPowerCon->ConnectedDeviceID." - Down" );
					}

					if ( $checkPowerCon->ConnectedDeviceID > 0 ) {
						$docCount++;
					}
				}

				$currSheet->setCellValue( "A".$row, $dc->Name );
				$currSheet->setCellValue( "B".$row, $affectedCabinet->Location );
				$currSheet->setCellValue( "C".$row, $affectedDevice->Label );
				$currSheet->setCellValue( "D".$row, $dept->Name );
				$currSheet->setCellValue( "E".$row, $affectedDevice->PowerSupplyCount );
				$currSheet->setCellValue( "F".$row, $docCount++ );
				$currSheet->setCellValue( "G".$row, $pduDown );

				if ( $pduDown == $affectedDevice->PowerSupplyCount || $docCount < $affectedDevice->PowerSupplyCount ) {
					$currSheet->getStyle('A'.$row.':G'.$row)
						->applyFromArray(
							array(
									'fill' => array(
										'type' => PHPExcel_Style_Fill::FILL_SOLID,
										'color' => array( 'rgb' => 'F28A8C')
									)
							)
						);					
				}
				$row++;
			}
		}
	}

	foreach( range( 'A', 'G' ) as $col ) {
		$currSheet->getColumnDimension($col)->setAutoSize( true );
	}
	$currSheet->calculateColumnWidths();

	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header( sprintf( "Content-Disposition: attachment;filename=\"opendcim-%s.xlsx\"", date( "YmdHis" ) ) );
	
	$writer = new PHPExcel_Writer_Excel2007($xl);
	$writer->save('php://output');
}
?>
