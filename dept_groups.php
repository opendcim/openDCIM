<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$dept=new Department();
	$contact=new Contact();
	$user=new User();

	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

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
	$dept->DeptID=(isset($_POST['deptid']) ? $_POST['deptid'] : $_GET['deptid']);
	$dept->GetDeptByID($facDB);

	// Update if form was submitted and action is set
	if(isset($_POST['action']) && $_POST['action']=="Submit"){
		$grpMembers=$_POST['chosen'];
		$dept->AssignContacts($grpMembers,$facDB);
	}

	$deptList=$contact->GetContactsForDepartment($dept->DeptID,$facDB);
	$contactList=$contact->GetContactList($facDB);
	$possibleList=array_obj_diff($contactList,$deptList);

	function array_obj_diff($array1,$array2){
		foreach($array1 as $key=>$value){$array1[$key]=serialize($value);}
		foreach($array2 as $key=>$value){$array2[$key]=serialize($value);}
		$array_diff=array_diff($array1,$array2);
		foreach($array_diff as $key=>$value){$array_diff[$key]=unserialize($value);}
		return $array_diff;
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Department Contact Maintenance</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
<script type="text/javascript">
function addToList()
{
  var from = document.getElementById( 'possibleList' );
  var to = document.getElementById( 'chosenList' );
  
  if (from.options.length > 0 ) { 
    if (to.options[0].value == 'temp') {
      to.options.length = 0;
    }
  }
  var sel = false;
  for (i=0;i<from.options.length;i++)
  {
    var current = from.options[i];
    if (current.selected) {
      sel = true;
      if (current.value == 'temp') {
        alert ('You cannot move this text!');
        return;
      }
      var newOpt = document.createElement('option');
      newOpt.text = current.text;
      newOpt.value = current.value;
      
      try {
        to.add(newOpt, null); // Standards compliant, doesn't work in IE
      }
      catch(ex) {
        to.add(newOpt);       // Works in IE
      }

      from.remove(i);
    }
  }
}

function removeFromList()
{
  var from = document.getElementById( 'chosenList' );
  var to = document.getElementById( 'possibleList' );
  
  if (from.options.length > 0 ) { 
    if (to.options[0].value == 'temp') {
      to.options.length = 0;
    }
  }
  var sel = false;
  for (i=0;i<from.options.length;i++)
  {
    var current = from.options[i];
    if (current.selected)
    {
      sel = true;
      if (current.value == 'temp')
      {
        alert ('You cannot move this text!');
        return;
      }
      var newOpt = document.createElement('option');
      newOpt.text = current.text;
      newOpt.value = current.value;
      
      try {
        to.add(newOpt, null); // Standards compliant, doesn't work in IE
      }
      catch(ex) {
        to.add(newOpt);       // Works in IE
      }

      from.remove(i);
    }
  }
}

function allSelect()
{
  List = document.getElementById( 'chosenList' );
  if (List.length && List.options[0].value == 'temp') return;
  for (i=0;i<List.length;i++)
  {
     List.options[i].selected = true;
  }
}
</script>
</head>
<body id="deptgroup">
<div class="centermargin">
<form action="<?php print $_SERVER['PHP_SELF']; ?>" method="POST" onSubmit="allSelect()">
<input type="hidden" name="deptid" value="<?php print $dept->DeptID; ?>">
<h3>Group to Administer: <?php print $dept->Name; ?></h3>
<div>
Possible Contacts
<select name="possible" id="possibleList" size="6" multiple="multiple">
<?php
	foreach($possibleList as $contactRow){
		print "<option value=\"$contactRow->ContactID\">$contactRow->LastName, $contactRow->FirstName</option>\n";
	}
?>
</select>
</div>
<div>
<input type="button" value="-->" onClick="javascript:addToList()" /><br>
<input type="button" value="<--" onClick="javascript:removeFromList()" /><br>
<input type="submit" value="Submit" name="action">
</div>
<div>
Assigned Contacts
<select name="chosen[]" id="chosenList" size="6" multiple="multiple">
<?php
	if(count($deptList)==0){print "<option value=\"temp\" />\n";}
	foreach($deptList as $contactRow){
		print "<option value=\"$contactRow->ContactID\">$contactRow->LastName, $contactRow->FirstName</option>\n";
	}
?>
</select>
</div>
</form>
</div>
</body>
</html>
