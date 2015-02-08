<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

/*
	Devices rewrite for use with the new power cords table. Making
	changes here so that devices.php isn't broken to shit and back
	in the mean time.
*/



	$subheader=__("Data Center Device Detail");

	$dev=new Device();
	$cab=new Cabinet();
	$contact=new Contact();

	$taginsert="";

	// Ajax functions
	// SNMP Test
	if(isset($_POST['snmptest'])){
		$snmpresults=snmprealwalk($_POST['ip'],$_POST['community'],'1.3.6.1.2.1.1');
		if($snmpresults){
			foreach($snmpresults as $oid => $value){
				print "$oid => $value <br>\n";
			}
		}else{
			print __("Something isn't working correctly");
		}
		exit;
	}

	// Get CDU uptime
	if(isset($_POST['cduuptime'])){
		$pdu=new PowerDistribution();
		$pdu->PDUID=$_POST['deviceid'];
		
		echo $pdu->GetSmartCDUUptime();
		exit;
	}

	// Get log entries
	if(isset($_POST['logging'])){
		$dev->DeviceID=$_POST['devid'];
		$actions=array();
		if($dev->GetDevice()){
			$actions=LogActions::GetLog($dev,false);
		}
		header('Content-Type: application/json');
		echo json_encode($actions);
		exit;
	}
	// Get cabinet height
	if(isset($_POST['cab'])){
		$cab->CabinetID=$_POST['cab'];
		$cab->GetCabinet();
		echo $cab->CabinetHeight;
		exit;
	}
	// Get list of color codes
	if(isset($_GET['cc'])){
		header('Content-Type: application/json');
		echo json_encode(ColorCoding::GetCodeList());
		exit;
	}
	// Get list of media types
	if(isset($_GET['mt'])){
		header('Content-Type: application/json');
		echo json_encode(MediaTypes::GetMediaTypeList());
		exit;
	}
	// Get list of name patterns
	if(isset($_GET['spn'])){
		header('Content-Type: application/json');
		$PortNamePatterns=array();
		if(isset($_GET['power'])){
			foreach(array('PS(1)','R(1)','Custom',__("From Template")) as $pattern){
				$PortNamePatterns[]['Pattern']=$pattern;
			}
		}else{
			foreach(array('NIC(1)','Port(1)','Fa/(1)','Gi/(1)','Ti/(1)','Custom',__("From Template")) as $pattern){
				$PortNamePatterns[]['Pattern']=$pattern;
			}
		}
		echo json_encode($PortNamePatterns);
		exit;
	}
	// Get connection path for a patch panel connection
	if(isset($_GET['path'])){
		$path=DevicePorts::followPathToEndPoint($_GET['ConnectedDeviceID'], $_GET['ConnectedPort']);

		foreach($path as $port){
			$dev->DeviceID=$port->DeviceID;
			$dev->GetDevice();
			$port->DeviceName=$dev->Label;
		}

		header('Content-Type: application/json');
		echo json_encode($path);
		exit;
	}

	// This will allow any jackass to certify an audit but the function is hidden and 
	// this is the type of function that will be fixed with the API so i'm not fixing it
	// as long as logging is enabled we'll know who triggered it.
	if(isset($_POST['audit'])){
		$dev->DeviceID=$_POST['audit'];
		$dev->Audit();

		$dev->AuditStamp=date('r',strtotime($dev->AuditStamp));
		header('Content-Type: application/json');
		echo json_encode($dev);
		exit;
	};

	if(isset($_POST['olog'])){
		$dev->DeviceID=$_POST['devid'];
		$dev->GetDevice();
		$dev->OMessage=sanitize($_POST['olog']);
		$tmpDev=new Device();
		$tmpDev->DeviceID=$dev->DeviceID;
		$tmpDev->GetDevice();
		$return=(class_exists('LogActions'))?LogActions::LogThis($dev,$tmpDev):false;
		header('Content-Type: application/json');
		echo json_encode($return);
		exit;
	};

	// Set all ports to the same label pattern, media type or color code
	if(isset($_POST['setall'])){
		$portnames=array();
		if(isset($_POST['spn']) && strlen($_POST['spn'])>0){
			// Special Condition to load ports from the device template and use those names
			if($_POST['spn']==__("From Template")){
				$dev->DeviceID=$_POST['devid'];
				$dev->GetDevice();
				$ports=(isset($_POST['power']))?new TemplatePowerPorts():new TemplatePorts();
				$ports->TemplateID=$dev->TemplateID;
				foreach($ports->getPorts() as $pn => $portobject){
					$portnames[$pn]=$portobject->Label;
				}
			}else{
				//using premade patterns if the input differs and causes an error then fuck em
				list($result, $msg, $idx) = parseGeneratorString($_POST['spn']);
				if($result){
					$dev->DeviceID=$_POST['devid'];
					$dev->GetDevice();
					$portnames=generatePatterns($result, (isset($_POST['power']))?$dev->PowerSupplyCount:$dev->Ports);
					// generatePatterns starts the index at 0, it's more useful to us starting at 1
					array_unshift($portnames, null);
				}
			}
		}
		// Make a new method to set all the ports to a media type?
		$blurg=(isset($_POST['power']))?PowerPorts::getPortList($_POST['devid']):DevicePorts::getPortList($_POST['devid']);
		foreach($blurg as $portnum => $port){
			$port->Label=(isset($_POST['spn']) && (($_POST['setall']=='true' && count($portnames)>0) || (count($portnames)>0 && strlen($port->Label)==0)))?$portnames[abs($port->PortNumber)]:$port->Label;
			if(!isset($_POST['power'])){
				$port->MediaID=(($_POST['setall']=='true' || $port->MediaID==0) && isset($_POST['mt']) && ($_POST['setall']=='true' || intval($_POST['mt'])>0))?$_POST['mt']:$port->MediaID;
				$port->ColorID=(($_POST['setall']=='true' || $port->ColorID==0) && isset($_POST['cc']) && ($_POST['setall']=='true' || intval($_POST['cc'])>0))?$_POST['cc']:$port->ColorID;
			}
			$port->updatePort();
			// Update the other side to keep media types in sync if it is connected same
			// rule applies that it will only be set if it is currently unset
			if($port->ConnectedDeviceID!='NULL'){
				$port->DeviceID=$port->ConnectedDeviceID;
				$port->PortNumber=$port->ConnectedPort;
				$port->getPort();
				if(!isset($_POST['power'])){
					$port->MediaID=(($_POST['setall']=='true' || $port->MediaID==0) && isset($_POST['mt']) && ($_POST['setall']=='true' || intval($_POST['mt'])>0))?$_POST['mt']:$port->MediaID;
					$port->ColorID=(($_POST['setall']=='true' || $port->ColorID==0) && isset($_POST['cc']) && ($_POST['setall']=='true' || intval($_POST['cc'])>0))?$_POST['cc']:$port->ColorID;
				}
				$port->updatePort();
			}
		}
		// Return all the ports for the device then eval just the MT and CC
		$dp=(isset($_POST['power']))?new PowerPorts():new DevicePorts();
		$dp->DeviceID=$_POST['devid'];
		header('Content-Type: application/json');
		$ports=array(
			'mt' => MediaTypes::GetMediaTypeList(),
			'cc' => ColorCoding::GetCodeList(),
			'ports' => $dp->getPorts()
			);
		echo json_encode($ports);
		exit;
	}
	if(isset($_POST['fp'])){
		$dev->DeviceID=$_POST['devid'];
		$dev->GetDevice();
		if($dev->Rights=="Write"){
			if($_POST['fp']==''){ // querying possible first ports
				$portCandidates=SwitchInfo::findFirstPort($dev->DeviceID);
				if(count($portCandidates)>0){
					foreach($portCandidates as $id => $portdesc){
						$checked=($id==$dev->FirstPortNum)?' checked':'';
						print '<input type="radio" name="firstportnum" id="fp'.$id.'" value="'.$id.'"'.$checked.'><label for="fp'.$id.'">'.$portdesc.'</label><br>';
					}
				}else{
					print __("ERROR: No ports found");
				}
			}else{ // setting first port
				$dev->FirstPortNum=$_POST['fp'];
				if($dev->UpdateDevice()){
					echo 'Updated';
				}else{
					echo 'Failure';
				}
			}
		}else{
			// The button to trigger this function is hidden if they don't have rights
			// but users aren't to be trusted.
			echo 'Failure';
		}
		exit;
	};
	if(isset($_POST['swdev'])){
		$dev->DeviceID=$_POST['swdev'];
		$dev->GetDevice();
		if($dev->Rights=="Write"){
			if(isset($_POST['saveport'])){
				$dp=new DevicePorts();
				$dp->DeviceID=$_POST['swdev'];
				$dp->PortNumber=$_POST['pnum'];
				$dp->Label=$_POST['pname'];
				$dp->MediaID=$_POST['porttype'];
				$dp->ColorID=$_POST['portcolor'];
				$dp->Notes=$_POST['cnotes'];
				$dp->ConnectedDeviceID=$_POST['cdevice'];
				$dp->ConnectedPort=$_POST['cdeviceport'];

				if($dp->updatePort()){
					// when updating the media type on a rear port update the mediatype on the front port as well to make sure they match.
					if($dp->PortNumber<0){
						$dp->PortNumber=abs($dp->PortNumber);
						$dp->GetPort();
						$dp->MediaID=$_POST['porttype'];
						$dp->ColorID=$_POST['portcolor'];
						$dp->updatePort();
					}
					echo 1;
				}else{
					echo 0;
				}
				exit;
			}
			if(isset($_POST['delport'])){
				$dp=new DevicePorts();
				$dp->DeviceID=$_POST['swdev'];
				$dp->PortNumber=$_POST['pnum'];
				$ports=end($dp->getPorts());
				function updatedevice($devid){
					$dev=new Device();
					$dev->DeviceID=$devid;
					$dev->GetDevice();
					$dev->Ports=$dev->Ports-1;
					$dev->UpdateDevice();
				}
				// remove the selected port then shuffle the data to fill the hole if needed
				if($ports->PortNumber!=$dp->PortNumber){
					foreach($ports as $i=>$prop){
						if($i!="PortNumber"){
							$dp->$i=$prop;
						}
					}
					if($dp->updatePort()){
						if($ports->removePort()){
							updatedevice($dp->DeviceID);
							echo 1;
							exit;
						}
					}
					echo 0;
				}else{ // Last available port. just delete it.
					if($dp->removePort()){
						updatedevice($dp->DeviceID);
						echo 1;
					}else{
						echo 0;
					}
				}
				exit;
			}
			// Attach all rear ports of patch panel to another patch panel
			if(isset($_POST['rear']) && isset($_POST['cdevice'])){
					$ConnectTo=new Device();
					$ConnectTo->DeviceID=$_POST['cdevice'];
					// error out if connecting device doesn't exist
					if(!$ConnectTo->GetDevice() && $_POST['cdevice']!='clear'){
						echo 'false';
						exit;
					}

					$cp=new DevicePorts();
					$cp->DeviceID=$ConnectTo->DeviceID;
					$cp=$cp->getPorts();
					$dp=new DevicePorts();
					$dp->DeviceID=$dev->DeviceID;
					foreach($dp->getPorts() as $index => $port){
						if($port->PortNumber<0){
							if($_POST['cdevice']=='clear' && $_POST['override']=='true'){
								$port->removeConnection();
							}elseif(isset($cp[$port->PortNumber]) && (is_null($port->ConnectedDeviceID) || (!is_null($port->ConnectedDeviceID) && $_POST['override']=='true'))){
								$port->ConnectedDeviceID=$ConnectTo->DeviceID;
								$port->ConnectedPort=$port->PortNumber;
								$port->updatePort();
							}
						}
					}

					$ports=array();
					$sql="SELECT p.*, d.Label as DeviceLabel, (SELECT Label FROM fac_Device 
						WHERE DeviceID=p.ConnectedDeviceID) AS ConnectedDeviceLabel, (SELECT Label 
						from fac_Ports WHERE DeviceID=p.ConnectedDeviceID AND 
						PortNumber=p.ConnectedPort) AS ConnectedPortLabel FROM fac_Ports p, 
						fac_Device d WHERE p.DeviceID=d.DeviceID AND p.DeviceID=$dev->DeviceID;";
					foreach($dbh->query($sql) as $row){
						$ports[$row['PortNumber']]=$row;
					}
					echo json_encode($ports);
				exit;
			}
		}
		if(isset($_POST['getport'])){
			$dp=new DevicePorts();
			$dp->DeviceID=$_POST['swdev'];
			$dp->PortNumber=$_POST['pnum'];
			$dp->getPort();

			$cd=new DevicePorts();
			$cd->DeviceID=$dp->ConnectedDeviceID;
			$cd->PortNumber=$dp->ConnectedPort;
			$cd->getPort();

			$mt=MediaTypes::GetMediaTypeList();
			$cc=ColorCoding::GetCodeList();
			$dp->MediaName=(isset($mt[$dp->MediaID]))?$mt[$dp->MediaID]->MediaType:'';
			$dp->ColorName=(isset($cc[$dp->ColorID]))?$cc[$dp->ColorID]->Name:'';
			$dev->DeviceID=$dp->ConnectedDeviceID;
			$dp->Label=($dp->Label=='')?abs($dp->PortNumber):$dp->Label;
			$dp->ConnectedDeviceLabel=($dev->GetDevice())?stripslashes($dev->Label):'';
			$dp->ConnectedDeviceType=$dev->DeviceType;
			$dp->ConnectedPort=(!is_null($cd->DeviceID) && $dp->ConnectedPort==0)?'':$dp->ConnectedPort;
			$dp->ConnectedPortLabel=(!is_null($cd->Label) && $cd->Label!='')?$cd->Label:$dp->ConnectedPort;
			($dp->ConnectedPort<0)?$dp->ConnectedPortLabel.=' ('.__("Rear").')':'';
			header('Content-Type: application/json');
			echo json_encode($dp);
			exit;
		}
		$list='';
		if(isset($_POST['listports'])){
			$dp=new DevicePorts();
			$dp->DeviceID=$_POST['thisdev'];
			$list=$dp->getPorts();
			if($config->ParameterArray["MediaEnforce"]=='enabled'){
				$dp->DeviceID=$_POST['swdev'];
				$dp->PortNumber=$_POST['pn'];
				$dp->getPort();
				foreach($list as $key => $port){
					if($port->MediaID!=$dp->MediaID){
						unset($list[$key]); // remove the nonmatching ports
					}
				}
			}
			foreach($list as $key => $port){
				if(!is_null($port->ConnectedDeviceID)){
					if($port->ConnectedDeviceID==$_POST['swdev'] && $port->ConnectedPort==$_POST['pn']){
						// This is what is currently connected so leave it in the list
					}else{
						// Remove any other ports that already have connections
						unset($list[$key]);
					}
				}
			}

			// S.U.T. #2342 I touch myself
			if($dp->DeviceID == $_POST['swdev'] && isset($list[$_POST['pn']])){
				unset($list[$_POST['pn']]);
			}

			// Sort the ports so that all front ports will be first then the rear ports.
			$front=array();
			$rear=array();

			foreach($list as $pn => $port){
				if($pn>0){
					$front[$pn]=$port;
				}else{
					$rear[$pn]=$port;
				}
			}

			// Positive and negative numbers have different sorts to make sure that 1 is on top of the list
			ksort($front);
			krsort($rear);

			$list=array_replace($front,$rear);
		}else{
			$patchpanels=(isset($_POST['rear']))?"true":null;
			$portnumber=(isset($_POST['pn']))?$_POST['pn']:null;
			$list=DevicePorts::getPatchCandidates($_POST['swdev'],$portnumber,null,$patchpanels);
		}
		header('Content-Type: application/json');
		echo json_encode($list);
		exit;
	}
	if(isset($_POST['esxrefresh'])){
		$dev->DeviceID=$_POST['esxrefresh'];
		$dev->GetDevice();
		if($dev->Rights=="Write"){
			ESX::RefreshInventory($_POST['esxrefresh']);
			buildesxtable($_POST['esxrefresh']);
		}
		exit;
	}
	if(isset($_POST['customattrrefresh'])){
		$template=new DeviceTemplate();
		$template->TemplateID=$_POST['customattrrefresh'];
		$template->GetTemplateByID();
		$dev->DeviceID=$_POST['deviceid'];
		$dev->GetDevice();	
		buildCustomAttributes($template, $dev);
		exit;
	}
	if(isset($_POST['refreshswitch'])){
		header('Content-Type: application/json');
		if(isset($_POST['names'])){
			$dev->DeviceID=$_POST['refreshswitch'];
			$dev->GetDevice();
			// This function should be hidden if they don't have rights, but just in case
			if($dev->Rights=="Write"){
				foreach(SwitchInfo::getPortNames($_POST['refreshswitch']) as $PortNumber => $Label){
					$port=new DevicePorts();
					$port->DeviceID=$_POST['refreshswitch'];
					$port->PortNumber=$PortNumber;
					$port->Label=$Label;
					$port->updateLabel();
				}
			}
			echo json_encode(SwitchInfo::getPortNames($_POST['refreshswitch']));
		}elseif(isset($_POST['notes'])){
			$dev->DeviceID=$_POST['refreshswitch'];
			$dev->GetDevice();
			// This function should be hidden if they don't have rights, but just in case
			if($dev->Rights=="Write"){
				foreach(SwitchInfo::getPortAlias($_POST['refreshswitch']) as $PortNumber => $Notes){
					$port=new DevicePorts();
					$port->DeviceID=$_POST['refreshswitch'];
					$port->PortNumber=$PortNumber;
					$port->getPort();
					$port->Notes=$Notes;
					$port->updatePort();
				}
			}
			echo json_encode(SwitchInfo::getPortAlias($_POST['refreshswitch']));
		}else{
			echo json_encode(SwitchInfo::getPortStatus($_POST['refreshswitch']));
		}
		exit;
	}

	if(isset($_POST["currwatts"]) && isset($_POST['pduid']) && $_POST['pduid'] >0){
		$pdu=new PowerDistribution();
		$pdu->PDUID=$_POST['pduid'];
		$wattage->Wattage='Err';
		$wattage->LastRead='Err';
		if($pdu->GetPDU()){
			$cab->CabinetID=$pdu->CabinetID;
			$cab->GetCabinet();
			if($person->canWrite($cab->AssignedTo)){
				$wattage=$pdu->LogManualWattage($_POST["currwatts"]);
				$wattage->LastRead=strftime("%c",strtotime($wattage->LastRead));
			}
		}
		header('Content-Type: application/json');
		echo json_encode($wattage);
		exit;
	}
	// END AJAX


	// These objects are used no matter what operation we're performing
	$templ=new DeviceTemplate();
	$mfg=new Manufacturer();
	$esc=new Escalations();
	$escTime=new EscalationTimes();
	$contactList=$contact->GetContactList();
	$Dept=new Department();
	$pwrConnection=new PowerPorts();
	$pdu=new PowerDistribution();
	$panel=new PowerPanel();
	$pwrCords=null;
	$chassis="";
	$copy = false;
	$copyerr=__("This device is a copy of an existing device.  Remember to set the new location before saving.");
	$childList=array();

	// This page was called from somewhere so let's do stuff.
	// If this page wasn't called then present a blank record for device creation.
	if(isset($_REQUEST['action'])||isset($_REQUEST['deviceid'])){
		if(isset($_REQUEST['cabinetid'])){
			$dev->Cabinet=$_REQUEST['cabinetid'];
			$cab->CabinetID=$dev->Cabinet;
			$cab->GetCabinet();
		}
		if(isset($_REQUEST['action'])&&$_REQUEST['action']=='new'){
			// sets install date to today when a new device is being created
			$dev->InstallDate=date("m/d/Y");
			// Some fields are pre-populated when you click "Add device to this cabinet"
			// If you are adding a device that is assigned to a specific customer, assume that device is also owned by that customer
			if($cab->AssignedTo >0){
				$dev->Owner=$cab->AssignedTo;
			}
		}

		// if no device id requested then we must be making a new device so skip all data lookups.
		if(isset($_REQUEST['deviceid'])){
			$dev->DeviceID=intval($_REQUEST['deviceid']);
			// If no action is requested then we must be just querying a device info.
			// Skip all modification checks
			$tagarray=array();
			if(isset($_POST['tags'])){
				$tagarray=json_decode($_POST['tags']);
			}
			if(isset($_POST['action'])){
				$dev->GetDevice();

				// Pull all properties from a template and apply to the device before we add the values set 
				// on the screen.  This will make sure things like slots are pulled from the template that aren't
				// available to the end user initially
				if($_POST['action']=='Create' && $_POST['templateid']>0){
					$templ->TemplateID=$_POST['templateid'];
					if($templ->GetTemplateByID()){
						foreach($templ as $prop => $value){
							$dev->$prop=$value;
						}
					}
				}

				if($dev->DeviceType=="CDU" || (isset($_POST['devicetype']) && $_POST['devicetype']=="CDU")){
					$pdu->PDUID=$dev->DeviceID;
					$pdu->GetPDU();
				}

				if($_POST['action']!='Child'){
					$dev->Label=$_POST['label'];
					$dev->SerialNo=$_POST['serialno'];
					$dev->AssetTag=$_POST['assettag'];
					$dev->Owner=$_POST['owner'];
					$dev->EscalationTimeID=$_POST['escalationtimeid'];
					$dev->EscalationID=$_POST['escalationid'];
					$dev->PrimaryContact=$_POST['primarycontact'];
					$dev->Cabinet=$_POST['cabinetid'];
					$dev->Position=$_POST['position'];
					$dev->Height=$_POST['height'];
					$dev->TemplateID=$_POST['templateid'];
					$dev->DeviceType=$_POST['devicetype'];
					$dev->MfgDate=date('Y-m-d',strtotime($_POST['mfgdate']));
					$dev->InstallDate=date('Y-m-d',strtotime($_POST['installdate']));
					$dev->WarrantyCo=$_POST['warrantyco'];
					$dev->WarrantyExpire=date('Y-m-d',strtotime($_POST['warrantyexpire']));
					$dev->Notes=trim($_POST['notes']);
					$dev->Notes=($dev->Notes=="<br>")?"":$dev->Notes;
					$dev->FirstPortNum=$_POST['firstportnum'];
					// All of the values below here are optional based on the type of device being dealt with
					(isset($_POST['chassisslots']))?$dev->ChassisSlots=$_POST['chassisslots']:'';
					(isset($_POST['rearchassisslots']))?$dev->RearChassisSlots=$_POST['rearchassisslots']:'';
					(isset($_POST['ports']))?$dev->Ports=$_POST['ports']:'';
					(isset($_POST['powersupplycount']))?$dev->PowerSupplyCount=$_POST['powersupplycount']:'';
					$dev->ParentDevice=(isset($_POST['parentdevice']))?$_POST['parentdevice']:"";
					$dev->PrimaryIP=(isset($_POST['primaryip']))?$_POST['primaryip']:"";
					$dev->SNMPCommunity=(isset($_POST['snmpcommunity']))?$_POST['snmpcommunity']:"";
					$dev->ESX=(isset($_POST['esx']))?$_POST['esx']:0;
					$dev->Reservation=(isset($_POST['reservation']))?($_POST['reservation']=="on")?1:0:0;
					$dev->NominalWatts=$_POST['nominalwatts'];
					$dev->HalfDepth=(isset($_POST['halfdepth']))?($_POST['halfdepth']=="on")?1:0:0;
					$dev->BackSide=(isset($_POST['backside']))?($_POST['backside']=="on")?1:0:0;
					// Used by CDU type devices only
					$pdu->Label=$dev->Label;
					$pdu->CabinetID=$dev->Cabinet;
					$pdu->IPAddress=$dev->PrimaryIP;
					(isset($_POST['panelid']))?$pdu->PanelID=$_POST['panelid']:'';
					(isset($_POST['breakersize']))?$pdu->BreakerSize=$_POST['breakersize']:'';
					(isset($_POST['panelpole']))?$pdu->PanelPole=$_POST['panelpole']:'';
					(isset($_POST['inputamperage']))?$pdu->InputAmperage=$_POST['inputamperage']:'';
					(isset($_POST['failsafe']))?$pdu->FailSafe=($_POST['failsafe']=="on")?1:0:'';
					(isset($_POST['panelid2']))?$pdu->PanelID2=$_POST['panelid2']:'';
					(isset($_POST['panelpole2']))?$pdu->PanelPole2=$_POST['panelpole2']:'';
					
				}

				if(($dev->TemplateID >0)&&(intval($dev->NominalWatts==0))){$dev->UpdateWattageFromTemplate();}

				$write=false;
				$write=($person->canWrite($cab->AssignedTo))?true:$write;
				$write=($dev->Rights=="Write")?true:$write;

				if($dev->Rights=="Write" && $dev->DeviceID >0){
					switch($_POST['action']){
						case 'Update':
							// User has changed the device type from chassis to something else and has said yes
							// that they want to remove the dependant child devices
							if(isset($_POST['killthechildren'])){
								$childList=$dev->GetDeviceChildren();
								foreach($childList as $childDev){
									$childDev->DeleteDevice();
								}
							}

							$dev->SetTags($tagarray);
							if($dev->Cabinet <0){
								$dev->MoveToStorage();
							}else{
								$dev->UpdateDevice();
								if($dev->DeviceType=="CDU"){
									$pdu->UpdatePDU();
								}
								updateCustomValues($dev);
							}
							break;
						case 'Delete':
							$dev->DeleteDevice();
							//the $dev object should still exist even though we've deleted the db entry now
							if($dev->ParentDevice >0){
								header('Location: '.redirect("devices.php?deviceid=$dev->ParentDevice"));
							}else{
								header('Location: '.redirect("cabnavigator.php?cabinetid=$dev->Cabinet"));
							}
							exit;
							break; // the exit should handle it
						case 'Copy':
							$copy=true;
							if(!$dev->CopyDevice()){
								$copyerr=__("Device did not copy.  Error.");
							}
							break;
						case 'Child':
							foreach($dev as $prop => $value){
								$dev->$prop=null;
							}
							$dev->ParentDevice=$_REQUEST["parentdevice"];

							// sets install date to today when a new device is being created
							$dev->InstallDate=date("m/d/Y");
							break;
					}
				// Can't check the device for rights because it shouldn't exist yet
				// but the user could have rights from the cabinet and it is checked above
				// when the device object is populated.
				}elseif($write && $_POST['action']=='Create'){
					if($dev->TemplateID>0 && intval($dev->NominalWatts==0)){
						$dev->UpdateWattageFromTemplate();
					}
					$dev->CreateDevice();
					if($dev->DeviceType=="CDU"){
						$pdu->CreatePDU($dev->DeviceID);
					}
					$dev->SetTags($tagarray);
					updateCustomValues($dev);
				}
			}

			/*
			 * Prepare data for display
			 *
			 */

			// Finished updating devices or creating them.  Refresh the object with data from the DB
			$dev->GetDevice();

			// Get any tags associated with this device
			$tags=$dev->GetTags();
			if(count($tags)>0){
				// We have some tags so build the javascript elements we need to create the tags themselves
				$taginsert="\t\ttags: {items: ".json_encode($tags)."},\n";
			}

			// Since a device exists we're gonna need some additional info, but only if it's not a copy
			if(!$copy){
				// clearing errors for now
				$LastWattage=$LastRead=$upTime=0;

				$pwrConnection->DeviceID=($dev->ParentDevice>0)?$dev->GetRootDeviceID():$dev->DeviceID;
				$pwrCords=$pwrConnection->getPorts();

				if($dev->DeviceType=='Switch'){
					$linkList=SwitchInfo::getPortStatus($dev->DeviceID);
				}elseif($dev->DeviceType=='CDU'){
					$pdu->PDUID=$dev->DeviceID;
					$pdu->GetPDU();

					$lastreading=$pdu->GetLastReading();
					$LastWattage=($lastreading)?$lastreading->Wattage:0;
					$LastRead=($lastreading)?strftime("%c",strtotime($lastreading->LastRead)):"Never";
				}
			}

			if($dev->ChassisSlots>0 || $dev->RearChassisSlots>0){
				$childList=$dev->GetDeviceChildren();
			}

			if($dev->ParentDevice >0){
				$pDev=new Device();
				$pDev->DeviceID=$dev->ParentDevice;
				$pDev->GetDevice();

				$parentList=$pDev->GetParentDevices();

				//$cab->CabinetID=$pDev->Cabinet;
				//JMGA: changed for multichassis
				$cab->CabinetID=$pDev->GetDeviceCabinetID();
				$cab->GetCabinet();
				$chassis="Chassis";

				// This is a child device and if the action of new is set let's assume the
				// departmental owner, primary contact, etc are the same as the parent
				if(isset($_POST['action']) && $_POST['action']=='Child'){
					$dev->Owner=$pDev->Owner;
					$dev->EscalationTimeID=$pDev->EscalationTimeID;
					$dev->EscalationID=$pDev->EscalationID;
					$dev->PrimaryContact=$pDev->PrimaryContact;
				}
			}
		}
		$cab->CabinetID=$dev->Cabinet;
		$cab->GetCabinet();
	}else{
		/*
		 * Everything below here will get processed when no deviceid is present
		 * aka adding a new device
		 */

		// sets install date to today when a new device is being created
		$dev->InstallDate=date("m/d/Y");
	}

	// We don't want someone accidentally adding a chassis device inside of a chassis slot.
	if($dev->ParentDevice>0){
		$devarray=array('Server' => __("Server"),
						'Appliance' => __("Appliance"),
						'Storage Array' => __("Storage Array"),
						'Switch' => __("Switch"),
						'Chassis' => __("Chassis"),
						'Patch Panel' => __("Patch Panel"),
						'Sensor' => __("Sensor"),
						);
	}else{
		$devarray=array('Server' => __("Server"),
						'Appliance' => __("Appliance"),
						'Storage Array' => __("Storage Array"),
						'Switch' => __("Switch"),
						'Chassis' => __("Chassis"),
						'Patch Panel' => __("Patch Panel"),
						'Physical Infrastructure' => __("Physical Infrastructure"),
						'CDU' => __("CDU"),
						'Sensor' => __("Sensor"),
						);
	}

	if($config->ParameterArray["mDate"]=="now"){
		if($dev->MfgDate <= "1970-01-01"){
			$dev->MfgDate=date("Y-m-d");
		}
	}

	if($config->ParameterArray["wDate"]=="now"){
		if($dev->WarrantyExpire <= "1970-01-01"){
			$dev->WarrantyExpire=date("Y-m-d");
		}
	}

	$portList=DevicePorts::getPortList($dev->DeviceID);
	$mediaTypes=MediaTypes::GetMediaTypeList();
	$colorCodes=ColorCoding::GetCodeList();
	$templateList=$templ->GetTemplateList();
	$escTimeList=$escTime->GetEscalationTimeList();
	$escList=$esc->GetEscalationList();
	$deptList=$Dept->GetDepartmentList();

	$templ->TemplateID=$dev->TemplateID;
	$templ->GetTemplateByID();


	
	$title=($dev->Label!='')?"$dev->Label :: $dev->DeviceID":__("openDCIM Device Maintenance");

	function buildesxtable($deviceid){
		$esx=new ESX();
		$esx->DeviceID=$deviceid;
		$vmList=$esx->GetDeviceInventory();

		print "\n<div class=\"table border\"><div><div>".__("VM Name")."</div><div>".__("Status")."</div><div>".__("Owner")."</div><div>".__("Last Updated")."</div></div>\n";
		foreach($vmList as $vmRow){
			if($vmRow->vmState=='poweredOff'){
				$statColor='red';
			}else{
				$statColor='green';
			}
			$Dept=new Department();
			$Dept->DeptID=$vmRow->Owner;
			if($Dept->DeptID >0){
				$Dept->GetDeptByID();
			}else{
				$Dept->Name=__("Unknown");
			}
			print "<div><div>$vmRow->vmName</div><div><font color=$statColor>$vmRow->vmState</font></div><div><a href=\"updatevmowner.php?vmindex=$vmRow->VMIndex\">$Dept->Name</a></div><div>$vmRow->LastUpdated</div></div>\n";
		}
		echo '</div> <!-- END div.table -->';
	}
	
	function buildCustomAttributes($template, $device) {
		$dcaList=DeviceCustomAttribute::GetDeviceCustomAttributeList();
		$tdcaList=$template->CustomValues;
		$dcvList=$device->CustomValues;

		$customvalues = array();

		// pull the "all devices" custom attributes
		if(isset($dcaList)) {
			foreach($dcaList as $dca) {
				if($dca->AllDevices==1) {
					$customvalues[$dca->AttributeID]["value"]=$dca->DefaultValue;	
					$customvalues[$dca->AttributeID]["type"]=$dca->AttributeType;
					$customvalues[$dca->AttributeID]["required"]=$dca->Required;
				}
			}
		}
		if(isset($tdcaList)) {
			// pull the device template level custom attributes (done second so we overwrite all devices)
			foreach($tdcaList as $AttributeID=>$tdca) {
				$customvalues[$AttributeID]["value"]=$tdca["value"];
				$customvalues[$AttributeID]["type"]=$dcaList[$AttributeID]->AttributeType;
				$customvalues[$AttributeID]["required"]=$tdca["required"];

			}
		}
		if(isset($dcvList)) {
			// pull the values set at this device level if any exist, the assumption being that one of the 2 loops above has already populated the type and required fields
			foreach($dcvList as $AttributeID=>$dcv) {
				if(array_key_exists($AttributeID, $customvalues)) {
					$customvalues[$AttributeID]["value"]=$dcv;
				} else {
					// this is probably an  error, what do?
				}
			}
		}
		echo '<div class="table">';	
		foreach($customvalues as $customkey=>$customdata) {
			$inputname = "customvalue[$customkey]";
			$validation="";
			$cvtype = $customvalues[$customkey]["type"];
			if($customvalues[$customkey]["required"]==1 || $cvtype!="string"){
				$validation=' class="validate[';
				$validationrules=array();
				if($customvalues[$customkey]["required"]==1) {
					$validationrules[]="required";
				}
				if($cvtype!="string" && $cvtype != "checkbox"){
					$validationrules[]='custom['.$cvtype.']';
				}
				$validation.=implode(",",$validationrules);
				$validation.=']" ';
			}
			echo '<div>
				<div><label for="',$inputname,'">',$dcaList[$customkey]->Label,'</label></div>';
			if($cvtype=="checkbox"){
				$checked = "";
				if($customdata["value"] == "1" || $customdata["value"]=="on"){
					$checked = " checked";
				}
				echo '<div><input type="checkbox" name="',$inputname,'" id="',$inputname,'"',$checked,'></div>';
			} else {
				echo '<div><input type="text"',$validation,' name="',$inputname,'" id="',$inputname,'" value="',$customdata["value"],'"></div>';

			}
		     	echo '</div>';
		}
		echo '</div>';
	}
	function updateCustomValues($device) {
		$template=new DeviceTemplate();
		$template->TemplateID=$device->TemplateID;
		$template->GetTemplateByID();
		
		$dcaList=DeviceCustomAttribute::GetDeviceCustomAttributeList();
		$tdcaList=$template->CustomValues;
		$defaultvalues = array();
		if(isset($dcaList)) {
			foreach($dcaList as $dca) {
				if($dca->AllDevices==1) {
					$defaultvalues[$dca->AttributeID]["value"]=$dca->DefaultValue;
					$defaultvalues[$dca->AttributeID]["required"]=$dca->Required;
				}
			}
		}
		if(isset($tdcaList)) {
			foreach($tdcaList as $AttributeID=>$tdca) {
				$defaultvalues[$AttributeID]["value"]=$tdca["value"];
				$defaultvalues[$AttributeID]["required"]=$tdca["required"];
			}
		}

		$device->DeleteCustomValues();

// this is throwing an error on update when there aren't any custom values, commenting this out so I can finish my shit without errors
		// TODO: what of server-side validation if this is a "required" attribute?
/*
		foreach($_POST["customvalue"] as $AttributeID=>$value) {
			if(trim($value) != trim($defaultvalues[$AttributeID]["value"])) {
				$device->InsertCustomValue($AttributeID, $value);	
			}
		}
*/
		
	}
// In the case of a child device we might define this above and in that case we
// need to preserve the flag
$write=(isset($write))?$write:false;
$write=($person->canWrite($cab->AssignedTo))?true:$write;
$write=($dev->Rights=="Write")?true:$write;


?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

  <title><?php echo $title; ?></title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <link rel="stylesheet" href="css/jHtmlArea.css" type="text/css">
  <style type="text/css"></style>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/mdetect.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript" src="scripts/jHtmlArea-0.8.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.textext.js"></script>
  <script type="text/javascript" src="scripts/common.js"></script>

<SCRIPT type="text/javascript" >
var nextField;
function getScan(fieldName){
    var href=window.location.href;
    var ptr=href.lastIndexOf("#");
    if(ptr>0){
        href=href.substr(0,ptr);
    }
	nextField=fieldName;
    window.location.href="zxing://scan/?ret="+escape(href+"#{CODE}");
}
var changingHash=false;
function getHash(){
	if ( !changingHash ) {
		changingHash=true;
		var hash=window.location.hash.substr(1);
		switch (nextField) {
			case "serialno":
				$('#serialno').val(unescape(hash));
				break;
			case "assettag":
				$('#assettag').val(unescape(hash));
				break;
			default:
				break;
		}
		// window.location.hash="";
		changingHash=false;
	}
}
</SCRIPT>

<script type="text/javascript">
/*
IE work around
http://stackoverflow.com/questions/5227088/creating-style-node-adding-innerhtml-add-to-dom-and-ie-headaches
*/

function swaplayout(){
	var sheet=document.createElement('style');
	sheet.type='text/css';
	var s=document.getElementsByTagName('style')[0];
	var button=document.getElementById('layout');

	function p(){ // set to portrait view
		s.parentNode.insertBefore(sheet, s);
		button.innerHTML="<?php echo __("Landscape"); ?>";
		setCookie("layout","Portrait");
	}

	function l(){ // set to landscape view
		if(sheet.styleSheet){ //IE
			s.styleSheet.cssText = "";
		}else{
			s.innerHTML = "";
		}
		button.innerHTML="<?php echo __("Portrait"); ?>";
		setCookie("layout","Landscape");
	}

	if(sheet.styleSheet){ // IE
		sheet.styleSheet.cssText = ".device div.left { display: block; }";
		(s.styleSheet.cssText==sheet.styleSheet.cssText)?l():p();
	}else{
		sheet.innerHTML = ".device div.left { display: block; }";
		(s.innerHTML==sheet.innerHTML)?l():p();
	}
}

$(document).ready(function() {
	var isMobile = {
		Android: function() {
			return navigator.userAgent.match(/Android/i);
		},
		BlackBerry: function() {
			return navigator.userAgent.match(/BlackBerry/i);
		},
		iOS: function() {
			return navigator.userAgent.match(/iPhone|iPad|iPod/i);
		},
		Opera: function() {
			return navigator.userAgent.match(/Opera Mini/i);
		},
		Windows: function() {
			return navigator.userAgent.match(/IEMobile/i);
		},
		any: function() {
			return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
		}
	};
	// Mobile devices get a scan barcode button and an accordian interface
	if(isMobile.any()){
		$('.main button').each(function(){
			if($(this).text()=='Scan Barcode'){
				$(this).css('display', 'inline');
			}
		});
		$('.left > fieldset ~ .table').each(function(){
<?php print "			$(this).before($('<h3><a href=\"#\">".__("Notes")."</a></h3>'));"; ?>
		});
		$('.right').contents().appendTo($('.left'));
<?php print "		$('.left').append('<h3><a href=\"#\">".__("Network & Power")."</a></h3>');"; ?>
		$('.right').next('div.table').appendTo($('.left'));
		$('.left legend').each(function(){
			$(this).parent('fieldset').before($('<h3><a href="#">'+$(this).text()+'</a></h3>'));
			$(this).remove();
		});
		$('.left > h3 ~ fieldset').each(function(){
			$a=$(this).children('.table');
			$($a.parent()).before($a);
			$(this).remove();
		});
		$('.table + .table').each(function(){
			$(this).prev().wrap($('<div />'));
			$(this).appendTo($(this).prev());
		});
		$('input[name="chassisslots"]').filter($('[type="hidden"]')).insertAfter($('.left'));
		$('.device .table').css('width', 'auto');
		$('.left').after($('<div class="table" id="target" style="width: 100%"></div>'));
		$('.caption').appendTo($('#target'));
		$('.left').accordion({
			autoHeight: false,
			collapsible: true
		}).removeClass('left');
	}

	// add the current ports value to the document data store
	$(document).data('ports',$('#ports').val());
	$(document).data('powersupplycount',$('#powersupplycount').val());
	$(document).data('devicetype', $('select[name="devicetype"]').val());
	$(document).data('defaultsnmp','<?php echo $config->ParameterArray["SNMPCommunity"]; ?>');
	$(document).data('showdc','<?php echo $config->ParameterArray["AppendCabDC"]; ?>');

	$('#deviceform').validationEngine();
	$('#mfgdate').datepicker();
	$('#installdate').datepicker();
	$('#warrantyexpire').datepicker();
	$('#owner').next('button').click(function(){
		window.open('contactpopup.php?deptid='+$('#owner').val(), 'Contacts Lookup', 'width=800, height=700, resizable=no, toolbar=no');
		return false;
	});

	// CDU functions
	$('#panelid').change( function(){
		$.get('scripts/ajax_panel.php?q='+$(this).val(), function(data) {
			$('#voltage').html(data['PanelVoltage'] +'/'+ Math.floor(data['PanelVoltage']/1.73));
		});
	});
	$('#btn_override').on('click',function(e){
		var btn=$(e.currentTarget);
		var target=$(e.currentTarget.previousSibling);
		if(btn.val()=='edit'){
			var specialinput=$('<input>').attr('size',5).val(target.text());
			specialinput.keypress(function(event){
				if(event.keyCode==10 || event.keyCode==13){
					event.preventDefault();
					btn.click();
				}
			});
			btn.val('submit').text(btn.data('submit')).css('height','2em');
			target.replaceWith(specialinput);
			specialinput.focus().select();
		}else{
			btn.val('edit').text(btn.data('edit')).css('height','');
			$.post('',{currwatts: target.val(), pduid:$('#deviceid').val()}).done(function(data){
				target.replaceWith($('<span>').text(data.Wattage));
				$('#lastread').text(data.LastRead);
			});
		}
	});

	// Do a call after the page loads to get the CDU uptime to speed up the initial page load
	if($('select[name=devicetype]').val()=='CDU'){
		$.post('',{cduuptime: '',deviceid: $('#deviceid').val()}, function(data) {
			$('#cduuptime').text(data);
		});
	}


	// Make SNMP community visible
	$('#snmpcommunity').focus(function(){$(this).attr('type','text');});
	$('#snmpcommunity').blur(function(){$(this).attr('type','password');});

	// What what?! an SNMP test function!?
	$('#primaryip,#snmpcommunity').on('change keyup keydown', function(){ SNMPTest(); }).change();

	function SNMPTest(){
		var ip=$('#primaryip');
		var snmp=$('#snmpcommunity');
		var dc=$(document).data('defaultsnmp');
		var community=(snmp.val()!='')?snmp.val():(dc!='')?dc:'';

		if(ip.val()!='' && community!=''){snmp.next('button').show().removeClass('hide');}else{snmp.next('button').hide();}
	}

	$('#btn_snmptest').click(function(e){
		e.preventDefault();
		var snmp=$('#snmpcommunity');
		var dc=$(document).data('defaultsnmp');
		var community=(snmp.val()!='')?snmp.val():(dc!='')?dc:'';
		$('#pdutest').html('<img src="images/mimesearch.gif" height="150px">Checking...');
		$.post('', {snmptest: $('#deviceid').val(),ip: $('#primaryip').val(),community: community}, function(data){
			$('#pdutest').html(data);
		});
		$('#pdutest').dialog({minWidth: 850, position: { my: "center", at: "top", of: window },closeOnEscape: true });
	});

	// Add in refresh functions for virtual machines
	var esxtable=$('<div>').addClass('table border').append('<div><div>VM Name</div><div>Status</div><div>Owner</div><div>Last Updated</div></div>');
	var esxbutton=$('<button>',{'type':'button'}).css({'position':'absolute','top':'10px','right':'2px'}).text('Refresh');
	esxbutton.click(esxrefresh);
	if($('#esx').val()==1){
		$('#esxframe').css('position','relative').append(esxbutton);
	}
	function esxrefresh(){
		$.post('',{esxrefresh: $('#deviceid').val()}).done(function(data){
			$('#esxframe .table ~ .table').replaceWith(data);
		});
	}

	// This is for adding blades to chassis devices
	$('#adddevice').click(function() {
		$(":input").attr("disabled","disabled");
		$('#parentdevice').removeAttr("disabled");
		$('#adddevice').removeAttr("disabled");
		$(this).submit();
		setTimeout(function(){
			$(":input").removeAttr("disabled"); // if they hit back it makes sure the fields aren't disabled
			$('#parentdevice').attr("disabled","disabled"); // if they hit back disable this so a chassis doesn't become its own parent
		},100);
	});

	// Device image previews
	$('#deviceimages > div > img').
		on('error',function(){$(this).hide();toggledeviceimages();}).
		on('load',function(){
			if($(this).context.width < $(this).context.height){
				$(this).css({'height':'275px','width':'auto'});
			}else{
				$(this).css({'height':'','width':''});
			}
			$(this).show().on('click',function(){
				var pop=$(this).clone();
				pop.attr('style','');
				$('<div>').html(pop.css({'max-width':'600px','max-height':'600px'})).dialog({
					width: 'auto',
					height: 'auto',
					modal: true
				});
			});
			toggledeviceimages();
		});

	function toggledeviceimages(){
		$('#deviceimages').show();
		var n=0;
		$('#deviceimages > div > img').each(function(){
			if($(this).is(":visible")){ n++;}
		});
		if(n==0){$('#deviceimages').hide();}
	}

	function customattrrefresh(templateid){
		$.post('',{customattrrefresh: templateid, deviceid:  $('#deviceid').val() }).done(function(data){
console.log($('#customattrs .table ~ .table'));
			$('#customattrs .table ').replaceWith(data);
		});


	}

	// Need to make some changes to the UI for the storage room
	$('#cabinetid').change(function(){
		var positionrow=$('#position').parent('div').parent('div');
		if($(this).val()==-1){
			positionrow.hide();
		}else{
			positionrow.show();
		}
	}).trigger('change');

	// Auto-Populate fields based on device templates
	$('#templateid').change( function(){
		$.get('scripts/ajax_template.php?q='+$(this).val(), function(data) {
			$('#height').val(data['Height']);
			$('#ports').val(data['NumPorts']);
			$('#nominalwatts').val(data['Wattage']);
			$('#powersupplycount').val(data['PSCount']);
			$('select[name=devicetype]').val(data['DeviceType']).trigger('change');
			$('#height').trigger('change');
			(data['FrontPictureFile']!='')?$('#devicefront').attr('src','pictures/'+data['FrontPictureFile']):$('#devicefront').removeAttr('src').hide();
			(data['RearPictureFile']!='')?$('#devicerear').attr('src','pictures/'+data['RearPictureFile']):$('#devicerear').removeAttr('src').hide();
			toggledeviceimages();
			customattrrefresh($('#templateid').val());
		});
	});

	$('select[name=devicetype]').change(function(){
// redo this to support a list of special frames hide all special frames except the category
// we're currently dealing with
		if($(this).val()=='Switch'){
			if($(document).data('devicetype')!='Switch'){
				$('#firstport button:not([name="firstport"])').hide();
			}
			if($('#deviceid').val()>0){
				$('#firstport').show().removeClass('hide');
				$('.switch div[id^="st"]').show();
			}
		}else{
			$('#firstport').hide();
			$('.switch div[id^="st"]').hide();
		}
		if($(this).val()=='Server'){
			$('#esxframe').show();
		}else{
			$('#esxframe').hide();
		}
		if($(this).val()=='CDU'){
			$('#cdu').show().removeClass('hide');
			$('#nominalwatts').parent('div').parent('div').addClass('hide');
		}else{
			$('#cdu').hide();
			$('#nominalwatts').parent('div').parent('div').removeClass('hide');
		}
		resize();
	}).change();
	$('#firstport button[name=firstport]').click(function(){
		var modal=$('<div />', {id: 'modal', title: 'Select switch first port'}).html('<div id="modaltext"></div><br><div id="modalstatus" class="warning"></div>').dialog({
			appendTo: 'body',
			modal: true,
			close: function(){$(this).dialog('destroy');}
		});
		$.post('',{fp: '', devid: $('#deviceid').val()}).done(function(data){
			$('#modaltext').html(data);
			$('#modaltext input').change(function(){
				var fpnum=$(this).val();
				$.post('',{fp: fpnum, devid: $('#deviceid').val()}).done(function(data){
					$('input[name=firstportnum]').val(fpnum);
					$('#modalstatus').html(data);
					$('#modal').dialog('destroy');
				}).then(refreshswitch($('#deviceid').val(),true));
			});
		});
	});
	$('#firstport button[name=refresh]').click(function(){
		refreshswitch($('#deviceid').val());
	});
	$('#firstport button[name=name]').click(function(){
		refreshswitch($('#deviceid').val(),'names');
	});
	$('#firstport button[name=notes]').click(function(){
		refreshswitch($('#deviceid').val(),'notes');
	});
	function refreshswitch(devid,names){
		var modal=$('<div />', {id: 'modal', title: 'Please wait...'}).html('<div id="modaltext"><img src="images/animatedswitch.gif" style="width: 100%;"><br>Polling device...</div><br><div id="modalstatus" class="warning"></div>').dialog({
			appendTo: 'body',
			minWidth: 500,
			closeOnEscape: false,
			dialogClass: "no-close",
			modal: true
		});
		if(names){
			if(names=='names'){
				$.post('',{refreshswitch: devid, names: names}).done(function(data){
					$.each(data, function(i,label){
						if(label){
							$('#spn'+i).text(label);
						}else{
							$('#spn'+i).text('');
						}
					});
					modal.dialog('destroy');
				});
			}else{
				$.post('',{refreshswitch: devid, notes: names}).done(function(data){
					$.each(data, function(i,notes){
						if(notes){
							$('#n'+i).text(notes);
						}else{
							$('#n'+i).text('');
						}
					});
					modal.dialog('destroy');
				});
			}
		}else{
			$.post('',{refreshswitch: devid}).done(function(data){
				$.each(data, function(i,portstatus){
					$('#st'+i).html($('<span>').addClass('ui-icon').addClass('status').addClass(portstatus));
				});
				modal.dialog('destroy');
			});
		}
	}
<?php
	// hide all the js functions if they don't have write permissions
	if($write){

print "		var dialog=$('<div>').prop('title',\"".__("Verify Delete Device")."\").html('<p><span class=\"ui-icon ui-icon-alert\" style=\"float:left; margin:0 7px 20px 0;\"></span><span></span></p>');";

		// Add an extra alert warning about child devices in chassis
		if($dev->DeviceType=='Chassis'){
?>
		$('select[name=devicetype]').change(function(){
			var form=$(this).parents('form');
			var btn=$(this);
			if($(this).val()!='Chassis'){
<?php echo '				dialog.find(\'span + span\').text("',__("If this device has blades installed they will be deleted and there is no undo. Are you sure?"),'");'; ?>
				dialog.dialog({
					resizable: false,
					modal: true,
					dialogClass: "no-close",
					buttons: {
<?php echo '				',__("Yes"),': function(){'; ?>
							$(this).dialog("destroy");
							form.append('<input type="hidden" class="killthechildren" name="killthechildren" value="yes">');
						},
<?php echo '				',__("No"),': function(){'; ?>
							$('.killthechildren').remove();
							$('select[name=devicetype]').val('Chassis').change();
							$(this).dialog("destroy");
						}
					}
				});
			}else{
				$('.killthechildren').remove();
			}
		});
<?php
		}

		// hide cabinet slot picker from child devices
		if($dev->ParentDevice==0){
?>
		$('#cabinetid').change(function(){
			$.post('', {cab: $("select#cabinetid").val()}, function(data){
				var posclass=$('#position').attr('class');
				if(parseInt(data.trim())>0){
					$('#position').attr('class',posclass.replace(/max\[([0-9]).*?\]/gi,"max["+data.trim()+"]")).trigger('focusout');
				}
			});
		});
		$('#height').change(function(){
			if($(this).val()==0){
				$('#position').attr('disabled', 'true');
				$(this).parents('form').append('<input class="tmpposition" type="hidden" name="position" value="0">');
			}else{
				$('#position').removeAttr('disabled');
				$('.tmpposition').remove();
			}
		});
		$('#height').trigger('change');
		$('#position').focus(function()	{
			var cab=$("select#cabinetid").val();
			var hd=$('#halfdepth').is(':checked');
			var bs=$('#backside').is(':checked');
			$.getJSON('scripts/ajax_cabinetuse.php?cabinet='+cab+'&deviceid='+$("#deviceid").val()+'&halfdepth='+hd+'&backside='+bs, function(data) {
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
				$('#positionselector').html(rackhtml);
				setTimeout(function(){
					var divwidth=$('.positionselector').width();
					var divheight=$('.positionselector').height();
					$('#positionselector').width(divwidth);
					$('#height').focus(function(){$('#positionselector').css({'left': '-1000px'});});
					$('#positionselector').css({
						'left':(($('.right').position().left)-(divwidth+40)),
						'top':(($('.right').position().top))
					});
					$('#positionselector').mouseleave(function(){
						$('#positionselector').css({'left': '-1000px'});
					});
					$('.positionselector > div > div + div > div').mouseover(function(){
						$('.positionselector > div > div + div > div').each(function(){
							$(this).removeAttr('style');
						});
						var unum=$("#height").val();
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
											$('#position').val($(this).attr('val')).trigger('focusout');
											$('#positionselector').css({'left': '-1000px'});
										});
									}
								}
							}
						}
					});
				},100);
			}, 'json');
		}).click(function(){
			$(this).trigger('focus');
		});
<?php
		}
?>

		$('#reservation').change(function(){
			if(!$(this).prop("checked")){
				var d=new Date();
				$('#installdate').datepicker("setDate",d);
			}
		});
		// Delete device confirmation dialog
		$('button[value="Delete"]').click(function(e){
			var form=$(this).parents('form');
			var btn=$(this);
<?php echo '				dialog.find(\'span + span\').text("',__("This device will be deleted and there is no undo. Are you sure?"),'");'; ?>
			dialog.dialog({
				resizable: false,
				modal: true,
				buttons: {
<?php echo '				',__("Yes"),': function(){'; ?>
						$(this).dialog("destroy");
						form.append('<input type="hidden" name="'+btn.attr("name")+'" value="'+btn.val()+'">');
						form.submit();
					},
<?php echo '				',__("No"),': function(){'; ?>
						$(this).dialog("destroy");
					}
				}
			});
		});

		$('#ports').change(function(){
			// not sure why .data() is turning an int into a string parseInt is fixing that
			if($(this).val() > parseInt($(document).data('ports'))){
				//make more ports and add the rows below
				$('button[value="Update"]').click();
			}else if($(this).val()==$(document).data('ports')){
				// this is the I changed my mind condition.
				$('.device .switch .delete').hide();
			}else{
				//S.U.T. present options to remove ports
				$('.device .switch .delete').show();
			}
		});
		$('#powersupplycount').change(function(){
			// not sure why .data() is turning an int into a string parseInt is fixing that
			if($(this).val() > parseInt($(document).data('powersupplycount'))){
				//make more ports and add the rows below
				$('button[value="Update"]').click();
			}else if($(this).val()==$(document).data('powersupplycount')){
				// this is the I changed my mind condition.
				$('.device .power .delete').hide();
			}else{
				//S.U.T. present options to remove ports
				$('.device .power .delete').show();
			}
		});

<?php
	} // end of javascript editing functions
?>
	// Make connections to other devices
	$('.switch.table > div ~ div, .patchpanel > div ~ div').each(function(){
		var row=$(this);
		if(portrights[row.data('port')]){ // only bind edit functions if they have rights
			row.row();
		}
	});

	$('.power > div ~ div').each(function(){
		var row=$(this);
		row.power();
	});

	function setPreferredLayout() {<?php if(isset($_COOKIE["layout"]) && strtolower($_COOKIE["layout"])==="portrait"){echo 'swaplayout();setCookie("layout","Portrait");';}else{echo 'setCookie("layout","Landscape");';} ?>}
	setPreferredLayout();
	$('#tags').width($('#tags').parent('div').parent('div').innerWidth()-$('#tags').parent('div').prev('div').outerWidth()-5);

	$('#tags').textext({
		plugins : 'autocomplete tags ajax arrow prompt focus',
<?php echo $taginsert; ?>
		ajax : {
			url : 'scripts/ajax_tags.php',
			dataType : 'json'
		}
	});
});

</script>

</head>
<body onhashchange="getHash()">
<?php include( 'header.inc.php' ); ?>
<div class="page device">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<button id="layout" onClick="swaplayout()">'.__("Portrait").'</button>';
echo($copy)?"<h3>$copyerr</h3>":'';
echo '<div class="center"><div>
<div id="positionselector"></div>
<form name="deviceform" id="deviceform" action="'.$_SERVER['PHP_SELF'].((isset($dev->DeviceID) && $dev->DeviceID>0)?"?deviceid=$dev->DeviceID":"").'" method="POST">
<div class="left">
<fieldset>
	<legend>'.__("Asset Tracking").'</legend>
	<div class="table">
		<div>
		   <div>'.__("Device ID").'</div>
		   <div><input type="text" name="deviceid" id="deviceid" value="'.$dev->DeviceID.'" size="6" readonly></div>
		</div>
		<div>
			<div><label for="reservation">'.__("Reservation?").'</label></div>
			<div><input type="checkbox" name="reservation" id="reservation"'.((($dev->Reservation) || $copy )?" checked":"").'></div>
		</div>
		<div>
		   <div><label for="label">'.__("Label").'</label></div>
		   <div><input type="text" class="validate[required,minSize[3],maxSize[50]]" name="label" id="label" size="40" value="'.$dev->Label.'"></div>
		</div>
		<div>
		   <div><label for="serialno">'.__("Serial Number").'</label></div>
		   <div><input type="text" name="serialno" id="serialno" size="40" value="'.$dev->SerialNo.'">
		   <button class="hide" type="button" onclick="getScan(\'serialno\')">',__("Scan Barcode"),'</button></div>
		</div>
		<div>
		   <div><label for="assettag">'.__("Asset Tag").'</label></div>
		   <div><input type="text" name="assettag" id="assettag" size="20" value="'.$dev->AssetTag.'">
		   <button class="hide" type="button" onclick="getScan(\'assettag\')">',__("Scan Barcode"),'</button></div>
		</div>
		<div>
		  <div><label for="primaryip">'.__("Primary IP / Host Name").'</label></div>
		  <div><input type="text" name="primaryip" id="primaryip" size="20" value="'.$dev->PrimaryIP.'">
				<input type="hidden" name="firstportnum" value="'.$dev->FirstPortNum.'"></div>
		</div>
		<div>
		  <div><label for="snmpcommunity">'.__("SNMP Read Only Community").'</label></div>
		  <div><input type="password" name="snmpcommunity" id="snmpcommunity" size="40" value="'.$dev->SNMPCommunity.'"><button type="button" class="hide" id="btn_snmptest">'.__("Test SNMP").'</button></div>
		</div>
		<div>
		   <div><label for="mfgdate">'.__("Manufacture Date").'</label></div>
		   <div><input type="text" class="validate[optional,custom[date]] datepicker" name="mfgdate" id="mfgdate" value="'.(($dev->MfgDate>'0000-00-00 00:00:00')?date('m/d/Y',strtotime($dev->MfgDate)):"").'">
		   </div>
		</div>
		<div>
		   <div><label for="installdate">'.__("Install Date").'</label></div>
		   <div><input type="text" class="validate[required,custom[date]] datepicker" name="installdate" id="installdate" value="'.(($dev->InstallDate>'0000-00-00 00:00:00')?date('m/d/Y',strtotime($dev->InstallDate)):"").'"></div>
		</div>
		<div>
		   <div><label for="warrantyco">'.__("Warranty Company").'</label></div>
		   <div><input type="text" name="warrantyco" id="warrantyco" value="'.$dev->WarrantyCo.'"></div>
		</div>
		<div>
		   <div><label for="installdate">'.__("Warranty Expiration").'</label></div>
		   <div><input type="text" class="validate[custom[date]] datepicker" name="warrantyexpire" id="warrantyexpire" value="'.date('m/d/Y',strtotime($dev->WarrantyExpire)).'"></div>
		</div>
		<div>
		   <div>'.__("Last Audit Completed").'</div>
		   <div><span id="auditdate">'.((strtotime($dev->AuditStamp)>0)?date('r',strtotime($dev->AuditStamp)):__("Audit not yet completed")).'</span></div>
		</div>
		<div>
		   <div><label for="owner">'.__("Departmental Owner").'</label></div>
		   <div>
			<select name="owner" id="owner">
				<option value=0>'.__("Unassigned").'</option>';

			foreach($deptList as $deptRow){
				if($dev->Owner==$deptRow->DeptID){$selected=" selected";}else{$selected="";}
				print "\t\t\t\t<option value=\"$deptRow->DeptID\"$selected>$deptRow->Name</option>\n";
			}

echo '			</select>
			<button type="button">',__("Show Contacts"),'</button>
		   </div>
		</div>
		<div>
		   <div>&nbsp;</div>
		   <div><fieldset>
		   <legend>',__("Escalation Information"),'</legend>
		   <div class="table">
			<div>
				<div><label for="escalationtimeid">',__("Time Period"),'</label></div>
				<div><select name="escalationtimeid" id="escalationtimeid">
					<option value="">',__("Select..."),'</option>';

				foreach($escTimeList as $escTime){
					if($escTime->EscalationTimeID==$dev->EscalationTimeID){$selected=" selected";}else{$selected="";}
					print "\t\t\t\t\t<option value=\"$escTime->EscalationTimeID\"$selected>$escTime->TimePeriod</option>\n";
				}

echo '				</select></div>
			</div>
			<div>
				<div><label for="escalationid">',__("Details"),'</label></div>
				<div><select name="escalationid" id="escalationid">
					<option value="">',__("Select..."),'</option>';

				foreach($escList as $esc){
					if($esc->EscalationID==$dev->EscalationID){$selected=" selected";}else{$selected="";}
					print "\t\t\t\t\t<option value=\"$esc->EscalationID\"$selected>$esc->Details</option>\n";
				}

echo '				</select></div>
			</div>
		   </div> <!-- END div.table -->
		   </fieldset></div>
		</div>
		<div>
		   <div><label for="primarycontact">',__("Primary Contact"),'</label></div>
		   <div><select name="primarycontact" id="primarycontact">
				<option value=0>',__("Unassigned"),'</option>';

			foreach($contactList as $contactRow){
				if($contactRow->ContactID==$dev->PrimaryContact){$contactUserID=$contactRow->UserID;$selected=" selected";}else{$selected="";}
				print "\t\t\t\t<option value=\"$contactRow->ContactID\"$selected>$contactRow->LastName, $contactRow->FirstName</option>\n";
			}

			print "\t\t\t</select>\n";

			if(isset($config->ParameterArray['UserLookupURL']) && isValidURL($config->ParameterArray['UserLookupURL']) && isset($contactUserID)){
				print "<button type=\"button\" onclick=\"window.open( '".$config->ParameterArray["UserLookupURL"]."$contactUserID', 'UserLookup')\">".__("Contact Lookup")."</button>\n";
			}

echo '		   </div>
		</div>
		<div>
			<div><label for="tags">',__("Tags"),'</label></div>
			<div><textarea type="text" name="tags" id="tags" rows="1"></textarea></div>
		</div>
	</div> <!-- END div.table -->
</fieldset>
<fieldset id="customattrs">
<legend>',__("Custom Attributes"),'</legend>';
buildCustomAttributes($templ,$dev);
echo '
</fieldset>
	<div class="table">
		<div>
		  <div><label for="notes">',__("Notes"),'</label></div>
		  <div><textarea name="notes" id="notes" cols="40" rows="8">',$dev->Notes,'</textarea></div>
		</div>
	</div> <!-- END div.table -->
</div><!-- END div.left -->
<div class="right">
<fieldset>
	<legend>',__("Physical Infrastructure"),'</legend>
	<div class="table">
		<div>
			<div><label for="cabinetid">',__("Cabinet"),'</label></div>';

		if($dev->ParentDevice==0){
			print "\t\t\t<div>".$cab->GetCabinetSelectList()."</div>\n";
		}else{
			print "\t\t\t<div>$cab->Location<input type=\"hidden\" name=\"cabinetid\" value=$cab->CabinetID></div>
		</div>
		<div>
			<div><label for=\"parentdevice\">".__("Parent Device")."</label></div>
			<div><select name=\"parentdevice\">\n";

			foreach($parentList as $parDev){
				if($pDev->DeviceID==$parDev->DeviceID){$selected=" selected";}else{$selected="";}
				print "\t\t\t\t<option value=\"$parDev->DeviceID\"$selected>$parDev->Label</option>\n";
			}
			print "\t\t\t</select></div>\n";
		}

echo '		</div>
		<div>
			<div><label for="templateid">',__("Device Class"),'</label></div>
			<div><select name="templateid" id="templateid">
				<option value=0>',__("Select a template..."),'</option>';

			foreach($templateList as $tempRow){
				// $devarray is helping to remove invalid device templates from child devices
				if(in_array($tempRow->DeviceType, array_keys($devarray))){
					if($dev->TemplateID==$tempRow->TemplateID){$selected=" selected";}else{$selected="";}
					$mfg->ManufacturerID=$tempRow->ManufacturerID;
					$mfg->GetManufacturerByID();
					print "\t\t\t\t<option value=\"$tempRow->TemplateID\"$selected>$mfg->Name - $tempRow->Model</option>\n";
				}
			}

echo '			</select>
			</div>
		</div>
		<div>
		   <div><label for="height">',($dev->ParentDevice==0)?__("Height"):__("Number of slots"),'</label></div>
		   <div><input type="number" class="required,validate[custom[onlyNumberSp]]" name="height" id="height" value="',$dev->Height,'"></div>
		</div>
		<div>
		   <div><label for="position">',__("Position"),'</label></div>
		   <div><input type="number" class="required,validate[custom[onlyNumberSp],min[1],max[',$cab->CabinetHeight,']]" name="position" id="position" value="',$dev->Position,'"></div>
		</div>
		';

		//JMGA: child devices not use HalfDepth
		if($dev->ParentDevice==0){
echo '		<div>
			<div><label for="halfdepth">'.__("Half Depth").'</label></div>
			<div><input type="checkbox" name="halfdepth" id="halfdepth"'.(($dev->HalfDepth)?" checked":"").'></div>
		</div>';
		}
echo '		<div>
			<div><label for="backside">'.__("Back Side").'</label></div>
			<div><input type="checkbox" name="backside" id="backside"'.(($dev->BackSide)?" checked":"").'></div>
		</div>';

		echo '		<div id="dphtml">
		   <div><label for="ports">',__("Number of Data Ports"),'</label></div>
		   <div><input type="number" class="optional,validate[custom[onlyNumberSp]]" name="ports" id="ports" value="',$dev->Ports,'"></div>
		</div>';

echo '		<div>
		   <div><label for="nominalwatts">',__("Nominal Draw (Watts)"),'</label></div>
		   <div><input type="text" class="optional,validate[custom[onlyNumberSp]]" name="nominalwatts" id="nominalwatts" value="',$dev->NominalWatts,'"></div>
		</div>';

		// Blade devices don't have power supplies
		if($dev->ParentDevice==0){
			echo '		<div>
		   <div><label for="powersupplycount">',__("Power Connections"),'</label></div>
		   <div><input type="number" class="optional,validate[custom[onlyNumberSp]]" name="powersupplycount" id="powersupplycount" value="',$dev->PowerSupplyCount,'"></div>
		</div>';
		}

		// Show extra info for chassis devices
		if($dev->DeviceType=="Chassis"){
			echo '		<div>
			<div><label>'.__("Chassis Total Draw").'</label></div>
			<div><input value="'.$dev->GetDeviceTotalPower().'" size="6" disabled></input></div>
		</div>
		<div>
			<div><label>'.__("Chassis Total Weight").'</label></div>
			<div><input value="'.$dev->GetDeviceTotalWeight().'" size="6" disabled></input></div>
		</div>';
		}

echo '		<div>
		   <div>',__("Device Type"),'</div>
		   <div><select name="devicetype">
			<option value=0>',__("Select..."),'</option>';

		foreach($devarray as $devType => $translation){
			if($devType==$dev->DeviceType){$selected=" selected";}else{$selected="";}
			print "\t\t\t<option value=\"$devType\"$selected>$translation</option>\n";
		}
echo '
		   </select></div>
		</div>
	</div> <!-- END div.table -->
</fieldset>
<fieldset id="deviceimages">
	<legend>Device Images</legend>
	<div>';
		$frontpic=($templ->FrontPictureFile!='')?' src="pictures/'.$templ->FrontPictureFile.'"':'';
		$rearpic=($templ->RearPictureFile!='')?' src="pictures/'.$templ->RearPictureFile.'"':'';
echo '
		<img id="devicefront" src="pictures/'.$templ->FrontPictureFile.'" alt="front of device">
		<img id="devicerear" src="pictures/'.$templ->RearPictureFile.'" alt="rear of device">
	</div>
</fieldset>
<fieldset id="cdu" class="hide">
	<legend>'.__("Power Specifications").'</legend>
	<div class="table">
		<div>
			<div><label for="panelid">',__("Source Panel"),'</label></div>
			<div>
				<select name="panelid" id="panelid" >
					<option value=0>',__("Select Panel"),'</option>';

		$Panel=new PowerPanel();
		$PanelList=$Panel->GetPanelList();
		foreach($PanelList as $key=>$value){
			$selected=($value->PanelID == $pdu->PanelID)?' selected':"";
			print "\n\t\t\t\t\t<option value=\"$value->PanelID\"$selected>$value->PanelLabel</option>\n"; 
		}

		echo '
				</select>
			</div>
		</div>
		<div>
			<div><label for="voltage">',__("Voltages:"),'</label></div>
			<div id="voltage">';

			if($pdu->PanelID >0){
				$pnl=new PowerPanel();
				$pnl->PanelID=$pdu->PanelID;
				$pnl->GetPanel();
			
				print $pnl->PanelVoltage." / ".intval($pnl->PanelVoltage/1.73);
			}

		echo '</div>
		</div>
		<div>
			<div><label for="breakersize">',__("Breaker Size (# of Poles)"),'</label></div>
			<div>
				<select name="breakersize" id="breakersize">';

			for($i=1;$i<4;$i++){
				if($i==$pdu->BreakerSize){$selected=" selected";}else{$selected="";}
				print "\n\t\t\t\t\t<option value=\"$i\"$selected>$i</option>";
			}

		echo '
				</select>
			</div>
		</div>
		<div>
			<div><label for="panelpole">',__("Panel Pole Number"),'</label></div>
			<div><input type="text" name="panelpole" id="panelpole" size=5 value="',$pdu->PanelPole,'"></div>
		</div>';

		if($pdu->BreakerSize>1) {
			echo '
		<div>
			<div><label for="allbreakerpoles">',__("All Breaker Poles"),'</label></div>
			<div>',$pdu->GetAllBreakerPoles(),'</div>
		</div>';
		}
		echo '
		<div>
			<div><label for="inputamperage">',__("Input Amperage"),'</label></div>
			<div><input type="text" name="inputamperage" id="inputamperage" size=5 value="',$pdu->InputAmperage,'"></div>
		</div>';

		// Only show the version, etc if we aren't creating a CDU
		if($dev->DeviceID>0){
		echo '
		<div>
			<div>',__("Uptime"),'</div>
			<div id="cduuptime">',$upTime,'</div>
		</div>
		<div>
			<div>',__("Firmware Version"),'</div>
			<div>',$pdu->FirmwareVersion,'</div>
		</div>
		<div>
			<div><label for="currwatts">',__("Wattage"),'</label></div>
			<div><span>',$LastWattage,'</span><button type="button" id="btn_override" value="edit" data-edit="',__("Manual Entry"),'" data-submit="',__("Submit"),'">',__("Manual Entry"),'</button></div>
		</div>
		<div>
			<div>',__("Last Update"),':</div>
			<div id="lastread">',$LastRead,'</div>
		</div>';
		}
		echo '
		<div class="caption">
			<fieldset class="noborder">
				<legend>',__("Automatic Transfer Switch"),'</legend>
				<div class="table centermargin border">
					<div>
						<div><label for="failsafe">',__("Fail Safe Switch?"),'</label></div>
						<div><input type="checkbox" name="failsafe" id="failsafe"',(($pdu->FailSafe)?" checked":""),'></div>
					</div>
					<div>
						<div><label for="panelid2">',__("Source Panel (Secondary Source)"),'</label></div>
						<div>
							<select name="panelid2" id="panelid2">
								<option value=0>',__("Select Panel"),'</option>';

				foreach($PanelList as $key=>$value){
					if($value->PanelID==$pdu->PanelID2){$selected=" selected";}else{$selected="";}
					print "\n\t\t\t\t\t\t<option value=$value->PanelID$selected>$value->PanelLabel</option>";
				}

			echo '
							</select>
						</div>
					</div>
					<div>
						<div><label for="panelpole2">',__("Panel Pole Number (Secondary Source)"),'</label></div>
						<div><input type="text" name="panelpole2" id="panelpole2" size=4 value="',$pdu->PanelPole2,'"></div>
					</div>
				</div>
			</fieldset>
		</div>
	</div>
</fieldset>
<fieldset id="firstport" class="hide">
	<legend>'.__("Switch SNMP").'</legend>
	<div><p>'.__("Use these buttons to set the first port for the switch, check the status of the ports again, or attempt to load the Port Name labels from the switch device.").'</p><button type="button" name="firstport">'.__("Set First Port").'</button><button type="button" name="refresh">'.__("Refresh Status").'</button><button type="button" name="name">'.__("Refresh Port Names").'</button><button type="button" name="notes">'.__("Refresh Port Notes").'</button></div>
</fieldset>';

	//
	// Do not display the chassis contents block if this is a child device (ParentDevice > 0)
	//
	if($dev->DeviceType=='Chassis'){

echo '<fieldset class="chassis">
	<legend>',__("Chassis Contents"),'</legend>
	<div class="table">
		<div>
			<div>&nbsp;</div>
			<div>',__("Front"),'</div>
			<div class="greybg">',__("Rear"),'</div>
		</div>
		<div>
			<div><label for="chassisslots">',__("Number of Slots in Chassis:"),'</label></div>
			<div><input type="text" id="chassisslots" class="optional,validate[custom[onlyNumberSp]]" name="chassisslots" size="4" value="',$dev->ChassisSlots,'"></div>
			<div class="greybg"><input type="text" id="rearchassisslots" class="optional,validate[custom[onlyNumberSp]]" name="rearchassisslots" size="4" value="',$dev->RearChassisSlots,'"></div>
		</div>';

	if($dev->ChassisSlots >0 || $dev->RearChassisSlots>0){

echo '	</div>
	<div class="table">
		<div>
			<div>',__("Slot #"),'</div>
			<div>',__("Height"),'</div>
			<div>',__("Device Name"),'</div>
			<div>',__("Device Type"),'</div>
		</div>';

	foreach($childList as $chDev){
		print "\t\t<div".(($chDev->BackSide)?' class="greybg"':'').">
			<div>$chDev->Position</div>
			<div>$chDev->Height</div>
			<div><a href=\"devices.php?deviceid=$chDev->DeviceID\">$chDev->Label</a></div>
			<div>$chDev->DeviceType</div>
		</div>\n";
	}
echo '		<div class="caption">
			<button type="submit" id="adddevice" value="Child" name="action">',__("Add Device"),'</button>
			<input type="hidden" id="parentdevice" name="parentdevice" disabled value="',$dev->DeviceID,'">
		</div>';
	}else{
echo '		<div class="caption">
			',__("You must first define how many slots are in the chassis before you can add devices."),'
		</div>';
	}
?>
	</div>
</fieldset>
<?php
	}

	// Do not display ESX block if device isn't a virtual server and the user doesn't have write access
	if(($write || $dev->ESX) && ($dev->DeviceType=="Server" || $dev->DeviceType=="")){
		echo '<fieldset id="esxframe">	<legend>',__("VMWare ESX Server Information"),'</legend>';
	// If the user doesn't have write access display the list of VMs but not the configuration information.
		if($write){

echo '	<div class="table">
		<div>
		   <div><label for="esx">'.__("ESX Server?").'</label></div>
		   <div><select name="esx" id="esx"><option value="1"'.(($dev->ESX==1)?" selected":"").'>'.__("True").'</option><option value="0"'.(($dev->ESX==0)?" selected":"").'>'.__("False").'</option></select></div>
		</div>
	</div><!-- END div.table -->';

		}
		if($dev->ESX){
			buildesxtable($dev->DeviceID);
		}
		print "</fieldset>\n";
	}
?>
</div><!-- END div.right -->
<div class="table" id="pandn">
<div><div>
<div class="table style">
<?php
	// Operational log
	// This is an optional block if logging is enabled
	if(class_exists('LogActions') && $dev->DeviceID >0){
		print "\t<div>\n\t\t  <div><a>".__("Operational Log")."</a></div>\n\t\t  <div><div id=\"olog\" class=\"table border\">\n\t\t\t<div><div>".__("Date")."</div></div>\n";

		// Wrapping the actual log events with a table of their own and a div that we can style
		print "\t<div><div><div><div class=\"table\">\n";

		foreach(LogActions::GetLog($dev,false) as $logitem){
			if($logitem->Property=="OMessage"){
				print "\t\t\t<div><div>$logitem->Time</div><div>$logitem->NewVal</div></div>\n";
			}
		}

		// Closing the row, table for the log events, and the stylable div
		print "\t</div></div></div></div>\n";

		// The input box and button
		print "\t\t\t<div><div><button type=\"button\">Add note</button><div><input /></div></div></div>\n";

		print "\t\t  </div></div>\n\t\t</div>\n";
		print "\t\t<!-- Spacer --><div><div>&nbsp;</div><div></div></div><!-- END Spacer -->\n"; // spacer row
	}

	//HTML content condensed for PHP logic clarity.
	// If $pwrCords is null then we're creating a device record. Skip power checking.
	if(!is_null($pwrCords)&&((isset($_POST['action'])&&$_POST['action']!='Child')||!isset($_POST['action']))&&(!in_array($dev->DeviceType,array('Physical Infrastructure','Patch Panel')))){
		print "		<div>\n\t\t\t<div><a id=\"power\">$chassis ".__("Power Connections")."</a></div>
			<div><div class=\"table border power\">
				<div>
					<div class=\"delete\" style=\"display: none;\"></div>
					<div>#</div>
					<div id=\"ppcn\">".__("Port Name")."</div>
					<div>".__("Device")."</div>
					<div>".__("Device Port")."</div>
					<div>".__("Notes")."</div>
<!--				<div>".__("Panel")."</div> -->
				</div>\n";
			foreach($pwrCords as $i => $cord){
				$tmppdu=new Device();
				$tmppdu->DeviceID=$cord->ConnectedDeviceID;
				$tmppdu->GetDevice();
//				$panel->PanelID=$pdu->PanelID;
//				$panel->GetPanel();
				$tmpcord=new PowerPorts();
				if($cord->ConnectedDeviceID>0 && !is_null($cord->ConnectedDeviceID)){
					$tmpcord->DeviceID=$cord->ConnectedDeviceID;
					$tmpcord->PortNumber=$cord->ConnectedPort;
					$tmpcord->getPort();
				}else{
					$cord->ConnectedDeviceID=0;
					$cord->ConnectedPort=0;
				}
				print "\t\t\t\t<div data-port=$i>
					<div>$i</div>
					<div data-default=\"$cord->Label\">$cord->Label</div>
					<div data-default=$cord->ConnectedDeviceID><a href=\"devices.php?deviceid=$cord->ConnectedDeviceID\">$tmppdu->Label</a></div>
					<div data-default=$cord->ConnectedPort>$tmpcord->Label</div>
					<div data-default=\"$cord->Notes\">$cord->Notes</div>
				</div>\n";
			}
$connectioncontrols=($dev->DeviceID>0)?'
<span style="display: inline-block; vertical-align: super;">'.__("Limit device selection to").':</span>
<div id="connection-limitor" data-role="controlgroup" data-type="horizontal">
	<input type="radio" name="connection-limitor" id="radio-choice-1" value="row" />
	<label for="radio-choice-1">Row</label>
	<input type="radio" name="connection-limitor" id="radio-choice-2" value="zone" />
	<label for="radio-choice-2">Zone</label>
	<input type="radio" name="connection-limitor" id="radio-choice-3" value="dc" />
	<label for="radio-choice-3">Datacenter</label>
	<input type="radio" name="connection-limitor" id="radio-choice-4" value="global" />
	<label for="radio-choice-4">Global</label>
</div>':'';

			print "			</div><!-- END div.table --></div>\n		</div><!-- END power connections -->\n		<!-- Spacer --><div><div>&nbsp;</div><div>$connectioncontrols</div></div><!-- END Spacer -->\n";




	}

	$jsondata=array();// array to store user ability to modify a port. index=portnumber, value=true/false
	// New simplified model will apply to all devices except for patch panels and physical infrastructure
	if(!in_array($dev->DeviceType,array('Physical Infrastructure','Patch Panel')) && !empty($portList) ){
		print "		<div>\n		  <div><a id=\"net\">".__("Connections")."</a></div>\n		  <div>\n			<div class=\"table border switch\">\n				<div>
				<div>#</div>
				<div id=\"spn\">".__("Port Name")."</div>
				<div>".__("Device")."</div>
				<div>".__("Device Port")."</div>
				<div>".__("Notes")."</div>";
		if($dev->DeviceType=='Switch'){print "\t\t\t\t<div id=\"st\">".__("Status")."</div>";}
		print "\t\t\t\t<div id=\"mt\">".__("Media Type")."</div>
			<div id=\"cc\">".__("Color Code")."</div>
			</div>\n";

		foreach($portList as $i => $port){
			$tmpDev=new Device();
			$tmpDev->DeviceID=$port->ConnectedDeviceID;
			$tmpDev->GetDevice();
			
			// Allow the user to modify the port if they have rights over the switch itself or
			// the attached device.
			$jsondata[$i]=($dev->Rights=="Write")?true:($tmpDev->Rights=="Write")?true:false;

			$cp=new DevicePorts();
			if($port->ConnectedDeviceID>0 && !is_null($port->ConnectedDeviceID)){
				$cp->DeviceID=$port->ConnectedDeviceID;
				$cp->PortNumber=$port->ConnectedPort;
				$cp->getPort();
			}else{
				$port->ConnectedDeviceID=0;
				$port->ConnectedPort=0;
				$cp->Label="";
			}

			if($cp->DeviceID >0 && $cp->Label==''){$cp->Label=$cp->PortNumber;};

			$mt=(isset($mediaTypes[$port->MediaID]))?$mediaTypes[$port->MediaID]->MediaType:'';
			$cc=(isset($colorCodes[$port->ColorID]))?$colorCodes[$port->ColorID]->Name:'';

			if($dev->DeviceType=='Switch'){$linkList[$i]=(isset($linkList[$i]))?$linkList[$i]:'err';}

			// the data attribute is used to store the previous value of the connection
			print "\t\t\t\t<div data-port=$i>
					<div id=\"sp$i\">$i</div>
					<div id=\"spn$i\">$port->Label</div>
					<div id=\"d$i\" data-default=$port->ConnectedDeviceID><a href=\"devices.php?deviceid=$port->ConnectedDeviceID\">$tmpDev->Label</a></div>
					<div id=\"dp$i\" data-default=$port->ConnectedPort><a href=\"paths.php?deviceid=$port->ConnectedDeviceID&portnumber=$port->ConnectedPort\">$cp->Label</a></div>
					<div id=\"n$i\" data-default=\"$port->Notes\">$port->Notes</div>";
			if($dev->DeviceType=='Switch'){print "\t\t\t\t<div id=\"st$i\"><span class=\"ui-icon status {$linkList[$i]}\"></span></div>";}
			print "\t\t\t\t<div id=\"mt$i\" data-default=$port->MediaID>$mt</div>
					<div id=\"cc$i\" data-default=$port->ColorID>$cc</div>
				</div>\n";
		}
		echo "			</div><!-- END div.table -->\n		  </div>\n		</div>";
	}

	if($dev->DeviceType=='Patch Panel'){
		print "\n\t<div>\n\t\t<div><a name=\"net\">".__("Connections")."</a></div>\n\t\t<div>\n\t\t\t<div class=\"table border patchpanel\">\n\t\t\t\t<div><div>".__("Front")."</div><div>".__("Device Port")."</div><div>".__("Notes")."</div><div id=\"pp\">".__("Patch Port")."</div><div id=\"mt\">".__("Media Type")."</div><div id=\"rear\">".__("Back")."</div><div>".__("Device Port")."</div><div>".__("Notes")."</div></div>\n";
		for($n=0; $n< sizeof($portList)/2; $n++){
			$i = $n + 1;	// The "port number" starting at 1
			$frontDev=new Device();
			$rearDev=new Device();

			$frontDev->DeviceID=$portList[$i]->ConnectedDeviceID;
			$rearDev->DeviceID=$portList[-$i]->ConnectedDeviceID;
			$frontDev->GetDevice();
			$rearDev->GetDevice();

			// Allow the user to modify the port if they have rights over the patch panel itself or
			// the attached device, but only the front port.  The rear is still reserved for administrators only.
			$jsondata[$i]=($dev->Rights=="Write")?true:($frontDev->Rights=="Write")?true:false;

			$fp=""; //front port label
			$cPort=new DevicePorts();
			if($frontDev->DeviceID >0){
				$cPort->DeviceID=$frontDev->DeviceID;
				$cPort->PortNumber=$portList[$i]->ConnectedPort;
				$cPort->getPort();
				$fp=($cPort->Label!="")?$cPort->Label:$cPort->PortNumber;
			}

			$mt=(isset($mediaTypes[$portList[$i]->MediaID]))?$mediaTypes[$portList[$i]->MediaID]->MediaType:'';

			// rear port label
			if($portList[-$i]->ConnectedPort!=''){
				$p=new DevicePorts();
				$p->DeviceID=$portList[-$i]->ConnectedDeviceID;
				$p->PortNumber=$portList[-$i]->ConnectedPort;
				$p->getPort();
				$rp=($p->Label=='')?abs($p->PortNumber):$p->Label;
				($p->PortNumber<0)?$rp.=' ('.__("Rear").')':'';
			}else{
				$rp='';
			}

			$portList[$i]->Label=($portList[$i]->Label=='')?$i:$portList[$i]->Label;
			print "\n\t\t\t\t<div data-port=$i>
					<div id=\"fd$i\" data-default=$frontDev->DeviceID><a href=\"devices.php?deviceid=$frontDev->DeviceID\">$frontDev->Label</a></div>
					<div id=\"fp$i\" data-default={$portList[$i]->ConnectedPort}><a href=\"paths.php?deviceid=$frontDev->DeviceID&portnumber={$portList[$i]->ConnectedPort}\">$fp</a></div>
					<div id=\"fn$i\" data-default=\"{$portList[$i]->Notes}\">{$portList[$i]->Notes}</div>
					<div id=\"pp$i\">{$portList[$i]->Label}</div>
					<div id=\"mt$i\" data-default={$portList[$i]->MediaID} data-color={$portList[$i]->ColorID}>$mt</div>
					<div id=\"rd$i\" data-default=$rearDev->DeviceID><a href=\"devices.php?deviceid=$rearDev->DeviceID\">$rearDev->Label</a></div>
					<div id=\"rp$i\" data-default={$portList[-$i]->ConnectedPort}><a href=\"paths.php?deviceid=$rearDev->DeviceID&portnumber={$portList[-$i]->ConnectedPort}\">$rp</a></div>
					<div id=\"rn$i\" data-default=\"{$portList[-$i]->Notes}\">{$portList[-$i]->Notes}</div>
				</div>";
		}
		print "\t\t\t</div><!-- END div.table -->\n\t\t</div>\n\t</div>\n";
	}
?>
		<div class="caption">
<?php
	if($write){
		if($dev->DeviceID >0){
			echo '			<button type="submit" name="action" value="Update">',__("Update"),'</button>
			<button type="submit" name="action" value="Copy">', __("Copy"), '</button>
			<button type="button" name="audit">',__("Certify Audit"),'</button>';
		} else {
			echo '			<button type="submit" name="action" value="Create">',__("Create"),'</button>';
		}
	}
	// Delete rights are seperate from write rights
	if(($write || $person->DeleteAccess) && $dev->DeviceID >0){
		echo '		<button type="button" name="action" value="Delete">',__("Delete"),'</button>';
	}
	if($dev->DeviceID >0){
		echo '		<a href="export_port_connections.php?deviceid=',$dev->DeviceID,'"><button type="button">',__("Export Connections"),'</button></a>';
	}
?>

		</div>
	</div> <!-- END div.table -->
</div></div>
</div> <!-- END div.table -->
</form>
</div></div>
<?php
	if($dev->ParentDevice >0){
		print "   <a href=\"devices.php?deviceid=$pDev->DeviceID\">[ ".__("Return to Parent Device")." ]</a><br>\n";
		print "   <a href=\"cabnavigator.php?cabinetid=".$dev->GetDeviceCabinetID()."\">[ ".__("Return to Navigator")." ]</a>";
	}elseif($dev->Cabinet >0){
		print "   <a href=\"cabnavigator.php?cabinetid=$cab->CabinetID\">[ ".__("Return to Navigator")." ]</a>";
	}else{
		if ($dev->Position>0){
			print "   <div><a href=\"storageroom.php?dc=$dev->Position\">[ ".__("Return to Storage Room")." ]</a></div>";
		}
		print "   <div><a href=\"storageroom.php\">[ ".__("Return to General Storage Room")." ]</a></div>";
	}
?>

<div id="auditconfirm" class="hide">
	<p><?php print __("Do you certify that you have completed an audit of this device?"); ?></p>
</div>

<div id="pdutest" title="Testing SNMP Communications"></div>

</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
	var portrights=$.parseJSON('<?php echo json_encode($jsondata); ?>');
	portrights['admin']=<?php echo ($person->SiteAdmin)?'true':'false'; ?>;
<?php
	if(!$write){
		print "\t\t//Disable all input if they don't have rights.
		$('#firstport button[name=firstport],#firstport button[name=name]').hide();
		$('.main input, .main select').prop('disabled', true);";
	}
?>
	$(document).ready(function() {
		// Don't attempt to open the datacenter tree until it is loaded
		function opentree(){
			if($('#datacenters .bullet').length==0){
				setTimeout(function(){
					opentree();
				},500);
			}else{
				expandToItem('datacenters','cab<?php echo $cab->CabinetID;?>');
			}
		}
		opentree();

		if(navigator.appName.indexOf("Internet Explorer")==-1){
			// experimental print function
			// only works with FF and Chrome.  Once again IE is a broken damnable mess.
			$('<button>', { 'type': 'button' }).text('Print').click(function(e){
				e.preventDefault();
				var insert=$('#header').clone(); // clone header
				var devinfo=$('<div>').addClass('device').addClass('page').append($('#deviceform').clone()); // clone devinfo
				devinfo.find('#pandn .caption').remove(); // remove buttons
				var popup=window.open('template.php',"Print", "menubar=0,location=0,height=700,width=700" );
				popup.onload=function(){
					$(popup.document.body).html(insert).after(devinfo); // put into the popup
					popup.print(); //print
				}
			}).appendTo('#pandn .caption');
		}

		// Add a spacer for use when/if port removal options are triggered
		$('.switch > div:first-child, .patchpanel > div:first-child').prepend($('<div>').addClass('delete').hide());
		// Endable Mass Change Options
		$('.switch.table, .patchpanel.table, .power.table').massedit();

		<?php echo (class_exists('LogActions') && $dev->DeviceID>0)?'LameLogDisplay();':''; ?>

		// Scroll the operations log to the bottom
		scrollolog();

		// Linkify URLs in the olog.
		function linkifyolog(){
			$('#olog .table > div').find('div + div').each(function(){
				$(this).html(urlify($(this).text()));
			});
		}
		linkifyolog();

		var zoom=$('<span>').addClass('ui-icon ui-icon-circle-zoomin').css('float','right');
		$('#olog > div:first-child > div').prepend(zoom);
		zoom.click(function(e){
			var dialog=$('<div>').html($('#olog .table').clone().addClass('border'));
			dialog.dialog({
				modal: true,
				width: $(window).width()-50,
				height: $(window).height()-50,
				beforeClose: function(){
					$(this).attr('id','');
				}
			}).attr('id','olog').find('div > div > div').css('padding','3px');
			dialog.find('div > div > div ~ div').css({'max-width':$(window).width()-114-$('#olog .table > div:first-child > div:first-child').width()});
		});

		// Setup an event listener for the enter key and prevent it from submitting the form
		$('#olog input').keypress(function (e) {    
			var charCode = e.charCode || e.keyCode || e.which;
			if (charCode  == 13) {
				// if enter is pressed and there is something in this line then submit a new operations event
				if($(this).val().trim()!=''){
					$('#olog button').trigger('click');
				}
				return false;
			}
		});
		$('#olog button').click(function(){
			if($('#olog input').val().trim()!=''){
				$.post('',{devid: $('#deviceid').val(), olog: $('#olog input').val()}).done(function(data){
					if(data){
						var row=$('<div>')
							.append($('<div>').text(getISODateTime(new Date())))
							.append($('<div>').text($('#olog input').val()));
						$('#olog .table').append(row);
						$('#olog input').val('');
						scrollolog();
						linkifyolog();
					}else{
						$('#olog input').effect('highlight', {color: 'salmon'}, 1500);
					}
				});
			}
		});

		$('.caption > button[name="audit"]').click(function(){
			$('#auditconfirm').removeClass('hide').dialog({
				modal: true,
				width: 'auto',
				buttons: {
					Yes: function(){
						$.post('',{audit: $('#deviceid').val()}).done(function(data){
							$('#auditdate').text(data.AuditStamp);
						});
						$(this).dialog("close");
					},
					No: function(){
						$(this).dialog("close");
					}
				}
			});
		});

		// Make the cabinet and template selections smart comboboxes
		$('#cabinetid').combobox();
		$('#templateid').combobox();
		$('select[name=parentdevice]').combobox();

		// Connection limitation selection
		$('#connection-limitor').buttonset().parent('div').css('text-align','right');

	});
</script>

</body>
</html>
