<?php
// upload_hdd_proof.php
// Secure upload endpoint for associating a PDF "preuve de destruction" to one or more HDDs.
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

try {
    // IDs selection
    $ids = $_POST['hdd_ids'] ?? [];
    if (!is_array($ids) || count($ids) === 0) {
        throw new Exception(__('Aucun HDD sélectionné'));
    }
    $ids = array_values(array_unique(array_map('intval', $ids)));

    // File presence and PHP upload error
    if (!isset($_FILES['proof_pdf'])) {
        throw new Exception(__('Erreur lors du transfert du fichier'));
    }
    if ($_FILES['proof_pdf']['error'] !== UPLOAD_ERR_OK) {
        $e = $_FILES['proof_pdf']['error'];
        $map = [
            UPLOAD_ERR_INI_SIZE   => __('Le fichier est trop volumineux (max 5 Mo)'),
            UPLOAD_ERR_FORM_SIZE  => __('Le fichier est trop volumineux (max 5 Mo)'),
            UPLOAD_ERR_PARTIAL    => __('Erreur lors du transfert du fichier'),
            UPLOAD_ERR_NO_FILE    => __('Aucun fichier fourni'),
            UPLOAD_ERR_NO_TMP_DIR => __('Répertoire temporaire manquant'),
            UPLOAD_ERR_CANT_WRITE => __('Impossible d\'écrire le fichier sur le disque'),
            UPLOAD_ERR_EXTENSION  => __('Téléversement bloqué par une extension'),
        ];
        throw new Exception($map[$e] ?? __('Erreur lors du transfert du fichier'));
    }

    $file = $_FILES['proof_pdf'];

    // Size <= 5 MiB
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception(__('Le fichier est trop volumineux (max 5 Mo)'));
    }

    // MIME check
    if (!class_exists('finfo')) {
        throw new Exception(__('L\'extension fileinfo n\'est pas disponible côté serveur'));
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if ($mime !== 'application/pdf') {
        throw new Exception(__('Fichier non autorisé (PDF uniquement)'));
    }

    // Normalized file name
    $datePart = date('Ymd-His');
    $randPart = substr(bin2hex(random_bytes(4)), 0, 8);
    $targetName = "proof_{$datePart}_{$randPart}.pdf";

    // Storage: use configured relative path (with trailing slash)
    $relBase = $config->ParameterArray['hdd_proof_path'] ?? 'assets/files/hdd/';
    // Ensure trailing slash for URL storage
    $relBase = rtrim($relBase, '/') . '/';
    $relPath = $relBase . $targetName; // Path persisted in DB (web path)

    // Build absolute filesystem path
    $basePath = $relBase;
    // If absolute path provided, use as is; else resolve relative to this script directory
    if (preg_match('#^(?:[A-Za-z]:\\\\|/)#', $basePath) === 1) {
        $baseDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $basePath), DIRECTORY_SEPARATOR);
    } else {
        $baseDir = rtrim(__DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($basePath, '/\\')), DIRECTORY_SEPARATOR);
    }

    if (!is_dir($baseDir)) {
        if (!@mkdir($baseDir, 0750, true)) {
            throw new Exception(__('Impossible de créer le répertoire de stockage') . ' : ' . $baseDir);
        }
    }
    if (!is_writable($baseDir)) {
        throw new Exception(__('Le répertoire de stockage n\'est pas inscriptible: ') . $baseDir);
    }

    $destPath = $baseDir . DIRECTORY_SEPARATOR . $targetName;
    if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new Exception(__('Erreur lors du transfert du fichier (move_uploaded_file)'));
    }
    @chmod($destPath, 0644);

    // Update DB for all selected IDs
    $updated = HDD::SetProofFileForIds($ids, $relPath);
    if ($updated <= 0) {
        throw new Exception(__('Aucune ligne mise à jour en base (vérifier la colonne ProofFile et les IDs)'));
    }

    $_SESSION['Message'] = __('Preuve de destruction enregistrée avec succès');
} catch (Exception $ex) {
    error_log('[upload_hdd_proof] ' . $ex->getMessage());
    $_SESSION['LastError'] = $ex->getMessage();
}

header('Location: ' . $return);
exit;
?>
