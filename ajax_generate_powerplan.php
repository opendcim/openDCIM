<?php
if (session_status() === PHP_SESSION_NONE) {
	session_name("openDCIMSession");
	session_start();
}
require_once "db.inc.php";
require_once "facilities.inc.php";

header('Content-Type: text/html; charset=utf-8');

// --- Ensure user session ---
$person = People::Current();
if(!$person || $person->UserID == ""){
	if(isset($_COOKIE['openDCIMUser'])){
		$person = new People();
		$person->UserID = $_COOKIE['openDCIMUser'];
		$person->GetPersonByUserID();
	}
}
if(!$person || $person->UserID == ""){
	echo '<div class="alert alert-danger">'.__("Session expired or user not found. Please reload the page.").'</div>';
	exit;
}

$cabinetid = intval($_POST['cabinetid'] ?? 0);
$mode      = sanitize($_POST['mode'] ?? 'balanced'); // balanced | dualpath | intelligent
$force     = intval($_POST['force'] ?? 0); // continue mode if metadata issues detected

// --- Load cabinet ---
$cab = new Cabinet();
$cab->CabinetID = $cabinetid;
$cab->GetCabinet();

// --- Load PDUs in cabinet ---
$pduObj = new PowerDistribution();
$pduObj->CabinetID = $cabinetid;
$pduList = $pduObj->GetPDUbyCabinet();
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
	echo '<div class="alert alert-warning">'.__("⚠ Intelligent Power Planner mode requires at least two PDUs.").'</div>';
	exit;
}

// --- Load devices ---
$dev = new Device();
$dev->Cabinet = $cabinetid;
$devices = $dev->ViewDevicesByCabinet();
if (empty($devices)) {
	echo '<div class="alert alert-info">'.__("ℹ No devices detected in this cabinet.").'</div>';
	exit;
}

/* ==========================================================
   Helper functions
   ========================================================== */

/**
 * Get nominal power for a device.
 */
function getDeviceWatts(Device $d){
	if (intval($d->NominalWatts) > 0) return intval($d->NominalWatts);
	if (intval($d->TemplateID) > 0) {
		$t = new DeviceTemplate();
		$t->TemplateID = $d->TemplateID;
		if($t->GetTemplateByID()){
			return intval($t->Wattage);
		}
	}
	return 0;
}

/**
 * Retrieve free power ports from a PDU, including ConnectorID / PhaseID / VoltageID.
 */
function getFreePowerPortsForPDUID($pduid){
	$pp = new PowerPorts();
	$ports = $pp->getPortList($pduid);
	$free  = [];
	foreach((array)$ports as $n => $port){
		if(intval($port->ConnectedDeviceID) === 0){
			$free[$n] = $port;
		}
	}
	return $free;
}

/**
 * Build a connector/phase map for each PDU:
 * $map[PDUID][ConnectorID][PhaseID] => [PortNumbers...]
 */
function buildPduConnectorPhaseMap($pduList){
	$map = [];
	$flatFree = [];
	$metaIssues = ['missingConnector'=>false, 'missingPhase'=>false];

	foreach($pduList as $p){
		$PDUID = $p->PDUID;
		$map[$PDUID] = [];
		$flatFree[$PDUID] = [];
		$freePorts = getFreePowerPortsForPDUID($PDUID);
		foreach($freePorts as $pn => $port){
			$cid = isset($port->ConnectorID) ? intval($port->ConnectorID) : null;
			$ph  = isset($port->PhaseID) ? intval($port->PhaseID) : null;

			if (empty($cid)) $metaIssues['missingConnector'] = true;
			if (empty($ph))  $metaIssues['missingPhase'] = true;

			$cidKey = $cid ?: 'null';
			$phKey  = $ph  ?: 'null';

			if(!isset($map[$PDUID][$cidKey])) $map[$PDUID][$cidKey] = [];
			if(!isset($map[$PDUID][$cidKey][$phKey])) $map[$PDUID][$cidKey][$phKey] = [];
			$map[$PDUID][$cidKey][$phKey][] = $pn;

			$flatFree[$PDUID][$pn] = $port;
		}
	}
	return [$map, $flatFree, $metaIssues];
}

/**
 * Get device power inlets still free.
 */
function getDeviceFreeInlets($deviceID){
	$dp = new DevicePorts();
	$ports = $dp->getPortList($deviceID);
	$free = [];
	foreach((array)$ports as $n => $port){
		if(property_exists($port,'PortType') && stripos($port->PortType,'power')===false){
			continue;
		}
		if(intval($port->ConnectedDeviceID) === 0){
			$free[$n] = $port;
		}
	}
	return $free;
}

/**
 * Select the best (PDU, Phase, Port) based on:
 * - required connector
 * - lowest current phase load
 */
function pickBestByConnectorPhase(
	$eligiblePDUs,
	&$pduMap,
	&$phaseLoad,
	$reqConnectorID,
	$allowNullMeta
){
	$best = null;
	$bestLoad = PHP_INT_MAX;

	foreach($eligiblePDUs as $p){
		$PDUID = $p->PDUID;
		if(!isset($pduMap[$PDUID])) continue;

		$connectorKeys = [];
		if(!is_null($reqConnectorID) && isset($pduMap[$PDUID][$reqConnectorID])){
			$connectorKeys[] = $reqConnectorID;
		} elseif($allowNullMeta && isset($pduMap[$PDUID]['null'])) {
			$connectorKeys[] = 'null';
		} elseif(is_null($reqConnectorID)){
			$connectorKeys = array_keys($pduMap[$PDUID]);
		}

		foreach($connectorKeys as $cidKey){
			foreach($pduMap[$PDUID][$cidKey] as $phKey => $ports){
				if (empty($ports)) continue;
				if ($phKey === 'null' && !$allowNullMeta) continue;
				$ph = ($phKey === 'null') ? 0 : intval($phKey);
				$load = intval($phaseLoad[$PDUID][$ph] ?? 0);
				if ($load < $bestLoad){
					$bestLoad = $load;
					$best = [
						'PDUID' => $PDUID,
						'Phase' => $ph,
						'PhaseKey' => $phKey,
						'ConnectorKey' => $cidKey,
						'Port'  => $ports[0]
					];
				}
			}
		}
	}
	return $best;
}

/* ==========================================================
   Build PDU map
   ========================================================== */

list($pduMap, $pduFree, $metaIssues) = buildPduConnectorPhaseMap($pduList);

// Warn user if ConnectorID or PhaseID are missing
if (($metaIssues['missingConnector'] || $metaIssues['missingPhase']) && !$force) {
	echo "<div class='alert alert-warning' style='margin-bottom:10px;'>";
	if ($metaIssues['missingConnector'] && $metaIssues['missingPhase']) {
		echo __("Unable to determine connectors and phases for some PDU ports (ConnectorID and PhaseID are null).");
	} elseif ($metaIssues['missingConnector']) {
		echo __("Unable to determine connectors for some PDU ports (ConnectorID is null).");
	} else {
		echo __("Unable to determine phases for some PDU ports (PhaseID is null).");
	}
	echo " ".__("The planner will proceed with a simplified balanced mode without connector/phase distinction.");
	echo "<br>".__("Do you want to continue?");
	echo "</div>";
	?>
	<div class="center">
		<button id="btnContinuePlanner" class="btn"><?php echo __("Continue"); ?></button>
		<button id="btnCancelPlanner" class="btn btn-secondary"><?php echo __("Cancel"); ?></button>
	</div>
	<script>
	$('#btnContinuePlanner').on('click', function(){
		$.post('ajax_generate_powerplan.php', {
			cabinetid: <?php echo intval($cabinetid); ?>,
			mode: '<?php echo $mode; ?>',
			force: 1
		}, function(resp){
			$('#autoPlanResult').html(resp);
		});
	});
	$('#btnCancelPlanner').on('click', function(){
		$('#autoPlanResult').html('<div class="alert alert-info"><?php echo __("Operation cancelled."); ?></div>');
	});
	</script>
	<?php
	exit;
}

/* ==========================================================
   Power plan computation
   ========================================================== */

$phaseLabels = [1=>'A',2=>'B',3=>'C'];
$phaseLoad = [];
foreach($pduList as $p){
	$phaseLoad[$p->PDUID] = [0=>0,1=>0,2=>0,3=>0];
}

$planRows = [];
$hasMonoFeed = false;
$pduArray = array_values($pduList);
$pduA = $pduArray[0] ?? null;
$pduB = $pduArray[1] ?? null;

foreach($devices as $dv){
	if(!in_array($dv->DeviceType, ['Server','Switch','Appliance','Chassis','Storage Array'])) continue;

	$feeds = max(1, intval($dv->PowerSupplyCount));
	$power = getDeviceWatts($dv);
	$hasMonoFeed = $hasMonoFeed || ($feeds == 1);
	$deviceFreeInlets = getDeviceFreeInlets($dv->DeviceID);
	$requestedFeeds = ($mode === 'dualpath') ? min(2, $feeds) : $feeds;

	for($f=0; $f<$requestedFeeds; $f++){
		$reqConnectorID = null;
		if(!empty($deviceFreeInlets)){
			$inletKey = array_key_first($deviceFreeInlets);
			$inlet = $deviceFreeInlets[$inletKey];
			unset($deviceFreeInlets[$inletKey]);
			if(property_exists($inlet,'ConnectorID') && $inlet->ConnectorID!==''){
				$reqConnectorID = intval($inlet->ConnectorID);
			}
		}

		if($mode === 'balanced' && count($pduArray) >= 2){
			$eligible = [ $pduArray[$f % 2] ];
		} elseif ($mode === 'dualpath' && $pduA && $pduB){
			$eligible = [ ($f==0 ? $pduA : $pduB) ];
		} elseif ($mode === 'intelligent'){
			if ($feeds >= 2 && $pduA && $pduB){
				$eligible = [ ($f==0 ? $pduA : $pduB) ];
			} else {
				$eligible = $pduArray;
			}
		} else {
			$eligible = $pduArray;
		}

		$best = pickBestByConnectorPhase($eligible, $pduMap, $phaseLoad, $reqConnectorID, (bool)$force);

		if($best){
			$PDUID = $best['PDUID'];
			$port  = $best['Port'];
			$phKey = $best['PhaseKey'];
			$ph    = $best['Phase'];

			// Reserve the port
			$bucket =& $pduMap[$PDUID][$best['ConnectorKey']][$phKey];
			$idx = array_search($port, $bucket);
			if($idx !== false) array_splice($bucket, $idx, 1);

			// Update load
			$phaseLoad[$PDUID][$ph] = ($phaseLoad[$PDUID][$ph] ?? 0) + ($power / max(1,$requestedFeeds));

			$pduLabel = '';
			foreach($pduList as $p){ if($p->PDUID == $PDUID){ $pduLabel = $p->Label; break; } }

			$planRows[] = [
				'Device'=>$dv->Label,
				'DeviceID'=>$dv->DeviceID,
				'PDU'=>$pduLabel ?: "PDU-$PDUID",
				'PDUID'=>$PDUID,
				'Port'=>$port
			];
		}else{
			$planRows[] = [
				'Device'=>$dv->Label,
				'Error'=>sprintf(__("⚠ No compatible outlet found for this device feed (connector %s)."),
					$reqConnectorID ?: __("any"))
			];
		}
	}
}

/* ==========================================================
   Render HTML
   ========================================================== */

echo "<h4>".__("Proposed Power Distribution Plan")."</h4>";

if ($hasMonoFeed) {
	echo '<div class="alert alert-info">'
		. __("⚠ Single-power devices detected. Installing a Static Transfer Switch (STS) is recommended to enhance power redundancy.")
		. '</div>';
}

echo "<table class='table table-striped'><tr><th>".__("Device")."</th><th>".__("PDU")."</th><th>".__("Port")."</th></tr>";
foreach($planRows as $r){
	if(isset($r['Error'])){
		echo "<tr><td>{$r['Device']}</td><td colspan=2><span class='error'>{$r['Error']}</span></td></tr>";
	} else {
		echo "<tr><td>{$r['Device']}</td><td>{$r['PDU']}</td><td>{$r['Port']}</td></tr>";
	}
}
echo "</table>";

$phaseLabels = [1=>'A',2=>'B',3=>'C'];
echo "<fieldset><legend>".__("Phase Load Summary")."</legend><ul>";
$totalPower = 0;
foreach($phaseLoad as $PDUID => $loads){
	$pduName = '';
	foreach($pduList as $p){ if($p->PDUID==$PDUID){ $pduName=$p->Label; break; } }
	if(!$pduName) $pduName = "PDU-$PDUID";
	$totalPower += array_sum($loads);
	echo "<li><strong>$pduName</strong>: ";
	$tmp = [];
	foreach([1,2,3] as $ph){
		$tmp[] = sprintf("Phase %s: %.1f W", $phaseLabels[$ph], $loads[$ph] ?? 0);
	}
	if(($loads[0] ?? 0) > 0){
		$tmp[] = sprintf("%s: %.1f W", __("Unknown"), $loads[0]);
	}
	echo implode(" • ", $tmp)."</li>";
}
echo "</ul></fieldset>";

function phaseColor($p){ if($p>80) return "#f44336"; if($p>60) return "#ffc107"; return "#4caf50"; }

echo "<fieldset><legend>".__("Visual Load Summary")."</legend>";
foreach($phaseLoad as $PDUID => $loads){
	$pduName = '';
	foreach($pduList as $p){ if($p->PDUID==$PDUID){ $pduName=$p->Label; break; } }
	if(!$pduName) $pduName = "PDU-$PDUID";
	echo "<div style='font-weight:bold;margin-top:10px;'>$pduName</div>";
	$maxLoad = max($loads) ?: 1;
	echo "<table class='phase-load-table' style='width:100%; border-collapse:collapse;'>";
	foreach([1,2,3,0] as $ph){
		if(($loads[$ph] ?? 0) <= 0) continue;
		$label = ($ph==0) ? __("Unknown") : $phaseLabels[$ph];
		$perc = round(($loads[$ph]/$maxLoad)*100);
		$col = phaseColor($perc);
		echo "<tr><td style='width:80px;font-weight:bold;'>Phase $label</td>
			<td style='width:80%;background:#eee;'><div style='height:12px;width:{$perc}%;background:$col;'></div></td>
			<td style='width:60px;text-align:right;'>".number_format($loads[$ph],0)." W</td></tr>";
	}
	echo "</table>";
}
echo "</fieldset>";

echo "<div class='center'>";
if($person->SiteAdmin || ($cab->AssignedTo && $person->CanWrite($cab->AssignedTo))){
	echo "<button id='btnApplyPowerPlan' class='btn btn-success'>".__("Apply and Save")."</button>";
}else{
	echo "<div class='alert alert-info'>".__("Read-only mode: preview and print only.")."</div>";
}
echo " <button onclick='window.print()' class='btn btn-secondary'>".__("Print Power Plan")."</button></div>";

$_SESSION["auto_plan_$cabinetid"] = $planRows;
?>