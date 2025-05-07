<?php
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

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch (true) {
	case preg_match('/^update_(\d+)$/', $action, $m):
		$hdd = new HDD();
		$hdd->HDDID = $m[1];
		$hdd->Label = $_POST['Label'][$hdd->HDDID];
		$hdd->SerialNo = $_POST['SerialNo'][$hdd->HDDID];
		$hdd->Status = $_POST['Status'][$hdd->HDDID];
		$hdd->TypeMedia = $_POST['TypeMedia'][$hdd->HDDID];
		$hdd->Size = $_POST['Size'][$hdd->HDDID];
		$hdd->Update();
		break;

	case preg_match('/^remove_(\d+)$/', $action, $m):
		$hdd = new HDD();
		$hdd->HDDID = $m[1];
		$hdd->Withdraw();
		break;

	case preg_match('/^delete_(\d+)$/', $action, $m):
		$hdd = new HDD();
		$hdd->HDDID = $m[1];
		$hdd->Delete();
		break;

	case preg_match('/^duplicate_(\d+)$/', $action, $m):
		HDD::DuplicateToEmptySlots($m[1]);
		break;

	case preg_match('/^destroy_(\d+)$/', $action, $m):
		HDD::MarkDestroyed($m[1]);
		break;

	case preg_match('/^reassign_(\d+)$/', $action, $m):
		HDD::ReassignToDevice($m[1], $deviceID);
		break;

	case preg_match('/^spare_(\d+)$/', $action, $m):
		HDD::MarkAsSpare($m[1]);
		break;

	case $action === "add_hdd":
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
			HDD::MarkDestroyed($id);
		}
		break;

	case $action === "print_list":
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename="HDD_List_Device_' . $deviceID . '.xls"');
		HDD::ExportPendingDestruction($deviceID);
		exit;
		break;
}

header("Location: managementhdd.php?DeviceID=$deviceID");
exit;
