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
class PDUStats {
	var $PDUID;
	var $Wattage;
	var $LastRead;

	function MakeSafe(){
		$this->PDUID=intval($this->PDUID);
		$this->Wattage=intval($this->Wattage);
		$this->LastRead=sanitize($this->LastRead);
	}

	//function MakeDisplay(){
	//}

	static function RowToObject($row){
		$m=new PDUStats();
		$m->PDUID=$row["PDUID"];
		$m->Wattage=$row["Wattage"];
		$m->LastRead=$row["LastRead"];
		return $m;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function GetPDUStatsByID(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PDUStats WHERE PDUID=$this->PDUID;";

		if($row=$this->query($sql)->fetch()){
			foreach(PDUStats::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}	
			return true;
		}else{
			return false;
		}
	}

	function UpdatePDUStats() {
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INTO fac_PDUStats (PDUID,Wattage,LastRead) VALUES 
			($this->PDUID,$this->Wattage,\"".date("Y-m-d H:i:s", strtotime($this->LastRead))."\") ON DUPLICATE KEY 
			UPDATE Wattage=$this->Wattage,LastRead=\"".date("Y-m-d H:i:s", strtotime($this->LastRead))."\";";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("UpdatePDUStats::PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}
		return true;
	}

	function DeletePDUStats() {
		global $dbh;

		$this->MakeSafe();

		$sql="DELETE from fac_PDUStats WHERE PDUID=$this->PDUID;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			
			error_log("DeletePDUStats::PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}
		return true;
	}

	function Search($indexedbyid=false,$loose=false){
		$this->MakeSafe();

		$sqlextend="";
		foreach($this as $prop => $val){
			if($val){
				extendsql($prop,$val,$sqlextend,$loose);
			}
		}

		$sql="SELECT * FROM fac_PDUStats $sqlextend;";

		$pdustatsList=array();
		foreach($this->query($sql) as $pdustatsRow){
			if($indexedbyid){
				$pdustatsList[$pdustatsRow["PDUID"]]=PDUStats::RowToObject($pdustatsRow);
			}else{
				$pdustatsList[]=PDUStats::RowToObject($pdustatsRow);
			}
		}

	return $pdustatsList;
	}

}
?>
