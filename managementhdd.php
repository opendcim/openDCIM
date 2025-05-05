<?php
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

$hddList = HDD::GetHDDByDevice($device->DeviceID);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet" href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
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

			<table class="border">
				<thead>
					<tr>
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
		<td>$i</td>
		<td><input type='text' name='Label[]' value='" . htmlentities($hdd->Label) . "'></td>
		<td><input type='text' name='SerialNo[]' value='" . htmlentities($hdd->SerialNo) . "'></td>
		<td><select name='Status[]'>
			<option value='on'" . ($hdd->Status == "on" ? " selected" : "") . ">On</option>
			<option value='off'" . ($hdd->Status == "off" ? " selected" : "") . ">Off</option>
			<option value='replace'" . ($hdd->Status == "replace" ? " selected" : "") . ">Replace</option>
			<option value='pending_destruction'" . ($hdd->Status == "pending_destruction" ? " selected" : "") . ">Pending Destruction</option>
		</select></td>
		<td><select name='TypeMedia[]'>
			<option value='SATA'" . ($hdd->TypeMedia == "SATA" ? " selected" : "") . ">SATA</option>
			<option value='SCSI'" . ($hdd->TypeMedia == "SCSI" ? " selected" : "") . ">SCSI</option>
			<option value='SD'" . ($hdd->TypeMedia == "SD" ? " selected" : "") . ">SD</option>
		</select></td>
		<td><input type='number' name='Size[]' value='" . intval($hdd->Size) . "'></td>
		<td><button type='submit' name='delete[]' value='" . $hdd->hddID . "'>" . __("Delete") . "</button></td>
	</tr>";
	$i++;
}
?>
				</tbody>
			</table>
			<button type="submit" name="action" value="update"><?php echo __("Save Changes"); ?></button>
		</form>
	</div>
</div>
<?php include("foot.inc.php"); ?>
</div>
</body>
</html>
