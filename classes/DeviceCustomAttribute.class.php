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

class DeviceCustomAttribute {
	var $AttributeID;
	var $Label;
	var $AttributeType='string';
	var $Required=0;
	var $AllDevices=0;
	var $DefaultValue;

	function MakeSafe() {
		$this->AttributeID=intval($this->AttributeID);
		$this->Label=sanitize($this->Label);
		$this->AttributeType=(in_array($this->AttributeType,$this->GetDeviceCustomAttributeTypeList()))?$this->AttributeType:'string';
		$this->Required=(intval($this->Required)>=1)?1:0;
		$this->AllDevices=(intval($this->AllDevices)>=1)?1:0;
		$this->DefaultValue=sanitize($this->DefaultValue);
	}
	
	function CheckInput() {
		$this->MakeSafe();
		
		if(!in_array($this->AttributeType, DeviceCustomAttribute::GetDeviceCustomAttributeTypeList())){
			return false;
		}
		if(trim($this->DefaultValue) != "") {
			switch($this->AttributeType){
				case "number":
					if(!filter_var($this->DefaultValue, FILTER_VALIDATE_FLOAT)) { return false; }
					break;
				case "integer":
					if(!filter_var($this->DefaultValue, FILTER_VALIDATE_INT)) { return false; }
					break;
				case "date":
					$dateparts = preg_split("/\/|-/", $this->DefaultValue);
					if(count($dateparts)!=3 || !checkdate($dateparts[0], $dateparts[1], $dateparts[2])) { return false; }
					break;
				case "phone":
					// stole this regex out of the jquery.validationEngine-en.js source
					if(!preg_match("/^([\+][0-9]{1,3}[\ \.\-])?([\(]{1}[0-9]{2,6}[\)])?([0-9\ \.\-\/]{3,20})((x|ext|extension)[\ ]?[0-9]{1,4})?$/", $this->DefaultValue)) { return false; }
					break;
				case "email":
					if(!filter_var($this->DefaultValue, FILTER_VALIDATE_EMAIL)) { return false; }
					break;
				case "ipv4":
					if(!filter_var($this->DefaultValue, FILTER_VALIDATE_IP)) { return false; }
					break;
				case "url":
					if(!filter_var($this->DefaultValue, FILTER_VALIDATE_URL)) { return false; }
					break;
				case "checkbox":
					$acceptable = array("0", "1", "true", "false", "on", "off");
					if(!in_array($this->DefaultValue, $acceptable)) { return false; }		
					break;
				case "set":
					// Attempt to stem S.U.T. here
					// will track if we want a blank space at the beginning of our list
					$blankstart=substr($this->DefaultValue,0,1)==',';
					// will store an array of our stuff
					$dirtyarray=array();
					// parse the list combining / removing duplicates
					foreach(explode(',',$this->DefaultValue) as $item){
						$dirtyarray[$item]=$item;
					}
					// trim possible leading blank spaces / other blanks
					if(!$blankstart){unset($dirtyarray['']);}
					// condense back to csv
					$this->DefaultValue=implode(',',$dirtyarray);
					break;
			}
		}
		return true;
	}

	static function RowToObject($dbRow) {
		$dca = new DeviceCustomAttribute();
		$dca->AttributeID=$dbRow["AttributeID"];
		$dca->Label=$dbRow["Label"];
		$dca->AttributeType=$dbRow["AttributeType"];
		$dca->Required=$dbRow["Required"];
		$dca->AllDevices=$dbRow["AllDevices"];
		$dca->DefaultValue=$dbRow["DefaultValue"];
		return $dca;
	}

	function CreateDeviceCustomAttribute() {
		global $dbh;
		$this->MakeSafe();

		// Prevent custom attributes from being made that match attributes we already have present
		$dev=new Device();
		if (in_array(strtolower($this->Label),array_map('strtolower', array_keys((array) $dev)))){
			return false;
		}

		if(!$this->CheckInput()) { return false; }
		$sql="INSERT INTO fac_DeviceCustomAttribute SET Label=\"$this->Label\",
			AttributeType=\"$this->AttributeType\", Required=$this->Required,
			AllDevices=$this->AllDevices,DefaultValue=\"$this->DefaultValue\";";

		if(!$dbh->exec($sql)) {
			$info=$dbh->errorInfo();
			error_log("CreateDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql");
			return false;
		} else {
			$this->AttributeID=$dbh->LastInsertId();
		}

		// If something is marked "AllDevices", we don't actually add it to all devices
		// in the database, we just check when displaying devices/templates and 
		// display any that are AllDevices to help reduce db size/complexity

		(class_exists('LogActions'))?LogActions::LogThis($this):'';

		return $this->AttributeID;

	}

	function UpdateDeviceCustomAttribute() {
		global $dbh;
		$this->MakeSafe();

		// Prevent custom attributes from being renamed to somethign that already exists 
		$dev=new Device();
		if (in_array(strtolower($this->Label),array_map('strtolower', array_keys((array) $dev)))){
			return false;
		}

		if(!$this->CheckInput()) { return false; }

		$old = new DeviceCustomAttribute();
		$old->AttributeID = $this->AttributeID;
		$old->GetDeviceCustomAttribute();

		$sql="UPDATE fac_DeviceCustomAttribute SET Label=\"$this->Label\",
			AttributeType=\"$this->AttributeType\", Required=$this->Required,
			AllDevices=$this->AllDevices,DefaultValue=\"$this->DefaultValue\"
			WHERE AttributeID=$this->AttributeID;";

		if(!$dbh->query($sql)) {
			$info=$dbh->errorInfo();
			error_log("UpdateDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';

		return true;
	}

	function GetDeviceCustomAttribute() {
		global $dbh;
		$this->MakeSafe();
		$sql="SELECT AttributeID, Label, AttributeType, Required, AllDevices, DefaultValue 
			FROM fac_DeviceCustomAttribute
			WHERE AttributeID=$this->AttributeID;";

		if($dcaRow=$dbh->query($sql)->fetch()) {
			foreach(DeviceCustomAttribute::RowToObject($dcaRow) as $prop => $value) {
				$this->$prop=$value;
			}
			return true;
		} else {
			return false;
		}
	}
	
	function RemoveDeviceCustomAttribute() {
		global $dbh;
		$this->AttributeID=intval($this->AttributeID);
	
		$sql="DELETE FROM fac_DeviceTemplateCustomValue WHERE AttributeID=$this->AttributeID;";
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("RemoveDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}

		$sql="DELETE FROM fac_DeviceCustomValue WHERE AttributeID=$this->AttributeID;";
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("RemoveDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}
		
		$sql="DELETE FROM fac_DeviceCustomAttribute WHERE AttributeID=$this->AttributeID;";
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("RemoveDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}
		
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;

	}

	function RemoveFromTemplatesAndDevices() {
		global $dbh;
		$this->AttributeID=intval($this->AttributeID);
	
		$sql="DELETE FROM fac_DeviceTemplateCustomValue WHERE AttributeID=$this->AttributeID;";
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("RemoveDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}

		$sql="DELETE FROM fac_DeviceCustomValue WHERE AttributeID=$this->AttributeID;";
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("RemoveDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}
		
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	static function GetDeviceCustomAttributeList($indexbyname=false) {
		global $dbh;
		$dcaList=array();
		
		$sql="SELECT AttributeID, Label, AttributeType, Required, AllDevices, DefaultValue
			FROM fac_DeviceCustomAttribute
			ORDER BY Label, AttributeID;";

		foreach($dbh->query($sql) as $dcaRow) {
			if($indexbyname){
				$dcaList[$dcaRow["Label"]]=DeviceCustomAttribute::RowToObject($dcaRow);
			}else{
				$dcaList[$dcaRow["AttributeID"]]=DeviceCustomAttribute::RowToObject($dcaRow);
			}
		}

		return $dcaList;
	}

	static function GetDeviceCustomAttributeTypeList() {
		$validtypes=array("string","number","integer","date","phone","email","ipv4","url","checkbox","set");

		return $validtypes;
	}	

	static function TimesUsed($AttributeID) {
		global $dbh;
		$AttributeID=intval($AttributeID);

		// get a count of the number of times this attribute is in templates or devices
		$sql="SELECT COUNT(*) + (SELECT COUNT(*) FROM fac_DeviceCustomValue WHERE 
		AttributeID=$AttributeID) AS Result FROM fac_DeviceTemplateCustomValue WHERE 
		AttributeID=$AttributeID";

		$count=$dbh->prepare($sql);
		$count->execute();
		
		return $count->fetchColumn();
	}
}
?>
