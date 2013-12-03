<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	/*
	 * Initial conversion from fac_PatchConnection, fac_SwitchConnection, 
	 * fac_DevicePorts over to a single table for all ports and their
	 * connected whatevers.  We'll handle this for release in the install.php
	 * this is meant for the dev branches only to do an immediate conversion.
	 */

	// Retrieve a list of all switch and panel devices and make ports for them.

	$sql='SELECT DeviceID,Ports,DeviceType from fac_Device WHERE 
		DeviceType in ("Switch", "Patch Panel") AND Ports>0;';

	foreach($dbh->query($sql) as $row){
		for($x=1;$x<=$row['Ports'];$x++){
			// Create a port for every device
			$ports[$row['DeviceID']][$x]["Label"] = $x;
			if($row['DeviceType']=='Patch Panel'){
				// Patch panels needs rear ports as well
				$ports[$row['DeviceID']][-$x]["Label"]=$x;
			}
		}
	}
	
	// Get a list of all non-switch and non-patch panel devices
	$sql = "select DeviceID, Ports, DeviceType from fac_Device WHERE
		DeviceType NOT IN ('Physical Infrastructure', 'Switch', 'Patch Panel')";
	$swCount = $dbh->prepare( "select * from fac_SwitchConnection where EndpointDeviceID=:deviceid order by EndpointPort ASC" );
	$ppCount = $dbh->prepare( "select * from fac_PatchConnection where FrontEndpointDeviceID=:deviceid order by FrontEndpointPort ASC" );
	$portHash = array();
	
	$dev = new Device();
	
	foreach ( $dbh->query( $sql ) as $row ) {
		$swCount->execute( array( ":deviceid" => $row["DeviceID"] ) );
		$totalPorts = $swCount->rowCount();
		
		$ppCount->execute( array( ":deviceid" => $row["DeviceID"] ) );
		$totalPorts += $ppCount->rowCount();
		
		$dev->DeviceID = $row["DeviceID"];
		$dev->GetDevice();
		if ( $dev->Ports < $totalPorts ) {
			$dev->Ports = $totalPorts;
			$dev->UpdateDevice();
		}
		
		while ( $conRow = $swCount->fetch() ) {
			$pNum = sizeof( $ports[$row["DeviceID"]] ) + 1;
			$ports[$row["DeviceID"]][$pNum] = array();
			$ports[$row["DeviceID"]][$pNum]["Label"] = $conRow["EndpointPort"];
			$ports[$row["DeviceID"]][$pNum]["Notes"] = $conRow["Notes"];
			$ports[$row["DeviceID"]][$pNum]["Connected Device"] = $conRow["SwitchDeviceID"];
			$ports[$row["DeviceID"]][$pNum]["Connected Port"] = $conRow["SwitchPortNumber"];
			$portHash[$row["DeviceID"] . "-" . $conRow["EndpointPort"]] = $pNum;
			$ports[$conRow["SwitchDeviceID"]][$conRow["SwitchPortNumber"]]["Notes"] = $conRow["Notes"];
			$ports[$conRow["SwitchDeviceID"]][$conRow["SwitchPortNumber"]]["Connected Device"] = $row["DeviceID"];
			$ports[$conRow["SwitchDeviceID"]][$conRow["SwitchPortNumber"]]["Connected Port"] = $pNum;
			printf( "Created port %d (%d) for device %d connected to Switch %d Port %d<br>\n", $pNum, $conRow["EndpointPort"], $row["DeviceID"], $conRow["SwitchDeviceID"], $conRow["SwitchPortNumber"] );
		}

		while ( $conRow = $ppCount->fetch() ) {
			$pNum = sizeof( $ports[$row["DeviceID"]] ) + 1;
			$ports[$row["DeviceID"]][$pNum] = array();
			$ports[$row["DeviceID"]][$pNum]["Label"] = $conRow["FrontEndpointPort"];
			$ports[$row["DeviceID"]][$pNum]["Notes"] = $conRow["FrontNotes"];
			$ports[$row["DeviceID"]][$pNum]["Connected Device"] = $conRow["PanelDeviceID"];
			$ports[$row["DeviceID"]][$pNum]["Connected Port"] = $conRow["PanelPortNumber"];
			$portHash[$row["DeviceID"] . "-" .$conRow["FrontEndpointPort"]] = $pNum;
			$ports[$conRow["PanelDeviceID"]][$conRow["PanelPortNumber"]]["Notes"] = $conRow["FrontNotes"];
			$ports[$conRow["PanelDeviceID"]][$conRow["PanelPortNumber"]]["Connected Device"] = $row["FrontEndpointDeviceID"];
			$ports[$conRow["PanelDeviceID"]][$conRow["PanelPortNumber"]]["Connected Port"] = $pNum;
			printf( "Created patch panel port %d (%d) for device %d<br>\n", $pNum, $conRow["FrontEndpointPort"], $row["DeviceID"] );
		}
	}
	
	foreach($dbh->query('SELECT * FROM fac_PatchConnection;') as $row){
		// Read all the patch connections again to get the rear connection info 
		$ports[$row['RearEndpointDeviceID']][-$row['RearEndpointPort']]['Connected Device']=$row['PanelDeviceID'];
		$ports[$row['RearEndpointDeviceID']][-$row['RearEndpointPort']]['Connected Port']=-$row['PanelPortNumber'];
		$ports[$row['RearEndpointDeviceID']][-$row['RearEndpointPort']]['Notes']=$row['RearNotes'];
		$ports[$row['PanelDeviceID']][-$row['PanelPortNumber']]['Connected Device']=$row['RearEndpointDeviceID'];
		$ports[$row['PanelDeviceID']][-$row['PanelPortNumber']]['Connected Port']=-$row['RearEndpointPort'];
		$ports[$row['PanelDeviceID']][-$row['PanelPortNumber']]['Notes']=$row['RearNotes'];
		$ports[$row['FrontEndpointDeviceID']][$row['FrontEndpointPort']]['Connected Device']=$row['PanelDeviceID'];
		$ports[$row['FrontEndpointDeviceID']][$row['FrontEndpointPort']]['Connected Port']=$row['PanelPortNumber'];
		$ports[$row['FrontEndpointDeviceID']][$row['FrontEndpointPort']]['Notes']=$row['FrontNotes'];
		$ports[$row['PanelDeviceID']][$row['PanelPortNumber']]['Connected Device']=$row['FrontEndpointDeviceID'];
		$ports[$row['PanelDeviceID']][$row['PanelPortNumber']]['Connected Port']=$row['FrontEndpointPort'];
		$ports[$row['PanelDeviceID']][$row['PanelPortNumber']]['Notes']=$row['FrontNotes'];	}

	// All the ports should be in the array now, use the prepared statement to load them all
	$populate = $dbh->prepare('INSERT INTO fac_Ports VALUES ( :deviceid, :portnumber, :label, 0, 0, "", :cdeviceid, :cport, :notes )
		ON DUPLICATE KEY UPDATE Label=:label, ConnectedDeviceID=:cdeviceid, ConnectedPort=:cport, Notes=:notes' );
print_r($ports);
	foreach($ports as $deviceid => $row){
		printf( "Saving %d ports for device %d.<br>\n", sizeof( $row ), $deviceid );
		foreach($row as $portnum => $port){
			$cdevice=(isset($port['Connected Device']))?$port['Connected Device']:null;
			$label=(isset($port['Label']))?$port['Label']:'';
			$cport=(isset($port['Connected Port']))?$port['Connected Port']:null;
			$notes=(isset($port['Notes']))?$port['Notes']:'';
			
//			$populate->execute( array( ":deviceid" => $deviceid, ":portnumber" => $portnum, ":label" => $label,
//				":cdeviceid" => $cdevice, ":cport" => $cport, ":notes" => $notes ) );
		}
	}
	
	printf( "\n<p>Conversion completed.</p>\n" );

?>
