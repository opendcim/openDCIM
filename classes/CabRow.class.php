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
class CabRow {
	var $CabRowID;
	var $Name;
	var $DataCenterID;
	var $ZoneID;

	function MakeSafe() {
		$this->CabRowID=intval($this->CabRowID);
		$this->Name=sanitize($this->Name);
		$this->DataCenterID=intval($this->DataCenterID);
		$this->ZoneID=intval($this->ZoneID);
	}

	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
	}

	static function RowToObject($row){
		$cabrow=new CabRow();
		$cabrow->CabRowID=$row["CabRowID"];
		$cabrow->Name=$row["Name"];
		$cabrow->DataCenterID=$row["DataCenterID"];
		$cabrow->ZoneID=$row["ZoneID"];
		$cabrow->MakeDisplay();

		return $cabrow;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function CreateCabRow(){
		global $dbh;
		$this->MakeSafe();

		$sql="INSERT INTO fac_CabRow SET Name=\"$this->Name\", 
			DataCenterID=$this->DataCenterID, ZoneID=$this->ZoneID;";
		if($dbh->exec($sql)){
			$this->CabRowID=$dbh->lastInsertId();

			updateNavTreeHTML();
				
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->CabRowID;
		}else{
			return false;
		}
	}
	
	function UpdateCabRow(){
		$this->MakeSafe();

		$oldcabrow=new CabRow();
		$oldcabrow->CabRowID=$this->CabRowID;
		$oldcabrow->GetCabRow();

		// TODO this here can lead to untracked changes on the cabinets. fix this to use the update method
		//update all cabinets in this cabrow
		$sql="UPDATE fac_Cabinet SET ZoneID=$this->ZoneID, DataCenterID=$this->DataCenterID WHERE CabRowID=$this->CabRowID;";
		if(!$this->query($sql)){
			return false;
		}
		
		$sql="UPDATE fac_CabRow SET Name=\"$this->Name\", DataCenterID=$this->DataCenterID, ZoneID=$this->ZoneID WHERE CabRowID=$this->CabRowID;";
		if(!$this->query($sql)){
			return false;
		}
		
		updateNavTreeHTML();
				
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldcabrow):'';
		return true;
	}
	
	function DeleteCabRow(){
		global $dbh;
		
		$this->MakeSafe();

		//update cabinets in this row
		$cabinet=new Cabinet();
		$cabinet->CabRowID=$this->CabRowID;
		$cabinetList=$cabinet->GetCabinetsByRow();
		foreach($cabinetList as $cab){
			$cab->CabRowID=0;
			$cab->UpdateCabinet();
		}

		//delete cabrow
		$sql="DELETE FROM fac_CabRow WHERE CabRowID=$this->CabRowID;";
		if(!$this->query($sql)){
			return false;
		}

		updateNavTreeHTML();
				
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	function GetCabRow(){
		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_CabRow WHERE CabRowID=$this->CabRowID;";

		if($row=$this->query($sql)->fetch()){
			foreach(CabRow::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}

	function GetCabRowsByZones(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_CabRow WHERE ZoneID=$this->ZoneID ORDER BY Name;";

		$cabrowList=array();
		foreach($this->query($sql) as $row){
			$cabrowList[]=CabRow::RowToObject($row);
		}

		return $cabrowList;
	}

	function GetCabRowsByDC($nozone=false){
		$this->MakeSafe();

		// If true return only rows that don't have a zone set, aka they're just part of the dc
		$sqladdon=($nozone)?"ZoneID=0":"ZoneID>0";

		$sql="SELECT * FROM fac_CabRow WHERE DataCenterID=$this->DataCenterID AND $sqladdon ORDER BY Name;";

		$cabrowList=array();
		foreach($this->query($sql) as $row){
			$cabrowList[]=CabRow::RowToObject($row);
		}

		return $cabrowList;
	}
	function GetCabRowList(){
		$sql="SELECT * FROM fac_CabRow ORDER BY Name ASC;";
		
		$cabrowList=array();
		foreach($this->query($sql) as $row){
			$cabrowList[]=CabRow::RowToObject($row);
		}
		
		return $cabrowList;
	}

	function GetCabRowFrontEdge($layout=""){
		//It returns the FrontEdge of most cabinets
		$this->MakeSafe();

		// If we know for sure a row is horizontal or vertical this will further limit
		// the results to valid faces only
		if($layout){
			if($layout=="Horizontal"){
				// top / bottom
				$layout=" AND (FrontEdge='Bottom' OR FrontEdge='Top')";
			}else{
				// right / left
				$layout=" AND (FrontEdge='Right' OR FrontEdge='Left')";
			}
		}

		$sql="SELECT FrontEdge, count(*) as CabCount FROM fac_Cabinet WHERE 
			CabRowID=$this->CabRowID$layout GROUP BY FrontEdge ORDER BY CabCount DESC 
			LIMIT 1;";

		if($cabinetRow=$this->query($sql)->fetch()){
			return $cabinetRow["FrontEdge"];
		}

		return "";
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

		// The join is purely to sort the templates by the manufacturer's name
		$sql="SELECT * FROM fac_CabRow $sqlextend ORDER BY Name ASC;";

		$rowList=array();

		foreach($this->query($sql) as $row){
			if($indexedbyid){
				$rowList[$row["CabRowID"]]=CabRow::RowToObject($row);
			}else{
				$rowList[]=CabRow::RowToObject($row);
			}
		}

		return $rowList;
	}

	// Make a simple reference to a loose search
	function LooseSearch($indexedbyid=false){
		return $this->Search($indexedbyid,true);
	}
}
?>