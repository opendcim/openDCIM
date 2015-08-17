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

class CDUTemplate {
	var $TemplateID;
	var $ManufacturerID;
	var $Model;
	var $Managed;
	var $ATS;
	var $VersionOID;
	var $Multiplier;
	var $OID1;
	var $OID2;
	var $OID3;
	var $ATSStatusOID;
	var $ATSDesiredResult;
	var $ProcessingProfile;
	var $Voltage;
	var $Amperage;
	var $NumOutlets;

	function MakeSafe(){
		$validSNMPVersions=array(1,'2c');
		$validMultipliers=array(0.01,0.1,1,10,100);
		$validProcessingProfiles=array('SingleOIDWatts','SingleOIDAmperes',
			'Combine3OIDWatts','Combine3OIDAmperes','Convert3PhAmperes');

		$this->TemplateID=intval($this->TemplateID);
		$this->ManufacturerID=intval($this->ManufacturerID);
		$this->Model=sanitize($this->Model);
		$this->Managed=intval($this->Managed);
		$this->ATS=intval($this->ATS);
		$this->VersionOID=sanitize($this->VersionOID);
		$this->Multiplier=(in_array($this->Multiplier, $validMultipliers))?$this->Multiplier:1;
		$this->OID1=sanitize($this->OID1);
		$this->OID2=sanitize($this->OID2);
		$this->OID3=sanitize($this->OID3);
		$this->ATSStatusOID=sanitize($this->ATSStatusOID);
		$this->ATSDesiredResult=sanitize($this->ATSDesiredResult);
		$this->ProcessingProfile=(in_array($this->ProcessingProfile, $validProcessingProfiles))?$this->ProcessingProfile:'SingleOIDWatts';
		$this->Voltage=intval($this->Voltage);
		$this->Amperage=intval($this->Amperage);
		$this->NumOutlets=intval($this->NumOutlets);
	}

	function MakeDisplay(){
		$this->Model=stripslashes($this->Model);
		$this->VersionOID=stripslashes($this->VersionOID);
		$this->OID1=stripslashes($this->OID1);
		$this->OID2=stripslashes($this->OID2);
		$this->OID3=stripslashes($this->OID3);
		$this->ATSStatusOID=stripslashes($this->ATSStatusOID);
		$this->ATSDesiredResult=stripslashes($this->ATSDesiredResult);
	}

	static function RowToObject($row){
		$template=new CDUTemplate();
		$template->TemplateID=$row["TemplateID"];
		$template->ManufacturerID=$row["ManufacturerID"];
		$template->Model=$row["Model"];
		$template->Managed=$row["Managed"];
		$template->ATS=$row["ATS"];
		$template->VersionOID=$row["VersionOID"];
		$template->Multiplier=$row["Multiplier"];
		$template->OID1=$row["OID1"];
		$template->OID2=$row["OID2"];
		$template->OID3=$row["OID3"];
		$template->ATSStatusOID=$row["ATSStatusOID"];
		$template->ATSDesiredResult=$row["ATSDesiredResult"];
		$template->ProcessingProfile=$row["ProcessingProfile"];
		$template->Voltage=$row["Voltage"];
		$template->Amperage=$row["Amperage"];
		$template->NumOutlets=$row["NumOutlets"];

		$template->MakeDisplay();

		return $template;
	}
	
	function GetTemplateList(){
		global $dbh;
		
		$sql="SELECT a.* FROM fac_CDUTemplate a, fac_Manufacturer b WHERE 
			a.ManufacturerID=b.ManufacturerID ORDER BY b.Name ASC,a.Model ASC;";
		
		$tmpList=array();
		foreach($dbh->query($sql) as $row){
			$tmpList[]=CDUTemplate::RowToObject($row);
		}
		
		return $tmpList;
	}
	
	function GetTemplate(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_CDUTemplate WHERE TemplateID=$this->TemplateID";

		if($row=$dbh->query($sql)->fetch()){
			foreach(CDUTemplate::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}

			return true;
		}else{
			return false;
		}
	}
	
	function CreateTemplate($templateid) {
		global $dbh;

		$this->MakeSafe();
		
		$sql="INSERT INTO fac_CDUTemplate SET ManufacturerID=$this->ManufacturerID, 
			Model=\"$this->Model\", Managed=$this->Managed, ATS=$this->ATS,
			VersionOID=\"$this->VersionOID\", 
			Multiplier=\"$this->Multiplier\", OID1=\"$this->OID1\", OID2=\"$this->OID2\", 
			OID3=\"$this->OID3\", ATSStatusOID=\"$this->ATSStatusOID\", ATSDesiredResult=\"$this->ATSDesiredResult\",
			ProcessingProfile=\"$this->ProcessingProfile\", 
			Voltage=$this->Voltage, Amperage=$this->Amperage, NumOutlets=$this->NumOutlets, TemplateID=$templateid";
		
		if(!$dbh->exec($sql)){
			// A combination of this Mfg + Model already exists most likely
			return false;
		}
		
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->TemplateID;
	}
	
	function UpdateTemplate() {
		global $dbh;

		$this->MakeSafe();

		$oldtemplate=new CDUTemplate();
		$oldtemplate->TemplateID=$this->TemplateID;
		$oldtemplate->GetTemplate();
		
		$sql="UPDATE fac_CDUTemplate SET ManufacturerID=$this->ManufacturerID, 
			Model=\"$this->Model\", Managed=$this->Managed, ATS=$this->ATS,
			VersionOID=\"$this->VersionOID\", 
			Multiplier=\"$this->Multiplier\", OID1=\"$this->OID1\", OID2=\"$this->OID2\", 
			OID3=\"$this->OID3\", ATSStatusOID=\"$this->ATSStatusOID\", ATSDesiredResult=\"$this->ATSDesiredResult\",
			ProcessingProfile=\"$this->ProcessingProfile\", 
			Voltage=$this->Voltage, Amperage=$this->Amperage, NumOutlets=$this->NumOutlets
			WHERE TemplateID=$this->TemplateID;";
		
		if(!$dbh->query($sql)){
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this,$oldtemplate):'';
			return true;
		}
	}
	
	function DeleteTemplate() {
		global $dbh;

		$this->MakeSafe();
		
		// First step is to clear any power strips referencing this template
		$sql="UPDATE fac_PowerDistribution SET CDUTemplateID=0 WHERE TemplateID=$this->TemplateID;";
		$dbh->query($sql);
		
		$sql="DELETE FROM fac_CDUTemplate WHERE TemplateID=$this->TemplateID;";
		$dbh->exec($sql);
		
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}
}

class PowerPorts {
	var $DeviceID;
	var $PortNumber;
	var $Label;
	var $ConnectedDeviceID;
	var $ConnectedPort;
	var $Notes;

	function MakeSafe() {
		$this->DeviceID=intval($this->DeviceID);
		$this->PortNumber=intval($this->PortNumber);
		$this->Label=sanitize($this->Label);
		$this->ConnectedDeviceID=intval($this->ConnectedDeviceID);
		$this->ConnectedPort=intval($this->ConnectedPort);
		$this->Notes=sanitize($this->Notes);

		if($this->ConnectedDeviceID==0 || $this->ConnectedPort==0){
			$this->ConnectedDeviceID="NULL";
			$this->ConnectedPort="NULL";
		}
	}

	function MakeDisplay(){
		$this->Label=stripslashes(trim($this->Label));
		$this->Notes=stripslashes(trim($this->Notes));
	}

	static function RowToObject($dbRow){
		$pp=new PowerPorts();
		$pp->DeviceID=$dbRow['DeviceID'];
		$pp->PortNumber=$dbRow['PortNumber'];
		$pp->Label=$dbRow['Label'];
		$pp->ConnectedDeviceID=$dbRow['ConnectedDeviceID'];
		$pp->ConnectedPort=$dbRow['ConnectedPort'];
		$pp->Notes=$dbRow['Notes'];

		$pp->MakeDisplay();

		return $pp;
	}

	function getPort(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerPorts WHERE DeviceID=$this->DeviceID AND PortNumber=$this->PortNumber;";

		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}else{
			foreach(PowerPorts::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}
	}

	function getPorts(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerPorts WHERE DeviceID=$this->DeviceID ORDER BY PortNumber ASC;";

		$ports=array();
		foreach($dbh->query($sql) as $row){
			$ports[$row['PortNumber']]=PowerPorts::RowToObject($row);
		}	
		return $ports;
	}

	function createPort($ignore_errors=false) {
		global $dbh;
		
		$this->MakeSafe();

		$sql="INSERT INTO fac_PowerPorts SET DeviceID=$this->DeviceID, 
			PortNumber=$this->PortNumber, Label=\"$this->Label\", 
			ConnectedDeviceID=$this->ConnectedDeviceID, ConnectedPort=$this->ConnectedPort, 
			Notes=\"$this->Notes\";";
			
		if(!$dbh->query($sql) && !$ignore_errors){
			$info=$dbh->errorInfo();

			error_log("createPort::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	static function createPorts($DeviceID,$update_existing=false){
		// If $update_existing is true then we'll try to create all the ports and as a by product
		// create any new ports.  The setting here will ensure we don't log any errors from the
		// ports that already exist.
		$dev=New Device;
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){return false;}

		// Check the user's permissions to modify this device
		if($dev->Rights!='Write'){return false;}
		$portList=array();

		//Search template ports
		$tports=array();
		if($dev->TemplateID>0){
			$tport=new TemplatePowerPorts();
			$tport->TemplateID=$dev->TemplateID;
			$tports=$tport->getPorts();
		}

		for($i=1; $i<=$dev->PowerSupplyCount; $i++){
			$portList[$i]=new PowerPorts();
			$portList[$i]->DeviceID=$dev->DeviceID;
			$portList[$i]->PortNumber=$i;
			if(isset($tports[$i])){
				// Get any attributes from the template
				foreach($tports[$i] as $key => $value){
					if(array_key_exists($key,$portList[$i])){
						$portList[$i]->$key=$value;
					}
				}
				// Temp fix for issue #632 until the models can be brought into alignment
				$portList[$i]->Notes=$tports[$i]->PortNotes;
			}
			$portList[$i]->Label=($portList[$i]->Label=="")?__("Power Connection")." $i":$portList[$i]->Label;
			$portList[$i]->createPort($update_existing);
		}
		return $portList;
	}

	function updateLabel(){
		global $dbh;

		$this->MakeSafe();

		$oldport=new PowerPorts(); // originating port prior to modification
		$oldport->DeviceID=$this->DeviceID;
		$oldport->PortNumber=$this->PortNumber;
		$oldport->getPort();

		$sql="UPDATE fac_PowerPorts SET Label=\"$this->Label\" WHERE 
			DeviceID=$this->DeviceID AND PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this,$oldport):'';
			return true;
		}
	}

	function removePort(){
		/* Remove a single power port from a device */
		global $dbh;

		if(!$this->getport()){
			return false;
		}

		// Disconnect anything that might be connected in the db
		$this->removeConnection();

		$sql="DELETE FROM fac_PowerPorts WHERE DeviceID=$this->DeviceID AND 
			PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
	}

	static function removePorts($DeviceID){
		/* Remove all ports from a device prior to delete, etc */
		global $dbh;

		$dev=new Device();
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){return false;}

		// Check the user's permissions to modify this device
		if($dev->Rights!='Write'){return false;}

		PowerPorts::removeConnections($DeviceID);

		$sql="DELETE FROM fac_PowerPorts WHERE DeviceID=$dev->DeviceID;";

		$dbh->exec($sql);

		return true;
	}

	function updatePort() {
		global $dbh;

		$oldport=new PowerPorts(); // originating port prior to modification
		$oldport->DeviceID=$this->DeviceID;
		$oldport->PortNumber=$this->PortNumber;
		$oldport->getPort();
		$tmpport=new PowerPorts(); // connecting to here
		$tmpport->DeviceID=$this->ConnectedDeviceID;
		$tmpport->PortNumber=$this->ConnectedPort;
		$tmpport->getPort();
		$oldtmpport=new PowerPorts(); // used for logging
		$oldtmpport->DeviceID=$oldport->ConnectedDeviceID;
		$oldtmpport->PortNumber=$oldport->ConnectedPort;
		$oldtmpport->getPort();

		//check rights before we go any further
		$dev=new Device();
		$dev->DeviceID=$this->DeviceID;
		$dev->GetDevice();
		$replacingdev=new Device();
		$replacingdev->DeviceID=$oldport->ConnectedDeviceID;
		$replacingdev->GetDevice();
		$connecteddev=new Device();
		$connecteddev->DeviceID=$this->ConnectedDeviceID;
		$connecteddev->GetDevice();

		$rights=false;
		$rights=($dev->Rights=="Write")?true:$rights;
		$rights=($replacingdev->Rights=="Write")?true:$rights;
		$rights=($connecteddev->Rights=="Write")?true:$rights;

		if(!$rights){
			return false;
		}
	
		$this->MakeSafe();

		// Quick sanity check so we aren't depending on the user
		$this->Label=($this->Label=="")?$this->PortNumber:$this->Label;

		// clear previous connection
		$oldport->removeConnection();
		$tmpport->removeConnection();

		if($this->ConnectedDeviceID==0 || $this->PortNumber==0 || $this->ConnectedPort==0){
			// when any of the above equal 0 this is a delete request
			// skip making any new connections but go ahead and update the device
			// reload tmpport with data from the other device
			$tmpport->DeviceID=$oldport->ConnectedDeviceID;
			$tmpport->PortNumber=$oldport->ConnectedPort;
			$tmpport->getPort();
		}else{
			// make new connection
			$tmpport->ConnectedDeviceID=$this->DeviceID;
			$tmpport->ConnectedPort=$this->PortNumber;
			$tmpport->Notes=$this->Notes;
			PowerPorts::makeConnection($tmpport,$this);
		}

		// update port
		$sql="UPDATE fac_PowerPorts SET ConnectedDeviceID=$this->ConnectedDeviceID,
			Label=\"$this->Label\", ConnectedPort=$this->ConnectedPort, 
			Notes=\"$this->Notes\" WHERE DeviceID=$this->DeviceID AND 
			PortNumber=$this->PortNumber;";
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("updatePort::PDO Error: {$info[2]} SQL=$sql");
			
			return false;
		}

		// two logs, because we probably modified two devices
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldport):'';
		(class_exists('LogActions'))?LogActions::LogThis($tmpport,$oldtmpport):'';
		return true;
	}

	static function makeConnection($port1,$port2){
		global $dbh;

		$port1->MakeSafe();
		$port2->MakeSafe();

		$sql="UPDATE fac_PowerPorts SET ConnectedDeviceID=$port2->DeviceID, 
			ConnectedPort=$port2->PortNumber, Notes=\"$port2->Notes\" WHERE 
			DeviceID=$port1->DeviceID AND PortNumber=$port1->PortNumber;
			UPDATE fac_PowerPorts SET ConnectedDeviceID=$port1->DeviceID, 
			ConnectedPort=$port1->PortNumber, Notes=\"$port1->Notes\" WHERE 
			DeviceID=$port2->DeviceID AND PortNumber=$port2->PortNumber;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("updatePort::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		return true;
	}

	function removeConnection(){
		global $dbh;

		$this->getPort();

		$sql="UPDATE fac_PowerPorts SET ConnectedDeviceID=NULL, ConnectedPort=NULL WHERE
			(DeviceID=$this->DeviceID AND PortNumber=$this->PortNumber) OR 
			(ConnectedDeviceID=$this->DeviceID AND ConnectedPort=$this->PortNumber);";

		/* not sure the best way to catch these errors this should modify 2 lines
		   per run. */
		try{
			$dbh->exec($sql);
		}catch(PDOException $e){
			echo $e->getMessage();
			die();
		}

		return true;
	}

	static function removeConnections($DeviceID){
		/* Drop all power connections on a device */
		global $dbh;

		$dev=new Device();
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){return false;}

		// Check the user's permissions to modify this device
		if($dev->Rights!='Write'){return false;}

		$sql="UPDATE fac_PowerPorts SET ConnectedDeviceID=NULL, ConnectedPort=NULL WHERE DeviceID=$dev->DeviceID OR ConnectedDeviceID=$dev->DeviceID;";

		$dbh->exec($sql);

		return true;
	}

	static function getPortList($DeviceID){
		global $dbh;
		
		$dev=new Device();
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){
			return false;	// This device doesn't exist
		}
		
		$sql="SELECT * FROM fac_PowerPorts WHERE DeviceID=$dev->DeviceID;";
		
		$portList=array();
		foreach($dbh->query($sql) as $row){
			$portList[$row['PortNumber']]=PowerPorts::RowToObject($row);
		}
		
		if( sizeof($portList)==0 && $dev->DeviceType!="Physical Infrastructure" ){
			// somehow this device doesn't have ports so make them now
			$portList=PowerPorts::createPorts($dev->DeviceID);
		}
		
		return $portList;
	}
}

class PowerConnection {
	/* PowerConnection:		A mapping of power strip (PDU) ports to the devices connected to them.
							Devices are limited to those within the same cabinet as the power strip,
							as connecting power across cabinets is not just a BAD PRACTICE, it's
							outright idiotic, except in temporary situations.
	*/
	
	var $PDUID;
	var $PDUPosition;
	var $DeviceID;
	var $DeviceConnNumber;

	private function MakeSafe(){
		$this->PDUID=intval($this->PDUID);
		$this->PDUPosition=sanitize($this->PDUPosition);
		$this->DeviceID=intval($this->DeviceID);
		$this->DeviceConnNumber=intval($this->DeviceConnNumber);
	}

	private function MakeDisplay(){
		$this->PDUPosition=stripslashes($this->PDUPosition);
	}

	static function RowToObject($row){
		$conn=new PowerConnection;
		$conn->PDUID=$row["PDUID"];
		$conn->PDUPosition=$row["PDUPosition"];
		$conn->DeviceID=$row["DeviceID"];
		$conn->DeviceConnNumber=$row["DeviceConnNumber"];
		$conn->MakeDisplay();

		return $conn;
	}

	function CanWrite(){
		global $person;
		// check rights
		$write=false;

			// check for an existing device
		$tmpconn=new PowerConnection();
		foreach($this as $prop => $value){
			$tmpconn->$prop=$value;
		}
		$tmpconn->GetPDUConnectionByPosition();
		$dev=new Device();
		$dev->DeviceID=$tmpconn->DeviceID;
		$dev->GetDevice();
		$write=($dev->Rights=="Write")?true:$write;

			// check for new device
		$dev->DeviceID=$this->DeviceID;
		$dev->GetDevice();
		$write=($dev->Rights=="Write")?true:$write;

			// check for rack ownership
		$pdu=new PowerDistribution();
		$pdu->PDUID=$this->PDUID;
		$pdu->GetPDU();
		$cab=new Cabinet();
		$cab->CabinetID=$pdu->CabinetID;
		$cab->GetCabinet();
		$write=($person->canWrite($cab->AssignedTo))?true:$write;

		return $write;
	}

	function CreateConnection(){
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INTO fac_PowerConnection SET DeviceID=$this->DeviceID, 
			DeviceConnNumber=$this->DeviceConnNumber, PDUID=$this->PDUID, 
			PDUPosition=\"$this->PDUPosition\" ON DUPLICATE KEY UPDATE DeviceID=$this->DeviceID,
			DeviceConnNumber=$this->DeviceConnNumber;";

		if($this->CanWrite()){
			if($dbh->query($sql)){
				(class_exists('LogActions'))?LogActions::LogThis($this):'';
				return true;
			}
		}
		return false;
	}
	
	function DeleteConnections(){
		/*
		 * This function is called when deleting a device, and will remove 
		 * ALL connections for the specified device.
		 */
		global $dbh;

		$this->MakeSafe();
		$sql="DELETE FROM fac_PowerConnection WHERE DeviceID=$this->DeviceID;";

		if($this->CanWrite()){
			if($dbh->exec($sql)){
				(class_exists('LogActions'))?LogActions::LogThis($this):'';
				return true;
			}
		}
		return false;
	}
	
	function RemoveConnection(){
		/*
		 * This function is called when removing a single connection, 
		 * specified by the unique combination of PDU ID and PDU Position.
		 */
		global $dbh;

		$this->MakeSafe();
		$sql="DELETE FROM fac_PowerConnection WHERE PDUID=$this->PDUID AND 
			PDUPosition=\"$this->PDUPosition\";";

		if($this->CanWrite()){
			if($dbh->exec($sql)){
				(class_exists('LogActions'))?LogActions::LogThis($this):'';
				return true;
			}
		}
		return false;
	}

	function GetPDUConnectionByPosition(){
		global $dbh;

		$this->MakeSafe();
		$sql="SELECT * FROM fac_PowerConnection WHERE PDUID=$this->PDUID AND 
			PDUPosition=\"$this->PDUPosition\";";
    
		if($row=$dbh->query($sql)->fetch()){
			foreach(PowerConnection::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}
		return false;
	}
  
	function GetConnectionsByPDU(){
		global $dbh;

		$this->MakeSafe();
		$sql="SELECT * FROM fac_PowerConnection WHERE PDUID=$this->PDUID ORDER BY 
			PDUPosition;";

		$connList=array();
		foreach($dbh->query($sql) as $row){
			$connList[$row["PDUPosition"]]=PowerConnection::RowToObject($row);
		}
		return $connList;
	}
  
	function GetConnectionsByDevice(){
		global $dbh;

		$this->MakeSafe();
    	$sql="SELECT * FROM fac_PowerConnection WHERE DeviceID=$this->DeviceID ORDER BY DeviceConnnumber ASC, PDUID, PDUPosition";

		$connList=array();
		foreach($dbh->query($sql) as $row){
			$connList[]=PowerConnection::RowToObject($row);
		}
		return $connList;
	}    
}

class PowerDistribution {
	/* PowerDistribution:	A power strip, essentially.  Intelligent power strips from APC, Geist Manufacturing,
							and Server Technologies are supported for polling of amperage.  Future implementation
							will include temperature/humidity probe data for inclusion on the data center mapping.
							Non-monitored power strips are also supported, but simply won't have data regarding
							current load.
							
							Power strips are mapped to the panel / circuit, and panels are mapped to the power source,
							which is then wrapped up at the data center level.
	*/
	
	var $PDUID;
	var $Label;
	var $CabinetID;
	var $TemplateID;
	var $IPAddress;
	var $SNMPCommunity;
	var $FirmwareVersion;
  	var $PanelID;
	var $BreakerSize;
	var $PanelPole;
	var $InputAmperage;
	var $FailSafe;
	var $PanelID2;
	var $PanelPole2;

	function MakeSafe(){
		$this->PDUID=intval($this->PDUID);
		$this->Label=sanitize($this->Label);
		$this->CabinetID=intval($this->CabinetID);
		$this->TemplateID=intval($this->TemplateID);
		$this->IPAddress=sanitize($this->IPAddress);
		$this->SNMPCommunity=sanitize($this->SNMPCommunity);
		$this->FirmwareVersion=sanitize($this->FirmwareVersion);
		$this->PanelID=intval($this->PanelID);
		$this->BreakerSize=intval($this->BreakerSize);
		$this->PanelPole=intval($this->PanelPole);
		$this->InputAmperage=intval($this->InputAmperage);
		$this->FailSafe=intval($this->FailSafe);
		$this->PanelID2=intval($this->PanelID2);
		$this->PanelPole2=intval($this->PanelPole2);
	}

	function MakeDisplay(){
		$this->Label=stripslashes($this->Label);
		$this->IPAddress=stripslashes($this->IPAddress);
		$this->SNMPCommunity=stripslashes($this->SNMPCommunity);
		$this->FirmwareVersion=stripslashes($this->FirmwareVersion);
	}

	static function RowToObject($row){
		$PDU=new PowerDistribution();
		$PDU->PDUID=$row["PDUID"];
		$PDU->Label=$row["Label"];
		$PDU->CabinetID=$row["CabinetID"];
		$PDU->TemplateID=$row["TemplateID"];
		$PDU->IPAddress=$row["IPAddress"];
		$PDU->SNMPCommunity=$row["SNMPCommunity"];
		$PDU->FirmwareVersion=$row["FirmwareVersion"];
		$PDU->PanelID=$row["PanelID"];
		$PDU->BreakerSize=$row["BreakerSize"];
		$PDU->PanelPole=$row["PanelPole"];
		$PDU->InputAmperage=$row["InputAmperage"];
		$PDU->FailSafe=$row["FailSafe"];
		$PDU->PanelID2=$row["PanelID2"];
		$PDU->PanelPole2=$row["PanelPole2"];

		$PDU->MakeDisplay();

		return $PDU;
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
	static private function BasicTests($DeviceID){
		global $config;

		// First check if the SNMP library is present
		if(!class_exists('OSS_SNMP\SNMP')){
			return false;
		}

		$dev=New Device();
		$dev->DeviceID=$DeviceID;

		// Make sure this is a real device and has an IP set
		if(!$dev->GetDevice()){return false;}
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
	static private function OSS_SNMP_Lookup($dev,$snmplookup,$oid=null){
		// This is find out the name of the function that called this to make the error logging more descriptive
		$caller=debug_backtrace();
		$caller=$caller[1]['function'];

		$snmpHost=new OSS_SNMP\SNMP($dev->PrimaryIP,$dev->SNMPCommunity,$dev->SNMPVersion,$dev->v3SecurityLevel,$dev->v3AuthProtocol,$dev->v3AuthPassphrase,$dev->v3PrivProtocol,$dev->v3PrivPassphrase);
		$snmpresult=false;
		try {
			$snmpresult=(is_null($oid))?$snmpHost->useSystem()->$snmplookup(true):$snmpHost->get($oid);
		}catch (Exception $e){
			$dev->IncrementFailures();
			error_log("PowerDistribution::$caller($dev->DeviceID) ".$e->getMessage());
		}

		$dev->ResetFailures();
		return $snmpresult;
	}

	function CreatePDU($pduid=null){
		global $dbh;

		$this->MakeSafe();

		$sqladdon=(!is_null($pduid))?", PDUID=".intval($pduid):"";

		$sql="INSERT INTO fac_PowerDistribution SET Label=\"$this->Label\", 
			CabinetID=$this->CabinetID, TemplateID=$this->TemplateID, 
			IPAddress=\"$this->IPAddress\", SNMPCommunity=\"$this->SNMPCommunity\", 
			PanelID=$this->PanelID, BreakerSize=$this->BreakerSize, 
			PanelPole=$this->PanelPole, InputAmperage=$this->InputAmperage, 
			FailSafe=$this->FailSafe, PanelID2=$this->PanelID2, 
			PanelPole2=$this->PanelPole2$sqladdon;";

		if($this->exec($sql)){
			$this->PDUID=$dbh->lastInsertId();

			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->PDUID;
		}else{
			$info=$dbh->errorInfo();

			error_log("CreatePDU::PDO Error: {$info[2]} SQL=$sql");

			return false;
		}
	}

	function UpdatePDU(){
		$this->MakeSafe();

		$oldpdu=new PowerDistribution();
		$oldpdu->PDUID=$this->PDUID;
		$oldpdu->GetPDU();

		$sql="UPDATE fac_PowerDistribution SET Label=\"$this->Label\", 
			CabinetID=$this->CabinetID, TemplateID=$this->TemplateID, 
			IPAddress=\"$this->IPAddress\", SNMPCommunity=\"$this->SNMPCommunity\", 
			PanelID=$this->PanelID, BreakerSize=$this->BreakerSize, 
			PanelPole=$this->PanelPole, InputAmperage=$this->InputAmperage, 
			FailSafe=$this->FailSafe, PanelID2=$this->PanelID2, PanelPole2=$this->PanelPole2
			WHERE PDUID=$this->PDUID;";

		(class_exists('LogActions'))?LogActions::LogThis($this,$oldpdu):'';
		return $this->query($sql);
	}

	function GetSourceForPDU(){
		$this->GetPDU();

		$panel=new PowerPanel();

		$panel->PanelID=$this->PanelID;
		$r = $panel->getPowerSource();

		return $r->PanelID;
	}
	
	function GetPDU(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerDistribution WHERE PDUID=$this->PDUID;";

		if($PDURow=$this->query($sql)->fetch()){
			foreach(PowerDistribution::RowToObject($PDURow) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='PDUID'){
					$this->$prop=null;
				}
			}
		}

		return true;
	}

	function GetPDUbyPanel(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerDistribution WHERE PanelID=$this->PanelID
			 OR PanelID2=$this->PanelID ORDER BY PanelPole ASC, CabinetID, Label";

		$PDUList=array();
		foreach($this->query($sql) as $PDURow){
			$PDUList[]=PowerDistribution::RowToObject($PDURow);
		}

		return $PDUList;
	}
	
	function GetPDUbyCabinet(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerDistribution WHERE CabinetID=$this->CabinetID;";

		$PDUList=array();
		foreach($this->query($sql) as $PDURow){
			$PDUList[$PDURow["PDUID"]]=PowerDistribution::RowToObject($PDURow);
		}

		return $PDUList;
	}
	
	function SearchByPDUName(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerDistribution WHERE Label LIKE \"%$this->Label%\";";

		$PDUList=array();
		foreach($this->query($sql) as $PDURow){
			$PDUList[$PDURow["PDUID"]]=PowerDistribution::RowToObject($PDURow);
		}

		return $PDUList;
	}

	/* These fac_PDUStats functions are UGLY.  When we build out RESTful API, they should be moved to a separate class and return objects */
	function GetLastReading(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PDUStats WHERE PDUID=$this->PDUID;";
		$stats=new stdClass();
		$stats->Wattage=0;
		$stats->LastRead=date('Y-m-d G:i:s',0);
		foreach($this->query($sql) as $row){
			foreach($row as $prop => $value){
				if(!is_int($prop)){
					$stats->$prop=$value;
				}
			}
		}

		return (is_object($stats))?$stats:false;
	}

	function GetWattageByDC($dc=null){
		// What was the idea behind this null function?
		if($dc==null){
			$sql="SELECT COUNT(Wattage) FROM fac_PDUStats;";
		}else{
			$sql="SELECT SUM(Wattage) AS Wattage FROM fac_PDUStats WHERE PDUID IN 
			(SELECT PDUID FROM fac_PowerDistribution WHERE CabinetID IN 
			(SELECT CabinetID FROM fac_Cabinet WHERE DataCenterID=".intval($dc)."))";
		}		
		
		return $this->query($sql)->fetchColumn();
	}
	
	function GetWattageByCabinet($CabinetID){
		$CabinetID=intval($CabinetID);
		if($CabinetID <1){
			return 0;
		}
		
		$sql="SELECT SUM(Wattage) AS Wattage FROM fac_PDUStats WHERE PDUID 
			IN (SELECT PDUID FROM fac_PowerDistribution WHERE CabinetID=$CabinetID);";

		if(!$wattage=$this->query($sql)->fetchColumn()){
			$wattage=0;
		}
		
		return $wattage;
	}

	function LogManualWattage($Wattage){
		$this->MakeSafe();

		$oldpdu=new PowerDistribution();
		$oldpdu->PDUID=$this->PDUID;
		$oldpdu->GetPDU();

		$oldreading=$oldpdu->GetLastReading();
		$oldpdu->Wattage=$oldreading->Wattage;

		$Wattage=intval($Wattage);
		$this->Wattage=$Wattage;
	
		$sql="INSERT INTO fac_PDUStats SET Wattage=$Wattage, PDUID=$this->PDUID, 
			LastRead=NOW() ON DUPLICATE KEY UPDATE Wattage=$Wattage, LastRead=NOW();";
		
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldpdu):'';
		return ($this->query($sql))?$this->GetLastReading():false;
	}
	
	function UpdateStats(){
		global $config;
		global $dbh;
		
		$sql="SELECT a.PDUID, d.SNMPVersion, b.Multiplier, b.OID1, 
			b.OID2, b.OID3, b.ProcessingProfile, b.Voltage, c.SNMPFailureCount FROM fac_PowerDistribution a, 
			fac_CDUTemplate b, fac_Device c, fac_DeviceTemplate d WHERE a.PDUID=c.DeviceID and a.TemplateID=b.TemplateID 
			AND a.TemplateID=d.TemplateID AND b.Managed=true AND c.PrimaryIP>'' and c.SNMPFailureCount<3";
		
		// The result set should have no PDU's with blank IP Addresses or SNMP Community, so we can forge ahead with processing them all
		foreach($this->query($sql) as $row){
			if(!$dev=PowerDistribution::BasicTests($row['PDUID'])){
				// if we fail the basic test on a single device we don't want to skip all the rest so continue instead of return false;
				continue;
			}

			// Just send back zero if we don't get a result.
			$pollValue1=$pollValue2=$pollValue3=0;

			$pollValue1=floatval(self::OSS_SNMP_Lookup($dev,null,$row["OID1"]));
			// We won't use OID2 or 3 without the other so make sure both are set or just ignore them
			if($row["OID2"]!="" && $row["OID3"]!=""){
				$pollValue2=floatval(self::OSS_SNMP_Lookup($dev,null,$row["OID2"]));
				$pollValue3=floatval(self::OSS_SNMP_Lookup($dev,null,$row["OID3"]));
				// Negativity test, it is required for APC 3ph modular PDU with IEC309-5W wires
				if ($pollValue2<0) $pollValue2=0;
				if ($pollValue3<0) $pollValue3=0;
			}
			
			// Have to reset this every time, otherwise the exec() will append
			unset($statsOutput);
			$amps=0;
			$watts=0;
			
			if($pollValue1){
				// The multiplier should be an int but no telling what voodoo the db might cause
				$multiplier=floatval($row["Multiplier"]);
				$voltage=intval($row["Voltage"]);

				switch ( $row["ProcessingProfile"] ) {
					case "SingleOIDAmperes":
						$amps=$pollValue1/$multiplier;
						$watts=$amps * $voltage;
						break;
					case "Combine3OIDAmperes":
						$amps=($pollValue1 + $pollValue2 + $pollValue3) / $multiplier;
						$watts=$amps * $voltage;
						break;
					case "Convert3PhAmperes":
						// OO does this next formula need another set of () to be clear?
						$amps=($pollValue1 + $pollValue2 + $pollValue3) / $multiplier / 3;
						$watts=$amps * 1.732 * $voltage;
						break;
					case "Combine3OIDWatts":
						$watts=($pollValue1 + $pollValue2 + $pollValue3) / $multiplier;
						break;
					default:
						$watts=$pollValue1 / $multiplier;
						break;
				}
			}
			
			$sql="INSERT INTO fac_PDUStats SET PDUID={$row["PDUID"]}, Wattage=$watts, 
				LastRead=now() ON DUPLICATE KEY UPDATE Wattage=$watts, LastRead=now();";
			if(!$dbh->query($sql)){
				$info=$dbh->errorInfo();
				error_log("PowerDistribution::UpdateStats::PDO Error: {$info[2]} SQL=$sql");
			}
			
			$this->PDUID=$row["PDUID"];
			if($ver=$this->GetSmartCDUVersion()){
				$sql="UPDATE fac_PowerDistribution SET FirmwareVersion=\"$ver\" WHERE PDUID=$this->PDUID;";
				if(!$dbh->query($sql)){
					$info=$dbh->errorInfo();
					error_log("PowerDistribution::UpdateStats::PDO Error: {$info[2]} SQL=$sql");
				}
			}
		}
	}
	
	function getATSStatus() {
		if(!$dev=PowerDistribution::BasicTests($this->PDUID)){
			return false;
		}

		// Make sure we have a real power device and not just a device
		if(!$this->GetPDU()){
			return false;
		}

		$tmpl=new CDUTemplate();
		$tmpl->TemplateID=$dev->TemplateID;
		if(!$tmpl->GetTemplate()){
			return false;
		}

		return self::OSS_SNMP_Lookup($dev,null,$tmpl->ATSStatusOID);
	}


	function GetSmartCDUUptime() {
		if(!$dev=PowerDistribution::BasicTests($this->PDUID)){
			return false;
		}

		// If this gets a value returned it will be in ticks
		$test=self::OSS_SNMP_Lookup($dev,"uptime");

		return ($test)?ticksToTime($test):$test;
	}
	
	function GetSmartCDUVersion(){
		if(!$dev=PowerDistribution::BasicTests($this->PDUID)){
			return false;
		}
		
		if(!$this->GetPDU()){
			return false;
		}
		
		$template=new CDUTemplate();
		$template->TemplateID=$this->TemplateID;
		if(!$template->GetTemplate()){
			return false;
		}

		return self::OSS_SNMP_Lookup($dev,null,"$template->VersionOID");
	}

	function GetAllBreakerPoles() {
		$this->GetPDU();

		$panel=new PowerPanel();
		$panel->PanelID=$this->PanelID;
		if($panel->getPanel()) {
			$ret = "$this->PanelPole";
			for($i=1;$i<$this->BreakerSize;$i++) {
				$adder = $i;
				if($panel->NumberScheme=="Odd/Even") {
						$adder = $i*2;
				}
				$next = $this->PanelPole+$adder;
				$ret = $ret . "-$next";
			}
			return $ret;
		}else{
			return "Error, source power panel not valid";
		}
	}

	function DeletePDU(){
		global $person;
		$this->MakeSafe();

		// Do not attempt anything else if the lookup fails
		if(!$this->GetPDU()){return false;}

		// Check rights
		$cab=new Cabinet();
		$cab->CabinetID=$this->CabinetID;
		$cab->GetCabinet();
		if(!$person->canWrite($cab->AssignedTo)){return false;}

		// First, remove any connections to the PDU
		$tmpConn=new PowerConnection();
		$tmpConn->PDUID=$this->PDUID;
		$connList=$tmpConn->GetConnectionsByPDU();
		
		foreach($connList as $delConn){
			$delConn->RemoveConnection();
		}

		// Clear out any records from PDUStats, possible S.U.T. involving changing
		// a devicetype but leaving behind a phantom reading for a non-power device
		$sql="DELETE FROM fac_PDUStats WHERE PDUID=$this->PDUID;";
		$this->exec($sql);

		$sql="DELETE FROM fac_PowerDistribution WHERE PDUID=$this->PDUID;";
		if(!$this->exec($sql)){
			// Something went south and this didn't delete.
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
	}

	function GetWattage() {
                $ret->Wattage1 = 0;
                $ret->Wattage2 = 0;
                $ret->Wattage3 = 0;
                $ret->LastRead = date("Y-m-d H:i:s");

                $measureFound = false;

                $mpList = new MeasurePoint();
                $mpList->EquipmentType = "Device";
                $mpList->EquipmentID = $this->PDUID;
                $mpList = $mpList->GetMPByEquipment();

                foreach($mpList as $mp) {
                        if($mp->Type == "elec") {
				$lastMeasure = new ElectricalMeasure();
				$lastMeasure->MPID = $mp->MPID;
				$lastMeasure = $lastMeasure->GetLastMeasure();

				if(!is_null($lastMeasure->Date)) {
					$measureFound = true;
					$ret->Wattage1 += $lastMeasure->Wattage1;
					$ret->Wattage2 += $lastMeasure->Wattage2;
					$ret->Wattage3 += $lastMeasure->Wattage3;
					if(strtotime($lastMeasure->Date) < strtotime($ret->LastRead))
						$ret->LastRead = $lastMeasure->Date;
				}
                        }
                }
                return ($measureFound)?$ret:false;
        }

}

class PowerPanel {
	/* PowerPanel:	PowerPanel(s) are the parents of PowerDistribution (power strips) and the children
					each other.  Panels are arranged as either Odd/Even (odd numbers on the left,
					even on the right) or Sequential (1 to N in a single column) numbering for the
					purpose of building out a panel schedule.  If a PowerPanel has no ParentPanelID defined
					then it is considered to be the PowerSource.  In other words, it's a reverse linked list.
	*/
	
	var $PanelID;
	var $PanelLabel;
	var $NumberOfPoles;
	var $MainBreakerSize;
	var $PanelVoltage;
	var $NumberScheme;
	var $ParentPanelID;
	var $ParentBreakerName;	// For switchgear, this usually won't be numbered, so we're accepting text
	var $PanelIPAddress;
	var $TemplateID;
	
	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}
	
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}

	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function lastInsertId() {
		global $dbh;
		return $dbh->lastInsertId();
	}
	
	function errorInfo() {
		global $dbh;
		return $dbh->errorInfo();
	}
	
	function MakeSafe() {
		$this->PanelID=intval($this->PanelID);
		$this->PanelLabel=sanitize($this->PanelLabel);
		$this->NumberOfPoles=intval($this->NumberOfPoles);
		$this->MainBreakerSize=intval($this->MainBreakerSize);
		$this->PanelVoltage=intval($this->PanelVoltage);
		$this->NumberScheme=($this->NumberScheme=='Odd/Even')?'Odd/Even':'Sequential';
		$this->ParentPanelID=intval($this->ParentPanelID);
		$this->ParentBreakerName=sanitize($this->ParentBreakerName);
		$this->PanelIPAddress=sanitize($this->PanelIPAddress);
		$this->TemplateID=intval($this->TemplateID);
	}

	function MakeDisplay(){
		$this->PanelLabel=stripslashes($this->PanelLabel);
		$this->ParentBreakerName=stripslashes($this->ParentBreakerName);
		$this->PanelIPAddress=stripslashes($this->PanelIPAddress);
	}

	static function RowToObject($row){
		$panel=new PowerPanel();
		$panel->PanelID=$row["PanelID"];
		$panel->PanelLabel=$row["PanelLabel"];
		$panel->NumberOfPoles=$row["NumberOfPoles"];
		$panel->MainBreakerSize=$row["MainBreakerSize"];
		$panel->PanelVoltage=$row["PanelVoltage"];
		$panel->NumberScheme=$row["NumberScheme"];
		$panel->ParentPanelID=$row["ParentPanelID"];
		$panel->ParentBreakerName=$row["ParentBreakerName"];
		$panel->TemplateID=$row["TemplateID"];
		$panel->PanelIPAddress=$row["PanelIPAddress"];

		$panel->MakeDisplay();

		return $panel;
	}


	function getPanelLoad(){
		$this->MakeSafe();

		$sql="SELECT SUM(Wattage) FROM fac_PDUStats WHERE PDUID IN (SELECT PDUID FROM 
			fac_PowerDistribution WHERE PanelID=$this->PanelID);";
		if($watts=$this->query($sql)->fetchColumn()){
			return $watts;
		}else{
			return 0;
		}
	}

	function getPanelList() {
		// Make this a clean list because it will be confusing if it's filtered
		$clean=new PowerPanel();
		return $clean->Search();
	}
	
	function getPowerSource() {
		$sql = "select * from fac_PowerPanel where PanelID=:PanelID";
		
		$st = $this->prepare( $sql );
		$st->setFetchMode( PDO::FETCH_CLASS, "PowerPanel" );
		
		$currParent = $this->ParentPanelID;
		while ( $currParent != 0 ) {
			$st->execute( array( ":PanelID"=>$currParent ) );
			$row = $st->fetch();
			$currParent = $row->ParentPanelID;
		}
		
		if ( ! @is_object( $row ) ) {
			// Someone called this on a PowerSource
			$row = new PowerPanel();
			foreach ( $this as $prop=>$val ) {
				$row->$prop = $val;
			}
		}
		
		return $row;
	}
	
	function getPanelListBySource( $onlyFirstGen = false ) {
		/* If you supply $this->ParentPanelID then you will get a list of all sources */
		$sql = "select * from fac_PowerPanel where ParentPanelID=:ParentPanelID order by PanelLabel ASC";
		
		$st = $this->prepare( $sql );
		$st->setFetchMode( PDO::FETCH_CLASS, "PowerPanel" );
		$st->execute( array( ":ParentPanelID"=>$this->ParentPanelID ) );
		
		$pList = array();
		while ( $row = $st->fetch() ) {
			$pList[] = $row;
		}
		
		if ( ! $onlyFirstGen ) {
			foreach ( $pList as $currPan ) {
				$st->execute( array( ":ParentPanelID"=>$currPan->PanelID ) );
				while ( $row = $st->fetch() ) {
					$pList[] = $row;
				}
			}
		}
		
		return $pList;
	}
	
	function getSources() {
		$clean=new PowerPanel();
		$clean->ParentPanelID=0;

		return $clean->Search();
	}

	function getPanelsByDataCenter( $DataCenterID ) {
		$sql = "select * from fac_PowerPanel where PanelID in (select PanelID from fac_PowerDistribution where CabinetID in (select CabinetID from fac_Cabinet where DataCenterID=:DataCenterID)) order by PanelLabel ASC";
		$st = $this->prepare( $sql );
		$st->execute( array( ":DataCenterID"=>$DataCenterID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "PowerPanel" );
		
		$sList = array();
		while ( $row = $st->fetch() ) {
			$sList[] = $row;
		}
		
		return $sList;
	}
	
	function getSourcesByDataCenter( $DataCenterID ) {
		$pList = $this->getPanelsByDataCenter( $DataCenterID );
		
		$tmpList = array();
		
		foreach ( $pList as $p ) { 
			$tmpList[] = $p->getPowerSource()->PanelID;
		}

		$tmpList = array_unique( $tmpList );
		$psList = array();
		foreach ( $tmpList as $pID ) {
			$row = new PowerPanel();
			$row->PanelID = $pID;
			$row->getPanel();
			
			$psList[] = $row;
		}
		
		return $psList;
	}
	
	function getPanel() {
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerPanel WHERE PanelID=$this->PanelID;";
		if($row=$this->query($sql)->fetch()){
			foreach(PowerPanel::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}

	function createPanel() {
		$this->MakeSafe();

		$sql="INSERT INTO fac_PowerPanel SET PanelIPAddress=\"$this->PanelIPAddress\", 
			PanelLabel=\"$this->PanelLabel\", NumberOfPoles=$this->NumberOfPoles, 
			MainBreakerSize=$this->MainBreakerSize, PanelVoltage=$this->PanelVoltage, 
			NumberScheme=\"$this->NumberScheme\", ParentPanelID=$this->ParentPanelID,
			ParentBreakerName=\"$this->ParentBreakerName\", TemplateID=$this->TemplateID;";

		if(!$this->exec($sql)){
			$info=$this->errorInfo();
			error_log("createPanel::PDO Error: {$info[2]} $sql");
			return false;
		}else{
			$this->PanelID=$this->lastInsertId();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->PanelID;
		}
	}
	
	function deletePanel() {
		// First, set any CDUs attached to this panel to simply not have an assigned panel
		$sql="UPDATE fac_PowerDistribution SET PanelID=0 WHERE PanelID=$this->PanelID;";
		$this->query($sql);

                $sql="UPDATE fac_PowerDistribution SET PanelID2=0 WHERE PanelID2=$this->PanelID;";
                $this->query($sql);

                //Then the mechanical devices
                $sql="UPDATE fac_MechanicalDevice SET PanelID=0 WHERE PanelID=$this->PanelID;";
                $this->query($sql);

                $sql="UPDATE fac_MechanicalDevice SET PanelID2=0 WHERE PanelID2=$this->PanelID;";
                $this->query($sql);

		//remove links between this PowerPanel and measure points
		$mp = new MeasurePoint();
		$mp->EquipmentType = "PowerPanel";
		$mp->EquipmentID = $this->PanelID;
		$mpList = $mp->GetMPByEquipment();

		foreach($mpList as $mpLinked) {
			$mpLinked->EquipmentType = "None";
			$mpLinked->EquipmentID = 0;
			$mpLinked->UpdateMP();
		}

		$sql="DELETE FROM fac_PowerPanel WHERE PanelID=$this->PanelID;";
		if(!$this->exec($sql)){
			$info=$this->errorInfo();

			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}
		
	function updatePanel(){
		$this->MakeSafe();
		
		$oldpanel=new PowerPanel();
		$oldpanel->PanelID=$this->PanelID;
		$oldpanel->getPanel();

		$sql="UPDATE fac_PowerPanel SET PanelIPAddress=\"$this->PanelIPAddress\",
			PanelLabel=\"$this->PanelLabel\", NumberOfPoles=$this->NumberOfPoles, 
			MainBreakerSize=$this->MainBreakerSize, PanelVoltage=$this->PanelVoltage, 
			NumberScheme=\"$this->NumberScheme\", ParentPanelID=$this->ParentPanelID,
			ParentBreakerName=\"$this->ParentBreakerName\", TemplateID=$this->TemplateID
			WHERE PanelID=$this->PanelID;";

		if(!$this->query($sql)){
			$info=$this->errorInfo();
			error_log("updatePanel::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this,$oldpanel):'';
		return true;
	}

	function Search($indexedbyid=false,$loose=false){
		// Store the value of devicetype before we muck with it
		$os=$this->NumberScheme;

		// Make everything safe for us to search with
		$this->MakeSafe();

		// This will store all our extended sql
		$sqlextend="";
		foreach($this as $prop => $val){
			// We force NumberScheme to a known value so this is to check if they wanted to search for the default
			if($prop=="NumberScheme" && $val=="Sequential" && $os!="Sequential"){
				continue;
			}
			if($val){
				extendsql($prop,$val,$sqlextend,$loose);
			}
		}

		$sql="SELECT * FROM fac_PowerPanel $sqlextend ORDER BY PanelLabel ASC;";

		$panelList=array();

		foreach($this->query($sql) as $row){
			if($indexedbyid){
				$panelList[$deviceRow["DeviceID"]]=PowerPanel::RowToObject($row);
			}else{
				$panelList[]=PowerPanel::RowToObject($row);
			}
		}

		return $panelList;
	}

	// Make a simple reference to a loose search
	function LooseSearch($indexedbyid=false){
		return $this->Search($indexedbyid,true);
	}

	function GetWattage() {
                $ret->Wattage1 = 0;
                $ret->Wattage2 = 0;
                $ret->Wattage3 = 0;
                $ret->LastRead = date("Y-m-d H:i:s");

                $measureFound = false;

                $mpList = new MeasurePoint();
                $mpList->EquipmentType = "PowerPanel";
                $mpList->EquipmentID = $this->PanelID;
                $mpList = $mpList->GetMPByEquipment();

                foreach($mpList as $mp) {
                        if($mp->Type == "elec") {
                                if($mp->Category!="UPS Output") {
                                        $lastMeasure = new ElectricalMeasure();
                                        $lastMeasure->MPID = $mp->MPID;
                                        $lastMeasure = $lastMeasure->GetLastMeasure();

                                        if(!is_null($lastMeasure->Date)) {
                                                $measureFound = true;
                                                $ret->Wattage1 += $lastMeasure->Wattage1;
						$ret->Wattage2 += $lastMeasure->Wattage2;
						$ret->Wattage3 += $lastMeasure->Wattage3;
                                                if(strtotime($lastMeasure->Date) < strtotime($ret->LastRead))
                                                        $ret->LastRead = $lastMeasure->Date;
                                        }
                                }
                        }
                }

		if(!$measureFound) {
			$pduList = new PowerDistribution();
			$pduList->PanelID = $this->PanelID;
			$pduList = $pduList->GetPDUbyPanel();
			$mechList = new MechanicalDevice();
			$mechList->PanelID = $this->PanelID;
			$mechList = $mechList->GetMechByPanel();
			$panelList = new PowerPanel();
			$panelList->ParentPanelID = $this->PanelID;
			$panelList = $panelList->getPanelListBySource(true);

			foreach($pduList as $pdu) {
				if($wattage = $pdu->GetWattage()) {
					$measureFound = true;
					$ret->Wattage1 += $wattage->Wattage1;
					$ret->Wattage2 += $wattage->Wattage2;
					$ret->Wattage3 += $wattage->Wattage3;
					if(strtotime($wattage->Date) < strtotime($ret->LastRead))
                                        	$ret->LastRead = $wattage->Date;
				}
			}
			foreach($mechList as $mech) {
				if($wattage = $mech->GetWattage()) {
					$measureFound = true;
					$ret->Wattage1 += $wattage->Wattage1;
					$ret->Wattage2 += $wattage->Wattage2;
					$ret->Wattage3 += $wattage->Wattage3;
					if(strtotime($wattage->Date) < strtotime($ret->LastRead))
                                        	$ret->LastRead = $wattage->Date;
				}
			}
			foreach($panelList as $panel) {
				if($wattage = $panel->GetWattage()) {
					$measureFound = true;
					$ret->Wattage1 += $wattage->Wattage1;
					$ret->Wattage2 += $wattage->Wattage2;
					$ret->Wattage3 += $wattage->Wattage3;
					if(strtotime($wattage->Date) < strtotime($ret->LastRead))
                                        	$ret->LastRead = $wattage->Date;
				}
			}
		}
                return ($measureFound)?$ret:false;
        }
}

class PanelSchedule {
	/* PanelSchedule:	Create a panel schedule based upon all of the known connections.  In
						other words - if you take down Panel A4, what cabinets will be affected?
	*/
	
	var $PanelID;
	var $PolePosition;
	var $NumPoles;
	var $Label;

	function MakeSafe(){
		$this->PanelID=intval($this->PanelID);
		$this->PolePosition=intval($this->PolePosition);
		$this->NumPoles=intval($this->NumPoles);
		$this->Label=sanitize($this->Label);
	}

	function MakeDisplay(){
		$this->Label=stripslashes($this->Label);
	}

	function MakeConnection(){
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INTO fac_PanelSchedule SET PanelID=$this->PanelID, 
			PolePosition=$this->PolePosition, NumPoles=$this->NumPoles, 
			Label=\"$this->Label\" ON DUPLICATE KEY UPDATE Label=\"$this->Label\", 
			NumPoles=$this->NumPoles;";

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $dbh->query($sql);
	}

	function DisplayPanel(){
		global $dbh;

		$html="<table border=1>\n";
		  
		$pan=new PowerPanel();
		$pan->PanelID=$this->PanelID;
		$pan->getPanel();
		 
		$sched=array_fill( 1, $pan->NumberOfPoles, "<td>&nbsp;</td>" );

		$sql="SELECT * FROM fac_PanelSchedule WHERE PanelID=$this->PanelID ORDER BY PolePosition ASC;";

		foreach($dbh->query($sql) as $row){
			$sched[$row["PolePosition"]]="<td rowspan={$row["NumPoles"]}>{$row["Label"]}</td>";
		  
			if($row["NumPoles"] >1){
				$sched[$row["PolePosition"] + 2] = "";
			}
		  
			if($row["NumPoles"] >2){
				$sched[$row["PolePosition"] + 4] = "";
			}

			for($i=1; $i< $pan->NumberOfPoles + 1; $i++){
				$html .= "<tr><td>$i</td>{$sched[$i]}<td>".($i+1)."</td>{$sched[++$i]}</tr>\n";
			}
		}

		$html .= "</table>\n";

		return $html;
	}
}
?>
