<?php
/*
	openDCIM

	This is the main class library for the openDCIM application, which
	is a PHP/Web based data center infrastructure management system.

	This application was originally written by Scott A. Milliken while
	employed at Vanderbilt University in Nashville, TN, as the
	Data Center Manager, and released under the GNU GPL.

	Copyright (C) 2011 Scott A. Milliken

	This program is free software:  you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published
	by the Free Software Foundation, version 3.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	For further details on the license, see http://www.gnu.org/licenses
*/

class Device {
	/*	Device:		Assets within the data center, at the most granular level.  There are three basic
					groupings of information kept about a device:  asset tracking, virtualization
					details, and physical infrastructure.
					If device templates are used, the default values for wattage and height can be
					used, but an override is allowed within the object.  Any value greater than zero
					for NominalWatts is used.  The Height is pulled from the template when selected,
					but any value set after that point is used.
	*/
	
	var $DeviceID;
	var $Label;
	var $SerialNo;
	var $AssetTag;
	var $PrimaryIP;
	var $SNMPVersion;
	var $v3SecurityLevel;
	var $v3AuthProtocol;
	var $v3AuthPassphrase;
	var $v3PrivProtocol;
	var $v3PrivPassphrase;
	var $SNMPCommunity;
	var $SNMPFailureCount;
	var $Hypervisor;
	var $APIUsername;
	var $APIPassword;
	var $APIPort;
	var $ProxMoxRealm;
	var $Owner;
	var $EscalationTimeID;
	var $EscalationID;
	var $PrimaryContact;
	var $Cabinet;
	var $Position;
	var $Height;
	var $Ports;
	var $FirstPortNum;
	var $TemplateID;
	var $NominalWatts;
	var $PowerSupplyCount;
	var $DeviceType;
	var $ChassisSlots;
	var $RearChassisSlots;
	var $ParentDevice;
	var $MfgDate;
	var $InstallDate;
	var $WarrantyCo;
	var $WarrantyExpire;
	var $Notes;
	var $Status;
	var $Rights;
	var $HalfDepth;
	var $BackSide;
	var $AuditStamp;
	var $CustomValues;
	var $Weight;

	public function __construct($deviceid=false){
		if($deviceid){
			$this->DeviceID=$deviceid;
		}
		return $this;
	}

	function MakeSafe() {
		if ( ! is_object( $this ) ) {
			// If called from a static procedure, $this is not a valid object and the routine will throw an error
			return;
		}

		// Instead of defaulting to v2c for snmp we'll default to whatever the system default is
		global $config;

		//Keep weird values out of DeviceType
		$validdevicetypes=array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure','CDU','Sensor');
		$validHypervisors=array('ESX', 'ProxMox', 'None' );
		$validSNMPVersions=array(1,'2c',3);
		$validv3SecurityLevels=array('noAuthNoPriv','authNoPriv','authPriv');
		$validv3AuthProtocols=array('MD5','SHA');
		$validv3PrivProtocols=array('DES','AES');

		$validStatus = DeviceStatus::getStatusNames();

		$this->DeviceID=intval($this->DeviceID);
		$this->Label=sanitize($this->Label);
		$this->SerialNo=sanitize($this->SerialNo);
		$this->AssetTag=sanitize($this->AssetTag);
		$this->PrimaryIP=sanitize($this->PrimaryIP);
		$this->SNMPVersion=(in_array($this->SNMPVersion, $validSNMPVersions))?$this->SNMPVersion:$config->ParameterArray["SNMPVersion"];
		$this->SNMPCommunity=sanitize($this->SNMPCommunity);
		$this->v3SecurityLevel=(in_array($this->v3SecurityLevel, $validv3SecurityLevels))?$this->v3SecurityLevel:'noAuthNoPriv';
		$this->v3AuthProtocol=(in_array($this->v3AuthProtocol, $validv3AuthProtocols))?$this->v3AuthProtocol:'MD5';
		$this->v3AuthPassphrase=sanitize($this->v3AuthPassphrase);
		$this->v3PrivProtocol=(in_array($this->v3PrivProtocol,$validv3PrivProtocols))?$this->v3PrivProtocol:'DES';
		$this->v3PrivPassphrase=sanitize($this->v3PrivPassphrase);
		$this->SNMPFailureCount=intval($this->SNMPFailureCount);
		$this->Hypervisor=(in_array($this->Hypervisor, $validHypervisors))?$this->Hypervisor:'None';
		$this->APIUserName=sanitize($this->APIUsername);
		$this->APIPassword=sanitize($this->APIPassword);
		$this->APIPort = intval($this->APIPort);
		$this->ProxMoxRealm=sanitize($this->ProxMoxRealm);
		$this->Owner=intval($this->Owner);
		$this->EscalationTimeID=intval($this->EscalationTimeID);
		$this->EscalationID=intval($this->EscalationID);
		$this->PrimaryContact=intval($this->PrimaryContact);
		$this->Cabinet=intval($this->Cabinet);
		$this->Position=intval($this->Position);
		$this->Height=intval($this->Height);
		$this->Ports=intval($this->Ports);
		$this->FirstPortNum=intval($this->FirstPortNum);
		$this->TemplateID=intval($this->TemplateID);
		$this->NominalWatts=intval($this->NominalWatts);
		$this->PowerSupplyCount=intval($this->PowerSupplyCount);
		$this->DeviceType=(in_array($this->DeviceType,$validdevicetypes))?$this->DeviceType:'Server';
		$this->ChassisSlots=intval($this->ChassisSlots);
		$this->RearChassisSlots=intval($this->RearChassisSlots);
		$this->ParentDevice=intval($this->ParentDevice);
		$this->MfgDate=sanitize($this->MfgDate);
		$this->InstallDate=sanitize($this->InstallDate);
		$this->WarrantyCo=sanitize($this->WarrantyCo);
		$this->WarrantyExpire=sanitize($this->WarrantyExpire);
		$this->Notes=sanitize($this->Notes,false);
		$this->Status=in_array( $this->Status, $validStatus )?$this->Status:"Reserved";
		$this->HalfDepth=intval($this->HalfDepth);
		$this->BackSide=intval($this->BackSide);
		$this->Weight=intval($this->Weight);
	}
	
	function MakeDisplay() {
		$this->Label=stripslashes($this->Label);
		$this->SerialNo=stripslashes($this->SerialNo);
		$this->AssetTag=stripslashes($this->AssetTag);
		$this->PrimaryIP=stripslashes($this->PrimaryIP);
		$this->SNMPCommunity=stripslashes($this->SNMPCommunity);
		$this->MfgDate=stripslashes($this->MfgDate);
		$this->InstallDate=stripslashes($this->InstallDate);
		$this->WarrantyCo=stripslashes($this->WarrantyCo);
		$this->WarrantyExpire=stripslashes($this->WarrantyExpire);
		$this->Notes=stripslashes($this->Notes);
	}

	static function RowToObject($dbRow,$filterrights=true,$extendmodel=true){
		/*
		 * Generic function that will take any row returned from the fac_Devices
		 * table and convert it to an object for use in array or other
		 *
		 * Pass false to filterrights when you don't need to check for rights for 
		 * whatever reason.
		 */

		$dev=new Device();
		$dev->DeviceID=$dbRow["DeviceID"];
		$dev->Label=$dbRow["Label"];
		$dev->SerialNo=$dbRow["SerialNo"];
		$dev->AssetTag=$dbRow["AssetTag"];
		$dev->PrimaryIP=$dbRow["PrimaryIP"];
		$dev->v3SecurityLevel=$dbRow["v3SecurityLevel"];
		$dev->v3AuthProtocol=$dbRow["v3AuthProtocol"];
		$dev->v3AuthPassphrase=$dbRow["v3AuthPassphrase"];
		$dev->v3PrivProtocol=$dbRow["v3PrivProtocol"];
		$dev->v3PrivPassphrase=$dbRow["v3PrivPassphrase"];
		$dev->SNMPVersion=$dbRow["SNMPVersion"];
		$dev->SNMPCommunity=$dbRow["SNMPCommunity"];
		$dev->SNMPFailureCount=$dbRow["SNMPFailureCount"];
		$dev->Hypervisor=$dbRow["Hypervisor"];
		$dev->APIUsername=$dbRow["APIUsername"];
		$dev->APIPassword=$dbRow["APIPassword"];
		$dev->APIPort=$dbRow["APIPort"];
		$dev->ProxMoxRealm=$dbRow["ProxMoxRealm"];
		$dev->Owner=$dbRow["Owner"];
		// Suppressing errors on the following two because they can be null and that generates an apache error
		@$dev->EscalationTimeID=$dbRow["EscalationTimeID"];
		@$dev->EscalationID=$dbRow["EscalationID"];
		$dev->PrimaryContact=$dbRow["PrimaryContact"];
		$dev->Cabinet=$dbRow["Cabinet"];
		$dev->Position=$dbRow["Position"];
		$dev->Height=$dbRow["Height"];
		$dev->Ports=$dbRow["Ports"];
		$dev->FirstPortNum=$dbRow["FirstPortNum"];
		$dev->TemplateID=$dbRow["TemplateID"];
		$dev->NominalWatts=$dbRow["NominalWatts"];
		$dev->PowerSupplyCount=$dbRow["PowerSupplyCount"];
		$dev->DeviceType=$dbRow["DeviceType"];
		$dev->ChassisSlots=$dbRow["ChassisSlots"];
		$dev->RearChassisSlots=$dbRow["RearChassisSlots"];
		$dev->ParentDevice=$dbRow["ParentDevice"];
		$dev->MfgDate=$dbRow["MfgDate"];
		$dev->InstallDate=$dbRow["InstallDate"];
		$dev->WarrantyCo=$dbRow["WarrantyCo"];
		@$dev->WarrantyExpire=$dbRow["WarrantyExpire"];
		$dev->Notes=$dbRow["Notes"];
		$dev->Status = $dbRow["Status"];
		$dev->HalfDepth=$dbRow["HalfDepth"];
		$dev->BackSide=$dbRow["BackSide"];
		$dev->AuditStamp=$dbRow["AuditStamp"];
		$dev->Weight=$dbRow["Weight"];
		$dev->GetCustomValues();
		
		$dev->MakeDisplay();

		if($extendmodel){
			// Extend our device model
			if($dev->DeviceType=="CDU"){
				$pdu=new PowerDistribution();
				$pdu->PDUID=$dev->DeviceID;
				$pdu->GetPDU();
				foreach($pdu as $prop => $val){
					$dev->$prop=$val;
				}
			}
			// Add in the "all devices" custom attributes 
			$dcaList=DeviceCustomAttribute::GetDeviceCustomAttributeList();
			if(isset($dcaList)) {
				foreach($dcaList as $dca) {
					if($dca->AllDevices==1) {
						// this will add in the attribute if it is empty
						if(!isset($dev->{$dca->Label})){
							$dev->{$dca->Label}='';
						}
					}
				}
			}
			// Add in the template specific attributes
			$tmpl=new DeviceTemplate($dev->TemplateID);
			$tmpl->GetTemplateByID();
			if(isset($tmpl->CustomValues)) {
				foreach($tmpl->CustomValues as $index => $value) {
					// this will add in the attribute if it is empty
					if(!isset($dev->{$dcaList[$index]->Label})){
						$dev->{$dcaList[$index]->Label}='';
					}
				}
			}
		}
		if($filterrights){
			$dev->FilterRights();
		} else {
			// Assume that you can read everything if the rights filtering is turned off
			$dev->Rights='Read';
		}

		return $dev;
	}

	private function FilterRights(){
		global $person;
		
		$cab=new Cabinet();
		$cab->CabinetID=$this->Cabinet;

		$this->Rights='None';
		if($person->canRead($this->Owner)){$this->Rights="Read";}
		if($person->canWrite($this->Owner)){$this->Rights="Write";} // write by device
		if($this->ParentDevice>0){ // this is a child device of a chassis
			$par=new Device();
			$par->DeviceID=$this->ParentDevice;
			$par->GetDevice();
			$this->Rights=($par->Rights=="Write")?"Write":$this->Rights;
		}elseif($cab->GetCabinet()){
			$this->Rights=($cab->Rights=="Write")?"Write":$this->Rights; // write because the cabinet is assigned
		}
		if($person->SiteAdmin && $this->DeviceType=='Patch Panel'){$this->Rights="Write";} // admin override of rights for patch panels

		// Remove information that this user isn't allowed to see
		if($this->Rights=='None'){
			$publicfields=array('DeviceID','Label','Cabinet','Position','Height','Status','DeviceType','Rights');
			foreach($this as $prop => $value){
				if(!in_array($prop,$publicfields)){
					$this->$prop=null;
				}
			}
		}
	}
	
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}

	/* All of these functions will REQUIRE the built-in SNMP functions - the external calls are simply too slow */
	static function BasicTests($DeviceID){
		global $config;

		// First check if the SNMP library is present
		if(!class_exists('OSS_SNMP\SNMP')){
			return false;
		}

		$dev=New Device();
		$dev->DeviceID=$DeviceID;

		// Make sure this is a real device and has an IP set
		// false on the rights check since we shoudln't ever need them for the snmp operations
		if(!$dev->GetDevice(false)){return false;}
		if($dev->PrimaryIP==""){return false;}

		// If the device doesn't have an SNMP community set, check and see if we have a global one
		$dev->SNMPCommunity=($dev->SNMPCommunity=="")?$config->ParameterArray["SNMPCommunity"]:$dev->SNMPCommunity;

		// We've passed all the repeatable tests, return the device object for digging
		return $dev;
	}

	// Making an attempt at reducing the lines that I was constantly repeating at a cost of making this a little more convoluted.
	/*
	 * Valid values for $snmplookup:
	 * contact - alpha numeric return of the system contact
	 * description - alpha numeric return of the system description can include line breaks
	 * location - alpha numeric return of the location if set
	 * name - alpha numeric return of the name of the system
	 * services - int 
	 * uptime - int - uptime of the device returned as ticks.  tick defined as 1/1000'th of a second
	 */
	static function OSS_SNMP_Lookup($dev,$snmplookup,$oid=null,$walk=false){
		// This is find out the name of the function that called this to make the error logging more descriptive
		$caller=debug_backtrace();
		$caller=$caller[1]['function'];

		$snmpHost=new OSS_SNMP\SNMP($dev->PrimaryIP,$dev->SNMPCommunity,$dev->SNMPVersion,$dev->v3SecurityLevel,$dev->v3AuthProtocol,$dev->v3AuthPassphrase,$dev->v3PrivProtocol,$dev->v3PrivPassphrase);
		$snmpresult=false;
		try {
			$snmpresult=(is_null($oid))?$snmpHost->useSystem()->$snmplookup(true):($walk)?$snmpHost->realWalk($oid):$snmpHost->get($oid);
		}catch (Exception $e){
			$dev->IncrementFailures();
			error_log("Device::$caller($dev->DeviceID) ".$e->getMessage());
		}

		$dev->ResetFailures();
		return $snmpresult;
	}

	// Same as above but does a walk instead of a get
	static function OSS_SNMP_Walk($dev,$snmplookup,$oid=null){
		return self::OSS_SNMP_Lookup($dev,$snmplookup,$oid,true);
	}

	function CreateDevice(){
		global $dbh;
		
		$this->MakeSafe();
		
		$this->Label=transform($this->Label);
		$this->SerialNo=transform($this->SerialNo);
		$this->AssetTag=transform($this->AssetTag);

		// SNMPFailureCount isn't in this list, because it should always start at zero 
		// (default) on new devices
		$sql="INSERT INTO fac_Device SET Label=\"$this->Label\",  
			AssetTag=\"$this->AssetTag\", PrimaryIP=\"$this->PrimaryIP\", 
			SNMPCommunity=\"$this->SNMPCommunity\", SNMPVersion=\"$this->SNMPVersion\",
			v3SecurityLevel=\"$this->v3SecurityLevel\", EscalationID=$this->EscalationID, 
			v3AuthProtocol=\"$this->v3AuthProtocol\", Position=$this->Position, 
			v3AuthPassphrase=\"$this->v3AuthPassphrase\", DeviceType=\"$this->DeviceType\",
			v3PrivProtocol=\"$this->v3PrivProtocol\", NominalWatts=$this->NominalWatts, 
			v3PrivPassphrase=\"$this->v3PrivPassphrase\", Weight=$this->Weight,
			SNMPFailureCount=$this->SNMPFailureCount, Hypervisor=\"$this->Hypervisor\", 
			APIUsername=\"$this->APIUsername\", APIPassword=\"$this->APIPassword\",
			APIPort=$this->APIPort, ProxMoxRealm=\"$this->ProxMoxRealm\",
			Owner=$this->Owner, EscalationTimeID=$this->EscalationTimeID, 
			PrimaryContact=$this->PrimaryContact, Cabinet=$this->Cabinet, Height=$this->Height, 
			Ports=$this->Ports, FirstPortNum=$this->FirstPortNum, TemplateID=$this->TemplateID, 
			PowerSupplyCount=$this->PowerSupplyCount, ChassisSlots=$this->ChassisSlots, 
			RearChassisSlots=$this->RearChassisSlots,ParentDevice=$this->ParentDevice,
			MfgDate=\"".date("Y-m-d", strtotime($this->MfgDate))."\", 
			InstallDate=\"".date("Y-m-d", strtotime($this->InstallDate))."\", 
			WarrantyCo=\"$this->WarrantyCo\", Notes=\"$this->Notes\", 
			WarrantyExpire=\"".date("Y-m-d", strtotime($this->WarrantyExpire))."\", 
			Status=\"$this->Status\", HalfDepth=$this->HalfDepth, 
			BackSide=$this->BackSide, SerialNo=\"$this->SerialNo\";";

		if(!$dbh->exec($sql)){
			$info = $dbh->errorInfo();

			error_log( "PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}

		$this->DeviceID=$dbh->lastInsertId();

		if($this->DeviceType=="CDU"){
			$pdu=new PowerDistribution();
			foreach($pdu as $prop => $val){
				if(isset($this->$prop)){
					$pdu->$prop=$this->$prop;
				}
			}
			// Damn non-standard id field
			$pdu->CabinetID=$this->Cabinet;
			$pdu->CreatePDU($this->DeviceID);
		}

		if($this->DeviceType=="Sensor"){
		}

		// Make ports last because they depend on extended devices being created in some cases
		DevicePorts::createPorts($this->DeviceID);
		PowerPorts::createPorts($this->DeviceID);

		// Deal with any custom attributes
		$dcaList=DeviceCustomAttribute::GetDeviceCustomAttributeList(true);
		// There shouldn't be any to delete, but just in case
		$this->DeleteCustomValues();
		foreach(array_intersect_key((array) $this, $dcaList) as $label=>$value){
			$this->InsertCustomValue($dcaList[$label]->AttributeID, $this->$label);
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';

		return $this->DeviceID;
	}

	function CopyDevice($clonedparent=null,$newPosition=null,$smartName=true) {
		/*
		 * Need to make a copy of a device for the purpose of assigning a reservation during a move
		 *
		 * The second parameter is optional for a copy.  If it is set and the device is a chassis
		 * this should be set to the ID of the new parent device.
		 *
		 * Also do not copy any power or network connections!
		 */
		
		// Get the device being copied
		$this->GetDevice();
		
		// If this is a chassis device then check for children to cloned BEFORE we change the deviceid
		if($this->DeviceType=="Chassis"){
			// Examine the name to try to make a smart decision about the naming
			if ( $smartName == true && preg_match("/(.+?[\[?\(]?)(\d+)-(\d+)([\)\]])?/", $this->Label, $tmpName ) ) {
				$numLen = strlen($tmpName[3]);
				$this->Label = sprintf( "%s%0".$numLen."d-%0".$numLen."d%s", $tmpName[1], $tmpName[3]+1, $tmpName[3]+($tmpName[3]-$tmpName[2]+1), @$tmpName[4]);
			} else {
				$this->Label = $this->Label . " (" . __("Copy") . ")";
			}
			$childList=$this->GetDeviceChildren();
		}	

		if($this->ParentDevice >0){
			/*
			 * Child devices will need to be constrained to the chassis. Check for open slots
			 * on whichever side of the chassis the blade is currently.  If a slot is available
			 * clone into the next available slot or return false and display an appropriate 
			 * errror message
			 */
			$tmpdev=new Device();
			$tmpdev->DeviceID=$this->ParentDevice;
			$tmpdev->GetDevice();
			preg_match("/(.+?[\[?\(]?)(\d+)-(\d+)([\)\]])?/", $tmpdev->Label, $tmpName);
			$children=$tmpdev->GetDeviceChildren();
			if($tmpdev->ChassisSlots>0 || $tmpdev->RearChassisSlots>0){
				// If we're cloning every child then there is no need to attempt to find empty slots
				if(is_null($clonedparent)){
					$front=array();
					$rear=array();
					$pos=$this->Position;
					if($tmpdev->ChassisSlots>0){
						for($i=1;$i<=$tmpdev->ChassisSlots;$i++){
							$front[$i]=false;
						}
					}
					if($tmpdev->RearChassisSlots>0){
						for($i=1;$i<=$tmpdev->RearChassisSlots;$i++){
							$rear[$i]=false;
						}
					}
					foreach($children as $child){
						($child->ChassisSlots==0)?$front[$child->Position]="yes":$rear[$child->Position]="yes";
					}
					if($this->ChassisSlots==0){
						//Front slot device
						for($i=$tmpdev->ChassisSlots;$i>=1;$i--){
							if($front[$i]!="yes"){$this->Position=$i;}
						}
					}else{
						//Rear slot device
						for($i=$tmpdev->RearChassisSlots;$i>=1;$i--){
							if($rear[$i]!="yes"){$this->Position=$i;}
						}
					}
				}
				// Make sure the position updated before creating a new device
				if((isset($pos) && $pos!=$this->Position) || !is_null($clonedparent)){
					(!is_null($clonedparent))?$this->ParentDevice=$clonedparent:'';
					$olddev=new Device();
					$olddev->DeviceID=$this->DeviceID;
					$olddev->GetDevice();
					if ( $smartName == true && preg_match("/(.*)(.\d)+(\ *[\]|\)])?/", $olddev->Label, $tmpChild ) ) {
						$numLen = strlen($tmpChild[2]);
						$this->Label = sprintf( "%s%0".$numLen."d%s", $tmpChild[1], $tmpChild[2]+sizeof($children), @$tmpChild[3]);
					}
					$this->CreateDevice();
					$olddev->CopyDeviceCustomValues($this);
					$this->DuplicateTags( $olddev->DeviceID );
				}else{
					return false;
				}
			}
		}else{
			// Set the position in the current cabinet above the usable space. This will
			// make the user change the position before they can update it.
			$cab=new Cabinet();
			$cab->CabinetID=$this->Cabinet;
			$cab->GetCabinet();
			if ( $newPosition == null ) {
				$this->Position=$cab->CabinetHeight+1;
			} else {
				$this->Position = $newPosition;
			}

			$olddev=new Device();
			$olddev->DeviceID=$this->DeviceID;
			$olddev->GetDevice();

			// Try to do some intelligent naming (sequence) if ending in a number
			if ( $smartName == true && preg_match("/(.*)(.\d)+(\ *[\]|\)])?/", $olddev->Label, $tmpName ) ) {
				$numLen = strlen($tmpName[2]);
				$this->Label = sprintf( "%s%0".$numLen."d%s", $tmpName[1], $tmpName[2]+1, @$tmpName[3]);
			}

			// And finally create a new device based on the exact same info
			$this->CreateDevice();
			$olddev->CopyDeviceCustomValues($this);
			$this->DuplicateTags( $olddev->DeviceID );
		}

		// If this is a chassis device and children are present clone them
		if(isset($childList)){
			foreach($childList as $child){
				$child->CopyDevice($this->DeviceID,null,$smartName);
			}
		}

		return true;
	}

	function CopyDeviceCustomValues($new) {
		// in this context, "$this" is the old device we are copying from, "$new" is where we are copying to
		global $dbh;
		if($this->GetDevice() && $new->GetDevice()) {
			$sql="INSERT INTO fac_DeviceCustomValue(DeviceID, AttributeID, Value) 
				SELECT $new->DeviceID, dcv.AttributeID, dcv.Value FROM fac_DeviceCustomValue dcv WHERE dcv.DeviceID=$this->DeviceID;";

			if(!$dbh->query($sql)){
				$info=$dbh->errorInfo();
				error_log("CopyDeviceCustomValues::PDO Error: {$info[2]} SQL=$sql");
				return false;
			}
			return true;
		} else { return false; }
	}

	function DuplicateTags( $sourceDeviceID ) {
		global $dbh;

		$dbh->exec( "insert ignore into fac_DeviceTags (DeviceID, TagID) select '" . $this->DeviceID . "', TagID from fac_DeviceTags where DeviceID='" . $sourceDeviceID . "'");
	}
	
	function IncrementFailures(){
		$this->MakeSafe();
		if($this->DeviceID==0){return false;}
		
		$sql="UPDATE fac_Device SET SNMPFailureCount=SNMPFailureCount+1 WHERE DeviceID=$this->DeviceID";
		
		if(!$this->query($sql)){
			error_log( "Device::IncrementFailures::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			return true;
		}
	}
	
	function ResetFailures(){
		$this->MakeSafe();
		if($this->DeviceID==0){return false;}

		$sql="UPDATE fac_Device SET SNMPFailureCount=0 WHERE DeviceID=$this->DeviceID";
		
		if(!$this->query($sql)){
			error_log( "Device::ResetFailures::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			return true;
		}
	}

	function Dispose( $DispositionID ) {
		//	Make sure the DispositionID is valid, otherwise just return false
		$dList = Disposition::getDisposition( $DispositionID );
		if ( count($dList) != 1 ) {
			return false;
		}

		//	Add the device to the Disposition
		$person = People::Current();
		$dm = new DispositionMembership();

		$dm->DispositionID = $DispositionID;
		$dm->DeviceID = $this->DeviceID;
		$dm->DisposedBy = $person->UserID;

		$dm->addDevice();

		//	If this was a chassis device, do the same to all children
		if ($this->ChassisSlots>0 || $this->RearChassisSlots>0){
			$descList=$this->GetDeviceDescendants();
			foreach($descList as $child){
				$child->Dispose();
			}
		}

		//	Now sever network and power connections (which should have already been done, but just in case)
		DevicePorts::removeConnections($this->DeviceID);
		$pc=new PowerConnection();
		$pc->DeviceID=$this->DeviceID;
		$pc->DeleteConnections();

		$this->Status = "Disposed";
		$this->Cabinet = 0;
		$this->UpdateDevice();

		return true;
	}
  
	function MoveToStorage() {
		// Cabinet ID of -1 means that the device is in the storage area
		$this->Cabinet=-1;
		$this->Position=$this->GetDeviceDCID();
		$this->UpdateDevice();
		
		// While the child devices will automatically get moved to storage as part of the UpdateDevice() call above, it won't sever their network connections
		// Multilevel chassis
		if ($this->ChassisSlots>0 || $this->RearChassisSlots>0){
			$descList=$this->GetDeviceDescendants();
			foreach($descList as $child){
				DevicePorts::removeConnections($child->DeviceID);
			}
		}

		// Delete all network connections first
		DevicePorts::removeConnections($this->DeviceID);
		// Delete all power connections too
		$pc=new PowerConnection();
		$pc->DeviceID=$this->DeviceID;
		$pc->DeleteConnections();

		return true;
	}
  
	function UpdateDevice() {
		global $dbh;
		/*
		 * Stupid User Tricks #417 - A user could change a device that has connections 
		 *   (switch or patch panel) to one that doesn't
		 * Stupid User Tricks #148 - A user could change a device that has children 
		 *   (chassis) to one that doesn't
		 *
		 * As a "safety mechanism" we simply won't allow updates if you try to change 
		 *   a chassis IF it has children
		 * For the switch and panel connections, though, we drop any defined connections
		 *
		 */
		
		$tmpDev=new Device();
		$tmpDev->DeviceID=$this->DeviceID;
		// You can't update what doesn't exist, so check for existing record first and 
		// retrieve the current location
		if(!$tmpDev->GetDevice()){
			return false;
		}

		// Check the user's permissions to modify this device, but only if it's not a 
		// CLI call
		if( php_sapi_name() != "cli" && $tmpDev->Rights!='Write'){return false;}
	
		$this->MakeSafe();	

		if($tmpDev->Cabinet!=$this->Cabinet){
			$cab=new Cabinet();
			$cab->CabinetID=$this->Cabinet;
			$cab->GetCabinet();
			// Make sure the user has rights to save a device into the new cabinet
			// Cabinet 0 is for disposed devices, Cabinet -1 is storage rooms
			if($this->Cabinet!='-1' && $this->Cabinet!=0 && $cab->Rights!="Write" ){return false;}

			// Clear the power connections
			PowerPorts::removeConnections($this->DeviceID);
		}

		// Everything after this point you already know that the Person has rights 
		// to make changes

		// A child device's cabinet must always match the parent so force it here
		if($this->ParentDevice){
			$parent=new Device();
			$parent->DeviceID=$this->ParentDevice;
			$parent->GetDevice();
			$this->Cabinet=$parent->Cabinet;
		}

		// SUT #148 - Previously defined chassis is no longer a chassis
		if($tmpDev->DeviceType == "Chassis" && $tmpDev->DeviceType != $this->DeviceType){
			// If it has children, return with no update
			$childList=$this->GetDeviceChildren();
			if(sizeof($childList)>0){
				$this->GetDevice();
				return false;
			}
		}

		// Force all uppercase for labels
		$this->Label=transform($this->Label);
		$this->SerialNo=transform($this->SerialNo);
		$this->AssetTag=transform($this->AssetTag);

		$sql="UPDATE fac_Device SET Label=\"$this->Label\", SerialNo=\"$this->SerialNo\", 
			AssetTag=\"$this->AssetTag\", PrimaryIP=\"$this->PrimaryIP\", 
			SNMPCommunity=\"$this->SNMPCommunity\", SNMPVersion=\"$this->SNMPVersion\",
			v3SecurityLevel=\"$this->v3SecurityLevel\", EscalationID=$this->EscalationID, 
			v3AuthProtocol=\"$this->v3AuthProtocol\", Position=$this->Position, 
			v3AuthPassphrase=\"$this->v3AuthPassphrase\", DeviceType=\"$this->DeviceType\",
			v3PrivProtocol=\"$this->v3PrivProtocol\", NominalWatts=$this->NominalWatts, 
			v3PrivPassphrase=\"$this->v3PrivPassphrase\", Weight=$this->Weight,
			SNMPFailureCount=$this->SNMPFailureCount, Hypervisor=\"$this->Hypervisor\", 
			APIUsername=\"$this->APIUsername\", APIPassword=\"$this->APIPassword\",
			APIPort=$this->APIPort, ProxMoxRealm=\"$this->ProxMoxRealm\", Owner=$this->Owner, 
			EscalationTimeID=$this->EscalationTimeID, PrimaryContact=$this->PrimaryContact, 
			Cabinet=$this->Cabinet, Height=$this->Height, Ports=$this->Ports, 
			FirstPortNum=$this->FirstPortNum, TemplateID=$this->TemplateID, 
			PowerSupplyCount=$this->PowerSupplyCount, ChassisSlots=$this->ChassisSlots, 
			RearChassisSlots=$this->RearChassisSlots,ParentDevice=$this->ParentDevice,
			MfgDate=\"".date("Y-m-d", strtotime($this->MfgDate))."\", 
			InstallDate=\"".date("Y-m-d", strtotime($this->InstallDate))."\", 
			WarrantyCo=\"$this->WarrantyCo\", Notes=\"$this->Notes\", 
			WarrantyExpire=\"".date("Y-m-d", strtotime($this->WarrantyExpire))."\", 
			Status=\"$this->Status\", HalfDepth=$this->HalfDepth, 
			BackSide=$this->BackSide WHERE DeviceID=$this->DeviceID;";

		// If the device won't update for some reason there is no cause to touch 
		// anything else about it so just return false
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("UpdateDevice::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}
		
		// Device has been changed to be a CDU from something else so we need to 
		// create the extra records
		if($this->DeviceType=="CDU" && $tmpDev->DeviceType!=$this->DeviceType){
			$pdu=new PowerDistribution();
			$pdu->CreatePDU($dev->DeviceID);
		// Device was changed from CDU to something else, clean up the extra shit
		}elseif($tmpDev->DeviceType=="CDU" && $tmpDev->DeviceType!=$this->DeviceType){
			$pdu=new PowerDistribution();
			$pdu->PDUID=$this->DeviceID;
			$pdu->DeletePDU();
		}

		// If we made it to a device update and the number of ports available don't 
		// match the device, just fix it.
		if($tmpDev->Ports!=$this->Ports){
			if($tmpDev->Ports>$this->Ports){ // old device has more ports
				for($n=$this->Ports; $n<$tmpDev->Ports; $n++){
					$p=new DevicePorts;
					$p->DeviceID=$this->DeviceID;
					$p->PortNumber=$n+1;
					$p->removePort();
					if($this->DeviceType=='Patch Panel'){
						$p->PortNumber=$p->PortNumber*-1;
						$p->removePort();
					}
				}
			}else{ // new device has more ports
				DevicePorts::createPorts($this->DeviceID,true);
			}
		}

		// If we made it to a device update and the number of power ports available 
		// don't match the device, just fix it.
		if($tmpDev->PowerSupplyCount!=$this->PowerSupplyCount){
			if($tmpDev->PowerSupplyCount>$this->PowerSupplyCount){
				// old device has more ports
				for($n=$this->PowerSupplyCount; $n<$tmpDev->PowerSupplyCount; $n++){
					$p=new PowerPorts();
					$p->DeviceID=$this->DeviceID;
					$p->PortNumber=$n+1;
					$p->removePort();
				}
			}else{ 
				// new device has more ports
				PowerPorts::createPorts($this->DeviceID,true);
			}
		}
		
		if(($tmpDev->DeviceType=="Switch" || $tmpDev->DeviceType=="Patch Panel") && $tmpDev->DeviceType!=$this->DeviceType){
			// SUT #417 - Changed a Switch or Patch Panel to something else (even if you 
			// change a switch to a Patch Panel, the connections are different)
			if($tmpDev->DeviceType=="Switch"){
				DevicePorts::removeConnections($this->DeviceID);
			}
			if($tmpDev->DeviceType=="Patch Panel"){
				DevicePorts::removeConnections($this->DeviceID);
				$p=new DevicePorts();
				$p->DeviceID=$this->DeviceID;
				$ports=$p->getPorts();
				foreach($ports as $i => $port){
					if($port->PortNumber<0){
						$port->removePort();
					}
				}
			}
		}

		if($this->DeviceType == "Patch Panel" && $tmpDev->DeviceType != $this->DeviceType){
			// This asshole just changed a switch or something into a patch panel. Make 
			// the rear ports.
			$p=new DevicePorts();
			$p->DeviceID=$this->DeviceID;
			if($tmpDev->Ports!=$this->Ports && $tmpDev->Ports<$this->Ports){
				// since we just made the new rear ports up there only make the first few, 
				// hopefully.
				for($n=1;$n<=$tmpDev->Ports;$n++){
					$i=$n*-1;
					$p->PortNumber=$i;
					$p->createPort();
				}
			}else{
				// make a rear port to match every front port
				$ports=$p->getPorts();
				foreach($ports as $i => $port){
					$port->PortNumber=$port->PortNumber*-1;
					$port->createPort();
				}
			}
		}

		// Check and see if we extended the model to include any of the attributes for 
		// a CDU
		if($this->DeviceType=="CDU"){
			$pdu=new PowerDistribution();
			$pdu->PDUID=$this->DeviceID;
			$pdu->GetPDU();
			foreach($pdu as $prop => $val){
				// See if the device modal was extended
				if(isset($this->$prop)){
					$pdu->$prop=$this->$prop;
				}
			}
			// Either we just updated this with new info or it's the same from the get
			$pdu->CabinetID=$this->Cabinet;
			$pdu->IPAddress=$this->PrimaryIP;
			$pdu->UpdatePDU();
		}

		// Deal with any custom attributes
		$dcaList=DeviceCustomAttribute::GetDeviceCustomAttributeList(true);
		$this->DeleteCustomValues();
		foreach(array_intersect_key((array) $this, $dcaList) as $label=>$value){
			$this->InsertCustomValue($dcaList[$label]->AttributeID, $value);
		}

		//Update children, if necesary
		if ($this->ChassisSlots>0 || $this->RearChassisSlots>0){
				$this->SetChildDevicesCabinet();
		}

		// See if this device had been previously marked as disposed - if so, remove from that listing and do some
		// sanity checks (also done in the UI, but this could be an API update)
		if ( $tmpDev->Status == "Disposed" && $this->Status != "Disposed" ) {
			DispositionMembership::removeDevice( $this->DeviceID );
		}

		if ( $this->Status == "Disposed" ) {
			// Don't allow items still marked as disposed to be placed in a cabinet at all
			$this->Cabinet = 0;
			$this->Position = 0;
		}
		
		(class_exists('LogActions'))?LogActions::LogThis($this,$tmpDev):'';
		return true;
	}

	function Audit() {
		global $dbh;

		// Make sure we're not trying to decommission a device that doesn't exist
		if(!$this->GetDevice()){
			return false;
		}

		$tmpDev=new Device();
		$tmpDev->DeviceID=$this->DeviceID;
		$tmpDev->GetDevice();

		$sql="UPDATE fac_Device SET AuditStamp=NOW() WHERE DeviceID=$this->DeviceID;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("Device:Audit::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}
		
		$this->GetDevice();

		(class_exists('LogActions'))?LogActions::LogThis($this,$tmpDev):'';
		return true;
	}

	function GetDevice($filterrights=true){
		global $dbh;
	
		$this->MakeSafe();
	
		if($this->DeviceID==0 || $this->DeviceID == null){
			return false;
		}
		
		$sql="SELECT * FROM fac_Device WHERE DeviceID=$this->DeviceID;";

		if($devRow=$dbh->query($sql)->fetch()){
			foreach(Device::RowToObject($devRow,$filterrights) as $prop => $value){
				$this->$prop=$value;
			}

			return true;
		}else{
			return false;
		}
	}
	
	function GetDeviceList( $datacenterid=null ) {
		if ( $datacenterid == null ) {
			$dcLimit = "";
		} else {
			$dcLimit = "and b.DataCenterID=" . $datacenterid;
		}
		
		$sql = "select a.* from fac_Device a, fac_Cabinet b where a.Cabinet=b.CabinetID $dcLimit order by b.DataCenterID ASC, Label ASC";
		
		$deviceList = array();
		foreach ( $this->query( $sql ) as $deviceRow ) {
			$deviceList[]=Device::RowToObject( $deviceRow );
		}
		
		return $deviceList;
	}	

	static function getDevicesByDC( $DataCenterID ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_Device where Cabinet in (select CabinetID from fac_Cabinet where DataCenterID=:DataCenterID)" );
		$st->execute( array( ":DataCenterID"=>$DataCenterID ));
		$devList = array();

		while ( $row = $st->fetch() ) {
			// We are using this mechanism instead of retrieving as PDO::FETCH_CLASS because we need to do rights filtering
			$devList[]=Device::RowToObject( $row );
		}

		return $devList;
	}

	static function GetDevicesByTemplate($templateID) {
		global $dbh;
		
		$sql = "select * from fac_Device where TemplateID='" . intval( $templateID ) . "' order by Label ASC";
		
		$deviceList = array();
		foreach ( $dbh->query( $sql ) as $deviceRow ) {
			$deviceList[]=Device::RowToObject( $deviceRow );
		}
		
		return $deviceList;
	}
	
	static function GetSwitchesToReport() {
		global $dbh;
		global $config;
		
		// No, Wilbur, these are not identical SQL statement except for the tag.  Please don't combine them, again.
		if ( $config->ParameterArray["NetworkCapacityReportOptIn"] == "OptIn") {
			$sql="SELECT * FROM fac_Device a, fac_Cabinet b WHERE a.Cabinet=b.CabinetID 
				AND DeviceType=\"Switch\" AND DeviceID IN (SELECT DeviceID FROM 
				fac_DeviceTags WHERE TagID IN (SELECT TagID FROM fac_Tags WHERE 
				Name=\"Report\")) ORDER BY b.DataCenterID ASC, b.Location ASC, Label ASC;";
		} else {
			$sql="SELECT * FROM fac_Device a, fac_Cabinet b WHERE a.Cabinet=b.CabinetID 
				AND DeviceType=\"Switch\" AND DeviceID NOT IN (SELECT DeviceID FROM 
				fac_DeviceTags WHERE TagID IN (SELECT TagID FROM fac_Tags WHERE 
				Name=\"NoReport\")) ORDER BY b.DataCenterID ASC, b.Location ASC, Label ASC;";
		}

		$deviceList=array();
		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[]=Device::RowToObject($deviceRow);
		}
		
		return $deviceList;
	}
	
	function GetDevicesbyAge($days=7){
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE DATEDIFF(CURDATE(),InstallDate)<=".
			intval($days)." ORDER BY InstallDate ASC;";
		
		$deviceList=array();
		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[]=Device::RowToObject($deviceRow);
		}
		
		return $deviceList;
	}
		
	function GetDeviceChildren() {
		global $dbh;
	
		$this->MakeSafe();
	

		// $sql="SELECT * FROM fac_Device WHERE ParentDevice=$this->DeviceID ORDER BY ChassisSlots, Position ASC;";
		// JMGA
		$sql="SELECT * FROM fac_Device WHERE ParentDevice=$this->DeviceID ORDER BY BackSide, Position ASC;";

		$childList = array();

		foreach($dbh->query($sql) as $row){
			$childList[]=Device::RowToObject($row);
		}
		
		return $childList;
	}
	
  function GetDeviceDescendants() {
		global $dbh;
		
		$dev=New Device();
	
		$this->MakeSafe();
	

		$sql="SELECT * FROM fac_Device WHERE ParentDevice=$this->DeviceID ORDER BY BackSide, Position ASC;";

		$descList = array();
		$descList2 = array();

		foreach($dbh->query($sql) as $row){
			$dev=Device::RowToObject($row);
			$descList[]=$dev;
			if ($dev->ChassisSlots>0 || $dev->RearChassisSlots>0){
				$descList2=$dev->GetDeviceDescendants();
				$descList=array_merge($descList,$descList2);
			}
		}
		
		return $descList;
	}
	
	function GetParentDevices(){
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE ChassisSlots>0 OR RearChassisSlots>0 ORDER BY Label ASC;";

		$parentList=array();
		foreach($dbh->query($sql) as $row){
			// Assigning here will trigger the FilterRights method and check the cabinet rights
			$temp=Device::RowToObject($row);
			if($temp->DeviceID==$this->ParentDevice || $temp->Rights=="Write"){
				$parentList[]=$temp;
			}
		}
		
		return $parentList;
	}
	
	static function GetReservationsByDate( $Days = null ) {
		global $dbh;

		// Since we are only concerned with physical space being occupied in terms of capacity, don't worry about child devices
		if ( $Days == null ) {
			$sql = "select * from fac_Device where Status='Reserved' order by InstallDate ASC";
		} else {
			$sql = sprintf( "select * from fac_Device where Status='Reserved' and InstallDate<=(CURDATE()+%d) ORDER BY InstallDate ASC", $Days );
		}
		
		$devList = array();

		foreach($dbh->query($sql) as $row){
			$devList[]=Device::RowToObject($row);
		}

		return $devList;
	}
	
	static function GetReservationsByDC( $dc ) {
		global $dbh;

		// Since we are only concerned with physical space being occupied in terms of capacity, don't worry about child devices
		$sql = sprintf( "select a.* from fac_Device a, fac_Cabinet b where a.Cabinet=b.CabinetID and b.DataCenterID=%d and Status='Reserved' order by a.InstallDate ASC, a.Cabinet ASC", $dc );
		
		$devList = array();

		foreach($dbh->query($sql) as $row){
			$devList[]=Device::RowToObject($row);
		}

		return $devList;
	}
	
	static function GetReservationsByOwner( $Owner ) {
		global $dbh;

		// Since we are only concerned with physical space being occupied in terms of capacity, don't worry about child devices
		$sql = sprintf( "select * from fac_Device where Owner=%d and Status='Reserved' order by InstallDate ASC, Cabinet ASC", $Owner );
		
		$devList = array();

		foreach($dbh->query($sql) as $row){
			$devList[]=Device::RowToObject($row);
		}

		return $devList;
	}
	
	function WhosYourDaddy(){
		$dev=new Device();
		
		if($this->ParentDevice==0){
			return $dev;
		}else{
			$dev->DeviceID=$this->ParentDevice;
			$dev->GetDevice();
			return $dev;
		}
	}

	function ViewDevicesByCabinet($includechildren=false){
	//this function should be a method of class "cabinet", not "device"	
		global $dbh;

		$this->MakeSafe();
		
		$cab=new Cabinet();
		$cab->CabinetID=$this->Cabinet;
		$cab->GetCabinet();

		// leaving the u1 check here for later		
		$order=" ORDER BY Position".((isset($cab->U1Position) && $cab->U1Position=="Top")?" ASC":" DESC");

		if($includechildren){
			$sql="SELECT * FROM fac_Device WHERE Cabinet=$this->Cabinet$order;";
		}elseif ($this->Cabinet<0){
			//StorageRoom
			if($this->Position>0){
				$sql="SELECT * FROM fac_Device WHERE Cabinet=$this->Cabinet AND 
					Position=$this->Position$order;";
			}else{
				$sql="SELECT * FROM fac_Device WHERE Cabinet=$this->Cabinet$order;";
			}
		}else{
			$sql="SELECT * FROM fac_Device WHERE Cabinet=$this->Cabinet AND 
				ParentDevice=0$order;";
		}
		
		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[]=Device::RowToObject($deviceRow);
		}

		return $deviceList;
	}
	
	function DeleteDevice(){
		global $dbh;

		// Can't delete something that doesn't exist
		if(!$this->GetDevice()){
			return false;
		}
	
		// First, see if this is a chassis that has children, if so, delete all of the children first
		if($this->ChassisSlots >0){
			$childList=$this->GetDeviceChildren();
			
			foreach($childList as $tmpDev){
				$tmpDev->DeleteDevice();
			}
		}

		// If this is a CDU then remove it from the other table
		if($this->DeviceType=="CDU"){
			$pdu=new PowerDistribution();
			$pdu->PDUID=$this->DeviceID;
			$pdu->DeletePDU();
		}

		// Delete any project membership
		ProjectMembership::removeMember( $this->DeviceID, 'Device' );
	
		// Delete all network connections first
		DevicePorts::removePorts($this->DeviceID);
		
		// Delete power connections next
		PowerPorts::removePorts($this->DeviceID);

		// Remove custom values
		$this->DeleteCustomValues();

		// Now delete the device itself
		$sql="DELETE FROM fac_Device WHERE DeviceID=$this->DeviceID;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	function SearchDevicebyLabel(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Device WHERE Label LIKE \"%$this->Label%\" ORDER BY Label;";

		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}

		return $deviceList;
	}

	function SearchDevicebyIP(){
		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Device WHERE Status<>'Disposed' AND PrimaryIP LIKE \"%$this->PrimaryIP%\" ORDER BY Label;";

		$deviceList = array();
		foreach($this->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}

		return $deviceList;
	}

	function GetDevicesbyOwner(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT *, (SELECT b.DataCenterID FROM fac_Device a, fac_Cabinet b 
			WHERE a.Cabinet=b.CabinetID AND a.DeviceID=search.DeviceID ORDER BY 
			b.DataCenterID, a.Label) DataCenterID FROM fac_Device search WHERE 
			Status<>'Disposed' AND Owner=$this->Owner ORDER BY Label;";

		$deviceList=array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}

		return $deviceList;
	}

        function GetESXDevices() {
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE Status<>'Disposed' AND Hypervisor='ESX' ORDER BY DeviceID;";

		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){ 
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}

		return $deviceList;
	}

	function Search($indexedbyid=false,$loose=false){
		global $dbh;
		$o=array();
		// Store any values that have been added before we make them safe 
		foreach($this as $prop => $val){
			if(isset($val)){
				$o[$prop]=$val;
			}
		}

		// Make everything safe for us to search with
		$this->MakeSafe();

		// Set this to assume we don't need to add in custom attributes until we explicitly need to
		$customSQL="";
		$attrList=DeviceCustomAttribute::GetDeviceCustomAttributeList(true);

		// This will store all our extended sql
		$sqlextend="";
		foreach($o as $prop => $val){
			if(property_exists("Device",$prop)){
				extendsql($prop,$this->$prop,$sqlextend,$loose);
			}else{
				if(array_key_exists($prop,$attrList)){
					attribsql($attrList[$prop]->AttributeID,$val,$customSQL,$loose);
				}else{
					// The requested attribute is not valid.  Ain't nobody got time for that!
				}
			}
		}
		if($sqlextend==""){
			// No base attributes to search, only custom
			$sqlextend="WHERE TRUE";
		}
		if($customSQL!=""){
			$customSQL="AND DeviceID IN (SELECT DeviceID FROM fac_DeviceCustomValue $customSQL)";
		}
		$sql="SELECT * FROM fac_Device $sqlextend $customSQL ORDER BY Label ASC;";

		$deviceList=array();

		foreach($dbh->query($sql) as $deviceRow){
			if($indexedbyid){
				$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
			}else{
				$deviceList[]=Device::RowToObject($deviceRow);
			}
		}

		return $deviceList;
	}

	// Make a simple reference to a loose search
	function LooseSearch($indexedbyid=false){
		return $this->Search($indexedbyid,true);
	}

	function SearchDevicebySerialNo(){
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_Device WHERE SerialNo LIKE \"%$this->SerialNo%\" ORDER BY Label;";

		$deviceList=array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}

		return $deviceList;
	}

	function SearchDevicebyAssetTag(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Device WHERE AssetTag LIKE \"%$this->AssetTag%\" ORDER BY Label;";

		$deviceList=array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}

		return $deviceList;

	}
  
	function SearchByCustomTag($tag=null){
		global $dbh;
		
		//
		//Build a somewhat ugly SQL expression in order to do 
		//semi-complicated tag searches.  All tags are
		//logically AND'ed togther.  Thus, if you search for tags
		//'foo' and 'bar' and '!quux', the results should be only 
		//those systems with both 'foo' and 'bar' tags while 
		//excluding those with 'quux'.
		//

		// Basic start of the query.
		$sql = "SELECT DISTINCT a.* FROM fac_Device a, fac_DeviceTags b, fac_Tags c WHERE a.DeviceID=b.DeviceID AND b.TagID=c.TagID ";

		//split the "tag" if needed, and strip whitespace
		//note that tags can contain spaces, so we have to use
		//something else in the search string (commas seem logical)
		$tags = explode(",", $tag);
		$tags = array_map("trim", $tags);

		//Two arrays, one of tags we want, and one of those we don't want.
		$want_tags = array();
		$not_want_tags = array();

		foreach ( $tags as $t ) {
			//If the tag starts with a "!" character, we want to 
			//specifically exclude it from the search.
			if (strpos($t, '!') !== false ) {
				$t=preg_replace('/^!/', '', $t,1);	//remove the leading "!" from the tag
			$not_want_tags[].= $t;
			} else {
				$want_tags[] .= $t;
			}
		}

		/*
		error_log(join(',',$want_tags));
		error_log(join(',',$not_want_tags));
		*/
		$num_want_tags = count($want_tags);
		if (count($want_tags)) {
			// This builds the part of the query that looks for all tags we want.
			// First, some basic SQL to start with
			$sql .= 'AND c.TagId in ( ';
			$sql .= 'SELECT Want.TagId from fac_Tags Want WHERE ';

			// Loop over the tags we want.
			$want_sql = sprintf("UCASE(Want.Name) LIKE UCASE('%%%s%%')", array_shift($want_tags));
			foreach ($want_tags as $t) {
				$want_sql .= sprintf(" OR UCASE(Want.Name) LIKE UCASE('%%%s%%')", $t);
			}

			$sql .= "( $want_sql ) )"; //extra parens for closing sub-select

		}

		//only include this section if we have negative tags
		if (count($not_want_tags)) {
			$sql .= 'AND a.DeviceID NOT IN ( ';
			$sql .= 'SELECT D.DeviceID FROM fac_Device D, fac_DeviceTags DT, fac_Tags T ';
			$sql .= 'WHERE D.DeviceID = DT.DeviceID ';
			$sql .= '  AND DT.TagID=T.TagID ';

			$not_want_sql = sprintf("UCASE(T.Name) LIKE UCASE('%%%s%%')", array_shift($not_want_tags));
            foreach ($not_want_tags as $t) {
                $not_want_sql .= sprintf(" OR UCASE(c.Name) LIKE UCASE('%%%s%%')", $t);
            }
			$sql .= "  AND ( $not_want_sql ) )"; //extra parens to close sub-select
		}

		// This bit of magic filters out the results that don't match enough tags.
		$sql .= "GROUP BY a.DeviceID HAVING COUNT(c.TagID) >= $num_want_tags";

		//error_log(">> $sql\n");

		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}
		
		return $deviceList;
	}

	function SearchByCustomAttribute($searchTerm=null){
		global $dbh;
		
		//
		//Build a somewhat ugly SQL expression in order to do 
		//semi-complicated attribute searches.  All attributes are
		//logically AND'ed togther.  Thus, if you search for attributes 
		//'foo' and 'bar' and '!quux', the results should be only 
		//those systems with both 'foo' and 'bar' attributes while 
		//excluding those with 'quux'.
		//

		// Basic start of the query.
		$sql = "SELECT DISTINCT a.* FROM fac_Device a, fac_DeviceCustomValue b WHERE a.DeviceID=b.DeviceID ";

		//split the searchTerm if needed, and strip whitespace
		//note that search terms can contain spaces, so we have to use
		//something else in the search string (commas seem logical)
		$terms = explode(",", $searchTerm);
		$terms = array_map("trim", $terms);

		//Two arrays, one of terms we want, and one of those we don't want.
		$want_terms = array();
		$not_want_terms = array();

		foreach ( $terms as $t ) {
			//If the term starts with a "!" character, we want to 
			//specifically exclude it from the search.
			if (strpos($t, '!') !== false ) {
				$t=preg_replace('/^!/', '', $t,1);	//remove the leading "!" from the term
			$not_want_terms[].= $t;
			} else {
				$want_terms[] .= $t;
			}
		}
		/*
		error_log(join(',',$want_terms));
		error_log(join(',',$not_want_terms));
		*/
		$num_want_terms = count($want_terms);
		if (count($want_terms)) {
			// This builds the part of the query that looks for all terms we want.

			$sql .= " AND a.DeviceID IN ( SELECT DeviceID from fac_DeviceCustomValue WHERE ";
			// Loop over the terms  we want.
			$want_sql = sprintf("UCASE(Value) LIKE UCASE('%%%s%%')", array_shift($want_terms));
			foreach ($want_terms as $t) {
				$want_sql .= sprintf(" OR UCASE(Value) LIKE UCASE('%%%s%%')", $t);
			}

			$sql .= " $want_sql ) "; //extra parens for closing sub-select

		}

		//only include this section if we have negative terms
		if (count($not_want_terms)) {

			$sql .= " AND a.DeviceID NOT IN (SELECT DeviceID from fac_DeviceCustomValue WHERE ";

			$not_want_sql = sprintf("UCASE(Value) LIKE UCASE('%%%s%%')", array_shift($not_want_terms));
			foreach ($not_want_terms as $t) {
				$not_want_sql .= sprintf(" OR UCASE(Value) LIKE UCASE('%%%s%%')", $t);
			}
			$sql .= "  $not_want_sql ) "; //extra parens to close sub-select
		}

		// This bit of magic filters out the results that don't match enough terms.
		$sql .= "GROUP BY a.DeviceID HAVING COUNT(b.AttributeID) >= $num_want_terms";

		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}
		
		return $deviceList;
	}
	
	function UpdateWattageFromTemplate() {
		$tmpl=new DeviceTemplate();
		$tmpl->TemplateID=$this->TemplateID;
		$tmpl->GetTemplateByID();

		$this->NominalWatts=$tmpl->Wattage;
	}
	
	function GetTop10Tenants(){
		global $dbh;
		
		$sql="SELECT SUM(Height) AS RackUnits,fac_Department.Name AS OwnerName FROM 
			fac_Device,fac_Department WHERE Owner IS NOT NULL AND fac_Device.Status<>'Disposed' AND
			fac_Device.Owner=fac_Department.DeptID GROUP BY Owner ORDER BY RackUnits 
			DESC LIMIT 0,10";

		$deptList = array();
		
		foreach($dbh->query($sql) as $row){
			$deptList[$row["OwnerName"]]=$row["RackUnits"];
		}
		  
		return $deptList;
	}
  
  
	function GetTop10Power(){
		global $dbh;
		
		$sql="SELECT SUM(NominalWatts) AS TotalPower,fac_Department.Name AS OwnerName 
			FROM fac_Device,fac_Department WHERE Owner IS NOT NULL AND fac_Device.Status<>'Disposed' AND
			fac_Device.Owner=fac_Department.DeptID GROUP BY Owner ORDER BY TotalPower 
			DESC LIMIT 0,10";

		$deptList=array();

		foreach($dbh->query($sql) as $row){
			$deptList[$row["OwnerName"]]=$row["TotalPower"];
		}
		  
		return $deptList;
	}
  
  
  function GetDeviceDiversity(){
	global $dbh;
	
    $pc=new PowerConnection();
    $PDU=new PowerDistribution();
	
	// If this is a child (card slot) device, then only the parent will have power connections defined
	if($this->ParentDevice >0){
		$tmpDev=new Device();
		$tmpDev->DeviceID=$this->ParentDevice;
		
		$sourceList=$tmpDev->GetDeviceDiversity();
	}else{
		$pc->DeviceID=$this->DeviceID;
		$pcList=$pc->GetConnectionsByDevice();
		
		$sourceList=array();
		$sourceCount=0;
		
		foreach($pcList as $pcRow){
			$PDU->PDUID=$pcRow->PDUID;
			$powerSource=$PDU->GetSourceForPDU();

			if(!in_array($powerSource,$sourceList)){
				$sourceList[$sourceCount++]=$powerSource;
			}
		}
	}
	
    return $sourceList;
  }

  function GetSinglePowerByCabinet(){
	global $dbh;
	
    // Return an array of objects for devices that
    // do not have diverse (spread across 2 or more sources)
    // connections to power
    $pc = new PowerConnection();
    $PDU = new PowerDistribution();
    
    $sourceList = $this->ViewDevicesByCabinet();

    $devList = array();
    
    foreach ( $sourceList as $devRow ) {    
      if ( ( $devRow->DeviceType == 'Patch Panel' || $devRow->DeviceType == 'Physical Infrastructure' || $devRow->ParentDevice > 0 ) && ( $devRow->PowerSupplyCount == 0 ) )
        continue;

      $pc->DeviceID = $devRow->DeviceID;
      
      $diversityList = $devRow->GetDeviceDiversity();
      
		if(sizeof($diversityList) <2){      
			$currSize=sizeof($devList);
			$devList[$currSize]=$devRow;
		}
    }
    
    return $devList;
  }

	function GetTags() {
		global $dbh;
		
		$sql="SELECT TagID FROM fac_DeviceTags WHERE DeviceID=".intval($this->DeviceID).";";

		$tags=array();

		foreach($dbh->query($sql) as $tagid){
			$tags[]=Tags::FindName($tagid[0]);
		}

		return $tags;
	}
	
	function SetTags($tags=array()) {
		global $dbh;

		$this->MakeSafe();		
		if(count($tags)>0){
			//Clear existing tags
			$this->SetTags();
			foreach($tags as $tag){
				$t=Tags::FindID($tag);
				if($t==0){
					$t=Tags::CreateTag($tag);
				}
				$sql="INSERT INTO fac_DeviceTags (DeviceID, TagID) VALUES ($this->DeviceID,$t);";
				if(!$dbh->exec($sql)){
					$info=$dbh->errorInfo();

					error_log("PDO Error: {$info[2]} SQL=$sql");
					return false;
				}				
			}
		}else{
			//If no array is passed then clear all the tags
			$sql="DELETE FROM fac_DeviceTags WHERE DeviceID=$this->DeviceID;";
			if(!$dbh->exec($sql)){
				return false;
			}
		}
		return;
	}
	
	function GetDeviceCabinetID(){
		$tmpDev = new Device();
		$tmpDev->DeviceID = $this->GetRootDeviceID();
		$tmpDev->GetDevice();
		return $tmpDev->Cabinet;	
	}
	
	function GetDeviceDCID(){
		$rootDev = new Device();
		$rootDev->DeviceID = $this->GetRootDeviceID();
		$rootDev->GetDevice();
		if ($rootDev->Cabinet>0){
			$cab = new Cabinet();
			$cab->CabinetID = $rootDev->Cabinet;
			$cab->GetCabinet();
			return $cab->DataCenterID;
		}else{
			//root device is in StorageRomm. DataCenterID is in his Position field.
			return $rootDev->Position;
		}
	}
	
	function GetDeviceLineage() {
		$devList=array();
		$num=1;
		$devList[$num]=new Device($this->DeviceID);
		$devList[$num]->GetDevice();
		
		while($devList[$num]->ParentDevice>0){
			$num++;
			$devList[$num]=new Device($devList[$num-1]->ParentDevice);
			$devList[$num]->GetDevice();
		}
		return $devList;	
	}

	function GetRootDeviceID(){
		$tmpDev = new Device();
		$tmpDev->DeviceID = $this->DeviceID;
		$tmpDev->GetDevice();
		
		while ( $tmpDev->ParentDevice <> 0) {
			$tmpDev->DeviceID = $tmpDev->ParentDevice;
			$tmpDev->GetDevice();
		}
		return $tmpDev->DeviceID;	
	}
	
	function GetDeviceTotalPower(){
		// Make sure we read the device from the db and didn't just get the device ID
		if(!isset($this->Rights)){
			if(!$this->GetDevice()){
				return 0;
			}
		}

		//calculate device power including child devices power
		$TotalPower=0;
		//own device power
		if($this->NominalWatts>0){
			$TotalPower=$this->NominalWatts;
		}elseif ($this->TemplateID>0){
			$templ=new DeviceTemplate();
			$templ->TemplateID=$this->TemplateID;
			$templ->GetTemplateByID();
			$TotalPower=$templ->Wattage;
		}

		//child device power
		if($this->ChassisSlots >0 || $this->RearChassisSlots >0){
			$childList=$this->GetDeviceChildren();
			foreach($childList as $tmpDev){
				$TotalPower+=$tmpDev->GetDeviceTotalPower();
			}
		}
		return $TotalPower;	
	}

	function GetDeviceTotalWeight(){
		// Make sure we read the device from the db and didn't just get the device ID
		if(!isset($this->Rights)){
			if(!$this->GetDevice()){
				return 0;
			}
		}
		//calculate device weight including child devices weight
		
		$TotalWeight=0;
		
		//own device weight
		if ($this->TemplateID>0){
			$templ=new DeviceTemplate();
			$templ->TemplateID=$this->TemplateID;
			$templ->GetTemplateByID();
			$TotalWeight=$templ->Weight;
		}
		
		//child device weight
		if($this->ChassisSlots >0 || $this->RearChassisSlots >0){
			$childList = $this->GetDeviceChildren();
			foreach ( $childList as $tmpDev ) {
				$TotalWeight+=$tmpDev->GetDeviceTotalWeight();
			}
		}
		return $TotalWeight;	
	}


	function GetChildDevicePicture($parentDetails, $rear=false){
		/*
		 * The following section will make a few assumptions
		 * - All dimensions will be given back as a percentage of the whole for scalability
		 * -- Labels will be the exception to that, we're just going to assign them values
		 * - Child devices will only have one face, front
		 * -- This makes the pictures on the templates easier to manage
		 * --- Children of an HTRAY or VTRAY will be treated as any other device with a front
		 *		and a rear image.  This makes this just stupidly complicated but has to be done
		 * -- Child devices defined with rear slots will have the rear slots ignored
		 * --- This logic needs to be applied to the functions that figure power usage and weight
		 *		so we don't end up with phantom sources
		 * - Child devices shouldn't need to conform to the 1.75:19 ratio we use for devices 
		 *		directly in a cabinet they will target the slot that they are inside
		 */
		$resp="";
		
		$templ=new DeviceTemplate();
		$templ->TemplateID=$this->TemplateID;
		$templ->GetTemplateByID();
		
		$parentDev=$parentDetails->parentDev;
		$parentTempl=$parentDetails->parentTempl;

		// API path correction
		$path="";
		if(preg_match('/api\//',str_replace(DIRECTORY_SEPARATOR, '/',getcwd()))){
			$path="../../";
		}

		// We'll only consider checking a rear image on a child if it is sitting on a shelf
		if(($parentTempl->Model=='HTRAY' || $parentTempl->Model=='VTRAY') && $rear){
			$picturefile="pictures/$templ->RearPictureFile";
		}else{
			$picturefile="pictures/$templ->FrontPictureFile";
		}
		if (!file_exists($path.$picturefile)){
			$picturefile="pictures/P_ERROR.png";
		}
		@list($width, $height)=getimagesize($path.$picturefile);
		// Make sure there is an image! DOH! If either is 0 then use a text box
		$width=intval($width);
		$height=intval($height);
		$noimage=false;
		if($width==0 || $height==0){
			$noimage=true;
			if($parentTempl->Model=='HTRAY'){
				$height=$parentDetails->targetWidth;
				$width=$parentDetails->targetHeight;
			}elseif($parentTempl->Model=='VTRAY'){
				$width=$parentDetails->targetWidth;
				$height=$parentDetails->targetHeight;
			}
		}

		// In the event of read error this will rotate a horizontal text label
		$hor_blade=($width=="" || $height=="")?true:($width>$height);

		// We only need these numbers in the event that we have a nested device
		// and need to scale the coordinates based off the original image size
		$kidsHavingKids=new stdClass();
		$kidsHavingKids->Height=($height)?$height:1;
		$kidsHavingKids->Width=($width)?$width:1;

		$slot=new Slot();
		$slotOK=false;

		//get slot from DB
		$slot->TemplateID=$parentDev->TemplateID;
		$slot->Position=$this->Position;
		$slot->BackSide=$this->BackSide;
		if(($parentTempl->Model=='HTRAY' || $parentTempl->Model=='VTRAY') || $slot->GetSlot()){
			// If we're dealing with a shelf mimic what GetSlot() would have done for our fake slot
			if($parentTempl->Model=='HTRAY' || $parentTempl->Model=='VTRAY'){
				$imageratio=($hor_blade || (!$hor_blade && $parentTempl->Model=='HTRAY'))?($width/$height):($height/$width);
				// If we don't have an image this will make the text box fit correctly, hopefully
				if($noimage){$imageratio=($parentTempl->Model=='HTRAY')?($height/$width):($width/$height);}
				$slot->W=($parentTempl->Model=='HTRAY')?$parentDetails->targetWidth/$parentDev->ChassisSlots:$parentDetails->targetWidth;
				$slot->H=($parentTempl->Model=='HTRAY')?$parentDetails->targetHeight:$parentDetails->targetHeight/$parentDev->ChassisSlots;
				$slot->X=($parentTempl->Model=='HTRAY')?($rear)?($parentDev->ChassisSlots-$this->Position-$this->Height+1)*$slot->W:($slot->Position-1)*$slot->W:0;
				$slot->Y=($parentTempl->Model=='HTRAY')?0:$parentDetails->targetHeight-$parentDetails->targetHeight/$parentDev->ChassisSlots*($this->Position+$this->Height-1);

				// Enlarge the slot if needed
				$slot->H=($parentTempl->Model=='HTRAY')?$parentDetails->targetHeight:$parentDetails->targetHeight/$parentDev->ChassisSlots*$this->Height;
				$slot->W=($parentTempl->Model=='HTRAY')?$parentDetails->targetWidth/$parentDev->ChassisSlots*$this->Height:$slot->H*$imageratio;

				// To center the devices in the slot we first needed to know the width figured just above
				$slot->X=($parentTempl->Model=='VTRAY')?($parentDetails->targetWidth-$slot->W)/2:$slot->X;

				// This covers the event that an image scaled properly will be too wide for the slot.
				// Recalculate all the things!  Shelves are stupid.
				if($parentTempl->Model=='VTRAY' && $slot->W>$parentDetails->targetWidth){
					$originalH=$slot->H;
					$slot->W=$parentDetails->targetWidth;
					$slot->H=$slot->W/$imageratio;
					$slot->X=0;
					$slot->Y=$originalH-$slot->H;
				}
				if($parentTempl->Model=='HTRAY' && $slot->W>$slot->H*$imageratio){
					$originalW=$slot->W;
					$originalX=$slot->X;
					$slot->W=$slot->H*$imageratio;
					$slot->X=($rear)?$originalX+($originalW-$slot->W):$slot->X;
				}elseif($parentTempl->Model=='HTRAY' && $slot->H>$slot->W*$this->Height/$imageratio && !$noimage){
					$originalH=$slot->H;
					$slot->H=($hor_blade)?$slot->W*$imageratio:$slot->W/$imageratio;
					$slot->Y=$originalH-$slot->H;
				}
				// Reset the zoome on the parent to 1 just for trays
				$parentDetails->zoomX=1;
				$parentDetails->zoomY=1;
			}
			// Check for slot orientation before we possibly modify it via height
			$hor_slot=($slot->W>$slot->H);

			// We dealt with the slot sizing above for trays this will bypass the next bit
			if($parentTempl->Model=='HTRAY' || $parentTempl->Model=='VTRAY'){$slotOK=true;$this->Height=0;}

			// This will prevent the freak occurance of a child device with a 0 height
			if($this->Height>=1){
				// If height==1 then just accept the defined slot as is
				if($this->Height>1){
					//get last slot
					$lslot=new Slot();
					$lslot->TemplateID=$slot->TemplateID;
					$lslot->Position=$slot->Position+$this->Height-1;
					// If the height extends past the defined slots then just get the last slot
					if($lslot->Position>(($slot->BackSide)?$parentDev->RearChassisSlots:$parentDev->ChassisSlots)){
						$lslot->Position=($slot->BackSide)?$parentDev->RearChassisSlots:$parentDev->ChassisSlots;
					}
					$lslot->BackSide=$slot->BackSide;
					if($lslot->GetSlot()){
						//calculate total size
						$xmin=min($slot->X, $lslot->X);
						$ymin=min($slot->Y, $lslot->Y);
						$xmax=max($slot->X+$slot->W, $lslot->X+$lslot->W);
						$ymax=max($slot->Y+$slot->H, $lslot->Y+$lslot->H);

						//put new size in $slot
						$slot->X=$xmin;
						$slot->Y=$ymin;
						$slot->W=$xmax-$xmin;
						$slot->H=$ymax-$ymin;
					}else{
						// Last slot isn't defined so just error out
						return;
					}
				}
				$slotOK=true;
			}
		}

		if ($slotOK){
			// Determine if the element needs to be rotated or not
			// This only evaluates if we have a horizontal image in a vertical slot
			$rotar=(!$hor_slot && $hor_blade)?"rotar_d":"";

			// Scale the slot to fit the forced aspect ratio
			$zoomX=$parentDetails->zoomX;
			$zoomY=$parentDetails->zoomY;
			$slot->X=$slot->X*$zoomX;
			$slot->Y=$slot->Y*$zoomY;
			$slot->W=$slot->W*$zoomX;
			$slot->H=$slot->H*$zoomY;
			
			if($rotar){
				$left=$slot->X-abs($slot->W-$slot->H)/2;
				$top=$slot->Y+abs($slot->W-$slot->H)/2;
				$height=$slot->W;
				$width=$slot->H;
			}else{
				$left=$slot->X;
				$top=$slot->Y;
				$height=$slot->H;
				$width=$slot->W;
			}
			$left=intval(round($left));$top=intval(round($top));
			$height=intval(round($height));$width=intval(round($width));

			// If they have rights to the device then make the picture clickable
			$clickable=($this->Rights!="None")?"\t\t\t<a href=\"devices.php?DeviceID=$this->DeviceID\">\n":"";
			$clickableend=($this->Rights!="None")?"\t\t\t</a>\n":"";
			
			// Add in flags for missing ownership
			// Device pictures are set on the template so always assume template has been set
			$flags=($this->Owner==0)?'(O)&nbsp;':'';
			$flags=($this->TemplateID==0)?$flags.'(T)&nbsp;':$flags;
			$flags=($flags!='')?'<span class="hlight">'.$flags.'</span>':'';

			$label="";
			$resp.="\t\t<div class=\"dept$this->Owner $rotar\" style=\"left: ".number_format(round($left/$parentDetails->targetWidth*100,2),2,'.','')."%; top: ".number_format(round($top/$parentDetails->targetHeight*100,2),2,'.','')."%; width: ".number_format(round($width/$parentDetails->targetWidth*100,2),2,'.','')."%; height:".number_format(round($height/$parentDetails->targetHeight*100,2),2,'.','')."%;\">\n$clickable";
//			if(($templ->FrontPictureFile!="" && !$rear) || ($templ->RearPictureFile!="" && $rear)){
			if($picturefile!='pictures/'){
				// IMAGE
				// this rotate should only happen for a horizontal slot with a vertical image
				$rotateimage=($hor_slot && !$hor_blade)?" class=\"rotar_d rlt\"  style=\"height: ".number_format(round($width/$height*100,2),2,'.','')."%; left: 100%; width: ".number_format(round($height/$width*100,2),2,'.','')."%; top: 0; position: absolute;\"":"";
				$resp.="\t\t\t\t<img data-deviceid=$this->DeviceID src=\"$picturefile\"$rotateimage alt=\"$this->Label\">\n";
				
				// LABEL FOR IMAGE
				if($hor_slot || $rotar && !$hor_slot){
					$label="\t\t\t<div class=\"label\" style=\"line-height:".$height."px; height:".$height."px;".(($height*0.8<13)?" font-size: ".intval($height*0.8)."px;":"")."\">";
				}else{
					// This is a vertical slot with a vertical picture so we have to rotate the label
					$label="\t\t\t<div class=\"rotar_d rlt label\" style=\"top: calc(".$height."px * 0.05); left: ".$width."px; width: calc(".$height."px * 0.9); line-height:".$width."px; height:".$width."px;".(($width*0.8<13)?" font-size: ".intval($width*0.8)."px; ":"")."\">";
				}
				$label.="<div>$flags$this->Label".(($rear)?" (".__("Rear").")":"")."</div></div>\n";
			}else{
				//LABEL for child device without image - Always show
				$resp.="\t\t\t\t<div class=\"label noimage\" data-deviceid=$this->DeviceID style='height: ".$height."px; line-height:".$height."px; ".(($height*0.8<13)?" font-size: ".intval($height*0.8)."px;":"")."'>";
				$resp.="<div>$flags$this->Label".(($rear)?" (".__("Rear").")":"")."</div></div>\n";
			}
			$resp.=$clickableend.$label;

// If the label on a nested chassis device proves to be a pita remove the label
// above and uncomment the following if
// if($this->ChassisSlots<4){$resp.=$label;}

			if($this->ChassisSlots >0){
				$kidsHavingKids->targetWidth=$width;
				$kidsHavingKids->targetHeight=$height;
				$kidsHavingKids->zoomX=$width/$kidsHavingKids->Width;
				$kidsHavingKids->zoomY=$height/$kidsHavingKids->Height;
				$kidsHavingKids->parentDev=$this;
				$kidsHavingKids->parentTempl=$templ;
				//multichassis
				$childList=$this->GetDeviceChildren();
				foreach($childList as $tmpDev){
					if ((!$tmpDev->BackSide && !$rear) || ($tmpDev->BackSide && $rear)){
						$resp.=$tmpDev->GetChildDevicePicture($kidsHavingKids,$rear);
					}
				}
			}
			$resp.="\t\t</div>\n";
		}
		return $resp;
	}
	function GetDevicePicture($rear=false,$targetWidth=220,$nolinks=false){
		// Just in case
		$targetWidth=($targetWidth==0)?220:$targetWidth;
		$rear=($rear==true || $rear==false)?$rear:true;
		$nolinks=($nolinks==true || $nolinks==false)?$nolinks:false;

		$templ=new DeviceTemplate();
		$templ->TemplateID=$this->TemplateID;
		$templ->GetTemplateByID();
		$resp="";

		if(($templ->FrontPictureFile!="" && !$rear) || ($templ->RearPictureFile!="" && $rear)){
			$picturefile="pictures/";
			$path="";
			if(preg_match('/api\//',str_replace(DIRECTORY_SEPARATOR, '/',getcwd()))){
				$path="../../";
			}
			$picturefile.=($rear)?$templ->RearPictureFile:$templ->FrontPictureFile;
			if (!file_exists($path.$picturefile)){
				$picturefile="pictures/P_ERROR.png";
			}

			// Get the true size of the template image
			list($pictW, $pictH)=getimagesize($path.$picturefile);

			// adjusted height = targetWidth * height:width ratio for 1u * height of device in U
			$targetHeight=$targetWidth*21/220*$this->Height;
			// Original calculation
//			$targetHeight=$targetWidth*1.75/19*$this->Height;

			// We need integers for the height and width because browsers act funny with decimals
			$targetHeight=intval($targetHeight);
			$targetWidth=intval($targetWidth);
			
			// URLEncode the image file name just to be compliant.
			$picturefile=str_replace(' ',"%20",$picturefile);

			// If they have rights to the device then make the picture clickable
			$clickable=($this->Rights!="None")?"\t\t<a href=\"devices.php?DeviceID=$this->DeviceID\">\n\t":"";
			$clickableend=($this->Rights!="None")?"\n\t\t</a>\n":"";

			// Add in flags for missing ownership
			// Device pictures are set on the template so always assume template has been set
			$flags=($this->Owner==0)?'(O)':'';
			$flags=($flags!='')?'<span class="hlight">'.$flags.'</span>':'';

			// This is for times when you want to use the image on a report but don't want links
			$nolinks=($nolinks)?' disabled':'';

			$resp.="\n\t<div class=\"picture$nolinks\" style=\"width: ".$targetWidth."px; height: ".$targetHeight."px;\">\n";
			$resp.="$clickable\t\t<img data-deviceid=$this->DeviceID src=\"$picturefile\" alt=\"$this->Label\">$clickableend\n";

			/*
			 * Labels on chassis devices were getting silly with smaller devices.  For aesthetic 
			 * reasons we are going to hide the label for the chassis devices that are less than 3U
			 * in height and have slots defined.  If it is just a chassis with nothing defined then 
			 * go ahead and show the chassis label.
			 */
			if(($this->Height<3 && $this->DeviceType=='Chassis' && (($rear && $this->RearChassisSlots > 0) || (!$rear && $this->ChassisSlots > 0))) || ($templ->Model=='HTRAY' || $templ->Model=='VTRAY') ){

			}else{
				$toneloc="";
				/* 
				 * We're going to assume that anytime we have a half-depth device it will always
				 * be mounted with its face showing.  If you email the list for help with showing
				 * the back of one, even with a valid reason, I will mock and belittle you, asshole.
				 *
				 * $rear true/false if we want the front face of the device
				 */
				if(!$this->HalfDepth){
					// if device is mounted on the back of the rack && we want the rear face
					if($this->BackSide && $rear){
						$toneloc="(".__("Rear").")";
					// if the device is mounted normally && we want the rear face
					}elseif(!$this->BackSide && $rear){
						$toneloc="(".__("Rear").")";
					}
				}

				$resp.="\t\t<div class=\"label\"><div>$flags$this->Label$toneloc</div></div>\n";
			}

			$parent=new stdClass();
			$parent->zoomX=$targetWidth/$pictW;
			$parent->zoomY=$targetHeight/$pictH;
			$parent->targetWidth=$targetWidth;
			$parent->targetHeight=$targetHeight;
			$parent->Height=$pictH;
			$parent->Width=$pictW;
			$parent->parentDev=$this;
			$parent->parentTempl=$templ;

			//Children
			$childList=$this->GetDeviceChildren();

			// Edge case where someone put more devices in a tray than they specified it slots
			if(($templ->Model=='HTRAY' || $templ->Model=='VTRAY') || ($this->ChassisSlots<count($childList))){
				$this->ChassisSlots=count($childList);
			}
			if (count($childList)>0){
				if(($this->ChassisSlots >0 && !$rear) || ($this->RearChassisSlots >0 && $rear) || ($templ->Model=='HTRAY' || $templ->Model=='VTRAY')){
					//children in front face
					foreach($childList as $tmpDev){
						if (($templ->Model=='HTRAY' || $templ->Model=='VTRAY') || ((!$tmpDev->BackSide && !$rear) || ($tmpDev->BackSide && $rear))){
							$resp.=$tmpDev->GetChildDevicePicture($parent,$rear);
						}
					}
				}
			}
			$resp.="\t</div>\n";
		}else{
			// We don't have an image on file for this device so return a generic lump of crap
			$resp="\t<div class=\"genericdevice\" data-deviceid=$this->DeviceID style=\"width: ".($targetWidth-4)."px;\">\n";
			
			// Add in flags for missing ownership
			$flags=($this->Owner==0)?'(O)':'';
			$flags.=($this->TemplateID==0)?'(T)':'';
			$flags=($flags!='')?'<span class="hlight">'.$flags.'</span>':'';

			// If they have rights to the device then make the picture clickable
			$clickable=($this->Rights!="None")?"\t\t<a href=\"devices.php?DeviceID=$this->DeviceID\">\n\t":"";
			$clickableend=($this->Rights!="None")?"\n\t\t</a>\n":"";

			$resp.="\t\t$clickable$flags$this->Label$clickableend\n";

			$resp.="\t</div>\n";
		}
		return $resp;
	}

	function GetCustomValues() {
		global $dbh;

		$this->MakeSafe();
		$dcv = array();
		$sql = "SELECT v.DeviceID, v.AttributeID, a.Label, v.Value
				FROM fac_DeviceCustomValue AS v, fac_DeviceCustomAttribute AS a
				WHERE DeviceID = $this->DeviceID AND v.AttributeID = a.AttributeID";
		foreach($dbh->query($sql) as $dcvrow){
			$this->{$dcvrow["Label"]}=$dcvrow["Value"];
		}
	}	

	function DeleteCustomValues() {
		global $dbh;

		$this->MakeSafe();
		$sql="DELETE FROM fac_DeviceCustomValue WHERE DeviceID = $this->DeviceID;";
		if($dbh->query($sql)) {
			$this->GetCustomValues();
// Commenting this out for now because it is making a confusing entry int he devicelog
// showing that a value was deleted but not what exactly.  Will revisit logging this
// when I move to make the custom values part of the primary device model
//			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
		return false;
	}

	function InsertCustomValue($AttributeID, $Value) {
		global $dbh;
	
		$this->MakeSafe();
		// make the custom attribute stuff safe
		$AttributeID = intval($AttributeID);
		$Value=sanitize(trim($Value));

		$sql = "INSERT INTO fac_DeviceCustomValue SET DeviceID = $this->DeviceID,
			AttributeID = $AttributeID, Value = \"$Value\";";
		if($dbh->query($sql)) {
			return true;
		}else{
			return false;
		}
	}
	function SetChildDevicesCabinet(){
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE ParentDevice=$this->DeviceID;";

		foreach($dbh->query($sql) as $row){
			$dev=Device::RowToObject($row);
			$dev->Cabinet=$this->Cabinet;
			$dev->UpdateDevice();
			if ($dev->ChassisSlots>0 || $dev->RearChassisSlots>0){
				$dev->SetChildDevicesCabinet();
			}
		}
	}

	// Making a function we can call from a device that will update itself and any other sensor in its immediate vicinity
	function UpdateSensor(){
		if(!$this->getDevice()){
			return false;
		}

		return Device::UpdateSensors($this->Cabinet);
	}	

	// This is a train wreck to have it in here, but everything is lumped into Devices, now...
	// this should now be functional however I question the positioning.  if we move this, update the function above
	static function UpdateSensors($CabinetID=null){
		global $config;
		global $dbh;

		// If CabinetID isn't specified try to update all sensors for the system
		$cablimit=(is_null($CabinetID))?"":" AND Cabinet=$cab->CabinetID";
		$sql="SELECT DeviceID FROM fac_Device WHERE DeviceType=\"Sensor\" AND 
			PrimaryIP!=\"\" AND TemplateID>0 AND SNMPFailureCount<3$cablimit;";

		foreach($dbh->query($sql) as $row){
			if(!$dev=Device::BasicTests($row['DeviceID'])){
				// This device failed the basic test but maybe the next won't
				continue;
			}

			$t=new SensorTemplate();
			$t->TemplateID=$dev->TemplateID;
			if(!$t->GetTemplate()){
				// Invalid template, how'd that happen?  Move on..
				continue;
			}

			$temp=($t->TemperatureOID)?floatval(self::OSS_SNMP_Lookup($dev,null,"$t->TemperatureOID")):0;
			$humidity=($t->HumidityOID)?floatval(self::OSS_SNMP_Lookup($dev,null,"$t->HumidityOID")):0;

			// Make the temp and humidity safe for sql
			$temp=float_sqlsafe($temp);
			$humidity=float_sqlsafe($humidity);

			// Strip out everything but numbers
			// not sure these are needed anymore thanks to the OSS_SNMP library
			$temp=preg_replace("/[^0-9.,+]/","",$temp);
			$humidity=preg_replace("/[^0-9.'+]/","",$humidity);

			// Apply multipliers
			$temp*=$t->TempMultiplier;
			$humidity*=$t->HumidityMultiplier;

			// Convert the units if necessary
			// device template is set to english but user wants metric so convert it
			if(($t->mUnits=="english") && ($config->ParameterArray["mUnits"]=="metric") && $temp){
				$temp=(($temp-32)*5/9);
			// device template is set to metric but the user wants english so convert it
			}elseif(($t->mUnits=="metric") && ($config->ParameterArray["mUnits"]=="english")){
				$temp=(($temp*9/5)+32);
			}

			// Assholes using commas for periods fucking up sql entries.
			$temp=number_format($temp, 2, '.', '');
			$humidity=number_format($humidity, 2, '.', '');

			// No need for any further sanitization it was all handled above
			$insertsql="INSERT INTO fac_SensorReadings SET DeviceID=$dev->DeviceID, 
				Temperature=$temp, Humidity=$humidity, LastRead=NOW() ON DUPLICATE KEY 
				UPDATE Temperature=$temp, Humidity=$humidity, LastRead=NOW();";

			if(!$dbh->query($insertsql)){
				$info=$dbh->errorInfo();

				error_log( "UpdateSensors::PDO Error: {$info[2]} SQL=$insertsql" );
				return false;
			}
		}

		return true;			
	}

	function GetSensorReading($filterrights=true){
		global $dbh;
		if(!$this->getDevice($filterrights)){
			return false;
		}
		// If this isn't a sensor device or doesn't have a template we can't have readings from it
		if($this->DeviceType!='Sensor' || $this->TemplateID==0){
			return false;
		}

		$readings=new stdClass();

		$sql="SELECT * FROM fac_SensorReadings WHERE DeviceID=$this->DeviceID LIMIT 1;";
		if(!$row=$dbh->query($sql)->fetch()){
			// Failed to get anything from the db so kick back bad data
			$readings->DeviceID=$this->DeviceID;
			$readings->Temperature=0;
			$readings->Humidity=0;
			$readings->LastRead=__("Error");
		}else{
			foreach($row as $prop => $val){
				if(!is_numeric($prop)){
					$readings->$prop=$val;
				}
			}
		}

		return $readings;
	}	

	static function resetCounter( $deviceID=false ) {
		// Simple call to reset all counters
		global $dbh;

		$p = People::Current();
		if ( ! $p->SiteAdmin ) {
			return false;
		}
		
		if ( $deviceID != false ) {
			$clause = "WHERE DeviceID=" . intval( $deviceID );
		}

		$sql = "update fac_Device set SNMPFailureCount=0 $clause";
		if ( !$dbh->query($sql)) {
			return false;
		} else {
			return true;
		}
	}
}
?>
