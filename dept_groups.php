<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	if(!$person->ContactAdmin){
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
	$person=new People();

	$dept->DeptID=(isset($_POST['deptid']) ? $_POST['deptid'] : $_GET['deptid']);
	$dept->GetDeptByID();

	// Update if form was submitted and action is set
	if(isset($_POST['action']) && $_POST['action']=="Submit"){
		$grpMembers=$_POST['chosen'];
		$dept->AssignContacts($grpMembers);
	}

	$deptList=$person->GetPeopleByDepartment($dept->DeptID);
	$contactList=$person->GetUserList();
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
  
  <title><?php __("openDCIM Department Contact Maintenance"); ?></title>
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
	<select name="chosen[]" id="chosenList" size="15" multiple="multiple">';
	foreach($deptList as $personRow){
		print "\t\t<option value=\"$personRow->PersonID\" selected=\"selected\">$personRow->LastName, $personRow->FirstName</option>\n";
	}
	foreach($possibleList as $personRow){
		print "\t\t<option value=\"$personRow->PersonID\">$personRow->LastName, $personRow->FirstName</option>\n";
	}
?>
	</select>
</div>
</form>
</div>
</body>
</html>
