<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	/*
	 * Initial conversion from fac_PatchConnection, fac_SwitchConnection, 
	 * fac_DevicePorts over to a single table for all ports and their
	 * connected whatevers.  We'll handle this for release in the install.php
	 * this is meant for the dev branches only to do an immediate conversion.
	 */

	// Retrieve a list of all devices and make ports for them.

	$sql='SELECT DeviceID,Ports,DeviceType from fac_Device WHERE 
		DeviceType!="Physical Infrastructure" AND Ports>0;';
	$insert=$dbh->prepare('INSERT INTO fac_Ports VALUES ( :deviceid, :portnumber, 
		"", 0, 0, "", :cdeviceid, :cport, :notes );');


	$errors=array();
	$ports=array();
	foreach($dbh->query($sql) as $row){
		for($x=1;$x<=$row['Ports'];$x++){
			// Create a port for every device
			$ports[$row['DeviceID']][$x]='';
			if($row['DeviceType']=='Patch Panel'){
				// Patch panels needs rear ports as well
				$ports[$row['DeviceID']][-$x]='';
			}
		}
	}

	$findswitch=$dbh->prepare('SELECT * FROM fac_SwitchConnection WHERE EndpointDeviceID=:deviceid ORDER BY EndpointPort ASC;');
	foreach($ports as $deviceid => $port){
		$findswitch->execute(array(':deviceid' => $deviceid));
		$defined=$findswitch->fetchAll();
		foreach($defined as $row){
			// Weed out any port numbers that have been defined outside the range of 
			// valid ports for the device
			if(isset($ports[$deviceid][$row['EndpointPort']])){
				// Device Ports
				$ports[$deviceid][$row['EndpointPort']]['Notes']=$row['Notes'];
				$ports[$deviceid][$row['EndpointPort']]['Connected Device']=$row['SwitchDeviceID'];
				$ports[$deviceid][$row['EndpointPort']]['Connected Port']=$row['SwitchPortNumber'];

				// Switch Ports
				$ports[$row['SwitchDeviceID']][$row['SwitchPortNumber']]['Notes']=$row['Notes'];
				$ports[$row['SwitchDeviceID']][$row['SwitchPortNumber']]['Connected Device']=$row['EndpointDeviceID'];
				$ports[$row['SwitchDeviceID']][$row['SwitchPortNumber']]['Connected Port']=$row['EndpointPort'];

			}else{
				// Either display this as a log item later or possibly backfill empty 
				// ports with this data
				$errors[$deviceid][$row['EndpointPort']]['Notes']=$row['Notes'];
				$errors[$deviceid][$row['EndpointPort']]['Connected Device']=$row['SwitchDeviceID'];
				$errors[$deviceid][$row['EndpointPort']]['Connected Port']=$row['SwitchPortNumber'];
			}
		}
	}

	$findpatch=$dbh->prepare('SELECT * FROM fac_PatchConnection WHERE FrontEndpointDeviceID=:deviceid ORDER BY FrontEndpointPort ASC;');
	foreach($ports as $deviceid => $port){
		$findpatch->execute(array(':deviceid' => $deviceid));
		$defined=$findpatch->fetchAll();
		foreach($defined as $row){
			// Weed out any port numbers that have been defined outside the range of 
			// valid ports for the device
			if(isset($ports[$deviceid][$row['FrontEndpointPort']])){
				// Connect the device to the panel
				$ports[$deviceid][$row['FrontEndpointPort']]['Notes']=$row['FrontNotes'];
				$ports[$deviceid][$row['FrontEndpointPort']]['Connected Device']=$row['PanelDeviceID'];
				$ports[$deviceid][$row['FrontEndpointPort']]['Connected Port']=$row['PanelPortNumber'];
				// Connect the panel to the device
				$ports[$row['PanelDeviceID']][$row['PanelPortNumber']]['Connected Device']=$row['FrontEndpointDeviceID'];
				$ports[$row['PanelDeviceID']][$row['PanelPortNumber']]['Connected Port']=$row['FrontEndpointPort'];
				$ports[$row['PanelDeviceID']][$row['PanelPortNumber']]['Notes']=$row['FrontNotes'];
			}else{
				// Either display this as a log item later or possibly backfill empty 
				// ports with this data
				$errors[$deviceid][$row['FrontEndpointPort']]['Notes']=$row['FrontNotes'];
				$errors[$deviceid][$row['FrontEndpointPort']]['Connected Device']=$row['PanelDeviceID'];
				$errors[$deviceid][$row['FrontEndpointPort']]['Connected Port']=$row['PanelPortNumber'];
			}
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
	}

print "Ports and related connections:<br>\n";
print_r($ports);
print "Errors and things that didn't quite match right:<br>\n";
print_r($errors);

	// All the ports should be in the array now, use the prepared statement to load them all
	foreach($ports as $deviceid => $row){
		foreach($row as $portnum => $port){
			$null=null;$blank="";
			$cdevice=(isset($port['Notes']))?$port['Connected Device']:null;
			$cport=(isset($port['Notes']))?$port['Connected Port']:null;
			$notes=(isset($port['Notes']))?$port['Notes']:'';

			$insert->bindParam(':cdeviceid',$cdevice);
			$insert->bindParam(':cport',$cport);
			$insert->bindParam(':notes',$notes);
			$insert->bindParam(':deviceid',$deviceid);
			$insert->bindParam(':portnumber',$portnum);

			$insert->execute();
		}
	}
?>
