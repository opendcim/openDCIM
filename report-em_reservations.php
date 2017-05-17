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
	$message = Swift_Message::NewInstance()->setSubject( __("Data Center Reserved Space Report" ) );

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
	
	$style = "
<style type=\"text/css\">
@media print {
	h2 {
		page-break-before: always;
	}
}
</style>";

	$htmlMessage = sprintf( "<!doctype html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>%s</title>%s</head><body><div id=\"header\" style=\"padding: 5px 0;background: %s;\"><center><img src=\"%s\"></center></div><div class=\"page\"><p>\n", __("Data Center Inventory Reservations"), $style, $config->ParameterArray["HeaderColor"], $logo  );

	$datedList = Device::GetReservationsByDate();
	
	$ownerList = array();
	foreach ( $datedList as $dev ) {
		if ( ! in_array( $dev->Owner, $ownerList ) ) {
			$ownerList[] = $dev->Owner;
		}
	}
	
	if ( sizeof( $datedList ) == 0 ) {
		$htmlMessage .= "<p>" . __("There are no reserved installations within the date range specified") . ".</p>\n";
	} else {
		$totalU = 0;
		$dept = new Department();

		foreach ( $ownerList as $owner ) {
			$deptU = 0;
			$cab = new Cabinet();
			$dc = new DataCenter();
			
			$dept->DeptID = $owner;
			$dept->GetDeptByID();
			
			$resList = Device::GetReservationsByOwner( $owner );
			
			$htmlMessage .= sprintf( "%s %s.", __("Scheduled Installations for Department"), $dept->Name );
			
			$htmlMessage .= "<table>\n<tr><th>Scheduled Date</th><th>Rack Units</th><th>Data Center</th><th>Location</th><th>Label</th></tr>\n";

			foreach ( $resList as $devRow ) {
				$cab->CabinetID = $devRow->Cabinet;
				$cab->GetCabinet();
				
				$dc->DataCenterID = $cab->DataCenterID;
				$dc->GetDataCenter();
				
				$deptU += $devRow->Height;
				
				$htmlMessage .= sprintf( "<tr><td>%s</td><td>%d</td><td>%s</td><td><a href=\"%s/cabnavigator.php?cabinetid=%d\">%s</a></td><td><a href=\"%s/devices.php?DeviceID=%d\">%s</a></td></tr>\n", date( "d F Y", strtotime( $devRow->InstallDate ) ), $devRow->Height, $dc->Name, $config->ParameterArray["InstallURL"], $cab->CabinetID, $cab->Location, $config->ParameterArray["InstallURL"], $devRow->DeviceID, $devRow->Label );
			}
			
			$totalU += $deptU;
			
			$htmlMessage .= sprintf( "</table><h3>%s %s: %d</h3><h2>&nbsp;</h2>\n", __("Total U Reserved for Department"), $dept->Name, $deptU );
		}
	}
	
	$htmlMessage .= sprintf( "<br><h3>%s: %d</h3>\n", __("Total U Reserved in DCIM"), $totalU );

	$message->setBody($htmlMessage,'text/html');

	try {
		$result = $mailer->send( $message );
	} catch( Swift_RfcComplianceException $e) {
		$error .= "Send: " . $e->getMessage() . "<br>\n";
	} catch( Swift_TransportException $e) {
		$error .= "Server: <span class=\"errmsg\">" . $e->getMessage() . "</span><br>\n";
	}
?>
