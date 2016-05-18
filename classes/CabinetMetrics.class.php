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

class CabinetMetrics {
	var $CabinetID;
	var $IntakeTemperature;
	var $IntakeHumidity;
	var $ExhaustTemperature;
	var $ExhaustHumidity;
	var $CalculatedPower;
	var $CalculatedWeight;
	var $MeasuredPower;
	var $LastRead;
	var $SpaceUsed;

	static function getMetrics( $CabinetID ) {
		global $dbh;
		
		$m = new CabinetMetrics();
		$m->CabinetID = $CabinetID;
		
		$params = array( ":CabinetID"=>$CabinetID );
		// Get the intake side
		$sql = "select max(Temperature) as Temp, max(Humidity) as Humid, LastRead from fac_SensorReadings where DeviceID in (select DeviceID from fac_Device where DeviceType='Sensor' and BackSide=0 and Cabinet=:CabinetID)";
		$st = $dbh->prepare( $sql );
		$st->execute( $params );
		if ( $row = $st->fetch() ) {
			$m->IntakeTemperature = $row["Temp"];
			$m->IntakeHumidity = $row["Humid"];
			$m->LastRead = $row["LastRead"];
		} else {
			error_log( "SQL Error CabinetMetrics::getMetrics" );
		}
		
		// Now the exhaust side
		$sql = "select max(Temperature) as Temp, max(Humidity) as Humid, LastRead from fac_SensorReadings where DeviceID in (select DeviceID from fac_Device where DeviceType='Sensor' and BackSide=1 and Cabinet=:CabinetID)";
		$st = $dbh->prepare( $sql );
		$st->execute( $params );
		if ( $row = $st->fetch() ) {
			$m->ExhaustTemperature = $row["Temp"];
			$m->ExhaustHumidity = $row["Humid"];
		}

		// Now the devices in the cabinet
		// Watts needs to count ALL devices
		$sql = "select sum(a.NominalWatts) as Power, sum(a.Height) as SpaceUsed, sum(b.Weight) as Weight from fac_Device a, fac_DeviceTemplate b where a.TemplateID=b.TemplateID and Cabinet=:CabinetID";
		$st = $dbh->prepare( $sql );
		$st->execute( $params );
		if ( $row = $st->fetch() ) {
			$m->CalculatedPower = $row["Power"];
			$m->CalculatedWeight = $row["Weight"];
		}

		// Space needs to only count devices that are not children of other devices (slots in a chassis)
		$sql = "select sum(if(HalfDepth,Height/2,Height)) as SpaceUsed from fac_Device where Cabinet=:CabinetID and ParentDevice=0";
		$st = $dbh->prepare( $sql );
		$st->execute( $params );
		if ( $row = $st->fetch() ) {
					$m->SpaceUsed = $row["SpaceUsed"];
		}

		
		// And finally the power readings
		$sql = "select sum(Wattage) as Power from fac_PDUStats where PDUID in (select DeviceID from fac_Device where DeviceType='CDU' and Cabinet=:CabinetID)";
		$st = $dbh->prepare( $sql );
		$st->execute( $params );
		if ( $row = $st->fetch() ) {
			$m->MeasuredPower = $row["Power"];
		}
		
		return $m;
	}
}
?>