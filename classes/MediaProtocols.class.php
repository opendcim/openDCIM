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
class MediaProtocols {
	public $ProtocolID;
	public $ProtocolName;

	function prepare($sql){
		global $dbh;
		return $dbh->prepare($sql);
	}
	
	function lastID() {
		global $dbh;
		return $dbh->lastInsertID();
	}
	
	static function getProtocol( $ProtocolID ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_MediaProtocols where ProtocolID=:ProtocolID" );
		$st->setFetchMode( PDO::FETCH_CLASS, "MediaProtocols" );
		$st->execute( array( ":ProtocolID"=>$ProtocolID ));
		if ( $row = $st->fetch() ) {
			return $row;
		} else {
			return false;
		}
	}

	static function getProtocolList() {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_MediaProtocols order by ProtocolName ASC" );
		$st->setFetchMode( PDO::FETCH_CLASS, "MediaProtocols" );
		$st->execute();

		$result = array();
		while ( $row = $st->fetch() ) {
			$result[$row->ProtocolID] = $row;
		}

		return $result;
	}

	static function deleteProtocol( $ProtocolID, $NewProtocolID = 0 ) {
		global $dbh;

		$oldProtocol = MediaProtocols::getProtocol( $ProtocolID );

		// Set any connections using this ProtocolID to the new one (default 0) instead
		$st = $dbh->prepare( "update fac_Ports set ProtocolID=:NewProtocolID where ProtocolID=:ProtocolID" );
		$st->execute( array( ":ProtocolID"=>$ProtocolID, ":NewProtocolID"=>$NewProtocolID ));

		$st = $dbh->prepare( "delete from fac_MediaProtocols where ProtocolID=:ProtocolID" );
		if ( $st->execute( array( ":ProtocolID"=>$ProtocolID ))) {
			(class_exists('LogActions'))?LogActions::LogThis($oldProtocol):'';
			return true;
		} else {
			return false;
		}
	}

	function createProtocol( $ProtocolName ) {
		$st = $this->prepare( "insert into fac_MediaProtocols set ProtocolName=:ProtocolName" );
		$st->execute( array( 	":ProtocolName"=>$ProtocolName ) );
		$this->ProtocolID = $this->lastID();

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->ProtocolID;
	}

	function updateProtocol() {
		$oldProtocol = getProtocol( $this->ProtocolID );

		$st = $this->prepare( "fac_MediaProtocols set ProtocolName=:ProtocolName where ProtocolID=:ProtocolID" );

		if( $st->execute( array( ":ProtocolID"=>$this->ProtocolID, ":ProtocolName"=>$this->ProtocolName ) ) ) {
			(class_exists('LogActions'))?LogActions::LogThis($this, $oldProtocol):'';
			return true;
		} else {
			return false;
		}
	}

	static function TimesUsed($id){
		global $dbh;

		$count=$dbh->prepare('SELECT * FROM fac_Ports WHERE ProtocolID='.intval($id));
		$count->execute();

		return $count->rowCount();
	}
}
?>
