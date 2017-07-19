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
class SupplyBin {
	var $BinID;
	var $Location;
	
	function MakeSafe(){
		$this->BinID=intval($this->BinID);
		$this->Location=sanitize($this->Location);
	}

	function MakeDisplay(){
		$this->Location=stripslashes($this->Location);
	}

	static function RowToObject($row){
		$bin=New SupplyBin();
		$bin->BinID=$row['BinID'];
		$bin->Location=$row['Location'];
		$bin->MakeDisplay();

		return $bin;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function GetBin(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_SupplyBin WHERE BinID=$this->BinID;";

		if($row=$this->query($sql)->fetch()){
			foreach(SupplyBin::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}
	
	function CreateBin(){
		global $dbh;
		$this->MakeSafe();

		$sql="INSERT INTO fac_SupplyBin SET Location=\"$this->Location\";";
		
		if(!$this->exec($sql)){
			return false;
		}else{
			$this->BinID=$dbh->lastInsertID();
			$this->MakeDisplay();
			return true;
		}
	}
	
	function UpdateBin(){
		$this->MakeSafe();

		$sql="UPDATE fac_SupplyBin SET Location=\"$this->Location\" WHERE 
			BinID=$this->BinID;";

		return $this->query($sql);
	}
	
	function DeleteBin(){
		// needs testing, not currently implemented
		$this->MakeSafe();

		$sql="DELETE FROM fac_SupplyBin WHERE BinID=$this->BinID; 
			DELETE FROM fac_BinContents WHERE BinID=$this->BinID; 
			DELETE FROM fac_BinAudits WHERE BinID=$this->BinID;";

		return $this->exec($sql);
	}
	
	function GetBinList(){
		$sql="SELECT * FROM fac_SupplyBin ORDER BY Location ASC;";
		
		$binList=array();
		foreach($this->query($sql) as $row){
			$binList[]=SupplyBin::RowToObject($row);
		}
		
		return $binList;
	}
}
?>
