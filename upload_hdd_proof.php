<?php
// upload_hdd_proof.php
// Secure upload endpoint for associating a destruction proof (PDF / XLS / XLSX / ODS) to one or more HDDs.
// Comment: robust validation, safe storage, explicit error feedback.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.inc.php';
require_once __DIR__ . '/facilities.inc.php';
require_once __DIR__ . '/classes/hdd.class.php';

if (!$person->ManageHDD) {
    header('Location: index.php');
    exit;
}

$return = $_POST['return'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index.php');
$isAjax = !empty($_POST['ajax']);

try {
    // IDs selection
    $ids = $_POST['hdd_ids'] ?? [];
    if (!is_array($ids) || count($ids) === 0) {
        throw new Exception(__('No HDD selected'));
    }
    $ids = array_values(array_unique(array_map('intval', $ids)));

    // File presence and PHP upload error
    if (!isset($_FILES['proof_pdf'])) {
        throw new Exception(__('File upload error'));
    }
    if ($_FILES['proof_pdf']['error'] !== UPLOAD_ERR_OK) {
        $e = $_FILES['proof_pdf']['error'];
        $map = [
            UPLOAD_ERR_INI_SIZE   => __('File too large (max 5 MB)'),
            UPLOAD_ERR_FORM_SIZE  => __('File too large (max 5 MB)'),
            UPLOAD_ERR_PARTIAL    => __('File upload error'),
            UPLOAD_ERR_NO_FILE    => __('No file provided'),
            UPLOAD_ERR_NO_TMP_DIR => __('Temporary directory is missing'),
            UPLOAD_ERR_CANT_WRITE => __('Unable to write the file to disk'),
            UPLOAD_ERR_EXTENSION  => __('Upload blocked by a PHP extension'),
        ];
        throw new Exception($map[$e] ?? __('File upload error'));
    }

    $file = $_FILES['proof_pdf'];

    // Size <= 5 MiB
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception(__('File too large (max 5 MB)'));
    }

    $allowedExtensions = [
        'pdf'  => ['application/pdf'],
        'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'ods'  => ['application/vnd.oasis.opendocument.spreadsheet', 'application/zip', 'application/octet-stream'],
    ];

    $originalExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!array_key_exists($originalExtension, $allowedExtensions)) {
        throw new Exception(__('File type not allowed (PDF, XLS, XLSX or ODS only)'));
    }

    // MIME check
    if (!class_exists('finfo')) {
        throw new Exception(__('The fileinfo PHP extension is not available on this server'));
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedExtensions[$originalExtension], true)) {
        throw new Exception(__('File type not allowed (PDF, XLS, XLSX or ODS only)'));
    }

    $applyDestroyStatus = !empty($_POST['apply_destroy_status']);
    $destroyDateInput = trim($_POST['destroy_date'] ?? '');
    $destroyDateValue = null;
    if ($applyDestroyStatus) {
        if ($destroyDateInput === '') {
            throw new Exception(__('Please select a destruction date when applying the destroyed status'));
        }
        $dateObject = DateTime::createFromFormat('Y-m-d', $destroyDateInput);
        if (!$dateObject) {
            throw new Exception(__('Invalid destruction date format (expected YYYY-MM-DD)'));
        }
        $destroyDateValue = $dateObject->format('Y-m-d');
    }

    // Normalized file name
    $datePart = date('Ymd-His');
    $randPart = substr(bin2hex(random_bytes(4)), 0, 8);
    $targetName = "proof_{$datePart}_{$randPart}.{$originalExtension}";

    // Storage: use configured path for filesystem + public URL
    $pathSetting = $config->ParameterArray['hdd_proof_path'] ?? 'assets/files/hdd/';
    $publicBase = rtrim($pathSetting, '/') . '/';

    // Resolve filesystem path from configuration setting
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

    $destPath = $baseDir . DIRECTORY_SEPARATOR . $targetName;
    if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new Exception(__('File upload error (move_uploaded_file)'));
    }
    @chmod($destPath, 0644);

    // Update DB for all selected IDs with the stored filename only
    global $dbh;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "UPDATE fac_HDD SET ProofFile = ? WHERE HDDID IN ($placeholders)";
    $params = array_merge([$targetName], $ids);
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $updated = $stmt->rowCount();
    if ($updated <= 0) {
        throw new Exception(__('No database rows were updated (check ProofFile column and IDs)'));
    }

    if ($applyDestroyStatus) {
        $statusPlaceholders = implode(',', array_fill(0, count($ids), '?'));
        $statusSql = "UPDATE fac_HDD SET Status = 'Destroyed', DateDestroyed = ? WHERE HDDID IN ($statusPlaceholders)";
        $statusParams = array_merge([$destroyDateValue], $ids);
        $statusStmt = $dbh->prepare($statusSql);
        $statusStmt->execute($statusParams);
    }

    $successMessage = __('Destruction proof uploaded successfully');
    $publicPath = $publicBase . $targetName;
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $successMessage, 'path' => $publicPath]);
        exit;
    }

    $_SESSION['Message'] = $successMessage;
} catch (Exception $ex) {
    error_log('[upload_hdd_proof] ' . $ex->getMessage());
    if ($isAjax) {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['success' => false, 'error' => $ex->getMessage()]);
        exit;
    }
    $_SESSION['LastError'] = $ex->getMessage();
}

header('Location: ' . $return);
exit;
?>
