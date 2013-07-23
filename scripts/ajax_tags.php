<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$user=new User();
	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights();

	$tagList=array();
	// if user has read rights then return a search if not return blank
	if($user->ReadAccess){
		$searchTerm="";
		if(isset($_REQUEST["q"])){
			$searchTerm=addslashes(trim($_REQUEST["q"]));
		}
		$sql="SELECT * FROM fac_Tags WHERE Name LIKE '%$searchTerm%';";
		foreach($dbh->query($sql)){
			$tagList[]=$row[1];
		}
	}
	header('Content-Type: application/json');
	echo json_encode($tagList);  
?>
