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
		}elseif(isset($_REQUEST["project"])) {
			$field="ProjectName";
		}elseif(isset($_REQUEST["model"])){
			$field="Model";
		}elseif(isset($_REQUEST["owner"])){
			$field="Owner";
		}elseif(isset($_REQUEST["notes"])){
			$field="Notes";
		}elseif(isset($_REQUEST["ip"])){
			$field="PrimaryIP";
		}else{
			$attrList=DeviceCustomAttribute::GetDeviceCustomAttributeList(true);
			foreach($attrList as $name => $attr){
				if(isset($_REQUEST[$name])){
					$field="Custom";
					$custom=$attr->AttributeID;
				}
			}
		}
	}
		
	//This will ensure that an empty json record set is returned if this is called directly or in some strange manner
	if($field!=""){
		// Remove extra % since we are already doing a wildcard search
		$searchTerm=addslashes(str_replace('_','\_',str_replace('%','',$searchTerm)));
		if($field=="Label"){
			$sql="SELECT DISTINCT Label FROM fac_Device WHERE Label LIKE '%$searchTerm%' 
				UNION SELECT DISTINCT Location AS Label FROM fac_Cabinet WHERE Location 
				LIKE '%$searchTerm%' UNION SELECT DISTINCT Label FROM fac_PowerDistribution 
				WHERE Label LIKE '%$searchTerm%' UNION SELECT DISTINCT vmName AS Label 
				FROM fac_VMInventory WHERE vmName LIKE '%$searchTerm%';";
		}elseif($field=="CustomTag"){
			$sql="SELECT DISTINCT Name FROM fac_Tags WHERE Name LIKE '%$searchTerm%'";
		}elseif($field=="ProjectName"){
			$sql="SELECT DISTINCT ProjectName FROM fac_Projects WHERE ProjectName LIKE '%$searchTerm%'";
		}elseif($field=="Model"){
			$sql = "SELECT DISTINCT Model from fac_DeviceTemplate WHERE Model like '%$searchTerm%'";
		}elseif($field=="Owner"){
			$sql="SELECT DISTINCT Name FROM fac_Department WHERE Name LIKE '%$searchTerm%'";
		}elseif($field=="Notes"){
			$sql="SELECT DISTINCT Notes FROM fac_Device WHERE Notes LIKE '%$searchTerm%' 
				UNION SELECT DISTINCT Notes FROM fac_Cabinet WHERE Notes LIKE '%$searchTerm%' 
				UNION SELECT DISTINCT Notes FROM fac_Ports WHERE Notes LIKE '%$searchTerm%' 
				UNION SELECT DISTINCT PortNotes AS Notes FROM fac_Ports	WHERE PortNotes LIKE '%$searchTerm%' 
				UNION SELECT DISTINCT Notes FROM fac_PowerPorts	WHERE Notes LIKE '%$searchTerm%';";
		}elseif($field=="Custom"){
			$sql="SELECT DISTINCT Value FROM fac_DeviceCustomValue WHERE 
				AttributeID=$custom AND Value LIKE '%$searchTerm%' AND Value !='' ORDER BY 
				Value ASC;";
		}else{
			$sql="SELECT DISTINCT $field FROM fac_Device WHERE $field LIKE '%$searchTerm%' LIMIT 500;";
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
