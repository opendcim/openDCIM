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

	function prepare($sql){
		global $dbh;
		return $dbh->prepare($sql);
	}
	
	function lastID() {
		global $dbh;
		return $dbh->lastInsertID();
	}

	function createStatus() {
		$sql = "insert into fac_DeviceStatus set Status=:Status, ColorCode=:ColorCode";
		$st = $this->prepare( $sql );

		$this->makeSafe();

		$result = $st->execute( array( ":Status"=>$this->Status, ":ColorCode"=>$this->ColorCode ));

		$this->StatusID = $this->lastID();

		return $this-StatusID;
	}

	static function getStatus( $StatusID = null, $Indexed = false ) {
		global $dbh;

		if ( $StatusID != null ) {
			$st = $dbh->prepare( "select * from fac_DeviceStatus where StatusID=:StatusID" );
			$args = array( ":StatusID"=>$StatusID );
		} else {
			$st = $dbh->prepare( "select * from fac_DeviceStatus order by Status ASC" );
			$args = array();
		}

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
		$sql = "update fac_DeviceStatus set Status=:Status, ColorCode=:ColorCode where StatusID=:StatusID";
		$st = $this->prepare( $sql );

		$this->makeSafe();

		return $st->execute( array( ":Status"=>$this->Status, ":ColorCode"=>$this->ColorCode, ":StatusID"=>$this->StatusID ));
	}

	static function removeStatus( $StatusID ) {
		// StatusID = Reserved
		// StatusID = Disposed
		// Both of which are reserved, so they can't be removed unless you go to the db directly, in which case, you deserve a broken system

		// Also, don't go trying to remove a status that doesn't exist
		$statCheck = DeviceStatus::getStatus( $StatusID );
		if ( sizeof($statCheck) != 1 || $statCheck[0]->Status == "Reserved" || $statCheck[0]=Status == "Disposed" ) {
			return false;
		}

		// Need to search for any devices that have been assigned the given status - if so, don't allow the delete
		$srchStat = DeviceStatus::getStatus( $StatusID );
		$srchDev = new Device();
		$srchDev->Status = $srchStat->Status;
		$dList = $srchDev->Search();

		if ( count($dList) == 0 ) {
			global $dbh;

			$st = $dbh->prepare( "delete from fac_DeviceStatus where StatusID=:StatusID" );

			return $st->execute( array( ":StatusID"=>$StatusID ));
		}

		return false;
	}
}
?>