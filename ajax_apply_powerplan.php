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

// -----------------------------------------------------------------------------
// APPLY POWER CONNECTIONS
// -----------------------------------------------------------------------------
echo "<h4>".__("Applying Power Distribution Plan")."</h4>";

$success = 0;
$failed  = 0;
$total   = 0;

foreach($plan as $row){
    if(
        empty($row['PDUID']) ||
        empty($row['Port'])  ||
        empty($row['DeviceID'])
    ){
        continue;
    }

    $total++;

    // On détermine le numéro d’entrée sur le device (DeviceConnNumber)
    // Ici, on suppose 1ère alim = 1, 2e alim = 2, etc.
    static $feedCount = [];
    $devID = intval($row['DeviceID']);
    if(!isset($feedCount[$devID])) $feedCount[$devID] = 1;
    else $feedCount[$devID]++;

    $conn = new PowerConnection();
    $conn->PDUID            = intval($row['PDUID']);
    $conn->PDUPosition      = intval($row['Port']);
    $conn->DeviceID         = $devID;
    $conn->DeviceConnNumber = $feedCount[$devID]; // important !

    // Nettoyage éventuel avant création
    $existing = new PowerConnection();
    $existing->PDUID = $conn->PDUID;
    $existing->PDUPosition = $conn->PDUPosition;
    $existing->RemoveConnection();

    if($conn->CreateConnection()){
        $success++;
    } else {
        error_log("❌ Failed to create connection for Device {$conn->DeviceID} Port {$conn->PDUPosition}");
        $failed++;
    }
}

// -----------------------------------------------------------------------------
// RESULT OUTPUT
// -----------------------------------------------------------------------------
if($success > 0){
    echo '<div class="alert alert-success">'
        .sprintf(__("✅ %d power connections successfully created."), $success)
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
