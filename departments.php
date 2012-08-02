<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$dept=new Department();
	$user=new User();

	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->ContactAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	if(isset($_REQUEST['deptid'])&&($_REQUEST['deptid']>0)){
		$dept->DeptID=(isset($_POST['deptid']) ? $_POST['deptid'] : $_GET['deptid']);
		$dept->GetDeptByID( $facDB );
	}

	if(isset($_POST['action'])&& (($_POST['action']=='Create') || ($_POST['action']=='Update'))){
		$dept->DeptID=$_POST['deptid'];
		$dept->Name=$_POST['name'];
		$dept->ExecSponsor=$_POST['execsponsor'];
		$dept->SDM=$_POST['sdm'];
		$dept->Classification=$_POST['classification'];
		$dept->DeptColor=$_POST['deptcolor'];

		if($_REQUEST['action']=='Create' && ($dept->Name != '' && $dept->Name != null)){
			$dept->CreateDepartment($facDB);
		}else{
			$dept->UpdateDepartment($facDB);
		}
	}
	$deptList=$dept->GetDepartmentList($facDB);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Department Information</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery.miniColors.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.miniColors.js"></script>
<script type="text/javascript">
function showgroup(obj){
	self.frames['groupadmin'].location.href='dept_groups.php?deptid='+obj;
	document.getElementById('groupadmin').style.display = "block";
	document.getElementById('deptname').readOnly = true
	document.getElementById('deptsponsor').readOnly = true
	document.getElementById('deptmgr').readOnly = true
	document.getElementById('deptcolor').readOnly = true
	document.getElementById('deptclass').disabled = true
	document.getElementById('controls').id = "displaynone";
	$('.color-picker ~ a').remove();
	$('.color-picker').unbind();
}
	$(document).ready( function() {
		$(".color-picker").miniColors({
			letterCase: 'uppercase',
			change: function(hex, rgb) {
				logData(hex, rgb);
			}
		});
	});
</script>
</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Department Detail</h3>
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<div class="table centermargin">
<div>
   <div>Department</div>
   <div><input type="hidden" name="action" value="query"><select name="deptid" onChange="form.submit()">
   <option value=0>New Department</option>
<?php
	foreach($deptList as $deptRow){
		if($dept->DeptID == $deptRow->DeptID){$selected=" selected";}else{$selected="";}
		print "   <option value=\"$deptRow->DeptID\"$selected>$deptRow->Name</option>\n";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="deptname">Department Name</label></div>
   <div><input type="text" size="50" name="name" id="deptname" value="<?php echo $dept->Name; ?>"></div>
</div>
<div>
   <div><label for="deptsponsor">Executive Sponsor</label></div>
   <div><input type="text" size="50" name="execsponsor" id="deptsponsor" value="<?php echo $dept->ExecSponsor; ?>"></div>
</div>
<div>
   <div><label for="deptmgr">Account Manager</label></div>
   <div><input type="text" size="50" name="sdm" id="deptmgr" value="<?php echo $dept->SDM; ?>"></div>
</div>
<div>
   <div><label for="deptcolor">Department Color</label></div>
   <div><div class="cp"><input type="text" class="color-picker" size="50" name="deptcolor" id="deptcolor" value="<?php echo $dept->DeptColor; ?>"></div></div>
</div>
<div>
   <div><label for="deptclass">Classification</label></div>
   <div><select name="classification" id="deptclass">
<?php
  foreach($config->ParameterArray['ClassList'] as $className){
	  if($dept->Classification==$className){$selected=" selected";}else{$selected="";}
	  print "   <option value=\"$className\"$selected>$className</option>\n";
  }
?>
    </select>
   </div>
</div>
<div class="caption" id="controls">
    <input type="submit" name="action" value="Create">
<?php
	if($dept->DeptID > 0){
		print "<input type=\"submit\" name=\"action\" value=\"Update\">\n<input type=\"button\" onClick=\"showgroup($dept->DeptID)\" value=\"Assign Contacts\">";
	}
?>
</div>
</div> <!-- END div.table -->
</form>
<iframe name="groupadmin" id="groupadmin" frameborder=0 scrolling="no"></iframe>
<br>
</div></div>
<a href="index.php">[ Return to Main Menu ]</a>
</div> <!-- END div.main -->
</div> <!-- END div.page -->
</body>
</html>
