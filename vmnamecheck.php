<?php
    require_once('db.inc.php');
    require_once('facilities.inc.php');
    
    $vm=new VM();
    $dev=new Device();
    $dept=new Department();

	$error="";

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

	$mailer=Swift_Mailer::newInstance($transport);
	$message=Swift_Message::NewInstance()->setSubject(__("Virtual Machine Inventory Exception Report"));

	// Set from address
	try{		
		$message->setFrom($config->ParameterArray['MailFromAddr']);
		$message->SetReplyTo($config->ParameterArray["MailToAddr"]);
	}catch(Swift_RfcComplianceException $e){
		$error.=__("MailFrom").": <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
	}

	// Add people to recipient list
	try{		
		$message->setTo($config->ParameterArray['MailToAddr']);
		/* // Add additional recipients below this section using the following examples
		 * // Using addTo() to add recipients iteratively
		 * $message->addTo('person1@example.org');
		 * $message->addTo('person2@example.org', 'Person 2 Name');
		 */

	}catch(Swift_RfcComplianceException $e){
		$error.=__("Data center team address").": <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
	}

	$logo=getcwd().'/'.$config->ParameterArray["PDFLogoFile"];
	$logo=$message->embed(Swift_Image::fromPath($logo)->setFilename('logo.png'));
	
	$style = "
<style type=\"text/css\">
@media print {
	h2 {
		page-break-before: always;
	}
}
</style>";

	// Send email about Virtual Machines that don't have owners assigned
	$vmList=$vm->GetOrphanVMList();
	if(count($vmList) >0){
		$vmCount=count($vmList);

		$htmlMessage = sprintf( "<!doctype html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>%s</title>%s</head><body><div id=\"header\" style=\"padding: 5px 0;background: %s;\"><center><img src=\"%s\"></center></div><div class=\"page\"><p>\n", __("Virtual Machine Inventory Exception Report"), $style, $config->ParameterArray["HeaderColor"], $logo  );
		$htmlMessage.="<p>".__("This is an automated message from the")." {$config->ParameterArray["OrgName"]} ".__("Inventory
			Process.  This process is scheduled to run once each business day.</p>
			<p>The following")." $vmCount ".__("Virtual Machines were detected in the environment
			and do not have an associated owner record.  It is assumed that
			these are new Virtual Machines.  Please click on the links below to update
			ownership information.")."</p>
			<p>".__("If the appropriate department is not listed as an option for ownership, please
			send an email to")." {$config->ParameterArray["FacMgrMail"]} ".__("to have it added.")."</p>
			<p>
			<table width=\"100%\" border=\"1\" padding=\"0\" bgcolor=white>
			<tr><td>".__("Server Name")."</td><td>".__("VM Name")."</td><td>".__("Status")."</td><td>".__("Last Updated")."</td></tr>";

		foreach($vmList as $vmRow){
			$dev->DeviceID=$vmRow->DeviceID;
			$dev->GetDevice();
        
			$dept->DeptID=$vmRow->Owner;
			if($dept->DeptID >0){
				$dept->GetDeptByID();
			}else{
				$dept->Name=__("Unknown");
			}
          
			$htmlMessage.="<tr><td>$dev->Label</td><td><a href=\"".$config->ParameterArray['InstallURL']."updatevmowner.php?vmindex=$vmRow->VMIndex"."\">$vmRow->vmName</a></td><td>$vmRow->vmState</td><td>$vmRow->LastUpdated</td></tr>\n";
		}
      
		$htmlMessage.="</table></body></html>";

		$message->setBody($htmlMessage,'text/html');
		try{
			$result=$mailer->send($message);
		}catch(Swift_RfcComplianceException $e){
			$error.="Send: ".$e->getMessage()."<br>\n";
		}catch(Swift_TransportException $e){
			$error.="Server: <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
		}
    }

	// Send email about Virtual Machines that are going to be pruned from inventory
	$vmList=$vm->GetExpiredVMList($config->ParameterArray["VMExpirationTime"]);
	if(count($vmList) >0){
		$vmCount=count($vmList);

      		$htmlMessage = sprintf( "<!doctype html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>%s</title>%s</head><body><div id=\"header\" style=\"padding: 5px 0;background: %s;\"><center><img src=\"%s\"></center></div><div class=\"page\"><p>\n", __("Virtual Machine Inventory Expiration Report"), $style, $config->ParameterArray["HeaderColor"], $logo  );
		$htmlMessage.="<p>".__("This is an automated message from the")." {$config->ParameterArray["OrgName"]} ".__("Virtual Machine Inventory
			Process.  This process is scheduled to run once each business day.")."</p>
			<p>".__("The following")." $vmCount ".__("Virtual Machines have not been detected within the
			past")." {$config->ParameterArray["VMExpirationTime"]} ".__("days and are assumed to be expired.  They are being removed from the
			inventory system.")."</p>
			<table width=\"100%\" border=\"1\" padding=\"0\" bgcolor=white>
			<tr><td>".__("Server Name")."</td><td>".__("VM Name")."</td><td>".__("Status")."</td><td>".__("Last Updated")."</td></tr>";

		foreach($vmList as $vmRow){
			$dev->DeviceID=$vmRow->DeviceID;
			$dev->GetDevice();
        
			$dept->DeptID=$vmRow->Owner;
			if($dept->DeptID >0){
				$dept->GetDeptByID();
			}else{
				$dept->Name=__("Unknown");
			}
          
			$htmlMessage.="<tr><td>$dev->Label</td><td>$vmRow->vmName</td><td>$vmRow->vmState</td><td>$vmRow->LastUpdated</td></tr>\n";
		}
      
		$htmlMessage.="</table></body></html>";

		$message->setBody($htmlMessage,'text/html');
		try{
			$result=$mailer->send($message);
		}catch(Swift_RfcComplianceException $e){
			$error.="Send: ".$e->getMessage()."<br>\n";
		}catch(Swift_TransportException $e){
			$error.="Server: <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
		}

		// Delete 'em
		$vm->ExpireVMs($config->ParameterArray["VMExpirationTime"]);
	}

	// output any errors so they might get recorded someplace
   	echo $error; 
?>
