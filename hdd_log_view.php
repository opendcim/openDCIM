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
	SELECT g.Time, g.UserID, g.LogText, h.Label, h.SerialNo
	FROM fac_GenericLog g
	JOIN fac_HDD h ON g.ItemID = h.hddID
	WHERE g.ItemType = 'HDD' AND h.DeviceID = ?
	ORDER BY g.Time DESC
";

$stmt = $dbh->prepare($sql);
$stmt->execute([$deviceID]);
$logEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
					<th><?php echo __("HDD Label"); ?></th>
					<th><?php echo __("Serial No"); ?></th>
					<th><?php echo __("Action"); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($logEntries as $entry): ?>
				<tr>
					<td><?php echo $entry['Time']; ?></td>
					<td><?php echo htmlentities($entry['UserID']); ?></td>
					<td><?php echo htmlentities($entry['Label']); ?></td>
					<td><?php echo htmlentities($entry['SerialNo']); ?></td>
					<td><?php echo htmlentities($entry['LogText']); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php include("foot.inc.php"); ?>
</body>
</html>
