<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once("db.inc.php");
require_once("facilities.inc.php");
require_once("classes/hdd.class.php");

$subheader = __("HDD Management");

if (!$person->ManageHDD) {
	header("Location: index.php");
	exit;
}

$device = new Device();

if (isset($_GET['DeviceID']) && is_numeric($_GET['DeviceID'])) {
	$device->DeviceID = intval($_GET['DeviceID']);
	if (!$device->GetDevice()) {
		echo __("Invalid DeviceID");
		exit;
	}
} else {
	echo __("DeviceID is required");
	exit;
}

$template = new DeviceTemplate();
$template->TemplateID = $device->TemplateID;
$template->GetTemplateByID();
$template->LoadHDDConfig();

if (!$template->EnableHDDFeature) {
	echo '<div class="error">'.__("This equipment does not support HDD management.").'</div>';
	exit;
}

$hddList = HDD::GetHDDByDevice($device->DeviceID);
$hddWaitList = HDD::GetRetiredHDDByDevice($device->DeviceID);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript">
	function confirmDelete() {
		return confirm("<?php echo __('This action is permanent and cannot be undone. Are you sure?'); ?>");
	}
  </script>
</head>
<body>
<?php include("header.inc.php"); ?>
<div class="page managehdd">
<?php include("sidebar.inc.php"); ?>
<div class="main">
	<div class="center">
		<h2><?php echo __("Manage HDDs for Device") . ": " . htmlentities($device->Label); ?></h2>
		<form method="POST" action="savehdd.php">
			<input type="hidden" name="DeviceID" value="<?php echo $device->DeviceID; ?>">

			<h3><?php echo __("Active HDDs"); ?></h3>
			<table class="border">
				<thead>
					<tr>
						<th><input type="checkbox" onclick="$('input[name=select_active\[\]]').prop('checked', this.checked);"></th>
						<th>#</th>
						<th><?php echo __("Label"); ?></th>
						<th><?php echo __("Serial No"); ?></th>
						<th><?php echo __("Status"); ?></th>
						<th><?php echo __("Type"); ?></th>
						<th><?php echo __("Size (GB)"); ?></th>
						<th><?php echo __("Actions"); ?></th>
					</tr>
				</thead>
				<tbody>
<?php
$i = 1;
foreach ($hddList as $hdd) {
	echo "<tr>
		<td><input type='checkbox' name='select_active[]' value='{$hdd->HDDID}'></td>
		<td>$i</td>
		<td><input type='text' name='Label[{$hdd->HDDID}]' value='" . htmlentities($hdd->Label) . "'></td>
		<td><input type='text' name='SerialNo[{$hdd->HDDID}]' value='" . htmlentities($hdd->SerialNo) . "'></td>
		<td><select name='Status[{$hdd->HDDID}]'>
			<option value='on'" . ($hdd->Status == "on" ? " selected" : "") . ">On</option>
			<option value='off'" . ($hdd->Status == "off" ? " selected" : "") . ">Off</option>
			<option value='replace'" . ($hdd->Status == "replace" ? " selected" : "") . ">Replace</option>
			<option value='pending_destruction'" . ($hdd->Status == "pending_destruction" ? " selected" : "") . ">Pending Destruction</option>
		</select></td>
		<td><select name='TypeMedia[{$hdd->HDDID}]'>
			<option value='SATA'" . ($hdd->TypeMedia == "SATA" ? " selected" : "") . ">SATA</option>
			<option value='SCSI'" . ($hdd->TypeMedia == "SCSI" ? " selected" : "") . ">SCSI</option>
			<option value='SD'" . ($hdd->TypeMedia == "SD" ? " selected" : "") . ">SD</option>
		</select></td>
		<td><input type='number' name='Size[{$hdd->HDDID}]' value='" . intval($hdd->Size) . "'></td>
		<td>
			<button type='submit' name='action' value='update_{$hdd->HDDID}'>✏️</button>
			<button type='submit' name='action' value='remove_{$hdd->HDDID}'>➖</button>
			<button type='submit' name='action' value='delete_{$hdd->HDDID}' onclick='return confirmDelete();'>🗑️</button>
			<button type='submit' name='action' value='duplicate_{$hdd->HDDID}'>📑</button>
		</td>
	</tr>";
	$i++;
}
?>
				</tbody>
			</table>
			<p>
				<button type="submit" name="action" value="add_hdd">➕ <?php echo __("Add New HDD"); ?></button>
				<button type="submit" name="action" value="bulk_remove">➖ <?php echo __("Remove Selected"); ?></button>
				<button type="submit" name="action" value="bulk_delete" onclick="return confirmDelete();">🗑️ <?php echo __("Delete Selected"); ?></button>
			</p>

			<h3><?php echo __("Pending Destruction / Reuse"); ?></h3>
			<table class="border">
				<thead>
					<tr>
						<th><input type="checkbox" onclick="$('input[name=select_pending\[\]]').prop('checked', this.checked);"></th>
						<th><?php echo __("Label"); ?></th>
						<th><?php echo __("Serial No"); ?></th>
						<th><?php echo __("Date Withdrawn"); ?></th>
						<th><?php echo __("Actions"); ?></th>
					</tr>
				</thead>
				<tbody>
<?php
foreach ($hddWaitList as $hdd) {
	echo "<tr>
		<td><input type='checkbox' name='select_pending[]' value='{$hdd->HDDID}'></td>
		<td>" . htmlentities($hdd->Label) . "</td>
		<td>" . htmlentities($hdd->SerialNo) . "</td>
		<td>{$hdd->dateWithdrawn}</td>
		<td>
			<button type='submit' name='action' value='destroy_{$hdd->HDDID}'>⚠️</button>
			<button type='submit' name='action' value='reassign_{$hdd->HDDID}'>♻️</button>
			<button type='submit' name='action' value='spare_{$hdd->HDDID}'>🔧</button>
		</td>
	</tr>";
}
?>
				</tbody>
			</table>
			<p>
				<button type="submit" name="action" value="bulk_destroy">⚠️ <?php echo __("Destroy Selected"); ?></button>
				<button type="submit" name="action" value="Export_list">🖨️ <?php echo __("Export List"); ?></button>
			</p>
		</form>
		<div style="margin-top: 20px; text-align: right;">
	<a class="button" href="hdd_log_view.php?DeviceID=<?php echo $device->DeviceID; ?>">
		<?php echo __("View HDD Activity Log"); ?>
	</a>
</div>
	</div>
</div>

</div>
</body>
</html>
