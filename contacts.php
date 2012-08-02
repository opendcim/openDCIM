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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
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
<h3>Data Center Contact Detail</h3>
<div class="center"><div>
<form action="<?php print $_SERVER['PHP_SELF'].$formfix; ?>" method="POST">
<div class="table">
<div>
   <div><label for="contactid">Contact</label></div>
   <div><input type="hidden" name="action" value="query"><select name="contactid" id="contactid" onChange="form.submit()">
   <option value=0>New Contact</option>
<?php
	foreach($contactList as $contactRow ) {
		if($contact->ContactID == $contactRow->ContactID){$selected=' selected="selected"';}else{$selected='';}
		print "<option value=\"$contactRow->ContactID\"$selected>$contactRow->LastName, $contactRow->FirstName</option>";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="UserID">UserID</label></div>
   <div><input type="text" name="UserID" id="UserID" value="<?php print $contact->UserID; ?>"></div>
</div>
<div>
   <div><label for="lastname">Last Name</label></div>
   <div><input type="text" name="lastname" id="lastname" value="<?php print $contact->LastName; ?>"></div>
</div>
<div>
   <div><label for="firstname">First Name</label></div>
   <div><input type="text" name="firstname" id="firstname" value="<?php print $contact->FirstName; ?>"></div>
</div>
<div>
   <div><label for="phone1">Phone 1</label></div>
   <div><input type="text" name="phone1" id="phone1" value="<?php print $contact->Phone1; ?>"></div>
</div>
<div>
   <div><label for="phone2">Phone 2</label></div>
   <div><input type="text" name="phone2" id="phone2" value="<?php print $contact->Phone2; ?>"></div>
</div>
<div>
   <div><label for="phone3">Phone 3</label></div>
   <div><input type="text" name="phone3" id="phone3" value="<?php print $contact->Phone3; ?>"></div>
</div>
<div>
   <div><label for="email">Email</label></div>
   <div><input type="text" size="50" name="email" id="email" value="<?php print $contact->Email; ?>"></div>
</div>
<div class="caption">
<?php
	if($contact->ContactID >0){
		echo '   <input type="submit" name="action" value="Update">';
	}else{
		echo '   <input type="submit" name="action" value="Create">';
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
