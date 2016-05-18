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

class SensorTemplate {
	/* Sensor Template - Information about how to get temperature/humidity from various types of devices */
	
	var $TemplateID;
	var $ManufacturerID;
	var $Model;
	var $TemperatureOID;
	var $HumidityOID;
	var $TempMultiplier;
	var $HumidityMultiplier;
	var $mUnits;
	
	function __construct() {
		$this->Model = "";
		$this->TemperatureOID = "";
		$this->HumidityOID = "";
		$this->TempMultiplier = "";
		$this->HumidityMultiplier = "";
		$this->mUnits = "";
	}

	function MakeSafe(){
		$validMultipliers=array(0.01,0.1,1,10,100);
		$validmUnits=array('english','metric');

		$this->TemplateID=intval($this->TemplateID);
		$this->ManufacturerID=intval($this->ManufacturerID);
		$this->Model=sanitize($this->Model);
		$this->TemperatureOID=sanitize($this->TemperatureOID);
		$this->HumidityOID=sanitize($this->HumidityOID);
		$this->TempMultiplier=(in_array($this->TempMultiplier, $validMultipliers))?$this->TempMultiplier:1;
		$this->HumidityMultiplier=(in_array($this->HumidityMultiplier, $validMultipliers))?$this->HumidityMultiplier:1;
		$this->mUnits=(in_array($this->mUnits, $validmUnits))?$this->mUnits:'english';
	}

	function MakeDisplay() {
		$this->TemperatureOID=stripslashes($this->TemperatureOID);
		$this->HumidityOID=stripslashes($this->HumidityOID);
	}

	static function RowToObject($dbRow){
		$st=new SensorTemplate();
		$st->TemplateID=$dbRow["TemplateID"];
		$st->ManufacturerID=$dbRow["ManufacturerID"];
		$st->Model=$dbRow["Model"];
		$st->TemperatureOID=$dbRow["TemperatureOID"];
		$st->HumidityOID=$dbRow["HumidityOID"];
		$st->TempMultiplier=$dbRow["TempMultiplier"];
		$st->HumidityMultiplier=$dbRow["HumidityMultiplier"];
		$st->mUnits=$dbRow["mUnits"];

		return $st;
	}

	function GetTemplate(){
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_SensorTemplate WHERE TemplateID=$this->TemplateID;";

		if($sensorRow=$dbh->query($sql)->fetch()){
			foreach(SensorTemplate::RowToObject($sensorRow) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}

	static function getTemplates(){
		global $dbh;
		
		$sql="SELECT * FROM fac_SensorTemplate ORDER BY ManufacturerID, Model ASC;";
		
		$tempList = array();
		foreach($dbh->query($sql) as $row){
			$tempList[]=SensorTemplate::RowToObject($row);
		}
		
		return $tempList;
	}
	
	function CreateTemplate($templateid){
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INTO fac_SensorTemplate SET ManufacturerID=$this->ManufacturerID, 
			Model=\"$this->Model\", TemperatureOID=\"$this->TemperatureOID\", 
			HumidityOID=\"$this->HumidityOID\", TempMultiplier=$this->TempMultiplier, 
			HumidityMultiplier=$this->HumidityMultiplier, mUnits=\"$this->mUnits\",
			TemplateID=".intval($templateid);

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("CreateTemplate::PDO Error: {$info[2]} $sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->TemplateID;
	}
	
	function UpdateTemplate() {
		global $dbh;
		
		$this->MakeSafe();	

		$old=new SensorTemplate();
		$old->TemplateID=$this->TemplateID;
		$old->GetTemplate();

		$sql="UPDATE fac_SensorTemplate SET ManufacturerID=$this->ManufacturerID, 
			Model=\"$this->Model\", TemperatureOID=\"$this->TemperatureOID\", 
			HumidityOID=\"$this->HumidityOID\", TempMultiplier=$this->TempMultiplier, 
			HumidityMultiplier=$this->HumidityMultiplier, mUnits=\"$this->mUnits\"
			WHERE TemplateID=$this->TemplateID;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("UpdateTemplate::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}
		(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';
		return true;
	}
	
	function DeleteTemplate() {
		global $dbh;
		
		// Set any sensors using this template back to the default "no template" value
		$sql = "update fac_Cabinet set SensorTemplateID=0 where SensorTemplateID=" . intval( $this->TemplateID );
		$dbh->exec( $sql );
		
		// Now it is "safe" to delete the record as it will leave no orphans
		$sql = "delete from fac_SensorTemplate where TemplateID=" . intval( $this->TemplateID );
		$dbh->exec( $sql );

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
	}
}
?>
