<?php
// -----------------------------------------------------------------------------
// openDCIM - Automatic PDU Link Planner : Apply generated power plan
// Version: 25.01 compatible
// Author: Alexandre Oliveira (feature/automatic-pdu-link-planner)
// -----------------------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    session_name("openDCIMSession");
    session_start();
}

require_once "db.inc.php";
require_once "facilities.inc.php";

// --- Restore user context ---
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

// --- Retrieve CabinetID ---
$cabinetid = intval($_POST['cabinetid'] ?? 0);
if($cabinetid <= 0){
    echo '<div class="alert alert-danger">'.__("Invalid cabinet ID.").'</div>';
    exit;
}

$cab = new Cabinet();
$cab->CabinetID = $cabinetid;
if(!$cab->GetCabinet()){
    echo '<div class="alert alert-danger">'.__("Cabinet not found.").'</div>';
    exit;
}

// --- Check permissions ---
if(!$person->SiteAdmin && !$person->CanWrite($cab->AssignedTo)){
    echo '<div class="alert alert-warning">'
        .__("You do not have sufficient rights to apply this power plan.")
        .'</div>';
    exit;
}

// --- Retrieve generated plan ---
$plan = $_SESSION["auto_plan_$cabinetid"] ?? [];
if(empty($plan)){
    echo '<div class="alert alert-warning">'.__("No power plan in memory. Please generate one first.").'</div>';
    exit;
}

echo "<h4>".__("Applying Power Distribution Plan")."</h4>";

$success = 0;
$failed  = 0;
$total   = 0;

global $dbh;

foreach($plan as $row){
    if(empty($row['PDUID']) || empty($row['Port']) || empty($row['DeviceID'])){
        continue;
    }

    $total++;
    $PDUID     = intval($row['PDUID']);
    $portNum   = intval($row['Port']);
    $deviceID  = intval($row['DeviceID']);

    // On détermine le numéro d’entrée sur le device (DeviceConnNumber)
    static $feedCount = [];
    if(!isset($feedCount[$deviceID])) $feedCount[$deviceID] = 1;
    else $feedCount[$deviceID]++;

    $conn = new PowerConnection();
    $conn->PDUID            = $PDUID;
    $conn->PDUPosition      = $portNum;
    $conn->DeviceID         = $deviceID;
    $conn->DeviceConnNumber = $feedCount[$deviceID];

    // Supprimer ancienne liaison éventuelle
    $existing = new PowerConnection();
    $existing->PDUID = $PDUID;
    $existing->PDUPosition = $portNum;
    $existing->RemoveConnection();

    // Crée la connexion (fac_PowerConnection)
    if($conn->CreateConnection()){
        // --- Synchroniser fac_PowerPorts ---

        // Récupérer port côté PDU
        $pp = new PowerPorts();
        $pduPorts = $pp->getPortList($PDUID);
        if(isset($pduPorts[$portNum])){
            $pduPort = $pduPorts[$portNum];
            $pduPort->ConnectedDeviceID = $deviceID;
            $pduPort->ConnectedPort = $feedCount[$deviceID];
            $pduPort->UpdatePort();
        }

        // Récupérer port côté DEVICE
        $devPorts = $pp->getPortList($deviceID);
        if(isset($devPorts[$feedCount[$deviceID]])){
            $devPort = $devPorts[$feedCount[$deviceID]];
            $devPort->ConnectedDeviceID = $PDUID;
            $devPort->ConnectedPort = $portNum;
            $devPort->UpdatePort();
        }

        $success++;
    } else {
        error_log("❌ Failed to create connection for Device $deviceID Port $portNum");
        $failed++;
    }
}

// -----------------------------------------------------------------------------
// RESULT OUTPUT
// -----------------------------------------------------------------------------
if($success > 0){
    echo '<div class="alert alert-success">'
        .sprintf(__("✅ %d power connections successfully created and synchronized."), $success)
        .'</div>';
}
if($failed > 0){
    echo '<div class="alert alert-danger">'
        .sprintf(__("⚠ %d connections failed."), $failed)
        .'</div>';
}
if($success == 0 && $failed == 0){
    echo '<div class="alert alert-info">'.__("No valid connections to apply.").'</div>';
}

echo "<p class='center'>"
    .sprintf(__("Processed %d total entries."), $total)
    ."</p>";

// --- Cleanup session ---
unset($_SESSION["auto_plan_$cabinetid"]);

?>
