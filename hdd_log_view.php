<?php
require_once("db.inc.php");
require_once("facilities.inc.php");

$subheader = __("HDD Activity Log");

if (!$person->ManageHDD) {
	header("Location: index.php");
	exit;
}

$deviceID = isset($_GET['DeviceID']) ? intval($_GET['DeviceID']) : 0;

if (!$deviceID) {
	echo __("DeviceID is required");
	exit;
}

$sql = "
	SELECT Time, UserID, Action, NewVal
	FROM fac_GenericLog
	WHERE Class = 'HDD' AND ObjectID = :DeviceID
	ORDER BY Time DESC";
$stmt = $dbh->prepare($sql);
$stmt->execute([':DeviceID' => $deviceID]);
$logEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatHddLogDetails($action, $payload) {
	$data = json_decode($payload, true);
	if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
		return $payload;
	}
	switch ($action) {
		case 'HDD_BULK_DESTROY':
			$count = isset($data['count']) ? intval($data['count']) : 0;
			$list = isset($data['ids']) && is_array($data['ids']) ? implode(', ', $data['ids']) : '';
			return sprintf(__("Bulk destroy of %d HDD(s). IDs: %s"), $count, $list);
		case 'HDD_CSV_BATCH':
			$notes = isset($data['notes']) && $data['notes'] !== '' ? $data['notes'] : __('None');
			$processed = isset($data['processed']) && is_array($data['processed']) ? implode(', ', $data['processed']) : __('None');
			$already = isset($data['already_processed']) && is_array($data['already_processed']) ? implode(', ', $data['already_processed']) : __('None');
			$missing = isset($data['missing']) && is_array($data['missing']) ? implode(', ', $data['missing']) : __('None');
			return sprintf(__("CSV batch - Notes: %s | Processed: %s | Already processed: %s | Unknown: %s"), $notes, $processed, $already, $missing);
		case 'HDD_Audit':
			return __("Audit certified for this device.");
		default:
			return $payload;
	}
}
?>

<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php echo $subheader; ?></title>
	<link rel="stylesheet" href="css/inventory.php">
</head>
<body>
<?php include("header.inc.php"); ?>
<div class="page">
<?php include("sidebar.inc.php"); ?>
	<div class="main">
		<h2><?php echo $subheader . ' - ' . __("Device") . ' #' . $deviceID; ?></h2>
		<table class="border">
			<thead>
				<tr>
					<th><?php echo __("Date/Time"); ?></th>
					<th><?php echo __("User"); ?></th>
					<th><?php echo __("Action"); ?></th>
					<th><?php echo __("Details"); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($logEntries as $entry): ?>
				<tr>
					<td><?php echo htmlentities($entry['Time']); ?></td>
					<td><?php echo htmlentities($entry['UserID']); ?></td>
					<td><?php echo htmlentities($entry['Action']); ?></td>
					<td><?php echo htmlentities(formatHddLogDetails($entry['Action'], $entry['NewVal'])); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php include("foot.inc.php"); ?>
</body>
</html>
