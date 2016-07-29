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

class ESX {
	/*	ESX:	VMWare ESX has the ability to query via SNMP the virtual machines hosted
				on a device.  This allows an inventory of virtual machines to be created,
				and departments and contacts can be assigned to them, just as you can a
				physical system.
				
				Unfortunately Microsoft Hyper-V does not support SNMP queries for VM
				inventory, and the only remote access is through PowerShell, which is
				only supported on Windows systems.  Therefore, no support for Hyper-V is
				in this software.
				
				Any other virtualization technology that supports SNMP queries should be easy
				to add.
	*/
	var $VMIndex;
	var $DeviceID;
	var $LastUpdated;
	var $vmID;
	var $vmName;
	var $vmState;
	var $Owner;
	var $PrimaryContact;
  
	static function RowToObject($dbRow){
		/*
		 * Generic function that will take any row returned from the fac_VMInventory
		 * table and convert it to an object for use in array or other
		 */

		$vm=new ESX();
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
			$vmList[$vmCount]=ESX::RowToObject($row);
			$vmCount++;
		}

		return $vmList;
	}

	static function EnumerateVMs($d,$debug=false){
		if(!$dev=Device::BasicTests($d->DeviceID)){
			// This device failed the basic tests
			return false;
		}

		$namesList=Device::OSS_SNMP_Walk($dev,null,".1.3.6.1.4.1.6876.2.1.1.2");
		$statesList=Device::OSS_SNMP_Walk($dev,null,".1.3.6.1.4.1.6876.2.1.1.6");

		$vmList=array();

		if(is_array($namesList) && count($namesList)>0 && count($namesList)==count($statesList)){
			$tempList=array_combine($namesList,$statesList);
		}else{
			$tempList=array();
		}

		if(count($tempList)){
			if($debug){
				printf("\t%d VMs found\n", count($tempList));
			}

			foreach($tempList as $name => $state){
				$vm=new ESX();
				$vm->DeviceID=$dev->DeviceID;
				$vm->LastUpdated=date( 'Y-m-d H:i:s' );
				$vm->vmID=count($vmList);
				$vm->vmName=trim(str_replace('"','',@end(explode(":",$name))));
				$vm->vmState=trim(str_replace('"','',@end(explode(":",$state))));
				$vmList[]=$vm;
			}
		}

		return $vmList;
	}
  
	function UpdateInventory($debug=false){
		$dev=new Device();

		$devList=$dev->GetESXDevices();

		foreach($devList as $esxDev){
			if($debug){
				print "Querying host $esxDev->Label @ $esxDev->PrimaryIP...\n";
			}

			if ( $esxDev->SNMPFailureCount < 3 ) {
				$vmList = ESX::RefreshInventory( $esxDev, $debug );
			}

			if($debug){
				print_r($vmList);
			}
		}
	}
  
	static function RefreshInventory( $ESXDevice, $debug = false ) {
		global $dbh;

		$dev = new Device();
		if ( is_object( $ESXDevice ) ) {
			$dev->DeviceID = $ESXDevice->DeviceID;
		} else {
			$dev->DeviceID = $ESXDevice;
		}
		$dev->GetDevice();
		
		$search = $dbh->prepare( "select * from fac_VMInventory where vmName=:vmName" );
		$update = $dbh->prepare( "update fac_VMInventory set DeviceID=:DeviceID, LastUpdated=:LastUpdated, vmID=:vmID, vmState=:vmState where vmName=:vmName" );
		$insert = $dbh->prepare( "insert into fac_VMInventory set DeviceID=:DeviceID, LastUpdated=:LastUpdated, vmID=:vmID, vmState=:vmState, vmName=:vmName" );
		
		$vmList = ESX::EnumerateVMs( $dev, $debug );
		if ( count( $vmList ) > 0 ) {
			foreach( $vmList as $vm ) {
				$search->execute( array( ":vmName"=>$vm->vmName ) );
				
				$parameters = array( ":DeviceID"=>$vm->DeviceID, ":LastUpdated"=>$vm->LastUpdated, ":vmID"=>$vm->vmID, ":vmState"=>$vm->vmState, ":vmName"=>$vm->vmName );

				if ( $search->rowCount() > 0 ) {
					$update->execute( $parameters );
					if ( $debug )
						error_log( "Updating existing VM '" . $vm->vmName . "'in inventory." );
				} else {
					$insert->execute( $parameters );
					if ( $debug ) 
						error_log( "Adding new VM '" . $vm->vmName . "'to inventory." );
				}
			}
		}
		
		return $vmList;
	}
  
	function GetVMbyIndex() {
		global $dbh;

		$sql="SELECT * FROM fac_VMInventory WHERE VMIndex=$this->VMIndex;";

		if(!$vmRow=$dbh->query($sql)->fetch()){
			return false;
		}else{
			foreach(ESX::RowToObject($vmRow) as $param => $value){
				$this->$param=$value;
			}
			return true;
		}
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
