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

use ProxmoxVE\Credentials;
use ProxmoxVE\Proxmox;

class PMox {
	/*	ProxMox:	Class that contains methods, only, specific to ProxMox.

					All properties and methods that are generic to VMs are in VM.class.php
	*/

	static function EnumerateVMs($d,$debug=false){
		$vmList=array();

		// Establish credentials for this particular device

		$credentials = new Credentials( $d->PrimaryIP,
			$d->APIUsername,
			$d->APIPassword,
			$d->ProxMoxRealm,
			$d->APIPort );

		try {
			$proxmox = new Proxmox($credentials);
			$pveList = $proxmox->get('/nodes/' . $d->Label . '/qemu' );
		} 
		catch( Exception $e ) {
			error_log( "Unable to poll ProxMox for inventory.  DeviceID=" . $d->DeviceID );
			exit;
		}

		if ( sizeof( $pveList ) > 0 ) {
			foreach( $pveList["data"] as $pve ) { 
				$tmpVM = new VM;

				$tmpVM->DeviceID = $d->DeviceID;
				$tmpVM->LastUpdated = date( "Y-m-d H:i:s" );
				$tmpVM->vmID = $pve["vmid"];
				$tmpVM->vmName = $pve["name"];
				$tmpVM->vmState = $pve["status"];

				if ( $debug ) {
					error_log( "VM: " . $tmpVM->vmName . " added to device " . $d->DeviceID );
				}

				$vmList[] = $tmpVM;
			}
		}
		
		return $vmList;
	}
  
	function UpdateInventory($debug=false){
		$dev=new Device();

		$d = new Device;

		$d->Hypervisor = "ProxMox";
		$devList = $d->Search();

		foreach($devList as $pveDev){
			if($debug){
				print "Querying host $pveDev->Label @ $pveDev->PrimaryIP...\n";
			}

			if ( $pveDev->SNMPFailureCount < 3 ) {
				$vmList = PMox::RefreshInventory( $pveDev, $debug );
			}

			if($debug){
				print_r($vmList);
			}
		}
	}
  
	static function RefreshInventory( $pveDevice, $debug = false ) {
		global $dbh;
		global $config;

		$dev = new Device();
		if ( is_object( $pveDevice ) ) {
			$dev->DeviceID = $pveDevice->DeviceID;
		} else {
			$dev->DeviceID = $pveDevice;
		}
		$dev->GetDevice();
		
		$search = $dbh->prepare( "select * from fac_VMInventory where vmName=:vmName" );
		$update = $dbh->prepare( "update fac_VMInventory set DeviceID=:DeviceID, LastUpdated=:LastUpdated, vmID=:vmID, vmState=:vmState where vmName=:vmName" );
		$insert = $dbh->prepare( "insert into fac_VMInventory set DeviceID=:DeviceID, LastUpdated=:LastUpdated, vmID=:vmID, vmState=:vmState, vmName=:vmName" );
		
		$vmList = PMox::EnumerateVMs( $dev, $debug );
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

		$expire = "delete from fac_VMInventory where to_days(now())-to_days(LastUpdated)>" . intval( $config->ParameterArray['VMExpirationTime']);
		$dbh->query( $expire );
		
		return $vmList;
	}

}
?>
