<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	header("Content-type: text/json");

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

		$message->setBody(__("This is a test email sent by an administrator from the openDCIM system at") . ' ' . $_SERVER['SERVER_NAME'],'text/html');

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
		$status["Code"] = 503;
		$status["Error"] = true;
		$status["Message"] = $error;
	} else {
		$status["Code"] = 200;
		$status["Error"] = false;
		$status["Message"] = __("Test email sent.");
	}

	echo json_encode( $status );
?>
