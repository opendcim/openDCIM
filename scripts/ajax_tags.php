<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$tagList=array();
	// if user has read rights then return a search if not return blank
	$searchTerm="";
	if(isset($_REQUEST["q"])){
		$searchTerm=addslashes(trim($_REQUEST["q"]));
	}
	$sql="SELECT * FROM fac_Tags WHERE Name LIKE '%$searchTerm%';";
	foreach($dbh->query($sql) as $row){
		$tagList[]=$row[1];
	}
	header('Content-Type: application/json');
	echo json_encode($tagList);  
?>
