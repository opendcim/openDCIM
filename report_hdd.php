<?php
// report_hdd.php (refactored dynamic table with DataTables)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.inc.php';
require_once __DIR__ . '/facilities.inc.php';
require_once __DIR__ . '/classes/hdd.class.php';

// Access control
if (!($person->ManageHDD || $person->SiteAdmin || $person->ReadAccess)) {
    echo __('This report requires global read access.');
    header('Refresh: 3; url=' . redirect());
    exit;
}

// Subheader for template
$subheader = __('HDD Management Report');

// 1) Build SQL: HDD en attente de destruction (avec contexte site)
$sql = "
    SELECT
      h.HDDID,
      h.DeviceID,
      d.Label AS DeviceLabel,
      dc.Name AS SiteName,
      h.SerialNo,
      h.Status,
      h.Size,
      h.TypeMedia,
      h.DateAdd,
      h.DateWithdrawn,
      h.DateDestroyed,
      h.ProofFile
    FROM fac_HDD h
    LEFT JOIN fac_Device d ON d.DeviceID = h.DeviceID
    LEFT JOIN fac_Cabinet c ON c.CabinetID = d.Cabinet
    LEFT JOIN fac_DataCenter dc ON dc.DataCenterID = c.DataCenterID
    WHERE h.Status = 'Pending_destruction'
";

// Execute query
$stmt = $dbh->prepare($sql);
$stmt->execute();
$hddList = $stmt->fetchAll(PDO::FETCH_OBJ);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($subheader, ENT_QUOTES); ?></title>
  <!-- Include DataTables CSS -->
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.dataTables.min.css" type="text/css">
    <!-- Include jQuery and DataTables JS -->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="scripts/pdfmake.min.js"></script>
  <script type="text/javascript" src="scripts/vfs_fonts.js"></script>
  <style type="text/css">
    div.dt-buttons { float: left; }
    #export_filter { float: left; margin-left: 25px; }
    .toolbar { margin: 10px 0; }
  </style>
  <script type="text/javascript">
    $(document).ready(function(){
      var table = $('#hdds').DataTable({
        "drawCallback": function( settings ) {
					redraw();resize();
				},
        dom: 'B<"clear">lfrtip',
        buttons: [
          {
            extend: 'excel',
            title: '<?php echo addslashes($subheader); ?>'
          }

        ]
      });
      // Filtres simples
      $('#filterStatus').on('change', function(){
        table.column(6).search(this.value).draw();
      });
      $('#filterSite').on('keyup change', function(){
        table.column(1).search(this.value).draw();
      });

      // Sélection
      $('#select_all').on('change', function(){
        var checked = this.checked;
        $('input.hdd-select').prop('checked', checked);
      });
      $('#btnUploadProof').on('click', function(){
        var ids = $('input.hdd-select:checked').map(function(){return this.value;}).get();
        if(ids.length === 0){
          alert('<?php echo addslashes(__('Veuillez sélectionner au moins un HDD')); ?>');
          return;
        }
        // Build and submit modal
        $('#uploadForm input[name="hdd_ids[]"]').remove();
        ids.forEach(function(id){
          $('<input>').attr({type:'hidden', name:'hdd_ids[]', value:id}).appendTo('#uploadForm');
        });
        $('#uploadModal').show();
      });
      $('#closeUploadModal').on('click', function(){ $('#uploadModal').hide(); });
    });
    function redraw(){
			if(($('#hdds').outerWidth()+$('#sidebar').outerWidth()+10)<$('.page').innerWidth()){
				$('.main').width($('#header').innerWidth()-$('#sidebar').outerWidth()-16);
			}else{
				$('.main').width($('#hdds').outerWidth()+40);
			}
			$('.page').width($('.main').outerWidth()+$('#sidebar').outerWidth()+10);
		}
  </script>
  <?php include 'header.inc.php'; ?>
</head>
<body>
  <div class="page managehdd">
    <?php include 'sidebar.inc.php'; ?>
    <div class="main">
      <div class="center">
        <h2><?php echo htmlspecialchars($subheader, ENT_QUOTES); ?></h2>
        <?php if (!empty($_SESSION['LastError'])) { echo '<div class="error">'.htmlspecialchars($_SESSION['LastError'], ENT_QUOTES, 'UTF-8').'</div>'; unset($_SESSION['LastError']); } ?>
        <?php if (!empty($_SESSION['Message']))   { echo '<div class="message">'.htmlspecialchars($_SESSION['Message'],   ENT_QUOTES, 'UTF-8').'</div>'; unset($_SESSION['Message']); } ?>

        <div class="toolbar">
          <label><?php echo __('Filtrer par statut'); ?>:
            <select id="filterStatus">
              <option value="">-- <?php echo __('Tous'); ?> --</option>
              <option value="Pending_destruction">Pending_destruction</option>
              <option value="Destroyed">Destroyed</option>
              <option value="On">On</option>
              <option value="Off">Off</option>
              <option value="Spare">Spare</option>
            </select>
          </label>
          <label style="margin-left:15px;">Site:
            <input type="text" id="filterSite" placeholder="<?php echo __('Site'); ?>">
          </label>
          <button id="btnUploadProof" class="button"><?php echo __('Ajouter une preuve de destruction (PDF)'); ?></button>
        </div>

        <table id="hdds" class="display stripe hover" style="width:100%">
          <thead>
            <tr>
              <th><input type="checkbox" id="select_all"></th>
              <th><?php echo __('Site'); ?></th>
              <th><?php echo __('Device ID'); ?></th>
              <th><?php echo __('Device Label'); ?></th>
              <th><?php echo __('HDDID'); ?></th>
              <th><?php echo __('Serial No HDD'); ?></th>
              <th><?php echo __('Status'); ?></th>
              <th><?php echo __('Size'); ?></th>
              <th><?php echo __('Type'); ?></th>
              <th><?php echo __('Date Added'); ?></th>
              <th><?php echo __('Date Withdrawn'); ?></th>
              <th><?php echo __('Date Destroyed'); ?></th>
              <th><?php echo __('Proof'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($hddList as $h): ?>
              <tr>
                <td><input type="checkbox" class="hdd-select" value="<?= (int)$h->HDDID ?>"></td>
                <td><?= htmlspecialchars($h->SiteName ?? '', ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->DeviceID, ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->DeviceLabel ?? '',ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->HDDID, ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->SerialNo, ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->Status, ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->Size, ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->TypeMedia, ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->DateAdd ?? '', ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->DateWithdrawn ?? '',ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->DateDestroyed ?? '',ENT_QUOTES) ?></td>
                <td>
                  <?php if (!empty($h->ProofFile)): ?>
                    <a href="<?= htmlspecialchars($h->ProofFile, ENT_QUOTES) ?>" target="_blank"><?php echo __('Voir la preuve'); ?></a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Upload modal -->
        <div id="uploadModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
          <div style="background-color:#fff; margin:10% auto; padding:20px; border:1px solid #888; width:400px; position:relative;">
            <span id="closeUploadModal" style="position:absolute; right:10px; top:10px; cursor:pointer;">&times;</span>
            <h3><?php echo __('Ajouter une preuve de destruction'); ?></h3>
            <form id="uploadForm" method="post" action="upload_hdd_proof.php" enctype="multipart/form-data">
              <input type="hidden" name="return" value="report_hdd.php">
              <div>
                <label for="proof_pdf"><?php echo __('Fichier PDF (max 5 Mo)'); ?></label>
                <input type="file" id="proof_pdf" name="proof_pdf" accept="application/pdf" required>
              </div>
              <div style="margin-top:15px; text-align:right;">
                <button type="submit" class="button"><?php echo __('Téléverser'); ?></button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

  
</body>
</html>
