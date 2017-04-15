<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$portnames='';
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
