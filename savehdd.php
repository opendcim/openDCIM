<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// test de validation du parse
echo "Si vous voyez ce message, PHP parse bien le fichier.<br>";

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
$deviceID = intval($_POST['DeviceID'] ?? 0);
	if (!$deviceID) {
		echo "Erreur : DeviceID manquant ou invalide.";
		exit;
	}

try {
	switch (true) 
	{	// Création d’un nouveau HDD depuis le modal
    	case $action === 'create_hdd_form':
			// Récupération et sanitation des champs
			$label     = $_POST['Label']    ?? '';
			$serialNo  = $_POST['SerialNo'] ?? '';
			$typeMedia = $_POST['TypeMedia']?? '';
			$size      = intval($_POST['Size'] ?? 0);
			$note      = $_POST['Note']     ?? '';

			// Création via instance pour inclure le champ Note
			$hdd = new HDD();
			$hdd->DeviceID          = $deviceID;
			$hdd->Label             = $label;
			$hdd->SerialNo          = $serialNo;
			$hdd->Status            = 'On';
			$hdd->TypeMedia         = $typeMedia;
			$hdd->Size              = $size;
			$hdd->StatusDestruction = 'none';
			$hdd->Note              = $note;
			$hdd->Create();
			break;
		
		case preg_match('/^update_(\d+)$/', $action, $m) === 1:
			$hdd = new HDD();
			$hdd->HDDID = $m[1];
			$hdd->Label = $_POST['Label'][$hdd->HDDID];
			$hdd->SerialNo = $_POST['SerialNo'][$hdd->HDDID];
			$hdd->Status = $_POST['Status'][$hdd->HDDID];
			$hdd->TypeMedia = $_POST['TypeMedia'][$hdd->HDDID];
			$hdd->Size = $_POST['Size'][$hdd->HDDID];
			$hdd->MakeSafe();
			$hdd->Update();
			echo "Je suis dans le case update";
			break;

		case preg_match('/^remove_(\d+)$/', $action, $m) === 1:
			$hdd = HDD::GetHDDByID(intval($m[1]));
			if ($hdd) {
			$hdd->SendForDestruction();
			}
			echo "Je suis dans le case remove";
			break;

		case preg_match('/^delete_(\d+)$/', $action, $m) === 1:
			$hdd = new HDD();
			$hdd->HDDID = $m[1];
			$hdd->Delete();
			echo "Je suis dans le case delete";
			break;

		case preg_match('/^duplicate_(\d+)$/', $action, $m) === 1:
			HDD::DuplicateToEmptySlots($m[1]);
			echo "Je suis dans le case duplicate pour HDDID {$m[1]}";
			exit;
			break;

		case preg_match('/^destroy_(\d+)$/', $action, $m) === 1:
			HDD::MarkDestroyed($m[1]);
			echo "destroy";
			break;

		case preg_match('/^reassign_(\d+)$/', $action, $m) === 1:
			HDD::ReassignToDevice($m[1], $deviceID);
			break;

		case preg_match('/^spare_(\d+)$/', $action, $m) === 1:
			HDD::MarkAsSpare($m[1]);
			break;

		case $action === "add_hdd":
			// This action is now handled via JS modal, fallback kept for legacy
			HDD::CreateEmpty($deviceID);
			break;
		
		case $action === "bulk_remove":
			foreach ($_POST['select_active'] ?? [] as $id) {
				HDD::WithdrawByID($id);
			}
			break;

		case $action === "bulk_delete":
			foreach ($_POST['select_active'] ?? [] as $id) {
				HDD::DeleteByID($id);
			}
			break;

		case $action === "bulk_destroy":
			foreach ($_POST['select_pending'] ?? [] as $id) {
				HDD::MarkDestroyed((intval)$id);
			}
			break;

		case $action === "print_list":
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="HDD_List_Device_' . $deviceID . '.xls"');
			HDD::ExportPendingDestruction($deviceID);
			exit;
			break;
		// Pour d’autres actions (update_, delete_, etc.), ajoutez vos cases ici
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
