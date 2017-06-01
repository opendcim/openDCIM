<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	function generate_spreadsheet($devList,$mediaIDList=array(),$hideEmptyPower=false){

		$devTmpl = new DeviceTemplate();
		$cab = new Cabinet();
		$mfg = new Manufacturer();
		
		$sheet = new PHPExcel();
		
		$sheet->getProperties()->setCreator("openDCIM");
		$sheet->getProperties()->setLastModifiedBy("openDCIM");
		$sheet->getProperties()->setTitle(__("Device Port Connections"));
		$sheet->getProperties()->setSubject(__("Device Port Detail"));
		
		$sheet->setActiveSheetIndex(0);
		$sheet->getActiveSheet()->SetCellValue('A1',__("SourceDevice"));
		$sheet->getActiveSheet()->SetCellValue('B1',__("SourcePort"));
		$sheet->getActiveSheet()->SetCellValue('C1',__("TargetDevice"));
		$sheet->getActiveSheet()->SetCellValue('D1',__("TargetPort"));
		$sheet->getActiveSheet()->SetCellValue('E1',__("Notes"));
		$sheet->getActiveSheet()->SetCellValue('F1',__("MediaType"));
		$sheet->getActiveSheet()->SetCellValue('G1',__("Color"));

		
		$sheet->getActiveSheet()->setTitle(__("Connections"));
		$row = 2;
		$devNum = 1;
		
		foreach ( $devList as $dev ) {
			// Create a worksheet for each device with details
			$sheet->createSheet($devNum);
			$sheet->setActiveSheetIndex($devNum);
			
			$cab->CabinetID = $dev->Cabinet;
			$cab->GetCabinet();

			$devTmpl->TemplateID = $dev->TemplateID;
			$devTmpl->GetTemplateByID();
			
			$mfg->ManufacturerID = $devTmpl->ManufacturerID;
			$mfg->GetManufacturerByID();
			
			$sheet->getActiveSheet()->SetCellValue('A1',__("Device Label"));
			$sheet->getActiveSheet()->SetCellValue('B1', $dev->Label );
			$sheet->getActiveSheet()->SetCellValue('A2',__("Manufacturer"));
			$sheet->getActiveSheet()->SetCellValue('B2', $mfg->Name );
			$sheet->getActiveSheet()->SetCellValue('A3',__("Model"));
			$sheet->getActiveSheet()->SetCellValue('B3', $devTmpl->Model );
			$sheet->getActiveSheet()->SetCellValue('A4',__("Serial Number"));
			$sheet->getActiveSheet()->SetCellValue('B4', $dev->SerialNo );
			$sheet->getActiveSheet()->SetCellValue('A5',__("Asset Tag"));
			$sheet->getActiveSheet()->SetCellValue('B5', $dev->AssetTag );	
			$sheet->getActiveSheet()->SetCellValue('A6',__("Target Cabinet"));
			$sheet->getActiveSheet()->SetCellValue('B6', $cab->Location );		
			$sheet->getActiveSheet()->SetCellValue('A7',__("Position"));
			$sheet->getActiveSheet()->SetCellValue('B7', $dev->Position );

			// Excel limits sheet titles to 31 characters or less
			// Also invalid chars of /\*?[]
			$sheetTitle = substr( $dev->Label, 0, 30 );
			$sheetTitle = preg_replace( '/[\\/\*\?\[\]]/', '_', $sheetTitle ); 
			$sheet->getActiveSheet()->setTitle( $sheetTitle );
			
			// Insert a picture into the device specific worksheet
			if ( file_exists( "pictures/".$devTmpl->FrontPictureFile ) ) {
				$img = new PHPExcel_Worksheet_Drawing();
				$img->setWorksheet($sheet->setActiveSheetIndex($devNum));
				$img->setName($dev->Label);
				$img->setPath("pictures/".$devTmpl->FrontPictureFile);
				$img->setCoordinates('B9');
				$img->setOffsetX(1);
				$img->setOffsetY(5);
			}
			
			foreach( range('A','B') as $columnID) {
				$sheet->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
			}
			
			$sheet->setActiveSheetIndex(0);
			
			$port = new DevicePorts();
			$port->DeviceID = $dev->DeviceID;
			$portList = $port->getPorts();

	/*	
		if ( sizeof( $portList ) < 1 ) {
			// No ports for this device
			header('Location: '.redirect());
			exit;
		}	
	*/
			if(in_array('-1', $mediaIDList)){
				$pport=new PowerPorts();
				$pport->DeviceID=$dev->DeviceID;
				$pportList=$pport->getPorts();

				// Make power cable labels based on the number of power supplies
				foreach($pportList as $powerPort){
					if(!$hideEmptyPower || $powerPort->ConnectedDeviceID > 0){
						$sheet->getActiveSheet()->SetCellValue('A' . $row, $dev->Label );
						$sheet->getActiveSheet()->SetCellValue('B' . $row, $powerPort->Label );

						if($powerPort->ConnectedDeviceID > 0){
						
							$targetDev=new Device();
							$targetPort=new PowerPorts();

							$targetDev->DeviceID=$powerPort->ConnectedDeviceID;
							$targetDev->GetDevice();

							$targetPort->DeviceID=$targetDev->DeviceID;
							$targetPort->PortNumber=$powerPort->ConnectedPort;
							$targetPort->getPort();

							$sheet->getActiveSheet()->SetCellValue('C' . $row, $targetDev->Label);
							$sheet->getActiveSheet()->SetCellValue('D' . $row, $targetPort->Label);
							$sheet->getActiveSheet()->SetCellValue('H' . $row, __("Power Connection"));
							
						}
						$row++;
					}
				}
			}
			
			foreach ( $portList as $devPort ) {
				// These are created inside the loop, because they need to be clean instances each time
				$targetDev = new Device();
				$targetPort = new DevicePorts();
				
				$color = new ColorCoding();
				$mediaType = new MediaTypes();
				
				if ( $devPort->ConnectedDeviceID > 0 || $devPort->Notes != "" ) {
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
					
					// Always print unspecified media types for those who don't want to specify them
					if ($devPort->MediaID==0 || in_array(''.$devPort->MediaID,$mediaIDList)){
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
					}
				}
				
				if ( $targetDev->DeviceType == "Patch Panel" ) {
					$path = DevicePorts::followPathToEndPoint( $devPort->ConnectedDeviceID, $devPort->ConnectedPort );
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
		}
		
		foreach( range('A','G') as $columnID) {
			$sheet->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
		}

		return $sheet;
	}

?>