<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

// DOH! this is for rapid resetting while testing.  Adjust the DeviceID accordingly
//$reset="DELETE FROM fac_Device WHERE DeviceID>2170;TRUNCATE TABLE fac_PowerPorts;DELETE FROM fac_Ports WHERE DeviceID>2170;";
//$dbh->query($reset);
	/*
	 * Initial conversion from fac_PowerConnection to fac_PowerPorts
	 * to lay the groundwork for conversion of the CDUs to standard devices
	 * We'll handle this for release in the install.php
	 * this is meant for the dev branches only to do an immediate conversion.
	 */

	/*
	   I made a poor asumption on the initial build of this that we'd always have fewer
	   CDU templates than device templates.  We're seeing an overlap conversion that is 
	   screwing the pooch.  This will find the highest template id from the two sets then
	   we'll jump the line on the device_template id's and get them lined up.
	 */
    $sql="SELECT TemplateID FROM fac_CDUTemplate UNION SELECT TemplateID FROM 
		fac_DeviceTemplate ORDER BY TemplateID DESC LIMIT 1;";
    $baseid=$dbh->query($sql)->fetchColumn();

	// CDU template conversion, to be done prior to device conversion
class PowerTemplate extends DeviceTemplate {
	function CreateTemplate($templateid=null){
		global $dbh;
		
		$this->MakeSafe();

		$sqlinsert=(is_null($templateid))?'':" TemplateID=$templateid,";

		$sql="INSERT INTO fac_DeviceTemplate SET ManufacturerID=$this->ManufacturerID, 
			Model=\"$this->Model\", Height=$this->Height, Weight=$this->Weight, 
			Wattage=$this->Wattage, DeviceType=\"$this->DeviceType\", 
			SNMPVersion=\"$this->SNMPVersion\", PSCount=$this->PSCount, 
			NumPorts=$this->NumPorts, Notes=\"$this->Notes\", 
			FrontPictureFile=\"$this->FrontPictureFile\", 
			RearPictureFile=\"$this->RearPictureFile\",$sqlinsert
			ChassisSlots=$this->ChassisSlots, RearChassisSlots=$this->RearChassisSlots;";

		if(!$dbh->exec($sql)){
			error_log( "SQL Error: " . $sql );
			return false;
		}else{
			$this->TemplateID=$dbh->lastInsertId();

			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			$this->MakeDisplay();
			return true;
		}
	}

	static function Convert($row){
		$ct=new stdClass();
		$ct->TemplateID=$row["TemplateID"];
		$ct->ManufacturerID=$row["ManufacturerID"];
		$ct->Model=$row["Model"];
		$ct->PSCount=$row["NumOutlets"];
		$ct->SNMPVersion=$row["SNMPVersion"];
		return $ct;
	}
}
	
	$converted=array(); //index old id, value new id
	$sql="SELECT * FROM fac_CDUTemplate;";
	foreach($dbh->query($sql) as $cdutemplate){
		$ct=PowerTemplate::Convert($cdutemplate);
		$dt=new PowerTemplate();
		$dt->TemplateID=++$baseid;
		$dt->ManufacturerID=$ct->ManufacturerID;
		$dt->Model="CDU $ct->Model";
		$dt->PSCount=$ct->PSCount;
		$dt->DeviceType="CDU";
		$dt->SNMPVersion=$ct->SNMPVersion;
		$dt->CreateTemplate($dt->TemplateID);
		$converted[$ct->TemplateID]=$dt->TemplateID;
	}

	// Update all the records with their new templateid
	foreach($converted as $oldid => $newid){
		$dbh->query("UPDATE fac_CDUTemplate SET TemplateID=$newid WHERE TemplateID=$oldid;");
		$dbh->query("UPDATE fac_PowerDistribution SET TemplateID=$newid WHERE TemplateID=$oldid");
	}

	// END - CDU template conversion

	// Store a list of existing CDU ids and their converted DeviceID
	$ConvertedCDUs=array();
	// Store a list of the cdu template ids and the number of power connections they support
	$CDUTemplates=array();
	// List of ports we are going to create for every device in the system
	$PowerPorts=array();
	// These should only apply to me but the possibility exists
	$PreNamedPorts=array();

class PowerDevice extends Device {
	/*
		to be efficient we don't want to create ports right now so we're extending 
		the class to overrie the create function
	*/
	function CreateDevice(){
		global $dbh;
		
		$this->MakeSafe();
		
		$this->Label=transform($this->Label);
		$this->SerialNo=transform($this->SerialNo);
		$this->AssetTag=transform($this->AssetTag);
		
		$sql="INSERT INTO fac_Device SET Label=\"$this->Label\", SerialNo=\"$this->SerialNo\", AssetTag=\"$this->AssetTag\", 
			PrimaryIP=\"$this->PrimaryIP\", SNMPCommunity=\"$this->SNMPCommunity\", Hypervisor=$this->Hypervisor, Owner=$this->Owner, 
			EscalationTimeID=$this->EscalationTimeID, EscalationID=$this->EscalationID, PrimaryContact=$this->PrimaryContact, 
			Cabinet=$this->Cabinet, Position=$this->Position, Height=$this->Height, Ports=$this->Ports, 
			FirstPortNum=$this->FirstPortNum, TemplateID=$this->TemplateID, NominalWatts=$this->NominalWatts, 
			PowerSupplyCount=$this->PowerSupplyCount, DeviceType=\"$this->DeviceType\", ChassisSlots=$this->ChassisSlots, 
			RearChassisSlots=$this->RearChassisSlots,ParentDevice=$this->ParentDevice, 
			MfgDate=\"".date("Y-m-d", strtotime($this->MfgDate))."\", 
			InstallDate=\"".date("Y-m-d", strtotime($this->InstallDate))."\", WarrantyCo=\"$this->WarrantyCo\", 
			WarrantyExpire=\"".date("Y-m-d", strtotime($this->WarrantyExpire))."\", Notes=\"$this->Notes\", 
			Reservation=$this->Reservation, HalfDepth=$this->HalfDepth, BackSide=$this->BackSide;";

		if ( ! $dbh->exec( $sql ) ) {
			$info = $dbh->errorInfo();

			error_log( "PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}

		$this->DeviceID = $dbh->lastInsertId();

		(class_exists('LogActions'))?LogActions::LogThis($this):'';

		return $this->DeviceID;
	}
}

	// Create new devices from existing CDUs
	$sql="SELECT * FROM fac_PowerDistribution;";
	foreach($dbh->query($sql) as $row){
		$dev=new PowerDevice();
		$dev->Label=$row['Label'];
		$dev->Cabinet=$row['CabinetID'];
		$dev->TemplateID=$row['TemplateID'];
		$dev->PrimaryIP=$row['IPAddress'];
		$dev->SNMPCommunity=$row['SNMPCommunity'];
		$dev->Position=0;
		$dev->Height=0;
		$dev->Ports=1;
		if(!isset($CDUTemplates[$dev->TemplateID])){
			$CDUTemplates[$dev->TemplateID]=$dbh->query("SELECT NumOutlets FROM fac_CDUTemplate WHERE TemplateID=$dev->TemplateID LIMIT 1;")->fetchColumn();
		}
		$dev->PowerSupplyCount=$CDUTemplates[$dev->TemplateID];
		$dev->PowerSupplyCount;
		$dev->DeviceType='CDU';
		$ConvertedCDUs[$row['PDUID']]=$dev->CreateDevice();
	}

	// Create a list of all ports that we need to create, no need to look at children or any device with no defined power supplies
	$sql="SELECT DeviceID, PowerSupplyCount FROM fac_Device WHERE ParentDevice=0 AND PowerSupplyCount>0;";
	foreach($dbh->query($sql) as $row){
		for($x=1;$x<=$row['PowerSupplyCount'];$x++){
			$PowerPorts[$row['DeviceID']][$x]['label']=$x;
		}
	}

	function workdamnit($numeric=true){
		// a PDUID of 0 is considered an error, data fragment, etc.  Fuck em, not dealing with em.
		global $dbh,$PreNamedPorts,$PowerPorts,$ConvertedCDUs;
		$sql="SELECT * FROM fac_PowerConnection;";
		foreach($dbh->query($sql) as $row){
			$port='';
			if(is_numeric($row['PDUPosition']) && $numeric && $row['PDUID']>0){
				$port=$row['PDUPosition'];
			}elseif(!is_numeric($row['PDUPosition']) && !$numeric && $row['PDUID']>0){
				$newPDUID=$ConvertedCDUs[$row['PDUID']];
				if(!isset($PreNamedPorts[$newPDUID][$row['PDUPosition']])){
					// Move the array pointer to the end of the ports array
					end($PowerPorts[$newPDUID]);
					$max=key($PowerPorts[$newPDUID]);
					++$max;
					// Create a new port for the named port, this will likely extend past the valid amount of ports on the device.
					$PowerPorts[$newPDUID][$max]['label']=$row['PDUPosition'];
					// Store a pointer between the name and new port index
					$PreNamedPorts[$newPDUID][$row['PDUPosition']]=$max;
				}
				$port=$PreNamedPorts[$newPDUID][$row['PDUPosition']];
			}
			if((is_numeric($row['PDUPosition']) && $numeric) || (!is_numeric($row['PDUPosition']) && !$numeric) && $row['PDUID']>0){
				// Create primary connections
				$PowerPorts[$row['DeviceID']][$row['DeviceConnNumber']]['ConnectedDeviceID']=$ConvertedCDUs[$row['PDUID']];
				$PowerPorts[$row['DeviceID']][$row['DeviceConnNumber']]['ConnectedPort']=$port;

				// Create reverse of primary
				$PowerPorts[$ConvertedCDUs[$row['PDUID']]][$port]['ConnectedDeviceID']=$row['DeviceID'];
				$PowerPorts[$ConvertedCDUs[$row['PDUID']]][$port]['ConnectedPort']=$row['DeviceConnNumber'];
			}
		}	
	}

	// We need to get a list of all existing power connections
	workdamnit(); // First time through setting up all numeric ports
	workdamnit(false); // Run through again but this time only deal with named ports and append them to the end of the numeric

print "Converted CDUs:\n<br>";
	print_r($ConvertedCDUs);
print "Port list:\n<br>";
	print_r($PowerPorts);

print "SQL entries:\n<br>";
	$n=1; $insertsql=''; $insertlimit=100;
	foreach($PowerPorts as $DeviceID => $PowerPort){
		foreach($PowerPort as $PortNum => $PortDetails){
			$label=(isset($PortDetails['label']))?$PortDetails['label']:$PortNum;
			$cdevice=(isset($PortDetails['ConnectedDeviceID']))?$PortDetails['ConnectedDeviceID']:'NULL';
			$cport=(isset($PortDetails['ConnectedPort']))?$PortDetails['ConnectedPort']:'NULL';

			$insertsql.="($DeviceID,$PortNum,\"$label\",$cdevice,$cport,\"\")";
			if($n%$insertlimit!=0){
				$insertsql.=" ,";
			}else{
echo $insertsql;
				$dbh->exec('INSERT INTO fac_PowerPorts VALUES'.$insertsql);
print "\n\n<br><br>";
				print_r($dbh->errorInfo());
				$insertsql='';
			}
			$n++;
		}
	}
	//do one last insert
	$insertsql=substr($insertsql, 0, -1);// shave off that last comma
echo $insertsql;
	$dbh->exec('INSERT INTO fac_PowerPorts VALUES'.$insertsql);
print "\n\n<br><br>";
				print_r($dbh->errorInfo());

	// Update all the records with their new deviceid
	foreach($ConvertedCDUs as $oldid => $newid){
		$dbh->query("UPDATE fac_PowerDistribution SET PDUID = '$newid' WHERE PDUID=$oldid;");
	}

