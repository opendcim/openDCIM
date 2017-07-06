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
		global $person;
		$log=new LogActions();
		$log->UserID=$person->UserID;

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
				if(preg_match("/(date|expire)/i", $key)){
					if(strtotime($originalobject->$key)==strtotime($object->$key)){
						// dates match but maybe different format so remove this match and go on
						unset($diff[$key]);
						continue;
					}
				}
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
			case "PowerPorts":
				$log->ObjectID=$object->DeviceID;
				$log->ChildID=$object->PortNumber;
				break;
			case "Projects":
				$log->ObjectID=$object->ProjectID;
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
			case "SensorTemplate":
				$log->ObjectID=$object->TemplateID;
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
			case "DeviceTemplate":
				// The following function isn't logged
				// UpdateDevice()
			case "Department":
				// Not sure how to go about tracking the changes in membership
			default:
				// Attempt to autofind the id of the object we've been handed
				foreach($object as $prop => $value){
					if(preg_match("/ID/", $prop)){
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
		// Since we don't know what kind of funky data might be passed here let's just use
		// a prepared statement and hope for the best.  We'll just need to be careful on
		// the display end of it to make sure we don't allow something crazy out.
		global $dbh;

		$stmt=$dbh->prepare('INSERT INTO fac_GenericLog (UserID, Class, ObjectID, ChildID, Property, Action, OldVal, NewVal, Time) 
			VALUES (:UserID, :Class, :ObjectID, :ChildID, :Property, :Action, :OldVal, :NewVal, CURRENT_TIMESTAMP)');
		$stmt->bindParam(':UserID', $this->UserID);
		$stmt->bindParam(':Class', $this->Class);
		$stmt->bindParam(':ObjectID', $this->ObjectID);
		$stmt->bindParam(':ChildID', $this->ChildID);
		$stmt->bindParam(':Action', $this->Action);
		$stmt->bindParam(':Property', $this->Property);
		$stmt->bindParam(':OldVal', $this->OldVal);
		// Array data is causing this to spew errors. If we want to log what the array
		// contains we can change this to print_r($this->NewVal,TRUE) but I think that
		// isn't going to be useful
		if(is_array($this->NewVal)){
			$this->NewVal='array()';
		}
		$stmt->bindParam(':NewVal', $this->NewVal);
		// These values can't be null and the PDO statement was being a bitch about it
		$this->Action=(is_null($this->Action))?'':$this->Action;
		$this->Property=(is_null($this->Property))?'':$this->Property;
		$this->OldVal=(is_null($this->OldVal))?'':$this->OldVal;
		$this->NewVal=(is_null($this->NewVal))?'':$this->NewVal;

		if(!$stmt->execute()){
			$info=$stmt->errorInfo();
			error_log("PDO Error::LogActions:WriteToDB {$info[1]}::{$info[2]}");
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


	// This is only gonna be used for sanitizing data used for searching
	function MakeSafe(){
		$p=new People();
		// If we want to really sanitize this list uncomment the function below
//		$this->UserID=(ArraySearchRecursive($this->UserID,$p->GetUserList(),'UserID'))?$this->UserID:'';
		$this->UserID=sanitize($this->UserID);
		$this->Class=in_array($this->Class,get_declared_classes())?$this->Class:'';
		$this->ObjectID=sanitize($this->ObjectID);
		$this->ChildID=sanitize($this->ChildID);
		$this->Action=sanitize($this->Action);
		$this->Property=sanitize($this->Property);
		$this->OldVal=sanitize($this->OldVal);
		$this->NewVal=sanitize($this->NewVal);
		$this->Time=date("Y-m-d", strtotime($this->Time));
	}

	function ListUnique($sqlcolumn){
		if(!in_array($sqlcolumn,array_keys((array)$this))){
			return false;
		}

		$this->MakeSafe();

		// This will store all our extended sql
		$sqlextend="";
		function findit($prop,$val,&$sql){
			$sql.=" AND $prop LIKE \"%$val%\"";
		}
		foreach($this as $prop => $val){
			if($val && $val!=date("Y-m-d", strtotime(0))){
				findit($prop,$val,$sqlextend);
			}
		}

		$sql="SELECT DISTINCT CAST($sqlcolumn AS CHAR(20)) AS Search FROM fac_GenericLog WHERE $sqlcolumn!=\"\"$sqlextend ORDER BY $sqlcolumn ASC;";
		$values=array();
		foreach($this->query($sql) as $row){
			$values[]=$row['Search'];
		}

		return array_unique($values);
	}

	function Search($num_rec_per_page=0,$page=1){
		$this->MakeSafe();

		// This will store all our extended sql
		$sqlextend="";
		foreach($this as $prop => $val){
			if($val && $val!=date("Y-m-d", strtotime(0))){
				extendsql($prop,$val,$sqlextend,true);
			}
		}

		$sqlextend.=" ORDER BY Time DESC";

		// Make sure someone didn't do something crazy with the input
		$page=intval($page);
		$num_rec_per_page=intval($num_rec_per_page);

		if($page && $num_rec_per_page){
			$start_from=($page-1) * $num_rec_per_page;
			$sqlextend.=" LIMIT $start_from, $num_rec_per_page";
		}

		$sql="SELECT * FROM fac_GenericLog $sqlextend;";

		$events=array();		
		foreach($this->query($sql) as $dbRow){
			$events[]=LogActions::RowToObject($dbRow);
		}

		return $events;
	}
}

?>
