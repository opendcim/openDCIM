<?php
	/*
	Multi-processing version of PDU Polling Script

	Not truly multi-threaded, as that requires the PECL pthreads extension, which in turns requires manual
	compilation.  Instead, we will use multi-processing techniques and the pcntl_ set of intrinsic PHP functions.
	 */
	DEFINE("MAXPROCESS", 10);

	require_once("db.inc.php");
	require_once("facilities.inc.php");

	global $dbh;

	function OSS_SNMP_Lookup($dev,$snmplookup,$oid=null){
		// This is find out the name of the function that called this to make the error logging more descriptive
		$caller="OSS_SNMP_Lookup";
		$failed = false;

		$snmpHost=new OSS_SNMP\SNMP($dev->PrimaryIP,$dev->SNMPCommunity,$dev->SNMPVersion,$dev->v3SecurityLevel,$dev->v3AuthProtocol,$dev->v3AuthPassphrase,$dev->v3PrivProtocol,$dev->v3PrivPassphrase);
		$snmpresult=false;
		try {
			$snmpresult=(is_null($oid))?$snmpHost->useSystem()->$snmplookup(true):$snmpHost->get($oid);
		}catch (Exception $e){
			IncrementFailures( $dev->DeviceID );
			$failed = true;
			error_log("PowerDistribution::$caller($dev->DeviceID) ".$e->getMessage());
		}

		if ( ! $failed ) {
			ResetFailures( $dev->DeviceID );
		}
		return $snmpresult;
	}

	function BasicTests( $DeviceID ) {
		global $config;

		// First check if the SNMP library is present
		if(!class_exists('OSS_SNMP\SNMP')){
			return false;
		}

		$dev=New Device();
		$dev->DeviceID=$DeviceID;

		// Make sure this is a real device and has an IP set
		if(!$dev->GetDevice()){return false;}
		if($dev->PrimaryIP==""){return false;}

		// If the device doesn't have an SNMP community set, check and see if we have a global one
		$dev->SNMPCommunity=($dev->SNMPCommunity=="")?$config->ParameterArray["SNMPCommunity"]:$dev->SNMPCommunity;

		// We've passed all the repeatable tests, return the device object for digging
		return $dev;
	}

	function IncrementFailures( $DeviceID ){
		global $dbh;

		if($DeviceID==0){return false;}
		
		$sql="UPDATE fac_Device SET SNMPFailureCount=SNMPFailureCount+1 WHERE DeviceID=$DeviceID";
		
		if(!$dbh->query($sql)){
			error_log( "Device::IncrementFailures::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			return true;
		}
	}
	
	function ResetFailures( $DeviceID ){
		global $dbh;

		if($DeviceID==0){return false;}

		$sql="UPDATE fac_Device SET SNMPFailureCount=0 WHERE DeviceID=$DeviceID";
		
		if(!$dbh->query($sql)){
			error_log( "Device::ResetFailures::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			return true;
		}
	}
	
	/*
	Get a result set of all PDUs that meet the criteria for polling
	 */
	$sql="SELECT a.PDUID, d.SNMPVersion, b.Multiplier, b.OID1, 
			b.OID2, b.OID3, b.ProcessingProfile, b.Voltage, c.SNMPFailureCount FROM fac_PowerDistribution a, 
			fac_CDUTemplate b, fac_Device c, fac_DeviceTemplate d WHERE a.PDUID=c.DeviceID and a.TemplateID=b.TemplateID 
			AND a.TemplateID=d.TemplateID AND b.Managed=true AND c.PrimaryIP>'' and c.SNMPFailureCount<3";
	$st = $dbh->prepare($sql);
	$st->execute();

	$procNum = 0;

	// Because this is a forking process, we have to load the result set of total PDU's to poll into an array and close the db before forking,
	// otherwise the first child that exits will end up closing the db handler, as all file descriptors present in the parent are shared by all children.
	// The solution is to open the db separately in each child process, so that it has a unique descriptor.
	$pduList = array();
	while( $row = $st->fetch()){
		if ( !$dev=BasicTests($row["PDUID"])) {
			// This device fails the basic tests of what's minimal in order to make sense of or to complete an SNMP poll, so skip it completely
			continue;
		}

		array_push( $pduList, $row );
	}

	$dbh = null;

	$pidsCount = 0;
	for ( $i = 0; $i < MAXPROCESS; $i++ ) {
		$pids[$pidsCount] = pcntl_fork();
		if ( $pids[$pidsCount]) {
			$pidsCount++;
			// I am the parent
		} else {
			// I am the child
			include( "db.inc.php" );
			processPDUList( $pduList, $i, MAXPROCESS );
			exit();
		}
	}

	for( $i = 0; $i < $pidsCount; $i++ ) {
		pcntl_waitpid($pids[$i], $status, WUNTRACED);
	}

	function processPDUList( $list, $start, $increment ) {
		global $dbh;
		global $config;

		$dev = new Device();
		$cdu = new PowerDistribution();

		for ( $n = $start; $n < sizeof( $list ); $n = $n + $increment ) {
				$row = $list[$n];

				// Just send back zero if we don't get a result.
				$pollValue1=$pollValue2=$pollValue3=0;

				// This stuff is normally done by BasicTests() but for this version we called it outside of the loop
				// So just a couple of parts of that function need to be reexecuted
				$dev->DeviceID = $row["PDUID"];
				$dev->GetDevice();

				// If the device doesn't have an SNMP community set, check and see if we have a global one
				$dev->SNMPCommunity=($dev->SNMPCommunity=="")?$config->ParameterArray["SNMPCommunity"]:$dev->SNMPCommunity;	

				$pollValue1=floatval(OSS_SNMP_Lookup($dev,null,$row["OID1"]));
				// We won't use OID2 or 3 without the other so make sure both are set or just ignore them
				if($row["OID2"]!="" && $row["OID3"]!=""){
					$pollValue2=floatval(OSS_SNMP_Lookup($dev,null,$row["OID2"]));
					$pollValue3=floatval(OSS_SNMP_Lookup($dev,null,$row["OID3"]));
					// Negativity test, it is required for APC 3ph modular PDU with IEC309-5W wires
					if ($pollValue2<0) $pollValue2=0;
					if ($pollValue3<0) $pollValue3=0;
				}
					
				// Have to reset this every time, otherwise the exec() will append
				unset($statsOutput);
				$amps=0;
				$watts=0;
					
				$threeOIDs = array("Combine3OIDAmperes","Convert3PhAmperes","Combine3OIDWatts");
				if((in_array($row["ProcessingProfile"], $threeOIDs) && ($pollValue1 || $pollValue2 || $pollValue3)) || $pollValue1){
					// The multiplier should be an int but no telling what voodoo the db might cause
					$multiplier=floatval($row["Multiplier"]);
					$voltage=intval($row["Voltage"]);

					switch ( $row["ProcessingProfile"] ) {
						case "SingleOIDAmperes":
							$amps=$pollValue1/$multiplier;
							$watts=$amps * $voltage;
							break;
						case "Combine3OIDAmperes":
							$amps=($pollValue1 + $pollValue2 + $pollValue3) / $multiplier;
							$watts=$amps * $voltage;
							break;
						case "Convert3PhAmperes":
							// OO does this next formula need another set of () to be clear?
							$amps=($pollValue1 + $pollValue2 + $pollValue3) / $multiplier / 3;
							$watts=$amps * 1.732 * $voltage;
							break;
						case "Combine3OIDWatts":
							$watts=($pollValue1 + $pollValue2 + $pollValue3) / $multiplier;
							break;
						default:
							$watts=$pollValue1 / $multiplier;
							break;
					}
				}
					
				$sql="INSERT INTO fac_PDUStats SET PDUID={$row["PDUID"]}, Wattage=$watts, 
					LastRead=now() ON DUPLICATE KEY UPDATE Wattage=$watts, LastRead=now();";
				if(!$dbh->query($sql)){
					$info=$dbh->errorInfo();
					error_log("Poll_PDU_Stats-Multiprocess::PDO Error: {$info[2]} SQL=$sql");
				}
	}
				$cdu->PDUID = $row["PDUID"];
				if($ver=$cdu->GetSmartCDUVersion()){
					$sql="UPDATE fac_PowerDistribution SET FirmwareVersion=\"$ver\" WHERE PDUID=$cdu->PDUID;";
					if(!$dbh->query($sql)){
						$info=$dbh->errorInfo();
						error_log("Poll_PDU_Stats-Multiprocess::PDO Error: {$info[2]} SQL=$sql");
					}
				}
		}

?>
