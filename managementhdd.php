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
    <h2><?php echo htmlspecialchars(__("Manage HDDs for Device") . ": " . $device->Label, ENT_QUOTES, 'UTF-8'); ?></h2>
    <form method="POST" action="savehdd.php">
      <input type="hidden" name="DeviceID" value="<?php echo (int)$device->DeviceID; ?>">

      <h3><?php echo htmlspecialchars(__("Active HDDs"), ENT_QUOTES, 'UTF-8'); ?></h3>
      <table class="border">
        <thead>
          <tr>
            <th><input type="checkbox" onclick="$('input[name=select_active\[\]]').prop('checked', this.checked);"></th>
            <th>#</th>
            <th><?php echo htmlspecialchars(__("Label"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Serial No"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Status"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Type"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Size (GB)"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Actions"), ENT_QUOTES, 'UTF-8'); ?></th>
          </tr>
        </thead>
        <tbody>
<?php
$i = 1;
foreach ($hddList as $hdd) {
    $id = (int)$hdd->HDDID;
    echo '<tr>' .
         '<td><input type="checkbox" name="select_active[]" value="' . $id . '"></td>' .
         '<td>' . $i . '</td>' .
         '<td><input type="text" name="Label[' . $id . ']" value="' . htmlspecialchars($hdd->Label, ENT_QUOTES, 'UTF-8') . '"></td>' .
         '<td><input type="text" name="SerialNo[' . $id . ']" value="' . htmlspecialchars($hdd->SerialNo, ENT_QUOTES, 'UTF-8') . '"></td>' .
         '<td><select name="Status[' . $id . ']">' .
           '<option value="On"' . ($hdd->Status === 'On' ? ' selected' : '') . '>On</option>' .
           '<option value="Off"' . ($hdd->Status === 'Off' ? ' selected' : '') . '>Off</option>' .
           '<option value="Replace"' . ($hdd->Status === 'Replace' ? ' selected' : '') . '>Replace</option>' .
           '<option value="Pending_destruction"' . ($hdd->Status === 'Pending_destruction' ? ' selected' : '') . '>Pending_destruction</option>' .
           '<option value="Destroyed_h2"' . ($hdd->Status === 'Destroyed_h2' ? ' selected' : '') . '>Destroyed_h2</option>' .
           '<option value="Spare"' . ($hdd->Status === 'Spare' ? ' selected' : '') . '>Spare</option>' .
         '</select></td>' .
         '<td><select name="TypeMedia[' . $id . ']">' .
           '<option value="HDD"' . ($hdd->TypeMedia === 'HDD' ? ' selected' : '') . '>HDD</option>' .
           '<option value="SSD"' . ($hdd->TypeMedia === 'SSD' ? ' selected' : '') . '>SSD</option>' .
           '<option value="MVME"' . ($hdd->TypeMedia === 'MVME' ? ' selected' : '') . '>MVME</option>' .
         '</select></td>' .
         '<td><input type="number" name="Size[' . $id . ']" value="' . (int)$hdd->Size . '"></td>' .
         '<td>' .
           '<button type="submit" name="action" value="update_' . $id . '">✏️</button>' .
           '<button type="submit" name="action" value="remove_' . $id . '">➖</button>' .
           '<button type="submit" name="action" value="delete_' . $id . '" onclick="return confirmDelete();">🗑️</button>' .
           '<button type="submit" name="action" value="duplicate_' . $id . '">📑</button>' .
         '</td>' .
         '</tr>';
    $i++;
}
?>
        </tbody>
      </table>
      <p>
        <button type="button" onclick="openModal()">➕ <?php echo htmlspecialchars(__("Add New HDD"), ENT_QUOTES, 'UTF-8'); ?></button>
        <button type="submit" name="action" value="bulk_remove">➖ <?php echo htmlspecialchars(__("Remove Selected"), ENT_QUOTES, 'UTF-8'); ?></button>
        <button type="submit" name="action" value="bulk_delete" onclick="return confirmDelete();">🗑️ <?php echo htmlspecialchars(__("Delete Selected"), ENT_QUOTES, 'UTF-8'); ?></button>
      </p>

      <h3><?php echo htmlspecialchars(__("Pending Destruction / Reuse"), ENT_QUOTES, 'UTF-8'); ?></h3>
      <table class="border">
        <thead>
          <tr>
            <th><input type="checkbox" onclick="$('input[name=select_pending\[\]]').prop('checked', this.checked);"></th>
            <th><?php echo htmlspecialchars(__("Label"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Serial No"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Date Withdrawn"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Actions"), ENT_QUOTES, 'UTF-8'); ?></th>
          </tr>
        </thead>
        <tbody>
<?php
foreach ($hddWaitList as $hdd) {
    $id = (int)$hdd->HDDID;
    echo '<tr>' .
         '<td><input type="checkbox" name="select_pending[]" value="' . $id . '"></td>' .
         '<td>' . htmlspecialchars($hdd->Label, ENT_QUOTES, 'UTF-8') . '</td>' .
         '<td>' . htmlspecialchars($hdd->SerialNo, ENT_QUOTES, 'UTF-8') . '</td>' .
         '<td>' . htmlspecialchars($hdd->DateWithdrawn, ENT_QUOTES, 'UTF-8') . '</td>' .
         '<td>' .
           '<button type="submit" name="action" value="destroy_' . $id . '">⚠️</button>' .
           '<button type="submit" name="action" value="reassign_' . $id . '">♻️</button>' .
           '<button type="submit" name="action" value="spare_' . $id . '">🔧</button>' .
         '</td>' .
         '</tr>';
}
?>
        </tbody>
      </table>
      <p>
        <button type="submit" name="action" value="bulk_destroy">⚠️ <?php echo htmlspecialchars(__("Destroy Selected"), ENT_QUOTES, 'UTF-8'); ?></button>
        <button type="submit" name="action" value="print_list">🖨️ <?php echo htmlspecialchars(__("Export List"), ENT_QUOTES, 'UTF-8'); ?></button>
      </p>
    </form>
    <div style="margin-top: 20px; text-align: right;">
      <a class="button" href="hdd_log_view.php?DeviceID=<?php echo (int)$device->DeviceID; ?>">
        <?php echo htmlspecialchars(__("View HDD Activity Log"), ENT_QUOTES, 'UTF-8'); ?>
      </a>
    </div>
  </div>
</div> <!-- End of main content -->

<!-- Modal Add HDD -->
<div id="hddModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
  <div style="background-color:#fff; margin:10% auto; padding:20px; border:1px solid #888; width:400px; position:relative;">
    <span onclick="closeModal()" style="position:absolute; right:10px; top:10px; cursor:pointer;">&times;</span>
    <h3><?php echo htmlspecialchars(__("Add New HDD"), ENT_QUOTES, 'UTF-8'); ?></h3>
    <form method="POST" action="savehdd.php">
      <input type="hidden" name="DeviceID" value="<?php echo (int)$device->DeviceID; ?>">
      <input type="hidden" name="action"   value="create_hdd_form">

      <label for="Label"><?php echo htmlspecialchars(__("Label"), ENT_QUOTES, 'UTF-8'); ?></label><br>
      <input type="text" name="Label" id="Label"    required><br><br>

      <label for="SerialNo"><?php echo htmlspecialchars(__("Serial No"), ENT_QUOTES, 'UTF-8'); ?></label><br>
      <input type="text" name="SerialNo" id="SerialNo" required><br><br>

      <label for="TypeMedia"><?php echo htmlspecialchars(__("Type"), ENT_QUOTES, 'UTF-8'); ?></label><br>
      <select name="TypeMedia" id="TypeMedia">
        <option value="HDD">HDD</option>
        <option value="SSD">SSD</option>
        <option value="MVME">MVME</option>
      </select><br><br>

      <label for="Size"><?php echo htmlspecialchars(__("Size (GB)"), ENT_QUOTES, 'UTF-8'); ?></label><br>
      <input type="number" name="Size" id="Size" value="0" min="0"><br><br>

      <label for="Note"><?php echo htmlspecialchars(__("Note"), ENT_QUOTES, 'UTF-8'); ?></label><br>
      <textarea name="Note" id="Note" rows="3"></textarea><br><br>

      <button type="submit"><?php echo htmlspecialchars(__("Add HDD"), ENT_QUOTES, 'UTF-8'); ?></button>
      <button type="button" onclick="closeModal()"><?php echo htmlspecialchars(__("Cancel"), ENT_QUOTES, 'UTF-8'); ?></button>
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