<?php
	require_once( "../db.inc.php" );
	require_once( "../facilities.inc.php" );

	$user=new User();
	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights($facDB);

	$tagList=array();
	// if user has read rights then return a search if not return blank
	if($user->ReadAccess){
		$searchTerm="";
		if(isset($_REQUEST["q"])){
			$searchTerm=mysql_real_escape_string($_REQUEST["q"]);
		}
		$sql="SELECT * FROM fac_Tags WHERE Name LIKE '%$searchTerm%';";
		$result=mysql_query($sql,$facDB);
		while($row=mysql_fetch_row($result)){
			$tagList[]=$row[1];
		}
	}
	header('Content-Type: application/json');
	echo json_encode($tagList);  
?>
