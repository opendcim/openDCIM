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

class RCI {
	static function GetStatistics( $limit = "global", $id = "" ) {
		//
		//	This function will return all statistics associated with the Rack Cooling Index
		//
		
		global $dbh;
		global $config;
		
		switch ( $limit ) {
			case "dc":
				$limitSQL = "and c.DataCenterID=$id";
				break;
			case "zone":
				$limitSQL = "and c.ZoneID=$id";
				break;
			default:
				$limitSQL = "";
		}

		$countSQL = "select count(distinct(b.Cabinet)) as TotalCabinets from fac_SensorReadings a, fac_Device b, fac_Cabinet c where a.DeviceID=b.DeviceID and b.Cabinet=c.CabinetID and b.BackSide=0 " . $limitSQL;
		$st = $dbh->prepare( $countSQL );
		$st->execute();
		$row = $st->fetch();
		$result["TotalCabinets"] = $row["TotalCabinets"];
		
		$lowSQL = "select c.Location, a.Temperature from fac_SensorReadings a, fac_Device b, fac_Cabinet c where a.DeviceID=b.DeviceID and b.BackSide=0 and b.Cabinet=c.CabinetID and a.Temperature<>0 and a.Temperature<'" . $config->ParameterArray["RCILow"] . "' $limitSQL order by Location ASC";
		$RCILow = array();
		$st = $dbh->prepare( $lowSQL );
		$st->execute();
		while ( $row = $st->fetch() ) {
			array_push( $RCILow, array( $row["Location"], $row["Temperature"] ));
		}
		
		$result["RCILowCount"] = sizeof( $RCILow );
		$result["RCILowList"] = $RCILow;
		
		$highSQL = "select c.Location, a.Temperature from fac_SensorReadings a, fac_Device b, fac_Cabinet c where a.DeviceID=b.DeviceID and b.BackSide=0 and b.Cabinet=c.CabinetID and a.Temperature<>0 and a.Temperature>'" . $config->ParameterArray["RCIHigh"] . "' $limitSQL order by Location ASC";
		$RCIHigh = array();
		$st = $dbh->prepare( $highSQL );
		$st->execute();
		while ( $row = $st->fetch() ) {
			array_push( $RCIHigh, array( $row["Location"], $row["Temperature"] ));
		}
		
		$result["RCIHighCount"] = sizeof( $RCIHigh );
		$result["RCIHighList"] = $RCIHigh;
		
		return $result;
	}
}	

?>
