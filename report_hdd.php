<?php
// report_hdd.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.inc.php';      // $dbh as PDO
require_once __DIR__ . '/facilities.inc.php';
require_once __DIR__ . '/classes/hdd.class.php';

// Access control
if (! $person->ManageHDD) {
    header("Location: index.php");
    exit;
}

// Subheader for template
$subheader = __("HDD Management Report");
    
// 1) Get filters from GET
$statusFilter      = $_GET['status']       ?? 'All';
$serialSearch      = $_GET['serial']       ?? '';
$deviceID          = (int)($_GET['deviceID'] ?? 0);
$DateAddFrom       = $_GET['DateAddFrom']         ?? '';
$DateAddTo         = $_GET['DateAddTo']           ?? '';
$DateWithFrom      = $_GET['DateWithdrawnFrom']   ?? '';
$DateWithTo        = $_GET['DateWithdrawnTo']     ?? '';
$DateDestFrom      = $_GET['DateDestroyedFrom']   ?? '';
$DateDestTo        = $_GET['DateDestroyedTo']     ?? '';

// 2) Build SQL filters
$where = [];
$params = [];
if ($statusFilter !== 'All') {
    $where[]  = 'h.Status = ?';
    $params[] = $statusFilter;
}
if ($serialSearch !== '') {
    $where[]  = 'h.SerialNo LIKE ?';
    $params[] = "%{$serialSearch}%";
}
if ($deviceID) {
    $where[]  = 'h.DeviceID = ?';
    $params[] = $deviceID;
}
if ($DateAddFrom) {
    $where[]  = 'h.DateAdd >= ?';
    $params[] = $DateAddFrom;
}
if ($DateAddTo) {
    $where[]  = 'h.DateAdd <= ?';
    $params[] = $DateAddTo;
}
if ($DateWithFrom) {
    $where[]  = 'h.DateWithdrawn >= ?';
    $params[] = $DateWithFrom;
}
if ($DateWithTo) {
    $where[]  = 'h.DateWithdrawn <= ?';
    $params[] = $DateWithTo;
}
if ($DateDestFrom) {
    $where[]  = 'h.DateDestroyed >= ?';
    $params[] = $DateDestFrom;
}
if ($DateDestTo) {
    $where[]  = 'h.DateDestroyed <= ?';
    $params[] = $DateDestTo;
}

// 3) Query database
$sql = "SELECT h.*, d.Label AS DeviceLabel
          FROM fac_HDD h
     LEFT JOIN fac_Device d ON d.DeviceID = h.DeviceID";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY h.DateAdd DESC';

$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$hddList = array_map(function(array $r) {
    $h = HDD::RowToObject($r);
    // Injecte ici le label de l’appareil parent
    $h->DeviceLabel = $r['DeviceLabel'] ?? '';
    return $h;
}, $rows);

// 4) Handle XLS export
if (isset($_GET['export']) && $_GET['export'] === 'xls') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="hdd_report.xls"');
    echo "<table><tr>"
       . "<th>#</th><th>HDDID</th><th>DeviceID</th><th>DeviceLabel</th>"
       . "<th>SerialNo</th><th>Status</th><th>Size</th><th>TypeMedia</th>"
       . "<th>DateAdd</th><th>DateWithdrawn</th><th>DateDestroyed</th></tr>";
    foreach ($rows as $i => $r) {
        echo '<tr>'
           . '<td>' . ($i + 1) . '</td>'
           . '<td>' . htmlspecialchars($r['HDDID'] ?? '', ENT_QUOTES) . '</td>'
           . '<td>' . htmlspecialchars($r['DeviceID'] ?? '', ENT_QUOTES) . '</td>'
           . '<td>' . htmlspecialchars($r['DeviceLabel'] ?? '', ENT_QUOTES) . '</td>'
           . '<td>' . htmlspecialchars($r['SerialNo'] ?? '', ENT_QUOTES) . '</td>'
           . '<td>' . htmlspecialchars($r['Status'] ?? '', ENT_QUOTES) . '</td>'
           . '<td>' . htmlspecialchars($r['Size'] ?? '', ENT_QUOTES) . '</td>'
           . '<td>' . htmlspecialchars($r['TypeMedia'] ?? '', ENT_QUOTES) . '</td>'
           . '<td>' . htmlspecialchars($r['DateAdd'] ?? '', ENT_QUOTES) . '</td>'
           . '<td>' . htmlspecialchars($r['DateWithdrawn'] ?? '', ENT_QUOTES) . '</td>'
           . '<td>' . htmlspecialchars($r['DateDestroyed'] ?? '', ENT_QUOTES) . '</td>'
           . '</tr>';
    }
    echo '</table>';
    exit;
}

// 5) Prepare upload directory
$uploadDir = __DIR__ . '/assets/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 6) Handle POST actions
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_destroyed') {
        $selected = $_POST['selected_hdd'] ?? [];
        if (empty($selected)) {
            $error = __('No disks selected for destruction.');
        } else {
            foreach ($selected as $hid) {
                HDD::MarkDestroyed((int)$hid);
            }
            $success = __('Selected disks have been marked as Destroyed.');
        }
    } elseif ($action === 'attach_proof') {
        $proofIds = $_POST['selected_hdd_proof'] ?? [];
        if (empty($proofIds)) {
            $error = __('No disks selected for proof attachment.');
        } else {
            $notDestroyed = array_filter($rows, function($r) use ($proofIds) {
                return in_array($r['HDDID'], $proofIds, true) && $r['Status'] !== 'Destroyed';
            });
            if ($notDestroyed) {
                $error = __('All selected disks must have status "Destroyed" to attach proof.');
            } elseif (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
                $error = __('Please attach a proof document (PDF).');
            } else {
                $ext      = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
                $ext      = preg_replace('/[^a-z0-9]/i', '', $ext);
                $filename = 'proof_' . time() . '.' . $ext;
                $dest     = $uploadDir . '/' . $filename;
                if (!move_uploaded_file($_FILES['proof']['tmp_name'], $dest)) {
                    $error = __('Failed to save the proof document.');
                } else {
                    $stmt = $dbh->prepare("UPDATE fac_HDD SET ProofDocument = ? WHERE HDDID = ?");
                    foreach ($proofIds as $hid) {
                        $stmt->execute([$filename, (int)$hid]);
                    }
                    $success = __('Proof document attached to selected destroyed disks.');
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars(__('Manage HDDs report'), ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="css/inventory.php" type="text/css">
    <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
    <script type="text/javascript" src="scripts/jquery.min.js"></script>
    <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
  <?php include( 'header.inc.php' ); ?>
<div class="page managehdd">
  <?php include( 'sidebar.inc.php' ); ?>
<div class="main">
  <div class="center">
    <h2><?php echo htmlspecialchars(__('Manage HDDs report'), ENT_QUOTES); ?></h2>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="get" id="filterForm" class="mb-3">
      <div class="row g-2 mb-3">
        <div class="col-auto">
          <label for="status"><?php echo __('Status'); ?></label>
          <select name="status" id="status">
            <option value="All">All</option>
            <?php foreach (['On','Off','Pending_destruction','Destroyed','Spare'] as $st): ?>
              <option value="<?= $st ?>" <?= $st === $statusFilter ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-auto">
          <label for="serial" class="form-label"><?php echo __('Serial'); ?></label>
          <input type="text" name="serial" id="serial" value="<?= htmlspecialchars($serialSearch) ?>" class="form-control">
        </div>
        <div class="col-auto">
          <label for="deviceID" class="form-label"><?php echo __('Device ID'); ?></label>
          <input type="number" name="deviceID" id="deviceID" value="<?= $deviceID ?>" class="form-control">
        </div>
        <div class="col-auto align-self-end">
          <button type="submit" class="btn btn-primary"><?php echo __('Filter'); ?></button>
          <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'xls'])) ?>" class="btn btn-success"><?php echo __('Export XLS'); ?> </a>
        </div>
      </div>

      <table class="table table-striped table-bordered">
        <thead class="table-light">
          <tr>
            <th>#</th><th>HDDID</th><th>DeviceID</th><th>Device Label</th><th>SerialNo</th>
            <th>Status</th><th>Size</th><th>Type</th>
            <th>DateAdd</th><th>DateWithdrawn</th><th>DateDestroyed</th>
            <th>Proof</th><th>Action</th>
          </tr>
          <tr>
            <?php for ($i = 0; $i < 9; $i++) echo '<th></th>'; ?>
            <th>
              <input type="date" name="DateAddFrom" value="<?= htmlspecialchars($DateAddFrom) ?>" class="form-control form-control-sm date-filter">
              <input type="date" name="DateAddTo"   value="<?= htmlspecialchars($DateAddTo) ?>"   class="form-control form-control-sm date-filter mt-1">
            </th>
            <th>
              <input type="date" name="DateWithdrawnFrom" value="<?= htmlspecialchars($DateWithFrom) ?>" class="form-control form-control-sm date-filter">
              <input type="date" name="DateWithdrawnTo"   value="<?= htmlspecialchars($DateWithTo) ?>"   class="form-control form-control-sm date-filter mt-1">
            </th>
            <th>
              <input type="date" name="DateDestroyedFrom" value="<?= htmlspecialchars($DateDestFrom) ?>" class="form-control form-control-sm date-filter">
              <input type="date" name="DateDestroyedTo"   value="<?= htmlspecialchars($DateDestTo) ?>"   class="form-control form-control-sm date-filter mt-1">
            </th>
            <th colspan="2"></th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; foreach ($hddList as $h): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($h->HDDID ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($h->DeviceID ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($h->DeviceLabel ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($h->SerialNo ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($h->Status ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($h->Size ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($h->TypeMedia ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($h->DateAdd ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($h->DateWithdrawn ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($h->DateDestroyed ?? '', ENT_QUOTES) ?></td>
            <td>
              <?php if (!empty($h->ProofDocument)): ?>
                <a href="assets/uploads/<?= rawurlencode($h->ProofDocument) ?>" target="_blank">View</a>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($h->Status === 'Pending_destruction'): ?>
                <input type="checkbox" name="selected_hdd[]" value="<?= $h->HDDID ?>" form="destroyForm">
              <?php elseif ($h->Status === 'Destroyed'): ?>
                <input type="checkbox" name="selected_hdd_proof[]" value="<?= $h->HDDID ?>" form="proofForm">
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </form>

    <?php

  // make array all GET :
  $filters = [
    'status'            => $statusFilter,
    'serial'            => $serialSearch,
    'deviceID'          => $deviceID,
    'DateAddFrom'       => $DateAddFrom,
    'DateAddTo'         => $DateAddTo,
    'DateWithdrawnFrom' => $DateWithFrom,
    'DateWithdrawnTo'   => $DateWithTo,
    'DateDestroyedFrom' => $DateDestFrom,
    'DateDestroyedTo'   => $DateDestTo,
  ];
?>
    <!-- Form to mark as destroyed -->
    <form id="destroyForm" method="post" class="mb-3">
          <?php foreach ($filters as $name => $value): ?>
    <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value, ENT_QUOTES) ?>">
  <?php endforeach; ?>
      <button type="submit" name="action" value="mark_destroyed" class="btn btn-warning"><?= __('Mark Selected as Destroyed') ?></button>
    </form>

    <!-- Form to attach proof -->
    <form id="proofForm" method="post" enctype="multipart/form-data" class="mb-3">
        <!-- Même champs cachés pour garder les filtres -->
  <?php foreach ($filters as $name => $value): ?>
    <input type="hidden" name="<?= htmlspecialchars($name) ?>"
           value="<?= htmlspecialchars($value, ENT_QUOTES) ?>">
  <?php endforeach; ?>
      <div class="mb-3">
        <label for="proof" class="form-label"><?= __('Proof Document (PDF)') ?></label>
        <input type="file" name="proof" id="proof" accept="application/pdf" class="form-control" required>
      </div>
      <button type="submit" name="action" value="attach_proof" class="btn btn-danger"><?= __('Attach Proof to Selected') ?></button>
    </form>

  </div>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
