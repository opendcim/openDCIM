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

$hddList     = HDD::GetHDDByDevice($device->DeviceID);
$hddWaitList = HDD::GetPendingByDevice($device->DeviceID);

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

	function toggleAddHDDForm() {
		document.getElementById("addHDDModal").style.display = "block";
	}

	function closeAddHDDForm() {
		document.getElementById("addHDDModal").style.display = "none";
	}
  </script>
<style>
  #addHDDModal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0; top: 0; width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.5);
  }
  #addHDDModal .modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 400px;
    border-radius: 8px;
  }
</style>
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
		<td>
		<select name='Status[{$hdd->HDDID}]'>
  			<option value='On'" . ($hdd->Status=="On"?" selected":"") . ">On</option>
  			<option value='Off'" . ($hdd->Status=="Off"?" selected":"") . ">Off</option>
			<option value='Replace'" . ($hdd->Status=="Replace"?" selected":"") . ">Replace</option>
			<option value='Pending_destruction'" . ($hdd->Status=="Pending_destruction"?" selected":"") . ">Pending_destruction</option>
			<option value='Destroyed_h2'" . ($hdd->Status=="Destroyed_h2"?" selected":"") . ">Destroyed_h2</option>
			<option value='Spare'" . ($hdd->Status=="Spare"?" selected":"") . ">Spare</option>
			</select>
		</td>
		<td><select name='TypeMedia[{$hdd->HDDID}]'>
			<option value='HDD'" . ($hdd->TypeMedia == "HDD" ? " selected" : "") . ">HDD</option>
			<option value='SSD'" . ($hdd->TypeMedia == "SSD" ? " selected" : "") . ">SSD</option>
			<option value='MVME'" . ($hdd->TypeMedia == "MVME" ? " selected" : "") . ">MVME</option>
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
				<button type="button" onclick="openModal()">➕ <?php echo __("Add New HDD"); ?></button>
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
</div> <!-- End of main content -->
<!-- Modal Add HDD -->
<div id="hddModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
  <div style="background-color:#fff; margin:10% auto; padding:20px; border:1px solid #888; width:400px; position:relative;">
    <span onclick="closeModal()" style="position:absolute; right:10px; top:10px; cursor:pointer;">&times;</span>
    <h3><?php echo __("Add New HDD"); ?></h3>
    <form method="POST" action="savehdd.php">
      <input type="hidden" name="DeviceID" value="<?php echo $device->DeviceID; ?>">
      <input type="hidden" name="action"   value="create_hdd_form">

      <label for="Label"><?php echo __("Label"); ?></label><br>
      <input type="text"   name="Label"    id="Label"    required><br><br>

      <label for="SerialNo"><?php echo __("Serial No"); ?></label><br>
      <input type="text"   name="SerialNo" id="SerialNo" required><br><br>

      <label for="TypeMedia"><?php echo __("Type"); ?></label><br>
      <select name="TypeMedia" id="TypeMedia">
        <option value="HDD">HDD</option>
        <option value="SSD">SSD</option>
        <option value="MVME">MVME</option>
      </select><br><br>

      <label for="Size"><?php echo __("Size (GB)"); ?></label><br>
      <input type="number" name="Size"      id="Size"     value="0" min="0"><br><br>

      <label for="Note"><?php echo __("Note"); ?></label><br>
      <textarea name="Note" id="Note" rows="3"></textarea><br><br>

      <button type="submit"><?php echo __("Add HDD"); ?></button>
      <button type="button" onclick="closeModal()"><?php echo __("Cancel"); ?></button>
    </form>
  </div>
</div>
<!-- End Modal Add HDD -->

<script type="text/javascript">
function openModal() {
  document.getElementById('hddModal').style.display = 'block';
}
function closeModal() {
  document.getElementById('hddModal').style.display = 'none';
}
window.onclick = function(event) {
  if (event.target == document.getElementById('hddModal')) {
    closeModal();
  }
}
</script>

</div> <!-- End of page -->

</body>
</html>
