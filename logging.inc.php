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


	=====================================================================


	Logging is designed to be object agnostic.  On any function you want
	logged add the following line to the function.

	LogActions::LogThis($this);


*/

class LogActions {
	var $UserID;
	var $Class;
	var $ObjectID;
	var $Action;
	var $Property;
	var $OldVal;
	var $NewVal;
	var $Time;

	function __construct(){
		$this->UserID=$_SERVER['REMOTE_USER'];
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}

	static function RowToObject($dbRow){
		/*
		 * Generic function that will take any row returned from the fac_Cabinet
		 * table and convert it to an object for use in array or other
		 */
		$log=new LogActions();
		$log->UserID=$dbRow["UserID"];
		$log->Class=$dbRow["Class"];
		$log->ObjectID=$dbRow["ObjectID"];
		$log->Property=$dbRow["Property"];
		$log->Action=$dbRow["Action"];
		$log->OldVal=$dbRow["OldVal"];
		$log->NewVal=$dbRow["NewVal"];
		$log->Time=$dbRow["Time"];

		return $log;
	}
	// Generic catch all logging function
	static function LogThis($object,$originalobject=null){
		$log=new LogActions();

		$trace=debug_backtrace();
		// we're only concerned with the 2nd record $trace can be read for a full debug if something calls for it
		$caller=$trace[1];
		$action=$caller['function'];
		if(preg_match("/create/i", $caller['function'])){$action='1';}
		if(preg_match("/delete/i", $caller['function'])){$action='2';}
		if(preg_match("/update/i", $caller['function'])){$action='3';}

		// Move the action onto the object
		$log->Action=$action;
		$log->Class=get_class($object);

		// Will return true/false for key and value comparison
		function key_comp($v1, $v2) {
			return ($v1 == $v2)?0:1;
		}

		function val_comp($v1, $v2) {
			return ($v1 == $v2)?0:1;
		}

		$diff=array();
		// Find the difference between the original object and the altered object, if present
		if(!is_null($originalobject)){
			$diff=(array_udiff_uassoc((array)$object,(array)$originalobject, "key_comp", "val_comp"));

			// Note the changed values
			foreach($diff as $key => $value){
				$diff[$key]=$key.": ".$originalobject->$key." => ".$object->$key;
			}
		}

		switch($log->Class){
			case "Device":
				$log->ObjectID=$object->DeviceID;
				break;
			case "Cabinet":
				$log->ObjectID=$object->CabinetID;
				break;
			case "SupplyBin":
			case "Supplies":
			case "Config":
				// do we want to track when the default system config has been updated?
			case "Contact":
			case "PowerDistribution":
			case "PowerConnection":
				// similar questions as to the switch connections. are we going to track this?
			case "CDUTemplate":
			case "PowerPanel":
				// only has create and update. should changes here be logged or figure out what changed and log that?
			case "PowerSource":
				// same as PowerPanel
			case "DeviceTemplate":
			default:
		}
		$return=true;
		// If there are any differences then we are upating an object otherwise
		// this is a new object so just log the action as a create
		if(count($diff)){
			foreach($diff as $key => $value){
				$log->Property=$key;
				$log->OldVal=$originalobject->$key;
				$log->NewVal=$object->$key;
				$return=($log->WriteToDB())?$return:false;
			}
		}else{
			$return=$log->WriteToDB();
		}
		return $return;
	}

	function WriteToDB(){
		$sql="INSERT INTO fac_GenericLog set UserID=\"$this->UserID\", 
			Class=\"$this->Class\", ObjectID=\"$this->ObjectID\", Action=\"$this->Action\", 
			Property=\"$this->Property\", OldVal=\"$this->OldVal\", NewVal=\"$this->NewVal\";";

		if(!$this->exec($sql)){
			global $dbh;
			$info=$dbh->errorInfo();

			error_log("PDO Error::LogActions:WriteToDB {$info[2]} SQL=$sql");
			return false;
		}
		return true;
	}

	static function GetLog($object=null){
		$log=new LogActions();

		if(!is_null($object)){
			$log->Class=get_class($object);

			// Attempt to autofind the id of the object we've been handed
			foreach($object as $prop => $value){
				if(preg_match("/id/i", $prop)){
					$log->ObjectID=$value;
					break;
				}
			}
		}

		function sql($sql,$prop,$var){
			$sql=(($sql=='')?" WHERE":" AND")." $prop=\"$var\"";
			return $sql;
		}

		// build out the query using all available data
		$sql="SELECT * FROM fac_GenericLog";

		$add='';
		$add=($log->Class!='')?sql($add,'Class',$log->Class):$add;
		$add=($log->ObjectID!='')?sql($add,'ObjectID',$log->ObjectID):$add;

		$sql.=$add.' ORDER BY Time ASC;';

		$events=array();		
		foreach($log->query($sql) as $dbRow){
			$events[]=LogActions::RowToObject($dbRow);
		}

		return $events;
	}

	// Add in functions here for actions lookup by device, user, date, etc
}

?>
