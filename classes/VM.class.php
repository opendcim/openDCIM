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

class VM {
	/*	VM:	Originally called ESX since VMWare ESX was the only supported hypervisor for
			remote SNMP queries.  However, ProxMox support has since been added, and it
			is anticipated that more may be added, such as remote data center (cloud) services.

			Methods that are generic to virtualization are in this class, while those specific
			to VWare ESX remain in the ESX class.
	*/
	var $VMIndex;
	var $DeviceID;
	var $LastUpdated;
	var $vmID;
	var $vmName;
	var $vmState;
	var $Owner;
	var $PrimaryContact;

	function MakeSafe(){
		$this->VMIndex=intval($this->VMIndex);
 		$this->DeviceID=intval($this->DeviceID);
		$this->vmID=intval($this->vmID);
		$this->vmName=sanitize($this->vmName);
		$this->vmState=sanitize($this->vmState);
		$this->Owner=intval($this->Owner);
		$this->PrimaryContact=intval($this->PrimaryContact);
 		$this->LastUpdated=sanitize($this->LastUpdated);
 	}

  
	static function RowToObject($dbRow){
		/*
		 * Generic function that will take any row returned from the fac_VMInventory
		 * table and convert it to an object for use in array or other
		 */

		$vm=new VM();
		$vm->VMIndex=$dbRow["VMIndex"];
		$vm->DeviceID=$dbRow["DeviceID"];
		$vm->LastUpdated=$dbRow["LastUpdated"];
		$vm->vmID=$dbRow["vmID"];
		$vm->vmName=$dbRow["vmName"];
		$vm->vmState=$dbRow["vmState"];
		$vm->Owner=$dbRow["Owner"];
		$vm->PrimaryContact=$dbRow["PrimaryContact"];

		return $vm;
	}

	function search($sql){
		global $dbh;

		$vmList=array();
		$vmCount=0;

		foreach($dbh->query($sql) as $row){
			$vmList[$vmCount]=VM::RowToObject($row);
			$vmCount++;
		}

		return $vmList;
	}


	function query($sql){
                global $dbh;
                return $dbh->query($sql);
        }

 
	function GetVMbyIndex() {
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_VMInventory WHERE VMIndex=$this->VMIndex;";

		if(!$vmRow=$dbh->query($sql)->fetch()){
			return false;
		}else{
			foreach(VM::RowToObject($vmRow) as $param => $value){
				$this->$param=$value;
			}
			return true;
		}
	}


	function SearchVM($indexedbyid=false,$loose=false){
		$this->MakeSafe();

		$sqlextend="";
                foreach($this as $prop => $val){
                        if($val){
                                extendsql($prop,$val,$sqlextend,$loose);
                        }
                }

		$sql="SELECT *  FROM fac_VMInventory $sqlextend;";

		$VMList=array();
		foreach($this->query($sql) as $VMRow){
			if($indexedbyid){
				$VMList[$VMRow["VMIndex"]]=VM::RowToObject($VMRow);
			}else{
				$VMList[]=VM::RowToObject($VMRow);
			}
		}
		return $VMList;
	}

	function CreateVM() {
		global $dbh;

                $this->MakeSafe();

		$sql="INSERT INTO fac_VMInventory (DeviceID,LastUpdated,vmID,vmName,vmState,Owner,PrimaryContact) VALUES
			($this->DeviceID,\"".date("Y-m-d H:i:s", strtotime($this->LastUpdated))."\",$this->vmID,
                        \"".$this->vmName."\",\"".$this->vmState."\",$this->Owner,$this->PrimaryContact);";

		if(!$dbh->query($sql)){
                        $info=$dbh->errorInfo();
			
			error_log("CreateVM::PDO Error: {$info[2]} SQL=$sql" );
                        return false;
                }
                return true;
	}
	
	function UpdateVM() {
		global $dbh;

		$this->MakeSafe();

		$sql="UPDATE fac_VMInventory SET DeviceID=$this->DeviceID,LastUpdated=\"".date("Y-m-d H:i:s", strtotime($this->LastUpdated))."\",vmID=$this->vmID,
			vmName=\"".$this->vmName."\",vmState=\"".$this->vmState."\",Owner=$this->Owner,PrimaryContact=$this->PrimaryContact WHERE
			VMIndex=$this->VMIndex;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("UpdateVM::PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}
		return true;                            
	}

	function DeleteVM() {
		global $dbh;

		$this->MakeSafe();

		$sql="DELETE from fac_VMInventory WHERE VMIndex=$this->VMIndex;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("DeleteVM::PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}
		return true;
	}
  
	function UpdateVMOwner() {
		global $dbh;

		$sql="UPDATE fac_VMInventory SET Owner=$this->Owner, PrimaryContact=$this->PrimaryContact WHERE VMIndex=$this->VMIndex;";
		$dbh->query($sql);
	} 
  
	function GetInventory() {
		$sql="SELECT * FROM fac_VMInventory ORDER BY DeviceID, vmName;";
		return $this->search($sql);
	}
  
	function GetDeviceInventory() {
		$sql="SELECT * FROM fac_VMInventory WHERE DeviceID=$this->DeviceID ORDER BY vmName;";
		return $this->search($sql);
	}
  
	function GetVMListbyOwner() {
		$sql="SELECT * FROM fac_VMInventory WHERE Owner=$this->Owner ORDER BY DeviceID, vmName;";
		return $this->search($sql);
	}
  
	function SearchByVMName() {
		$sql="SELECT * FROM fac_VMInventory WHERE vmName like \"%$this->vmName%\";";
		return $this->search($sql);
	}
  
	function GetOrphanVMList(){
		$sql="SELECT * FROM fac_VMInventory WHERE Owner=0;"; 
		return $this->search($sql);
	}

	function GetExpiredVMList($numDays){
		// I don't think this is standard SQL and will need to be looked at closer
		$sql="SELECT * FROM fac_VMInventory WHERE to_days(now())-to_days(LastUpdated)>$numDays;"; 
		return $this->search($sql);
	}
  
	function ExpireVMs($numDays){
		global $dbh;

		// Don't allow calls to expire EVERYTHING
		if($numDays >0){
			$sql="DELETE FROM fac_VMInventory WHERE to_days(now())-to_days(LastUpdated)>$numDays;";
			$dbh->query($sql);
		}
	}

}
?>
