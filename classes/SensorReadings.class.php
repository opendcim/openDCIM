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
		$m->SensorID=$row["DeviceID"] ?? null;
		$m->Temperature=$row["Temperature"] ?? null;
		$m->Humidity= $row["Humidity"] ?? null;
		$m->LastRead=$row["LastRead"] ?? null;
		return $m;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function GetSensorReadingsByID(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_SensorReadings WHERE DeviceID=$this->SensorID;";

		$stmt=$this->query($sql);
		if($stmt && ($row=$stmt->fetch())){
			foreach(get_object_vars(SensorReadings::RowToObject($row)) as $prop => $value){
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

		$lastRead=strtotime((string)$this->LastRead);
		$lastRead=($lastRead === false) ? 0 : $lastRead;
		$lastReadSQL=date("Y-m-d H:i:s", $lastRead);
		$sql="INSERT INTO fac_SensorReadings (DeviceID,Temperature,Humidity,LastRead) VALUES 
			($this->SensorID,$this->Temperature,$this->Humidity,\"$lastReadSQL\") ON DUPLICATE KEY 
			UPDATE Temperature=$this->Temperature,Humidity=$this->Humidity,LastRead=\"$lastReadSQL\";";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("UpdateSensorReadings::PDO Error: " . ($info[2] ?? 'Unknown error') . " SQL=$sql" );
			return false;
		}
		return true;
	}

	function DeleteSensorReadings(){
		global $dbh;

		$this->MakeSafe();

		$sql="DELETE from fac_SensorReadings where DeviceID=$this->SensorID;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("DeleteSensorReadings::PDO Error: " . ($info[2] ?? 'Unknown error') . " SQL=$sql" );
			return false;
		}
		return true;
	}

	function Search($indexedbyid=false,$loose=false){
		$this->MakeSafe();

		$sqlextend="";
		foreach(get_object_vars($this) as $prop => $val){
			if($val){
				extendsql($prop,$val,$sqlextend,$loose);
			}
		}

		$sql="SELECT * FROM fac_SensorReadings $sqlextend;";

		$sensorreadingsList=array();
		$stmt=$this->query($sql);
		if($stmt){
			foreach($stmt as $sensorreadingsRow){
				if($indexedbyid){
					$sensorreadingsList[$sensorreadingsRow["DeviceID"] ?? null]=SensorReadings::RowToObject($sensorreadingsRow);
				}else{
					$sensorreadingsList[]=SensorReadings::RowToObject($sensorreadingsRow);
				}
			}
		}

		return $sensorreadingsList;
	}

}
?>
