<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	if(!$user->ContactAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	// This page has no function without a valid dept id
	if(!isset($_REQUEST['deptid'])){
		echo "How'd you get here without a referral?";
		exit;
	}
	$dept=new Department();
	$contact=new Contact();

	$dept->DeptID=(isset($_POST['deptid']) ? $_POST['deptid'] : $_GET['deptid']);
	$dept->GetDeptByID();

	// Update if form was submitted and action is set
	if(isset($_POST['action']) && $_POST['action']=="Submit"){
		$grpMembers=$_POST['chosen'];
		$dept->AssignContacts($grpMembers);
	}

	$deptList=$contact->GetContactsForDepartment($dept->DeptID);
	$contactList=$contact->GetContactList();
	$possibleList=array_obj_diff($contactList,$deptList);

	function array_obj_diff($array1,$array2){
		foreach($array1 as $key=>$value){$array1[$key]=serialize($value);}
		foreach($array2 as $key=>$value){$array2[$key]=serialize($value);}
		$array_diff=array_diff($array1,$array2);
		foreach($array_diff as $key=>$value){$array_diff[$key]=unserialize($value);}
		return $array_diff;
	}
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Department Contact Maintenance</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.ui.multiselect.css" type="text/css">
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.ui.multiselect.js"></script>

  <script type="text/javascript">
	$(document).ready(function(){
		$('#chosenList').multiselect();
	});
  </script>

</head>
<body id="deptgroup">
<div class="centermargin">
<?php
echo '<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<input type="hidden" name="deptid" value="',$dept->DeptID,'">
<h3>',__("Group to Administer"),': ',$dept->Name,'<button type="submit" value="Submit" name="action">',__("Submit"),'</button></h3>
<div>
	<select name="chosen[]" id="chosenList" size="6" multiple="multiple">';
	foreach($deptList as $contactRow){
		print "\t\t<option value=\"$contactRow->ContactID\" selected=\"selected\">$contactRow->LastName, $contactRow->FirstName</option>\n";
	}
	foreach($possibleList as $contactRow){
		print "\t\t<option value=\"$contactRow->ContactID\">$contactRow->LastName, $contactRow->FirstName</option>\n";
	}
?>
	</select>
</div>
</form>
</div>
</body>
</html>
