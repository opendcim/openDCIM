<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
foreach (['db.inc.php','facilities.inc.php','classes/hdd.class.php'] as $f) {
    require_once __DIR__ . "/{$f}";
}

$subheader = __("HDD Management");

if (!$person->ManageHDD) {
    header("Location: index.php"); exit;
}

// 4. Récupère DeviceID
$deviceID = filter_input(INPUT_GET, 'DeviceID', FILTER_VALIDATE_INT);
if (!$deviceID) {
    echo __('DeviceID is required'); exit;
}
$device = new Device(); $device->DeviceID = $deviceID;
if (!$device->GetDevice()) {
    echo __('Invalid DeviceID'); exit;
}
// Load Template
$template = new DeviceTemplate();
$template->TemplateID = $device->TemplateID;
$template->GetTemplateByID();
$template->LoadHDDConfig();

if (!$template->EnableHDDFeature) {
    echo '<div class="error">'.__("This equipment does not support HDD management.").'</div>';
    exit;
}
// Get lists
$hddList     = HDD::GetHDDByDevice($device->DeviceID);
$hddWaitList = HDD::GetPendingByDevice($device->DeviceID);
$hdddestroyedList = HDD::GetDestroyedHDDByDevice($device->DeviceID);

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

 .responsive-table {
overflow-x: auto;
align: center;
}
.table2{
    table {
      border-collapse: collapse;
      border: 1px;
      align: center;
      }
    th, td {
      padding: 1px;
      text-align: left;
      vertical-align: middle;
      table-layout: fixed;
      }
    th {
      background-color:rgba(242, 242, 242, 0.86);
      text-align: center;
  }
}
input[type="text"]{

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
    
    <table class="table2" style="margin: 0 auto;">
        <thead>
          <tr>
            <th><input type="checkbox" id="select_all_active" onclick="$('input[name=select_active\[\]]').prop('checked', this.checked);"></th>
            <th>#</th>
            <th><?php echo htmlspecialchars(__("Label"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Serial No"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Status"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Type"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Size (GB)"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Note"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Actions"), ENT_QUOTES, 'UTF-8'); ?></th>
          </tr>
        </thead>
        <tbody>
<?php
$i = 1;
foreach ($hddList as $hdd) {
    $id = (int)$hdd->HDDID;
    echo '<tr>'.
         '<td><input type="checkbox" class="select_active" name="select_active[]" value="'.$id.'"></td>'.
         '<td>'.$i.'</td>'.
         '<td><input type="text" name="Label['.$id.']" value="'.htmlspecialchars($hdd->Label, ENT_QUOTES, 'UTF-8').'"></td>'.
         '<td style="width: 150px;"><input type="text" name="SerialNo['.$id.']" value="'.htmlspecialchars($hdd->SerialNo, ENT_QUOTES, 'UTF-8').'"></td>'.
         '<td><select name="Status['.$id.']">'.
           '<option value="On"'.($hdd->Status === 'On' ? ' selected' : '').'>On</option>'.
           '<option value="Off"'.($hdd->Status === 'Off' ? ' selected' : '').'>Off</option>' .
           '<option value="Pending_destruction"'.($hdd->Status === 'Pending_destruction' ? ' selected' : '').'>Pending_destruction</option>'.
           '<option value="Destroyed"'.($hdd->Status === 'Destroyed' ? ' selected' : '').'>Destroyed</option>'.
           '<option value="Spare"'.($hdd->Status === 'Spare' ? ' selected' : '').'>Spare</option>'.
         '</select></td>'.
         '<td><select name="TypeMedia['.$id.']">'.
           '<option value="HDD"'.($hdd->TypeMedia === 'HDD' ? ' selected' : '').'>HDD</option>'.
           '<option value="SSD"'.($hdd->TypeMedia === 'SSD' ? ' selected' : '').'>SSD</option>'.
           '<option value="MVME"'.($hdd->TypeMedia === 'MVME' ? ' selected' : '').'>MVME</option>'.
         '</select></td>'.
         '<td><input type="number" name="Size['.$id.']" value="'.(int)$hdd->Size.'"></td>'.
         '<td><input type="text" name="Note['.$id.']" value="'.htmlspecialchars($hdd->Note ?? '', ENT_QUOTES, 'UTF-8').'"></td>'.
         '<td>'.
           '<button type="submit" name="action" value="update_'.$id.'" title="'.__("Update").'">✏️</button>'.
           '<button type="submit" name="action" value="remove_'.$id.'" title="'.__("Remove").'">➖</button>'.
           '<button type="submit" name="action" value="delete_'.$id.'" title="'.__("Delete").'" onclick="return confirmDelete();">🗑️</button>'.
           '<button type="submit" name="action" value="duplicate_'.$id.'" title="'.__("Duplicate all slot").'">📑</button>'.
         '</td>'.
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
        <button type="button" onclick="openModal2()">➕ <?php echo htmlspecialchars(__("Fast Add New HDD with scan S/N"), ENT_QUOTES, 'UTF-8'); ?></button>
      </p>

      <h3><?php echo htmlspecialchars(__("Pending Destruction / Reuse"), ENT_QUOTES, 'UTF-8'); ?></h3>
      
    <table class="table2" style="margin: 0 auto; width: 820px;">
        <thead>
          <tr>
            <th>#</th>
            <th><?php echo htmlspecialchars(__("Label"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Serial No"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Status"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Date Withdrawn"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Actions"), ENT_QUOTES, 'UTF-8'); ?></th>
          </tr>
        </thead>
        <tbody>
<?php
$i = 1;
foreach ($hddWaitList as $hdd) {
    $id = (int)$hdd->HDDID;
    echo '<tr>
         <td>'.$i.'</td>
         <td> '.htmlspecialchars($hdd->Label, ENT_QUOTES, "UTF-8").'</td>
         <td> '.htmlspecialchars($hdd->SerialNo, ENT_QUOTES, "UTF-8").'</td>
         <td> '.htmlspecialchars($hdd->Status, ENT_QUOTES, "UTF-8").'</td>
         <td> '.$hdd->DateWithdrawn.'</td>
         <td> 
           <button type="submit" title="'.__("Destroy Selected Permanently").'" name="action" value="destroy_'.$id.'">⚠️</button>
           <button type="submit" title="'.__("Reasign in slot").'" name="action" value="reassign_'.$id.'">↩️</button>
           <button type="submit" title="'.__("Spare").'" name="action" value="spare_'.$id.'">🔧</button>
           <button type="button" class="btn-assign-device" data-hddid="'.$id.'" data-bs-toggle="modal" data-bs-target="#assignModal" title="'.__("Assign other device").'">♻️ '.htmlspecialchars(__("Assign other device"), ENT_QUOTES, "UTF-8").'</button>
         </td>
         </tr>';
    $i++;
}
?>
        </tbody>
      </table>
  
      <p>
        <button type="submit" name="action" value="bulk_destroy">⚠️ <?php echo htmlspecialchars(__("Destroy Selected"), ENT_QUOTES, 'UTF-8'); ?></button>
        <button type="submit" name="action" value="export_list">🖨️ <?php echo htmlspecialchars(__("Export List"), ENT_QUOTES, 'UTF-8'); ?></button>
      </p>
      <!-- Spacer --><div><div>&nbsp;</div><div></div></div><!-- END Spacer -->
       <h3><?php echo htmlspecialchars(__("Destroyed"), ENT_QUOTES, 'UTF-8'); ?></h3>

      <table class="table2" style="margin: 0 auto; width: 820px;">
              <thead>
                <tr>
                  <th><input type="checkbox" id="select_all_destroyed" onclick="$('input[name=select_destroyed\[\]]').prop('checked', this.checked);"></th>
                  <th>#</th>
                  <th><?php echo htmlspecialchars(__("Label"), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(__("Serial No"), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(__("Status"), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(__("Date Destroyed"), ENT_QUOTES, 'UTF-8'); ?></th>
                </tr>
              </thead>
              <tbody>
                  <?php
                  $i = 1;
                  foreach ($hdddestroyedList as $hdd) {
                      $id = (int)$hdd->HDDID;
                      echo '<tr>
                          <td><input type="checkbox" class="select_pending" name="select_pending[]" value="'.$id.'"></td>
                          <td>'.$i.'</td>
                          <td> '.htmlspecialchars($hdd->Label, ENT_QUOTES, "UTF-8").'</td>
                          <td> '.htmlspecialchars($hdd->SerialNo, ENT_QUOTES, "UTF-8").'</td>
                          <td> '.htmlspecialchars($hdd->Status, ENT_QUOTES, "UTF-8").'</td>
                          <td> '.$hdd->DateDestroyed.'</td>
                          </tr>';
                      $i++;
                  }
                  ?>
              </tbody>
      </table>

    </form>
    <div style="margin-top: 20px; text-align: right;">
      <a class="button" href="hdd_log_view.php?DeviceID=<?php echo (int)$device->DeviceID; ?>">
        <?php echo htmlspecialchars(__("View HDD Activity Log"), ENT_QUOTES, 'UTF-8'); ?>
      </a></div>
    <div style="margin-top: 20px; text-align: right;">
      <?php
        if($deviceID >0){
            echo '<button type="button" name="auditHDD">',__("Certify Audit HDD"),'</button>';
          }
          ?></div>
    <div style="margin-top: 20px; text-align: right;">
      <a class="button" href="report_hdd.php">
        <?php echo htmlspecialchars(__("HDD report"), ENT_QUOTES, 'UTF-8'); ?>
      </a></div>

  </div> <!-- End of center div -->
      <div>
       <?php
          if($deviceID>0){
            print "   <a href=\"devices.php?DeviceID=$deviceID\">[ ".__("Return to Parent Device")." ]</a><br>\n";
            print "   <a href=\"cabnavigator.php?cabinetid=".$deviceID->GetDeviceCabinetID()."\">[ ".__("Return to Navigator")." ]</a>";
          }else{
            print "   <div><a href=\"index.php\">[ ".__("Return index")." ]</a></div>";
            }
            
        ?>
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
</div> <!-- End Modal Add HDD -->
<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="assignForm">
      <input type="hidden" name="hddid" id="assign_hddid" value="">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="assignModalLabel"><?= htmlspecialchars(__("Assign HDD to another device"), ENT_QUOTES, 'UTF-8') ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="deviceSearch" class="form-label"><?= htmlspecialchars(__("Search device"), ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" class="form-control" id="deviceSearch" placeholder="<?= htmlspecialchars(__("Type at least 2 chars…"), ENT_QUOTES, 'UTF-8') ?>">
            <ul id="deviceResults" class="list-group mt-2"></ul>
          </div>
          <input type="hidden" name="deviceid" id="assign_deviceid" value="">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(__("Cancel"), ENT_QUOTES, 'UTF-8') ?></button>
          <button type="submit" class="btn btn-primary" id="btnAssign" disabled><?= htmlspecialchars(__("Assign"), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
      </div>
    </form>
  </div>
</div> 
<!--End Assign Modal -->
</div> <!-- End of page -->

<script type="text/javascript">
  // Modal
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

  // Message confirm delete
    function confirmDelete() {
      return confirm("<?php echo __('This action is permanent and cannot be undone. Are you sure?'); ?>");
    }
  
  // Select all toggles
    function toggleAddHDDForm() {
      document.getElementById("addHDDModal").style.display = "block";
    }
    function closeAddHDDForm() {
      document.getElementById("addHDDModal").style.display = "none";
    }
  // Quand on change la case "select all" des actifs
    $('#select_all_active').on('change', function() {
    // coche ou décoche toutes les cases de classe .select_active
    $('.select_active').prop('checked', this.checked);
    });
  // Pareil pour les pending
    $('#select_all_pending').on('change', function() {
      $('.select_pending').prop('checked', this.checked);
    });

</script>
<script>
$(function(){
  var $modal   = $('#assignModal'),
      $hddid   = $('#assign_hddid'),
      $search  = $('#deviceSearch'),
      $results = $('#deviceResults'),
      $devId   = $('#assign_deviceid'),
      $btn     = $('#btnAssign');

  // 1) À l’ouverture, on initialise le HDDID
  $('.btn-assign-device').on('click', function(){
    $hddid.val( $(this).data('hddid') );
    $search.val('');
    $results.empty();
    $devId.val('');
    $btn.prop('disabled', true);
  });

  // 2) Recherche AJAX dès 2 caractères
  $search.on('input', function(){
    var q = $(this).val().trim();
    if(q.length < 2) {
      $results.empty();
      return;
    }
    $.getJSON('search_devices.php', { q: q }, function(items){
      $results.empty();
      if(!items.length){
        $results.append('<li class="list-group-item"><?= addslashes(__("No match")) ?></li>');
      }
      items.forEach(function(dev){
        $('<li class="list-group-item list-group-item-action">')
          .text(dev.Name + ' (ID '+ dev.DeviceID +')')
          .data('id', dev.DeviceID)
          .appendTo($results)
          .on('click', function(){
            $results.children().removeClass('active');
            $(this).addClass('active');
            $devId.val( $(this).data('id') );
            $btn.prop('disabled', false);
          });
      });
    });
  });

  // 3) Soumission du form en AJAX
  $('#assignForm').on('submit', function(e){
    e.preventDefault();
    $.post('assign_hdd.php', $(this).serialize(), function(resp){
      if(resp.success){
        window.location.reload();
      } else {
        alert('<?= addslashes(__("Error:")) ?> ' + resp.error);
      }
    }, 'json');
  });
});
</script>

</body>
</html>