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

// 1) Build SQL: include Device Label via JOIN
$sql = "
    SELECT
      h.HDDID,
      h.DeviceID,
      d.Label AS DeviceLabel,
      h.SerialNo,
      h.Status,
      h.Size,
      h.TypeMedia,
      h.DateAdd,
      h.DateWithdrawn,
      h.DateDestroyed,
      h.ProofDocument
    FROM fac_HDD h
    LEFT JOIN fac_Device d ON d.DeviceID = h.DeviceID
";

// Execute query
$stmt = $dbh->prepare($sql);
$stmt->execute();
$hddList = $stmt->fetchAll(PDO::FETCH_OBJ);
?>
<!doctype html>
<html lang="en">
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
  </style>
  <script type="text/javascript">
    $(document).ready(function(){
      $('#hdds').DataTable({
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

        <table id="hdds" class="display stripe hover" style="width:100%">
          <thead>
            <tr>
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
                  <?php if (!empty($h->ProofDocument)): ?>
                    <a href="assets/uploads/<?=
rawurlencode($h->ProofDocument) ?>" target="_blank"><?php echo
__('View'); ?></a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </div>
    </div>
  </div>

  
</body>
</html>