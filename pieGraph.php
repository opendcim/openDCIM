<?php
require './JpGraph/jpgraph.php';
require './JpGraph/jpgraph_pie.php';

require_once("db.inc.php");
require_once("facilities.inc.php");

if(!isset($_GET['startdate']) || !isset($_GET['enddate']) || !isset($_GET['puelevel']) || !isset($_GET['pueperiod']) || !isset($_GET['puefrequency']) || $_GET['startdate'] >= $_GET['enddate'])
	//no data, no graph
	exit;

if(!isset($_GET['IT']) && !isset($_GET['Mech']) && !isset($_GET['UPSI']) && !isset($_GET['UPSO']) && !isset($_GET['Cooling']) && !isset($_GET['noUPS']))
	exit;

$mpArray = array();
$mpTypes = array('UPSI', 'UPSO', 'Mech', 'Cooling', 'IT');

foreach($mpTypes as $t) {
	if(isset($_GET[$t]) && $_GET[$t] != "")
		$data[$t] = $_GET[$t];
}

$data['UPSI'] -= $data['UPSO'];
$data['UPSO'] += $data['noUPS'];
$data['UPSO'] -= $data['Mech'] + $data['IT'] + $data['Cooling'];

unset($data['noUPS']);

$colors = array( 'UPSI' => 'aquamarine3', 'UPSO' => 'AntiqueWhite3', 'Mech' => 'lightcoral', 'Cooling' => 'gold2', 'IT' => 'lightslateblue');

$n=0;
foreach($data as $i => $d) {
	if($d <= 0) {
		unset($data[$i]);
		unset($colors[$i]);
	}
	else {
		$data[$n] = $d;
		$colors[$n] = $colors[$i];
		unset($data[$i]);
		$n++;
	}
}

//$title="Energy consumption repartition";
$height=(isset($_GET['height']) && $_GET['height'] >= 400)?$_GET['height']:400;
$width=(isset($_GET['width']) && $_GET['width'] >= 400)?$_GET['width']:400;

$graph=new PieGraph($width, $height);

/*$graph->title->Set($title);
$graph->title->SetFont(FF_FONT2, FS_BOLD);
$graph->title->SetPos(($width - $graph->title->GetWidth($graph->img))/2, $height-40, 'bottom');
*/

$graph->img->SetAntiAliasing(true);

$graph->SetMargin(0,0,0,0);

$pie = new PiePlot($data);
$graph->Add($pie);

$pie->SetSliceColors($colors);
$pie->SetSize(160);

$total = array_sum($data);

foreach($data as $n => $d) {
	$labels[$n] = "%.2f kW.h\n".round($d*100/$total,2)."%%"; 
}

$pie->SetLabelType(PIE_VALUE_ABS);
$pie->SetLabels($labels);
$pie->SetLabelPos(0.75);

if($total > 0)
	$graph->Stroke();
?>
