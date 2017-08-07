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
		$this->PanelPole=sanitize($this->PanelPole);
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

	static function calculateEstimatedLoad( $devID ) {
		global $dbh;

		$sql = "select sum(NominalWatts) as TotalWatts from fac_Device where DeviceID in (select ConnectedDeviceID from fac_PowerPorts where DeviceID=" . intval($devID) . ")";

		if ( $row = $dbh->query( $sql, PDO::FETCH_ASSOC )->fetch() ) {
			return $row["TotalWatts"];
		} else {
			return 0;
		}
	}

	function CreatePDU($pduid=null){
		global $dbh;

		$this->MakeSafe();

		$sqladdon=(!is_null($pduid))?", PDUID=".intval($pduid):"";

		$sql="INSERT INTO fac_PowerDistribution SET Label=\"$this->Label\", 
			CabinetID=$this->CabinetID, TemplateID=$this->TemplateID, 
			IPAddress=\"$this->IPAddress\", SNMPCommunity=\"$this->SNMPCommunity\", 
			PanelID=$this->PanelID, BreakerSize=$this->BreakerSize, 
			PanelPole=\"$this->PanelPole\", InputAmperage=$this->InputAmperage, 
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
			PanelPole=\"$this->PanelPole\", InputAmperage=$this->InputAmperage, 
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

			$threeOIDs = array("Combine3OIDAmperes","Convert3PhAmperes","Combine3OIDWatts");
			if((in_array($row["ProcessingProfile"], $threeOIDs) && ($pollValue1 || $pollValue2 || $pollValue3)) || $pollValue1){
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
			// Make the float safe for insert into mysql
			$watts=float_sqlsafe($watts);

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
}
?>
