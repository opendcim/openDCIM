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

class MeasurePoint {
	var $MeasurePointID;
	var $Name;
	var $MeasurementType;
	var $LoadSide;
	var $UnitOfMeasure;
	var $AcquisitionMechanism;
	var $Resource;

	function prepare( $sql ) {
		global $dbh;

		return $dbh->prepare( $sql );
	}

	function lastInsertId() {
		global $dbh;

		return $dbh->lastInsertId();
	}

	function makeSafe() {
		// Valid measurement types (for starters)
		$validMeasureTypes = array( "Power", "Temperature", "Humidity", "Position", "Capacity", "Flow" );
		// Valid units of measure (for now)
		$validUoM = array( "Watts", "Degrees", "Percentage", "CFM" );

		$this->MeasurePointID = intval( $this->MeasurePointID );
		$this->Name = sanitize( $this->Name );
		$this->MeasurementType = ( in_array( $this->MeasurementType, $validMeasureTypes ))?$this->MeasurementType:"Power";
		$this->LoadSide = ( $this->LoadSide == 1 )?1:0;
		$this->AcquisitionMechanism = intval( $this->AcquisitionMechanism );
		// This might have to be removed depending on what we encounter with API specs, but I think it should be ok
		$this->Resource = sanitize( $this->Resource );
	}
}

?>