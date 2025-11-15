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
class PowerPhases {
	public $PhaseID;
	public $PhaseName;

	function prepare($sql){
		global $dbh;
		return $dbh->prepare($sql);
	}
	
	function lastID() {
		global $dbh;
		return $dbh->lastInsertID();
	}
	
	static function getPhase( $PhaseID ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_PowerPhases where PhaseID=:PhaseID" );
		$st->setFetchMode( PDO::FETCH_CLASS, "PowerPhases" );
		$st->execute( array( ":PhaseID"=>$PhaseID ));
		if ( $row = $st->fetch() ) {
			return $row;
		} else {
			return false;
		}
	}

	static function getPhaseList() {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_PowerPhases order by PhaseName ASC" );
		$st->setFetchMode( PDO::FETCH_CLASS, "PowerPhases" );
		$st->execute();

		$result = array();
		while ( $row = $st->fetch() ) {
			$result[] = $row;
		}

		return $result;
	}

	static function deletePhase( $PhaseID ) {
		global $dbh;

		$oldPhase = getPhase( $PhaseID );

		// Set any connections using this PhaseID to have NULL instead
		$st = $dbh->prepare( "update fac_Ports set PhaseID=NULL where PhaseID=:PhaseID" );
		$st->execute( array( ":PhaseID"=>$PhaseID ));

		$st = $dbh->prepare( "delete from fac_PowerPhases where PhaseID=:PhaseID" );
		if ( $st->execute( array( ":PhaseID"=>$PhaseID ))) {
			(class_exists('LogActions'))?LogActions::LogThis($oldPhase):'';
			return true;
		} else {
			return false;
		}
	}

	function createPhase( $PhaseName ) {
		$st = $this->prepare( "insert into fac_PowerPhases set PhaseName=:PhaseName" );
		$st->execute( array( 	":PhaseName"=>$PhaseName ) );
		$this->PhaseID = $this->lastID();

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->PhaseID;
	}

	function updatePhase() {
		$oldPhase = getPhase( $this->PhaseID );
		
		$st = $this->prepare( "fac_PowerPhases set PhaseName=:PhaseName where PhaseID=:PhaseID" );

		if( $st->execute( array( ":PhaseID"=>$this->PhaseID, ":PhaseName"=>$this->PhaseName ) ) ) {
			(class_exists('LogActions'))?LogActions::LogThis($this, $oldPhase):'';
			return true;
		} else {
			return false;
		}
	}
}
?>
