<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$device = new Device();
	$devList = $device->GetDevicesbyAge($config->ParameterArray["NewInstallsPeriod"] );

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
	$message = Swift_Message::NewInstance()->setSubject( __("Recent Data Center Installations Report" ) );

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

	$logo=getcwd().'/'.$config->ParameterArray["PDFLogoFile"];
	$logo=$message->embed(Swift_Image::fromPath($logo)->setFilename('logo.png'));

	$htmlMessage = sprintf( "<!doctype html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>ITS Data Center Inventory</title></head><body><div id=\"header\" style=\"padding: 5px 0;background: %s;\"><center><img src=\"%s\"></center></div><div class=\"page\"><p><h3>Installations in the Past 7 Days</h3>\n", $config->ParameterArray["HeaderColor"], $logo );
	
	$htmlMessage .= sprintf( "<p>The following systems have been entered into openDCIM, with an Install Date set to within the past %d days.  Please review these entries to determine if follow-up documentation is required.</p>", $config->ParameterArray["NewInstallsPeriod"] );
	
	
	if ( sizeof( $devList ) == 0 ) {
		$htmlMessage .= "<p>There are no recorded installations within the date range specified.</p>\n";
	} else {
		$cab = new Cabinet();
		$dc = new DataCenter();
		$dept = new Department();
		
		$htmlMessage .= "<table>\n<tr><th>Installed Date</th><th>Reserved</th><th>Data Center</th><th>Location</th><th>Label</th><th>Owner</th></tr>\n";

		foreach ( $devList as $devRow ) {
			$cab->CabinetID = $devRow->Cabinet;
			$cab->GetCabinet();
			
			$dc->DataCenterID = $cab->DataCenterID;
			$dc->GetDataCenter();
			
			$dept->DeptID = $devRow->Owner;
			$dept->GetDeptByID();
			
			$htmlMessage .= sprintf( "<tr><td>%s</td><td>%s</td><td>%s</td><td><a href=\"%s/cabnavigator.php?cabinetid=%d\">%s</a></td><td><a href=\"%s/devices.php?DeviceID=%d\">%s</a></td><td>%s</td></tr>\n", date( "d F Y", strtotime( $devRow->InstallDate ) ), $devRow->Reservation == 1 ? "Y" : "N", $dc->Name, $config->ParameterArray["InstallURL"], $cab->CabinetID, $cab->Location, $config->ParameterArray["InstallURL"], $devRow->DeviceID, $devRow->Label, $dept->Name );
		}
		
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
