<?php 
require_once "db.inc.php";
require_once "facilities.inc.php";

$dotCommand = $config->ParameterArray["dot"];

function createName($label, $nameList) {
	$suffix = "";
	$cnt = 2;
	while(in_array($label.$suffix, $nameList)) {
		$suffix = "_".$cnt;
		$cnt++;
	}
	$nameList[] = $label.$suffix;
	return $label.$suffix;
}

function hasMPLinked($id, $eqType, $mpType) {
	$mpList = new MeasurePoint();
	$mpList->EquipmentID = $id;
	$mpList->EquipmentType = $eqType;
	$mpList = $mpList->GetMPByEquipment();

	foreach($mpList as $mp)
		if($mp->Type == $mpType)
			return true;
	return false;
}

if(isset($_GET["datacenterid"])) {
	$dc = new DataCenter();
	$dc->DataCenterID = $_GET["datacenterid"];
	if(!$dc->GetDataCenter()) {
		echo __("DataCenter doesn't exists");
		exit;
	}
	$sources = new PowerPanel();
	$sources = $sources->getSourcesByDataCenter($dc->DataCenterID);

	foreach($sources as $panel)
		$data[0][]->object = $panel;

	$line = 0;
	$nameList = array();
	while(isset($data[$line])) {
		foreach($data[$line] as $index => $element) {
			$element->Name = createName($element->object->PanelLabel, $nameList);

			$children = new PowerPanel();
			$children->ParentPanelID = $element->object->PanelID;
			$children = $children->getPanelListBySource();

			$data[$line][$index]->hasEMP = hasMPLinked($data[$line][$index]->object->PanelID, "PowerPanel", "elec");
			$data[$line][$index]->hasCMP = hasMPLinked($data[$line][$index]->object->PanelID, "PowerPanel", "cooling");
			$data[$line][$index]->hasAMP = hasMPLinked($data[$line][$index]->object->PanelID, "PowerPanel", "air");
			$data[$line][$index]->children = array();
			foreach($children as $panel) {
				$data[$line + 1][]->object = $panel;

				$child = new stdClass();
				$child->line = $line + 1;
				$child->index = count($data[$line + 1]) -1;
				$data[$line][$index]->children[] = $child;
			}
		}
		$line++;
	}

	for($n=$line-1; $n>=0; $n--) {
		foreach ($data[$n] as $element) {
			$pduList = new PowerDistribution();
			$pduList->PanelID = $element->object->PanelID;
			$pduList = $pduList->GetPDUbyPanel();
			foreach($pduList as $pdu) {
				$pduElement = new stdClass();
				$pduElement->object = $pdu;
				$pduElement->Name = createName($pdu->Label, $nameList);
				$pduElement->children = array();
				$pduElement->hasEMP = hasMPLinked($pdu->PDUID, "Device", "elec");
				$pduElement->hasCMP = hasMPLinked($pdu->PDUID, "Device", "cooling");
				$pduElement->hasAMP = hasMPLinked($pdu->PDUID, "Device", "air");
				$data[$line][] = $pduElement;

				$child = new stdClass();
				$child->line = $line;
				$child->index = count($data[$line]) -1;
				$element->children[] = $child;
			}

			$mechList = new MechanicalDevice();
			$mechList->PanelID = $element->object->PanelID;
			$mechList = $mechList->GetMechByPanel();
			foreach($mechList as $mech) {
				$mechElement = new stdClass();
				$mechElement->object = $mech;
				$mechElement->Name = createName($mech->Label, $nameList);
				$mechElement->children = array();
				$mechElement->hasEMP = hasMPLinked($mech->MechID, "MechanicalDevice", "elec");
				$mechElement->hasCMP = hasMPLinked($mech->MechID, "MechanicalDevice", "cooling");
				$mechElement->hasAMP = hasMPLinked($mech->MechID, "MechanicalDevice", "air");
				$data[$line + 1][] = $mechElement;

				$child = new stdClass();
				$child->line = $line+1;
				$child->index = count($data[$line+1]) -1;
				$element->children[] = $child;
			}
		}
	}
	$line++;

	foreach ($data[$line - 1] as $element) {
		$connections = new PowerPorts();
		$connections->DeviceID = $element->object->PDUID;
		$connections = $connections->getPorts();
		foreach($connections as $co) {
			if(!is_null($co->ConnectedDeviceID)) {
				$dev = new Device();
				$dev->DeviceID = $co->ConnectedDeviceID;
				if($dev->GetDevice()) {
					$devElement = new stdClass();
					$devElement->object = $dev;
					$devElement->Name = createName($dev->Label, $nameList);
					$devElement->children = array();
					$devElement->hasEMP = hasMPLinked($dev->DeviceID, "Device", "elec");
					$devElement->hasCMP = hasMPLinked($dev->DeviceID, "Device", "cooling");
					$devElement->hasAMP = hasMPLinked($dev->DeviceID, "Device", "air");
					$data[$line][] = $devElement;

					$child = new stdClass();
					$child->line = $line;
					$child->index = count($data[$line]) -1;
					$element->children[] = $child;
				}
			}
		}
	}

	$graphstr = "digraph G {
		node[shape=plaintext]
		splines=ortho;
		{
			rank=sink;
			Legend[label=<<TABLE BORDER=\"0\" CELLBORDER=\"1\" CELLSPACING=\"0\">
				<TR><TD COLSPAN=\"2\">".__("Legend")."</TD></TR>
				<TR><TD>".__("Electrical Measure Point")."</TD><TD><FONT COLOR=\"darkorange\">E</FONT></TD></TR>
				<TR><TD>".__("Cooling Measure Point")."</TD><TD><FONT COLOR=\"darkgreen\">C</FONT></TD></TR>
				<TR><TD>".__("Air Measure Point")."</TD><TD><FONT COLOR=\"steelblue\">A</FONT></TD></TR>
			</TABLE>>]
		}\n";
	foreach($data as $n => $line) {
			$graphstr .= "\tsubgraph cluster_$n {
			color = invis;\n";
		foreach($line as $element) {
			$graphstr .= "\t\t\"$element->Name\"[label=<<TABLE BORDER=\"0\" CELLBORDER=\"1\" CELLSPACING=\"0\"><TR>";
			if($element->hasEMP || $element->hasCMP || $element->hasAMP) {
				$graphstr .= "<TD PORT=\"IN\">$element->Name</TD></TR><TR><TD PORT=\"OUT\">";
				if($element->hasEMP)
					$graphstr .= "<FONT COLOR=\"darkorange\">E</FONT>";
				if($element->hasCMP)
					$graphstr .= "<FONT COLOR=\"darkgreen\">C</FONT>";
				if($element->hasAMP)
					$graphstr .= "<FONT COLOR=\"steelblue\">A</FONT>";
				$graphstr .= "</TD></TR></TABLE>> color=mediumblue]";
			} else {
				$graphstr .= "<TD PORT=\"INOUT\">$element->Name</TD></TR></TABLE>>]";
			}
			$graphstr .= ";\n";
		}
		$graphstr .= "\t}\n";
	}

	foreach($data as $numLine => $line) {
		foreach($line as $element) {
			foreach($element->children as $child) {
				if($element->hasEMP || $element->hasCMP || $element->hasAMP)
					$graphstr .= "\t\"$element->Name\":OUT->\"";
				else
					$graphstr .= "\t\"$element->Name\":INOUT->\"";
				if($data[$child->line][$child->index]->hasEMP || $data[$child->line][$child->index]->hasCMP || $data[$child->line][$child->index]->hasAMP)
					$graphstr .= $data[$child->line][$child->index]->Name."\":IN;\n";
				else
					$graphstr .= $data[$child->line][$child->index]->Name."\":INOUT;\n";
			}
		}
	}
	$graphstr .= "\tlabelloc=\"t\";\tlabel=\"$dc->Name\";}";

	$dotfile = tempnam('/tmp/', 'dot_');
	$graphfile = tempnam('/tmp/', 'graph_');
	if(file_put_contents($dotfile, $graphstr)) {
		$graph = array();
		$retval = 0;

		exec("$dotCommand -Tpng -o$graphfile $dotfile", $graph, $retval);

		if($retval == 0) {
			header("Content-Type: image/png");
			//unlink($dotfile);
			print file_get_contents($graphfile);
			unlink($graphfile);
			exit;
		}
	}
}
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <style type="text/css"></style>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript">
	$(document).ready(function(){
		$('#datacenterid').val("");
	});
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
        <div class="page">
<?php
        include('sidebar.inc.php');


	echo '	<div class="main">
			<form method="GET">
				<label for="datacenterid">'.__("DataCenter ID").'</label>
				<select name="datacenterid" id="datacenterid" onChange="submit();">';
	
	$dcList = new DataCenter();
	$dcList = $dcList->GetDCList();
	foreach($dcList as $dc) {
		echo "<option value=\"$dc->DataCenterID\">$dc->Name</option>";
	}
	
	echo '			</select>
			</form>
		</div>';
?>
	</div>
</body>
</html>
