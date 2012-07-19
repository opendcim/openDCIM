<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );
	require_once( 'swiftmailer/swift_required.php' );
	
	$user = new User();
	
	$user->UserID = $_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );
	
	if(!$user->RackRequest){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$Dept=new Department();
	$cab=new Cabinet();
	$dev=new Device();
	$req=new RackRequest();
	$contact=new Contact();
	$tmpContact=new Contact();
	
	$contactList=$contact->GetContactList($facDB);

	//We only need to worry about sending email in the event this is a new submission and no other time.
	if(isset($_REQUEST["action"])){
		$tmpContact->ContactID=intval($_REQUEST["requestorid"]);
		$tmpContact->GetContactByID($facDB);
		$email=$tmpContact->Email;

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
			
		$message=Swift_Message::NewInstance()
			->setSubject($config->ParameterArray['MailSubject'])
			->setFrom($config->ParameterArray['MailFromAddr'])
			->setTo($email);

		$logo='images/'.$config->ParameterArray["PDFLogoFile"];
		$logo=$message->embed(Swift_Image::fromPath($logo)
			->setFilename('logo.png')
		);

		$htmlMessage='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>ITS Data Center Inventory</title>
	</head>
	<body>
	<div id="header" style="padding: 5px 0;background: #006633;"><center><img src="'.$logo.'"></center></div>
	<div class="page">
	<p>
	<h3>ITS Facilities Rack Request</h3>'."\n";

		if(isset($_REQUEST['action'])&& ($user->WriteAccess && ($_REQUEST['action'] == 'Create'))){
			$req->RequestorID=$_REQUEST['requestorid'];
			$req->Label=$_REQUEST['label'];
			$req->SerialNo=$_REQUEST['serialno'];
			$req->MfgDate=$_REQUEST['mfgdate'];
			$req->AssetTag=$_REQUEST['assettag'];
			$req->ESX=$_REQUEST['esx'];
			$req->Owner=$_REQUEST['owner'];
			$req->DeviceHeight=$_REQUEST['deviceheight'];
			$req->EthernetCount=$_REQUEST['ethernetcount'];
			$req->VLANList=$_REQUEST['vlanlist'];
			$req->SANCount=$_REQUEST['sancount'];
			$req->SANList=$_REQUEST['sanlist'];
			$req->DeviceClass=$_REQUEST['deviceclass'];
			$req->DeviceType=$_REQUEST['devicetype'];
			$req->LabelColor=$_REQUEST['labelcolor'];
			$req->CurrentLocation=$_REQUEST['currentlocation'];
			$req->SpecialInstructions=$_REQUEST['specialinstructions'];

			$req->CreateRequest($facDB);

			$htmlMessage.="<p>Your request for racking up the device labeled $req->Label has been received.
			The Network Operations Center will examine the request and contact you if more information is needed
			before the request can be processed.  You will receive a notice when this request has been completed.
			Please allow up to 2 business days for requests to be completed.</p>

			<p>Your Request ID is $req->RequestID and you may view the request online at
			<a href=\"https://{$_SERVER['SERVER_NAME']}{$_SERVER['PHP_SELF']}?requestid=$req->RequestID\">
			this link</a>.</p>
			
			</body></html>";

			$message->setBody($htmlMessage,'text/html');
			$result=$mailer->send($message);
		}elseif(isset($_REQUEST['action']) && ($user->WriteAccess && ($_REQUEST['action']=='Update Request'))){
			$req->RequestID=$_REQUEST['requestid'];
		  
			$req->Label=$_REQUEST['label'];
			$req->SerialNo=$_REQUEST['serialno'];
			$req->MfgDate=$_REQUEST['mfgdate'];
			$req->AssetTag=$_REQUEST['assettag'];
			$req->ESX=$_REQUEST['esx'];
			$req->Owner=$_REQUEST['owner'];
			$req->DeviceHeight=$_REQUEST['deviceheight'];
			$req->EthernetCount=$_REQUEST['ethernetcount'];
			$req->VLANList=$_REQUEST['vlanlist'];
			$req->SANCount=$_REQUEST['sancount'];
			$req->SANList=$_REQUEST['sanlist'];
			$req->DeviceClass=$_REQUEST['deviceclass'];
			$req->DeviceType=$_REQUEST['devicetype'];
			$req->LabelColor=$_REQUEST['labelcolor'];
			$req->CurrentLocation=$_REQUEST['currentlocation'];
			$req->SpecialInstructions=$_REQUEST['specialinstructions'];

			$req->UpdateRequest($facDB);
	   }elseif(isset($_REQUEST['action']) && ($user->RackAdmin && ($_REQUEST['action']=='Move to Rack'))){
			$req->RequestID=$_REQUEST['requestid'];
			$req->GetRequest($facDB);
			
			$req->CompleteRequest( $facDB );
			
			$dev->Label=$_REQUEST["label"];
			$dev->SerialNo=$_REQUEST["serialno"];
			if($_REQUEST["mfgdate"] >''){
				$dev->MfgDate=date('Y-m-d',strtotime($_REQUEST["mfgdate"]));
			}
			
			$dev->InstallDate=date('Y-m-d');
			$dev->AssetTag=$_REQUEST["assettag"];
			$dev->ESX=$_REQUEST["esx"];
			$dev->Owner=$_REQUEST["owner"];
			$dev->Cabinet=$_REQUEST['cabinetid'];
			$dev->Position=$_REQUEST['position'];
			$dev->Height=$_REQUEST["deviceheight"];
			$dev->Ports=$_REQUEST["ethernetcount"];
			$dev->DeviceType=$_REQUEST["devicetype"];
			$dev->TemplateID=$_REQUEST["deviceclass"];
			
			$dev->CreateDevice($facDB);
			
			$htmlMessage.="<p>Your request for racking up the device labeled $req->Label has been completed.</p>

			<p>To view your device in its final location <a href=\"https://{$_SERVER['SERVER_NAME']}/devices.php?deviceid=$dev->DeviceID\"> this link</a>.</p>
			
			</body></html>";

			$message->setBody($htmlMessage,'text/html');
			$result=$mailer->send($message);
		
			header('Location: '.redirect("devices.php?deviceid=$dev->DeviceID"));
			exit;
	  }elseif(isset($_REQUEST['action']) && ($_REQUEST['action']=='Delete Request')){
			$req->RequestID=$_REQUEST['requestid'];
			$req->GetRequest($facDB);
		
			if($user->RackAdmin){
				$req->DeleteRequest($facDB);
			}
		header('Location: '.redirect('index.php'));
		exit;
	   }
	}
	// If requestid is set we are either looking up a request or performing an action on one already. Refresh the object from the DB
	if(isset($_REQUEST['requestid']) && $_REQUEST['requestid']>0){
		$req->RequestID=$_REQUEST['requestid'];
		$req->GetRequest($facDB);
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Facilities Cabinet Maintenance</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui-1.8.18.custom.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">

  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui-1.8.18.custom.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.timepicker.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>

  <script type="text/javascript">
  	$(document).ready(function() {
		$(function(){
			$('#deviceform').validationEngine({'custom_error_messages' : {
					'#vlanlist' : {
						'required': {
							'message': "You must specify the VLAN information for the ethernet connections."
						}
					},
					'#sanlist' : {
						'condRequired': {
							'message': "You must specify the SAN port information to continue."
						}
					},
					'#currentlocation' : {
						'required': {
							'message': "You must specify the current location of the equipment."
						}
					}
				}	
			});
			$('#mfgdate').datepicker({});
		});
		// Disabled the form validation so that the delete button will work
		$('input[value|="Delete Request"]').click(function(){
			$('#deviceform').validationEngine('detach');
		});
	});
  </script>


</head>
<body>
<div id="header"></div>
<div class="page">
<?php
    include('sidebar.inc.php');
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Rack Request</h3>
<div class="center"><div>
<?php
	print "<form name=\"deviceform\" id=\"deviceform\" action=\"{$_SERVER["PHP_SELF"]}\" method=\"POST\">
	<input type=\"hidden\" name=\"requestid\" value=\"$req->RequestID\">\n";
?>
<div class="table">
	<div>
		<div><label for="requestor">Requestor</label></div>
		<div>
			<select name="requestorid" id="requestorid">
<?php
	foreach($contactList as $tmpContact){
		if($tmpContact->UserID==$user->UserID){$selected="SELECTED";}else{$selected="";}
		print "				<option value=\"$tmpContact->ContactID\" $selected>$tmpContact->LastName, $tmpContact->FirstName</option>";
	}
?>
			</select>
		</div>
	</div>
	<div>
		<div><label for="label">Label</label></div>
		<div><input type="text" name="label" id="label" class="validate[required,minSize[3],maxSize[50]]" size="50" value="<?php echo $req->Label; ?>"></div>
	</div>
	<div>
		<div><label for="labelcolor">Label Color</label></div>
		<div>
			<select name="labelcolor" id="labelcolor">
<?php
	foreach(array('White','Yellow','Red') as $colorCode){
		if($req->LabelColor==$colorCode){$selected='SELECTED';}else{$selected='';}
		print "				<option value=\"$colorCode\" $selected>$colorCode</option>\n";
	}
?>
			</select>
		</div>
	</div>
	<div>
		<div><label for="serialno">Serial Number</label></div>
		<div><input type="text" name="serialno" id="serialno" class="validate[required]" size="50" value="<?php echo $req->SerialNo; ?>"></div>
	</div>
	<div>
		<div><label for="mfgdate">Manufacture Date</label></div>
		<div><input type="text" name="mfgdate" id="mfgdate" size="20" value="<?php echo date( 'm/d/Y', strtotime( $req->MfgDate ) ); ?>"></div>
	</div>
	<div>
		<div><label for="assettag">Asset Tag</label></div>
		<div><input type="text" name="assettag" id="assettag" size="20" value="<?php echo $req->AssetTag; ?>"></div>
	</div>
	<div>
		<div><label for="esx">ESX Server?</label></div>
		<div><select name="esx" id="esx"><option value="1" <?php echo ($req->ESX == 1) ? 'SELECTED' : ''; ?>>True</option><option value="0" <?php echo ($dev->ESX == 0) ? 'SELECTED' : ''; ?>>False</option></select></div>
	</div>
	<div>
		<div><label for="owner">Departmental Owner</label></div>
		<div>
			<select name="owner" id="owner" class="validate[required]">
				<option value=0>Unassigned</option>
<?php
	$deptList = $Dept->GetDepartmentList( $facDB );

	foreach($deptList as $deptRow){
		if($req->Owner==$deptRow->DeptID){$selected='selected';}else{$selected='';}
		print "				<option value=\"$deptRow->DeptID\" $selected>$deptRow->Name</option>\n";
	}
?>
			</select>
		</div>
	</div>
	<div>
		<div><label for="deviceheight">Height</label></div>
		<div><input type="text" name="deviceheight" id="deviceheight" class="validate[required,custom[onlyNumberSp]]" size="15" value="<?php echo $req->DeviceHeight; ?>"></div>
	</div>
	<div>
		<div><label for="ethernetcount">Number of Ethernet Connections</label></div>
		<div><input type="text" name="ethernetcount" id="ethernetcount" class="validate[optional,custom[onlyNumberSp]]" size="15" value="<?php echo $req->EthernetCount; ?>"></div>
	</div>
	<div>
		<div><label for="vlanlist">VLAN Settings<span>(ie - eth0 on 973, eth1 on 600)</span></label></div>
		<div><input type="text" name="vlanlist" id="vlanlist" class="validate[condRequired[ethernetcount]]" size="50" value="<?php echo $req->VLANList; ?>"></div>
	</div>
	<div>
		<div><label for="sancount">Number of SAN Connections</label></div>
		<div><input type="text" name="sancount" id="sancount" class="validate[optional,custom[onlyNumberSp]]" size="15" value="<?php echo $req->SANCount; ?>"></div>
	</div>
	<div>
		<div><label for="sanlist">SAN Port Assignments</label></div>
		<div><input type="text" name="sanlist" id="sanlist" class="validate[condRequired[sancount]]" size="50" value="<?php echo $req->SANList; ?>"></div>
	</div>
	<div>
		<div><label for="deviceclass">Device Class</label></div>
		<div>
			<select name="deviceclass" id="deviceclass">
				<option value=0>Select a template...</option>
<?php
	$templ=new DeviceTemplate();
	$templateList=$templ->GetTemplateList($facDB);
	$mfg=new Manufacturer();
  
	foreach($templateList as $tempRow){
		if($req->DeviceClass==$tempRow->TemplateID){$selected = 'selected';}else{$selected = '';}
		$mfg->ManufacturerID=$tempRow->ManufacturerID;
		$mfg->GetManufacturerByID($facDB);
		print "				<option value=\"$tempRow->TemplateID\" $selected>$mfg->Name - $tempRow->Model</option>\n";
	}
?>
			</select>
		</div>
	</div>
	<div>
		<div><label for="devicetype">Device Type</label></div>
		<div>
			<select name="devicetype" id="devicetype" class="validate[required]">
				<option value=0>Select...</option>
<?php
	foreach(array('Server','Appliance','Storage Array','Switch','Routing Chassis','Patch Panel','Physical Infrastructure') as $devType){
		if($devType==$req->DeviceType){$selected = 'SELECTED';}else{$selected = '';}
		print "				<option value=\"$devType\" $selected>$devType</option>\n";
	}
?>
			</select>
		</div>
	</div>
	<div>
		<div><label for="currentlocation">Current Location</label></div>
		<div><input type="text" name="currentlocation" id="currentlocation" class="validate[required]" size="50" value="<?php echo $req->CurrentLocation; ?>"></div>
	</div>
	<div>
		<div><label for="specialinstructions">Special Instructions</label></div>
		<div><textarea name="specialinstructions" id="specialinstructions" cols=50 rows=5><?php echo $req->SpecialInstructions; ?></textarea></div>
	</div>
<?php
	if($user->RackAdmin && ($req->RequestID>0)){
		$rackHTML=$cab->GetCabinetSelectList($facDB)." Position: <input type=\"text\" name=\"position\" size=5>";
		echo "<div><div><label for=\"cabinetid\">Select Rack Location:</label></div><div>$rackHTML</div></div>\n";
	}
?>
	<div class="caption">
<?php
	if($user->RackRequest){
		if($req->RequestID >0){
			if($user->RackAdmin && ($req->RequestID>0)){
				echo '<input type="submit" name="action" value="Move to Rack">';
			}
			echo '<input type="submit" name="action" value="Update Request">';
			if($user->DeleteAccess){
				echo '<input type="submit" name="action" value="Delete Request">';
			}
		}else{
			echo '<input type="submit" name="action" value="Create">';
		}
	}
?>
	</div>
</div> <!-- END div.table -->
</form>
</div></div>
<a href="index.php">[ Return to Main Menu ]</a>
</div> <!-- END div.main -->
</div> <!-- END div.page -->
</body>
</html>
