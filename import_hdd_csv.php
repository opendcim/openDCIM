<?php
// import_hdd_csv.php
// Batch CSV processing for HDD destruction automation (serial based)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.inc.php';
require_once __DIR__ . '/facilities.inc.php';
require_once __DIR__ . '/classes/hdd.class.php';

if (!$person->ManageHDD) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => __('Permission denied')]);
    exit;
}

$sendJson = function(array $payload, int $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
};

$detectDelimiter = function(string $line): string {
    $candidates = [',', ';', "\t", '|'];
    $best = ',';
    $max = 0;
    foreach ($candidates as $delim) {
        $count = substr_count($line, $delim);
        if ($count > $max) {
            $max = $count;
            $best = $delim;
        }
    }
    return $best;
};

try {
    $force = !empty($_POST['force']);
    $columnName = trim($_POST['csv_column'] ?? '');
    if ($columnName === '') {
        throw new Exception(__('Please select the CSV column containing serial numbers'));
    }

    if (!isset($_FILES['batch_csv'])) {
        throw new Exception(__('CSV file is required'));
    }

    if ($_FILES['batch_csv']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception(__('Error while uploading the CSV file'));
    }

    if ($_FILES['batch_csv']['size'] > 2 * 1024 * 1024) {
        throw new Exception(__('CSV file is too large (max 2 MB)'));
    }

    $csvTmp = $_FILES['batch_csv']['tmp_name'];
    $lines = file($csvTmp, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false || count($lines) === 0) {
        throw new Exception(__('Unable to read CSV file or file is empty'));
    }

    $headerLine = $lines[0];
    $delimiter = $detectDelimiter($headerLine);
    $headers = str_getcsv($headerLine, $delimiter);
    $headersNormalized = array_map(function($h) {
        return strtolower(trim($h));
    }, $headers);

    $targetIndex = array_search(strtolower($columnName), $headersNormalized, true);
    if ($targetIndex === false) {
        throw new Exception(__('Selected column was not found in the CSV header'));
    }

    $serials = [];
    for ($i = 1; $i < count($lines); $i++) {
        $row = str_getcsv($lines[$i], $delimiter);
        if (!isset($row[$targetIndex])) {
            continue;
        }
        $sn = trim($row[$targetIndex]);
        if ($sn !== '') {
            $serials[] = $sn;
        }
    }
    $serials = array_values(array_unique($serials));
    if (empty($serials)) {
        throw new Exception(__('No serial numbers were found in the selected column'));
    }

    $placeholders = implode(',', array_fill(0, count($serials), '?'));
    $stmt = $dbh->prepare("SELECT HDDID, SerialNo, Status, DateDestroyed FROM fac_HDD WHERE SerialNo IN ($placeholders)");
    $stmt->execute($serials);
    $foundActive = [];
    $alreadyProcessed = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $serial = $row['SerialNo'];
        $status = strtolower($row['Status']);
        $isDestroyed = ($status === 'destroyed') || (!empty($row['DateDestroyed']));
        if ($isDestroyed) {
            $alreadyProcessed[$serial] = (int)$row['HDDID'];
        } else {
            $foundActive[$serial] = (int)$row['HDDID'];
        }
    }

    $missing = array_values(array_diff($serials, array_keys($foundActive), array_keys($alreadyProcessed)));
    $recognizedIds = array_values($foundActive);
    $recognizedSerials = array_keys($foundActive);
    $alreadyProcessedSerials = array_keys($alreadyProcessed);

    if (!empty($missing) && !$force) {
        $sendJson([
            'require_confirm' => true,
            'missing' => $missing,
            'message' => sprintf(__('Found %d serial numbers. %d were not recognized. Continue processing the recognized entries?'), count($recognizedIds), count($missing))
        ]);
    }

    if (empty($recognizedIds)) {
        throw new Exception(__('No matching HDD serial numbers were available for processing'));
    }

    $applyDestroyStatus = !empty($_POST['apply_destroy_status']);
    $destroyDateInput = trim($_POST['destroy_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    if ($applyDestroyStatus) {
        if ($destroyDateInput === '') {
            throw new Exception(__('Please provide a destruction date when applying the destroyed status'));
        }
        $dt = DateTime::createFromFormat('Y-m-d', $destroyDateInput);
        if (!$dt) {
            throw new Exception(__('Invalid destruction date format (expected YYYY-MM-DD)'));
        }
        $destroyDateValue = $dt->format('Y-m-d 00:00:00');
    } else {
        $destroyDateValue = null;
    }

    $proofFileName = null;
    if (isset($_FILES['batch_proof']) && $_FILES['batch_proof']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['batch_proof']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception(__('Error while uploading the proof file'));
        }
        if ($_FILES['batch_proof']['size'] > 5 * 1024 * 1024) {
            throw new Exception(__('Proof file is too large (max 5 MB)'));
        }
        $allowedProofExtensions = [
            'pdf'  => ['application/pdf'],
            'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
            'ods'  => ['application/vnd.oasis.opendocument.spreadsheet', 'application/zip', 'application/octet-stream'],
        ];
        $proofExtension = strtolower(pathinfo($_FILES['batch_proof']['name'], PATHINFO_EXTENSION));
        if (!array_key_exists($proofExtension, $allowedProofExtensions)) {
            throw new Exception(__('Proof file type not allowed (PDF, XLS, XLSX or ODS only)'));
        }
        if (!class_exists('finfo')) {
            throw new Exception(__('The fileinfo PHP extension is required to validate the proof file'));
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $proofMime = $finfo->file($_FILES['batch_proof']['tmp_name']);
        if (!in_array($proofMime, $allowedProofExtensions[$proofExtension], true)) {
            throw new Exception(__('Proof file type not allowed (PDF, XLS, XLSX or ODS only)'));
        }
        $datePart = date('Ymd-His');
        $randPart = substr(bin2hex(random_bytes(4)), 0, 8);
        $proofFileName = "proof_batch_{$datePart}_{$randPart}.{$proofExtension}";

        $pathSetting = $config->ParameterArray['hdd_proof_path'] ?? 'assets/files/hdd/';
        $storageRoot = $pathSetting;
        if (preg_match('#^(?:[A-Za-z]:\\\\|/)#', $storageRoot) === 1) {
            $baseDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storageRoot), DIRECTORY_SEPARATOR);
        } else {
            $baseDir = rtrim(__DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($storageRoot, '/\\')), DIRECTORY_SEPARATOR);
        }
        if (!is_dir($baseDir)) {
            if (!@mkdir($baseDir, 0750, true)) {
                throw new Exception(__('Unable to create the storage directory') . ' : ' . $baseDir);
            }
        }
        if (!is_writable($baseDir)) {
            throw new Exception(__('Storage directory is not writable: ') . $baseDir);
        }
        $destPath = $baseDir . DIRECTORY_SEPARATOR . $proofFileName;
        if (!@move_uploaded_file($_FILES['batch_proof']['tmp_name'], $destPath)) {
            throw new Exception(__('Error while saving the proof file'));
        }
        @chmod($destPath, 0644);
    }

    $idPlaceholders = implode(',', array_fill(0, count($recognizedIds), '?'));
    if ($proofFileName !== null) {
        $proofStmt = $dbh->prepare("UPDATE fac_HDD SET ProofFile = ? WHERE HDDID IN ($idPlaceholders)");
        $proofParams = array_merge([$proofFileName], $recognizedIds);
        $proofStmt->execute($proofParams);
    }

    if ($applyDestroyStatus) {
        $statusStmt = $dbh->prepare("UPDATE fac_HDD SET Status='Destroyed', DateDestroyed = ? WHERE HDDID IN ($idPlaceholders)");
        $statusParams = array_merge([$destroyDateValue], $recognizedIds);
        $statusStmt->execute($statusParams);
    }

    $timestamp = date('c');
    $logLines = [];
    $logLines[] = 'Timestamp: '.$timestamp;
    $logLines[] = 'User: '.$person->UserID;
    $logLines[] = 'Notes: '.($notes !== '' ? $notes : __('None'));
    $logLines[] = sprintf('Processed serials (%d): %s', count($recognizedSerials), $recognizedSerials ? implode(', ', $recognizedSerials) : __('None'));
    $logLines[] = sprintf('Already processed serials (%d): %s', count($alreadyProcessedSerials), $alreadyProcessedSerials ? implode(', ', $alreadyProcessedSerials) : __('None'));
    $logLines[] = sprintf('Unknown serials (%d): %s', count($missing), $missing ? implode(', ', $missing) : __('None'));
    $logLines[] = 'Proof file: '.($proofFileName !== null ? $proofFileName : __('None'));
    if ($applyDestroyStatus) {
        $logLines[] = 'Destruction date: '.$destroyDateValue;
    }
    $logText = implode("\n", $logLines);

    $logSummary = json_encode([
        'timestamp' => $timestamp,
        'user' => $person->UserID,
        'notes' => $notes,
        'processed' => $recognizedSerials,
        'already_processed' => $alreadyProcessedSerials,
        'missing' => $missing
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    HDD::RecordGenericLog(null, $person->UserID, 'HDD_CSV_BATCH', $logSummary);

    $messageParts = [];
    $messageParts[] = sprintf(__('Processed %d serial numbers.'), count($recognizedIds));
    if (!empty($alreadyProcessedSerials)) {
        $messageParts[] = __('Serial numbers already processed! Only valid serials were handled.');
    }
    if (!empty($missing)) {
        $messageParts[] = sprintf(__('Skipped %d unknown serial numbers.'), count($missing));
    }
    if ($proofFileName !== null) {
        $messageParts[] = __('Proof file assigned to matching HDDs.');
    }

    $response = [
        'success' => true,
        'message' => implode(' ', $messageParts),
        'missing' => $missing,
        'log_text' => $logText,
        'reload' => true
    ];
    if (!empty($alreadyProcessedSerials)) {
        $response['already_message'] = __('Serial numbers already processed! Only valid serials were handled.');
    }
    $sendJson($response);
} catch (Exception $ex) {
    $sendJson(['success' => false, 'error' => $ex->getMessage()], 400);
}
