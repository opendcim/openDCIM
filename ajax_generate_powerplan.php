<?php
require_once "db.inc.php";
require_once "facilities.inc.php";

header('Content-Type: text/html; charset=utf-8');

$cabinetid = intval($_POST['cabinetid'] ?? 0);
$mode      = sanitize($_POST['mode'] ?? 'balanced');

$person = new People();
$person->GetUserRights();

$pduList  = PowerDistribution::GetPDUbyCabinet($cabinetid);
$pduCount = is_array($pduList) ? count($pduList) : 0;

if ($pduCount == 0) {
	echo '<div class="alert alert-warning">'.__("⚠ No PDU detected in this cabinet.").'</div>';
	exit;
}
if ($mode === 'dualpath' && $pduCount < 2) {
	echo '<div class="alert alert-warning">'.__("⚠ Dual Power Path mode requires at least two PDUs.").'</div>';
	exit;
}

$devices = Device::GetDevicesByCabinet($cabinetid);
if (empty($devices)) {
	echo '<div class="alert alert-info">'.__("ℹ No devices detected in this cabinet.").'</div>';
	exit;
}

// ---------- Helpers ----------

// a) free power ports on a PDU, including ConnectorID/PhaseID/VoltageID
function getFreePowerPortsForPDUID($pduid){
	$pp = new PowerPorts();
	$ports = $pp->getPortList($pduid); // PowerPorts[] indexed by PortNumber
	$free  = [];
	foreach($ports as $n => $port){
		if(intval($port->ConnectedDeviceID) === 0){
			$free[$n] = $port; // keep full object with ConnectorID/PhaseID/VoltageID
		}
	}
	return $free;
}

// b) get device inlet requirements (array of device power inlets still free, with ConnectorID/VoltageID)
//    We read on the DEVICE side to know what connector/voltage it expects.
function getDeviceFreeInlets($deviceID){
	$dp = new DevicePorts(); // class provided in your repo
	$ports = $dp->getPortList($deviceID); // returns array of ports incl. power inlets
	$free = [];
	foreach($ports as $n => $port){
		// We only consider POWER inlets, not data; rely on class typing if present, else heuristic:
		if(property_exists($port,'PortType') && stripos($port->PortType,'power')===false){
			continue; // not a power inlet
		}
		if(intval($port->ConnectedDeviceID) === 0){
			$free[$n] = $port; // should carry ConnectorID/VoltageID if schema supports
		}
	}
	// Fallback: if DevicePorts doesn't carry ConnectorID/VoltageID, return empty -> no strict filter on device side
	return $free;
}

// c) choose best (PDU,Port) strictly compatible with given device inlet requirements,
//    and balancing the phase total load (lower is better).
function pickBestPduPortStrict($targetsPDU, $pduState, $requiredConnectorID, $requiredVoltageID, &$phaseLoad){
	$best = null;
	$bestScore = PHP_INT_MAX;
	$chosenPhase = 0;

	foreach($targetsPDU as $pdu){
		$PDUID = $pdu->PDUID;
		if(!isset($pduState[$PDUID])) continue;
		foreach($pduState[$PDUID]['free'] as $pn => $portObj){
			// Strict filter: connector + voltage must match if device specified something
			if($requiredConnectorID && intval($portObj->ConnectorID) !== intval($requiredConnectorID)) continue;
			if($requiredVoltageID   && intval($portObj->VoltageID)   !== intval($requiredVoltageID))   continue;

			$ph = intval($portObj->PhaseID) ?: 0;
			$score = intval($phaseLoad[$ph] ?? 0);
			if($score < $bestScore){
				$bestScore  = $score;
				$best       = ['pdu'=>$pdu,'port'=>$pn,'phase'=>$ph];
			}
		}
	}
	return $best; // null if none
}

// ---------- Build PDU state (free ports) ----------
$pduState = []; // PDUID => ['obj'=>PowerDistribution, 'free'=>[port#=>PowerPorts]]
foreach($pduList as $pdu){
	$pduState[$pdu->PDUID] = [
		'obj'  => $pdu,
		'free' => getFreePowerPortsForPDUID($pdu->PDUID)
	];
}

// Phase load accumulator (unknown phase = 0 bucket)
$phaseLoad   = [];            // phaseID => watts
$phaseLabels = [1=>'A',2=>'B',3=>'C'];
$planRows    = [];
$hasMonoFeed = false;

// ---------- Main loop over devices ----------
foreach($devices as $dev){
	if(!in_array($dev->DeviceType, ['Server','Switch','Appliance','Chassis','Storage Array'])){ continue; }

	$feeds = max(1, intval($dev->PowerSupplyCount));
	$power = intval($dev->Power);
	$hasMonoFeed = $hasMonoFeed || ($feeds == 1);

	// read FREE inlets on the DEVICE to know expected Connector/Voltage (strict)
	$deviceFreeInlets = getDeviceFreeInlets($dev->DeviceID);

	// Resolve how many feeds we request in this mode
	$requestedFeeds = ($mode === 'dualpath') ? min(2, $feeds) : $feeds;

	// What PDUs are eligible for each feed (depends on mode)
	$targets = array_values($pduList); // default: all
	if($mode === 'balanced' && count($targets) >= 2){
		// We'll alternate [0],[1],[0],[1] ...
	} elseif($mode === 'dualpath' && count($targets) >= 2){
		// feed 1 -> [0], feed 2 -> [1]
	} else {
		// intelligent: consider all targets each time, scoring by phaseLoad
	}

	$assigned = [];
	for($f=0; $f<$requestedFeeds; $f++){
		// Pick a device FREE inlet compatible (for this feed)
		$reqConnectorID = null;
		$reqVoltageID   = null;

		if(!empty($deviceFreeInlets)){
			// take one free inlet and use its requirements
			$inletKey = array_key_first($deviceFreeInlets);
			$inlet    = $deviceFreeInlets[$inletKey];
			unset($deviceFreeInlets[$inletKey]);
			if(property_exists($inlet,'ConnectorID') && $inlet->ConnectorID){ $reqConnectorID = intval($inlet->ConnectorID); }
			if(property_exists($inlet,'VoltageID')   && $inlet->VoltageID){   $reqVoltageID   = intval($inlet->VoltageID); }
		}
		// If device side doesn't expose requirements, req* stay null -> no strict filter on that axis.

		// Determine eligible PDU set for this feed based on mode
		$eligiblePDU = $targets;
		if($mode === 'balanced' && count($targets) >= 2){
			$eligiblePDU = [$targets[$f % 2]];
		}
		if($mode === 'dualpath' && count($targets) >= 2){
			$eligiblePDU = [$targets[min($f,1)]];
		}

		// Pick best PDU/Port strictly matching connector/voltage + min phase load
		$best = pickBestPduPortStrict($eligiblePDU, $pduState, $reqConnectorID, $reqVoltageID, $phaseLoad);

		if($best){
			$assigned[] = $best;
			// reserve the chosen port
			unset($pduState[$best['pdu']->PDUID]['free'][$best['port']]);
			// update phase load (W divided across feeds of this device)
			$ph = intval($best['phase']) ?: 0;
			$phaseLoad[$ph] = ($phaseLoad[$ph] ?? 0) + ($power / max(1,$requestedFeeds));
		}else{
			// No compatible outlet found for this feed
			$niceConn = ($reqConnectorID) ? sprintf(__("connector #%d"), $reqConnectorID) : __("any connector");
			$niceVolt = ($reqVoltageID)   ? sprintf(__("voltage #%d"),   $reqVoltageID)   : __("any voltage");
			$planRows[] = [
				'Device'=>$dev->Label,
				'Error'=>sprintf(__("⚠ No compatible outlet found for this device feed (%s, %s)."), $niceConn, $niceVolt)
			];
		}
	}

	if(!empty($assigned)){
		foreach($assigned as $a){
			$planRows[] = [
				'Device'   => $dev->Label,
				'DeviceID' => $dev->DeviceID,
				'PDU'      => $a['pdu']->Label,
				'PDUID'    => $a['pdu']->PDUID,
				'Port'     => $a['port']
			];
		}
	}
}

// ---------- HTML Render ----------
echo "<h4>".__("Proposed Power Distribution Plan")."</h4>";

if ($hasMonoFeed) {
	echo '<div class="alert alert-info">'
		. __("⚠ Single-power devices detected. Installing a Static Transfer Switch (STS) is recommended to enhance power redundancy.")
		. '</div>';
}

echo "<table class='table table-striped'>
	<tr><th>".__("Device")."</th><th>".__("PDU")."</th><th>".__("Port")."</th></tr>";
foreach($planRows as $r){
	if(isset($r['Error'])){
		echo "<tr><td>{$r['Device']}</td><td colspan=2><span class='error'>{$r['Error']}</span></td></tr>";
	} else {
		echo "<tr><td>{$r['Device']}</td><td>{$r['PDU']}</td><td>{$r['Port']}</td></tr>";
	}
}
echo "</table>";

echo "<fieldset><legend>".__("Phase Load Summary")."</legend><ul>";
foreach($phaseLoad as $ph => $load){
	$label = $phaseLabels[$ph] ?? __("Unknown");
	echo "<li>".sprintf(__("Phase %s"), $label).": ".number_format($load,1)." W</li>";
}
echo "</ul></fieldset>";

// ---------- Phase Load Visual Summary ----------
$totalPower = array_sum($phaseLoad);
$maxPhaseLoad = ($totalPower > 0) ? max($phaseLoad) : 0;

// Définir couleurs dynamiques selon % de charge max
function phaseColor($percent){
	if($percent > 80){ return "#f44336"; }   // rouge
	if($percent > 60){ return "#ffc107"; }   // jaune
	return "#4caf50";                        // vert
}

echo "<fieldset><legend>".__("Visual Load Summary")."</legend>";
echo "<table class='phase-load-table' style='width:100%; border-collapse:collapse;'>";

foreach($phaseLoad as $ph => $load){
	$label = $phaseLabels[$ph] ?? __("Unknown");
	$percent = ($maxPhaseLoad > 0) ? round(($load / $maxPhaseLoad) * 100) : 0;
	$color = phaseColor($percent);

	echo "<tr>
			<td style='width:60px; font-weight:bold;'>Phase $label</td>
			<td style='width:80%; background:#eee; border-radius:5px;'>
				<div style='height:12px; width:{$percent}%; background:{$color}; border-radius:5px;'></div>
			</td>
			<td style='width:60px; text-align:right;'>".number_format($load,0)." W</td>
		</tr>";
}

echo "</table>";

if($totalPower > 0){
	echo "<p style='text-align:center; margin-top:8px; font-weight:bold;'>"
		.sprintf(__("Total Estimated Power: %.1f W"), $totalPower)
		."</p>";
}

echo "</fieldset>";
// person write access
echo "<div class='center'>";
if($person->WriteAccess()){
	echo "<button id='btnApplyPowerPlan' class='btn btn-success'>".__("Apply and Save")."</button>";
} else {
	echo "<div class='alert alert-info'>".__("Read-only mode: preview and print only.")."</div>";
}
echo " <button onclick='window.print()' class='btn btn-secondary'>".__("Print Power Plan")."</button></div>";

$_SESSION["auto_plan_$cabinetid"] = $planRows;

