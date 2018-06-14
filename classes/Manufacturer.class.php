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
class Manufacturer {
	var $ManufacturerID;
	var $Name;
	var $GlobalID;
	var $SubscribeToUpdates;

	public function __construct($manufacturerid=false){
		if($manufacturerid){
			$this->ManufacturerID=$manufacturerid;
		}
		return $this;
	}

	function MakeSafe(){
		$this->ManufacturerID=intval($this->ManufacturerID);
		$this->Name=sanitize($this->Name);
		$this->GlobalID = intval( $this->GlobalID );
		$this->SubscribeToUpdates = intval( $this->SubscribeToUpdates );
	}

	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
	}

	static function RowToObject($row){
		$m=new Manufacturer();
		$m->ManufacturerID=$row["ManufacturerID"];
		$m->Name=$row["Name"];
		$m->GlobalID = $row["GlobalID"];
		$m->SubscribeToUpdates = $row["SubscribeToUpdates"];
		$m->MakeDisplay();

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
	
	function getManufacturerByGlobalID() {
		$st = $this->prepare( "select * from fac_Manufacturer where GlobalID=:GlobalID" );
		$st->execute( array( ":GlobalID"=>$this->GlobalID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "Manufacturer" );
		
		if ( $row = $st->fetch() ) {
			foreach( $row as $prop=>$val ) {
				$this->$prop = $val;
			}
			
			return true;
		} else {
			return false;
		}
	}

	// Wrapper to make this method like the other classes
	function GetManufacturer(){
		return $this->GetManufacturerByID();
	}
	
	function GetManufacturerByID(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Manufacturer WHERE ManufacturerID=$this->ManufacturerID;";

		if($row=$this->query($sql)->fetch()){
			foreach(Manufacturer::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}	
			return true;
		}else{
			return false;
		}
	}
	
	function GetManufacturerByName(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Manufacturer WHERE ucase(Name)=ucase('".$this->Name."');";

		if($row=$this->query($sql)->fetch()){
			foreach(Manufacturer::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}	
			return true;
		}else{
			return false;
		}
	}
	
	static function GetManufacturerList($indexbyid=false){
		global $dbh;

		$sql="SELECT * FROM fac_Manufacturer ORDER BY Name ASC;";

		$ManufacturerList=array();
		foreach($dbh->query($sql) as $row){
			if($indexbyid){
				$ManufacturerList[$row['ManufacturerID']]=Manufacturer::RowToObject($row);
			}else{
				$ManufacturerList[]=Manufacturer::RowToObject($row);
			}
		}

		return $ManufacturerList;
	}
	
	function getSubscriptionList() {
		$st = $this->prepare( "select * from fac_Manufacturer where GlobalID>0 and SubscribeToUpdates=true order by GlobalID ASC" );
		$st->execute();
		$st->setFetchMode( PDO::FETCH_CLASS, "Manufacturer" );
		
		$mList = array();
		while ( $row = $st->fetch() ) {
			$mList[] = $row;
		}
		
		return $mList;
	}

	function CreateManufacturer(){
		global $dbh;
		
		$this->MakeSafe();

		$sql="INSERT INTO fac_Manufacturer SET Name=\"$this->Name\", GlobalID=$this->GlobalID,
		SubscribeToUpdates=$this->SubscribeToUpdates;";

		if(!$dbh->exec($sql)){
			error_log( "SQL Error: " . $sql );
			return false;
		}else{
			$this->ManufacturerID=$dbh->lastInsertID();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			$this->MakeDisplay();
			return true;
		}
	}

	function DeleteManufacturer($TransferTo=null){
		$this->MakeSafe();

		$tmpl=new DeviceTemplate();
		$tmpl->ManufacturerID=$this->ManufacturerID;
		$templates=$tmpl->GetTemplateListByManufacturer();

		// If a TransferTo isn't supplied then just delete the templates that depend on this key
		foreach($templates as $DeviceTemplate){
			// A manufacturerid of 0 is impossible so if we get that via something fuck 'em delete
			if(!is_null($TransferTo) && intval($TransferTo)>0){
				$DeviceTemplate->ManufacturerID=$TransferTo;
				$DeviceTemplate->UpdateTemplate();
			}else{
				// This option is not being provided but us at this time, maybe through the API
				$DeviceTemplate->DeleteTemplate();
			}
		}

		$sql="DELETE FROM fac_Manufacturer WHERE ManufacturerID=$this->ManufacturerID;";

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->query($sql);
	}

	function UpdateManufacturer(){
		$this->MakeSafe();

		$sql="UPDATE fac_Manufacturer SET Name=\"$this->Name\", GlobalID=$this->GlobalID, SubscribeToUpdates=$this->SubscribeToUpdates WHERE ManufacturerID=$this->ManufacturerID;";

		$old=new Manufacturer();
		$old->ManufacturerID=$this->ManufacturerID;
		$old->GetManufacturerByID();

		$this->MakeDisplay();
		(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';
		return $this->query($sql);
	}
}
?>
