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

require_once('Class_ModbusTcp.inc');


class EnergyType {

	var $EnergyTypeID;
	var $Name;
	var $GasEmissionFactor;

	function MakeSafe() {
		$this->EnergyTypeID=intval($this->EnergyTypeID);
		$this->Name=sanitize($this->Name);
		$this->GasEmissionFactor=floatval($this->GasEmissionFactor);
	}

	function MakeDisplay() {
		$this->Name=stripslashes($this->Name);
	}

	static function RowToObject($dbRow) {
		$energytype=new EnergyType();
		$energytype->EnergyTypeID=$dbRow["EnergyTypeID"];
		$energytype->Name=$dbRow["Name"];
		$energytype->GasEmissionFactor=$dbRow["GasEmissionFactor"];

		$energytype->MakeDisplay();

		return $energytype;
	}

	function CreateEnergyType() {
		global $dbh;
	
		$this->MakeSafe();

		$sql = "INSERT INTO fac_EnergyType SET 
				Name=\"$this->Name\",
				GasEmissionFactor=$this->GasEmissionFactor;";
		
		if(!$dbh->exec($sql)) {
			$info=$dbh->errorInfo();

                        error_log("CreateEnergyType::PDO Error: {$info[2]} $sql");

			return false;
		} 
		$this->EnergyTypeID = $dbh->lastInsertID();
		(class_exists('LogActions'))?LogActions::LogThis($this):'';

		return $this->EnergyTypeID;
	}

	function DeleteEnergyType() {
		global $dbh;
		
		$this->MakeSafe();

		$sql="DELETE FROM fac_EnergyType WHERE EnergyTypeID=$this->EnergyTypeID;";
		if(!$dbh->exec($sql))
			return false;
		
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	function UpdateEnergyType(){
		global $dbh;

		$this->MakeSafe();

		$old=new EnergyType();
		$old->EnergyTypeID=$this->EnergyTypeID;
		$old->GetEnergyType();

		$sql="UPDATE fac_EnergyType SET 
			Name=\"$this->Name\", 
			GasEmissionFactor=$this->GasEmissionFactor
			WHERE EnergyTypeID=$this->EnergyTypeID;";

		if(!$dbh->query($sql)) {
			$info=$dbh->errorInfo();
                        error_log("UpdateEnergyType::PDO Error: {$info[2]} SQL=$sql");

			return false;
		}
		(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';

		return true;
	}

	function GetEnergyType(){
                global $dbh;

                $this->MakeSafe();

                $sql="SELECT * FROM fac_EnergyType WHERE EnergyTypeID=$this->EnergyTypeID;";

                if($row=$dbh->query($sql)->fetch()){
                        foreach(EnergyType::RowToObject($row) as $prop => $value){
                                $this->$prop=$value;
                        }
                        return true;
                }else{
                        return false;
                }
        }


	function GetEnergyTypeList() {
		global $dbh;

                $sql="SELECT * FROM fac_EnergyType ORDER BY Name ASC;";
                
                $energytypeList = array();
                foreach($dbh->query($sql) as $row){
                        $energytypeList[]=EnergyType::RowToObject($row);
                }
                
                return $energytypeList;
	}
}

class MeasurePoint {

	/* Mother class for measure points
	* A measure point can be any device in your data center. Application will poll it and add the measures to the data base.
	*
	*/

	var $MPID;		//ID of the measure point
	var $Label;		//name of the measure point
	var $IPAddress;		//IP address to connect to the mp
	var $Type;		//type of the mp. (elec, cooling or air) elec-> to measure power and energy, cooling-> to measure cooling device usage, air-> to measure temperature and humidity
	var $ConnectionType;	//type of connection (SNMP or Modbus)
	
	static $TypeTab = array(	"elec" => "Electrical",
					"cooling" => "Cooling",
					"air" => "Air");

	static $ConnectionTypeTab = array(	"SNMP" => "SNMP",
						"Modbus" => "Modbus");

	function __construct() {
		if(func_num_args() == 1)
			foreach(func_get_arg(0) as $key => $value)
				$this->$key = $value;
	}

	protected function MakeSafe() {
		$validConnectionTypes = array('SNMP', 'Modbus');
		$validTypes = array('elec', 'cooling', 'air');
		$this->MPID=intval($this->MPID);
		$this->Label=sanitize($this->Label);
		$this->IPAddress=sanitize($this->IPAddress);
		$this->ConnectionType=(in_array($this->ConnectionType, $validConnectionTypes))?$this->ConnectionType:'SNMP';
		$this->Type=(in_array($this->Type, $validTypes))?$this->Type:'elec';
	}

	protected function MakeDisplay() {
		$this->Label=stripslashes($this->Label);
		$this->IPAddress=stripslashes($this->IPAddress);
	}

	static function RowToObject($dbRow) {
		$mp=new MeasurePoint();
		$mp->MPID=$dbRow["MPID"];
		$mp->Label=$dbRow["Label"];
		$mp->IPAddress=$dbRow["IPAddress"];
		$mp->Type=$dbRow["Type"];
		$mp->ConnectionType=$dbRow["ConnectionType"];

		$mp->MakeDisplay();

		return $mp;
	}

	/*
	 * Valid values for $snmplookup:
	 * contact - alpha numeric return of the system contact
	 * description - alpha numeric return of the system description can include line breaks
	 * location - alpha numeric return of the location if set
	 * name - alpha numeric return of the name of the system
	 * services - int 
	 * uptime - int - uptime of the device returned as ticks.  tick defined as 1/1000'th of a second
	 */
	static protected function OSS_SNMP_Lookup($mp,$snmplookup,$oids=null){
		// This is find out the name of the function that called this to make the error logging more descriptive
		$caller=debug_backtrace();
		$caller=$caller[1]['function'];

		$snmpHost=new OSS_SNMP\SNMP($mp->IPAddress,$mp->SNMPCommunity,$mp->SNMPVersion,$mp->v3SecurityLevel,$mp->v3AuthProtocol,$mp->v3AuthPassphrase,$mp->v3PrivProtocol,$mp->v3PrivPassphrase);
		$snmpresult=array();
		foreach($oids as $key => $oid) {
			if($mp->IPAddress != '' && $oid != '') {
				try {
					$snmpresult[$key]=(is_null($oid))?$snmpHost->useSystem()->$snmplookup(true):$snmpHost->get($oid);
				}catch (Exception $e){
					//$mp->IncrementFailures();
					$snmpresult[$key] = false;
					error_log("MeasurePoint::$caller($mp->MPID) ".$e->getMessage());
				}
			} else {
				$snmpresult[$key] = false;
			}
		}

		//$mp->ResetFailures();
		return $snmpresult;
	}

	static protected function Modbus_Lookup($mp, $registerTab) {

		/*
		 *ModbusMeasure: Read information through Modbus connection
		 *	-IPAddress: IP address of the device
		 *	-UnitID: ID of the device
		 *	-register: First register to read
		 *	-nbWords: nb registers to read
		 */
		global $config;
		usleep(1000);
		$valTab = array();
		$modbus = new ModbusTcp();
		$modbus->SetAdIpPLC($mp->IPAddress);
		$modbus->Unit = $mp->UnitID;
		foreach($registerTab as $key => $register) {
			if($mp->IPAddress != '') {
				if($register > 0) {
					//We add 400001 to read a register
					$ret = $modbus->ReadModbus($register+400001, $mp->NbWords);
					$val = 0;
					foreach($ret as $r)
						$val = $val * 65536 + $r;
					$valTab[$key] = $val;
				} else {
					$valTab[$key] = false;
				}
			} else {
				$valTab[$key] = false;
			}
		}
		$modbus->ModClose();
		return $valTab;
	}

	protected function CreateMP() {
		global $dbh;

		$this->MakeSafe();

		$sql = "INSERT INTO fac_MeasurePoint SET 
				Label=\"$this->Label\",
				IPAddress=\"$this->IPAddress\",
				Type=\"$this->Type\",
				ConnectionType=\"$this->ConnectionType\";";
		
		if(!$dbh->exec($sql))
			return false;
		else
			$this->MPID = $dbh->lastInsertID();
		
		return $this->MPID;
	}

	protected function DeleteMP() {
		global $dbh;
		
		$this->MakeSafe();

		$sql="DELETE FROM fac_AssoMeasurePointGroup WHERE MPID=$this->MPID;";	
		$dbh->exec($sql);

		$sql="DELETE FROM fac_MeasurePoint WHERE MPID=$this->MPID;";
		if(!$dbh->exec($sql))
			return false;
		
		return true;
	}

	protected function UpdateMP(){
		global $dbh;

		$this->MakeSafe();

		$sql="UPDATE fac_MeasurePoint SET 
			Label=\"$this->Label\", 
			IPAddress=\"$this->IPAddress\", 
			Type=\"$this->Type\",
			ConnectionType=\"$this->ConnectionType\" 
			WHERE MPID=$this->MPID;";
		if(!$dbh->query($sql))
			return false;

		return true;
	}

	function GetMP() {
		global $dbh;

		$sql = "SELECT Type, ConnectionType FROM fac_MeasurePoint WHERE MPID=$this->MPID;";

		if($row=$dbh->query($sql)->fetch()){
			$class = self::$ConnectionTypeTab[$row["ConnectionType"]].self::$TypeTab[$row["Type"]]."MeasurePoint";

			if(is_object($ret = new $class)) {
				$ret->MPID = $this->MPID;
				return $ret->GetMP(); 
			}
		}
		return null;
	}

	function GetMPList() {
		$ret = array();
		foreach(MeasurePoint::$TypeTab as $type) {
			$class = $type."MeasurePoint";
			$mp = new $class;
			if(is_object($mp))
				$ret = array_merge($ret, $mp->GetMPList());
		}	
		return $ret;
	}

	function ImportMeasures($filePath) {
		$delimiter = ",";
		$enclosure = '"';
		$escape = "\\";

		$measureClass = MeasurePoint::$TypeTab[$this->Type]."Measure";
		foreach(get_class_vars($measureClass)  as $property => $value)
			$fields[] = $property;

		$date_exists = false;

		if(!($file = fopen($filePath, "r")))
			return __("Error: Couldn't open csv file.");

		if($columnsTitle = fgetcsv($file, 0, $delimiter, $enclosure, $escape)) {
			foreach($columnsTitle as $title) {
				if(!in_array($title, $fields)) {
					fclose($file);
					return __("Import Error: There is an invalid column title : ").' "'.$title.'"';
				}
				$date_exists = ($title == "Date")?true:$date_exists;
			}

			if(!$date_exists) {
				fclose($file);
				return __("Import Error: There is not any column for dates.");
			}

			while($line = fgetcsv($file, 0, $delimiter, $enclosure, $escape)) {
				$data = new $measureClass;
				$data->MPID = $this->MPID;
				foreach($columnsTitle as $key => $field) {
					$data->$field = $line[$key];
				}
				$data->CreateMeasure();
			}
			fclose($file);
			return __("Import is complete.");
		}
		fclose($file);
		return __("Error: Couldn't read columns title.");
	}
}


class ElectricalMeasurePoint extends MeasurePoint{

	/* A measure point to record power and energy
	* Used in DCEM and PUE pages
	*/	

	var $DataCenterID;	//Data center's id containing the mp
	var $EnergyTypeID;
	var $Category;		//category of the mp. It can be a UPS input, UPS output, cooling device, other mechanical device or IT device
	var $UPSPowered;	//is this mp powered by an UPS
	var $PowerMultiplier;	//multiplier to apply to power measure
	var $EnergyMultiplier;	//multiplier to apply to energy measure

	protected function MakeSafe() {
		$validMultipliers = array('0.01','0.1','1','10','100');
		$validCategories = array('none', 'IT', 'Cooling', 'Other Mechanical', 'UPS Input', 'UPS Output', 'Energy Reuse', 'Renewable Energy');

		parent::MakeSafe();

		$this->DataCenterID=intval($this->DataCenterID);
		$this->EnergyTypeID=intval($this->EnergyTypeID);
		$this->Category=(in_array($this->Category, $validCategories))?$this->Category:'none';
		$this->UPSPowered=intval($this->UPSPowered);
		$this->PowerMultiplier=(in_array($this->PowerMultiplier, $validMultipliers))?$this->PowerMultiplier:'1';
		$this->EnergyMultiplier=(in_array($this->EnergyMultiplier, $validMultipliers))?$this->EnergyMultiplier:'1';
	}

	protected function MakeDisplay() {
		parent::MakeDisplay();
	}

	static function RowToObject($dbRow) {
		$mp=new ElectricalMeasurePoint(parent::RowToObject($dbRow));
		$mp->DataCenterID=$dbRow["DataCenterID"];
		$mp->EnergyTypeID=$dbRow["EnergyTypeID"];
		$mp->Category=$dbRow["Category"];
		$mp->UPSPowered=$dbRow["UPSPowered"];
		$mp->PowerMultiplier=$dbRow["PowerMultiplier"];
		$mp->EnergyMultiplier=$dbRow["EnergyMultiplier"];

		return $mp;
	}

	protected function CreateMP() {
		global $dbh;

		$this->MakeSafe();

		if(parent::CreateMP()) {
			$sql = "INSERT INTO fac_ElectricalMeasurePoint SET
					MPID=$this->MPID, 
					DataCenterID=$this->DataCenterID, 
					EnergyTypeID=$this->EnergyTypeID,
					Category=\"$this->Category\",
					UPSPowered=\"$this->UPSPowered\",
					PowerMultiplier=\"$this->PowerMultiplier\",
					EnergyMultiplier=\"$this->EnergyMultiplier\";";
			
			if(!$dbh->exec($sql))
				return false;
			
			return $this->MPID;
		}
		return $false;
	}

	protected function DeleteMP() {
		global $dbh;
		
		$this->MakeSafe();

		if(parent::DeleteMP()) {
			
			$sql="DELETE FROM fac_ElectricalMeasure WHERE MPID=$this->MPID;";
			$dbh->exec($sql);

			$sql="DELETE FROM fac_ElectricalMeasurePoint WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;
			
			return true;
		}
		return false;
	}

	protected function UpdateMP(){
		global $dbh;

		$this->MakeSafe();

		$oldmp = new MeasurePoint();
		$oldmp->MPID = $this->MPID;
		$oldmp = $oldmp->GetMP();

		if(parent::UpdateMP()) {
			if($this->Type != $oldmp->Type) {
				$oldClass = MeasurePoint::$TypeTab[$oldmp->Type]."MeasurePoint";
				
				//if old class is not empty
				if(count(get_object_vars(new $oldClass)) > count(get_object_vars(new MeasurePoint()))) {
					$sql="DELETE FROM fac_$oldClass WHERE MPID = $this->MPID;";
					if(!$dbh->exec($sql))
						return false;
				}

				$sql="INSERT INTO fac_ElectricalMeasurePoint SET
					MPID=$this->MPID,
					DataCenterID=$this->DataCenterID,
					EnergyTypeID=$this->EnergyTypeID,
					Category=\"$this->Category\",
					UPSPowered=\"$this->UPSPowered\",
					PowerMultiplier=\"$this->PowerMultiplier\",
					EnergyMultiplier=\"$this->EnergyMultiplier\";";
				if(!$dbh->exec($sql))
					return false;
			} else {
				$sql="UPDATE fac_ElectricalMeasurePoint SET 
					DataCenterID=$this->DataCenterID,
					EnergyTypeID=$this->EnergyTypeID, 
					Category=\"$this->Category\", 
					UPSPowered=\"$this->UPSPowered\",
					PowerMultiplier=\"$this->PowerMultiplier\", 
					EnergyMultiplier=\"$this->EnergyMultiplier\" 
					WHERE MPID=$this->MPID;";
				if(!$dbh->query($sql))
					return false;
			}
			return true;
		}
		return false;
	}

	function GetMPList() {
		$ret = array();
		foreach(MeasurePoint::$ConnectionTypeTab as $connectionType) {
			$class = $connectionType."ElectricalMeasurePoint";
			$mp = new $class;
			if(is_object($mp))
				$ret = array_merge($ret, $mp->GetMPList());
		}	
		return $ret;
	}

	function GetMeasurePointsByDC() {
		$ret = array();
		foreach(MeasurePoint::$ConnectionTypeTab as $connectionType) {
			$class = $connectionType."ElectricalMeasurePoint";
			$mp = new $class; 
			if(is_object($mp)) {
				$mp->DataCenterID = $this->DataCenterID;
				$ret = array_merge($ret, $mp->GetMeasurePointsByDC());
			}
		}	
		return $ret;
	}

	function Poll(){
		global $config;
	
		$config=new Config();

		$m = new ElectricalMeasure();
		$m->MPID = $this->MPID;

		$lastMeasure = new ElectricalMeasure();
		$lastMeasure->MPID = $this->MPID;
		$lastMeasure->GetLastMeasure();

		switch($this->ConnectionType) {
			case "SNMP":
				if ( $this->SNMPCommunity == "" ) {
					$this->SNMPCommunity = $config->ParameterArray["SNMPCommunity"];
				}

				$values = self::OSS_SNMP_Lookup($this, null, array($this->OID1, $this->OID2, $this->OID3, $this->OIDEnergy));
				break;
			case "Modbus":
				$values = self::Modbus_Lookup($this, array($this->Register1, $this->Register2, $this->Register3, $this->RegisterEnergy));
				break;
		}
		if($values[0]!=false || $values[1]!=false || $values[2]!=false){
			$m->Wattage1=intval($values[0] * $this->PowerMultiplier);
			$m->Wattage2=@intval($values[1] * $this->PowerMultiplier);
			$m->Wattage3=@intval($values[2] * $this->PowerMultiplier);
		}
		if($values[3] != false) {
			$m->Energy=@intval($values[3] * $this->EnergyMultiplier);
		}
		else if($values[0]!=false || $values[1]!=false || $values[2]!=false){
			if($lastMeasure->Energy != null) {
				$values[3] = intval($lastMeasure->Energy + (($m->Wattage1 + $m->Wattage2 + $m->Wattage3) * (strtotime(date("Y-m-d H:i:s")) - strtotime($lastMeasure->Date)) / 3600) / 1000);
				$m->Energy = intval($values[3] * $this->EnergyMultiplier);
			} else {
				$m->Energy=0;
			}
		} else {
			//nothing to record
			return;
		}
		$m->Date=date("Y-m-d H:i:s");
		$m->CreateMeasure();
	}
}

class SNMPElectricalMeasurePoint extends ElectricalMeasurePoint {
	
	// Definition of an electrical measure point with a SNMP connection

	var $SNMPCommunity;	//SNMP community
	var $SNMPVersion;	//SNMP version
	var $v3SecurityLevel;
        var $v3AuthProtocol;
        var $v3AuthPassphrase;
        var $v3PrivProtocol;
        var $v3PrivPassphrase;
	var $OID1;		//OID of the first phase to measure power (W). If the measure point has less than 3 phases, let other OID blank
	var $OID2;		//OID of the second phase.
	var $OID3;		//OID of the third phase.
	var $OIDEnergy;		//OID to measure the energy. Measure has to be a counter (kW.h).

	function MakeSafe() {
		parent::Makesafe();
		$validVersions = array('1','2c','3');
                $validv3SecurityLevels=array('noAuthNoPriv','authNoPriv','authPriv');
                $validv3AuthProtocols=array('MD5','SHA');
                $validv3PrivProtocols=array('DES','AES');
		$this->SNMPCommunity=sanitize($this->SNMPCommunity);
		$this->SNMPVersion=(in_array($this->SNMPVersion, $validVersions))?$this->SNMPVersion:'1';
                $this->v3SecurityLevel=(in_array($this->v3SecurityLevel, $validv3SecurityLevels))?$this->v3SecurityLevel:'noAuthNoPriv';
                $this->v3AuthProtocol=(in_array($this->v3AuthProtocol, $validv3AuthProtocols))?$this->v3AuthProtocol:'MD5';
                $this->v3AuthPassphrase=sanitize($this->v3AuthPassphrase);
                $this->v3PrivProtocol=(in_array($this->v3PrivProtocol,$validv3PrivProtocols))?$this->v3PrivProtocol:'DES';
                $this->v3PrivPassphrase=sanitize($this->v3PrivPassphrase);
		$this->OID1=sanitize($this->OID1);
		$this->OID2=sanitize($this->OID2);
		$this->OID3=sanitize($this->OID3);
		$this->OIDEnergy=sanitize($this->OIDEnergy);
	}

	function MakeDisplay() {
		parent::MakeDisplay();
		$this->SNMPCommunity=stripslashes($this->SNMPCommunity);
		$this->OID1=stripslashes($this->OID1);
		$this->OID2=stripslashes($this->OID2);
		$this->OID3=stripslashes($this->OID3);
		$this->OIDEnergy=stripslashes($this->OIDEnergy);
	}

	static function RowToObject($dbRow) {
		$mp=new SNMPElectricalMeasurePoint(parent::RowToObject($dbRow));
		$mp->SNMPCommunity=$dbRow["SNMPCommunity"];
		$mp->SNMPVersion=$dbRow["SNMPVersion"];
                $mp->v3SecurityLevel=$dbRow["v3SecurityLevel"];
                $mp->v3AuthProtocol=$dbRow["v3AuthProtocol"];
                $mp->v3AuthPassphrase=$dbRow["v3AuthPassphrase"];
                $mp->v3PrivProtocol=$dbRow["v3PrivProtocol"];
                $mp->v3PrivPassphrase=$dbRow["v3PrivPassphrase"];
		$mp->OID1=$dbRow["OID1"];
		$mp->OID2=$dbRow["OID2"];
		$mp->OID3=$dbRow["OID3"];
		$mp->OIDEnergy=$dbRow["OIDEnergy"];

		$mp->MakeDisplay();
	
		return $mp;
	}

	function CreateMP() {
		global $dbh;

		$this->MakeSafe();

		if(parent::CreateMP()) {

			$sql = "INSERT INTO fac_SNMPElectricalMeasurePoint SET
					MPID=$this->MPID,
					SNMPCommunity=\"$this->SNMPCommunity\",
					SNMPVersion=\"$this->SNMPVersion\",
                                        v3SecurityLevel=\"$this->v3SecurityLevel\",
                                        v3AuthProtocol=\"$this->v3AuthProtocol\", 
                                        v3AuthPassphrase=\"$this->v3AuthPassphrase\", 
                                        v3PrivProtocol=\"$this->v3PrivProtocol\", 
                                        v3PrivPassphrase=\"$this->v3PrivPassphrase\",
					OID1=\"$this->OID1\",
					OID2=\"$this->OID2\",
					OID3=\"$this->OID3\",
					OIDEnergy=\"$this->OIDEnergy\";";
			
			if(!$dbh->exec($sql))
				return false;
		
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->MPID;
		}
		return false;
	}

	function DeleteMP() {
		global $dbh;
		
		$this->MakeSafe();
		
		if(parent::DeleteMP()) {
			$sql="DELETE FROM fac_SNMPElectricalMeasurePoint WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;

			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
		return false;
	}

	function UpdateMP(){
		global $dbh;
		
		$this->MakeSafe();
		
		$oldmp = new MeasurePoint();
		$oldmp->MPID = $this->MPID;
		$oldmp = $oldmp->GetMP();

		if($oldmp->ConnectionType != $this->ConnectionType || $oldmp->Type != $this->Type) {
			$oldClass = MeasurePoint::$ConnectionTypeTab[$oldmp->ConnectionType].MeasurePoint::$TypeTab[$oldmp->Type]."MeasurePoint";
			$sql="DELETE FROM fac_".$oldClass." WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;

			$sql = "INSERT INTO fac_SNMPElectricalMeasurePoint SET
					MPID=$this->MPID,
					SNMPCommunity=\"$this->SNMPCommunity\",
					SNMPVersion=\"$this->SNMPVersion\",
                                        v3SecurityLevel=\"$this->v3SecurityLevel\",
                                        v3AuthProtocol=\"$this->v3AuthProtocol\", 
                                        v3AuthPassphrase=\"$this->v3AuthPassphrase\", 
                                        v3PrivProtocol=\"$this->v3PrivProtocol\", 
                                        v3PrivPassphrase=\"$this->v3PrivPassphrase\",
					OID1=\"$this->OID1\",
					OID2=\"$this->OID2\",
					OID3=\"$this->OID3\",
					OIDEnergy=\"$this->OIDEnergy\";";
			if(!$dbh->exec($sql))
				return false;
		} else {
			$sql="UPDATE fac_SNMPElectricalMeasurePoint SET 
				SNMPCommunity=\"$this->SNMPCommunity\", 
				SNMPVersion=\"$this->SNMPVersion\", 
                                v3SecurityLevel=\"$this->v3SecurityLevel\",
                                v3AuthProtocol=\"$this->v3AuthProtocol\", 
                                v3AuthPassphrase=\"$this->v3AuthPassphrase\", 
                                v3PrivProtocol=\"$this->v3PrivProtocol\", 
                                v3PrivPassphrase=\"$this->v3PrivPassphrase\",
				OID1=\"$this->OID1\", 
				OID2=\"$this->OID2\", 
				OID3=\"$this->OID3\", 
				OIDEnergy=\"$this->OIDEnergy\" 
				WHERE MPID=$this->MPID;";
			if(!$dbh->query($sql))
				return false;
		}

		if(parent::UpdateMP())
			return false;
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldmp):'';
		return true;
	}

	function GetMP() {
		global $dbh;

		$this->MakeSafe();

		$sql="select * from fac_MeasurePoint NATURAL JOIN fac_ElectricalMeasurePoint NATURAL JOIN fac_SNMPElectricalMeasurePoint where MPID=$this->MPID;";

		if($row=$dbh->query($sql)->fetch()){
			foreach(SNMPElectricalMeasurePoint::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='MPID'){
					$this->$prop=null;
				}
			}
		}
	
		return $this;
	}

	function GetMPList() {
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_MeasurePoint NATURAL JOIN fac_ElectricalMeasurePoint NATURAL JOIN fac_SNMPElectricalMeasurePoint ORDER BY Label ASC;";

		$MPList=array();
		foreach($dbh->query($sql) as $row){
			$MPList[]=SNMPElectricalMeasurePoint::RowToObject($row);
		}

		return $MPList;
	}

	function GetMeasurePointsByDC() {
		global $dbh;

		$this->MakeSafe();

		$sql="select * from fac_MeasurePoint NATURAL JOIN fac_ElectricalMeasurePoint NATURAL JOIN fac_SNMPElectricalMeasurePoint where DataCenterID=$this->DataCenterID ORDER BY Label ASC;";

		$MPList=array();
		foreach($dbh->query($sql) as $row) {
			$MPList[]=SNMPElectricalMeasurePoint::RowToObject($row);
		}
		return $MPList;
	}
}

class ModbusElectricalMeasurePoint extends ElectricalMeasurePoint {
	
	//Defintion of an electrical measure point with a Modbus connection

	var $UnitID;		//ID of the measure point in the bus
	var $NbWords;		//quantity of words to read
	var $Register1;		//register of the first phase to measure power (W). If the measure point has less than 3 phases, let other registers blank
	var $Register2;		//register of the second phase.
	var $Register3;		//register of the third phase.
	var $RegisterEnergy;	//register to measure the energy. Measure has to be a counter (kW.h).

	function MakeSafe() {
		parent::Makesafe();
		$this->UnitID=intval($this->UnitID);
		$this->NbWords=intval($this->NbWords);
		$this->Register1=intval($this->Register1);
		$this->Register2=intval($this->Register2);
		$this->Register3=intval($this->Register3);
		$this->RegisterEnergy=intval($this->RegisterEnergy);
	}

	function MakeDisplay() {
		parent::MakeDisplay();
	}

	static function RowToObject($dbRow) {
		$mp=new ModbusElectricalMeasurePoint(parent::RowToObject($dbRow));
		$mp->UnitID=$dbRow["UnitID"];
		$mp->NbWords=$dbRow["NbWords"];
		$mp->Register1=$dbRow["Register1"];
		$mp->Register2=$dbRow["Register2"];
		$mp->Register3=$dbRow["Register3"];
		$mp->RegisterEnergy=$dbRow["RegisterEnergy"];

		$mp->MakeDisplay();

		return $mp;
	}

	function CreateMP() {
		global $dbh;

		$this->MakeSafe();

		if(parent::CreateMP()) {

			$sql = "INSERT INTO fac_ModbusElectricalMeasurePoint SET
					MPID=$this->MPID,
					UnitID=\"$this->UnitID\",
					NbWords=\"$this->NbWords\",
					Register1=\"$this->Register1\",
					Register2=\"$this->Register2\",
					Register3=\"$this->Register3\",
					RegisterEnergy=\"$this->RegisterEnergy\";";
			
			if(!$dbh->exec($sql))
				return false;
		
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->MPID;
		}
		return false;
	}

	function DeleteMP() {
		global $dbh;
		
		$this->MakeSafe();
		
		if(parent::DeleteMP()) {
			$sql="DELETE FROM fac_ModbusElectricalMeasurePoint WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;

			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
		return false;
	}

	function UpdateMP(){
		global $dbh;
		
		$this->MakeSafe();

		$oldmp = new MeasurePoint();
		$oldmp->MPID = $this->MPID;
		$oldmp = $oldmp->GetMP();

		if($oldmp->ConnectionType != $this->ConnectionType || $oldmp->Type != $this->Type) {
			$oldClass = MeasurePoint::$ConnectionTypeTab[$oldmp->ConnectionType].MeasurePoint::$TypeTab[$oldmp->Type]."MeasurePoint";
			$sql="DELETE FROM fac_".$oldClass." WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;

			$sql = "INSERT INTO fac_ModbusElectricalMeasurePoint SET
					MPID=$this->MPID,
					UnitID=\"$this->UnitID\",
					NbWords=\"$this->NbWords\",
					Register1=\"$this->Register1\",
					Register2=\"$this->Register2\",
					Register3=\"$this->Register3\",
					RegisterEnergy=\"$this->RegisterEnergy\";";
			if(!$dbh->exec($sql))
				return false;
		} else {
			$sql="UPDATE fac_ModbusElectricalMeasurePoint SET 
				UnitID=\"$this->UnitID\", 
				NbWords=\"$this->NbWords\", 
				Register1=\"$this->Register1\", 
				Register2=\"$this->Register2\", 
				Register3=\"$this->Register3\", 
				RegisterEnergy=\"$this->RegisterEnergy\" 
				WHERE MPID=$this->MPID;";
			if(!$dbh->query($sql))
				return false;
		}

		if(parent::UpdateMP())
			return false;
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldmp):'';
		return true;
	}

	function GetMP() {
		global $dbh;

		$this->MakeSafe();

		$sql="select * from fac_MeasurePoint NATURAL JOIN fac_ElectricalMeasurePoint NATURAL JOIN fac_ModbusElectricalMeasurePoint where MPID=$this->MPID;";

		if($row=$dbh->query($sql)->fetch()){
			foreach(ModbusElectricalMeasurePoint::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='MPID'){
					$this->$prop=null;
				}
			}
		}
		return $this;
	}

	function GetMPList() {
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_MeasurePoint NATURAL JOIN fac_ElectricalMeasurePoint NATURAL JOIN fac_ModbusElectricalMeasurePoint ORDER BY Label ASC;";

		$MPList=array();
		foreach($dbh->query($sql) as $row){
			$MPList[]=ModbusElectricalMeasurePoint::RowToObject($row);
		}

		return $MPList;
	}

	function GetMeasurePointsByDC() {
		global $dbh;

		$this->MakeSafe();

		$sql="select * from fac_MeasurePoint NATURAL JOIN fac_ElectricalMeasurePoint NATURAL JOIN fac_ModbusElectricalMeasurePoint where DataCenterID=$this->DataCenterID ORDER BY Label ASC;";

		$MPList=array();
		foreach($dbh->query($sql) as $row) {
			$MPList[]=ModbusElectricalMeasurePoint::RowToObject($row);
		}
		return $MPList;
	}
}

class ElectricalMeasure {

	// An ElectricalMeasure contains the values measured by an ElectricalMeasurePoint at a given date

	var $MPID;	//ID of the measure point
	var $Wattage1;	//power on the first phase (W)
	var $Wattage2;	//power on the second phase
	var $Wattage3;	//power on the third phase
	var $Energy;	//energy counter (kW.h)
	var $Date;	//date of the measure

	function MakeSafe() {
		$this->MPID=intval($this->MPID);
		$this->Wattage1=intval($this->Wattage1);
		$this->Wattage2=intval($this->Wattage2);
		$this->Wattage3=intval($this->Wattage3);
		$this->Energy=intval($this->Energy);
		$this->Date=date("Y-m-d H:i:s",strtotime($this->Date));
	}

	static function RowToObject($dbRow) {
		$m=new ElectricalMeasure();
		$m->MPID=$dbRow["MPID"];
		$m->Wattage1=$dbRow["Wattage1"];
		$m->Wattage2=$dbRow["Wattage2"];
		$m->Wattage3=$dbRow["Wattage3"];
		$m->Energy=$dbRow["Energy"];
		$m->Date=$dbRow["Date"];

		return $m;
	}

	function CreateMeasure() {
		global $dbh;

		$this->MakeSafe();

		$sql = "INSERT INTO fac_ElectricalMeasure SET
				MPID=$this->MPID,
				Wattage1=\"$this->Wattage1\",
				Wattage2=\"$this->Wattage2\",
				Wattage3=\"$this->Wattage3\",
				Energy=\"$this->Energy\",
				Date=\"$this->Date\";";
			
		if(!$dbh->exec($sql))
			return false;
		else
			return true;
	}

	function GetLastMeasure() {
		global $dbh;

		$this->MakeSafe();

		$sql = "SELECT * FROM fac_ElectricalMeasure WHERE MPID=$this->MPID AND Date=(SELECT MAX(Date) FROM fac_ElectricalMeasure WHERE MPID=$this->MPID);";
	
		if($row=$dbh->query($sql)->fetch()){
			foreach(ElectricalMeasure::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='MPID'){
					$this->$prop=null;
				}
			}
		}
		return $this;
	}

	function GetMeasuresOnInterval($start = "1970-01-01 00:00:00", $end = "3000-01-01 00:00:00") {
		global $dbh;

		$this->MakeSafe();

		$sql = "SELECT * FROM fac_ElectricalMeasure WHERE MPID=$this->MPID AND Date >= \"$start\" AND Date <= \"$end\" ORDER BY Date;";

		$measureList = array();
		foreach($dbh->query($sql) as $row) {
			$measureList[]=ElectricalMeasure::RowToObject($row);
		}
		return $measureList;
	}
}

class MeasurePointGroup {

	//It is a collection of measure points. It allows user to modify quickly a group of measure points
	//Measure point groups can also be used to generate a polling script
	//A measure point can ben added to plenty measure point groups
	//connection between mpg and measure points is defined by the AssoMeasurePointGroup class in data base

	var $MPGID;	//ID of the measure point group(mpg)
	var $Name;	//name of the mpg
	var $MPList;	//list of the measure points contained in the mpg 

	function MakeSafe() {
		$this->MPGID=intval($this->MPGID);
		$this->Name=sanitize($this->Name);
		if(is_array($this->MPList))
			foreach($this->MPList as $mpid)
				$mpid=intval($mpid);
		else
			$this->MPList=array();
	}

	function MakeDisplay() {
		$this->Name=stripslashes($this->Name);
	}

	static function RowToObject($dbrow) {
		$mpg = new MeasurePointGroup();
		$mpg->MPGID = $dbrow["MPGID"];
		$mpg->Name = $dbrow["Name"];
		foreach($dbrow["MPList"] as $d)	
			$mpg->MPList[] = $d["MPID"];

		$mpg->MakeDisplay();

		return $mpg;
	}

	function CreateMPG() {	
		global $dbh;

		$this->MakeSafe();

		$sql = "INSERT INTO fac_MeasurePointGroup SET
				Name=\"$this->Name\";";
			
		if(!$dbh->exec($sql))
			return false;
		$this->MPGID = $dbh->lastInsertID();

		foreach($this->MPList as $mp) {
			$sql = "INSERT INTO fac_AssoMeasurePointGroup SET
					MPGID=$this->MPGID,
					MPID=$mp;";
			$dbh->exec($sql);
		}
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->MPGID;
	}

	function GetMPG() {
		global $dbh;

		$this->MakeSafe();

		$sql = "SELECT * FROM fac_MeasurePointGroup WHERE MPGID=$this->MPGID;";
		$sqlList = "SELECT MPID FROM fac_AssoMeasurePointGroup WHERE MPGID=$this->MPGID;";
		
		if($row=$dbh->query($sql)->fetch()){
			$row["MPList"] = $dbh->query($sqlList)->fetchAll();
			foreach(MeasurePointGroup::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='MPGID'){
					$this->$prop=null;
				}
			}
		}
	
		return $this;
	}

	function GetMPGList() {
		global $dbh;

		$sql = "SELECT * FROM fac_MeasurePointGroup;";
		
		$mpglist=array();
		foreach($dbh->query($sql) as $row){
			$sqlList = "SELECT MPID FROM fac_AssoMeasurePointGroup WHERE MPGID=".$row["MPGID"].";";
			$row["MPList"] = $dbh->query($sqlList)->fetchAll();
			$mpglist[] = MeasurePointGroup::RowToObject($row);
		}
		return $mpglist;
	}

	function GetMeasurePointGroupsByDC($dc) {
		global $dbh;

		$sql = "SELECT * FROM fac_MeasurePointGroup 
				WHERE (SELECT COUNT(MPID) FROM fac_ElectricalMeasurePoint NATURAL JOIN fac_AssoMeasurePointGroup 
					WHERE fac_AssoMeasurePointGroup.MPGID = fac_MeasurePointGroup.MPGID
					AND DataCenterID=$dc) > 0;";
		
		$mpglist=array();
		foreach($dbh->query($sql) as $row){
			$sqlList = "SELECT MPID FROM fac_AssoMeasurePointGroup WHERE MPGID=".$row["MPGID"].";";
			$row["MPList"] = $dbh->query($sqlList)->fetchAll();
			$mpglist[] = MeasurePointGroup::RowToObject($row);
		}
		return $mpglist;
	}

	function GetMeasurePointGroupsByType($type) {
		global $dbh;

		$sql = "SELECT * FROM fac_MeasurePointGroup 
				WHERE (SELECT COUNT(MPID) FROM fac_MeasurePoint NATURAL JOIN fac_AssoMeasurePointGroup 
					WHERE fac_AssoMeasurePointGroup.MPGID = fac_MeasurePointGroup.MPGID
					AND Type=\"$type\") > 0;";
		
		$mpglist=array();
		foreach($dbh->query($sql) as $row){
			$sqlList = "SELECT MPID FROM fac_AssoMeasurePointGroup WHERE MPGID=".$row["MPGID"].";";
			$row["MPList"] = $dbh->query($sqlList)->fetchAll();
			$mpglist[] = MeasurePointGroup::RowToObject($row);
		}
		return $mpglist;
	}

	function DeleteMPG() {
		global $dbh;

		$this->MakeSafe();

		$script = __DIR__."/poll_scripts/".$this->Name."_".$this->MPGID.".php";

		if(is_file($script))
			unlink($script);

		$sql = "DELETE FROM fac_AssoMeasurePointGroup WHERE MPGID=$this->MPGID;";
		$dbh->exec($sql);

		$sql = "DELETE FROM fac_MeasurePointGroup WHERE MPGID=$this->MPGID;";	
		$dbh->exec($sql);

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
	}

	function UpdateMPG() {
		global $dbh;	
	
		$this->MakeSafe();

		$oldmpg = new MeasurePointGroup();
		$oldmpg->MPGID = $this->MPGID;
		$oldmpg = $oldmpg->GetMPG();

		$oldscript = __DIR__."/poll_scripts/".$oldmpg->Name."_".$oldmpg->MPGID.".php";
		$newscript = __DIR__."/poll_scripts/".$this->Name."_".$this->MPGID.".php";

		if(is_file($oldscript))
			rename($oldscript, $newscript);

		$sql="UPDATE fac_MeasurePointGroup SET Name=\"$this->Name\" WHERE MPGID=$this->MPGID;";
		$dbh->query($sql);

		$sql = "DELETE FROM fac_AssoMeasurePointGroup WHERE MPGID=$this->MPGID;";
		$dbh->exec($sql);
		
		foreach($this->MPList as $mp) {
			$sql = "INSERT INTO fac_AssoMeasurePointGroup SET
					MPGID=$this->MPGID,
					MPID=$mp;";
			$dbh->exec($sql);
		}
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldmpg):'';
	}
}

class CoolingMeasurePoint extends MeasurePoint{

	/*
	* A cooling measure point measures cooling devices activity. Application will poll it and add the measures to the data base.
	* As there is no attributes to this class now, there is no equivalent in the database.
	*/	

	protected function MakeSafe() {
		parent::MakeSafe();
	}

	protected function MakeDisplay() {
		parent::MakeDisplay();
	}

	static function RowToObject($dbRow) {
		$mp=new CoolingMeasurePoint(parent::RowToObject($dbRow));
	
		$mp->MakeDisplay();

		return $mp;
	}

	protected function CreateMP() {
		global $dbh;

		$this->MakeSafe();

		if(parent::CreateMP()) {	
			return $this->MPID;
		}
		return false;
	}

	protected function DeleteMP() {
		global $dbh;
		
		$this->MakeSafe();

		if(parent::DeleteMP()) {
			$sql="DELETE FROM fac_CoolingMeasure WHERE MPID=$this->MPID;";
			$dbh->exec($sql);
			
			return true; 
		}
		return false;
	}

	protected function UpdateMP(){
		global $dbh;

		$this->MakeSafe();
		
		$oldmp = new MeasurePoint();
		$oldmp->MPID = $this->MPID;
		$oldmp = $oldmp->GetMP();
		
		if(parent::UpdateMP()) {
			$oldClass = MeasurePoint::$TypeTab[$oldmp->Type]."MeasurePoint";
				
			//if old class is not empty
			if(count(get_object_vars(new $oldClass)) > count(get_object_vars(new MeasurePoint()))) {
				$sql="DELETE FROM fac_$oldClass WHERE MPID = $this->MPID;";
				if(!$dbh->exec($sql))
					return false;
			}
			return true;
		}
		return false;
	}

	function GetMPList() {	
		$ret = array();
		foreach(MeasurePoint::$ConnectionTypeTab as $connectionType) {
			$class = $connectionType."CoolingMeasurePoint";
			$mp = new $class;
			if(is_object($mp))
				$ret = array_merge($ret, $mp->GetMPList());
		}	
		return $ret;
	}

	function Poll(){
		global $config;
	
		$config=new Config();

		$m = new CoolingMeasure();
		$m->MPID = $this->MPID;

		$lastMeasure = new CoolingMeasure();
		$lastMeasure->MPID = $this->MPID;
		$lastMeasure->GetLastMeasure();

		switch($this->ConnectionType) {
			case "SNMP":
				if ( $this->SNMPCommunity == "" ) {
					$this->SNMPCommunity = $config->ParameterArray["SNMPCommunity"];
				}

				$values = self::OSS_SNMP_Lookup($this, null, array($this->FanSpeedOID, $this->CoolingOID));
				break;
			case "Modbus":
				$values = self::Modbus_Lookup($this, array($this->FanSpeedRegister, $this->CoolingRegister));
				break;
		}
		if($values[0]!=false || $values[1]!=false) {
			$m->FanSpeed=@intval($values[0]);
			$m->Cooling=@intval($values[1]);

			$m->Date=date("Y-m-d H:i:s");
			$m->CreateMeasure();
		}
	}
}

class SNMPCoolingMeasurePoint extends CoolingMeasurePoint {
	
	// Definition of cooling measure point with a SNMP connection

	var $SNMPCommunity;	//SNMP community
	var $SNMPVersion;	//SNMP version
	var $v3SecurityLevel;
        var $v3AuthProtocol;
        var $v3AuthPassphrase;
        var $v3PrivProtocol;
        var $v3PrivPassphrase;
	var $FanSpeedOID;	//OID of the fan speed percentage.
	var $CoolingOID;	//OID of the cooling percentage.
	
	function MakeSafe() {
		parent::Makesafe();
		$validVersions = array('1','2c','3');
 		$validv3SecurityLevels=array('noAuthNoPriv','authNoPriv','authPriv');
                $validv3AuthProtocols=array('MD5','SHA');
                $validv3PrivProtocols=array('DES','AES');
		$this->SNMPCommunity=sanitize($this->SNMPCommunity);
		$this->SNMPVersion=(in_array($this->SNMPVersion, $validVersions))?$this->SNMPVersion:'1';
		$this->v3SecurityLevel=(in_array($this->v3SecurityLevel, $validv3SecurityLevels))?$this->v3SecurityLevel:'noAuthNoPriv';
                $this->v3AuthProtocol=(in_array($this->v3AuthProtocol, $validv3AuthProtocols))?$this->v3AuthProtocol:'MD5';
                $this->v3AuthPassphrase=sanitize($this->v3AuthPassphrase);
                $this->v3PrivProtocol=(in_array($this->v3PrivProtocol,$validv3PrivProtocols))?$this->v3PrivProtocol:'DES';
                $this->v3PrivPassphrase=sanitize($this->v3PrivPassphrase);
		$this->FanSpeedOID=sanitize($this->FanSpeedOID);
		$this->CoolingOID=sanitize($this->CoolingOID);
	}

	function MakeDisplay() {
		parent::MakeDisplay();
		$this->SNMPCommunity=stripslashes($this->SNMPCommunity);
		$this->FanSpeedOID=stripslashes($this->FanSpeedOID);
		$this->CoolingOID=stripslashes($this->CoolingOID);
	}

	static function RowToObject($dbRow) {
		$mp=new SNMPCoolingMeasurePoint(parent::RowToObject($dbRow));
		$mp->SNMPCommunity=$dbRow["SNMPCommunity"];
		$mp->SNMPVersion=$dbRow["SNMPVersion"];
                $mp->v3SecurityLevel=$dbRow["v3SecurityLevel"];
                $mp->v3AuthProtocol=$dbRow["v3AuthProtocol"];
                $mp->v3AuthPassphrase=$dbRow["v3AuthPassphrase"];
                $mp->v3PrivProtocol=$dbRow["v3PrivProtocol"];
                $mp->v3PrivPassphrase=$dbRow["v3PrivPassphrase"];
		$mp->FanSpeedOID=$dbRow["FanSpeedOID"];
		$mp->CoolingOID=$dbRow["CoolingOID"];

		$mp->MakeDisplay();

		return $mp;
	}

	function CreateMP() {
		global $dbh;

		$this->MakeSafe();

		if(parent::CreateMP()) {

			$sql = "INSERT INTO fac_SNMPCoolingMeasurePoint SET
					MPID=$this->MPID,
					SNMPCommunity=\"$this->SNMPCommunity\",
					SNMPVersion=\"$this->SNMPVersion\",
					v3SecurityLevel=\"$this->v3SecurityLevel\",
					v3AuthProtocol=\"$this->v3AuthProtocol\", 
					v3AuthPassphrase=\"$this->v3AuthPassphrase\", 
                        		v3PrivProtocol=\"$this->v3PrivProtocol\", 
					v3PrivPassphrase=\"$this->v3PrivPassphrase\",
					FanSpeedOID=\"$this->FanSpeedOID\",
					CoolingOID=\"$this->CoolingOID\";";
			
			if(!$dbh->exec($sql))
				return false;
		
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->MPID;
		}
		return false;
	}

	function DeleteMP() {
		global $dbh;
		
		$this->MakeSafe();
		
		if(parent::DeleteMP()) {
			$sql="DELETE FROM fac_SNMPCoolingMeasurePoint WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;

			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
		return false;
	}

	function UpdateMP(){
		global $dbh;
		
		$this->MakeSafe();
		
		$oldmp = new MeasurePoint();
		$oldmp->MPID = $this->MPID;
		$oldmp = $oldmp->GetMP();

		if($oldmp->ConnectionType != $this->ConnectionType || $oldmp->Type != $this->Type) {
			$oldClass = MeasurePoint::$ConnectionTypeTab[$oldmp->ConnectionType].MeasurePoint::$TypeTab[$oldmp->Type]."MeasurePoint";
			$sql="DELETE FROM fac_".$oldClass." WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;

			$sql = "INSERT INTO fac_SNMPCoolingMeasurePoint SET
					MPID=$this->MPID,
					SNMPCommunity=\"$this->SNMPCommunity\",
					SNMPVersion=\"$this->SNMPVersion\",
					v3SecurityLevel=\"$this->v3SecurityLevel\",
					v3AuthProtocol=\"$this->v3AuthProtocol\", 
					v3AuthPassphrase=\"$this->v3AuthPassphrase\", 
                        		v3PrivProtocol=\"$this->v3PrivProtocol\", 
					v3PrivPassphrase=\"$this->v3PrivPassphrase\",
					FanSpeedOID=\"$this->FanSpeedOID\",
					CoolingOID=\"$this->CoolingOID\";";
			if(!$dbh->exec($sql))
				return false;
		} else {
			$sql="UPDATE fac_SNMPCoolingMeasurePoint SET 
				SNMPCommunity=\"$this->SNMPCommunity\", 
				SNMPVersion=\"$this->SNMPVersion\", 
				v3SecurityLevel=\"$this->v3SecurityLevel\",
				v3AuthProtocol=\"$this->v3AuthProtocol\", 
				v3AuthPassphrase=\"$this->v3AuthPassphrase\", 
                        	v3PrivProtocol=\"$this->v3PrivProtocol\", 
				v3PrivPassphrase=\"$this->v3PrivPassphrase\",
				FanSpeedOID=\"$this->FanSpeedOID\", 
				CoolingOID=\"$this->CoolingOID\" 
				WHERE MPID=$this->MPID;";
			if(!$dbh->query($sql))
				return false;
		}

		if(!parent::UpdateMP())
			return false;
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldmp):'';
		return true;
	}

	function GetMP() {
		global $dbh;

		$this->MakeSafe();

		$sql="select * from fac_MeasurePoint NATURAL JOIN fac_SNMPCoolingMeasurePoint where MPID=$this->MPID;";

		if($row=$dbh->query($sql)->fetch()){
			foreach(SNMPCoolingMeasurePoint::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='MPID'){
					$this->$prop=null;
				}
			}
		}
		return $this;
	}

	function GetMPList() {
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_MeasurePoint NATURAL JOIN fac_SNMPCoolingMeasurePoint ORDER BY Label ASC;";

		$MPList=array();
		foreach($dbh->query($sql) as $row){
			$MPList[]=SNMPCoolingMeasurePoint::RowToObject($row);
		}

		return $MPList;
	}
}

class ModbusCoolingMeasurePoint extends CoolingMeasurePoint {
	
	//Defintion of a cooling measure point with a Modbus connection

	var $UnitID;			//ID of the cooling measure point in the bus
	var $NbWords;			//quantity of words to read
	var $FanSPeedRegister;		// register to measure fan speed
	var $CoolingRegister;		// register to measure compressor usage
	
	function MakeSafe() {
		parent::Makesafe();
		$this->UnitID=intval($this->UnitID);
		$this->NbWords=intval($this->NbWords);
		$this->FanSpeedRegister=intval($this->FanSpeedRegister);
		$this->CoolingRegister=intval($this->CoolingRegister);
	}

	function MakeDisplay() {
		parent::MakeDisplay();
	}

	static function RowToObject($dbRow) {
		$mp=new ModbusCoolingMeasurePoint(parent::RowToObject($dbRow));
		$mp->UnitID=$dbRow["UnitID"];
		$mp->NbWords=$dbRow["NbWords"];
		$mp->FanSpeedRegister=$dbRow["FanSpeedRegister"];
		$mp->CoolingRegister=$dbRow["CoolingRegister"];

		$mp->MakeDisplay();

		return $mp;
	}

	function CreateMP() {
		global $dbh;

		$this->MakeSafe();

		if(parent::CreateMP()) {
			$sql = "INSERT INTO fac_ModbusCoolingMeasurePoint SET
					MPID=$this->MPID,
					UnitID=\"$this->UnitID\",
					NbWords=\"$this->NbWords\",
					FanSpeedRegister=\"$this->FanSpeedRegister\",
					CoolingRegister=\"$this->CoolingRegister\";";
			
			if(!$dbh->exec($sql))
				return false;
		
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->MPID;
		}
		return false;
	}

	function DeleteMP() {
		global $dbh;
		
		$this->MakeSafe();
		
		if(parent::DeleteMP()) {
			$sql="DELETE FROM fac_ModbusCoolingMeasurePoint WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;

			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
		return false;
	}

	function UpdateMP(){
		global $dbh;
		
		$this->MakeSafe();

		$oldmp = new MeasurePoint();
		$oldmp->MPID = $this->MPID;
		$oldmp = $oldmp->GetMP();

		if($oldmp->ConnectionType != $this->ConnectionType || $oldmp->Type != $this->Type) {
			$oldClass = MeasurePoint::$ConnectionTypeTab[$oldmp->ConnectionType].MeasurePoint::$TypeTab[$oldmp->Type]."MeasurePoint";
			echo $oldClass." ".$oldmp->ConnectionType.$oldmp->Type;
			$sql="DELETE FROM fac_".$oldClass." WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;
			$sql = "INSERT INTO fac_ModbusCoolingMeasurePoint SET
					MPID=$this->MPID,
					UnitID=\"$this->UnitID\",
					NbWords=\"$this->NbWords\",
					FanSpeedRegister=\"$this->FanSpeedRegister\",
					CoolingRegister=\"$this->CoolingRegister\";";
			if(!$dbh->exec($sql))
				return false;
		} else {
			$sql="UPDATE fac_ModbusCoolingMeasurePoint SET 
				UnitID=\"$this->UnitID\", 
				NbWords=\"$this->NbWords\", 
				FanSpeedRegister=\"$this->FanSpeedRegister\", 
				CoolingRegister=\"$this->CoolingRegister\" 
				WHERE MPID=$this->MPID;";
			if(!$dbh->query($sql))
				return false;
		}
		
		if(parent::UpdateMP())
			return false;
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldmp):'';
		return true;
	}

	function GetMP() {
		global $dbh;

		$this->MakeSafe();

		$sql="select * from fac_MeasurePoint NATURAL JOIN fac_ModbusCoolingMeasurePoint where MPID=$this->MPID;";

		if($row=$dbh->query($sql)->fetch()){
			foreach(ModbusCoolingMeasurePoint::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='MPID'){
					$this->$prop=null;
				}
			}
		}
		return $this;
	}

	function GetMPList() {
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_MeasurePoint NATURAL JOIN fac_ModbusCoolingMeasurePoint ORDER BY Label ASC;";

		$MPList=array();
		foreach($dbh->query($sql) as $row){
			$MPList[]=ModbusCoolingMeasurePoint::RowToObject($row);
		}
		return $MPList;
	}
}

class CoolingMeasure {

	// A Measure contains the values measured by a CoolingMeasurePoint at a given date

	var $MPID;	//ID of the cooling measure point
	var $FanSpeed;	//
	var $Cooling;	//compressor usage
	var $Date;	//date of the measure

	function MakeSafe() {
		$this->MPID=intval($this->MPID);
		$this->FanSpeed=intval($this->FanSpeed);
		$this->Cooling=intval($this->Cooling);
		$this->Date=date("Y-m-d H:i:s",strtotime($this->Date));
	}

	static function RowToObject($dbRow) {
		$m=new CoolingMeasure();
		$m->MPID=$dbRow["MPID"];
		$m->FanSpeed=$dbRow["FanSpeed"];
		$m->Cooling=$dbRow["Cooling"];
		$m->Date=$dbRow["Date"];

		return $m;
	}

	function CreateMeasure() {
		global $dbh;

		$this->MakeSafe();

		$sql = "INSERT INTO fac_CoolingMeasure SET
				MPID=$this->MPID,
				FanSpeed=\"$this->FanSpeed\",
				Cooling=\"$this->Cooling\",
				Date=\"$this->Date\";";
			
		if(!$dbh->exec($sql))
			return false;
		else
			return true;
	}

	function GetLastMeasure() {
		global $dbh;

		$this->MakeSafe();

		$sql = "SELECT * FROM fac_CoolingMeasure WHERE MPID=$this->MPID AND Date=(SELECT MAX(Date) FROM fac_CoolingMeasure WHERE MPID=$this->MPID);";
	
		if($row=$dbh->query($sql)->fetch()){
			foreach(CoolingMeasure::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='MPID'){
					$this->$prop=null;
				}
			}
		}
		return $this;
	}

	function GetMeasuresOnInterval($start = "1970-01-01 00:00:00", $end = "3000-01-01 00:00:00") {
		global $dbh;

		$this->MakeSafe();

		$sql = "SELECT * FROM fac_CoolingMeasure WHERE MPID=$this->MPID AND Date >= \"$start\" AND Date <= \"$end\" ORDER BY Date;";

		$measureList = array();
		foreach($dbh->query($sql) as $row) {
			$measureList[]=CoolingMeasure::RowToObject($row);
		}
		return $measureList;
	}
}

class AirMeasurePoint extends MeasurePoint{

	/*
	* An air measure point measures air temperature and humidity. Application will poll it and add the measures to the data base.
	*As there is no attributes to this class now, there is no equivalent in the database.
	*/
	
	protected function MakeSafe() {
		parent::MakeSafe();
	}

	protected function MakeDisplay() {
		parent::MakeDisplay();
	}

	static function RowToObject($dbRow) {
		$mp=new AirMeasurePoint(parent::RowToObject($dbRow));

		$mp->MakeDisplay();

		return $mp;
	}

	protected function CreateMP() {
		global $dbh;

		$this->MakeSafe();

		if(parent::CreateMP()) {
			return $this->MPID;
		}
		return false;
	}

	protected function DeleteMP() {
		global $dbh;
		
		$this->MakeSafe();

		if(parent::DeleteMP()) {
			$sql="DELETE FROM fac_AirMeasure WHERE MPID=$this->MPID;";
			$dbh->exec($sql);

			return true;
		}
		return false;
	}

	protected function UpdateMP(){
		global $dbh;

		$this->MakeSafe();

		$oldmp = new MeasurePoint();
		$oldmp->MPID = $this->MPID;
		$oldmp = $oldmp->GetMP();

		if(parent::UpdateMP()) {
			$oldClass = MeasurePoint::$TypeTab[$oldmp->Type]."MeasurePoint";
				
			//if old class is not empty
			if(count(get_object_vars(new $oldClass)) > count(get_object_vars(new MeasurePoint()))) {
				$sql="DELETE FROM fac_$oldClass WHERE MPID = $this->MPID;";
				if(!$dbh->exec($sql))
					return false;
			}
			return true;
		}
		return false;
	}

	function GetMPList() {
		$ret = array();
		foreach(MeasurePoint::$ConnectionTypeTab as $connectionType) {
			$class = $connectionType."AirMeasurePoint";
			$mp = new $class;
			if(is_object($mp))
				$ret = array_merge($ret, $mp->GetMPList());
		}	
		return $ret;
	}

	function Poll(){
		global $config;
	
		$config=new Config();

		$m = new AirMeasure();
		$m->MPID = $this->MPID;

		$lastMeasure = new AirMeasure();
		$lastMeasure->MPID = $this->MPID;
		$lastMeasure->GetLastMeasure();

		switch($this->ConnectionType) {
			case "SNMP":
				if ( $this->SNMPCommunity == "" ) {
					$this->SNMPCommunity = $config->ParameterArray["SNMPCommunity"];
				}
				$values = self::OSS_SNMP_Lookup($this, null, array($this->TemperatureOID, $this->HumidityOID));
				break;
			case "Modbus":
				$values = self::Modbus_Lookup($this, array($this->TemperatureRegister, $this->HumidityRegister));
				break;
		}
		if($values[0]!=false || $values[1]!=false) {
			$m->Temperature=@floatval($values[0]);
			$m->Humidity=@floatval($values[1]);

			$m->Date=date("Y-m-d H:i:s");
			$m->CreateMeasure();
		}
	}
}

class SNMPAirMeasurePoint extends AirMeasurePoint {
	
	// Definition of air measure point with a SNMP connection

	var $SNMPCommunity;		//SNMP community
	var $SNMPVersion;		//SNMP version
	var $v3SecurityLevel;
        var $v3AuthProtocol;
        var $v3AuthPassphrase;
        var $v3PrivProtocol;
        var $v3PrivPassphrase;
	var $TemperatureOID;		//OID of the temperature.
	var $HumidityOID;		//OID of the humidity percentage.
	
	function MakeSafe() {
		parent::Makesafe();
		$validVersions = array('1','2c','3');
 		$validv3SecurityLevels=array('noAuthNoPriv','authNoPriv','authPriv');
                $validv3AuthProtocols=array('MD5','SHA');
                $validv3PrivProtocols=array('DES','AES');
		$this->SNMPCommunity=sanitize($this->SNMPCommunity);
		$this->SNMPVersion=(in_array($this->SNMPVersion, $validVersions))?$this->SNMPVersion:'1';
		$this->v3SecurityLevel=(in_array($this->v3SecurityLevel, $validv3SecurityLevels))?$this->v3SecurityLevel:'noAuthNoPriv';
                $this->v3AuthProtocol=(in_array($this->v3AuthProtocol, $validv3AuthProtocols))?$this->v3AuthProtocol:'MD5';
                $this->v3AuthPassphrase=sanitize($this->v3AuthPassphrase);
                $this->v3PrivProtocol=(in_array($this->v3PrivProtocol,$validv3PrivProtocols))?$this->v3PrivProtocol:'DES';
                $this->v3PrivPassphrase=sanitize($this->v3PrivPassphrase);
		$this->TemperatureOID=sanitize($this->TemperatureOID);
		$this->HumidityOID=sanitize($this->HumidityOID);
	}

	function MakeDisplay() {
		parent::MakeDisplay();
		$this->SNMPCommunity=stripslashes($this->SNMPCommunity);
		$this->TemperatureOID=stripslashes($this->TemperatureOID);
		$this->HumidityOID=stripslashes($this->HumidityOID);
	}

	static function RowToObject($dbRow) {
		$mp=new SNMPAirMeasurePoint(parent::RowToObject($dbRow));
		$mp->SNMPCommunity=$dbRow["SNMPCommunity"];
		$mp->SNMPVersion=$dbRow["SNMPVersion"];
                $mp->v3SecurityLevel=$dbRow["v3SecurityLevel"];
                $mp->v3AuthProtocol=$dbRow["v3AuthProtocol"];
                $mp->v3AuthPassphrase=$dbRow["v3AuthPassphrase"];
                $mp->v3PrivProtocol=$dbRow["v3PrivProtocol"];
                $mp->v3PrivPassphrase=$dbRow["v3PrivPassphrase"];
		$mp->TemperatureOID=$dbRow["TemperatureOID"];
		$mp->HumidityOID=$dbRow["HumidityOID"];

		$mp->MakeDisplay();

		return $mp;
	}

	function CreateMP() {
		global $dbh;

		$this->MakeSafe();

		if(parent::CreateMP()) {
			$sql = "INSERT INTO fac_SNMPAirMeasurePoint SET
					MPID=$this->MPID,
					SNMPCommunity=\"$this->SNMPCommunity\",
					SNMPVersion=\"$this->SNMPVersion\",
					v3SecurityLevel=\"$this->v3SecurityLevel\",
					v3AuthProtocol=\"$this->v3AuthProtocol\", 
					v3AuthPassphrase=\"$this->v3AuthPassphrase\", 
                        		v3PrivProtocol=\"$this->v3PrivProtocol\", 
					v3PrivPassphrase=\"$this->v3PrivPassphrase\",
					TemperatureOID=\"$this->TemperatureOID\",
					HumidityOID=\"$this->HumidityOID\";";
			
			if(!$dbh->exec($sql))
				return false;
		
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->MPID;
		}
		return false;
	}

	function DeleteMP() {
		global $dbh;
		
		$this->MakeSafe();
		
		if(parent::DeleteMP()) {
			$sql="DELETE FROM fac_SNMPAirMeasurePoint WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;

			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
		return false;
	}

	function UpdateMP(){
		global $dbh;
		
		$this->MakeSafe();
		
		$oldmp = new MeasurePoint();
		$oldmp->MPID = $this->MPID;
		$oldmp = $oldmp->GetMP();

		if($oldmp->ConnectionType != $this->ConnectionType || $oldmp->Type != $this->Type) {
			$oldClass = MeasurePoint::$ConnectionTypeTab[$oldmp->ConnectionType].MeasurePoint::$TypeTab[$oldmp->Type]."MeasurePoint";
			$sql="DELETE FROM fac_".$oldClass." WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;

			$sql = "INSERT INTO fac_SNMPAirMeasurePoint SET
					MPID=$this->MPID,
					SNMPCommunity=\"$this->SNMPCommunity\",
					SNMPVersion=\"$this->SNMPVersion\",
					v3SecurityLevel=\"$this->v3SecurityLevel\",
					v3AuthProtocol=\"$this->v3AuthProtocol\", 
					v3AuthPassphrase=\"$this->v3AuthPassphrase\", 
                        		v3PrivProtocol=\"$this->v3PrivProtocol\", 
					v3PrivPassphrase=\"$this->v3PrivPassphrase\",
					TemperatureOID=\"$this->TemperatureOID\",
					HumidityOID=\"$this->HumidityOID\";";
			if(!$dbh->exec($sql))
				return false;
		} else {
			$sql="UPDATE fac_SNMPAirMeasurePoint SET 
				SNMPCommunity=\"$this->SNMPCommunity\", 
				SNMPVersion=\"$this->SNMPVersion\",
				v3SecurityLevel=\"$this->v3SecurityLevel\",
				v3AuthProtocol=\"$this->v3AuthProtocol\", 
				v3AuthPassphrase=\"$this->v3AuthPassphrase\", 
                        	v3PrivProtocol=\"$this->v3PrivProtocol\", 
				v3PrivPassphrase=\"$this->v3PrivPassphrase\",
				TemperatureOID=\"$this->TemperatureOID\", 
				HumidityOID=\"$this->HumidityOID\" 
				WHERE MPID=$this->MPID;";
			if(!$dbh->query($sql))
				return false;
		}

		if(parent::UpdateMP())
			return false;
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldmp):'';
		return true;
	}

	function GetMP() {
		global $dbh;

		$this->MakeSafe();

		$sql="select * from fac_MeasurePoint NATURAL JOIN fac_SNMPAirMeasurePoint where MPID=$this->MPID;";

		if($row=$dbh->query($sql)->fetch()){
			foreach(SNMPAirMeasurePoint::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='MPID'){
					$this->$prop=null;
				}
			}
		}
		return $this;
	}

	function GetMPList() {
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_MeasurePoint NATURAL JOIN fac_SNMPAirMeasurePoint ORDER BY Label ASC;";

		$MPList=array();
		foreach($dbh->query($sql) as $row){
			$MPList[]=SNMPAirMeasurePoint::RowToObject($row);
		}
		return $MPList;
	}
}

class ModbusAirMeasurePoint extends AirMeasurePoint {
	
	//Defintion of an air measure point with a Modbus connection

	var $UnitID;			//ID of the air measure point in the bus
	var $NbWords;			//quantity of words to read
	var $TemperatureRegister;	//
	var $HumidityRegister;		//
	
	function MakeSafe() {
		parent::Makesafe();
		$this->UnitID=intval($this->UnitID);
		$this->NbWords=intval($this->NbWords);
		$this->TemperatureRegister=intval($this->TemperatureRegister);
		$this->HumidityRegister=intval($this->HumidityRegister);
	}

	function MakeDisplay() {
		parent::MakeDisplay();
	}

	static function RowToObject($dbRow) {
		$mp=new ModbusCoolingMeasurePoint(parent::RowToObject($dbRow));
		$mp->UnitID=$dbRow["UnitID"];
		$mp->NbWords=$dbRow["NbWords"];
		$mp->TemperatureRegister=$dbRow["TemperatureRegister"];
		$mp->HumidityRegister=$dbRow["HumidityRegister"];

		$mp->MakeDisplay();

		return $mp;
	}

	function CreateMP() {
		global $dbh;

		$this->MakeSafe();

		if(parent::CreateMP()) {
			$sql = "INSERT INTO fac_ModbusAirMeasurePoint SET
					MPID=$this->MPID,
					UnitID=\"$this->UnitID\",
					NbWords=\"$this->NbWords\",
					TemperatureRegister=\"$this->TemperatureRegister\",
					HumidityRegister=\"$this->HumidityRegister\";";
			
			if(!$dbh->exec($sql))
				return false;
		
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->MPID;
		}
		return false;
	}

	function DeleteMP() {
		global $dbh;
		
		$this->MakeSafe();
		
		if(parent::DeleteMP()) {
			$sql="DELETE FROM fac_ModbusAirMeasurePoint WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;

			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
		return false;
	}

	function UpdateMP(){
		global $dbh;
		
		$this->MakeSafe();

		$oldmp = new MeasurePoint();
		$oldmp->MPID = $this->MPID;
		$oldmp = $oldmp->GetMP();

		if($oldmp->ConnectionType != $this->ConnectionType || $oldmp->Type != $this->Type) {
			$oldClass = MeasurePoint::$ConnectionTypeTab[$oldmp->ConnectionType].MeasurePoint::$TypeTab[$oldmp->Type]."MeasurePoint";
			$sql="DELETE FROM fac_".$oldClass." WHERE MPID=$this->MPID;";
			if(!$dbh->exec($sql))
				return false;

			$sql = "INSERT INTO fac_ModbusAirMeasurePoint SET
					MPID=$this->MPID,
					UnitID=\"$this->UnitID\",
					NbWords=\"$this->NbWords\",
					TemperatureRegister=\"$this->TemperatureRegister\",
					HumidityRegister=\"$this->HumidityRegister\";";
			if(!$dbh->exec($sql))
				return false;
		} else {
			$sql="UPDATE fac_ModbusAirMeasurePoint SET 
				UnitID=\"$this->UnitID\", 
				NbWords=\"$this->NbWords\", 
				TemperatureRegister=\"$this->TemperatureRegister\", 
				HumidityRegister=\"$this->HumidityRegister\" 
				WHERE MPID=$this->MPID;";
			if(!$dbh->query($sql))
				return false;
		}

		if(parent::UpdateMP())
			return false;
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldmp):'';
		return true;
	}

	function GetMP() {
		global $dbh;

		$this->MakeSafe();

		$sql="select * from fac_MeasurePoint NATURAL JOIN fac_ModbusAirMeasurePoint where MPID=$this->MPID;";

		if($row=$dbh->query($sql)->fetch()){
			foreach(ModbusAirMeasurePoint::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='MPID'){
					$this->$prop=null;
				}
			}
		}
	
		return $this;
	}

	function GetMPList() {
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_MeasurePoint NATURAL JOIN fac_ModbusAirMeasurePoint ORDER BY Label ASC;";

		$MPList=array();
		foreach($dbh->query($sql) as $row){
			$MPList[]=ModbusAirMeasurePoint::RowToObject($row);
		}

		return $MPList;
	}
}

class AirMeasure {

	// An Air Measure contains the values measured by an AirMeasurePoint at a given date

	var $MPID;		//ID of the air measure point
	var $Temperature;	//
	var $Humidity;		//
	var $Date;		//date of the measure

	function MakeSafe() {
		$this->MPID=intval($this->MPID);
		$this->Temperature=floatval($this->Temperature);
		$this->Humidity=floatval($this->Humidity);
		$this->Date=date("Y-m-d H:i:s",strtotime($this->Date));
	}

	static function RowToObject($dbRow) {
		$m=new CoolingMeasure();
		$m->MPID=$dbRow["MPID"];
		$m->Temperature=$dbRow["Temperature"];
		$m->Humidity=$dbRow["Humidity"];
		$m->Date=$dbRow["Date"];

		return $m;
	}

	function CreateMeasure() {
		global $dbh;

		$this->MakeSafe();

		$sql = "INSERT INTO fac_AirMeasure SET
				MPID=$this->MPID,
				Temperature=\"$this->Temperature\",
				Humidity=\"$this->Humidity\",
				Date=\"$this->Date\";";
			
		if(!$dbh->exec($sql))
			return false;
		else
			return true;
	}

	function GetLastMeasure() {
		global $dbh;

		$this->MakeSafe();

		$sql = "SELECT * FROM fac_AirMeasure WHERE MPID=$this->MPID AND Date=(SELECT MAX(Date) FROM fac_AirMeasure WHERE MPID=$this->MPID);";
	
		if($row=$dbh->query($sql)->fetch()){
			foreach(AirMeasure::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			foreach($this as $prop => $value){
				if($prop!='MPID'){
					$this->$prop=null;
				}
			}
		}
		return $this;
	}

	function GetMeasuresOnInterval($start = "1970-01-01 00:00:00", $end = "3000-01-01 00:00:00") {
		global $dbh;

		$this->MakeSafe();

		$sql = "SELECT * FROM fac_AirMeasure WHERE MPID=$this->MPID AND Date >= \"$start\" AND Date <= \"$end\" ORDER BY Date;";

		$measureList = array();
		foreach($dbh->query($sql) as $row) {
			$measureList[]=AirMeasure::RowToObject($row);
		}
		return $measureList;
	}
}

function SNMPMeasure($IPAddress, $Community, $SNMPVersion, $OIDTab) {

	/*
	 *SNMPMeasure: Read information through SNMP connection
	 *	-IPAddress: IP address of the device
	 *	-Community: Community of the device
	 *	-SNMPVersion: SNMP version to use
	 *	-OID: OID to read (refere to the MIB of the device)
	 */

	global $config;
	if($IPAddress != '') {
		switch($SNMPVersion) {
			case '1':
				foreach( $OIDTab as $OID) {
					if($OID != '') {
						$valTab[] = eregi_replace(".*: |\"", "", snmpget($IPAddress, $Community, $OID));
					} else {
						$valTab[] = '';
					}
				}
				break;
			case '2c':
				foreach( $OIDTab as $OID) {
					if($OID != '')
						$valTab[] = eregi_replace(".*: |\"", "", snmp2_get($IPAddress, $Community, $OID));
					else
						$valTab[] = '';
				}
				break;
			default:
				foreach( $OIDTab as $OID) {
					if($OID != '')
						$valTab[] = eregi_replace(".*: |\"", "", snmpget($IPAddress, $Community, $OID));
					else
						$valTab[] = '';
				}
				break;
		}
	        return $valTab;
	} else {
		return array();
	}
}
?>
