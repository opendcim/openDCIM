<?php

foreach (['db.inc.php','facilities.inc.php','classes/hdd.class.php'] as $f) {
    require_once __DIR__ . "/{$f}";
}

$subheader = __("HDD Management");

if (!$person->ManageHDD) {
    header("Location: index.php"); exit;
}

// 4. Récupère DeviceID
$DeviceID = filter_input(INPUT_GET, 'DeviceID', FILTER_VALIDATE_INT);
if (!$DeviceID) {
    echo __('DeviceID is required'); exit;
}
$dev = new Device(); $dev->DeviceID = $DeviceID;
if (!$dev->GetDevice()) {
    echo __('Invalid DeviceID'); exit;
}
// Load Template
$template = new DeviceTemplate();
$template->TemplateID = $dev->TemplateID;
$template->GetTemplateByID();
$template->LoadHDDConfig();

if (!$template->EnableHDDFeature) {
    echo '<div class="error">'.__("This equipment does not support HDD management.").'</div>';
    exit;
}
// Get lists
$hddList     = HDD::GetHDDByDevice($dev->DeviceID);
$hddWaitList = HDD::GetPendingByDevice($dev->DeviceID);
$hdddestroyedList = HDD::GetDestroyedHDDByDevice($dev->DeviceID);
$hddSpareList = HDD::GetSpareHDDByDevice($dev->DeviceID);

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
   
  </style>
</head>
<body>
<?php include("header.inc.php"); ?>
<div class="page managehdd">
<?php include("sidebar.inc.php"); ?>
<div class="main">
  <div class="center">
    <h2><?php echo htmlspecialchars(__("Manage HDDs for Device") . ": " . $dev->Label, ENT_QUOTES, 'UTF-8'); ?></h2>
    <?php if (!empty($_SESSION['LastError'])) { echo '<div class="error">'.htmlspecialchars($_SESSION['LastError'], ENT_QUOTES, 'UTF-8').'</div>'; unset($_SESSION['LastError']); } ?>
    <?php if (!empty($_SESSION['Message']))   { echo '<div class="message">'.htmlspecialchars($_SESSION['Message'],   ENT_QUOTES, 'UTF-8').'</div>'; unset($_SESSION['Message']); } ?>
    <form method="POST" action="savehdd.php">
      <input type="hidden" name="DeviceID" value="<?php echo (int)$dev->DeviceID; ?>">
      <h3><?php echo htmlspecialchars(__("Active HDDs"), ENT_QUOTES, 'UTF-8'); ?></h3>
    
    <table class="table2">
        <thead>
          <tr>
            <th><input type="checkbox" id="select_all_active" onclick="$('input[name=select_active\[\]]').prop('checked', this.checked);"></th>
            <th>#</th>
            
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
    echo '<tr>
         <td><input type="checkbox" class="select_active" name="select_active[]" value="'.$id.'"></td>
         <td>'.$i.'</td>
         <td style="width: 150px;"><input type="text" name="SerialNo['.$id.']" value="'.htmlspecialchars($hdd->SerialNo, ENT_QUOTES, 'UTF-8').'"></td>
         <td><select name="Status['.$id.']">
           <option value="On"'.($hdd->Status === 'On' ? ' selected' : '').'>On</option>
           <option value="Off"'.($hdd->Status === 'Off' ? ' selected' : '').'>Off</option>
           <option value="Pending_destruction"'.($hdd->Status === 'Pending_destruction' ? ' selected' : '').'>Pending_destruction</option>
           <option value="Destroyed"'.($hdd->Status === 'Destroyed' ? ' selected' : '').'>Destroyed</option>
           <option value="Spare"'.($hdd->Status === 'Spare' ? ' selected' : '').'>Spare</option>
         </select></td>
         <td><select name="TypeMedia['.$id.']">
           <option value="HDD"'.($hdd->TypeMedia === 'HDD' ? ' selected' : '').'>HDD</option>
           <option value="SSD"'.($hdd->TypeMedia === 'SSD' ? ' selected' : '').'>SSD</option>
           <option value="MVME"'.($hdd->TypeMedia === 'MVME' ? ' selected' : '').'>MVME</option>
         </select></td>
         <td><input type="number" name="Size['.$id.']" value="'.(int)$hdd->Size.'"></td>
       
         <td>
           <button type="submit" name="action" value="update_'.$id.'" title="'.__("Update").'">✏️</button>
           <button type="submit" name="action" value="remove_'.$id.'" title="'.__("Remove pending destroy").'">➖</button>
           <button type="submit" name="action" value="delete_'.$id.'" title="'.__("Delete").'" onclick="return confirmDelete();">🗑️</button>
           <button type="submit" name="action" value="duplicate_'.$id.'" title="'.__("Duplicate all slot").'">📑</button>           
         </td>
         </tr>';
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

      <h3><?php echo htmlspecialchars(__("Pending Destroyed / Reuse"), ENT_QUOTES, 'UTF-8'); ?></h3>
      
    <table class="table2">
        <thead>
          <tr>
            <th><input type="checkbox" id="select_all_pending_destroyed" onclick="$('input[name=select_pending_destroyed\[\]]').prop('checked', this.checked);"></th>
            <th>#</th>
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
         <td><input type="checkbox" class="select_pending_destroyed" name="select_pending_destroyed[]" value="'.$id.'"></td>
         <td>'.$i.'</td>
         <td> '.htmlspecialchars($hdd->SerialNo, ENT_QUOTES, "UTF-8").'</td>
         <td> '.htmlspecialchars($hdd->Status, ENT_QUOTES, "UTF-8").'</td>
         <td> '.$hdd->DateWithdrawn.'</td>
         <td> 
           <button type="submit" title="'.__("Reasign in slot").'" name="action" value="reassign_'.$id.'">↩️</button>
           <button type="submit" title="'.__("Spare").'" name="action" value="spare_'.$id.'">🔧</button>
           <button type="button" class="btn-assign-device" data-hddid="'.$id.'" onclick="openAssignModal(this)" title="'.__("Assign other device").'">♻️</button>
         </td>
         </tr>';
    $i++;
}
?>
        </tbody>
      </table>
  
      <p>
        <button type="submit" name="action" value="bulk_destroy">⚠️ <?php echo htmlspecialchars(__("Destroy Selected"), ENT_QUOTES, 'UTF-8'); ?></button>
      </p><p>
        <button type="button" id="btnUploadProofMgmt" class="button"><?php echo __('Ajouter une preuve de destruction (PDF)'); ?></button>
      </p>
      <!-- table hdd destroyed -->
       <h3><?php echo htmlspecialchars(__("Destroyed"), ENT_QUOTES, 'UTF-8'); ?></h3>
      <table class="table2">
              <thead>
                <tr>
                  <th>#</th>
                  <th><?php echo htmlspecialchars(__("Serial No"), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(__("Status"), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(__("Date Destroyed"), ENT_QUOTES, 'UTF-8'); ?></th>
                  <th><?php echo htmlspecialchars(__("Proof of destruction"), ENT_QUOTES, 'UTF-8'); ?></th>
                </tr>
              </thead>
              <tbody>
                  <?php
                  $i = 1;
                  foreach ($hdddestroyedList as $hdd) {
                      $id = (int)$hdd->HDDID;
                      echo '<tr>
                          <td>'.$i.'</td>
                          <td> '.htmlspecialchars($hdd->SerialNo, ENT_QUOTES, "UTF-8").'</td>
                          <td> '.htmlspecialchars($hdd->Status, ENT_QUOTES, "UTF-8").'</td>
                          <td> '.$hdd->DateDestroyed.'</td>
                          <td>'.(!empty($hdd->ProofFile) ? '<a target="_blank" href="'.htmlspecialchars($hdd->ProofFile, ENT_QUOTES, 'UTF-8').'">'.__('Voir la preuve').'</a>' : '').'</td>
                          </tr>';
                      $i++;
                  }
                  ?>
              </tbody>
      </table>
<!-- table hdd spare -->
      <h3><?php echo htmlspecialchars(__("Spare"), ENT_QUOTES, 'UTF-8'); ?></h3>
      <table class="table2">
        <thead>
          <tr>
            <th><input type="checkbox" id="select_all_spare" onclick="$('input[name=select_spare\[\]]').prop('checked', this.checked);"></th>
            <th>#</th>
            <th><?php echo htmlspecialchars(__("Serial No"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Status"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Date Withdrawn"), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(__("Actions"), ENT_QUOTES, 'UTF-8'); ?></th>
          </tr>
        </thead>
        <tbody>
<?php
$i = 1;
foreach ($hddSpareList as $hdd) {
    $id = (int)$hdd->HDDID;
    echo '<tr>
         <td><input type="checkbox" class="select_spare" name="select_spare[]" value="'.$id.'"></td>
         <td>'.$i.'</td>
         <td> '.htmlspecialchars($hdd->SerialNo, ENT_QUOTES, "UTF-8").'</td>
         <td> '.htmlspecialchars($hdd->Status, ENT_QUOTES, "UTF-8").'</td>
         <td> '.$hdd->DateWithdrawn.'</td>
         <td> 
           <button type="submit" title="'.__("Pendind Destroy Selected Permanently").'" name="action" value="remove_'.$id.'">➖</button>
           <button type="submit" title="'.__("Reasign in slot").'" name="action" value="reassign_'.$id.'">↩️</button>
           <button type="button" class="btn-assign-device" data-hddid="'.$id.'" onclick="openAssignModal(this)" title="'.__("Assign other device").'">♻️</button>
           <button type="submit" name="action" value="delete_'.$id.'" title="'.__("Delete").'" onclick="return confirmDelete();">🗑️</button>
         </td>
         </tr>';
    $i++;
}
?>
        </tbody>
      </table>

    </form>
      
      <?php
        if($DeviceID >0){
      echo '<div style="margin-top: 20px; text-align: right;"><button type="button" name="auditHDD">',__("Certify Audit HDD"),'</button></div>
            <div style="margin-top: 20px; text-align: right;"><button type="button" onclick="window.location.href=\'report_hdd.php\';">'.__('Report HDD').'</button></div>
            <div style="margin-top: 20px; text-align: right;"><button type="submit" name="action" value="export_list">🖨️'.__('Export all to excel').'</button></div>
            <div style="margin-top: 20px; text-align: right;"><button type="button" onclick="window.location.href=\'hdd_log_view.php?DeviceID='.(int)$dev->DeviceID.'\';">'.__('View HDD Activity Log').'</button></div>';
          }
          ?>

  </div> <!-- End of center div -->
      <div>
       <?php
          if($DeviceID>0){
            print "   <a href=\"devices.php?DeviceID=$DeviceID\">[ ".__("Return to Parent Device")." ]</a><br>\n";
            print "   <a href=\"cabnavigator.php?cabinetid=".$dev->GetDeviceCabinetID()."\">[ ".__("Return to Navigator")." ]</a><br>\n";
            print "   <a href=\"dc_stats.php?dc=".$dev->GetDeviceDCID()."\">[ ".__("Return to DC")." ]</a>";
          }else{
            print "   <div><a href=\"index.php\">[ ".__("Return index")." ]</a></div>";
            }
            ?>
        </div>

</div> <!-- End of main content -->

<!-- Modal Add HDD -->
<div id="addHDDModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
  <div style="background-color:#fff; margin:10% auto; padding:20px; border:1px solid #888; width:300px; height:300px; position:relative;">
    <span onclick="closeModal()" style="position:absolute; right:10px; top:10px; cursor:pointer;">&times;</span>
    <h3><?php echo htmlspecialchars(__("Add New HDD"), ENT_QUOTES, 'UTF-8'); ?></h3>
    <form method="POST" action="savehdd.php" class="center">
      <input type="hidden" name="DeviceID" value="<?php echo (int)$dev->DeviceID; ?>">
      <input type="hidden" name="action"   value="create_hdd_form">

      <label for="SerialNo"><?php echo htmlspecialchars(__("Serial No"), ENT_QUOTES, 'UTF-8'); ?></label><br>
      <input type="text" name="SerialNo" id="SerialNo" required>
      <button id="pasteBtn" title="Paste from Clipboard">📋</button><br><br>

      <label for="TypeMedia"><?php echo htmlspecialchars(__("Type"), ENT_QUOTES, 'UTF-8'); ?></label><br>
      <select name="TypeMedia" id="TypeMedia">
        <option value="HDD">HDD</option>
        <option value="SSD">SSD</option>
        <option value="MVME">MVME</option>
      </select><br><br>

      <label for="Size"><?php echo htmlspecialchars(__("Size (GB)"), ENT_QUOTES, 'UTF-8'); ?></label><br>
      <input type="number" name="Size" id="Size" value="0" min="0"><br><br>

      <button type="submit"><?php echo htmlspecialchars(__("Add HDD"), ENT_QUOTES, 'UTF-8'); ?></button>
      <button type="button" onclick="closeModal()"><?php echo htmlspecialchars(__("Cancel"), ENT_QUOTES, 'UTF-8'); ?></button>
    </form>
  </div>
</div> <!-- End Modal Add HDD -->

</div> <!-- End of page -->

<!-- Modal Upload Proof -->
<div id="uploadProofModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
  <div style="background-color:#fff; margin:10% auto; padding:20px; border:1px solid #888; width:420px; position:relative;">
    <span id="closeUploadProofModal" style="position:absolute; right:10px; top:10px; cursor:pointer;">&times;</span>
    <h3><?php echo __('Ajouter une preuve de destruction'); ?></h3>
    <form id="uploadProofForm" method="post" action="upload_hdd_proof.php" enctype="multipart/form-data">
      <input type="hidden" name="return" value="managementhdd.php?DeviceID=<?php echo (int)$dev->DeviceID; ?>">
      <div>
        <label for="proof_pdf_m"><?php echo __('Fichier PDF (max 5 Mo)'); ?></label>
        <input type="file" id="proof_pdf_m" name="proof_pdf" accept="application/pdf" required>
      </div>
      <div style="margin-top:15px; text-align:right;">
        <button type="submit" class="button"><?php echo __('Téléverser'); ?></button>
      </div>
    </form>
  </div>
</div>

</div> <!-- End of page -->

<script type="text/javascript">
  // Modal
  function openModal() {
  document.getElementById('addHDDModal').style.display = 'block';
  }
  function closeModal() {
  document.getElementById('addHDDModal').style.display = 'none';
  }
  window.onclick = function(event) {
  if (event.target == document.getElementById('addHDDModal')) {
    closeModal();
  }
  }
  // Modal function paste from clipboard
  document.getElementById('pasteBtn').addEventListener('click', async () => {
      try {
        const text = await navigator.clipboard.readText();
        document.getElementById('SerialNo').value = text;
      } catch (err) {
        console.error('Error read clipboard :', err);
        alert('Paste failed: Check browser permissions.');
      }
    });

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
    $('#select_all_pending_destroyed').on('change', function() {
      $('.select_pending_destroyed').prop('checked', this.checked);
    });

  // Upload proof modal flow
  $('#btnUploadProofMgmt').on('click', function(){
    var ids = $('input.select_pending_destroyed:checked').map(function(){return this.value;}).get();
    if(ids.length === 0){
      alert('<?php echo addslashes(__('Veuillez sélectionner au moins un HDD')); ?>');
      return;
    }
    $('#uploadProofForm input[name="hdd_ids[]"]').remove();
    ids.forEach(function(id){
      $('<input>').attr({type:'hidden', name:'hdd_ids[]', value:id}).appendTo('#uploadProofForm');
    });
    $('#uploadProofModal').show();
  });
  $('#closeUploadProofModal').on('click', function(){ $('#uploadProofModal').hide(); });

</script>
</body>
</html>
