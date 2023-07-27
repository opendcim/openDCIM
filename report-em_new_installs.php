<?php
	require_once "db.inc.php";
	require_once "facilities.inc.php";
	require __DIR__."/vendor/autoload.php";

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\SMTP;

	$error = "";
	$device = new Device();
	$devList = $device->GetDevicesbyAge($config->ParameterArray["NewInstallsPeriod"] );

	$mail = new PHPMailer(true);
	$mail->CharSet = 'UTF-8';
	$mail->SMTPDebug = SMTP::DEBUG_OFF;
	$mail->isSMTP();
	$mail->Host = $config->ParameterArray['SMTPServer'];
	$mail->Port = $config->ParameterArray['SMTPPort'];
	$mail->SMTPAutoTLS = false;

	// If any port other than 25 is specified, assume encryption and authentication
	if($config->ParameterArray['SMTPPort']!= 25){
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		$mail->SMTPAuth = true;
		$mail->Username = $config->ParameterArray['SMTPUser'];
		$mail->Password = $config->ParameterArray['SMTPPassword'];
	}

	$mail->Subject = $config->ParameterArray['MailSubject'];
	$mail->setFrom( $config->ParameterArray['MailFromAddr'] );
	$mail->isHTML(true);

	$mail->addAttachment( $config->ParameterArray["PDFLogoFile"], "logo.png" );
	$mail->Subject = __("Recent Data Center Installations Report" );

	$mail->addAddress($config->ParameterArray['FacMgrMail']);

	$htmlMessage = sprintf( "<!doctype html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>ITS Data Center Inventory</title></head><body><div id=\"header\" style=\"padding: 5px 0;background: %s;\"><center><img src=\"%s\"></center></div><div class=\"page\"><p><h3>Installations in the Past 7 Days</h3>\n", $config->ParameterArray["HeaderColor"], "logo.png" );
	
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

	$mail->Body = $htmlMessage;
	try {
		$mail->send();
	} catch (Exception $e) {
		error_log( "Mailer error: {$mail->ErrorInfo}" );
	}
?>
