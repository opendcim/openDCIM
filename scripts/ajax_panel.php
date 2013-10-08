<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$pnl=new PowerPanel();

	$searchTerm="";
	if(isset($_REQUEST["q"])){
		$searchTerm=$_REQUEST["q"];
	}
		
	//This will ensure that an empty json record set is returned if this is called directly or in some strange manner
	if($searchTerm!=""){
		$pnl->PanelID=$searchTerm;
		$pnl->GetPanel();
	}
	header('Content-Type: application/json');
	echo json_encode($pnl);  
?>
