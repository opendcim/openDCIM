<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	if(!$person->ContactAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	// This page has no function without a valid dept id
	if(!isset($_REQUEST['personid'])){
		echo "How'd you get here without a referral?";
		exit;
	}
	$dept=new Department();
	$person=new People();

	$person->PersonID=(isset($_POST['personid']) ? $_POST['personid'] : $_GET['personid']);
	$person->GetPerson();
	
	// Update if form was submitted and action is set
	if(isset($_POST['action']) && $_POST['action']=="Submit"){
		$grpMembers=$_POST['chosen'];
		$person->AssignDepartments($grpMembers);
	}

	$deptList=$person->GetDeptsByPerson();
	$departmentList=$dept->GetDepartmentList();
	$possibleList=array_obj_diff($departmentList,$deptList);

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
  
  <title><?php __("openDCIM Department Membership Maintenance"); ?></title>
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
<input type="hidden" name="personid" value="',$person->PersonID,'">
<h3>',__("Department Membership"),': ',$dept->Name,'<button type="submit" value="Submit" name="action">',__("Submit"),'</button></h3>
<div>
	<select name="chosen[]" id="chosenList" size="15" multiple="multiple">';
	foreach($deptList as $dRow){
		print "\t\t<option value=\"$dRow->DeptID\" selected=\"selected\">$dRow->Name</option>\n";
	}
	foreach($possibleList as $dRow){
		print "\t\t<option value=\"$dRow->DeptID\">$dRow->Name</option>\n";
	}
?>
	</select>
</div>
</form>
</div>
</body>
</html>
