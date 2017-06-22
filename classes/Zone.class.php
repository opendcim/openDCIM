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
class Zone {
	var $ZoneID;
	var $DataCenterID;
	var $Description;
	var $MapX1;
	var $MapY1;
	var $MapX2;
	var $MapY2;
	var $MapZoom;  // % of Zoom (100=>no zoom)
	function MakeSafe(){
		$this->ZoneID=intval($this->ZoneID);
		$this->DataCenterID=intval($this->DataCenterID);
		$this->Description=sanitize($this->Description);
		// ensure all coordinates are positive values
		$this->MapX1=abs($this->MapX1);
		$this->MapY1=abs($this->MapY1);
		$this->MapX2=abs($this->MapX2);
		$this->MapY2=abs($this->MapY2);
		$this->MapZoom=abs($this->MapZoom);
	}

	function MakeDisplay(){
		$this->Description=stripslashes($this->Description);
	}

	static function RowToObject($row){
		$zone=New Zone();
		$zone->ZoneID=$row["ZoneID"];
		$zone->DataCenterID=$row["DataCenterID"];
		$zone->Description=$row["Description"];
		$zone->MapX1=$row["MapX1"];
		$zone->MapY1=$row["MapY1"];
		$zone->MapX2=$row["MapX2"];
		$zone->MapY2=$row["MapY2"];
		$zone->MapZoom=$row["MapZoom"];
		$zone->MakeDisplay();

		return $zone;
	}
 
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function CreateZone(){
		global $dbh;
			
		$this->MakeSafe();
			
		$sql="INSERT INTO fac_Zone SET Description=\"$this->Description\", 
			DataCenterID=$this->DataCenterID,
			MapX1=$this->MapX1,
			MapY1=$this->MapY1,
			MapX2=$this->MapX2,
			MapY2=$this->MapY2,
			MapZoom=$this->MapZoom
			;";
		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			$this->ZoneID=$dbh->lastInsertID();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->ZoneID;
		}
	}
	
	function UpdateZone(){
		$this->MakeSafe();

		$oldzone=new Zone();
		$oldzone->ZoneID=$this->ZoneID;
		$oldzone->GetZone();
			
		//update all cabinets in this zone
		$sql="UPDATE fac_Cabinet SET DataCenterID=$this->DataCenterID WHERE 
			ZoneID=$this->ZoneID;";
		if(!$this->query($sql)){
			return false;
		}
	
		//update zone	
		$sql="UPDATE fac_Zone SET Description=\"$this->Description\", 
			DataCenterID=$this->DataCenterID,
			MapX1=$this->MapX1,
			MapY1=$this->MapY1,
			MapX2=$this->MapX2,
			MapY2=$this->MapY2, 
			MapZoom=$this->MapZoom 
			WHERE ZoneID=$this->ZoneID;";
		if(!$this->query($sql)){
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this,$oldzone):'';
		return true;
	}
	
	function DeleteZone(){
		global $dbh;
		
		$this->MakeSafe();
		
		//update all cabinets in this zone
		$cabinet=new Cabinet();
		$cabinet->ZoneID=$this->ZoneID;
		$cabinetList=$cabinet->GetCabinetsByZone();
		foreach($cabinetList as $cab){
			$cab->CabRowID=0;
			$cab->ZoneID=0;
			$cab->UpdateCabinet();
		}

		//delete CabRows in this zone
		$cabrow=new CabRow();
		$cabrow->ZoneID=$this->ZoneID;
		$cabrowlist=$cabrow->GetCabRowsByZones();
		foreach($cabrowlist as $cabRow){
			$cabRow->DeleteCabRow();
		}

		//delete zone
		$sql="DELETE FROM fac_Zone WHERE ZoneID=$this->ZoneID;";
		if(!$this->query($sql)){
			return false;
		}
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	// return array of immediate children
	function GetChildren(){
		$children=array();
		$cabrow=new CabRow();
		$cabrow->ZoneID=$this->ZoneID;
		foreach($cabrow->GetCabRowsByZones() as $row){
			$children[]=$row;
		}
		// While not currently supported this will allow us to nest cabinets into zones directly without a cabinet row
		$cab=new Cabinet();
		$cab->ZoneID=$this->ZoneID;
		foreach($cab->GetCabinetsByZone() as $cab){
			if($cab->CabRowID==0){
				$children[]=$cab;
			}
		}

		return $children;
	}
 
	function GetZone(){
		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Zone WHERE ZoneID=$this->ZoneID;";
		if($row=$this->query($sql)->fetch()){
			foreach(Zone::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}
  
	function GetZonesByDC($limit=false){
		$this->MakeSafe();
		
		$hascoords=($limit)?'AND MapX1!=MapX2 AND MapY1!=MapY2':'';

		$sql="SELECT * FROM fac_Zone WHERE DataCenterID=$this->DataCenterID $hascoords 
			ORDER BY Description;";
		
		$zoneList=array();
		foreach($this->query($sql) as $row){
			$zoneList[]=Zone::RowToObject($row);
		}
		
		return $zoneList;
	}

	static function GetZoneList($indexedbyid=false){
		global $dbh;

		$sql="SELECT * FROM fac_Zone ORDER BY DataCenterID ASC, Description ASC;";

		$zoneList=array();
		foreach($dbh->query($sql) as $row){
			if($indexedbyid){
				$zoneList[$row['ZoneID']]=Zone::RowToObject($row);
			}else{
				$zoneList[]=Zone::RowToObject($row);
			}
		}
		
		return $zoneList;
	}
	
	function GetZoneStatistics(){
		$this->GetZone();

		$sql="SELECT SUM(CabinetHeight) as TotalU FROM fac_Cabinet WHERE
			ZoneID=$this->ZoneID;";
		$zoneStats["TotalU"]=($test=$this->query($sql)->fetchColumn())?$test:0;

		$sql="SELECT SUM(a.Height) as TotalU FROM fac_Device a,fac_Cabinet b WHERE
			a.Cabinet=b.CabinetID AND b.ZoneID=$this->ZoneID AND ParentDevice=0 AND
			a.DeviceType NOT IN ('Server','Storage Array');";
		$zoneStats["Infrastructure"]=($test=$this->query($sql)->fetchColumn())?$test:0;

		$sql="SELECT SUM(a.Height) as TotalU FROM fac_Device a,fac_Cabinet b WHERE
			a.Cabinet=b.CabinetID AND b.ZoneID=$this->ZoneID AND ParentDevice=0 AND
			a.Status!='Reserved' AND a.DeviceType IN ('Server', 'Storage Array');";
		$zoneStats["Occupied"]=($test=$this->query($sql)->fetchColumn())?$test:0;

        $sql="SELECT SUM(a.Height) FROM fac_Device a,fac_Cabinet b WHERE
			a.Cabinet=b.CabinetID AND a.Status!='Reserved' AND ParentDevice=0 AND
			b.ZoneID=$this->ZoneID;";
		$zoneStats["Allocated"]=($test=$this->query($sql)->fetchColumn())?$test:0;
		
        $zoneStats["Available"]=$zoneStats["TotalU"] - $zoneStats["Occupied"] - $zoneStats["Infrastructure"] - $zoneStats["Allocated"];

		// Perform two queries - one is for the wattage overrides (where NominalWatts > 0) and one for the template (default) values
		$sql="SELECT SUM(NominalWatts) as TotalWatts FROM fac_Device a,fac_Cabinet b WHERE 
			a.Cabinet=b.CabinetID AND a.NominalWatts>0 AND 
			b.ZoneID=$this->ZoneID;";
		$zoneStats["ComputedWatts"]=($test=$this->query($sql)->fetchColumn())?$test:0;
		
		$sql="SELECT SUM(c.Wattage) as TotalWatts FROM fac_Device a, fac_Cabinet b, 
			fac_DeviceTemplate c WHERE a.Cabinet=b.CabinetID AND 
			a.TemplateID=c.TemplateID AND a.NominalWatts=0 AND 
			b.ZoneID=$this->ZoneID;";
		$zoneStats["ComputedWatts"]+=($test=$this->query($sql)->fetchColumn())?$test:0;
		
		$sql="SELECT SUM(Wattage) AS Wattage FROM fac_PDUStats WHERE PDUID IN 
			(SELECT PDUID FROM fac_PowerDistribution WHERE CabinetID IN 
			(SELECT CabinetID FROM fac_Cabinet WHERE ZoneID=$this->ZoneID))";
		$zoneStats["MeasuredWatts"]=($test=$this->query($sql)->fetchColumn())?$test:0;
		
		$sql="SELECT AVG(NULLIF(Temperature, 0)) AS AvgTemp FROM fac_SensorReadings a, 
			fac_Device b, fac_Cabinet c WHERE a.DeviceID=b.DeviceID AND b.BackSide=0 and
			b.Cabinet=c.CabinetID AND a.DeviceID IN (SELECT b.DeviceID FROM fac_Device 
			WHERE ZoneID=$this->ZoneID);";
		$zoneStats["AvgTemp"]=($test=round($this->query($sql)->fetchColumn()))?$test:0;

		$sql="SELECT AVG(NULLIF(Humidity, 0)) AS AvgHumdity FROM fac_SensorReadings a, 
			fac_Device b, fac_Cabinet c WHERE a.DeviceID=b.DeviceID AND b.BackSide=0 and
			b.Cabinet=c.CabinetID AND a.DeviceID IN (SELECT b.DeviceID FROM fac_Device 
			WHERE ZoneID=$this->ZoneID);";
		$zoneStats["AvgHumidity"]=($test=round($this->query($sql)->fetchColumn()))?$test:0;

		$sql = "select count(*) from fac_Cabinet where ZoneID=" . intval($this->ZoneID);
		$zoneStats["TotalCabinets"]=($test=$this->query($sql)->fetchColumn())?$test:0;
		
		return $zoneStats;
	}

	function Search($indexedbyid=false,$loose=false){
		$o=new stdClass();
		// Store any values that have been added before we make them safe 
		foreach($this as $prop => $val){
			if(isset($val)){
				$o->$prop=$val;
			}
		}

		// Make everything safe for us to search with
		$this->MakeSafe();

		// This will store all our extended sql
		$sqlextend="";
		foreach($o as $prop => $val){
			extendsql($prop,$this->$prop,$sqlextend,$loose);
		}

		$sql="SELECT * FROM fac_Zone $sqlextend ORDER BY Description ASC;";

		$zoneList=array();
		foreach($this->query($sql) as $zoneRow){
			if($indexedbyid){
				$zoneList[$zoneRow["ZoneID"]]=Zone::RowToObject($zoneRow);
			}else{
				$zoneList[]=Zone::RowToObject($zoneRow);
			}
		}

		return $zoneList;
	}

	// Make a simple reference to a loose search
	function LooseSearch($indexedbyid=false){
		return $this->Search($indexedbyid,true);
	}
}
?>
