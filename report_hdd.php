<?php
// report_hdd.php

require 'db.inc.php';              // Initialise $db (mysqli)
date_default_timezone_set('Europe/Paris');

// Répertoire d’uploads
$uploadDir = __DIR__ . '/assets/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Messages
$error   = '';
$success = '';

// 1) Traitement du POST “Mark Destroyed”
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_destroyed') {
    $selected = $_POST['selected_hdd'] ?? [];
    if (empty($selected)) {
        $error = 'Aucun disque sélectionné.';
    }
    if (empty($error)) {
        if (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Veuillez joindre la preuve de destruction (PDF).';
        }
    }
    if (empty($error)) {
        $ext      = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
        $ext      = preg_replace('/[^a-z0-9]/i','',$ext);
        $filename = 'proof_' . time() . '.' . $ext;
        $dest     = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($_FILES['proof']['tmp_name'], $dest)) {
            $error = 'Échec de l’enregistrement du fichier.';
        } else {
            $stmt = $db->prepare("
                UPDATE fac_HDD
                   SET Status = 'Destroyed',
                       DateDestroyed = NOW(),
                       ProofDocument = ?
                 WHERE HDDID = ?
            ");
            foreach ($selected as $hid) {
                $hddid = intval($hid);
                $stmt->bind_param('si', $filename, $hddid);
                $stmt->execute();
            }
            $success = 'Disques marqués « Destroyed » avec preuve enregistrée.';
        }
    }
}

// 2) Traitement de l’export XLS
if (isset($_GET['export']) && $_GET['export'] === 'xls') {
    // Récupération des filtres GET
    $statusFilter        = $_GET['status']              ?? '';
    $DateAddFrom         = $_GET['DateAddFrom']         ?? '';
    $DateAddTo           = $_GET['DateAddTo']           ?? '';
    $DateWithdrawnFrom   = $_GET['DateWithdrawnFrom']   ?? '';
    $DateWithdrawnTo     = $_GET['DateWithdrawnTo']     ?? '';
    $DateDestroyedFrom   = $_GET['DateDestroyedFrom']   ?? '';
    $DateDestroyedTo     = $_GET['DateDestroyedTo']     ?? '';

    // Construction dynamique du WHERE
    $where  = []; $types = ''; $params = [];
    if ($statusFilter && $statusFilter !== 'All') {
        $where[]  = 'h.Status = ?'; $types .= 's'; $params[] = $statusFilter;
    }
    if ($DateAddFrom) {
        $where[]  = 'h.DateAdd >= ?'; $types .= 's'; $params[] = $DateAddFrom;
    }
    if ($DateAddTo) {
        $where[]  = 'h.DateAdd <= ?'; $types .= 's'; $params[] = $DateAddTo;
    }
    if ($DateWithdrawnFrom) {
        $where[]  = 'h.DateWithdrawn >= ?'; $types .= 's'; $params[] = $DateWithdrawnFrom;
    }
    if ($DateWithdrawnTo) {
        $where[]  = 'h.DateWithdrawn <= ?'; $types .= 's'; $params[] = $DateWithdrawnTo;
    }
    if ($DateDestroyedFrom) {
        $where[]  = 'h.DateDestroyed >= ?'; $types .= 's'; $params[] = $DateDestroyedFrom;
    }
    if ($DateDestroyedTo) {
        $where[]  = 'h.DateDestroyed <= ?'; $types .= 's'; $params[] = $DateDestroyedTo;
    }

    // Requête d’export
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
      FROM fac_HDD h
      LEFT JOIN fac_Device d ON h.DeviceID = d.DeviceID
      " . ($where ? "WHERE " . implode(' AND ', $where) : "") . "
      ORDER BY h.HDDID
    ";
    $stmt = $db->prepare($sql);
    if ($where) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    // Envoi du XLS
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="report_hdd.xls"');
    echo "HDDID\tDeviceID\tDeviceLabel\tLabel\tSerialNo\tStatus\tSize\tTypeMedia\tDateAdd\tDateWithdrawn\tDateDestroyed\tNote\n";
    while ($r = $res->fetch_assoc()) {
        $cols = [
            $r['HDDID'],
            $r['DeviceID'],
            $r['DeviceLabel'],
            $r['SerialNo'],
            $r['Status'],
            $r['Size'],
            $r['TypeMedia'],
            $r['DateAdd'],
            $r['DateWithdrawn'],
            $r['DateDestroyed'],
        ];
        $escaped = array_map(function($v){
            return str_replace(["\t","\r\n","\n"], [' ', ' ', ' '], $v);
        }, $cols);
        echo implode("\t", $escaped) . "\n";
    }
    exit;
}

// 3) Lecture des filtres GET pour l’affichage
$statusFilter        = $_GET['status']              ?? '';
$DateAddFrom         = $_GET['DateAddFrom']         ?? '';
$DateAddTo           = $_GET['DateAddTo']           ?? '';
$DateWithdrawnFrom   = $_GET['DateWithdrawnFrom']   ?? '';
$DateWithdrawnTo     = $_GET['DateWithdrawnTo']     ?? '';
$DateDestroyedFrom   = $_GET['DateDestroyedFrom']   ?? '';
$DateDestroyedTo     = $_GET['DateDestroyedTo']     ?? '';

// 4) Construction dynamique du WHERE pour l’affichage
$where  = []; $types = ''; $params = [];
if ($statusFilter && $statusFilter !== 'All') {
    $where[]  = 'h.Status = ?'; $types .= 's'; $params[] = $statusFilter;
}
if ($DateAddFrom) {
    $where[]  = 'h.DateAdd >= ?'; $types .= 's'; $params[] = $DateAddFrom;
}
if ($DateAddTo) {
    $where[]  = 'h.DateAdd <= ?'; $types .= 's'; $params[] = $DateAddTo;
}
if ($DateWithdrawnFrom) {
    $where[]  = 'h.DateWithdrawn >= ?'; $types .= 's'; $params[] = $DateWithdrawnFrom;
}
if ($DateWithdrawnTo) {
    $where[]  = 'h.DateWithdrawn <= ?'; $types .= 's'; $params[] = $DateWithdrawnTo;
}
if ($DateDestroyedFrom) {
    $where[]  = 'h.DateDestroyed >= ?'; $types .= 's'; $params[] = $DateDestroyedFrom;
}
if ($DateDestroyedTo) {
    $where[]  = 'h.DateDestroyed <= ?'; $types .= 's'; $params[] = $DateDestroyedTo;
}

// 5) Requête principale
$sql = "
  SELECT
    h.*,
    d.Label AS DeviceLabel
  FROM fac_HDD h
  LEFT JOIN fac_Device d ON h.DeviceID = d.DeviceID
  " . ($where ? "WHERE " . implode(' AND ', $where) : "") . "
  ORDER BY h.HDDID
";
$stmt = $db->prepare($sql);
if ($where) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res  = $stmt->get_result();
$hdds = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Report HDD</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .date-filter { width: 100px; }
  </style>
</head>
<body class="p-4">
  <div class="container">
    <h1 class="mb-4">Report des HDD</h1>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- 6) Formulaire GET pour filtres + affichage du tableau -->
    <form method="get" id="filterForm" class="mb-3">
      <div class="row g-2 mb-3">
        <div class="col-auto">
          <label for="status" class="form-label">Statut</label>
          <select name="status" id="status" class="form-select">
            <option value="All">Tous</option>
            <?php foreach (['On','Off','Pending_destruction','Destroyed','Spare'] as $st): ?>
              <option value="<?= $st ?>" <?= $st === $statusFilter ? 'selected' : '' ?>>
                <?= $st ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-auto align-self-end">
          <button type="submit" class="btn btn-primary">Filtrer</button>
          <a href="report_hdd.php?<?= http_build_query(array_merge($_GET, ['export'=>'xls'])) ?>"
             class="btn btn-success">Export XLS</a>
        </div>
      </div>

      <table class="table table-striped table-bordered">
        <thead class="table-light">
          <!-- En-têtes fixes -->
          <tr>
            <th>#</th>
            <th>HDDID</th>
            <th>DeviceID</th>
            <th>Device Label</th>
            <th>SerialNo</th>
            <th>Status</th>
            <th>Size</th>
            <th>Type</th>
            <th>DateAdd</th>
            <th>DateWithdrawn</th>
            <th>DateDestroyed</th>
            <th>Preuve</th>
            <th>Sélection</th>
          </tr>
          <!-- Filtres date dans l’en-tête -->
          <tr>
            <?php for ($c = 0; $c < 9; $c++) echo '<th></th>'; ?>
            <th>
              <input type="date"  name="DateAddFrom"       value="<?= htmlspecialchars($DateAddFrom) ?>"       class="form-control form-control-sm date-filter">
              <input type="date"  name="DateAddTo"         value="<?= htmlspecialchars($DateAddTo) ?>"         class="form-control form-control-sm date-filter mt-1">
            </th>
            <th>
              <input type="date"  name="DateWithdrawnFrom" value="<?= htmlspecialchars($DateWithdrawnFrom) ?>" class="form-control form-control-sm date-filter">
              <input type="date"  name="DateWithdrawnTo"   value="<?= htmlspecialchars($DateWithdrawnTo) ?>"   class="form-control form-control-sm date-filter mt-1">
            </th>
            <th>
              <input type="date"  name="DateDestroyedFrom" value="<?= htmlspecialchars($DateDestroyedFrom) ?>" class="form-control form-control-sm date-filter">
              <input type="date"  name="DateDestroyedTo"   value="<?= htmlspecialchars($DateDestroyedTo) ?>"   class="form-control form-control-sm date-filter mt-1">
            </th>
            <th colspan="3"></th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; foreach ($hdds as $h): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= $h['HDDID'] ?></td>
            <td><?= $h['DeviceID'] ?></td>
            <td><?= htmlspecialchars($h['DeviceLabel'] ?? '') ?></td>
            <td><?= htmlspecialchars($h['SerialNo'],     ENT_QUOTES) ?></td>
            <td><?= $h['Status'] ?></td>
            <td><?= $h['Size'] ?></td>
            <td><?= $h['TypeMedia'] ?></td>
            <td><?= $h['DateAdd'] ?></td>
            <td><?= $h['DateWithdrawn'] ?></td>
            <td><?= $h['DateDestroyed'] ?></td>
            <td>
              <?php if (!empty($h['ProofDocument'])): ?>
                <a href="uploads/<?= rawurlencode($h['ProofDocument']) ?>" target="_blank">Voir</a>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if ($h['Status'] === 'Pending_destruction'): ?>
                <input
                  type="checkbox"
                  name="selected_hdd[]"
                  value="<?= $h['HDDID'] ?>"
                  form="markForm"
                >
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </form>

    <!-- 7) Formulaire POST pour MarkDestroyed -->
    <form
      id="markForm"
      method="post"
      action="report_hdd.php?<?= http_build_query($_GET) ?>"
      enctype="multipart/form-data"
      class="mt-3"
    >
      <div class="mb-3">
        <label for="proof" class="form-label">Preuve de destruction (PDF)</label>
        <input
          type="file"
          name="proof"
          id="proof"
          accept="application/pdf"
          class="form-control"
          required
        >
      </div>
      <button
        type="submit"
        name="action"
        value="mark_destroyed"
        class="btn btn-danger"
      >
        Marquer « Destroyed »
      </button>
    </form>
  </div>

  <!-- JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
