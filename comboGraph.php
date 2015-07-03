<?php
require './JpGraph/jpgraph.php';
require './JpGraph/jpgraph_line.php';

require_once("db.inc.php");
require_once("facilities.inc.php");

$graceTabMin = array(	"power" => 0,
			"energy" => 0,
			"temperature" => 50,
			"humidity" => 50,
			"cooling" => 0,
			"fanspeed" => 0);

$graceTabMax = array(	"power" => 0,
			"energy" => 0,
			"temperature" => 50,
			"humidity" => 50,
			"cooling" => 0,
			"fanspeed" => 0);

//data's calculation

$left = new AxisData($_GET["lefttype"], 'l');
$right = new AxisData($_GET["righttype"], 'r');


//Creation of the graph

$height=(isset($_GET['height']) && $_GET['height'] >= 400)?$_GET['height']:400;
$width=(isset($_GET['width']) && $_GET['width'] >= 700)?$_GET['width']:700;

$graph=new Graph($width, $height);

$graph->title->Set($title);
$graph->title->SetFont(FF_FONT2, FS_BOLD);
$graph->title->SetPos(($width - $graph->title->GetWidth($graph->img))/2, $height-20, 'bottom');
$graph->SetBox(false);
$graph->SetFrame(false);

$graph->img->SetAntiAliasing(true);

$graph->SetMargin(50,50,10 + 20 * (floor((count($left->Data) + count($right->Data)-1) / 3)+1),0);

//creation of left plots

$noPlot = true;
foreach( $left->Data as $set) {
        if(count($set['y']) > 0) {
                $plot=new LinePlot($set['y'], $set['x']);
                $plot->SetLegend($set['name']." (".__($left->Type).")");
                $graph->Add($plot);
                $noPlot = false;
        }
}

//creation of right plots
//print_r($right->Data[0]);
$noPlot2 = true;
foreach($right->Data as $set) {
	if(count($set['y']) > 0) {
		$plot=new LinePlot($set['y'], $set['x']);
		$plot->SetLegend($set['name']." (".__($right->Type).")");
		$graph->AddY2($plot);
		$noPlot2 = false;
	}
}

if(!$noPlot) {
        $graph->SetScale("intint");
	$ytitle = $left->Type." (".$left->Unit.")";
	$graph->yaxis->SetTitle($ytitle, 'high');
	$graph->yaxis->SetTitleSide(SIDE_RIGHT);
	$graph->yaxis->SetTitleMargin(10);
}

if(!$noPlot2) {
	$graph->SetY2Scale("int");
	$y2title = $right->Type." (".$right->Unit.")";
	$graph->y2axis->SetTitle($y2title, 'high');
	$graph->y2axis->SetTitleSide(SIDE_LEFT);
	$graph->y2axis->SetTitleMargin(-2);
}

$graph->xaxis->SetLabelFormatCallback("makeDate");
$graph->xaxis->SetLabelAngle(70);
$graph->xaxis->SetTitle('Date', 'high');
$graph->xaxis->SetTitleSide(SIDE_TOP);
$graph->xaxis->SetTitleMargin(-10);

$graph->legend->SetFrameWeight(1);
$graph->legend->SetAbsPos($width/2,5,'center','top');

$graph->yaxis->scale->SetGrace($graceTabMax[$left->Type], $graceTabMin[$left->Type]);
if(isset($_GET["righttype"]))
	$graph->y2axis->scale->SetGrace($graceTabMax[$right->Type], $graceTabMin[$right->Type]);

$graph->Stroke();

function makeDate($timestamp) {
        //converstion of timestamps to strings
        return date("Y-m-d H", $timestamp)."h";
}

class AxisData {

	//a class to store axis data

	var $Unit;
	var $Type;
	var $Data;

	static $unitArray = array(	"power" => "W",
					"energy" => "kW.h",
					"temperature" => "°C",
					"humidity" => "%",
					"cooling" => "%",
					"fanspeed" => "%");

	function __construct($type, $ids) {
		global $config;
		$this->Type = $type;
		$this->Unit = AxisData::$unitArray[$this->Type];
		if($this->Unit == "°C" && $config->ParameterArray["mUnits"] == "english")
			$this->Unit = "°F";
		$this->Data = createGraph($this->Type, $ids);
	}
}

function createGraph($type, $side) {
	switch($type) {
		case "energy":
			$ids = explode(",", $_GET[$side."elecids"]);
			return createEnergyGraph($ids);
		case "power":
			$ids = explode(",", $_GET[$side."elecids"]);
			return createPowerGraph($ids);
		case "temperature":
			$ids = explode(",", $_GET[$side."airids"]);
			return createStandardGraph($ids, 'Temperature');
		case "humidity":
			$ids = explode(",", $_GET[$side."airids"]);
			return createStandardGraph($ids, 'Humidity');
		case "cooling":
			$ids = explode(",", $_GET[$side."coolingids"]);
			return createStandardGraph($ids, 'Cooling');
		case "fanspeed":
			$ids = explode(",", $_GET[$side."coolingids"]);
			return createStandardGraph($ids, 'FanSpeed');
		default:
			return array();
	}
}

function createEnergyGraph($idList) {
	if(!isset($_GET['startdate']) || !isset($_GET['enddate']) || !isset($_GET['frequency']) || $_GET['startdate'] >= $_GET['enddate'])
		//no data, no graph
		return array();

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
			$iter = new DateInterval("P1H");
			$formatString = "Y-m-d H:00:00";
			break;
	}

	$firstDate = strtotime($_GET['enddate']);
	$lastDate = strtotime($_GET['startdate']);

	$n=0;
	foreach($idList as $id) {
		$mp = new MeasurePoint();
		$mp->MPID = $id;
		$mp = $mp->GetMP();

		$measureList = new ElectricalMeasure();
		$measureList->MPID = $mp->MPID;
		$measureList = $measureList->GetMeasuresOnInterval($_GET['startdate'], $_GET['enddate']);

		if(count($measureList) > 1) {
			$measureTab[$n]['name'] = $mp->Label;
			$measureTab[$n]['initDate'] = strtotime($measureList[0]->Date);
			$firstDate=(strtotime($measureList[0]->Date) < $firstDate)?strtotime($measureList[0]->Date):$firstDate;
			$lastDate=(strtotime($measureList[count($measureList)-1]->Date) > $lastDate)?strtotime($measureList[count($measureList)-1]->Date):$lastDate;

			for($i=1; $i<count($measureList); $i++) {
				$measureTab[$n][$i-1]['date'] = strtotime($measureList[$i]->Date);
				$measureTab[$n][$i-1]['energy'] = $measureList[$i]->Energy - $measureList[$i-1]->Energy;
			}
			$n++;
		}
	}

	$end = new DateTime();

	$end->setTimestamp(strtotime(date($formatString,$firstDate)));
	$end->add($iter);
	$data[0]['x'][0] = $end->getTimestamp();

	$i=1;
	while($data[0]['x'][$i-1] < $lastDate) {
		$end->setTimestamp($data[0]['x'][$i-1]);
		$end->add($iter);
		$data[0]['x'][$i] = $end->getTimestamp();
		$i++;
	}

	$n=0;
	foreach($measureTab as &$measure) {
		$data[$n]['name'] = $measure['name'];
		unset($measure['name']);

		$previousDate = $measure['initDate'];
		unset($measure['initDate']);

		if($n!=0) {
			$data[$n]['x'] = $data[0]['x'];
		}

		$i=0;
		while($previousDate >= $data[$n]['x'][$i]) {
			$data[$n]['y'][$i] = 0;
			$i++;
		}
		foreach($measure as $row) {
			if($row['date'] <= $data[$n]['x'][$i]) {
				//the measure is fully inside the interval
				$data[$n]['y'][$i]+=$row['energy'];
			} else {
				//get first part of measure
				$data[$n]['y'][$i]+=$row['energy'] * ($data[$n]['x'][$i] - $previousDate) / ($row['date'] - $previousDate);
				$i++;
				while($row['date'] > $data[$n]['x'][$i]) {
					//get middle parts of measure
					$data[$n]['y'][$i]+=$row['energy'] * ($data[$n]['x'][$i] - $data[$n]['x'][$i-1]) / ($row['date'] - $previousDate);
					$i++;
				}
				//get last part of measure
				$data[$n]['y'][$i]+=$row['energy'] * ($row['date'] - $data[$n]['x'][$i-1]) / ($row['date'] - $previousDate);
			}
			$previousDate = $row['date'];
		}
		while($i < count($data[$n]['x'])) {
			$data[$n]['y'][$i] += 0;
			$i++;
		}
		$n++;
	}
	return $data;
}

function createPowerGraph($idList) {
	
	if(!isset($_GET['startdate']) || !isset($_GET['enddate']) || $_GET['startdate'] >= $_GET['enddate'] || !is_array($idList))
		return array();

	$n=0;
	foreach($idList as $id) {
        	$mp = new MeasurePoint();
		$mp->MPID = $id;
        	$mp = $mp->GetMP();

		$measureList = new ElectricalMeasure();
		$measureList->MPID = $id;
		$measureList = $measureList->GetMeasuresOnInterval($_GET['startdate'], $_GET['enddate']);

		if(isset($_GET["combinephases"]) && $_GET["combinephases"] == true) {
			$data[$n]['name'] = $mp->Label;

			foreach($measureList as $i => $m) {
				$data[$n]['y'][$i] = $m->Wattage1 + $m->Wattage2 + $m->Wattage3;
				$data[$n]['x'][$i] = strtotime($m->Date);
			}

			$n++;
		} else {
			$data[$n]['name'] = $mp->Label." phase 1";
			$data[$n+1]['name'] = $mp->Label." phase 2";
			$data[$n+2]['name'] = $mp->Label." phase 3";

			foreach($measureList as $i => $m) {
				$data[$n]['y'][$i] = $m->Wattage1;
				$data[$n+1]['y'][$i] = $m->Wattage2;
				$data[$n+2]['y'][$i] = $m->Wattage3;
				$data[$n]['x'][$i] = strtotime($m->Date);
				$data[$n+1]['x'][$i] = strtotime($m->Date);
				$data[$n+2]['x'][$i] = strtotime($m->Date);
			}

			$n += 3;
		}
	}
	return $data;
}

function createStandardGraph($idList, $measureUnit) {
	
	if(!isset($_GET['startdate']) || !isset($_GET['enddate']) || $_GET['startdate'] >= $_GET['enddate'] || !is_array($idList))
		return array();

	$n=0;
	foreach($idList as $id) {
        	$mp = new MeasurePoint();
		$mp->MPID = $id;
        	$mp = $mp->GetMP();

		$class = MeasurePoint::$TypeTab[$mp->Type]."Measure";
		$measureList = new $class;
		$measureList->MPID = $id;
		$measureList = $measureList->GetMeasuresOnInterval($_GET['startdate'], $_GET['enddate']);

		$data[$n]['name'] = $mp->Label;

		foreach($measureList as $i => $m) {
			$data[$n]['y'][$i] = $m->$measureUnit;
			$data[$n]['x'][$i] = strtotime($m->Date);
		}

		$n++;
	}
	return $data;
}

?>
