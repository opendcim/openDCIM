<?php
    require_once('db.inc.php');
    require_once('facilities.inc.php');
	require_once('swiftmailer/swift_required.php');
    
    $esx=new ESX();
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
	$message=Swift_Message::NewInstance()->setSubject(_("Virtual Machine Inventory Exception Report"));

	// Set from address
	try{		
		$message->setFrom($config->ParameterArray['MailFromAddr']);
		$message->SetReplyTo($config->ParameterArray["MailToAddr"]);
	}catch(Swift_RfcComplianceException $e){
		$error.=_("MailFrom").": <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
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
		$error.=_("Data center team address").": <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
	}


	// Send email about Virtual Machines that don't have owners assigned
	$esxList=$esx->GetOrphanVMList($facDB);
	if(count($esxList) >0){
		$esxCount=count($esxList);
      
		$htmlMessage="<html>
			<head>
			   <title>"._('Virtual Machine Inventory Exception Report')."</title>
			</head>
			<body>
			<p>"._('This is an automated message from the')." {$config->ParameterArray["OrgName"]} "._('Inventory
			Process.  This process is scheduled to run once each business day.</p>
			<p>The following')." $esxCount "._('Virtual Machines were detected in the environment
			and do not have an associated owner record.  It is assumed that
			these are new Virtual Machines.  Please click on the links below to update
			ownership information.')."</p>
			<p>"._('If the appropriate department is not listed as an option for ownership, please
			send an email to')." {$config->ParameterArray["FacMgrMail"]} "._('to have it added.')."</p>
			<p>
			<table width=\"100%\" border=\"1\" padding=\"0\" bgcolor=white>
			<tr><td>"._('Server Name')."</td><td>"._('VM Name')."</td><td>"._('Status')."</td><td>"._('Last Updated')."</td></tr>";

		foreach($esxList as $esxRow){
			$dev->DeviceID=$esxRow->DeviceID;
			$dev->GetDevice($facDB);
        
			$dept->DeptID=$esxRow->Owner;
			if($dept->DeptID >0){
				$dept->GetDeptByID($facDB);
			}else{
				$dept->Name=_("Unknown");
			}
          
			$htmlMessage.="<tr><td>$dev->Label</td><td><a href=\"".redirect("updatevmowner.php?vmindex=$esxRow->VMIndex")."\">$esxRow->vmName</a></td><td>$esxRow->vmState</td><td>$esxRow->LastUpdated</td></tr>\n";
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
	$esxList=$esx->GetExpiredVMList($config->ParameterArray["VMExpirationTime"],$facDB);
	if(count($esxList) >0){
		$esxCount=count($esxList);
      
		$htmlMessage="<html>
			<head>
			   <title>"._('Virtual Machine Inventory Expiration Report')."</title>
			</head>
			<body>
			<p>"._('This is an automated message from the')." {$config->ParameterArray["OrgName"]} "._('Virtual Machine Inventory
			Process.  This process is scheduled to run once each business day.')."</p>
			<p>"._('The following')." $esxCount "._('Virtual Machines have not been detected within the
			past')." {$config->ParameterArray["VMExpirationTime"]} "._('days and are assumed to be expired.  They are being removed from the
			inventory system.')."</p>
			<table width=\"100%\" border=\"1\" padding=\"0\" bgcolor=white>
			<tr><td>"._('Server Name')."</td><td>"._('VM Name')."</td><td>"._('Status')."</td><td>"._('Last Updated')."</td></tr>";

		foreach($esxList as $esxRow){
			$dev->DeviceID=$esxRow->DeviceID;
			$dev->GetDevice($facDB);
        
			$dept->DeptID=$esxRow->Owner;
			if($dept->DeptID >0){
				$dept->GetDeptByID($facDB);
			}else{
				$dept->Name=_("Unknown");
			}
          
			$htmlMessage.="<tr><td>$dev->Label</td><td>$esxRow->vmName</td><td>$esxRow->vmState</td><td>$esxRow->LastUpdated</td></tr>\n";
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
		$esx->ExpireVMs($config->ParameterArray["VMExpirationTime"],$facDB);
	}

	// output any errors so they might get recorded someplace
   	echo $error; 
?>
