<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$tmpl = new DeviceTemplate();

	$deviceList=array();
	$searchTerm="";
	if(isset($_REQUEST["q"])){
		$searchTerm=$_REQUEST["q"];
	}
		
	//This will ensure that an empty json record set is returned if this is called directly or in some strange manner
	if($searchTerm!=""){
		$tmpl->TemplateID=$searchTerm;
		$tmpl->GetTemplateByID();
	}
	header('Content-Type: application/json');
	echo json_encode($tmpl);  
?>
