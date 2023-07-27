<?php
	require_once "../db.inc.php";
	require_once "../facilities.inc.php";
	require "../vendor/autoload.php";

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\SMTP;

	header("Content-type: text/html");

	if(isset( $_REQUEST['SMTPServer'] ) && isset( $_REQUEST['SMTPPort'] ) && isset( $_REQUEST['SMTPUser'] ) && isset( $_REQUEST['SMTPPassword'] ) && isset( $_REQUEST['FacMgrMail']) ) {
		$mail = new PHPMailer(true);
		$mail->CharSet = 'UTF-8';
		$mail->SMTPDebug = SMTP::DEBUG_OFF;
		$mail->isSMTP();
		$mail->isHTML(true);
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

		if ( isset( $_REQUEST['MailFrom'])) {
			$mail->setFrom( $_REQUEST['MailFrom'] );
		} else {
			$mail->setFrom( $_REQUEST['FacMgrMail'] );
		}

		if ( isset( $_REQUEST['MailToAddr'])) {
			$mail->addAddress( $_REQUEST['MailToAddr'] );
		} else {
			$mail->addAddress( $_REQUEST['FacMgrMail'] );
		}

		$mail->Subject = __("Test Email from openDCIM");

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

		$mail->Body = $htmlMessage;
		try {
			$mail->send();
		} catch (Exception $e) {
			error_log( "Mailer error: {$mail->ErrorInfo}" );
		}
	} else {
		$error = __("Script called without sufficient parameters.") . "<br>" . print_r( strip_tags($_REQUEST), true );
	}

	if ( isset( $error ) ) {
		$Response = "<h2>" . __("An error has occurred:") . "</h2><p>" . $error . "</p>";
	} else {
		$Response = "<h2>" . __("SMTP Server Connection Test Successful") . "</h2>";
	}

	echo $Response;
?>
