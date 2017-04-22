<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$portnames=array();
	// send a list of premade port naming patterns
	foreach(array('NIC(1)','Port(1)','Fa/(1)','Gi/(1)','Ti/(1)') as $pattern){
		$portnames[]['Pattern']=$pattern;
	}
	// if a pattern is requested then send back a list of port names instead of the port pattern list
	if(isset($_REQUEST["pattern"]) && isset($_REQUEST["count"])){
		//using premade patterns if the input differs and causes an error then fuck em
		list($result, $msg, $idx) = parseGeneratorString($_REQUEST["pattern"]);
		if($result){
			$portnames=generatePatterns($result, intval($_REQUEST["count"]));
			// generatePatterns starts the index at 0, it's more useful to us starting at 1
			array_unshift($portnames, null);
		}
	}

	header('Content-Type: application/json');
	echo json_encode($portnames);
