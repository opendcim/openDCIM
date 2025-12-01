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
$customDestroyDate = trim($_POST['custom_destroy_date'] ?? '');
$customDestroyDate = ($customDestroyDate === '') ? null : $customDestroyDate;
$targetDeviceID = isset($_POST['target_device_id']) ? intval($_POST['target_device_id']) : 0;

if (!function_exists('logHddManagementAction')) {
	function logHddManagementAction(string $actionName, array $details = []): void {
		global $deviceID, $person;
		$payload = '';
		if (!empty($details)) {
			$payload = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			if ($payload === false) {
				$payload = '';
			}
		}
		HDD::RecordGenericLog($deviceID, $person->UserID, $actionName, $payload);
	}
}

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
			logHddManagementAction('HDD_CREATE', [
				'hdd_id' => $hdd->HDDID,
				'serial' => $hdd->SerialNo,
				'type'   => $hdd->TypeMedia,
				'size'   => $hdd->Size
			]);
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
			if ($hdd->Update()) {
				logHddManagementAction('HDD_UPDATE', [
					'hdd_id' => $hdd->HDDID,
					'serial' => $hdd->SerialNo,
					'status' => $hdd->Status,
					'type'   => $hdd->TypeMedia,
					'size'   => $hdd->Size
				]);
			}
			break;

		case preg_match('/^remove_(\d+)$/', $action, $m) === 1:
			$removeId = intval((int)$m[1]);
			$hdd = HDD::GetHDDByID($removeId);
			if ($hdd && $hdd->SendForDestruction()) {
				logHddManagementAction('HDD_SEND_FOR_DESTRUCTION', [
					'ids'    => [$removeId],
					'count'  => 1,
					'source' => 'single'
				]);
			}
			break;

		case preg_match('/^delete_(\d+)$/', $action, $m) === 1:
			$deleteId = intval((int)$m[1]);
			if (HDD::DeleteByID($deleteId)) {
				logHddManagementAction('HDD_DELETE', [
					'ids'   => [$deleteId],
					'count' => 1
				]);
			}
			break;

		case preg_match('/^duplicate_(\d+)$/', $action, $m) === 1:
			$sourceId = intval((int)$m[1]);
			$newIds = HDD::DuplicateToEmptySlots($sourceId);
			if (!empty($newIds)) {
				logHddManagementAction('HDD_DUPLICATE', [
					'source_id' => $sourceId,
					'count'     => count($newIds),
					'new_ids'   => $newIds
				]);
			}
			break;

		case preg_match('/^destroy_(\d+)$/', $action, $m) === 1:
			$destroyId = intval((int)$m[1]);
			if (HDD::MarkDestroyed($destroyId, $customDestroyDate)) {
				$details = [
					'ids'    => [$destroyId],
					'count'  => 1,
					'source' => 'single'
				];
				if ($customDestroyDate) {
					$details['destroy_date'] = $customDestroyDate;
				}
				logHddManagementAction('HDD_DESTROY', $details);
			}
			break;

		case preg_match('/^reassign_(\d+)$/', $action, $m) === 1:
			$reassignId = intval((int)$m[1]);
			$targetId = ($targetDeviceID > 0) ? $targetDeviceID : $deviceID;
			if ($targetId <= 0) {
				throw new Exception(__('Invalid target device for reassignment.'));
			}
			if ($targetDeviceID > 0 && HDD::GetRemainingSlotCount($targetId) <= 0) {
				$_SESSION['LastError'] = __('slot hdd is full');
				break;
			}
			if (HDD::ReassignToDevice($reassignId, $targetId)) {
				logHddManagementAction('HDD_REASSIGN', [
					'hdd_id'        => $reassignId,
					'target_device' => $targetId
				]);
				$targetLabel = '';
				$targetDeviceObj = new Device();
				$targetDeviceObj->DeviceID = $targetId;
				if ($targetDeviceObj->GetDevice()) {
					$targetLabel = $targetDeviceObj->Label;
				}
				if ($targetDeviceID > 0 && $targetLabel !== '') {
					$_SESSION['Message'] = sprintf(__('HDD transfered in (%s)'), $targetLabel);
				} elseif ($targetDeviceID > 0) {
					$_SESSION['Message'] = __('HDD reassigned successfully');
				}
			} else {
				if ($targetDeviceID > 0 && HDD::GetRemainingSlotCount($targetId) <= 0) {
					$_SESSION['LastError'] = __('slot hdd is full');
				} else {
					$_SESSION['LastError'] = __('Unable to reassign HDD');
				}
			}
			break;

		case preg_match('/^spare_(\d+)$/', $action, $m) === 1:
			$spareId = intval((int)$m[1]);
			if (HDD::MarkAsSpare($spareId)) {
				logHddManagementAction('HDD_MARK_SPARE', [
					'hdd_id' => $spareId
				]);
			}
			break;
		
		case $action === "bulk_remove":
			$removedIds = [];
			// $_POST['select_active'] contient un tableau d'IDs cochés
			foreach ($_POST['select_active'] ?? [] as $id) {
				$intId = intval($id);
				// Récupère l'objet complet pour préserver ses autres propriétés
				if ($intId > 0 && ($hdd = HDD::GetHDDByID($intId)) && $hdd->SendForDestruction()) {
					$removedIds[] = $intId;
				}
			}
			if (!empty($removedIds)) {
				logHddManagementAction('HDD_BULK_REMOVE', [
					'ids'   => $removedIds,
					'count' => count($removedIds)
				]);
			}
			break;

		case $action === "bulk_delete":
			$deletedIds = [];
			foreach ($_POST['select_active'] ?? [] as $id) {
				$intId = intval($id);
				if ($intId > 0 && HDD::DeleteByID($intId)) {
					$deletedIds[] = $intId;
				}
			}
			if (!empty($deletedIds)) {
				logHddManagementAction('HDD_BULK_DELETE', [
					'ids'   => $deletedIds,
					'count' => count($deletedIds)
				]);
			}
			break;

		case $action === "bulk_destroy":
			$pendingSelected = $_POST['select_pending_destroyed'] ?? ($_POST['select_pending'] ?? []);
			$destroyedIds = [];
			foreach ($pendingSelected as $id) {
				$intId = intval($id);
				if ($intId > 0 && HDD::MarkDestroyed($intId, $customDestroyDate)) {
					$destroyedIds[] = $intId;
				}
			}
			if (!empty($destroyedIds)) {
				$details = [
					'ids'    => $destroyedIds,
					'count'  => count($destroyedIds),
					'source' => 'bulk_destroy'
				];
				if ($customDestroyDate) {
					$details['destroy_date'] = $customDestroyDate;
				}
				logHddManagementAction('HDD_BULK_DESTROY', $details);
			}
			break;
					
		case $action === "bulk_destroyFromActive":
			$activeSelected = $_POST['select_active'] ?? [];
			$destroyedActive = [];
			foreach ($activeSelected as $id) {
				$intId = intval($id);
				if ($intId > 0 && HDD::MarkDestroyed($intId, $customDestroyDate)) {
					$destroyedActive[] = $intId;
				}
			}
			if (!empty($destroyedActive)) {
				$details = [
					'ids'    => $destroyedActive,
					'count'  => count($destroyedActive),
					'source' => 'bulk_destroy_active'
				];
				if ($customDestroyDate) {
					$details['destroy_date'] = $customDestroyDate;
				}
				logHddManagementAction('HDD_BULK_DESTROY', $details);
			}
			break;

		case $action === "export_list":
			// Export XLS complet en 3 feuilles
			logHddManagementAction('HDD_EXPORT', [
				'mode' => 'full_device',
				'format' => 'xls'
			]);
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
