<?php // content="text/plain; charset=utf-8"
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_bar.php');

// Some data
for($i=0; $i < 12; ++$i) {
    $databary[$i] = rand(1,20);
}
$months=$gDateLocale->GetShortMonth();

// New graph with a drop shadow
$graph = new Graph(300,200);
$graph->SetShadow();

// Use a "text" X-scale
$graph->SetScale('textlin');

// Specify X-labels
$graph->xaxis->SetTickLabels($months);
$graph->xaxis->SetTextLabelInterval(2);

// Set title and subtitle
$graph->title->Set('Textscale with tickinterval=2');

// Use built in font
$graph->title->SetFont(FF_FONT1,FS_BOLD);

// Create the bar plot
$b1 = new BarPlot($databary);
$b1->SetLegend('Temperature');

// The order the plots are added determines who's ontop
$graph->Add($b1);

// Finally output the  image
$graph->Stroke();

?>


