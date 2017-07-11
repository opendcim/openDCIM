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

class DispositionMembership {
	var $DispositionID;
	var $DeviceID;
	var $DispositionDate;
	var $DisposedBy;

	function prepare( $sql ) {
		global $dbh;

		return $dbh->prepare( $sql );
	}

	function addDevice() {
		$st = $this->prepare( "insert into fac_DispositionMembership set DispositionID=:DispositionID, DeviceID=:DeviceID, DispositionDate=NOW(), DisposedBy=:DisposedBy" );
		return $st->execute( array( ":DispositionID"=>$this->DispositionID, ":DeviceID"=>$this->DeviceID, ":DisposedBy"=>$this->DisposedBy ));
	}

	static function getDevices( $DispositionID ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_DispositionMembership where DispositionID=:DispositionID" );
		$st->setFetchMode( PDO::FETCH_CLASS, "DispositionMembership" );

		$st->execute( array( ":DispositionID"=>$DispositionID ));

		$devList = array();

		while ( $row = $st->fetch() ) {
			$devList[] = $row;
		}

		return $devList;
	}

	static function removeDevice( $DeviceID ) {
		global $dbh;

		$st = $dbh->prepare( "delete from fac_DispositionMembership where DeviceID=:DeviceID" );
		return $st->execute( array( ":DeviceID"=>$DeviceID ));
	}
}
?>