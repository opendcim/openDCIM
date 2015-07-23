<?php

require_once( "../db.inc.php" );
require_once( "../facilities.inc.php" );


$_POST["enddate"] = date("Y-m-d H:i:s", strtotime($_POST["enddate"]) + 86400);
switch($_POST["type"]) {
	case "power":
		echo json_encode(getPowerLine());
		break;
	case "energy":
		if($_POST["graphtype"] == "pie") {
			// let's make a pie
			echo json_encode(getPieData())."";
		} else if($_POST["graphtype"] == "line") {
			//let's make lines
			echo json_encode(getEnergyLine());
		}
		break;
	default:
		echo json_encode(getDefaultLine());
		break;
}

function getPowerLine() {
	$id = $_POST["id"];

	$measure = new ElectricalMeasure();
	$measure->MPID = $id;
	$measure = $measure->GetMeasuresOnInterval($_POST["startdate"], $_POST["enddate"]);

	if(count($measure) <= 1)
		return array();

	$n=0;
	if(isset($_POST["splitphases"]) && $_POST["splitphases"] == "true") {
		foreach($measure as $row) {
			$data[0][$n][0] = $row->Date;
                        $data[0][$n][1] = intval($row->Wattage1);
			$data[1][$n][0] = $row->Date;
                        $data[1][$n][1] = intval($row->Wattage2);
			$data[2][$n][0] = $row->Date;
                        $data[2][$n][1] = intval($row->Wattage3);
			$n++;
		}
	} else {
		foreach($measure as $row) {
			$data[$n][0] = $row->Date;
			$data[$n][1] = $row->Wattage1;
			$data[$n][1] += $row->Wattage2;
			$data[$n][1] += $row->Wattage3;
			$n++;
		}
	}
	return $data;
}

function getPieData() {
	$id = $_POST["id"];

	$measure = new ElectricalMeasure();
	$measure->MPID = $id;
	$measure = $measure->GetMeasuresOnInterval($_POST["startdate"], $_POST["enddate"]);

	if(count($measure) > 1) {
		return $measure[count($measure)-1]->Energy - $measure[0]->Energy;
	} else
		return 0;
}

function getEnergyLine() {
	switch($_POST['frequency']) {
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

	$firstDate = strtotime($_POST['enddate']);
	$lastDate = strtotime($_POST['startdate']);

	$measure = new ElectricalMeasure();
	$measure->MPID = $_POST["id"];
	$measure = $measure->GetMeasuresOnInterval($_POST['startdate'], $_POST['enddate']);
	if(count($measure) > 1) {
		$firstDate=(strtotime($measure[0]->Date) < $firstDate)?strtotime($measure[0]->Date):$firstDate;
		$lastDate=(strtotime($measure[count($measure)-1]->Date) > $lastDate)?strtotime($measure[count($measure)-1]->Date):$lastDate;

		for($n=1; $n<count($measure); $n++) {
			$measureTab[$n-1]['date'] = strtotime($measure[$n]->Date);
			$measureTab[$n-1]['energy'] = $measure[$n]->Energy - $measure[$n-1]->Energy;
		}
	}

	$end = new DateTime();

	$dates[0] = strtotime(date($formatString,$firstDate));

	$i=0;
	while($dates[$i] < $lastDate) {
		$end->setTimestamp($dates[$i]);
		$end->add($iter);
		$dates[$i+1] = $end->getTimestamp();
		$i++;
	}
	$data = array();

	$i=0;
	$previousDate = $firstDate;
	if(isset($_POST["datestring"]))
		$data[$i][0] = date("Y-m-d H:i:s", $dates[$i]);
	else
		$data[$i][0] = $dates[$i];

	foreach($measureTab as $row) {
		if($row['date'] <= $dates[$i+1]) {
			//the measure is fully inside the interval
			$data[$i][1]+=$row['energy'];
		} else {
			//get first part of measure
			$data[$i][1]+=$row['energy'] * ($dates[$i+1] - $previousDate) / ($row['date'] - $previousDate);
			$i++;
			if(isset($_POST["datestring"]))
				$data[$i][0] = date("Y-m-d H:i:s", $dates[$i]);
        		else
				$data[$i][0] = $dates[$i];
			while($row['date'] > $dates[$i+1]) {
				//get middle parts of measure
				$data[$i][1] += $row['energy'] * ($dates[$i+1] - $dates[$i]) / ($row['date'] - $previousDate);
				$i++;
				if(isset($_POST["datestring"]))
                                	$data[$i][0] = date("Y-m-d H:i:s", $dates[$i]);
                        	else 
					$data[$i][0] = $dates[$i];
			}
			//get last part of measure
			$data[$i][1] += $row['energy'] * ($row['date'] - $dates[$i]) / ($row['date'] - $previousDate);
		}
		$previousDate = $row['date'];
	}

	return $data;
}

function getDefaultLine() {
	$id = $_POST["id"];

	switch($_POST["type"]) {
		case "temperature":
			$measure = new AirMeasure();
			$dataName = "Temperature";
			break;
		case "humidity":
			$measure = new AirMeasure();
			$dataName = "Humidity";
			break;
		case "cooling":
			$measure = new CoolingMeasure();
			$dataName = "Cooling";
			break;
		case "fanspeed":
			$measure = new CoolingMeasure();
			$dataName = "FanSpeed";
			break;
		default:
			return array();
	}

	$measure->MPID = $id;
	$measure = $measure->GetMeasuresOnInterval($_POST["startdate"], $_POST["enddate"]);

	$n=0;
	foreach($measure as $row) {
		$data[$n][0] = $row->Date;
		$data[$n][1] = floatval($row->$dataName);
		$n++;
	}

	return $data;
}
?>
