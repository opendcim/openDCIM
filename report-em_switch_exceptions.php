<?php
	require_once "db.inc.php";
	require_once "facilities.inc.php";
	require __DIR__."/vendor/autoload.php";

	$mail = new DCIMMail(true);
	$mail->Subject = __("Data Center Switch Capacity Report" );
	$mail->addAttachment( $config->ParameterArray["PDFLogoFile"], "logo.png" );

	$htmlMessage = sprintf( "<!doctype html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>ITS Data Center Inventory</title></head><body><div id=\"header\" style=\"padding: 5px 0;background: %s;\"><center><img src=\"%s\"></center></div><div class=\"page\"><p>\n", $config->ParameterArray["HeaderColor"], "logo.png" );
	
	$htmlMessage .= sprintf( "<p>The following switches are near full capacity per documentation.</p>" );
	
	$devList = Device::GetSwitchesToReport();
	$lastDC = null;
	$lastCabinet = null;
	$urlBase = $config->ParameterArray["InstallURL"];
	$threshold = intval($config->ParameterArray["NetworkThreshold"]) / 100;
	
	if ( sizeof( $devList ) == 0 ) {
		$htmlMessage .= "<p>There are no switches that qualify for this report.</p>\n";
	} else {
		$cab = new Cabinet();
		$dc = new DataCenter();
		$port = new DevicePorts();
		$dev = new Device();
		
		$exceptionRows = "";
		$mismatchRows = "";

		foreach ( $devList as $devRow ) {
			if ( $devRow->Cabinet != $lastCabinet ) {
				$cab->CabinetID = $devRow->Cabinet;
				$cab->GetCabinet();
				$lastCabinet = $cab->CabinetID;
				$cabinet = $cab->Location;
			}
			
			if ( $cab->DataCenterID != $lastDC ) {
				$dc->DataCenterID = $cab->DataCenterID;
				$dc->GetDataCenter();
				$lastDC = $dc->DataCenterID;
				$dataCenter = $dc->Name;
			}
			
			$port->DeviceID = $devRow->DeviceID;
			$activeCount = intval( $port->getActivePortCount() );
			
			$portList = $port->getPorts();	
			$statusList = SwitchInfo::getPortStatus( $devRow->DeviceID );

			if ( $statusList && sizeof( $statusList ) == sizeof( $portList ) && ( sizeof( $portList ) > 0 ) ) {
				for ( $n = 0; $n <= sizeof( $portList ); $n++ ) {
					// The return from getPorts() is not a sequential array, it's associative, so you can't iterate through by number
					$currPort = array_shift( $portList );
					if ( ( $statusList[$n+1] == "up" && ( $currPort->Notes=="" && $currPort->ConnectedDeviceID == null )) || ( $statusList[$n+1] == "down" && ( $currPort->Notes!="" || $currPort->ConnectedDeviceID != null )) ) {
						if ( $currPort->ConnectedDeviceID > 0 ) {
							$dev->DeviceID = $currPort->ConnectedDeviceID;
							$dev->GetDevice();
							$devAnchor = "<a href=\"" . $urlBase . "devices.php?DeviceID=" . $dev->DeviceID . "\">" . $dev->Label . "</a>";
							$port->DeviceID = $currPort->ConnectedDeviceID;
							$port->PortNumber =$currPort->ConnectedPort;
							$port->getPort();
							$portName = $port->Label;
						} else {
							$devAnchor = "&nbsp;";
							$portName = "&nbsp;";
						}
						
						$exceptionRows .= sprintf( "<tr><td><a href=\"%sdevices.php?DeviceID=%d\">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n", $urlBase, $devRow->DeviceID, $devRow->Label, $currPort->Label, $devAnchor, $portName, $currPort->Notes, $statusList[$n+1] );
					}
				}
			}

			if ( $activeCount >= floor( $devRow->Ports * $threshold ) ) {
				$mismatchRows .= sprintf( "<tr><td>%s</td><td>%s</td><td><a href=\"%sdevices.php?DeviceID=%d\">%s</a></td><td>%d</td><td>%d</td></tr>\n", $dataCenter, $cabinet, $urlBase, $devRow->DeviceID, $devRow->Label, $devRow->Ports, $activeCount );
				$dataCenter = "&nbsp;";
				$cabinet = "&nbsp;";
			}
		}

		if ( $mismatchRows != "" ) {
			$htmlMessage .= "<table border='1'>\n<tr><th>Data Center</th><th>Cabinet</th><th>Device Name</th><th>Total Ports</th><th>Documented Ports</th></tr>\n";
			$htmlMessage .= $mismatchRows;
			$htmlMessage .= "</table>\n";
		}
		
		if ( $exceptionRows != "" ) {
			$htmlMessage .= "<p>The following ports specifically have an exception between documentation and SNMP information.</p>";
			$htmlMessage .= "<table border='1'>\n<tr><th>Device Name</th><th>Port</th><th>Documented Device</th><th>Documented Port</th><th>Notes</th><th>Link Status</th></tr>\n";
			$htmlMessage .= $exceptionRows;
			$htmlMessage .= "</table>\n";
		}
	}

	$mail->Body = $htmlMessage;
	try {
		$mail->send();
	} catch (Exception $e) {
		error_log( "Mailer error: {$mail->ErrorInfo}" );
	}
?>
