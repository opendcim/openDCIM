<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights();

	if(!$user->SiteAdmin){
		header('Location: '.redirect());
		exit;
	}

	$userRights=new User();
	$status="";

	if(isset($_REQUEST['seluserid']) && strlen($_REQUEST['seluserid']) >0){
		$userRights->UserID=$_REQUEST['seluserid'];
		$userRights->GetUserRights();
	}
	
	if(isset($_POST['action'])&&isset($_POST['userid'])){
		if((($_POST['action']=='Create')||($_POST['action']=='Update'))&&(isset($_POST['name'])&&$_POST['name']!=null&&$_POST['name']!='')){
			$userRights->UserID=$_POST['userid'];
			$userRights->Name=$_POST['name'];
			$userRights->AdminOwnDevices=(isset($_POST['adminowndevices']))?1:0;
			$userRights->ReadAccess=(isset($_POST['readaccess']))?1:0;
			$userRights->WriteAccess=(isset($_POST['writeaccess']))?1:0;
			$userRights->DeleteAccess=(isset($_POST['deleteaccess']))?1:0;
			$userRights->ContactAdmin=(isset($_POST['contactadmin']))?1:0;
			$userRights->RackRequest=(isset($_POST['rackrequest']))?1:0;
			$userRights->RackAdmin=(isset($_POST['rackadmin']))?1:0;
			$userRights->SiteAdmin=(isset($_POST['siteadmin']))?1:0;
			$userRights->Disabled=(isset($_POST['disabled']))?1:0;

			if($_POST['action']=='Create'){
  				$userRights->CreateUser($facDB);
			}else{
				$status=__("Updated");
				$userRights->UpdateUser($facDB);
			}
		}else{
		//Should we ever add a delete user function it will go here
		}
		// Reload rights because actions like disable reset other rights
		$userRights->GetUserRights();
	}

	$userList=$userRights->GetUserList($facDB);
	$adminown=($userRights->AdminOwnDevices)?"checked":"";
	$read=($userRights->ReadAccess)?"checked":"";
	$write=($userRights->WriteAccess)?"checked":"";
	$delete=($userRights->DeleteAccess)?"checked":"";
	$contact=($userRights->ContactAdmin)?"checked":"";
	$request=($userRights->RackRequest)?"checked":"";
	$rackadmin=($userRights->RackAdmin)?"checked":"";
	$admin=($userRights->SiteAdmin)?"checked":"";
	$disabled=($userRights->Disabled)?"checked":"";

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM User Manager</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<div id="header"></div>

<div class="page">
<?php
    include( 'sidebar.inc.php' );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',__("Data Center Contact Detail"),'</h3>
<h3>',$status,'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<input type="hidden" name="action" value="query">
<div class="table">
<div>
   <div><label for="seluserid">',__("User"),'</label></div>
   <div><select name="seluserid" id="seluserid" onChange="form.submit()">
   <option value="">',__("New User"),'</option>';

	foreach($userList as $userRow){
		if($userRights->UserID == $userRow->UserID){$selected=' selected';}else{$selected="";}
		print "<option value=\"$userRow->UserID\"$selected>$userRow->Name</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="userid">',__("UserID"),'</label></div>
   <div><input type="text" name="userid" id="userid" value="',$userRights->UserID,'"></div>
</div>
<div>
   <div><label for="name">',__("Name"),'</label></div>
   <div><input type="text" name="name" id="name" value="',$userRights->Name,'"></div>
</div>
<div>
   <div><label>',__("Rights"),'</label></div>
   <div id="nofloat">
	<input name="adminowndevices" id="adminowndevices" type="checkbox"',$adminown,'><label for="adminowndevices">',__("Admin Own Devices"),'</label><br>
	<input name="readaccess" id="readaccess" type="checkbox"',$read,'><label for="readaccess">',__("Read/Report Access (Global)"),'</label><br>
	<input name="writeaccess" id="writeaccess" type="checkbox"',$write,'><label for="writeaccess">',__("Modify/Enter Devices (Global)"),'</label><br>
	<input name="deleteaccess" id="deleteaccess" type="checkbox"',$delete,'><label for="deleteaccess">',__("Delete Devices (Global)"),'</label><br>
	<input name="contactadmin" id="contactadmin" type="checkbox"',$contact,'><label for="contactadmin">',__("Enter/Modify Contacts and Departments"),'</label><br>
	<input name="rackrequest" id="rackrequest" type="checkbox"',$request,'><label for="rackrequest">',__("Enter Rack Requests"),'</label><br>
	<input name="rackadmin" id="rackadmin" type="checkbox"',$rackadmin,'><label for="rackadmin">',__("Complete Rack Requests"),'</label><br>
	<input name="siteadmin" id="siteadmin" type="checkbox"',$admin,'><label for="siteadmin">',__("Manage Site and Users"),'</label><br>
	<input name="disabled" id="disabled" type="checkbox"',$disabled,'><label for="disabled">',__("Disabled"),'</label><br>	
   </div>
</div>
<div class="caption">';

	if(strlen($userRights->UserID) >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>';
	}else{
		echo '	 <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div> <!-- END div.main -->
</div> <!-- END div.page -->
</body>
</html>
