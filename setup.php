<!DOCTYPE html>

<html>

<head>
	<title>openDCIM Setup Wizard</title>

	<script src="http://cdn.jquerytools.org/1.2.5/full/jquery.tools.min.js"></script>
	<link rel="stylesheet" type="text/css" href="css/tabs.css" />
</head>

<body>

<!-- tabs -->
<ul class="css-tabs">
	<li><a href="wizDataCenter.php">Data Centers</a></li>
	<li><a href="wizCabinets.php">Cabinets</a></li>
	<li><a href="wizDevices.php">Devices</a></li>
</ul>

<!-- single pane, always visible -->
<div class="css-panes">
	<div style="display:block"></div>
</div>
<script>

$(function() {
	$("ul.css-tabs").tabs("div.css-panes > div", {effect: 'ajax'});
});
</script>
</body>
</html>
