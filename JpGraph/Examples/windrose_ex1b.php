<?php
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_windrose.php');

// Data can be specified using both ordinal index of the axis
// as well as the direction label
$data = array(
    0 => array(1,1,2.5,4),
    1 => array(3,4,1,4),
    'wsw' => array(1,5,5,3),
    'N' => array(2,7,5,4,2),
    15 => array(2,7,12));

// First create a new windrose graph with a title
$graph = new WindroseGraph(400,400);
$graph->title->Set('Windrose example 1b');
$graph->title->SetFont(FF_VERDANA,FS_BOLD,12);
$graph->title->SetColor('navy');

// Create the windrose plot.
// The default plot will have 16 compass axis.
$wp = new WindrosePlot($data);
$wp->SetRadialGridStyle('solid');
$graph->Add($wp);

// Setup the range so that the values do not touch eachother
$wp->SetRanges(array(0,1,2,3,4,5,6,7,8,10));
$wp->SetRangeStyle(RANGE_DISCRETE); // Cmp with RANGE_OVERLAPPING as default

// Send the graph to the browser
$graph->Stroke();
?>

