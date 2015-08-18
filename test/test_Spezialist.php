<?php
require_once "db.inc.php";
require_once "facilities.inc.php";

$dotCommand = $config->ParameterArray["dot"];

$graphstr = 'digraph G {
	node[shape=plaintext]
	splines=ortho;
	{
		rank=sink;
		Legend[label=<<TABLE BORDER="0" CELLBORDER="1" CELLSPACING="0">
			<TR><TD COLSPAN="2">Legend</TD></TR>
			<TR><TD>Electrical Measure Point</TD><TD><FONT COLOR="darkorange">E</FONT></TD></TR>
			<TR><TD>Cooling Measure Point</TD><TD><FONT COLOR="darkgreen">C</FONT></TD></TR>
			<TR><TD>Air Measure Point</TD><TD><FONT COLOR="steelblue">A</FONT></TD></TR>
		</TABLE>>]
         }
	test->{Hello; Spezialist;}

	"P10"[label=<<TABLE BORDER="0" CELLBORDER="1" CELLSPACING="0"><TR><TD PORT="IN">P10</TD></TR><TR><TD PORT="OUT"><FONT COLOR="darkorange">E</FONT></TD></TR></TABLE>> color=mediumblue];
	"Emerson_S23"[label=<<TABLE BORDER="0" CELLBORDER="1" CELLSPACING="0"><TR><TD PORT="IN">Emerson_S23</TD></TR><TR><TD PORT="OUT"><FONT COLOR="darkorange">E</FONT><FONT COLOR="darkgreen">C</FONT><FONT COLOR="steelblue">A</FONT></TD></TR></TABLE>> color=mediumblue];

	Hello->"P10":IN;
	"P10":OUT->"Emerson_S23":IN;
}';

$dotfile = tempnam('/tmp/', 'dot_test_');
$graphfile = tempnam('/tmp/', 'graph_test_');
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


?>
