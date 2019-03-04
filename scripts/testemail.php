<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	header("Content-type: text/html");

	if(isset( $_REQUEST['SMTPServer'] ) && isset( $_REQUEST['SMTPPort'] ) && isset( $_REQUEST['SMTPUser'] ) && isset( $_REQUEST['SMTPPassword'] ) && isset( $_REQUEST['FacMgrMail']) ) {
		if ( $_REQUEST['SMTPPort'] != 25 ) {
			$transport=Swift_SmtpTransport::newInstance()
				->setHost($_REQUEST['SMTPServer'])
				->setPort($_REQUEST['SMTPPort'])
				->setEncryption('ssl')
				->setUsername($_REQUEST['SMTPUser'])
				->setPassword($_REQUEST['SMTPPassword']);
		} else {
			$transport=Swift_SmtpTransport::newInstance()
				->setHost($_REQUEST['SMTPServer'])
				->setPort($_REQUEST['SMTPPort']);
		}

		$mailer = Swift_Mailer::newInstance($transport);
		$message = Swift_Message::NewInstance()->setSubject( __("openDCIM Test Message" ) );

		if ( isset( $_REQUEST['MailFrom'])) {
			$mailFrom = $_REQUEST['MailFrom'];
		} else {
			$mailFrom = $_REQUEST['FacMgrMail'];
		}

		try{		
			$message->setFrom($mailFrom);
		}catch(Swift_RfcComplianceException $e){
			$error.=__("MailFrom").": <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
		}

		if ( isset( $_REQUEST['MailToAddr'])) {
			$mailTo = $_REQUEST['MailToAddr'];
		} else {
			$mailTo = $_REQUEST['FacMgrMail'];
		}

		try{		
			$message->addTo($mailTo);
		}catch(Swift_RfcComplianceException $e){
			$error.=__("MailTo").": <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
		}

		$style = "
<style type=\"text/css\">
@media print {
	h2 {
		page-break-before: always;
	}
}
</style>";

		$htmlMessage = sprintf( "<!doctype html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>%s</title>%s</head><body><div id=\"header\" style=\"padding: 5px 0;background: %s;\"><center></center></div><div class=\"page\"><p>\n", __("Data Center SMTP Test"), $style, $config->ParameterArray["HeaderColor"]  );

		$htmlMessage .= __("<p>This is a test email sent by an administrator from the openDCIM system at") . ' ' . $_SERVER['SERVER_NAME'] . '</p>';

		$message->setBody($htmlMessage,'text/html');

		try {
			$result = $mailer->send( $message );
		} catch( Swift_RfcComplianceException $e) {
			$error .= "Send: " . $e->getMessage() . "<br>\n";
		} catch( Swift_TransportException $e) {
			$error .= "Server: <span class=\"errmsg\">" . $e->getMessage() . "</span><br>\n";
		}
	} else {
		$error = __("Script called without sufficient parameters.") . "<br>" . print_r( $_REQUEST, true );
	}

	if ( isset( $error ) ) {
		$Response = "<h2>" . __("An error has occurred:") . "</h2><p>" . $error . "</p>";
	} else {
		$Response = "<h2>" . __("SMTP Server Connection Test Successful") . "</h2>";
	}

	echo $Response;
?>
