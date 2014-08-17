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
	// List of ports we are going to create for every device in the system
	$PowerPorts=array();

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

	// Create list of all ports that we need to create, no need to look at children or any device with no defined power supplies
	$sql="SELECT DeviceID, PowerSupplyCount FROM fac_Device WHERE ParentDevice=0 AND PowerSupplyCount>0;";
	foreach($dbh->query($sql) as $row){
		for($x=1;$x<=$row['PowerSupplyCount'];$x++){
			$PowerPorts[$row['DeviceID']][$x]['label']=$x;
		}
	}

	// We need to get a list of all existing power connections
	$sql="SELECT * FROM fac_PowerConnection;";
	foreach($dbh->query($sql) as $row){
		// Create primary connections
		$PowerPorts[$row['DeviceID']][$row['DeviceConnNumber']]['ConnectedDeviceID']=$ConvertedCDUs[$row['PDUID']];
		$PowerPorts[$row['DeviceID']][$row['DeviceConnNumber']]['ConnectedPort']=$row['PDUPosition'];

		// Create reverse of primary
		$PowerPorts[$ConvertedCDUs[$row['PDUID']]][$row['PDUPosition']]['ConnectedDeviceID']=$row['DeviceID'];
		$PowerPorts[$ConvertedCDUs[$row['PDUID']]][$row['PDUPosition']]['ConnectedPort']=$row['DeviceConnNumber'];
	}

//	print_r($PowerPorts);
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
				$insertsql='';
			}
			$n++;
		}
	}
	//do one last insert
	$insertsql=substr($insertsql, 0, -1);// shave off that last comma
echo $insertsql;
	$dbh->exec('INSERT INTO fac_PowerPorts VALUES'.$insertsql);
