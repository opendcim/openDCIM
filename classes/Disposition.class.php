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

class Disposition {
	var $DispositionID;
	var $Name;
	var $Description;
	var $ReferenceNumber;
	var $Status;

	function prepare($sql){
		global $dbh;
		return $dbh->prepare($sql);
	}
	
	function lastID() {
		global $dbh;
		return $dbh->lastInsertID();
	}

	function makeSafe() {
		$this->Status = in_array( $this->Status, array( "Active", "Inactive" ))?$this->Status:"Active";
	}

	function createDisposition() {
		$sql = "insert into fac_Disposition set Name=:Name, Description=:Description, ReferenceNumber=:ReferenceNumber, Status=:Status";
		$st = $this->prepare( $sql );

		$this->makeSafe();

		$result = $st->execute( array( ":Name"=>$this->Name, ":Description"=>$this->Description, ":ReferenceNumber"=>$this->ReferenceNumber, ":Status"=>$this->Status ));

		$this->DispositionID = $this->lastID();

		return $this->DispositionID;
	}

	static function getDisposition( $DispositionID = null, $Indexed = false ) {
		global $dbh;

		if ( $DispositionID != null ) {
			$st = $dbh->prepare( "select * from fac_Disposition where DispositionID=:DispositionID" );
			$args = array( ":DispositionID"=>$DispositionID );
		} else {
			$st = $dbh->prepare( "select * from fac_Disposition order by Name ASC" );
			$args = array();
		}

		$st->setFetchMode( PDO::FETCH_CLASS, "Disposition" );
		$st->execute( $args );

		$dList = array();

		while ( $row = $st->fetch() ) {
			if ( $Indexed ) {
				$dList[$row->DispositionID]=$row;
			} else {
				$dList[] = $row;
			}
		}	

		return $dList;	
	}

	function updateDisposition() {
		$sql = "update fac_Disposition set Name=:Name, Description=:Description, ReferenceNumber=:ReferenceNumber, Status=:Status where DispositionID=:DispositionID";
		$st = $this->prepare( $sql );

		$this->makeSafe();

		return $st->execute( array( ":Name"=>$this->Name, ":Description"=>$this->Description, ":ReferenceNumber"=>$this->ReferenceNumber, ":Status"=>$this->Status, ":DispositionID"=>$this->DispositionID ));
	}

	static function removeDisposition( $DispositionID ) {
		//  Don't allow this for any dispositions that have members assigned to it
		$devCount = count( DispositionMembership::getDevices( $DispositionID ));

		if ( $devCount > 0 ) {
			return false;
		}

		global $dbh;

		$st = $dbh->prepare( "delete from fac_Disposition where DispositionID=:DispositionID" );

		return $st->execute( array( ":DispositionID"=>$DispositionID ));
	}
}
?>