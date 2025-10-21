<?php
if (session_status() === PHP_SESSION_NONE) {
	session_name("openDCIMSession");
	session_start();
}

require_once "db.inc.php";
require_once "facilities.inc.php";

// --- Ensure $person is loaded ---
$person = People::Current();

if(!$person || $person->UserID == ""){
	if(isset($_COOKIE['openDCIMUser'])){
		$person = new People();
		$person->UserID = $_COOKIE['openDCIMUser'];
		$person->GetPersonByUserID();
	}
}

if(!$person || $person->UserID == ""){
	echo '<div class="alert alert-danger">'
		.__("Session expired or user not found. Please reload the page.")
		.'</div>';
	exit;
}

header('Content-Type: text/html; charset=utf-8');

$cabinetid = intval($_POST['cabinetid'] ?? 0);
$mode      = sanitize($_POST['mode'] ?? 'balanced');

// Get PDU List
$pdu = new PowerDistribution();
$pdu->CabinetID = $cabinetid;
$pduList = $pdu->GetPDUbyCabinet();
$pduCount = is_array($pduList) ? count($pduList) : 0;

if ($pduCount == 0) {
	echo '<div class="alert alert-warning">'.__("⚠ No PDU detected in this cabinet.").'</div>';
	exit;
}
if ($mode === 'dualpath' && $pduCount < 2) {
	echo '<div class="alert alert-warning">'.__("⚠ Dual Power Path mode requires at least two PDUs.").'</div>';
	exit;
}
if ($mode === 'intelligent' && $pduCount < 2) {
	echo '<div class="alert alert-warning">'.__("⚠ Intelligent Power Planner requires at least two PDUs.").'</div>';
	exit;
}

// --- Get devices ---
$dev = new Device();
$dev->Cabinet = $cabinetid;
$devices = $dev->ViewDevicesByCabinet();
if (empty($devices)) {
	echo '<div class="alert alert-info">'.__("ℹ No devices detected in this cabinet.").'</div>';
	exit;
}

// ---------- Helpers ----------
function getFreePowerPortsForPDUID($pduid){
	$pp = new PowerPorts();
	$ports = $pp->getPortList($pduid);
	$free  = [];
	foreach($ports as $n => $port){
		if(intval($port->ConnectedDeviceID) === 0){
			$free[$n] = $port;
		}
	}
	return $free;
}

function getDeviceFreeInlets($deviceID){
	$dp = new DevicePorts();
	$ports = $dp->getPortList($deviceID);
	$free = [];
	foreach($ports as $n => $port){
		if(property_exists($port,'PortType') && stripos($port->PortType,'power')===false){
			continue;
		}
		if(intval($port->ConnectedDeviceID) === 0){
			$free[$n] = $port;
		}
	}
	return $free;
}

function pickBestPduPortStrict($targetsPDU, $pduState, $requiredConnectorID, $requiredVoltageID, &$phaseLoad){
	$best = null;
	$bestScore = PHP_INT_MAX;
	foreach($targetsPDU as $pdu){
		$PDUID = $pdu->PDUID;
		if(!isset($pduState[$PDUID])) continue;
		foreach($pduState[$PDUID]['free'] as $pn => $portObj){
			if($requiredConnectorID && intval($portObj->ConnectorID) !== intval($requiredConnectorID)) continue;
			if($requiredVoltageID   && intval($portObj->VoltageID)   !== intval($requiredVoltageID))   continue;
			$ph = intval($portObj->PhaseID) ?: 0;
			$score = intval($phaseLoad[$ph] ?? 0);
			if($score < $bestScore){
				$bestScore = $score;
				$best = ['pdu'=>$pdu,'port'=>$pn,'phase'=>$ph];
			}
		}
	}
	return $best;
}

// ---------- Build PDU state ----------
$pduState = [];
$missingConnectorOrPhase = false;

foreach($pduList as $pdu){
	$freePorts = getFreePowerPortsForPDUID($pdu->PDUID);
	$pduState[$pdu->PDUID] = [
		'obj'  => $pdu,
		'free' => $freePorts
	];

	// Vérification des données critiques
	foreach($freePorts as $port){
		if(empty($port->ConnectorID) || empty($port->PhaseID)){
			$missingConnectorOrPhase = true;
			break 2; // on sort dès qu’un port incomplet est détecté
		}
	}
}

// ⚠️ Alerte si ConnectorID ou PhaseID manquant
if($missingConnectorOrPhase && $mode === 'intelligent'){
	echo '<div class="alert alert-warning">'
		.__("⚠ Warning: One or more PDU ports have missing ConnectorID or PhaseID values.<br>
		Automatic balancing by connector/phase cannot be guaranteed.<br><br>
		The planner will proceed in generic mode (balanced only).")
		.'</div>';
}

$phaseLoad   = [];
$phaseLabels = [1=>'A',2=>'B',3=>'C'];
$planRows    = [];
$hasMonoFeed = false;

// ---------- Main loop ----------
foreach($devices as $dev){
	if(!in_array($dev->DeviceType, ['Server','Switch','Appliance','Chassis','Storage Array'])){ continue; }

	$feeds = max(1, intval($dev->PowerSupplyCount));
	$power = intval($dev->Power);
	$hasMonoFeed = $hasMonoFeed || ($feeds == 1);
	$deviceFreeInlets = getDeviceFreeInlets($dev->DeviceID);

	// Mode 3: Intelligent Power Planner
	if($mode === 'intelligent'){
		$pdus = array_values($pduList);
		if(count($pdus) < 2) continue;

		$pduA = $pdus[0];
		$pduB = $pdus[1];

		// Carte PDU / Phase / Connecteur
		$pduPhaseMap = [];
		foreach($pduState as $pid => $pdata){
			foreach($pdata['free'] as $pn => $port){
				$ph = intval($port->PhaseID) ?: 0;
				$conn = intval($port->ConnectorID) ?: 0;
				if(!isset($pduPhaseMap[$pid][$ph][$conn])) $pduPhaseMap[$pid][$ph][$conn] = [];
				$pduPhaseMap[$pid][$ph][$conn][] = $pn;
			}
		}

		$assigned = [];
		for($f=1; $f<=min($feeds,2); $f++){
			$reqConnectorID = null;
			$reqVoltageID   = null;
			if(!empty($deviceFreeInlets)){
				$inletKey = array_key_first($deviceFreeInlets);
				$inlet    = $deviceFreeInlets[$inletKey];
				unset($deviceFreeInlets[$inletKey]);
				if(property_exists($inlet,'ConnectorID') && $inlet->ConnectorID) $reqConnectorID = intval($inlet->ConnectorID);
				if(property_exists($inlet,'VoltageID')   && $inlet->VoltageID)   $reqVoltageID   = intval($inlet->VoltageID);
			}

			$targetPDU = ($f == 1) ? $pduA : $pduB;
			$best = null;
			$bestScore = PHP_INT_MAX;

			foreach($pduPhaseMap[$targetPDU->PDUID] as $phase => $conns){
				if($reqConnectorID && !isset($conns[$reqConnectorID])) continue;
				$connKey = $reqConnectorID ?: array_key_first($conns);
				if(empty($conns[$connKey])) continue;
				$score = $phaseLoad[$phase] ?? 0;
				if($score < $bestScore){
					$bestScore = $score;
					$best = [
						'pdu'=>$targetPDU,
						'phase'=>$phase,
						'conn'=>$connKey,
						'port'=>array_shift($pduPhaseMap[$targetPDU->PDUID][$phase][$connKey])
					];
				}
			}

			if(!$best){
				$planRows[] = [
					'Device'=>$dev->Label,
					'Error'=>sprintf(__("⚠ No compatible port found on %s (connector %d)."), $targetPDU->Label, $reqConnectorID)
				];
				continue;
			}

			$planRows[] = [
				'Device'=>$dev->Label,
				'DeviceID'=>$dev->DeviceID,
				'PDU'=>$best['pdu']->Label,
				'PDUID'=>$best['pdu']->PDUID,
				'Port'=>$best['port']
			];
			unset($pduState[$best['pdu']->PDUID]['free'][$best['port']]);
			$phaseLoad[$best['phase']] = ($phaseLoad[$best['phase']] ?? 0) + ($power / min($feeds,2));
		}
		continue;
	}

	// Modes 1 & 2
	$requestedFeeds = ($mode === 'dualpath') ? min(2, $feeds) : $feeds;
	$targets = array_values($pduList);
	$assigned = [];

	for($f=0; $f<$requestedFeeds; $f++){
		$reqConnectorID = null;
		$reqVoltageID   = null;

		if(!empty($deviceFreeInlets)){
			$inletKey = array_key_first($deviceFreeInlets);
			$inlet    = $deviceFreeInlets[$inletKey];
			unset($deviceFreeInlets[$inletKey]);
			if(property_exists($inlet,'ConnectorID') && $inlet->ConnectorID){ $reqConnectorID = intval($inlet->ConnectorID); }
			if(property_exists($inlet,'VoltageID')   && $inlet->VoltageID){   $reqVoltageID   = intval($inlet->VoltageID); }
		}

		$eligiblePDU = $targets;
		if($mode === 'balanced' && count($targets) >= 2){
			$eligiblePDU = [$targets[$f % 2]];
		}
		if($mode === 'dualpath' && count($targets) >= 2){
			$eligiblePDU = [$targets[min($f,1)]];
		}

		$best = pickBestPduPortStrict($eligiblePDU, $pduState, $reqConnectorID, $reqVoltageID, $phaseLoad);
		if($best){
			$assigned[] = $best;
			unset($pduState[$best['pdu']->PDUID]['free'][$best['port']]);
			$ph = intval($best['phase']) ?: 0;
			$phaseLoad[$ph] = ($phaseLoad[$ph] ?? 0) + ($power / max(1,$requestedFeeds));
		}else{
			$planRows[] = [
				'Device'=>$dev->Label,
				'Error'=>__("⚠ No compatible outlet found for this device feed.")
			];
		}
	}

	if(!empty($assigned)){
		foreach($assigned as $a){
			$planRows[] = [
				'Device'=>$dev->Label,
				'DeviceID'=>$dev->DeviceID,
				'PDU'=>$a['pdu']->Label,
				'PDUID'=>$a['pdu']->PDUID,
				'Port'=>$a['port']
			];
		}
	}
}

// ---------- HTML Output ----------
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

$totalPower = array_sum($phaseLoad);
$maxPhaseLoad = ($totalPower > 0) ? max($phaseLoad) : 0;
function phaseColor($percent){
	if($percent > 80){ return "#f44336"; }
	if($percent > 60){ return "#ffc107"; }
	return "#4caf50";
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

$cab = new Cabinet();
$cab->CabinetID = $cabinetid;
$cab->GetCabinet();

echo "<div class='center'>";
if($person->SiteAdmin || ($cab->AssignedTo && $person->CanWrite($cab->AssignedTo))){
	echo "<button id='btnApplyPowerPlan' class='btn btn-success'>".__("Apply and Save")."</button>";
} else {
	echo "<div class='alert alert-info'>".__("Read-only mode: preview and print only.")."</div>";
}
echo " <button onclick='window.print()' class='btn btn-secondary'>".__("Print Power Plan")."</button></div>";

$_SESSION["auto_plan_$cabinetid"] = $planRows;
?>
