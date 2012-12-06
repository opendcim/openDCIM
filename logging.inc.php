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

class LogActions {
	var $UserID;
	var $DeviceID;
	var $Action;
	var $Notes;
	var $Time;

	// Generic catch all logging function
	static function LogThis($object){
		$userid=$_SERVER['REMOTE_USER'];
		$trace=debug_backtrace();
		// we're only concerned with the 2nd record $trace can be read for a full debug if something calls for it
		$caller=$trace[1];
		$action=$caller['function'];
		if(preg_match("/create/i", $caller['function'])){$action='1';}
		if(preg_match("/delete/i", $caller['function'])){$action='2';}
		if(preg_match("/update/i", $caller['function'])){$action='3';}
		switch(get_class($object)){
			case "Device":
				$sql="INSERT INTO fac_DeviceLog set UserID='$userid', DeviceID='$object->DeviceID', Action=$action, Time=NOW();";
				break;
			case "Cabinet":
			case "SwitchConnection":
				// not sure what should be logged here exactly if anything, probably just create, update and remove
				//create connection
				//remove connection
				//drop endpoint connections?
				//drop switch connections?
			case "SupplyBin":
			case "Supplies":
			case "Config":
				// do we want to track when the default system config has been updated?
			case "Contact":
			default:
				// default action to keep it from doing anything if it doesn't match a known logging option
				// $caller['class'] :: object's owning class
				$sql="";
		}
		mysql_query($sql);
	}

	// Add in functions here for actions lookup by device, user, date, etc
}

?>
