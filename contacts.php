<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$contact=new Contact();
	$user=new User();

	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->ContactAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$formfix="";
	if(isset($_REQUEST['contactid']) && ($_REQUEST['contactid']>0)) {
		$contact->ContactID=(isset($_POST['contactid']) ? $_POST['contactid'] : $_GET['contactid']);
		$contact->GetContactByID($facDB);

		$formfix="?contactid=$contact->ContactID";
	}

	if(isset($_POST['action']) && (($_POST['action']=='Create') || ($_POST['action']=='Update'))){
		$contact->ContactID=$_POST['contactid'];
		$contact->UserID=$_POST['UserID'];
		$contact->LastName=$_POST['lastname'];
		$contact->FirstName=$_POST['firstname'];
		$contact->Phone1=$_POST['phone1'];
		$contact->Phone2=$_POST['phone2'];
		$contact->Phone3=$_POST['phone3'];
		$contact->Email=$_POST['email'];

		if($_POST['action'] == 'Create'){
			if($contact->LastName != null && $contact->LastName != ''){
  				$contact->CreateContact($facDB);
			}
		}else{
			$contact->UpdateContact($facDB);
		}
	}
	$contactList=$contact->GetContactList($facDB);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
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
<h2><?php print $config->ParameterArray['OrgName']; ?></h2>
<?php
echo '<h3>',_("Data Center Contact Detail"),'</h3>
<div class="center"><div>
<form action="',$_SERVER['PHP_SELF'].$formfix,'" method="POST">
<div class="table">
<div>
   <div><label for="contactid">',_("Contact"),'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="contactid" id="contactid" onChange="form.submit()">
   <option value=0>',_("New Contact"),'</option>';

	foreach($contactList as $contactRow ) {
		if($contact->ContactID == $contactRow->ContactID){$selected=' selected="selected"';}else{$selected='';}
		print "<option value=\"$contactRow->ContactID\"$selected>$contactRow->LastName, $contactRow->FirstName</option>";
	}
echo '	</select></div>
</div>
<div>
   <div><label for="UserID">',_("UserID"),'</label></div>
   <div><input type="text" name="UserID" id="UserID" value="',$contact->UserID,'"></div>
</div>
<div>
   <div><label for="lastname">',_("Last Name"),'</label></div>
   <div><input type="text" name="lastname" id="lastname" value="',$contact->LastName,'"></div>
</div>
<div>
   <div><label for="firstname">',_("First Name"),'</label></div>
   <div><input type="text" name="firstname" id="firstname" value="',$contact->FirstName,'"></div>
</div>
<div>
   <div><label for="phone1">',_("Phone 1"),'</label></div>
   <div><input type="text" name="phone1" id="phone1" value="',$contact->Phone1,'"></div>
</div>
<div>
   <div><label for="phone2">',_("Phone 2"),'</label></div>
   <div><input type="text" name="phone2" id="phone2" value="',$contact->Phone2,'"></div>
</div>
<div>
   <div><label for="phone3">',_("Phone 3"),'</label></div>
   <div><input type="text" name="phone3" id="phone3" value="',$contact->Phone3,'"></div>
</div>
<div>
   <div><label for="email">',_("Email"),'</label></div>
   <div><input type="text" size="50" name="email" id="email" value="',$contact->Email,'"></div>
</div>
<div class="caption">';

	if($contact->ContactID >0){
		echo '   <button type="submit" name="action" value="Update">',_("Update"),'</button>';
	}else{
		echo '   <button type="submit" name="action" value="Create">',_("Create"),'</button>';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',_("Return to Main Menu"),' ]</a>'; ?>
</div> <!-- END div.main -->
</div> <!-- END div.page -->
</body>
</html>
