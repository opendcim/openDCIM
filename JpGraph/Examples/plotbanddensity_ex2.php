<?php // content="text/plain; charset=utf-8"
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_bar.php');

$datay=array(10,29,3,6);

// Create the graph. 
$graph = new Graph(200,200);	
$graph->SetScale('textlin');
$graph->SetMargin(25,10,20,25);
$graph->SetBox(true);

// Add 10% grace ("space") at top and botton of Y-scale. 
$graph->yscale->SetGrace(10);
$graph->ygrid->Show(false);

// Create a bar pot
$bplot = new BarPlot($datay);
$bplot->SetFillColor("lightblue");


// .. and add the plot to the graph
$graph->Add($bplot);

// Add band
$band = new PlotBand(HORIZONTAL,BAND_3DPLANE,15,35,'khaki4');
$band->SetDensity(80);
$band->ShowFrame(true);
$graph->AddBand($band);

// Set title
$graph->title->SetFont(FF_ARIAL,FS_BOLD,10);
$graph->title->SetColor('darkred');
$graph->title->Set('BAND_3DPLANE, Density=80');


$graph->Stroke();
?>
