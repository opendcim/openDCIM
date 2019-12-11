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
class SensorReadings {
	var $SensorID;
	var $Temperature;
	var $Humidity;
	var $LastRead;

	function MakeSafe(){
		$this->SensorID=intval($this->SensorID);
		$this->Temperature=floatval($this->Temperature);
		$this->Humidity=floatval($this->Humidity);
		$this->LastRead=sanitize($this->LastRead);
	}

	//function MakeDisplay(){
	//}

	static function RowToObject($row){
		$m=new SensorReadings();
		$m->SensorID=$row["DeviceID"];
		$m->Temperature=$row["Temperature"];
		$m->Humidity= $row["Humidity"];
		$m->LastRead=$row["LastRead"];
		return $m;
	}

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
	
	function GetSensorReadingsByID(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Device a, fac_SensorReadings b WHERE a.DeviceID=$this->SensorID AND a.DeviceType='Sensor' AND a.DeviceID=b.DeviceID;";

		if($row=$this->query($sql)->fetch()){
			foreach(SensorReadings::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}	
			return true;
		}else{
			return false;
		}
	}

	function UpdateSensorReadings() {
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INTO fac_SensorReadings (DeviceID,Temperature,Humidity,LastRead) VALUES 
			($this->SensorID,$this->Temperature,$this->Humidity,\"".date("Y-m-d H:i:s", strtotime($this->LastRead))."\") ON DUPLICATE KEY 
			UPDATE Temperature=$this->Temperature,Humidity=$this->Humidity,LastRead=\"".date("Y-m-d H:i:s", strtotime($this->LastRead))."\";";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("UpdateSensorReadings::PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}
		return true;
	}

		function Search($indexedbyid=false){
		// Make everything safe for us to search with
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Device a, fac_SensorReadings b WHERE a.DeviceType='Sensor' AND a.DeviceID=b.DeviceID;";

		$sensorreadingsList=array();
		foreach($this->query($sql) as $sensorreadingsRow){
			if($indexedbyid){
				$sensorreadingsList[$sensorreadingsRow["DeviceID"]]=SensorReadings::RowToObject($sensorreadingsRow);
			}else{
				$sensorreadingsList[]=SensorReadings::RowToObject($sensorreadingsRow);
			}
		}

		return $sensorreadingsList;
		}

}
?>
