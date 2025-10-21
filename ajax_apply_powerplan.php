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

// --- Retrieve CabinetID and plan data ---
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

// --- Retrieve plan from session ---
$plan = $_SESSION["auto_plan_$cabinetid"] ?? [];

if(empty($plan)){
    echo '<div class="alert alert-warning">'.__("No power plan in memory. Please generate one first.").'</div>';
    exit;
}

echo "<h4>".__("Applying Power Distribution Plan")."</h4>";

// --- Apply connections ---
$success = 0;
$failed  = 0;
$total   = 0;

$pc = new PowerConnection();

foreach($plan as $row){
    if(
        !isset($row['PDUID']) ||
        !isset($row['Port'])  ||
        !isset($row['DeviceID'])
    ){
        continue;
    }

    $total++;

    $pc->PDUID        = intval($row['PDUID']);
    $pc->PDUPosition  = intval($row['Port']);
    $pc->DeviceID     = intval($row['DeviceID']);

    // Remove any existing connection before creating a new one (avoid duplicate)
    $existing = new PowerConnection();
    $existing->PDUID = $pc->PDUID;
    $existing->PDUPosition = $pc->PDUPosition;
    $existing->RemoveConnection();

    if($pc->CreateConnection()){
        $success++;
    } else {
        $failed++;
    }
}

// --- Output summary ---
echo '<div class="alert alert-success">'
    .sprintf(__("✅ %d power connections successfully created."), $success)
    .'</div>';

if($failed > 0){
    echo '<div class="alert alert-danger">'
        .sprintf(__("⚠ %d connections failed."), $failed)
        .'</div>';
}

if($success == 0 && $failed == 0){
    echo '<div class="alert alert-info">'.__("No valid connections to apply.").'</div>';
}

// --- Final note ---
echo "<p class='center'>"
    .sprintf(__("Processed %d total entries."), $total)
    ."</p>";

// --- Cleanup temporary session ---
unset($_SESSION["auto_plan_$cabinetid"]);

?>
