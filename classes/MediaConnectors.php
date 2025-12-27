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
class MediaConnectors {
	public $ConnectorID;
	public $ConnectorType;

	function prepare($sql){
		global $dbh;
		return $dbh->prepare($sql);
	}
	
	function lastID() {
		global $dbh;
		return $dbh->lastInsertID();
	}
	
	static function getConnector( $ConnectorID ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_MediaConnectors where ConnectorID=:ConnectorID" );
		$st->setFetchMode( PDO::FETCH_CLASS, "MediaConnectors" );
		$st->execute( array( ":ConnectorID"=>$ConnectorID ));
		if ( $row = $st->fetch() ) {
			return $row;
		} else {
			return false;
		}
	}

	static function getConnectorList() {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_MediaConnectors order by ConnectorType ASC" );
		$st->setFetchMode( PDO::FETCH_CLASS, "MediaConnectors" );
		$st->execute();

		$result = array();
		while ( $row = $st->fetch() ) {
			$result[$row->ConnectorID] = $row;
		}
		return $result;
	}

	static function deleteConnector( $ConnectorID ) {
		global $dbh;

		$oldConnector = getConnector( $connectorID );

		// Set any connections using this ConnectorID to have NULL instead
		$st = $dbh->prepare( "update fac_Ports set ConnectorID=NULL where ConnectorID=:ConnectorID" );
		$st->execute( array( ":ConnectorID"=>$ConnectorID ));

		$st = $dbh->prepare( "delete from fac_MediaConnectors where ConnectorID=:ConnectorID" );
		if ( $st->execute( array( ":ConnectorID"=>$ConnectorID ))) {
			(class_exists('LogActions'))?LogActions::LogThis($oldConnector):'';
			return true;
		} else {
			return false;
		}
	}

	function createConnector( $ConnectorType ) {
		$st = $this->prepare( "insert into fac_MediaConnectors set ConnectorType=:ConnectorType" );
		$st->execute( array( 	":ConnectorType"=>$ConnectorType ) );
		$this->ConnectorID = $this->lastID();

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->ConnectorID;
	}

	function updateConnector() {
		$oldConnector = getConnector( $this->ConnectorID );

		$st = $this->prepare( "fac_MediaConnectors set ConnectorType=:ConnectorType where ConnectorID=:ConnectorID" );

		if( $st->execute( array( ":ConnectorID"=>$this->ConnectorID, ":ConnectorType"=>$this->ConnectorType ) ) ) {
			(class_exists('LogActions'))?LogActions::LogThis($this, $oldConnector):'';
			return true;
		} else {
			return false;
		}
	}

	static function TimesUsed($id){
		global $dbh;

		$count=$dbh->prepare('SELECT * FROM fac_Ports WHERE ConnectorID='.intval($id));
		$count->execute();

		return $count->rowCount();
	}
}
?>
