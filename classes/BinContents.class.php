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
class BinContents {
	var $BinID;
	var $SupplyID;
	var $Count;

	function MakeSafe(){
		$this->BinID=intval($this->BinID);
		$this->SupplyID=intval($this->SupplyID);
		$this->Count=intval($this->Count);
	}

	static function RowToObject($row){
		$bin=new BinContents();
		$bin->BinID=$row["BinID"];
		$bin->SupplyID=$row["SupplyID"];
		$bin->Count=$row["Count"];

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
	
	function AddContents(){
		$sql="INSERT INTO fac_BinContents SET BinID=$this->BinID, SupplyID=$this->SupplyID, Count=$this->Count;";
		return $this->exec($sql);
	}
	
	function GetBinContents(){
		$this->MakeSafe();

		/* Return all of the supplies found in this bin */
		$sql="SELECT * FROM fac_BinContents WHERE BinID=$this->BinID;";
		
		$binList=array();
		foreach($this->query($sql) as $row){
			$binList[]=BinContents::RowToObject($row);
		}
		
		return $binList;
	}
	
	function FindSupplies(){
		$this->MakeSafe();

		/* Return all of the bins where this SupplyID is found */
		$sql="SELECT a.* FROM fac_BinContents a, fac_SupplyBin b WHERE 
			a.SupplyID=$this->SupplyID AND a.BinID=b.BinID ORDER BY b.Location ASC;";

		$binList=array();
		foreach($this->query($sql) as $row){
			$binList[]=BinContents::RowToObject($row);
		}
		
		return $binList;		
	}
	
	function UpdateCount(){
		$this->MakeSafe();

		$sql="UPDATE fac_BinContents SET Count=$this->Count WHERE BinID=$this->BinID 
			AND SupplyID=$this->SupplyID;";

		return $this->query($sql);
	}
	
	function RemoveContents(){
		$this->MakeSafe();

		$sql="DELETE FROM fac_BinContents WHERE BinID=$this->BinID AND 
			SupplyID=$this->SupplyID;";

		return $this->exec($sql);
	}
	
	function EmptyBin(){
		$this->MakeSafe();

		$sql="DELETE FROM fac_BinContents WHERE BinID=$this->BinID;";

		return $this->exec($sql);
	}
}
?>
