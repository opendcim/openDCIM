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
	$message = Swift_Message::NewInstance()->setSubject( __("Data Center Disabled SNMP Devices Report" ) );

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
	
	$htmlMessage .= sprintf( "<p>The following devices have been disabled from SNMP polling due to three consecutive failed attempts.  Click on the link provided to re-enable, or click on the 'Enable All' button to re-activate all at once.</p>" );
	
	$dev = new Device();
	$dev->SNMPFailureCount=3;

	$devList = $dev->Search();
	$lastDC = null;
	$lastCabinet = null;
	$urlBase = $config->ParameterArray["InstallURL"];
	
	$exceptionRows = "";
	
	if ( sizeof( $devList ) == 0 ) {
		$htmlMessage .= "<p>There are no devices that qualify for this report.</p>\n";
	} else {
		$cab = new Cabinet();
		$dc = new DataCenter();
		$port = new DevicePorts();
		$dev = new Device();
		
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
			
			$exceptionRows .= sprintf( "<tr><td><a href=\"%sdevices.php?rc&DeviceID=%d\">%s</a></td><td>%s</td><td>%s</td></tr>\n", $urlBase, $devRow->DeviceID, $devRow->Label, $dc->Name, $cab->Location );
		}
	}

	if ( $exceptionRows != "" ) {
		$htmlMessage .= sprintf( "<p><a href=\"%sdevices.php?rcall\">Reset All Devices</a></p>\n", $urlBase );
		$htmlMessage .= "<table border='1'>\n<tr><th>Device Name</th><th>Data Center</th><th>Cabinet</th></tr>\n";
		$htmlMessage .= $exceptionRows;
		$htmlMessage .= "</table>\n";
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
