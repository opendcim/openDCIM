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
	var $ChildID; // for use in cases like power and network connections that don't have individual id values
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
		$log->ChildID=$dbRow["ChildID"];
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
		$caller=(isset($trace[1]))?$trace[1]:array('function' => 'direct');
		$action=$caller['function'];
		if(preg_match("/create/i", $caller['function'])){$action='1';}
		if(preg_match("/delete/i", $caller['function'])){$action='2';}
		if(preg_match("/update/i", $caller['function'])){$action='3';}

		// Move the action onto the object
		$log->Action=$action;
		$log->Class=get_class($object);

		// Will return true/false for key and value comparison
		if(!function_exists("key_comp")){
			function key_comp($v1, $v2) {
				return ($v1 == $v2)?0:1;
			}

			function val_comp($v1, $v2) {
				return ($v1 == $v2)?0:1;
			}
		}

		// The diff function is acting retarded with some values so scrub em
		foreach($object as $key => $value){
			if($value=='NULL' || $value=='0'){
				$object->$key='';
			}
		}
		if(!is_null($originalobject)){
			foreach($originalobject as $key => $value){
				if($value=='NULL' || $value=='0'){
					$originalobject->$key='';
				}
			}
		}
		$diff=array();
		// Find the difference between the original object and the altered object, if present
		if(!is_null($originalobject)){
			$diff=(array_udiff_uassoc((array)$object,(array)$originalobject, "key_comp", "val_comp"));

			// Note the changed values
			foreach($diff as $key => $value){
				// Suppressing errors here because if a new value exists on the object there won't be one in the 
				// original and it will throw an error on the web server
				@$diff[$key]=$key.": ".$originalobject->$key." => ".$object->$key;
			}
		}

		switch($log->Class){
			case "Device":
				$log->ObjectID=$object->DeviceID;
				break;
			case "Cabinet":
				$log->ObjectID=$object->CabinetID;
				break;
			case "CabinetAudit";
				$log->ObjectID=$object->CabinetID;
				break;
			case "DevicePorts":
				$log->ObjectID=$object->DeviceID;
				$log->ChildID=$object->PortNumber;
				// The two following functions are not logged
				// DevicePorts::removeConnections()
				// DevicePorts::removePorts()
				break;
			case "TemplatePorts":
				$log->ObjectID=$object->TemplateID;
				$log->ChildID=$object->PortNumber;
				break;
			case "RackRequest":
				$log->ObjectID=$object->RequestID;
				break;
			case "Slot":
				$log->ObjectID=$object->TemplateID;
				$log->ChildID=$object->Position;
				break;
			case "PowerConnection":
				$log->ObjectID=$object->DeviceID;
				$log->ChildID=$object->DeviceConnNumber;
				break;
				// similar questions as to the switch connections. are we going to track this?
			case "SupplyBin":
			case "Supplies":
			case "Config":
				// do we want to track when the default system config has been updated?
			case "PowerDistribution":
			case "CDUTemplate":
			case "PowerPanel":
				// only has create and update. should changes here be logged or figure out what changed and log that?
			case "PowerSource":
				// same as PowerPanel
			case "DeviceTemplate":
				// The following function isn't logged
				// UpdateDevice()
			case "Department":
				// Not sure how to go about tracking the changes in membership
			default:
				// Attempt to autofind the id of the object we've been handed
				foreach($object as $prop => $value){
					if(preg_match("/id/i", $prop)){
						$log->ObjectID=$value;
						break;
					}
				}
		}
		$return=true;
		// If there are any differences then we are upating an object otherwise
		// this is a new object so just log the action as a create
		if(!is_null($originalobject)){
			if(count($diff)){
				foreach($diff as $key => $value){
					$log->Property=$key;
					// Suppressing errors here because if a new value exists on the object there won't be one in the 
					// original and it will throw an error on the web server
					@$log->OldVal=$originalobject->$key;
					$log->NewVal=$object->$key;
					$return=($log->WriteToDB())?$return:false;
				}
			}
			// in the event that two objects were passed but no changes found, 
			// we just wrote the same info back to the db, nothing to log
		}else{
			// if we're creating a new object make a note of all the values
			if($log->Action==1){
				foreach($object as $prop => $value){
					$log->Property=$prop;
					$log->NewVal=$value;
					// Log only new object properties that have values
					// this should cut down on the amount of useless junk we are putting into the log
					$return=($log->NewVal)?$log->WriteToDB():true;
				}
			}else{
				$return=$log->WriteToDB();
			}
		}
		return $return;
	}

	function WriteToDB(){
		$child=($this->ChildID==null)?', ChildID=NULL':", ChildID=$this->ChildID";

		$sql="INSERT INTO fac_GenericLog set UserID=\"$this->UserID\", 
			Class=\"$this->Class\", ObjectID=\"$this->ObjectID\"$child, Action=\"$this->Action\", 
			Property=\"$this->Property\", OldVal=\"$this->OldVal\", NewVal=\"$this->NewVal\";";

		if(!$this->exec($sql)){
			global $dbh;
			$info=$dbh->errorInfo();

			error_log("PDO Error::LogActions:WriteToDB {$info[2]} SQL=$sql");
			return false;
		}
		return true;
	}

	static function GetLog($object=null,$limitbyclass=true){
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
			$sql=$sql.(($sql=='')?" WHERE":" AND")." $prop=\"$var\"";
			return $sql;
		}

		// build out the query using all available data
		$sql="SELECT * FROM fac_GenericLog";

		$add='';
		$add=($limitbyclass && $log->Class!='')?sql($add,'Class',$log->Class):$add;
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
