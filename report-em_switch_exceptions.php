<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	// If any port other than 25 is specified, assume encryption and authentication
	if($config->ParameterArray['SMTPPort']!= 25){
		$transport=Swift_SmtpTransport::newInstance()
			->setHost($config->ParameterArray['SMTPServer'])
			->setPort($config->ParameterArray['SMTPPort'])
			->setEncryption('ssl')
			->setUsername($config->ParameterArray['SMTPUser'])
			->setPassword($config->ParameterArray['SMTPPassword']);
	}else{
		$transport=Swift_SmtpTransport::newInstance()
			->setHost($config->ParameterArray['SMTPServer'])
			->setPort($config->ParameterArray['SMTPPort']);
	}

	$mailer = Swift_Mailer::newInstance($transport);
	$message = Swift_Message::NewInstance()->setSubject( __("Data Center Switch Capacity Exceptions Report" ) );

	// Set from address
	try{		
		$message->setFrom($config->ParameterArray['MailFromAddr']);
	}catch(Swift_RfcComplianceException $e){
		$error.=__("MailFrom").": <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
	}

	// Add data center team to the list of recipients
	try{		
		$message->addTo($config->ParameterArray['FacMgrMail']);
	}catch(Swift_RfcComplianceException $e){
		$error.=__("Facility Manager email address").": <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
	}

	$logo=getcwd().'/images/'.$config->ParameterArray["PDFLogoFile"];
	$logo=$message->embed(Swift_Image::fromPath($logo)->setFilename('logo.png'));

	$htmlMessage = sprintf( "<!doctype html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>ITS Data Center Inventory</title></head><body><div id=\"header\" style=\"padding: 5px 0;background: %s;\"><center><img src=\"%s\"></center></div><div class=\"page\"><p>\n", $config->ParameterArray["HeaderColor"], $logo );
	
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

			if ( sizeof( $statusList ) == sizeof( $portList ) && ( sizeof( $portList ) > 0 ) ) {
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

	$message->setBody($htmlMessage,'text/html');

	try {
		$result = $mailer->send( $message );
	} catch( Swift_RfcComplianceException $e) {
		$error .= "Send: " . $e->getMessage() . "<br>\n";
	} catch( Swift_TransportException $e) {
		$error .= "Server: <span class=\"errmsg\">" . $e->getMessage() . "</span><br>\n";
	}
?>
