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


class CabinetTemps {
	/* CabinetTemps:	Temperature sensor readings from intelligent, SNMP readable temperature sensors */
	
	var $CabinetID;
	var $LastRead;
	var $Temp;
	var $Humidity;

	function GetReading() {
		global $dbh;
		
		$sql = sprintf( "select * from fac_CabinetTemps where CabinetID=%d", $this->CabinetID );
		
		if ( $row = $dbh->query( $sql )->fetch() ) {
			$this->LastRead = date( "m-d-Y H:i:s", strtotime($row["LastRead"]) );
			$Temp = $row["Temp"];
			$Humidity = $row["Humidity"];
		} else {
			$info = $dbh->errorInfo();

			error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
			return false;
		}
		
		return;
	}	
}
?>
