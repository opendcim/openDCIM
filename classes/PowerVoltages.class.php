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
class PowerVoltages {
	public $VoltageID;
	public $VoltageName;

	function prepare($sql){
		global $dbh;
		return $dbh->prepare($sql);
	}
	
	function lastID() {
		global $dbh;
		return $dbh->lastInsertID();
	}
	
	static function getVoltage( $VoltageID ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_PowerVoltages where VoltageID=:VoltageID" );
		$st->setFetchMode( PDO::FETCH_CLASS, "PowerVoltages" );
		$st->execute( array( ":VoltageID"=>$VoltageID ));
		if ( $row = $st->fetch() ) {
			return $row;
		} else {
			return false;
		}
	}

	static function getVoltageList() {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_PowerVoltages order by VoltageName ASC" );
		$st->setFetchMode( PDO::FETCH_CLASS, "PowerVoltages" );
		$st->execute();

		$result = array();
		while ( $row = $st->fetch() ) {
			$result[$row->VoltageID] = $row;
		}

		return $result;
	}

	static function deleteVoltage( $VoltageID, $NewVoltageID ) {
		global $dbh;

		$oldVoltage = PowerVoltages::getVoltage( $VoltageID );

		// Set any connections using this VoltageID to have NewID (Default 0) instead
		$st = $dbh->prepare( "update fac_PowerPorts set VoltageID=:NewVoltageID where VoltageID=:VoltageID" );
		$st->execute( array( ":VoltageID"=>$VoltageID, ":NewVoltageID"=>$NewVoltageID ));

		$st = $dbh->prepare( "delete from fac_PowerVoltages where VoltageID=:VoltageID" );
		if ( $st->execute( array( ":VoltageID"=>$VoltageID ))) {
			(class_exists('LogActions'))?LogActions::LogThis($oldVoltage):'';
			return true;
		} else {
			return false;
		}
	}

	function createVoltage( $VoltageName ) {
		$st = $this->prepare( "insert into fac_PowerVoltages set VoltageName=:VoltageName" );
		$st->execute( array( 	":VoltageName"=>$VoltageName ) );
		$this->VoltageID = $this->lastID();

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->VoltageID;
	}

	function updateVoltage() {
		$oldVoltage = getVoltage( $this->VoltageID );
		
		$st = $this->prepare( "fac_PowerVoltages set VoltageName=:VoltageName where VoltageID=:VoltageID" );

		if( $st->execute( array( ":VoltageID"=>$this->VoltageID, ":VoltageName"=>$this->VoltageName ) ) ) {
			(class_exists('LogActions'))?LogActions::LogThis($this, $oldVoltage):'';
			return true;
		} else {
			return false;
		}
	}

	static function TimesUsed($id){
		global $dbh;

		$count=$dbh->prepare('SELECT * FROM fac_PowerPorts WHERE VoltageID='.intval($id));
		$count->execute();

		return $count->rowCount();
	}
}
?>
