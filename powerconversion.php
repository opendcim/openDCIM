<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

$reset="DELETE FROM fac_Device WHERE DeviceID>2170;TRUNCATE TABLE fac_PowerPorts;DELETE FROM fac_Ports WHERE DeviceID>2170;";
$dbh->query($reset);
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
	// List of ports we are going to create for every device in the system
	$PowerPorts=array();
	// These should only apply to me but the possibility exists
	$PreNamedPorts=array();

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

	// Create a list of all ports that we need to create, no need to look at children or any device with no defined power supplies
	$sql="SELECT DeviceID, PowerSupplyCount FROM fac_Device WHERE ParentDevice=0 AND PowerSupplyCount>0;";
	foreach($dbh->query($sql) as $row){
		for($x=1;$x<=$row['PowerSupplyCount'];$x++){
			$PowerPorts[$row['DeviceID']][$x]['label']=$x;
		}
	}

	function workdamnit($numeric=true){
		// a PDUID of 0 is considered an error, data fragment, etc.  Fuck em, not dealing with em.
		global $dbh,$PreNamedPorts,$PowerPorts,$ConvertedCDUs;
		$sql="SELECT * FROM fac_PowerConnection;";
		foreach($dbh->query($sql) as $row){
			$port='';
			if(is_numeric($row['PDUPosition']) && $numeric && $row['PDUID']>0){
				$port=$row['PDUPosition'];
			}elseif(!is_numeric($row['PDUPosition']) && !$numeric && $row['PDUID']>0){
				$newPDUID=$ConvertedCDUs[$row['PDUID']];
				if(!isset($PreNamedPorts[$newPDUID][$row['PDUPosition']])){
					// Move the array pointer to the end of the ports array
					end($PowerPorts[$newPDUID]);
					$max=key($PowerPorts[$newPDUID]);
					++$max;
					// Create a new port for the named port, this will likely extend past the valid amount of ports on the device.
					$PowerPorts[$newPDUID][$max]['label']=$row['PDUPosition'];
					// Store a pointer between the name and new port index
					$PreNamedPorts[$newPDUID][$row['PDUPosition']]=$max;
				}
				$port=$PreNamedPorts[$newPDUID][$row['PDUPosition']];
			}
			if((is_numeric($row['PDUPosition']) && $numeric) || (!is_numeric($row['PDUPosition']) && !$numeric) && $row['PDUID']>0){
				// Create primary connections
				$PowerPorts[$row['DeviceID']][$row['DeviceConnNumber']]['ConnectedDeviceID']=$ConvertedCDUs[$row['PDUID']];
				$PowerPorts[$row['DeviceID']][$row['DeviceConnNumber']]['ConnectedPort']=$port;

				// Create reverse of primary
				$PowerPorts[$ConvertedCDUs[$row['PDUID']]][$port]['ConnectedDeviceID']=$row['DeviceID'];
				$PowerPorts[$ConvertedCDUs[$row['PDUID']]][$port]['ConnectedPort']=$row['DeviceConnNumber'];
			}
		}	
	}

	// We need to get a list of all existing power connections
	workdamnit(); // First time through setting up all numeric ports
	workdamnit(false); // Run through again but this time only deal with named ports and append them to the end of the numeric

	print_r($PowerPorts);
	$n=1; $insertsql=''; $insertlimit=100;
	foreach($PowerPorts as $DeviceID => $PowerPort){
		foreach($PowerPort as $PortNum => $PortDetails){
			$label=(isset($PortDetails['label']))?$PortDetails['label']:$PortNum;
			$cdevice=(isset($PortDetails['ConnectedDeviceID']))?$PortDetails['ConnectedDeviceID']:'NULL';
			$cport=(isset($PortDetails['ConnectedPort']))?$PortDetails['ConnectedPort']:'NULL';

			$insertsql.="($DeviceID,$PortNum,\"$label\",$cdevice,$cport,\"\")";
			if($n%$insertlimit!=0){
				$insertsql.=" ,";
			}else{
echo $insertsql;
				$dbh->exec('INSERT INTO fac_PowerPorts VALUES'.$insertsql);
print "\n";
				print_r($dbh->errorInfo());
				$insertsql='';
			}
			$n++;
		}
	}
	//do one last insert
	$insertsql=substr($insertsql, 0, -1);// shave off that last comma
echo $insertsql;
	$dbh->exec('INSERT INTO fac_PowerPorts VALUES'.$insertsql);
print "\n";
				print_r($dbh->errorInfo());
