<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$subheader=__("Data Center Rack Request");

	if($config->ParameterArray["RackRequests"] != "enabled" || !$person->RackRequest){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$Dept=new Department();
	$cab=new Cabinet();
	$dev=new Device();
	$req=new RackRequest();
	$contact=new People();
	$tmpContact=new People();
	$formfix=$error='';	
	$contactList=$person->GetUserList();
	# defaulting to None
	$validHypervisors=array( "None", "ESX", "ProxMox");

	//We only need to worry about sending email in the event this is a new submission and no other time.
	if(isset($_POST["action"])){
		if(isset($_REQUEST['requestid']) && $_REQUEST['requestid'] >0){
			$req->RequestID=$_REQUEST['requestid'];
			$req->GetRequest();

			$contact->PersonID=$req->RequestorID;
			$contact->GetPerson();
		}

		$tmpContact->PersonID=$_POST["requestorid"];
		$tmpContact->GetPerson();

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
			$error.=__("MailFrom").": <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
		}

		// Add rack requestor to the list of recipients
		try{		
			$message->addTo($tmpContact->Email);
		}catch(Swift_RfcComplianceException $e){
			$error.=__("Check contact details for")." <a href=\"usermgr.php?PersonID=$tmpContact->PersonID\">$tmpContact->LastName, $tmpContact->FirstName</a>: <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
		}

		// Add data center team to the list of recipients
		try{		
			$message->addTo($config->ParameterArray['MailToAddr']);
		}catch(Swift_RfcComplianceException $e){
			$error.=__("Data center team address").": <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
		}

		$logo='images/'.$config->ParameterArray["PDFLogoFile"];
		$logo=$message->embed(Swift_Image::fromPath($logo)->setFilename('logo.png'));

		$htmlMessage='<!doctype html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>ITS Data Center Inventory</title></head><body><div id="header" style="padding: 5px 0;background: '.$config->ParameterArray["HeaderColor"].';"><center><img src="'.$logo.'"></center></div><div class="page"><p><h3>'.__("ITS Facilities Rack Request").'</h3>'."\n";

		if($_POST['action'] == 'Create'){
			$req->RequestorID=$_POST['requestorid'];
			$req->Label=$_POST['label'];
			$req->SerialNo=$_POST['serialno'];
			$req->MfgDate=$_POST['mfgdate'];
			$req->AssetTag=$_POST['assettag'];
			$req->Hypervisor=$_POST['hypervisor'];
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

			$req->CreateRequest();
			$contact->PersonID=$req->RequestorID;
			$contact->GetPerson();

			$htmlMessage.="<p>".sprintf(__('Your request for racking up the device labeled %1$s has been received.
			The Network Operations Center will examine the request and contact you if more information is needed
			before the request can be processed.  You will receive a notice when this request has been completed.
			Please allow up to 2 business days for requests to be completed.'),$req->Label)."</p>

			<p>".sprintf(__('Your Request ID is %1$d and you may view the request online at'),$req->RequestID)."
			<a href=\"https://{$_SERVER['SERVER_NAME']}{$_SERVER['SCRIPT_NAME']}?requestid=$req->RequestID\">
			".__("this link")."</a>.</p>
			
			</body></html>";

			$message->setBody($htmlMessage,'text/html');
			try{
				$result=$mailer->send($message);
			}catch(Swift_RfcComplianceException $e){
				$error.="Send: ".$e->getMessage()."<br>\n";
			}catch(Swift_TransportException $e){
				$error.="Server: <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
			}
		}elseif(($_POST['action']=='Update Request'||$_POST['action']=='Move to Rack') && (($person->RackRequest && $person->UserID==$contact->UserID)||$person->RackAdmin)){
			$req->RequestorID=$_POST['requestorid'];
			$req->Label=$_POST['label'];
			$req->SerialNo=$_POST['serialno'];
			$req->MfgDate=date('Y-m-d',strtotime($_POST["mfgdate"]));
			$req->AssetTag=$_POST['assettag'];
			$req->Hypervisor=$_POST['hypervisor'];
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

			$req->UpdateRequest();

			if($person->RackAdmin && $_POST['action']=='Move to Rack'){
				$req->CompleteRequest();
				
				$dev->Label=$req->Label;
				$dev->SerialNo=$req->SerialNo;
				$dev->MfgDate=$req->MfgDate;
				$dev->InstallDate=date('Y-m-d');
				$dev->AssetTag=$req->AssetTag;
				$dev->Hypervisor=$req->Hypervisor;
				$dev->Owner=$req->Owner;
				$dev->Cabinet=$_POST['CabinetID'];
				$dev->Position=$_POST['position'];
				$dev->Height=$req->DeviceHeight;
				$dev->Ports=$req->EthernetCount;
				$dev->DeviceType=$req->DeviceType;
				$dev->TemplateID=$req->DeviceClass;

				$dev->CreateDevice();
		
				$htmlMessage.="<p>".sprintf(__('Your request for racking up the device labeled %1$s has been completed.'),$req->Label)."</p>";
				$htmlMessage.="<p>".sprintf(__('To view your device in its final location click %1$s'),
				"<a href=\"".redirect("devices.php?DeviceID=$dev->DeviceID")."\"> ".__("this link")."</a>.</p>
				</body></html>");

				$message->setBody($htmlMessage,'text/html');
				try{
					$result=$mailer->send($message);
				}catch(Swift_RfcComplianceException $e){
					$error.="Send: ".$e->getMessage()."<br>\n";
				}catch(Swift_TransportException $e){
					$error.="Server: <span class=\"errmsg\">".$e->getMessage()."</span><br>\n";
				}
			
				header('Location: '.redirect("devices.php?DeviceID=$dev->DeviceID"));
				exit;
			}
	  }elseif($_POST['action']=='Delete Request'){
		  if($person->RackAdmin||$person->UserID==$contact->UserID){
			$req->DeleteRequest();
			header('Location: '.redirect('index.php'));
			exit;
		  }else{
			// This should never be hit under normal circumstatnces.
			$error.=__("You do not have permission to delete this request");
		  }
	   }
	}
	// If requestid is set we are either looking up a request or performing an action on one already. Refresh the object from the DB
	if(isset($_REQUEST['requestid']) && $_REQUEST['requestid']>0){
		$req->RequestID=$_REQUEST['requestid'];
		$req->GetRequest();
		$formfix="?requestid=$req->RequestID";

		$contact->PersonID=$req->RequestorID;
		$contact->GetPerson();
	}
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Facilities Cabinet Maintenance</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">

  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>

  <script type="text/javascript">
  	$(document).ready(function() {
		$(function(){
<?php			
print "			$('#deviceform').validationEngine({'custom_error_messages' : {
					'#vlanlist' : {
						'condRequired': {
							'message': '".__("You must specify the VLAN information for the ethernet connections").".'
						}
					},
					'#sanlist' : {
						'condRequired': {
							'message': '".__("You must specify the SAN port information to continue").".'
						}
					},
					'#currentlocation' : {
						'required': {
							'message': '".__("You must specify the current location of the equipment").".'
						}
					}
				}	
			});";
?>
			$('#mfgdate').datepicker({dateFormat: "yy-mm-dd"});
			$('#deviceclass').change( function(){
				$.get('scripts/ajax_template.php?q='+$(this).val(), function(data) {
					$('#deviceheight').val(data['Height']);
					$('#devicetype').val(data['DeviceType']);
				});
			});
		});
		// Disable the form validation so that the delete button will work
		$('input[value|="Delete Request"]').click(function(){
			$('#deviceform').validationEngine('detach');
		});
<?php
	if($person->RackAdmin && ($req->RequestID>0)){
?>
		$('#position').focus(function()	{
			var cab=$("select#CabinetID").val();
			$.get('scripts/ajax_cabinetuse.php?cabinet='+cab, function(data) {
				var ucount=0;
				$.each(data, function(i,inuse){
					ucount++;
				});
				var rackhtmlleft='';
				var rackhtmlright='';
				for(ucount=ucount; ucount>0; ucount--){
					if(data[ucount]){var cssclass='notavail'}else{var cssclass=''};
					rackhtmlleft+='<div>'+ucount+'</div>';
					rackhtmlright+='<div val='+ucount+' class="'+cssclass+'"></div>';
				}
				var rackhtml='<div class="table border positionselector"><div><div>'+rackhtmlleft+'</div><div>'+rackhtmlright+'</div></div></div>';
				$('#Positionselector').html(rackhtml);
				setTimeout(function(){
					var divwidth=$('.positionselector').width();
					$('#Positionselector').width(divwidth);
					$('#CabinetID').focus(function(){$('#Positionselector').css({'left': '-1000px'});});
					$('#specialinstructions').focus(function(){$('#Positionselector').css({'left': '-1000px'});});
					$('#Positionselector').css({'left':(($('#position').position().left)+(divwidth+20))});
					$('#Positionselector').mouseleave(function(){
						$('#Positionselector').css({'left': '-1000px'});
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
								}else{
									$(this).css({'background-color': background, 'cursor': pointer});
									if(background=='green'){
										$(this).click(function(){
											$('#position').val($(this).attr('val'));
											$('#Positionselector').css({'left': '-1000px'});
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
<?php include( 'header.inc.php' ); ?>
<div class="page request">
<?php
    include('sidebar.inc.php');

echo '<div class="main">';

if($error!=""){echo '<fieldset class="exception border error"><legend>Errors</legend>'.$error.'</fieldset>';}

echo '<div class="center"><div>
<div id="Positionselector"></div>
<form name="deviceform" id="deviceform" action="',$_SERVER["SCRIPT_NAME"],$formfix,'" method="POST">
	<input type="hidden" name="requestid" value="',$req->RequestID,'">';

echo '<div class="table">
	<div>
		<div><label for="requestorid">',__("Requestor"),'</label></div>
		<div>
			<select name="requestorid" id="requestorid">';

	foreach($contactList as $tmpContact){
		if($tmpContact->UserID==$contact->UserID){$selected=" selected";}else{$selected="";}
		print "				<option value=\"$tmpContact->PersonID\"$selected>$tmpContact->LastName, $tmpContact->FirstName</option>";
	}

echo '			</select>
		</div>
	</div>
	<div>
		<div><label for="label">',__("Label").'</label></div>
		<div><input type="text" name="label" id="label" class="validate[required,minSize[3],maxSize[50]]" size="50" value="',$req->Label,'"></div>
	</div>
	<div>
		<div><label for="labelcolor">',__("Label Color").'</label></div>
		<div>
			<select name="labelcolor" id="labelcolor">';

	foreach(array(__("White"),__("Yellow"),__("Red")) as $colorCode){
		if($req->LabelColor==$colorCode){$selected=' selected';}else{$selected='';}
		print "				<option value=\"$colorCode\"$selected>$colorCode</option>\n";
	}

echo '			</select>
		</div>
	</div>
	<div>
		<div><label for="serialno">',__("Serial Number"),'</label></div>
		<div><input type="text" name="serialno" id="serialno" class="validate[required]" size="50" value="',$req->SerialNo,'"></div>
	</div>
	<div>
		<div><label for="mfgdate">',__("Manufacture Date"),'</label></div>
		<div><input type="text" name="mfgdate" id="mfgdate" size="20" value="',date('Y-m-d',strtotime($req->MfgDate)),'"></div>
	</div>
	<div>
		<div><label for="assettag">',__("Asset Tag"),'</label></div>
		<div><input type="text" name="assettag" id="assettag" size="20" value="',$req->AssetTag,'"></div>
	</div>
	<div>
		<div><label for="Hypervisor">',__("Hypervisor?"),'</label></div>
		<div><select name="hypervisor" id="hypervisor">';
	foreach($validHypervisors as $h){
		$selected=($req->Hypervisor==$h)?" selected":"";
		print "\t\t\t<option value=\"$h\" $selected>$h</option>\n";
	}
echo '
		</select></div>
	</div>
	<div>
		<div><label for="owner">',__("Departmental Owner"),'</label></div>
		<div>
			<select name="owner" id="owner" class="validate[required]">
				<option value=0>',__("Unassigned"),'</option>';

	$deptList = $Dept->GetDepartmentList();

	foreach($deptList as $deptRow){
		if($req->Owner==$deptRow->DeptID){$selected=' selected';}else{$selected='';}
		print "				<option value=\"$deptRow->DeptID\"$selected>$deptRow->Name</option>\n";
	}

echo '			</select>
		</div>
	</div>
	<div>
		<div><label for="deviceclass">',__("Device Class"),'</label></div>
		<div>
			<select name="deviceclass" id="deviceclass">
				<option value=0>',__("Select a template"),'...</option>';

	$templ=new DeviceTemplate();
	$templateList=$templ->GetTemplateList();
	$mfg=new Manufacturer();
  
	foreach($templateList as $tempRow){
		if($req->DeviceClass==$tempRow->TemplateID){$selected = ' selected';}else{$selected = '';}
		$mfg->ManufacturerID=$tempRow->ManufacturerID;
		$mfg->GetManufacturerByID();
		print "				<option value=\"$tempRow->TemplateID\"$selected>$mfg->Name - $tempRow->Model</option>\n";
	}

echo '			</select>
		</div>
	</div>
	<div>
		<div><label for="deviceheight">',__("Height"),'</label></div>
		<div><input type="text" name="deviceheight" id="deviceheight" class="validate[required,custom[onlyNumberSp]]" size="15" value="',$req->DeviceHeight,'"></div>
	</div>
	<div>
		<div><label for="ethernetcount">',__("Number of Ethernet Connections"),'</label></div>
		<div><input type="text" name="ethernetcount" id="ethernetcount" class="validate[optional,custom[onlyNumberSp],min[1]]" size="15" value="'.(($req->EthernetCount!=0) ? $req->EthernetCount : '').'"></div>
	</div>
	<div>
		<div><label for="vlanlist">',__("VLAN Settings"),'<span>(ie - eth0 on 973, eth1 on 600)</span></label></div>
		<div><input type="text" name="vlanlist" id="vlanlist" class="validate[condRequired[ethernetcount]]" size="50" value="',$req->VLANList,'"></div>
	</div>
	<div>
		<div><label for="sancount">',__("Number of SAN Connections"),'</label></div>
		<div><input type="text" name="sancount" id="sancount" class="validate[optional,custom[onlyNumberSp],min[1]]" size="15" value="'.(($req->SANCount!=0) ? $req->SANCount : '').'"></div>
	</div>
	<div>
		<div><label for="sanlist">',__("SAN Port Assignments"),'</label></div>
		<div><input type="text" name="sanlist" id="sanlist" class="validate[condRequired[sancount]]" size="50" value="',$req->SANList,'"></div>
	</div>

	<div>
		<div><label for="devicetype">',__("Device Type"),'</label></div>
		<div>
			<select name="devicetype" id="devicetype" class="validate[required]">
				<option value=0>',__("Select"),'...</option>
				<option value="Server"'.(($req->DeviceType=="Server")?' selected':'').'>',__("Server"),'</option>
				<option value="Appliance"'.(($req->DeviceType=="Appliance")?' selected':'').'>',__("Appliance"),'</option>
				<option value="Storage Array"'.(($req->DeviceType=="Storage Array")?' selected':'').'>',__("Storage Array"),'</option>
				<option value="Switch"'.(($req->DeviceType=="Switch")?' selected':'').'>',__("Switch"),'</option>
				<option value="Chassis"'.(($req->DeviceType=="Chassis")?' selected':'').'>',__("Chassis"),'</option>
				<option value="Patch Panel"'.(($req->DeviceType=="Patch Panel")?' selected':'').'>',__("Patch Panel"),'</option>
				<option value="Physical Infrastructure"'.(($req->DeviceType=="Physical Infrastructure")?' selected':'').'>',__("Physical Infrastructure"),'</option>
			</select>
		</div>
	</div>
	<div>
		<div><label for="currentlocation">',__("Current Location"),'</label></div>
		<div><input type="text" name="currentlocation" id="currentlocation" class="validate[required]" size="50" value="',$req->CurrentLocation,'"></div>
	</div>
	<div>
		<div><label for="specialinstructions">',__("Special Instructions"),'</label></div>
		<div><textarea name="specialinstructions" id="specialinstructions" cols=50 rows=5>',$req->SpecialInstructions,'</textarea></div>
	</div>';

	if($person->RackAdmin && ($req->RequestID>0)){
		echo '<div><div><label for="CabinetID">',__("Select Rack Location"),':</label></div><div>'.$cab->GetCabinetSelectList().'&nbsp;&nbsp;<label for="position">',__("Position"),':</label> <input type="text" name="position" id="position" size=5></div></div>';
	}
?>
	<div class="caption">
<?php
	if($person->RackRequest||$person->RackAdmin){
		if($req->RequestID >0){
			if($person->RackAdmin||($person->UserID==$contact->UserID)){
				echo '<button type="submit" name="action" value="Update Request">',__("Update Request"),'</button>';
				echo '<button type="submit" name="action" value="Delete Request">',__("Delete Request"),'</button>';
			}
			if($person->RackAdmin){
				echo '<button type="submit" name="action" value="Move to Rack">',__("Move to Rack"),'</button>';
			}
		}else{
			echo '<button type="submit" name="action" value="Create">',__("Create"),'</button>';
		}
	}
?>
	</div>
</div> <!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div> <!-- END div.main -->
</div> <!-- END div.page -->
</body>
</html>
