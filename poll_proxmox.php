<?php

require_once 'vendor/autoload.php';
require_once 'facilities.inc.php';

use ProxmoxVE\Credentials;
use ProxmoxVE\Proxmox;

global $dbh;

$d = new Device;

/************************************
*		TO DO: 						*
*									*
*	Since ProxMox will start every 	*
*	cluster over with VMID = 100 	*
*	how will we keep uniqueness		*
* 	among the VM inventory.			*
************************************/

$d->Hypervisor = "ProxMox";
$devList = $d->Search();

foreach( $devList as $dev ) {
	// Establish credentials for this particular device

	$server = $dev->IPAddress;
	$user = $dev->SNMPv3Passphrase;
	$pass = $dev->SNMPv3PrivPassphrase;

	$credentials = new Credentials($server, $user, $pass);
	$proxmox = new Proxmox($credentials);
	$vmList = $proxmox->get('/nodes/' . $dev->SNMPCommunity . '/qemu' );

	foreach ( $vmList['data'] as $vm ) {
		$parameters = array( ":DeviceID"=>$dev->DeviceID, ":LastUpdated"=>date("Y-m-d H:i:s"), ":vmID"=>$vm['vmid'], ":vmState"=>$vm['status'], ":vmName"=>$vm['name'] );


	}
}
?>