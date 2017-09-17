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

class DeviceStatus {
	var $StatusID;
	var $Status;
	var $ColorCode;

	public function __construct($statusid=false){
		if($statusid){
			$this->StatusID=$statusid;
		}
		return $this;
	}

	function MakeSafe(){
		$this->StatusID=intval($this->StatusID);
		$this->Status=sanitize($this->Status);
		$this->ColorCode=sanitize($this->ColorCode);
		if($this->ColorCode==""){
			$this->ColorCode="#FFFFFF"; // New color picker was allowing for an empty value
		}
	}

	static function RowToObject($row){
		$ds=new DeviceStatus();
		$ds->StatusID=$row["StatusID"];
		$ds->Status=$row["Status"];
		$ds->ColorCode=$row["ColorCode"];

		return $ds;
	}
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function prepare($sql){
		global $dbh;
		return $dbh->prepare($sql);
	}
	
	function lastID() {
		global $dbh;
		return $dbh->lastInsertID();
	}

	function createStatus() {
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INTO fac_DeviceStatus SET Status=\"$this->Status\", 
			ColorCode=\"$this->ColorCode\"";
	
		if($this->exec($sql)){
			$this->StatusID=$dbh->lastInsertId();
		}else{
			$info=$dbh->errorInfo();

			error_log("PDO Error::createStatus {$info[2]}");
			return false;
		}
		
		return $this->StatusID;
	}

	function getStatus() {
		$this->MakeSafe();

		$sql="SELECT * FROM fac_DeviceStatus WHERE StatusID=$this->StatusID;";

        if($row=$this->query($sql)->fetch()){
            foreach(DeviceStatus::RowToObject($row) as $prop=>$value){
                $this->$prop=$value;
            }

            return true;
        }else{
            // Kick back a blank record if the StatusID was not found
            foreach($this as $prop=>$value){
                if($prop!='StatusID'){
                    $this->$prop = '';
                }
            }

            return false;
        }
	}

	static function getStatusList($Indexed = false ) {
		global $dbh;

		$st = $dbh->prepare( "SELECT * FROM fac_DeviceStatus ORDER BY Status ASC;" );
		$args = array();

		$st->setFetchMode( PDO::FETCH_CLASS, "DeviceStatus" );
		$st->execute( $args );

		$sList = array();

		while ( $row = $st->fetch() ) {
			if ( $Indexed ) {
				$sList[$row->StatusID]=$row;
			} else {
				$sList[] = $row;
			}
		}	

		return $sList;	
	}

	static function getStatusNames() {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_DeviceStatus order by Status ASC" );

		$st->execute( array() );
		$sList = array();

		while ( $row = $st->fetch() ) {
			$sList[] = $row["Status"];
		}

		return $sList;
	}

	function updateStatus() {
		$this->MakeSafe();

		$oldstatus=new DeviceStatus($this->StatusID);
		$oldstatus->getStatus();

		$sql="UPDATE fac_DeviceStatus SET Status=\"$this->Status\", 
			ColorCode=\"$this->ColorCode\" WHERE StatusID=\"$this->StatusID\";";

		if($this->StatusID==0){
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this,$oldstatus):'';
			$this->query($sql);

			return true;
		} 
	}

	function removeStatus() {
		// Status = Reserved
		// Status = Disposed
		// Both of which are reserved, so they can't be removed unless you go to the db directly, in which case, you deserve a broken system

		// Also, don't go trying to remove a status that doesn't exist
		$ds=new DeviceStatus($this->StatusID);
		if(!$ds->getStatus() || $ds->Status == "Reserved" || $ds->Status == "Disposed" ) {
			return false;
		}

		// Need to search for any devices that have been assigned the given status - if so, don't allow the delete
		$srchDev=new Device();
		$srchDev->Status=$ds->Status;
		$dList=$srchDev->Search();

		if(count($dList)==0){
			$st=$this->prepare( "delete from fac_DeviceStatus where StatusID=:StatusID" );
			return $st->execute( array( ":StatusID"=>$this->StatusID ));
		}

		return false;
	}
}
?>
