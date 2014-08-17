<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	/*
	 * Initial conversion from fac_PowerConnection to fac_PowerPorts
	 * to lay the groundwork for conversion of the CDUs to standard devices
	 * We'll handle this for release in the install.php
	 * this is meant for the dev branches only to do an immediate conversion.
	 */

	// Store a list of existing CDU ids and their converted DeviceID
	$ConvertedCDUs=array();
	// Store a list of the cdu template ids and the number of power connections they support
	$CDUTemplates=array();

	// Create new devices from existing CDUs
	$sql="SELECT * FROM fac_PowerDistribution;";
	foreach($dbh->query($sql) as $row){
		$dev=new Device();
		$dev->Label=$row['Label'];
		$dev->Cabinet=$row['CabinetID'];
		$dev->TemplateID=$row['TemplateID'];
		$dev->PrimaryIP=$row['IPAddress'];
		$dev->SNMPCommunity=$row['SNMPCommunity'];
		$dev->Position=0;
		$dev->Height=0;
		$dev->Ports=1;
		if(!isset($CDUTemplates[$dev->TemplateID])){
			$CDUTemplates[$dev->TemplateID]=$dbh->query("SELECT NumOutlets FROM fac_CDUTemplate WHERE TemplateID=$dev->TemplateID LIMIT 1;")->fetchColumn();
		}
		$dev->PowerSupplyCount=$CDUTemplates[$dev->TemplateID];
		$dev->PowerSupplyCount;
		$dev->DeviceType='CDU';
		$ConvertedCDUs[$row['PDUID']]=$dev->CreateDevice();

	}

	// We need to get a list of all existing power connections

	$sql="SELECT * FROM fac_PowerConnection;";
	foreach($dbh->query($sql) as $row){
		$PowerPort=new stdClass();
		$PowerPort->ConnectedDeviceID=$ConvertedCDUs[$row['PDUID']];
		$PowerPort->ConnectedPort=$row['PDUPosition'];
		$PowerPort->DeviceID=$row['DeviceID'];
		$PowerPort->PortNumber=$row['DeviceConnNumber'];
	}
