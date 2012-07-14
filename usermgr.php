<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->SiteAdmin){
		header('Location: '.redirect());
		exit;
	}

	$userRights = new User();

	if (isset($_REQUEST['seluserid']) && strlen($_REQUEST['seluserid']) > 0) {
		$userRights->UserID = $_REQUEST['seluserid'];
		$userRights->GetUserRights( $facDB );
	}

	if(isset($_REQUEST['action'])){
		if(($_REQUEST['action'] == 'Create' ) || ($_REQUEST['action']=='Update')){
			$userRights->UserID = $_REQUEST['userid'];
			$userRights->Name = $_REQUEST['name'];
			@$userRights->ReadAccess = ( $_REQUEST['readaccess'] == 'on' ) ? 1 : 0;
			@$userRights->WriteAccess = ( $_REQUEST['writeaccess'] == 'on' ) ? 1 : 0;
			@$userRights->DeleteAccess = ( $_REQUEST['deleteaccess'] == 'on' ) ? 1 : 0;
			@$userRights->ContactAdmin = ( $_REQUEST['contactadmin'] == 'on' ) ? 1 : 0;
			@$userRights->RackRequest = ( $_REQUEST['rackrequest'] == 'on' ) ? 1 : 0;
			@$userRights->RackAdmin = ( $_REQUEST['rackadmin'] == 'on' ) ? 1 : 0;
			@$userRights->SiteAdmin = ( $_REQUEST['siteadmin'] == 'on' ) ? 1 : 0;

			if($_REQUEST['action'] == 'Create'){
			  if($userRights->Name != null && $userRights->Name != '')
  				$userRights->CreateUser($facDB);
			}else{
				$userRights->UpdateUser($facDB);
			}
		}
	}

	$userList = $userRights->GetUserList($facDB);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM User Manager</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
</head>
<body>
<div id="header"></div>

<div class="page">
<?php
    include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Contact Detail</h3>
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<input type="hidden" name="action" value="query">
<div class="table">
<div>
   <div><label for="seluserid">User</label></div>
   <div><select name="seluserid" id="seluserid" onChange="form.submit()">
   <option value="">New User</option>
<?php
	foreach( $userList as $userRow ) {
		echo "<option value=\"$userRow->UserID\"";
		if($userRights->UserID == $userRow->UserID){
			echo ' selected';
		}
		echo ">$userRow->Name</option>\n";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="userid">UserID</label></div>
   <div><input type="text" name="userid" id="userid" value="<?php echo $userRights->UserID; ?>"></div>
</div>
<div>
   <div><label for="name">Name</label></div>
   <div><input type="text" name="name" id="name" value="<?php echo $userRights->Name; ?>"></div>
</div>
<div>
   <div><label>Rights</label></div>
   <div id="nofloat">
<input name="readaccess" id="readaccess" type="checkbox" <?php if ( $userRights->ReadAccess ) echo 'checked'; ?> >
<label for="readaccess">Read/Report Access</label>
<br>
<input name="writeaccess" id="writeaccess" type="checkbox" <?php if ( $userRights->WriteAccess ) echo 'checked'; ?> >
<label for="writeaccess">Modify/Enter Devices</label>
<br>
<input name="deleteaccess" id="deleteaccess" type="checkbox" <?php if ( $userRights->DeleteAccess ) echo 'checked'; ?> >
<label for="deleteaccess">Delete Devices</label>
<br>
<input name="contactadmin" id="contactadmin" type="checkbox" <?php if ( $userRights->ContactAdmin ) echo 'checked'; ?> >
<label for="contactadmin">Enter/Modify Contacts and Departments</label>
<br>
<input name="rackrequest" id="rackrequest" type="checkbox" <?php if ( $userRights->RackRequest ) echo 'checked'; ?> >
<label for="rackrequest">Enter Rack Requests</label>
<br>
<input name="rackadmin" id="rackadmin" type="checkbox" <?php if ( $userRights->RackAdmin ) echo 'checked'; ?> >
<label for="rackadmin">Complete Rack Requests</label>
<br>
<input name="siteadmin" id="siteadmin" type="checkbox" <?php if ( $userRights->SiteAdmin ) echo 'checked'; ?> >
<label for="siteadmin">Manage Site and Users</label>
<br>
   </div>
</div>
<div class="caption">
   <input type="submit" name="action" value="Create">
<?php
	if(strlen($user->UserID) >0){
		echo '   <input type="submit" name="action" value="Update">';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
</div></div>
<a href="index.php">[ Return to Main Menu ]</a>
</div> <!-- END div.main -->
</div> <!-- END div.page -->
</body>
</html>
