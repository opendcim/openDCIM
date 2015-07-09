<?php
require './JpGraph/jpgraph.php';
require './JpGraph/jpgraph_line.php';

require_once("db.inc.php");
require_once("facilities.inc.php");

if(!isset($_GET['startdate']) || !isset($_GET['enddate']) || !isset($_GET['frequency']) || $_GET['startdate'] >= $_GET['enddate'] || (!isset($_GET['IT']) && !isset($_GET['Mech']) && !isset($_GET['UPSI']) && !isset($_GET['UPSO']) && !isset($_GET['Cooling'])))
	//no data, no graph
	exit;

switch($_GET['frequency']) {
	case "hourly":
		$iter = new DateInterval("PT1H");
		$formatString = "Y-m-d H:00:00";
		break;
	case "daily":
		$iter = new DateInterval("P1D");
		$formatString = "Y-m-d 00:00:00";
		break;
	case "monthly":
		$iter = new DateInterval("P1M");
		$formatString = "Y-m-01 00:00:00";
		break;
	case "yearly":
		$iter = new DateInterval("P1Y");
		$formatString = "Y-01-01 00:00:00";
		break;
	default:
		$iter = new DateInterval("PT1H");
		$formatString = "Y-m-d H:00:00";
		break;
}

$mpArray = array();
$mpTypes = array("UPSI", "UPSO", "Mech", "Cooling", "IT");

foreach($mpTypes as $t) {
	if(isset($_GET[$t]) && $_GET[$t] != "")
		$mpArray[$t] = explode(",",$_GET[$t]);
}

$firstDate = strtotime($_GET['enddate']);
$lastDate = strtotime($_GET['startdate']);

$noUPScnt = 0;

foreach($mpArray as $type => $mpList) {
	foreach($mpList as $i => $mp) {
		$measure = new ElectricalMeasure();
		$measure->MPID = $mp;
		$measure = $measure->GetMeasuresOnInterval($_GET['startdate'], $_GET['enddate']);
		$measurePoint = new MeasurePoint();
		$measurePoint->MPID = $mp;
		$measurePoint = $measurePoint->GetMP();
		if(count($measure) > 1) {
			$firstDate=(strtotime($measure[0]->Date) < $firstDate)?strtotime($measure[0]->Date):$firstDate;
			$lastDate=(strtotime($measure[count($measure)-1]->Date) > $lastDate)?strtotime($measure[count($measure)-1]->Date):$lastDate;
			
			if($measurePoint->UPSPowered == 0 && $type != "UPSI" && $type != "UPSO") {
				$measureTab[$type][$i]['initDate'] = strtotime($measure[0]->Date);
				$measureTab["noUPS"][$noUPScnt]['initDate'] = strtotime($measure[0]->Date);
				for($n=1; $n<count($measure); $n++) {
					$measureTab[$type][$i][$n-1]['date'] = strtotime($measure[$n]->Date);
					$measureTab[$type][$i][$n-1]['energy'] = $measure[$n]->Energy - $measure[$n-1]->Energy;
					$measureTab["noUPS"][$noUPScnt][$n-1]['date'] = strtotime($measure[$n]->Date);
					$measureTab["noUPS"][$noUPScnt][$n-1]['energy'] = $measure[$n]->Energy - $measure[$n-1]->Energy;
				}
				$noUPScnt++;
			} else {
				$measureTab[$type][$i]['initDate'] = strtotime($measure[0]->Date);
				for($n=1; $n<count($measure); $n++) {
					$measureTab[$type][$i][$n-1]['date'] = strtotime($measure[$n]->Date);
					$measureTab[$type][$i][$n-1]['energy'] = $measure[$n]->Energy - $measure[$n-1]->Energy;
				}
			}
		}
	}
}

$end = new DateTime();

$intervals['startInterval'][0] = strtotime(date($formatString,$firstDate));
$end->setTimestamp($intervals['startInterval'][0]);
$end->add($iter);
$intervals['endInterval'][0] = $end->getTimestamp();

$i=1;
while($intervals['endInterval'][$i-1] < $lastDate) {
	$intervals['startInterval'][$i] = $intervals['endInterval'][$i-1];
	$end->setTimestamp($intervals['startInterval'][$i]);
	$end->add($iter);
	$intervals['endInterval'][$i] = $end->getTimestamp();
	$i++;
}

foreach($measureTab as $type => $list) {
	foreach($list as $n => $measureList) {

		$i=0;
		$previousDate = $measureList['initDate'];
		unset($measureList['initDate']);
		while($previousDate >= $intervals['endInterval'][$i]) {
			$data[$type][$i] += 0;
			$i++;
		}
		
		foreach($measureList as $row) {
			if($row['date'] <= $intervals['endInterval'][$i]) {
				//the measure is fully inside the interval
				$data[$type][$i]+=$row['energy'];
			} else {
				//get first part of measure
				$data[$type][$i]+=$row['energy'] * ($intervals['endInterval'][$i] - $previousDate) / ($row['date'] - $previousDate);
				$i++;
				while($row['date'] > $intervals['endInterval'][$i]) {
					//get middle parts of measure
					$data[$type][$i]+=$row['energy'] * ($intervals['endInterval'][$i] - $intervals['startInterval'][$i]) / ($row['date'] - $previousDate);
					$i++;
				}
				//get last part of measure
				$data[$type][$i]+=$row['energy'] * ($row['date'] - $intervals['startInterval'][$i]) / ($row['date'] - $previousDate);
			}
			$previousDate = $row['date'];
		}
		while($i < count($intervals['endInterval'])) {
			$data[$type][$i] += 0;
			$i++;
		}
	}
}

for($i=0; $i < count($data['Mech']); $i++) {
	$data['Mech'][$i] += $data['Cooling'][$i] + $data['IT'][$i];
}

for($i=0; $i < count($data['Cooling']); $i++) {
	$data['Cooling'][$i] += $data['IT'][$i];
}

for($i=0; $i < count($data['UPSI']); $i++) {
	$data['UPSI'][$i] += $data['noUPS'][$i];
}

for($i=0; $i < count($data['UPSO']); $i++) {
	$data['UPSO'][$i] += $data['noUPS'][$i];
}
unset($data['noUPS']);

for($i=0; $i < count($data['IT']); $i++) {
	if($data['IT'][$i] !=0)
		$pue[$i] = $data['UPSI'][$i] / $data['IT'][$i];
	else
		$pue[$i] = 0;
}

$title="Energy consumption";
$height=(isset($_GET['height']) && $_GET['height'] >= 400)?$_GET['height']:400;
$width=(isset($_GET['width']) && $_GET['width'] >= 700)?$_GET['width']:700;

$graph=new Graph($width, $height);

$graph->title->Set($title);
$graph->title->SetFont(FF_FONT2, FS_BOLD);
$graph->title->SetPos(($width - $graph->title->GetWidth($graph->img))/2, $height-40, 'bottom');

$graph->SetMargin(50,20,10 + 23 * (floor((count($data)-1) / 4)+2),0);

$color = array( 'IT' => 'lightslateblue',
		'Cooling' => 'gold2',
		'Mech' => 'lightcoral',
		'UPSO' => 'AntiqueWhite3',
		'UPSI' => 'aquamarine3',
		'pue' => 'black');

$names = array( 'IT' => __("IT"),
		'Cooling' => __("Cooling"),
		'Mech' => __("Other Mechanical Devices"),
		'UPSO' => __("Other"),
		'UPSI' => __("Power sources loss"));

$noPlot = true;
foreach( $data as $type => $set) {
	if(count($set) > 0) {
		$plot[$type]=new LinePlot($set, $intervals['endInterval']);
		$plot[$type]->SetLegend($names[$type]);
		$graph->Add($plot[$type]);
		$plot[$type]->SetColor($color[$type]);
		$plot[$type]->SetFillColor($plot[$type]->color);
		$noPlot = false;
	}
}
if(count($pue) > 0) {
	$plot['pue']=new LinePlot($pue, $intervals['endInterval']);
	$plot['pue']->SetLegend('PUE');
	$graph->AddY2($plot['pue']);
	$plot['pue']->SetWeight(3);
	$plot['pue']->SetColor($color['pue']);
}

$graph->SetY2OrderBack(false);

if(!$noPlot) {
	$graph->SetScale("intint");
	$graph->SetY2Scale("int");
	$graph->yaxis->scale->SetAutoMin(0);
}

$graph->xaxis->SetLabelFormatCallback("makeDate");
$graph->xaxis->SetLabelAngle(60);
$graph->xaxis->SetTitle('Date', 'high');
$graph->xaxis->SetTitleSide(SIDE_TOP);
$graph->xaxis->SetTitleMargin(-10);

$graph->yaxis->SetTitle('Energy (kW.h)', 'high');
$graph->yaxis->SetTitleSide(SIDE_RIGHT);
$graph->yaxis->SetTitleMargin(10);

$graph->y2axis->SetTitle('PUE', 'high');
$graph->y2axis->SetTitleSide(SIDE_LEFT);
$graph->y2axis->SetTitleMargin(-1);

$graph->legend->SetColumns(4);
$graph->legend->SetFrameWeight(1);
$graph->legend->SetAbsPos($width/2,5,'center','top');

$graph->Stroke();

function makeDate($timestamp) {
	return date("Y-m-d", $timestamp);
}

?>
