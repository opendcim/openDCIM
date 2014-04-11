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
	var $SNMPVersion;
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
		$validMultipliers=array(0.1,1,10,100);
		$validProcessingProfiles=array('SingleOIDWatts','SingleOIDAmperes',
			'Combine3OIDWatts','Combine3OIDAmperes','Convert3PhAmperes');

		$this->TemplateID=intval($this->TemplateID);
		$this->ManufacturerID=intval($this->ManufacturerID);
		$this->Model=addslashes(trim($this->Model));
		$this->Managed=intval($this->Managed);
		$this->ATS=intval($this->ATS);
		$this->SNMPVersion=(in_array($this->SNMPVersion, $validSNMPVersions))?$this->SNMPVersion:'2c';
		$this->VersionOID=addslashes(trim($this->VersionOID));
		$this->Multiplier=(in_array($this->Multiplier, $validMultipliers))?$this->Multiplier:1;
		$this->OID1=addslashes(trim($this->OID1));
		$this->OID2=addslashes(trim($this->OID2));
		$this->OID3=addslashes(trim($this->OID3));
		$this->ATSStatusOID=addslashes(trim($this->ATSStatusOID));
		$this->ATSDesiredResult=addslashes(trim($this->ATSDesiredResult));
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
		$template->SNMPVersion=$row["SNMPVersion"];
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

		foreach($dbh->query($sql) as $row){
			foreach(CDUTemplate::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}
		
		return;
	}
	
	function CreateTemplate() {
		global $dbh;

		$this->MakeSafe();
		
		$sql="INSERT INTO fac_CDUTemplate SET ManufacturerID=$this->ManufacturerID, 
			Model=\"$this->Model\", Managed=$this->Managed, ATS=$this->ATS,
			SNMPVersion=\"$this->SNMPVersion\", VersionOID=\"$this->VersionOID\", 
			Multiplier=\"$this->Multiplier\", OID1=\"$this->OID1\", OID2=\"$this->OID2\", 
			OID3=\"$this->OID3\", ATSStatusOID=\"$this->ATSStatusOID\", ATSDesiredResult=\"$this->ATSDesiredResult\",
			ProcessingProfile=\"$this->ProcessingProfile\", 
			Voltage=$this->Voltage, Amperage=$this->Amperage, NumOutlets=$this->NumOutlets;";
		
		if(!$dbh->exec($sql)){
			// A combination of this Mfg + Model already exists most likely
			return false;
		}else{
			$this->TemplateID = $dbh->lastInsertID();
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
			SNMPVersion=\"$this->SNMPVersion\", VersionOID=\"$this->VersionOID\", 
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
		$this->PDUPosition=intval($this->PDUPosition);
		$this->DeviceID=intval($this->DeviceID);
		$this->DeviceConnNumber=intval($this->DeviceConnNumber);
	}

	static function RowToObject($row){
		$conn=new PowerConnection;
		$conn->PDUID=$row["PDUID"];
		$conn->PDUPosition=$row["PDUPosition"];
		$conn->DeviceID=$row["DeviceID"];
		$conn->DeviceConnNumber=$row["DeviceConnNumber"];

		return $conn;
	}

	function CanWrite(){
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
		$write=(User::Current()->canWrite($cab->AssignedTo))?true:$write;

		return $write;
	}

	function CreateConnection(){
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INTO fac_PowerConnection SET DeviceID=$this->DeviceID, 
			DeviceConnNumber=$this->DeviceConnNumber, PDUID=$this->PDUID, 
			PDUPosition=$this->PDUPosition ON DUPLICATE KEY UPDATE DeviceID=$this->DeviceID,
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
			PDUPosition=$this->PDUPosition;";

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
			PDUPosition=$this->PDUPosition;";
    
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

	function CreatePDU(){
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INTO fac_PowerDistribution SET Label=\"$this->Label\", 
			CabinetID=$this->CabinetID, TemplateID=$this->TemplateID, 
			IPAddress=\"$this->IPAddress\", SNMPCommunity=\"$this->SNMPCommunity\", 
			PanelID=$this->PanelID, BreakerSize=$this->BreakerSize, 
			PanelPole=$this->PanelPole, InputAmperage=$this->InputAmperage, 
			FailSafe=$this->FailSafe, PanelID2=$this->PanelID2, PanelPole2=$this->PanelPole2;";

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
		$panel->GetPanel();

		return $panel->PowerSourceID;
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

		$Wattage=intval($Wattage);
	
		$sql="INSERT INTO fac_PDUStats SET Wattage=$Wattage, PDUID=$this->PDUID, 
			LastRead=NOW() ON DUPLICATE KEY UPDATE Wattage=$Wattage, LastRead=NOW();";
		
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return ($this->query($sql))?$this->GetLastReading():false;
	}
	
	function UpdateStats(){
		global $config;
		
		if(function_exists("snmpget")){
			$usePHPSNMP=true;
		}else{
			$usePHPSNMP=false;
		}
		
		$config=new Config();
		
		$sql="SELECT PDUID, IPAddress, SNMPCommunity, SNMPVersion, Multiplier, OID1, 
			OID2, OID3, ProcessingProfile, Voltage FROM fac_PowerDistribution a, 
			fac_CDUTemplate b WHERE a.TemplateID=b.TemplateID AND b.Managed=true 
			AND IPAddress>'' AND SNMPCommunity>''";
		
		// The result set should have no PDU's with blank IP Addresses or SNMP Community, so we can forge ahead with processing them all
		
		foreach($this->query($sql) as $row){
			// If only one OID is used, the OID2 and OID3 should be blank, so no harm in just making one string
			$OIDString = $row["OID1"] . " " . $row["OID2"] . " " . $row["OID3"];
			
			// Have to reset this every time, otherwise the exec() will append
			unset($statsOutput);
			$amps=0;
			$watts=0;
			
			if ( $row["SNMPCommunity"] == "" ) {
				$Community = $config->ParameterArray["SNMPCommunity"];
			} else {
				$Community = $row["SNMPCommunity"];
			}
			
			if ( $usePHPSNMP ) {
				if ( $row["SNMPVersion"] == "2c" ){
					$tmp = explode( " ", @snmp2_get( $row["IPAddress"], $Community, $row["OID1"] ));
				}else{
					$tmp = explode( " ", @snmpget( $row["IPAddress"], $Community, $row["OID1"] ));
				}
				
				$pollValue1 = @$tmp[1];
				
				if ( $row["OID2"] != "" ) {
					if ( $row["SNMPVersion"] == "2c" ){
						$tmp2 = explode( " ", @snmp2_get( $row["IPAddress"], $Community, $row["OID2"] ));
					}else{
						$tmp2 = explode( " ", @snmpget( $row["IPAddress"], $Community, $row["OID2"] ));
					}
					if ( sizeof( $tmp2 ) > 0 ){
						$pollValue2 = $tmp2[1];
					}
				}
				
				if ( $row["OID3"] != "" ) {
					if ( $row["SNMPVersion"] == "2c" ){
						$tmp3 = explode( " ", @snmp2_get( $row["IPAddress"], $Community, $row["OID3"] ));
					}else{
						$tmp3 = explode( " ", @snmpget( $row["IPAddress"], $Community, $row["OID3"] ));
					}
					if ( sizeof( $tmp3 ) > 0 ){
						$pollValue3 = $tmp3[1];
					}
				}
			} else {
				$pollCommand="{$config->ParameterArray["snmpget"]} -v {$row["SNMPVersion"]} -t 0.5 -r 2 -c $Community {$row["IPAddress"]} $OIDString | {$config->ParameterArray["cut"]} -d: -f4";
				
				exec( $pollCommand, $statsOutput );
				
				$pollValue1 = @$statsOutput[0];
				$pollValue2 = @$statsOutput[1];
				$pollValue3 = @$statsOutput[2];
			}
			
			if($pollValue1!=""){
				// The multiplier should be an int but no telling what voodoo the db might cause
				$pollValue1=intval($pollValue1);
				$pollValue2=@intval($pollValue2);
				$pollValue3=@intval($pollValue3);
				$row["Multiplier"]=floatval($row["Multiplier"]);
				$row["Voltage"]=intval($row["Voltage"]);

				switch ( $row["ProcessingProfile"] ) {
					case "SingleOIDAmperes":
						$amps=$pollValue1/$row["Multiplier"];
						$watts=$amps * $row["Voltage"];
						break;
					case "Combine3OIDAmperes":
						$amps=($pollValue1 + $pollValue2 + $pollValue3) / $row["Multiplier"];
						$watts=$amps * $row["Voltage"];
						break;
					case "Convert3PhAmperes":
						// OO does this next formula need another set of () to be clear?
						$amps=($pollValue1 + $pollValue2 + $pollValue3) / $row["Multiplier"] / 3;
						$watts=$amps * 1.732 * $row["Voltage"];
						break;
					case "Combine3OIDWatts":
						$watts=($pollValue1 + $pollValue2 + $pollValue3) / $row["Multiplier"];
						break;
					default:
						$watts=$pollValue1 / $row["Multiplier"];
						break;
				}
			}
			
			$sql="INSERT INTO fac_PDUStats SET PDUID={$row["PDUID"]}, Wattage=$watts, LastRead=now() ON 
				DUPLICATE KEY UPDATE Wattage=$watts, LastRead=now();";
			$this->exec($sql);
			
			$this->PDUID=$row["PDUID"];      
			$sql="UPDATE fac_PowerDistribution SET FirmwareVersion=\"".
				$this->GetSmartCDUVersion()."\" WHERE PDUID=$this->PDUID;";
			$this->exec($sql);
		}
	}
	
	function getATSStatus() {
		global $config;
		
		if ( ! function_exists( "snmpget" ) ) {
			return;
		}
		
		$tmpl = new CDUTemplate();
		$tmpl->TemplateID = $this->TemplateID;
		$tmpl->GetTemplate();
		
		if ( ! $this->IPAddress || ! $tmpl->ATSStatusOID ) {
			return;
		}
		
		if ( $this->SNMPCommunity == "" ) {
			$Community = $config->ParameterArray["SNMPCommunity"];
		} else {
			$Community = $this->SNMPCommunity;
		}
		
		if ( $tmpl->SNMPVersion == "1" ) {
			list( $trash, $result ) = explode( ":", snmpget( $this->IPAddress, $Community, $tmpl->ATSStatusOID ));
		} else {
			list( $trash, $result ) = explode( ":", snmp2_get( $this->IPAddress, $Community, $tmpl->ATSStatusOID ));
		}
		
		return $result;
	}
	
	function GetSmartCDUUptime(){
		global $config;

		$this->GetPDU();
		$tmpl=new CDUTemplate();
		$tmpl->TemplateID=$this->TemplateID;
		$tmpl->GetTemplate();

		if ( ! $this->IPAddress ) {
			return "Not Configured";
		} else {
			$serverIP = $this->IPAddress;
			if ( $this->SNMPCommunity == "" ) {
				$Community = $config->ParameterArray["SNMPCommunity"];
			} else {
				$Community = $this->SNMPCommunity;
			}
			
			if(!function_exists("snmpget")){
				$pollCommand ="{$config->ParameterArray["snmpget"]} -v 2c -t 0.5 -r 2 -c $Community $serverIP sysUpTimeInstance";

				exec($pollCommand, $statsOutput);
				// need error checking here

				if(count($statsOutput) >0){
					$statsOutput=explode(")",$statsOutput[0]);
					$upTime=end($statsOutput);
				}else{
					$upTime = "Unknown";
				}
			}else{
				if($tmpl->SNMPVersion=="2c"){
					$result = explode( ")", @snmp2_get( $this->IPAddress, $Community, "sysUpTimeInstance" ));
				}else{
					$result = explode( ")", @snmpget( $this->IPAddress, $Community, "sysUpTimeInstance" ));
				}				
				$upTime = trim( @$result[1] );
			}
			
			return $upTime;
		}
	}
  
	function GetSmartCDUVersion(){
		global $config;
		
		$this->GetPDU();
		
		$template=new CDUTemplate();
		$template->TemplateID=$this->TemplateID;
		$template->GetTemplate();

		if ( ! $this->IPAddress ) {
			return "Not Configured";
		} else {
			$serverIP = $this->IPAddress;
			
			if ( $this->SNMPCommunity == "" ) {
				$Community = $config->ParameterArray["SNMPCommunity"];
			} else {
				$Community = $this->SNMPCommunity;
			}
			
			if(!function_exists("snmpget")){
				$pollCommand="{$config->ParameterArray["snmpget"]} -v 2c -t 0.5 -r 2 -c $Community $this->IPAddress $template->VersionOID";

				exec( $pollCommand, $statsOutput );
				// need error checking here

				if(count($statsOutput) >0){
					$version = str_replace( "\"", "", end( explode( " ", $statsOutput[0] ) ) );
				}else{
					$version = "Unknown";
				}
			}else{
				if($template->SNMPVersion=="2c"){
					$result = explode( "\"", @snmp2_get( $this->IPAddress, $Community, $template->VersionOID ));
				}else{
					$result = explode( "\"", @snmpget( $this->IPAddress, $Community, $template->VersionOID ));
				}
				$version = @$result[1];
			}
			
			return $version;
		}
	}

	function DeletePDU(){
		$this->MakeSafe();

		// Do not attempt anything else if the lookup fails
		if(!$this->GetPDU()){return false;}

		// Check rights
		$cab=new Cabinet();
		$cab->CabinetID=$this->CabinetID;
		$cab->GetCabinet();
		if(!User::Current()->canWrite($cab->AssignedTo)){return false;}

		// First, remove any connections to the PDU
		$tmpConn=new PowerConnection();
		$tmpConn->PDUID=$this->PDUID;
		$connList=$tmpConn->GetConnectionsByPDU();
		
		foreach($connList as $delConn){
			$delConn->RemoveConnection();
		}
		
		$sql="DELETE FROM fac_PowerDistribution WHERE PDUID=$this->PDUID;";
		if(!$this->exec($sql)){
			// Something went south and this didn't delete.
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
	}
}

class PowerPanel {
	/* PowerPanel:	PowerPanel(s) are the parents of PowerDistribution (power strips) and the children
					PowerSource(s).  Panels are arranged as either Odd/Even (odd numbers on the left,
					even on the right) or Sequential (1 to N in a single column) numbering for the
					purpose of building out a panel schedule.
	*/
	
	var $PanelID;
	var $PowerSourceID;
	var $PanelLabel;
	var $NumberOfPoles;
	var $MainBreakerSize;
	var $PanelVoltage;
	var $NumberScheme;

	function MakeSafe(){
		$this->PanelID=intval($this->PanelID);
		$this->PowerSourceID=intval($this->PowerSourceID);
		$this->PanelLabel=addslashes(trim($this->PanelLabel));
		$this->NumberOfPoles=intval($this->NumberOfPoles);
		$this->MainBreakerSize=intval($this->MainBreakerSize);
		$this->PanelVoltage=intval($this->PanelVoltage);
		$this->NumberScheme=($this->NumberScheme=='Sequential')?$this->NumberScheme:'Odd/Even';
	}

	function MakeDisplay(){
		$this->PanelLabel=stripslashes($this->PanelLabel);
	}

	static function RowToObject($row){
		$panel=new PowerPanel();
		$panel->PanelID=$row["PanelID"];
		$panel->PowerSourceID=$row["PowerSourceID"];
		$panel->PanelLabel=$row["PanelLabel"];
		$panel->NumberOfPoles=$row["NumberOfPoles"];
		$panel->MainBreakerSize=$row["MainBreakerSize"];
		$panel->PanelVoltage=$row["PanelVoltage"];
		$panel->NumberScheme=$row["NumberScheme"];

		$panel->MakeDisplay();

		return $panel;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}

	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}

	function Search($sql){
		$PanelList=array();
		foreach($this->query($sql) as $row){    
			$PanelList[]=PowerPanel::RowToObject($row);
		}

		return $PanelList;
	}
	
	static function GetPanelsByDataCenter($DataCenterID){
		$sql="SELECT * FROM fac_PowerPanel a, fac_PowerSource b WHERE 
			a.PowerSourceID=b.PowerSourceID AND b.DataCenterID=\"".intval($DataCenterID).
			"\" ORDER BY PanelLabel;";
	  
		return $this->Search($sql);
	}

	function GetPanelList(){
		$sql="SELECT * FROM fac_PowerPanel ORDER BY PanelLabel;";

		return $this->Search($sql);
	}
  
	function GetPanelListBySource(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerPanel WHERE PowerSourceID=$this->PowerSourceID ORDER BY PanelLabel";

		return $this->Search($sql);
	}
  
	function GetPanel() {
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerPanel WHERE PanelID=$this->PanelID;";

		if($row=$this->query($sql)->fetch()){
			foreach(PowerPanel::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='PanelID'){
					$this->$prop=null;
				}
			}
		}
		return true;
	}

  
	function CreatePanel(){
		global $dbh;
		$this->MakeSafe();

		$sql="INSERT INTO fac_PowerPanel SET PowerSourceID=$this->PowerSourceID, 
			PanelLabel=\"$this->PanelLabel\", NumberOfPoles=$this->NumberOfPoles, 
			MainBreakerSize=$this->MainBreakerSize, PanelVoltage=$this->PanelVoltage, 
			NumberScheme=\"$this->NumberScheme\";";

		if($dbh->exec($sql)){
			$this->PanelID=$dbh->lastInsertId();

			return $this->PanelID;
		}else{
			$info=$dbh->errorInfo();

			error_log("CreatePanel::PDO Error: {$info[2]} SQL=$sql");

			return false;
		}
	}

	function DeletePanel() {
		global $dbh;
		$this->MakeSafe();
		
		// First, set any CDUs attached to this panel to simply not have an assigned panel
		$sql = "update fac_PowerDistribution set PanelID='' where PanelID='".$this->PanelID."'";
		$dbh->exec( $sql );
		
		$sql = "delete from fac_PowerPanel where PanelID='".$this->PanelID."'";
		$dbh->exec( $sql );
	}
		
	function UpdatePanel(){
		global $dbh;
		$this->MakeSafe();

		$sql="UPDATE fac_PowerPanel SET PowerSourceID=$this->PowerSourceID, 
			PanelLabel=\"$this->PanelLabel\", NumberOfPoles=$this->NumberOfPoles, 
			MainBreakerSize=$this->MainBreakerSize, PanelVoltage=$this->PanelVoltage, 
			NumberScheme=\"$this->NumberScheme\" WHERE PanelID=$this->PanelID;";

		return $dbh->query($sql);
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
		$this->Label=addslashes(trim($this->Label));
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

		return $dbh->query($sql);
	}

	function DisplayPanel(){
		global $dbh;

		$html="<table border=1>\n";
		  
		$pan=new PowerPanel();
		$pan->PanelID=$this->PanelID;
		$pan->GetPanel();
		 
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

class PowerSource {
	/* PowerSource:		This is the most upstream power source that is managed in DCIM.
						You will need to have at least one power source per data center, 
						even if they are physically the same (such as 1 UPS for the
						entire site, or utility power for multiple sites).  Small data
						centers will most likely have just one power source per data centers,
						but large ones may even equate utility power down to which feeder
						or transfer switch that is in use.
						
						At this time there are no parent/child relationships between
						power sources, but it may be implemented in a future release.
	*/
	
	var $PowerSourceID;
	var $SourceName;
	var $DataCenterID;
	var $IPAddress;
	var $Community;
	var $LoadOID;
	var $Capacity;

	function MakeSafe(){
		$this->PowerSourceID=intval($this->PowerSourceID);
		$this->SourceName=addslashes(trim($this->SourceName));
		$this->DataCenterID=intval($this->DataCenterID);
		$this->IPAddress=addslashes(trim($this->IPAddress));
		$this->Community=addslashes(trim($this->Community));
		$this->LoadOID=addslashes(trim($this->LoadOID));
		$this->Capacity=intval($this->Capacity);
	}

	function MakeDisplay(){
		$this->SourceName=stripslashes($this->SourceName);
		$this->IPAddress=stripslashes($this->IPAddress);
		$this->Community=stripslashes($this->Community);
		$this->LoadOID=stripslashes($this->LoadOID);
	}

	static function RowToObject($row){
		$source=new PowerSource;
		$source->PowerSourceID=$row["PowerSourceID"];
		$source->SourceName=$row["SourceName"];
		$source->DataCenterID=$row["DataCenterID"];
		$source->IPAddress=$row["IPAddress"];
		$source->Community=$row["Community"];
		$source->LoadOID=$row["LoadOID"];
		$source->Capacity=$row["Capacity"];

		$source->MakeDisplay();

		return $source;
	}

	function CreatePowerSource(){
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INTO fac_PowerSource SET SourceName=\"$this->SourceName\", 
			DataCenterID=$this->DataCenterID, IPAddress=\"$this->IPAddress\", 
			Community=\"$this->Community\", LoadOID=\"$this->LoadOID\", 
			Capacity=$this->Capacity;";

		if(!$dbh->exec($sql)){
			return false;
		}else{
			$this->PowerSourceID = $dbh->lastInsertID();
		}
		
		return $this->PowerSourceID;
	}
	
	function DeletePowerSource() {
		global $dbh;
		
		$this->MakeSafe();
		
		$pp = new PowerPanel();
		$pp->PowerSourceID = $this->PowerSourceID;
		$ppList = $pp->GetPanelListBySource();
		
		foreach( $ppList as $p ) {
			$p->DeletePanel();
		}
		
		$sql = "delete from fac_PowerSource where PowerSourceID='".$this->PowerSourceID."'";
		$dbh->exec( $sql );
	}

	function UpdatePowerSource(){
		global $dbh;

		$this->MakeSafe();

		$sql="UPDATE fac_PowerSource SET SourceName=\"$this->SourceName\", 
			DataCenterID=$this->DataCenterID, IPAddress=\"$this->IPAddress\", 
			Community=\"$this->Community\", LoadOID=\"$this->LoadOID\", 
			Capacity=$this->Capacity WHERE PowerSourceID=$this->PowerSourceID;";

		return $dbh->query($sql);
	}

	function GetSourcesByDataCenter(){ 
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerSource WHERE DataCenterID=$this->DataCenterID;";

		$SourceList=array();
		foreach($dbh->query($sql) as $row){
			$SourceList[$row["PowerSourceID"]]=PowerSource::RowToObject($row);
		}

		return $SourceList;
	}

	function GetPSList(){
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerSource ORDER BY SourceName ASC;";

		$SourceList=array();
		foreach($dbh->query($sql) as $row){
			$SourceList[]=PowerSource::RowToObject($row);
		}

		return $SourceList;
	} 

	function GetSource(){
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerSource WHERE PowerSourceID=$this->PowerSourceID;";

		if($row=$dbh->query($sql)->fetch()){
			foreach(PowerSource::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='PowerSourceID'){
					$this->$prop=null;
				}
			}
		}
	
		return true;
	}

	function GetCurrentLoad(){
		global $config;
		
		if ( $this->Community == "" ) {
			$Community = $config->ParameterArray["SNMPCommunity"];
		} else {
			$Community = $this->Community;
		}
		
		$totalLoad = 0;
		
		// Liebert UPS Query
		// Query OID .1.3.6.1.4.1.476.1.1.1.1.1.2.0 to get the model number
		// If model type is blank (NFinity), OID = 1.3.6.1.4.1.476.1.42.3.5.2.2.1.8.3
		// If model type is Series 300 / 600, OID = .1.3.6.1.4.1.476.1.1.1.1.4.2.0
		$pollCommand=$config->ParameterArray["snmpget"]." -v 1 -c $Community $this->IPAddress .1.3.6.1.4.1.476.1.1.1.1.1.2.0 | ".$config->ParameterArray["cut"]." -d: -f4";
		exec($pollCommand,$snmpOutput);

		if(@$snmpOutput[0]!=""){
			$pollCommand=$config->ParameterArray["snmpget"]." -v 1 -c $Community $this->IPAddress .1.3.6.1.4.1.476.1.1.1.1.4.2.0 | ".$config->ParameterArray["cut"]." -d: -f4";
			exec($pollCommand,$loadOutput);
			
			$totalLoad=($loadOutput[0] * $this->Capacity) / 100;
		}else{
			$pollCommand=$config->ParameterArray["snmpget"]." -v 1 -c $Community $this->IPAddress .1.3.6.1.4.1.476.1.42.3.5.2.2.1.8.3 | ".$config->ParameterArray["cut"]." -d: -f4";
			exec($pollCommand,$loadOutput);
			
			$totalLoad=$loadOutput[0];
		}

		return $totalLoad;
	}

}

?>
