<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("db.inc.php");
require_once("facilities.inc.php");
require_once("classes/hdd.class.php");

if (!$person->ManageHDD) {
	header("Location: index.php");
	exit;
}

$deviceID = isset($_POST['DeviceID']) ? intval($_POST['DeviceID']) : 0;

if (!$deviceID) {
	header("Location: index.php");
	exit;
}

$action   = $_POST['action']   ?? '';

try {
	switch (true) 
	{	// Création d’un nouveau HDD depuis le modal
    	case $action === 'create_hdd_form':
			// Récupération et sanitation des champs
			$serialNo  = $_POST['SerialNo'] ?? '';
			$typeMedia = $_POST['TypeMedia']?? '';
			$size      = intval($_POST['Size'] ?? 0);

			// Création via instance pour inclure le champ Note
			$hdd = new HDD();
			$hdd->DeviceID          = $deviceID;
			$hdd->SerialNo          = $serialNo;
			$hdd->Status            = 'On';
			$hdd->TypeMedia         = $typeMedia;
			$hdd->Size              = $size;
			$hdd->Create();
			break;
		
		case preg_match('/^update_(\d+)$/', $action, $m) === 1:
			$id = intval((int)$m[1]);
			// Récupère l’objet complet (avec tous les champs)
			$hdd = HDD::GetHDDByID($id);
			if (!$hdd) {
				throw new Exception("HDDID {$id} introuvable.");
			}
			// Ne mettez à jour QUE ce qui vient du formulaire
			$hdd->SerialNo  = $_POST['SerialNo'][$id] ?? $hdd->SerialNo;
			$hdd->Status    = $_POST['Status'][$id]   ?? $hdd->Status;
			$hdd->TypeMedia = $_POST['TypeMedia'][$id]?? $hdd->TypeMedia;
			$hdd->Size      = intval($_POST['Size'][$id] ?? $hdd->Size);
			// Maintenant vous avez déjà StatusDestruction, Note, DateAdd, etc.
			$hdd->MakeSafe();
			$hdd->Update();
			break;

		case preg_match('/^remove_(\d+)$/', $action, $m) === 1:
			$hdd = HDD::GetHDDByID(intval((int)$m[1]));
			if ($hdd) {
			$hdd->SendForDestruction();
			}
			break;

		case preg_match('/^delete_(\d+)$/', $action, $m) === 1:
			HDD::DeleteByID((int)$m[1]);
			break;

		case preg_match('/^duplicate_(\d+)$/', $action, $m) === 1:
			HDD::DuplicateToEmptySlots((int)$m[1]);
			break;

		case preg_match('/^destroy_(\d+)$/', $action, $m) === 1:
			HDD::MarkDestroyed((int)$m[1]);
			break;

		case preg_match('/^reassign_(\d+)$/', $action, $m) === 1:
			HDD::ReassignToDevice((int)$m[1], $deviceID);
			break;

		case preg_match('/^spare_(\d+)$/', $action, $m) === 1:
			HDD::MarkAsSpare((int)$m[1]);
			break;
		
		case $action === "bulk_remove":
			  // $_POST['select_active'] contient un tableau d’IDs cochés
			  foreach ($_POST['select_active'] ?? [] as $id) {
				$id = intval($id);
				// Récupère l’objet complet pour préserver ses autres propriétés
				if ($hdd = HDD::GetHDDByID($id)) {
					$hdd->SendForDestruction();
				}
			}
			break;

		case $action === "bulk_delete":
			foreach ($_POST['select_active'] ?? [] as $id) {
				HDD::DeleteByID(intval($id));
			}
			break;

		case $action === "bulk_destroy":
			$pendingSelected = $_POST['select_pending_destroyed'] ?? ($_POST['select_pending'] ?? []);
			$destroyedIds = [];
			foreach ($pendingSelected as $id) {
				$intId = intval($id);
				if ($intId > 0 && HDD::MarkDestroyed($intId)) {
					$destroyedIds[] = $intId;
				}
			}
			if (!empty($destroyedIds)) {
				$details = json_encode([
					'ids' => $destroyedIds,
					'count' => count($destroyedIds),
					'source' => 'bulk_destroy'
				], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				HDD::RecordGenericLog($deviceID, $person->UserID, 'HDD_BULK_DESTROY', $details);
			}
			break;
					
		case $action === "bulk_destroyFromActive":
			$activeSelected = $_POST['select_active'] ?? [];
			$destroyedActive = [];
			foreach ($activeSelected as $id) {
				$intId = intval($id);
				if ($intId > 0 && HDD::MarkDestroyed($intId)) {
					$destroyedActive[] = $intId;
				}
			}
			if (!empty($destroyedActive)) {
				$details = json_encode([
					'ids' => $destroyedActive,
					'count' => count($destroyedActive),
					'source' => 'bulk_destroy_active'
				], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				HDD::RecordGenericLog($deviceID, $person->UserID, 'HDD_BULK_DESTROY', $details);
			}
			break;

		case $action === "export_list":
			 // Export XLS complet en 3 feuilles
			 HDD::ExportAllToXls($deviceID);
			 // (la méthode se termine par exit())
			break;
		
		case $action === "certify_audit":
			if (HDD::RecordAudit($deviceID, $person->UserID)) {
				$_SESSION['Message'] = __('HDD audit recorded successfully');
			} else {
				$_SESSION['LastError'] = __('Unable to record HDD audit');
			}
			break;
		
        default:
            throw new Exception("Action inconnue : “{$action}”.");
    }

} catch (\PDOException $e) {
    echo "<h2>Erreur base de données :</h2><pre>" . htmlentities($e->getMessage()) . "</pre>";
    exit;
} catch (\Exception $e) {
    echo "<h2>Erreur :</h2><pre>" . htmlentities($e->getMessage()) . "</pre>";
    exit;
}
	
// Redirect to the HDD management page
header('Location: managementhdd.php?DeviceID=' . urlencode($deviceID));
exit;
