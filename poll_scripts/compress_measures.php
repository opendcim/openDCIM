<?php 
require_once('../db.inc.php');
require_once('../facilities.inc.php');

global $config;
global $dbh;

$DaysBeforeCompression = $config->ParameterArray["DaysBeforeCompression"];

$date = new DateTime();
$date->setTime(0,0,0);
$dateInterval = new DateInterval("P".$DaysBeforeCompression."D");

$date->sub($dateInterval);

$dateInterval = new DateInterval("P1D");

$sql = "SELECT MIN(Date) as Date from fac_ElectricalMeasure;";

$minDate = $dbh->query($sql)->fetch();
$minDate = new DateTime($minDate["Date"]);
$minDate->setTime(0,0,0);

$endDate = $date->format("Y-m-d H:i:s");
$date->sub($dateInterval);
$mpList = new ElectricalMeasurePoint();
$mpList = $mpList->GetMPList();
while($minDate->diff($date)->invert == 0) {
	foreach($mpList as $mp) {
		$measureList = new ElectricalMeasure();
		$measureList->MPID = $mp->MPID;
		$measureList = $measureList->GetMeasuresOnInterval($date->format("Y-m-d H:i:s"), $endDate);

		if(count($measureList) > 1) {
			$compMeasure = new ElectricalMeasure();
			$compMeasure->MPID = $mp->MPID;
			$compMeasure->Wattage1 = 0;
			$compMeasure->Wattage2 = 0;
			$compMeasure->Wattage3 = 0;
			$compMeasure->Energy = $measureList[0]->Energy;
			$compMeasure->Date = $date->format("Y-m-d H:i:s");

			foreach($measureList as $measure) {
				$compMeasure->Wattage1 += $measure->Wattage1;
				$compMeasure->Wattage2 += $measure->Wattage2;
				$compMeasure->Wattage3 += $measure->Wattage3;
			}

			$compMeasure->Wattage1 /= count($measureList);
			$compMeasure->Wattage2 /= count($measureList);
			$compMeasure->Wattage3 /= count($measureList);

			$sql = "DELETE FROM fac_ElectricalMeasure WHERE MPID = $mp->MPID AND Date >= \"{$date->format("Y-m-d H:i:s")}\" AND Date < \"$endDate\";";
			if($dbh->exec($sql))
				$compMeasure->CreateMeasure();
		}
	}
	$endDate = $date->format("Y-m-d H:i:s");
	$date->sub($dateInterval);
}
?>
