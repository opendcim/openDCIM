<?php
require_once "db.inc.php";
require_once "facilities.inc.php";

header('Content-Type: application/json; charset=utf-8');

$cabinetid = intval($_POST['cabinetid'] ?? 0);
$plan      = $_SESSION["auto_plan_$cabinetid"] ?? [];

$person = new People();
$person->GetUserRights();

if (!$person->WriteAccess()) {
	echo json_encode(["error" => __("You do not have permission to apply this plan.")]);
	exit;
}

$pp = new PowerPorts();
$dp = new DevicePorts();

$ok = 0; $err = [];

foreach($plan as $row){
	if(isset($row['Error'])){ continue; }

	// 1) Read PDU chosen port to know ConnectorID/VoltageID constraints
	$pduPorts = $pp->getPortList($row['PDUID']);
	if(!isset($pduPorts[$row['Port']])){
		$err[] = $row['Device']." – ".__("target PDU port no longer available");
		continue;
	}
	$pduPortObj = $pduPorts[$row['Port']];
	$needConnID = property_exists($pduPortObj,'ConnectorID') ? intval($pduPortObj->ConnectorID) : null;
	$needVoltID = property_exists($pduPortObj,'VoltageID')   ? intval($pduPortObj->VoltageID)   : null;

	// 2) Pick a FREE device inlet compatible with PDU port
	$devPorts = $dp->getPortList($row['DeviceID']);
	$devInletObj = null;
	foreach($devPorts as $n => $dport){
		// only power inlets
		if(property_exists($dport,'PortType') && stripos($dport->PortType,'power')===false) continue;
		if(intval($dport->ConnectedDeviceID)!=0) continue;

		// strict match if device exposes ConnectorID/VoltageID, else accept
		$okConn = true; $okVolt = true;
		if($needConnID && property_exists($dport,'ConnectorID') && $dport->ConnectorID){
			$okConn = (intval($dport->ConnectorID) === $needConnID);
		}
		if($needVoltID && property_exists($dport,'VoltageID') && $dport->VoltageID){
			$okVolt = (intval($dport->VoltageID) === $needVoltID);
		}
		if($okConn && $okVolt){ $devInletObj = $dport; break; }
	}
	if(!$devInletObj){
		$err[] = $row['Device']." – ".__("no free compatible power inlet on device");
		continue;
	}

	// 3) Make the power connection (device inlet <-> selected PDU port)
	if(!$pp->makeConnection($devInletObj, $pduPortObj)){
		$err[] = $row['Device']." – ".__("failed to make power connection");
		continue;
	}

	LogActions::Insert('Device', $row['DeviceID'], 'AutoLink', 'PDU', '', $row['PDUID']);
	$ok++;
}

if($err){
	echo json_encode(["partial"=>true, "applied"=>$ok, "errors"=>$err]);
} else {
	echo json_encode(["success"=>true, "applied"=>$ok]);
}
