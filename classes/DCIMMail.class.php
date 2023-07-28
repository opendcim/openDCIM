<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class DCIMMail extends PHPMailer {
	public function __construct(){
		$this->init();
	}

	function init(){
		global $config;

		$this->CharSet = 'UTF-8';
		$this->isHTML(true);

		if (count($this->all_recipients) == 0){
			$this->addAddress($config->ParameterArray['FacMgrMail']);
		}
		if ($this->From == ''){
			$this->setFrom( $config->ParameterArray['MailFromAddr'] );
		}
		if ($this->Subject == ''){
			$this->setFrom( $config->ParameterArray['MailSubject'] );
		}

		$this->SMTPDebug = SMTP::DEBUG_OFF;
		$this->isSMTP();
		$this->Host = $config->ParameterArray['SMTPServer'];
		$this->Port = $config->ParameterArray['SMTPPort'];
		$this->SMTPAutoTLS = false;

		// If any port other than 25 is specified, assume encryption and authentication
		if($config->ParameterArray['SMTPPort']!= 25){
			$this->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			$this->SMTPAuth = true;
			$this->Username = $config->ParameterArray['SMTPUser'];
			$this->Password = $config->ParameterArray['SMTPPassword'];
		}
error_log(print_r($this,true));
	}
}
