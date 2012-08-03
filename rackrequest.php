<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );
	require_once( 'swiftmailer/swift_required.php' );
	
	$user=new User();
	
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);
	
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
	$formfix=$error='';	
	$contactList=$contact->GetContactList($facDB);
	$contact->UserID=$user->UserID;
	$contact->GetContactByUserID($facDB);

	//We only need to worry about sending email in the event this is a new submission and no other time.
	if(isset($_POST["action"])){
		if(isset($_REQUEST['requestid']) && $_REQUEST['requestid'] >0){
			$req->RequestID=$_REQUEST['requestid'];
			$req->GetRequest($facDB);

			$contact->ContactID=$req->RequestorID;
			$contact->GetContactByID($facDB);
		}

		$tmpContact->ContactID=$_POST["requestorid"];
		$tmpContact->GetContactByID($facDB);

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
		$message=Swift_Message::NewInstance()->setSubject($config->ParameterArray['MailSubject']);

		// Set from address
		try{		
			$message->setFrom($config->ParameterArray['MailFromAddr']);
		}catch(Swift_RfcComplianceException $e){
			$error.="MailFrom: <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
		}

		// Add rack requestor to the list of recipients
		try{		
			$message->addTo($tmpContact->Email);
		}catch(Swift_RfcComplianceException $e){
			$error.="Check contact details for <a href=\"contacts.php?contactid=$tmpContact->ContactID\">$tmpContact->LastName, $tmpContact->FirstName</a>: <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
		}

		// Add data center team to the list of recipients
		try{		
			$message->addTo($config->ParameterArray['MailToAddr']);
		}catch(Swift_RfcComplianceException $e){
			$error.="Data center team address: <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
		}

		$logo='images/'.$config->ParameterArray["PDFLogoFile"];
		$logo=$message->embed(Swift_Image::fromPath($logo)->setFilename('logo.png'));

		$htmlMessage='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1252"><meta http-equiv="X-UA-Compatible" content="IE=edge"><title>ITS Data Center Inventory</title></head><body><div id="header" style="padding: 5px 0;background: '.$config->ParameterArray["HeaderColor"].';"><center><img src="'.$logo.'"></center></div><div class="page"><p><h3>ITS Facilities Rack Request</h3>'."\n";

		if($_POST['action'] == 'Create'){
			$req->RequestorID=$_POST['requestorid'];
			$req->Label=$_POST['label'];
			$req->SerialNo=$_POST['serialno'];
			$req->MfgDate=$_POST['mfgdate'];
			$req->AssetTag=$_POST['assettag'];
			$req->ESX=$_POST['esx'];
			$req->Owner=$_POST['owner'];
			$req->DeviceHeight=$_POST['deviceheight'];
			$req->EthernetCount=$_POST['ethernetcount'];
			$req->VLANList=$_POST['vlanlist'];
			$req->SANCount=$_POST['sancount'];
			$req->SANList=$_POST['sanlist'];
			$req->DeviceClass=$_POST['deviceclass'];
			$req->DeviceType=$_POST['devicetype'];
			$req->LabelColor=$_POST['labelcolor'];
			$req->CurrentLocation=$_POST['currentlocation'];
			$req->SpecialInstructions=$_POST['specialinstructions'];

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
			try{
				$result=$mailer->send($message);
			}catch(Swift_RfcComplianceException $e){
				$error.="Send: ".$e->getMessage()."<br>\n";
			}
		}elseif(($_POST['action']=='Update Request'||$_POST['action']=='Move to Rack') && (($user->RackRequest && $user->UserID==$contact->UserID)||$user->RackAdmin)){
			$req->RequestorID=$_POST['requestorid'];
			$req->Label=$_POST['label'];
			$req->SerialNo=$_POST['serialno'];
			$req->MfgDate=date('Y-m-d',strtotime($_POST["mfgdate"]));
			$req->AssetTag=$_POST['assettag'];
			$req->ESX=$_POST['esx'];
			$req->Owner=$_POST['owner'];
			$req->DeviceHeight=$_POST['deviceheight'];
			$req->EthernetCount=$_POST['ethernetcount'];
			$req->VLANList=$_POST['vlanlist'];
			$req->SANCount=$_POST['sancount'];
			$req->SANList=$_POST['sanlist'];
			$req->DeviceClass=$_POST['deviceclass'];
			$req->DeviceType=$_POST['devicetype'];
			$req->LabelColor=$_POST['labelcolor'];
			$req->CurrentLocation=$_POST['currentlocation'];
			$req->SpecialInstructions=$_POST['specialinstructions'];

			$req->UpdateRequest($facDB);

			if($user->RackAdmin && $_POST['action']=='Move to Rack'){
				$req->CompleteRequest($facDB);
				
				$dev->Label=$req->Label;
				$dev->SerialNo=$req->SerialNo;
				$dev->MfgDate=$req->MfgDate;
				$dev->InstallDate=date('Y-m-d');
				$dev->AssetTag=$req->AssetTag;
				$dev->ESX=$req->ESX;
				$dev->Owner=$req->Owner;
				$dev->Cabinet=$_POST['cabinetid'];
				$dev->Position=$_POST['position'];
				$dev->Height=$req->DeviceHeight;
				$dev->Ports=$req->EthernetCount;
				$dev->DeviceType=$req->DeviceType;
				$dev->TemplateID=$req->DeviceClass;
				
				$dev->CreateDevice($facDB);
				
				$htmlMessage.="<p>Your request for racking up the device labeled $req->Label has been completed.</p>
				<p>To view your device in its final location <a href=\"https://{$_SERVER['SERVER_NAME']}{$_SERVER['PHP_SELF']}/devices.php?deviceid=$dev->DeviceID\"> this link</a>.</p>
				</body></html>";

				$message->setBody($htmlMessage,'text/html');
				try{
					$result=$mailer->send($message);
				}catch(Swift_RfcComplianceException $e){
					$error.="Send: ".$e->getMessage()."<br>\n";
				}
			
				header('Location: '.redirect("devices.php?deviceid=$dev->DeviceID"));
				exit;
			}
	  }elseif($_POST['action']=='Delete Request'){
		  if($user->RackAdmin||$user->UserID==$contact->UserID){
			$req->DeleteRequest($facDB);
			header('Location: '.redirect('index.php'));
			exit;
		  }else{
			// This should never be hit under normal circumstatnces.
			$error.="You do not have permission to delete this request";
		  }
	   }
	}
	// If requestid is set we are either looking up a request or performing an action on one already. Refresh the object from the DB
	if(isset($_REQUEST['requestid']) && $_REQUEST['requestid']>0){
		$req->RequestID=$_REQUEST['requestid'];
		$req->GetRequest($facDB);
		$formfix="?requestid=$req->RequestID";

		$contact->ContactID=$req->RequestorID;
		$contact->GetContactByID($facDB);
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
		// Disable the form validation so that the delete button will work
		$('input[value|="Delete Request"]').click(function(){
			$('#deviceform').validationEngine('detach');
		});
<?php
	if($user->RackAdmin && ($req->RequestID>0)){
?>
		$('#position').focus(function()	{
			var cab=$("select#cabinetid").val();
			$.get('scripts/ajax_cabinetuse.php?cabinet='+cab, function(data) {
				var rackhtmlleft='';
				var rackhtmlright='';
				$.each(data, function(i,inuse){
					if(inuse){var cssclass='notavail'}else{var cssclass=''};
					rackhtmlleft+='<div>'+i+'</div>';
					rackhtmlright+='<div val='+i+' class="'+cssclass+'"></div>';
				});
				var rackhtml='<div class="table border positionselector"><div><div>'+rackhtmlleft+'</div><div>'+rackhtmlright+'</div></div></div>';
				$('#positionselector').html(rackhtml);
				setTimeout(function(){
					var divwidth=$('.positionselector').width();
					$('#positionselector').width(divwidth);
					$('#cabinetid').focus(function(){$('#positionselector').css({'left': '-1000px'});});
					$('#specialinstructions').focus(function(){$('#positionselector').css({'left': '-1000px'});});
					$('#positionselector').css({'left':(($('#position').position().left)+(divwidth+20))});
					$('#positionselector').mouseleave(function(){
						$('#positionselector').css({'left': '-1000px'});
					});
					$('.positionselector > div > div + div > div').mouseover(function(){
						$('.positionselector > div > div + div > div').each(function(){
							$(this).removeAttr('style');
						});
						var unum=$("#deviceheight").val();
						if(unum>=1 && $(this).attr('class')!='notavail'){
							var test='';
							var background='green';
							// check each element start with pointer
							for (var x=0; x<unum; x++){
								if(x!=0){
									test+='.prev()';
									eval("if($(this)"+test+".attr('class')=='notavail' || $(this)"+test+".length ==0){background='red';}");
									eval("console.log($(this)"+test+".attr('val')+' '+$(this)"+test+".attr('class'))");
								}else{
									if($(this).attr('class')=='notavail'){background='red';}
								}
							}
							test='';
							if(background=='red'){var pointer='default'}else{var pointer='pointer'}
							for (x=0; x<unum; x++){
								if(x!=0){
									test+='.prev()';
									eval("$(this)"+test+".css({'background-color': '"+background+"'})");
									eval("console.log($(this)"+test+".attr('val'))");
								}else{
									$(this).css({'background-color': background, 'cursor': pointer});
									if(background=='green'){
										$(this).click(function(){
											$('#position').val($(this).attr('val'));
											$('#positionselector').css({'left': '-1000px'});
										});
									}
								}
							}
						}
					});
				},100);
			});
		});
<?php
	}
?>
	});
  </script>


</head>
<body>
<div id="header"></div>
<div class="page request">
<?php
    include('sidebar.inc.php');
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Rack Request</h3>
<?php if($error!=""){echo '<fieldset class="exception border error"><legend>Errors</legend>'.$error.'</fieldset>';} ?>
<div class="center"><div>
<div id="positionselector"></div>
<?php
	print "<form name=\"deviceform\" id=\"deviceform\" action=\"{$_SERVER["PHP_SELF"]}$formfix\" method=\"POST\">
	<input type=\"hidden\" name=\"requestid\" value=\"$req->RequestID\">\n";
?>
<div class="table">
	<div>
		<div><label for="requestor">Requestor</label></div>
		<div>
			<select name="requestorid" id="requestorid">
<?php
	foreach($contactList as $tmpContact){
		if($tmpContact->UserID==$contact->UserID){$selected="SELECTED";}else{$selected="";}
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
		<div><input type="text" name="ethernetcount" id="ethernetcount" class="validate[optional,custom[onlyNumberSp],min[1]]" size="15" value="<?php echo ($req->EthernetCount!=0) ? $req->EthernetCount : ''; ?>"></div>
	</div>
	<div>
		<div><label for="vlanlist">VLAN Settings<span>(ie - eth0 on 973, eth1 on 600)</span></label></div>
		<div><input type="text" name="vlanlist" id="vlanlist" class="validate[condRequired[ethernetcount]]" size="50" value="<?php echo $req->VLANList; ?>"></div>
	</div>
	<div>
		<div><label for="sancount">Number of SAN Connections</label></div>
		<div><input type="text" name="sancount" id="sancount" class="validate[optional,custom[onlyNumberSp],min[1]]" size="15" value="<?php echo ($req->SANCount!=0) ? $req->SANCount : ''; ?>"></div>
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
	foreach(array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure') as $devType){
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
		echo '<div><div><label for="cabinetid">Select Rack Location:</label></div><div>'.$cab->GetCabinetSelectList($facDB).'&nbsp;&nbsp;<label for="position">Position:</label> <input type="text" name="position" id="position" size=5></div></div>';
	}
?>
	<div class="caption">
<?php
	if($user->RackRequest||$user->RackAdmin){
		if($req->RequestID >0){
			if($user->RackAdmin||($user->UserID==$contact->UserID)){
				echo '<input type="submit" name="action" value="Update Request">';
				echo '<input type="submit" name="action" value="Delete Request">';
			}
			if($user->RackAdmin){
				echo '<input type="submit" name="action" value="Move to Rack">';
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
