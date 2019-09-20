<?php
	// This was taken mostly from report_outage_simulator.php: the wheel
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
	$sheet = new PHPExcel();

	// Define useful styles and a function to get a colour style
	$styles = array(
			'center' => array(
					'alignment' => array(
							'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
						)
				),
			'left' => array(
					'alignment' => array(
							'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
						)
				),
			'bold' => array(
					'font' => array(
							'bold' => true
						)
				),
		);

	function colorStyle($fg='',$bg=''){
		$style = array(
			'fill' => array(
					'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'color' => array('rgb' => $bg)
			),
			'font' => array(
					'color' => array('rgb' => $fg)
			)
		);
		if($bg=='')
			unset($style['fill']);
		return $style;
	}

	$sheet->getProperties()->setCreator("openDCIM");
	$sheet->getProperties()->setLastModifiedBy("openDCIM");
	$sheet->getProperties()->setTitle(__("Simulated Outage Report"));
	$sheet->getProperties()->setSubject(__("Simulated Power Outage by Project"));

	$sheet->setActiveSheetIndex(0);
	$sheet->getActiveSheet()->setTitle(__("Summary"));
	$row = 1;
	
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

	$activeSheet = $sheet->getActiveSheet();
	$activeSheet->setShowGridlines(false);
	$activeSheet->SetCellValue('A1',__("Power Outage Simulation report"));
	$activeSheet->getStyle('A1')->applyFromArray(
		array_merge_recursive($styles['bold'],$styles['center']) );
	$activeSheet->SetCellValue('A2',__("Data Center").': '.
		($_REQUEST['datacenterid'] > 0 ? $dc->Name : __("All Data Centers") ) );
	$activeSheet->mergeCells('A1:C1');
	$activeSheet->mergeCells('A2:C2');
	$activeSheet->getColumnDimension('A')->setAutoSize(true);
	$activeSheet->getColumnDimension('B')->setAutoSize(true);
	$activeSheet->getColumnDimension('C')->setAutoSize(true);
	$activeSheet->getColumnDimension('D')->setAutoSize(true);
	
	// Create an array to map projects to status
	$projStatus=array();
	$projRow=array();
	$projSheets=array();
	$statusKeys=array('Down'=>0,'Undocumented'=>1,'Degraded'=>2,
					'Degraded/Fail-Safe'=>3,'Normal'=>4);
	$statusColors=array('Down'=>colorStyle('ff0000'),
						'Undocumented'=>colorStyle('25abbb'),
						'Degraded'=>colorStyle('ffa200'),
						'Degraded/Fail-Safe'=>colorStyle('ff078d'),
						'Normal'=>colorStyle());

	$activeSheet->SetCellValue('A4',__("Project Name"));
	$activeSheet->SetCellValue('B4',__("Project Sponsor"));
	$activeSheet->SetCellValue('C4',__("Project Status"));
	$activeSheet->getStyle('A4:C4')->applyFromArray(array_merge_recursive($styles['center'],$styles['bold']));
	$row = 5;

	$unassigned = new StdClass();
	$unassigned->ProjectName ='Unassaigned Devices';
	$unassigned->ProjectSponsor ='';
	$unassigned->ProjectID ='-1';

	$projects = Projects::getProjectList();
	$projects[]= $unassigned;

	foreach($projects as $proj){
		// Add the project to the summary sheet
		$activeSheet = $sheet->setActiveSheetIndex(0);
		$bg = ($row + ( $skipNormal ? 1 : 0)) % 2 > 0 ? colorStyle() : colorStyle('000000','f9f9f9');
		$activeSheet->getStyle('A'.$row.':C'.$row)->applyFromArray($bg);
		$activeSheet->SetCellValue('A'.$row,$proj->ProjectName);
		$activeSheet->SetCellValue('B'.$row,$proj->ProjectSponsor);

		// Create a new sheet for each Project
		$index = $sheet->getSheetCount();
		$projStatus[$proj->ProjectID] = array(0,0,0,0,0);
		$projSheets[$proj->ProjectID] = $index;
		$projRow[$proj->ProjectID] = 4 + ($skipNormal ? 1 : 0);

		$sheet->createSheet($index);
		$activeSheet = $sheet->setActiveSheetIndex($index);
		$activeSheet->setShowGridlines(false);

		$sheetTitle = substr( $proj->ProjectName, 0, 30 );
		$sheetTitle = preg_replace( '/[\\/\*\?\[\]]/', '_', $sheetTitle ); 
		$activeSheet->setTitle( $sheetTitle );
		$activeSheet->mergeCells('A1:F1');

		if($skipNormal){
			$activeSheet->SetCellValue('A2',__("Only listing systems which are down or unknown."));
			$activeSheet->mergeCells('A2:F2');
		}

		// Print header for the project
		$headerRow = $skipNormal? 4 : 3;
		$activeSheet->SetCellValue('A1', __("Project").': '.$proj->ProjectName);
		$activeSheet->getStyle('A1')->applyFromArray(
			array_merge_recursive($styles['center'],$styles['bold']) );

		$activeSheet->SetCellValue('A'. $headerRow, __("Cabinet"));
		$activeSheet->getColumnDimension('A')->setAutoSize(true);
		$activeSheet->SetCellValue('B'. $headerRow, __("DeviceName"));
		$activeSheet->getColumnDimension('B')->setAutoSize(true);
		$activeSheet->SetCellValue('C'. $headerRow, __("Status"));
		$activeSheet->getColumnDimension('C')->setAutoSize(true);
		$activeSheet->SetCellValue('D'. $headerRow, __("Position"));
		$activeSheet->getColumnDimension('D')->setAutoSize(true);
		$activeSheet->SetCellValue('E'. $headerRow, __("Primary Contact"));
		$activeSheet->getColumnDimension('E')->setAutoSize(true);
		$activeSheet->SetCellValue('F'. $headerRow, __("Owner"));
		$activeSheet->getColumnDimension('F')->setAutoSize(true);

		$activeSheet->getStyle('A'.$headerRow.':F'.$headerRow)->applyFromArray(
			array_merge_recursive($styles['center'],$styles['bold']));

		$row++;
	}

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
				$outageStatus = "Down";
				
				// If there is not a circuit to the cabinet that is unaffected, no need to even check
				if ( $diversity ) {
					// If a circuit was entered with no panel ID, or a device has no connections documented, mark it as unknown
					// The only way to be sure a device will stay up is if we have a connection to an unaffected circuit,
					// or to a failsafe switch (ATS) connected to at least one unaffected circuit.
					$outageStatus = "Down";
					
					$connList = PowerPorts::getConnectedPortList( $devRow->DeviceID );
					
					$devPDUList = array();
					$fsDiverse = false;
					
					if ( count( $connList ) == 0 ) {
						$outageStatus = "Undocumented";
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
							$outageStatus = "Degraded";
						elseif ( $fsDiverse )
							$outageStatus = "Degraded/Fail-Safe";
						else
							$outageStatus = "Normal";
					}
				}

				$membership = array();
				foreach(ProjectMembership::getDeviceMembership($devRow->DeviceID) as $proj)
					$membership[]=$proj->ProjectID;

				if(count($membership) == 0)
					$membership[] = '-1';

				foreach($membership as $projID){
					$projStatus[$projID][$statusKeys[$outageStatus]]++;

					$activeSheet = $sheet->setActiveSheetIndex($projSheets[$projID]);
					$row = $projRow[$projID];

					// Alternate row colours, always starting with blank.
					$bg = ($row + ( $skipNormal ? 0 : 1)) % 2 > 0 ? colorStyle() : colorStyle('000000','f9f9f9');


					if ( ! $skipNormal || ( $skipNormal && ( $outageStatus == "Down" || $outageStatus == "Undocumented" ) ) ) {

						$activeSheet->getStyle('A'.$row.':'.'F'.$row)->applyFromArray($bg);
						$activeSheet->SetCellValue('A'.$row, $cabRow->Location );
						$activeSheet->SetCellValue('B'.$row, $devRow->Label );
						$activeSheet->SetCellValue('C'.$row, __($outageStatus) );
						$activeSheet->getStyle('C'.$row)->applyFromArray(
							$statusColors[$outageStatus]);
						$activeSheet->SetCellValue('D'.$row, $devRow->Position );
						$activeSheet->getStyle('D'.$row)->applyFromArray($styles['left']);

						$tmpPerson->PersonID = $devRow->PrimaryContact;
						$tmpPerson->GetPerson();

						$activeSheet->SetCellValue('E'.$row, $tmpPerson->Email );

						$dept->DeptID = $devRow->Owner;
						$dept->GetDeptByID();

						$activeSheet->SetCellValue('F'.$row, $dept->Name );
					}

					$row++;

					// Children devices
					if ( $devRow->ChassisSlots>0 || $devRow->RearChassisSlots>0 ) {
						$kidList = $devRow->GetDeviceChildren();
						foreach ( $kidList as $k ) {
							$tmpPerson->PersonID = $devRow->PrimaryContact;
							$tmpPerson->GetPerson();

							// Children keep the same alternat row colour as the
							// parent, but have a lighter text colour
							$activeSheet->getStyle('A'.$row.':'.'F'.$row)->applyFromArray(
								array_merge(colorStyle('393939'),$bg));
							$activeSheet->SetCellValue('A'.$row, $cabRow->Location );
							$activeSheet->SetCellValue('B'.$row, $k->Label );
							$activeSheet->SetCellValue('C'.$row, __($outageStatus) );
							$activeSheet->getStyle('C'.$row)->applyFromArray(
								$statusColors[$outageStatus]);
							$activeSheet->SetCellValue('D'.$row, __("Child"));
							$activeSheet->SetCellValue('E'.$row, $tmpPerson->Email );

							$dept->DeptID = $k->Owner;
							$dept->GetDeptByID();

							$activeSheet->SetCellValue('F'.$row, $dept->Name );
							
							$row++;
						}
					}
					$projRow[$projID] = $row;
					$activeSheet->setSelectedCell('G1');
				}
			}
		}
	}

	$activeSheet = $sheet->setActiveSheetIndex(0);
	$row = 5;

	$ids = array();
	foreach (Projects::getProjectList() as $proj)
		$ids[]=$proj->ProjectID;
	$ids[] = '-1';

	foreach($ids as $id){
		$status = $projStatus[$id];
		$comp = $status[$statusKeys['Down']]
				+$status[$statusKeys['Degraded']]
				+$status[$statusKeys['Degraded/Fail-Safe']];
		$sum = $comp
				+ $status[$statusKeys['Normal']]
				+ $status[$statusKeys['Undocumented']];

		if($skipNormal && $comp == 0)
			continue;

		$activeSheet->SetCellValue('C'.$row,
			$comp > 0 ? ''.floor($comp*100./$sum).'% '.__("Compromised") : __("Normal") );
		if($comp > 0)
			$activeSheet->getStyle('C'.$row)->applyFromArray(
				$comp/$sum > 0.5 ? colorStyle('ff0000') : colorStyle('ffa200'));

		$row++;
	}

	// Remove empty sheets
	if($skipNormal){
		$row = 5;
		$offset = 0;
		foreach ($projSheets as $proj => $index)
			if ($projRow[$proj] == 5) {
				$sheet->removeSheetByIndex($index-$offset);
				$activeSheet->removeRow($row-$offset);

				$offset++;
				$row++;
			}
		$row -= $offset;
	}

	// If we're back where we started, nothing was affected
	if($row == 5){
		$activeSheet->SetCellValue('A5',__("No Devices Were Affected"));
		$activeSheet->mergeCells('A5:C5');
		$activeSheet->getStyle('A5')->applyFromArray($styles['center']);
		$row++;
	}

	$tags = '';
	foreach ($includeTags as $tag)
		$tags.= ' '.$tag;
	if($tags != ''){
		$row++;
		$activeSheet->SetCellValue('A'.$row,__("Included Tags").':'.$tags);
		$activeSheet->mergeCells('A'.$row.':D'.$row);
	}

	$tags = '';
	foreach ($excludeTags as $tag)
		$tags.= ' '.$tag;
	if($tags != ''){
		$row++;
		$activeSheet->SetCellValue('A'.$row,__("Excluded Tags").':'.$tags);
		$activeSheet->mergeCells('A'.$row.':D'.$row);
	}

	$row++;
	$activeSheet->setSelectedCell('K1');
	$logo=getcwd().'/'.$config->ParameterArray["PDFLogoFile"];
	$img = new PHPExcel_Worksheet_Drawing();
	$img->setName($config->ParameterArray["PDFLogoFile"]);
	$img->setPath($logo);
	$img->setWorksheet($activeSheet);
	$img->setCoordinates('D1');

	$writer = new PHPExcel_Writer_Excel2007($sheet);

	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header( "Content-Disposition: attachment;filename=\"openDCIM-power-outage-simulation.xlsx\"" );
	$writer->save("php://output");

}
?>
