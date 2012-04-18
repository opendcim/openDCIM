<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$user=new User();
	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights($facDB);

	$deviceList=array();
	// if user has read rights then return a search if not return blank
	if($user->ReadAccess){
		$field="";
		$searchTerm="";
		if(isset($_REQUEST["q"])){
			$searchTerm=$_REQUEST["q"];
			if(isset($_REQUEST["name"])){
				$field="Label";
			}elseif(isset($_REQUEST["serial"])){
				$field="SerialNo";
			}elseif(isset($_REQUEST["tag"])){
				$field="AssetTag";
			}
		}
			
		//This will ensure that an empty json record set is returned if this is called directly or in some strange manner
		if($field!=""){
			$sql="SELECT DISTINCT $field FROM fac_Device WHERE $field LIKE '%" . mysql_real_escape_string( $searchTerm ) . "%';";
			$result=mysql_query($sql,$facDB);
			$x=0;
			while($devrow=mysql_fetch_row($result)){
				$deviceList[$x]=$devrow[0];
				++$x;
			}
		}
	}
	header('Content-Type', 'application/json');
	echo json_encode($deviceList);  
?>
