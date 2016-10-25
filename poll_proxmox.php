<?php

require_once 'db.inc.php';
require_once 'vendor/autoload.php';
require_once 'facilities.inc.php';

use ProxmoxVE\Credentials;
use ProxmoxVE\Proxmox;

global $dbh;

$d = new Device;

$d->Hypervisor = "ProxMox";
$devList = $d->Search();

foreach( $devList as $dev ) {
	// Establish credentials for this particular device

	$credentials = [
		'hostname' => $dev->PrimaryIP,
		'username' => $dev->v3AuthPassphrase,
		'password' => $dev->v3PrivPassphrase
	];
	try {
		$proxmox = new Proxmox($credentials);
		$vmList = $proxmox->get('/nodes/' . $dev->SNMPCommunity . '/qemu' );
	} 
	catch( Exception $e ) {
		error_log( "Unable to poll ProxMox for inventory.  DeviceID=" . $dev->DeviceID );
		exit;
	}

	$st = $dbh->prepare( "insert into fac_VMInventory set DeviceID=:DeviceID, LastUpdated=:LastUpdated, vmID=:vmID, vmState=:vmState, vmName=:vmName ON DUPLICATE KEY Update DeviceID=:DeviceID, vmState=:vmState, LastUpdated=:LastUpdated" );

	foreach ( $vmList['data'] as $vm ) {
		$parameters = array( ":DeviceID"=>$dev->DeviceID, ":LastUpdated"=>date("Y-m-d H:i:s"), ":vmID"=>$vm['vmid'], ":vmState"=>$vm['status'], ":vmName"=>$vm['name'] );

		$st->execute( $parameters );	
	}
}
?>