<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );
	require_once( "PHPExcel/PHPExcel.php" );
	require_once( "PHPExcel/PHPExcel/Writer/Excel2007.php" );
	
	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights();
	
	if((isset($_REQUEST["deviceid"]) && ($_REQUEST["deviceid"]=="" || $_REQUEST["deviceid"]==null)) || !isset($_REQUEST["deviceid"])){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
	
	$dev = new Device();
	$dev->DeviceID = $_REQUEST["deviceid"];
	
	if ( ! $dev->GetDevice() ) {
		// Not a valid device ID
		header('Location: '.redirect());
		exit;
	}
	
	$port = new DevicePorts();
	$port->DeviceID = $dev->DeviceID;
	$portList = $port->getPorts();
	
	if ( sizeof( $portList ) < 1 ) {
		// No ports for this device
		header('Location: '.redirect());
		exit;
	}	
	
	$sheet = new PHPExcel();
	
	$sheet->getProperties()->setCreator("openDCIM");
	$sheet->getProperties()->setLastModifiedBy("openDCIM");
	$sheet->getProperties()->setTitle("Device Port Connections");
	$sheet->getProperties()->setSubject("Device Port Detail");
	$sheet->getProperties()->setDescription("Detailed port connection information for " .  $dev->Label . ".");
	
	$sheet->setActiveSheetIndex(0);
	$sheet->getActiveSheet()->SetCellValue('A1','SourceDevice');
	$sheet->getActiveSheet()->SetCellValue('B1','SourcePort');
	$sheet->getActiveSheet()->SetCellValue('C1','TargetDevice');
	$sheet->getActiveSheet()->SetCellValue('D1','TargetPort');
	$sheet->getActiveSheet()->SetCellValue('E1','Notes');
	$sheet->getActiveSheet()->SetCellValue('F1','MediaType');
	$sheet->getActiveSheet()->SetCellValue('G1','Color');
	
	$sheet->getActiveSheet()->setTitle("Connections");
	
	$row = 2;
	
	foreach ( $portList as $devPort ) {
		// These are created inside the loop, because they need to be clean instances each time
		$targetDev = new Device();
		$targetPort = new DevicePorts();
		
		$color = new ColorCoding();
		$mediaType = new MediaTypes();

		$targetDev->DeviceID = $devPort->ConnectedDeviceID;
		$targetDev->GetDevice();
		
		$targetPort->DeviceID = $targetDev->DeviceID;
		$targetPort->PortNumber = $devPort->ConnectedPort;
		$targetPort->getPort();
		
		if ( $targetPort->Label == '' ) {
			$targetPort->Label = $devPort->ConnectedDeviceID > 0 ? $devPort->ConnectedPort : '';
		}
		
		$color->ColorID = $devPort->ColorID;
		$color->GetCode();
		
		$mediaType->MediaID = $devPort->MediaID;
		$mediaType->GetType();
		
		$sheet->getActiveSheet()->SetCellValue('A' . $row, $dev->Label);
		$sheet->getActiveSheet()->SetCellValue('B' . $row, $devPort->Label);
		$sheet->getActiveSheet()->SetCellValue('C' . $row, $targetDev->Label);
		$sheet->getActiveSheet()->SetCellValue('D' . $row, $targetPort->Label);
		$sheet->getActiveSheet()->SetCellValue('E' . $row, $devPort->Notes);
		$sheet->getActiveSheet()->SetCellValue('F' . $row, $mediaType->MediaType);
		$sheet->getActiveSheet()->SetCellValue('G' . $row, $color->Name);

		$row++;
		
		if ( $targetDev->DeviceType == "Patch Panel" ) {
			$path = DevicePorts::followPathToEndPoint( $devPort->ConnectedDeviceID, -$devPort->ConnectedPort );
			$pDev = new Device();
			$tDev = new Device();
			$pPort = new DevicePorts();
			$tPort = new DevicePorts();
			
			foreach ( $path as $p ) {
				// Skip any rear port connections
				if ( $p->PortNumber > 0 && $p->ConnectedPort > 0 ) {
					$pDev->DeviceID = $p->DeviceID;
					$pDev->GetDevice();
					$tDev->DeviceID = $p->ConnectedDeviceID;
					$tDev->GetDevice();
					
					$pPort->DeviceID = $p->DeviceID;
					$pPort->PortNumber = $p->PortNumber;
					$pPort->getPort();
					
					if ( $pPort->Label == "" )
						$pPort->Label = $pPort->PortNumber;
						
					$tPort->DeviceID = $p->ConnectedDeviceID;
					$tPort->PortNumber = $p->ConnectedPort;
					$tPort->getPort();
					
					if ( $tPort->Label == "" )
						$tPort->Label = $tPort->PortNumber;
					
					$sheet->getActiveSheet()->SetCellValue('A' . $row, $pDev->Label);
					$sheet->getActiveSheet()->SetCellValue('B' . $row, $pPort->Label);
					$sheet->getActiveSheet()->SetCellValue('C' . $row, $tDev->Label);
					$sheet->getActiveSheet()->SetCellValue('D' . $row, $tPort->Label);
					$sheet->getActiveSheet()->SetCellValue('E' . $row, $pPort->Notes);
					$sheet->getActiveSheet()->SetCellValue('F' . $row, $mediaType->MediaType);
					$sheet->getActiveSheet()->SetCellValue('G' . $row, $color->Name);

					$row++;
				}
			}
		}
	}
	
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header( sprintf( "Content-Disposition: attachment;filename=\"openDCIM-dev" . $dev->DeviceID . "-connections.xlsx\"", date( "YmdHis" ) ) );
	
	$writer = new PHPExcel_Writer_Excel2007($sheet);
	$writer->save('php://output');
?>
