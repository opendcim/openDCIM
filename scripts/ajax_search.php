<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$deviceList=array();
	// if user has read rights then return a search if not return blank
	$field="";
	$searchTerm="";
	if(isset($_REQUEST["q"])){
		$searchTerm=$_REQUEST["q"];
		if(isset($_REQUEST["name"]) || isset($_REQUEST["label"])){
			$field="Label";
		}elseif(isset($_REQUEST["serial"])){
			$field="SerialNo";
		}elseif(isset($_REQUEST["tag"]) || isset($_REQUEST["asset"])){
			$field="AssetTag";
		}elseif(isset($_REQUEST["ctag"])){
			$field="CustomTag";
		}elseif(isset($_REQUEST["owner"])){
			$field="Owner";
		}elseif(isset($_REQUEST["ip"])){
			$field="PrimaryIP";
		}
	}
		
	//This will ensure that an empty json record set is returned if this is called directly or in some strange manner
	if($field!=""){
		$searchTerm=addslashes($searchTerm);
		if($field=="Label"){
			$sql="SELECT DISTINCT Label FROM fac_Device WHERE Label LIKE '%$searchTerm%' 
				UNION SELECT DISTINCT Location AS Label FROM fac_Cabinet WHERE Location 
				LIKE '%$searchTerm%' UNION SELECT DISTINCT Label FROM fac_PowerDistribution 
				WHERE Label LIKE '%$searchTerm%' UNION SELECT DISTINCT vmName AS Label 
				FROM fac_VMInventory WHERE vmName LIKE '%$searchTerm%';";
		}elseif($field=="CustomTag"){
			$sql="SELECT DISTINCT Name FROM fac_Tags WHERE Name LIKE '%$searchTerm%'";
		}elseif($field=="Owner"){
			$sql="SELECT DISTINCT Name FROM fac_Department WHERE Name LIKE '%$searchTerm%'";
		}else{
			$sql="SELECT DISTINCT $field FROM fac_Device WHERE $field LIKE '%$searchTerm%';";
		}
		$x=0;
		foreach($dbh->query($sql) as $devrow){
			$deviceList[$x]=$devrow[0];
			++$x;
		}
	}
	header('Content-Type: application/json');
	echo json_encode($deviceList);  
?>
