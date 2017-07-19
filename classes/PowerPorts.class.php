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

class PowerPorts {
	var $DeviceID;
	var $PortNumber;
	var $Label;
	var $ConnectedDeviceID;
	var $ConnectedPort;
	var $Notes;

	function MakeSafe() {
		$this->DeviceID=intval($this->DeviceID);
		$this->PortNumber=intval($this->PortNumber);
		$this->Label=sanitize($this->Label);
		$this->ConnectedDeviceID=intval($this->ConnectedDeviceID);
		$this->ConnectedPort=intval($this->ConnectedPort);
		$this->Notes=sanitize($this->Notes);

		if($this->ConnectedDeviceID==0 || $this->ConnectedPort==0){
			$this->ConnectedDeviceID="NULL";
			$this->ConnectedPort="NULL";
		}
	}

	function MakeDisplay(){
		$this->Label=stripslashes(trim($this->Label));
		$this->Notes=stripslashes(trim($this->Notes));
	}

	static function RowToObject($dbRow){
		$pp=new PowerPorts();
		$pp->DeviceID=$dbRow['DeviceID'];
		$pp->PortNumber=$dbRow['PortNumber'];
		$pp->Label=$dbRow['Label'];
		$pp->ConnectedDeviceID=$dbRow['ConnectedDeviceID'];
		$pp->ConnectedPort=$dbRow['ConnectedPort'];
		$pp->Notes=$dbRow['Notes'];

		$pp->MakeDisplay();

		return $pp;
	}

	function getPort(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerPorts WHERE DeviceID=$this->DeviceID AND PortNumber=$this->PortNumber;";

		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}else{
			foreach(PowerPorts::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}
	}

	function getPorts(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerPorts WHERE DeviceID=$this->DeviceID ORDER BY PortNumber ASC;";

		$ports=array();
		foreach($dbh->query($sql) as $row){
			$ports[$row['PortNumber']]=PowerPorts::RowToObject($row);
		}	
		return $ports;
	}

	function createPort($ignore_errors=false) {
		global $dbh;
		
		$this->MakeSafe();

		$sql="INSERT INTO fac_PowerPorts SET DeviceID=$this->DeviceID, 
			PortNumber=$this->PortNumber, Label=\"$this->Label\", 
			ConnectedDeviceID=$this->ConnectedDeviceID, ConnectedPort=$this->ConnectedPort, 
			Notes=\"$this->Notes\";";
			
		if(!$dbh->query($sql) && !$ignore_errors){
			$info=$dbh->errorInfo();

			error_log("createPort::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	static function createPorts($DeviceID,$update_existing=false){
		// If $update_existing is true then we'll try to create all the ports and as a by product
		// create any new ports.  The setting here will ensure we don't log any errors from the
		// ports that already exist.
		$dev=New Device;
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){return false;}

		// Check the user's permissions to modify this device
		if($dev->Rights!='Write'){return false;}
		$portList=array();

		//Search template ports
		$tports=array();
		if($dev->TemplateID>0){
			$tport=new TemplatePowerPorts();
			$tport->TemplateID=$dev->TemplateID;
			$tports=$tport->getPorts();
		}

		for($i=1; $i<=$dev->PowerSupplyCount; $i++){
			$portList[$i]=new PowerPorts();
			$portList[$i]->DeviceID=$dev->DeviceID;
			$portList[$i]->PortNumber=$i;
			if(isset($tports[$i])){
				// Get any attributes from the template
				foreach($tports[$i] as $key => $value){
					if(array_key_exists($key,$portList[$i])){
						$portList[$i]->$key=$value;
					}
				}
				// Temp fix for issue #632 until the models can be brought into alignment
				$portList[$i]->Notes=$tports[$i]->PortNotes;
			}
			$portList[$i]->Label=($portList[$i]->Label=="")?__("Power Connection")." $i":$portList[$i]->Label;
			$portList[$i]->createPort($update_existing);
		}
		return $portList;
	}

	function updateLabel(){
		global $dbh;

		$this->MakeSafe();

		$oldport=new PowerPorts(); // originating port prior to modification
		$oldport->DeviceID=$this->DeviceID;
		$oldport->PortNumber=$this->PortNumber;
		$oldport->getPort();

		$sql="UPDATE fac_PowerPorts SET Label=\"$this->Label\" WHERE 
			DeviceID=$this->DeviceID AND PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this,$oldport):'';
			return true;
		}
	}

	function removePort(){
		/* Remove a single power port from a device */
		global $dbh;

		if(!$this->getport()){
			return false;
		}

		// Disconnect anything that might be connected in the db
		$this->removeConnection();

		$sql="DELETE FROM fac_PowerPorts WHERE DeviceID=$this->DeviceID AND 
			PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
	}

	static function removePorts($DeviceID){
		/* Remove all ports from a device prior to delete, etc */
		global $dbh;

		$dev=new Device();
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){return false;}

		// Check the user's permissions to modify this device
		if($dev->Rights!='Write'){return false;}

		PowerPorts::removeConnections($DeviceID);

		$sql="DELETE FROM fac_PowerPorts WHERE DeviceID=$dev->DeviceID;";

		$dbh->exec($sql);

		return true;
	}

	function updatePort() {
		global $dbh;

		$oldport=new PowerPorts(); // originating port prior to modification
		$oldport->DeviceID=$this->DeviceID;
		$oldport->PortNumber=$this->PortNumber;
		$oldport->getPort();
		$tmpport=new PowerPorts(); // connecting to here
		$tmpport->DeviceID=$this->ConnectedDeviceID;
		$tmpport->PortNumber=$this->ConnectedPort;
		$tmpport->getPort();
		$oldtmpport=new PowerPorts(); // used for logging
		$oldtmpport->DeviceID=$oldport->ConnectedDeviceID;
		$oldtmpport->PortNumber=$oldport->ConnectedPort;
		$oldtmpport->getPort();

		//check rights before we go any further
		$dev=new Device();
		$dev->DeviceID=$this->DeviceID;
		$dev->GetDevice();
		$replacingdev=new Device();
		$replacingdev->DeviceID=$oldport->ConnectedDeviceID;
		$replacingdev->GetDevice();
		$connecteddev=new Device();
		$connecteddev->DeviceID=$this->ConnectedDeviceID;
		$connecteddev->GetDevice();

		$rights=false;
		$rights=($dev->Rights=="Write")?true:$rights;
		$rights=($replacingdev->Rights=="Write")?true:$rights;
		$rights=($connecteddev->Rights=="Write")?true:$rights;

		if(!$rights){
			return false;
		}
	
		$this->MakeSafe();

		// Quick sanity check so we aren't depending on the user
		$this->Label=($this->Label=="")?$this->PortNumber:$this->Label;

		// clear previous connection
		$oldport->removeConnection();
		$tmpport->removeConnection();

		if($this->ConnectedDeviceID==0 || $this->PortNumber==0 || $this->ConnectedPort==0){
			// when any of the above equal 0 this is a delete request
			// skip making any new connections but go ahead and update the device
			// reload tmpport with data from the other device
			$tmpport->DeviceID=$oldport->ConnectedDeviceID;
			$tmpport->PortNumber=$oldport->ConnectedPort;
			$tmpport->getPort();
		}else{
			// make new connection
			$tmpport->ConnectedDeviceID=$this->DeviceID;
			$tmpport->ConnectedPort=$this->PortNumber;
			$tmpport->Notes=$this->Notes;
			PowerPorts::makeConnection($tmpport,$this);
		}

		// update port
		$sql="UPDATE fac_PowerPorts SET ConnectedDeviceID=$this->ConnectedDeviceID,
			Label=\"$this->Label\", ConnectedPort=$this->ConnectedPort, 
			Notes=\"$this->Notes\" WHERE DeviceID=$this->DeviceID AND 
			PortNumber=$this->PortNumber;";
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("updatePort::PDO Error: {$info[2]} SQL=$sql");
			
			return false;
		}

		// two logs, because we probably modified two devices
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldport):'';
		(class_exists('LogActions'))?LogActions::LogThis($tmpport,$oldtmpport):'';
		return true;
	}

	static function makeConnection($port1,$port2){
		global $dbh;

		$port1->MakeSafe();
		$port2->MakeSafe();

		$sql="UPDATE fac_PowerPorts SET ConnectedDeviceID=$port2->DeviceID, 
			ConnectedPort=$port2->PortNumber, Notes=\"$port2->Notes\" WHERE 
			DeviceID=$port1->DeviceID AND PortNumber=$port1->PortNumber;
			UPDATE fac_PowerPorts SET ConnectedDeviceID=$port1->DeviceID, 
			ConnectedPort=$port1->PortNumber, Notes=\"$port1->Notes\" WHERE 
			DeviceID=$port2->DeviceID AND PortNumber=$port2->PortNumber;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("updatePort::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		return true;
	}

	function removeConnection(){
		global $dbh;

		$this->getPort();

		$sql="UPDATE fac_PowerPorts SET ConnectedDeviceID=NULL, ConnectedPort=NULL WHERE
			(DeviceID=$this->DeviceID AND PortNumber=$this->PortNumber) OR 
			(ConnectedDeviceID=$this->DeviceID AND ConnectedPort=$this->PortNumber);";

		/* not sure the best way to catch these errors this should modify 2 lines
		   per run. */
		try{
			$dbh->exec($sql);
		}catch(PDOException $e){
			echo $e->getMessage();
			die();
		}

		return true;
	}

	static function removeConnections($DeviceID){
		/* Drop all power connections on a device */
		global $dbh;

		$dev=new Device();
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){return false;}

		// Check the user's permissions to modify this device
		if($dev->Rights!='Write'){return false;}

		$sql="UPDATE fac_PowerPorts SET ConnectedDeviceID=NULL, ConnectedPort=NULL WHERE DeviceID=$dev->DeviceID OR ConnectedDeviceID=$dev->DeviceID;";

		$dbh->exec($sql);

		return true;
	}

	static function getPortList($DeviceID){
		global $dbh;
		
		$dev=new Device();
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){
			return false;	// This device doesn't exist
		}
		
		$sql="SELECT * FROM fac_PowerPorts WHERE DeviceID=$dev->DeviceID;";
		
		$portList=array();
		foreach($dbh->query($sql) as $row){
			$portList[$row['PortNumber']]=PowerPorts::RowToObject($row);
		}
		
		if( sizeof($portList)==0 && $dev->DeviceType!="Physical Infrastructure" ){
			// somehow this device doesn't have ports so make them now
			$portList=PowerPorts::createPorts($dev->DeviceID);
		}
		
		return $portList;
	}

	static function getConnectedPortList($DeviceID){
		global $dbh;
		
		$dev=new Device();
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){
			return false;	// This device doesn't exist
		}
		
		$sql="SELECT * FROM fac_PowerPorts WHERE DeviceID=$dev->DeviceID and ConnectedDeviceID>0";
		
		$portList=array();
		foreach($dbh->query($sql) as $row){
			$portList[$row['PortNumber']]=PowerPorts::RowToObject($row);
		}
		
		if( sizeof($portList)==0 && $dev->DeviceType!="Physical Infrastructure" ){
			// somehow this device doesn't have ports so make them now
			$portList=PowerPorts::createPorts($dev->DeviceID);
		}
		
		return $portList;
	}

	function Search($indexedbyid=false,$loose=false){
		global $dbh;
		// Store any values that have been added before we make them safe 
		foreach($this as $prop => $val){
			if(isset($val)){
				$o[$prop]=$val;
			}
		}

		// Make everything safe for us to search with
		$this->MakeSafe();

		// This will store all our extended sql
		$sqlextend="";
		foreach($o as $prop => $val){
			extendsql($prop,$this->$prop,$sqlextend,$loose);
		}
		$sql="SELECT * FROM fac_PowerPorts $sqlextend ORDER BY DeviceID, PortNumber ASC;";

		$portList=array();

		foreach($dbh->query($sql) as $portRow){
			if($indexedbyid){
				$portList[$portRow["DeviceID"].$portRow["PortNumber"]]=PowerPorts::RowToObject($portRow);
			}else{
				$portList[]=PowerPorts::RowToObject($portRow);
			}
		}

		return $portList;
	}

	// Make a simple reference to a loose search
	function LooseSearch($indexedbyid=false){
		return $this->Search($indexedbyid,true);
	}
}
?>
