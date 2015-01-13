<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Data Center Department Detail");

	if(!$person->ContactAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$dept=new Department();

	if(isset($_REQUEST['deptid'])&&($_REQUEST['deptid']>0)){
		$dept->DeptID=(isset($_POST['deptid']) ? $_POST['deptid'] : $_GET['deptid']);
		$dept->GetDeptByID();
	}

	if(isset($_POST['action'])&& (($_POST['action']=='Create') || ($_POST['action']=='Update'))){
		$dept->DeptID=$_POST['deptid'];
		$dept->Name=trim($_POST['name']);
		$dept->ExecSponsor=$_POST['execsponsor'];
		$dept->SDM=$_POST['sdm'];
		$dept->Classification=$_POST['classification'];
		$dept->DeptColor=$_POST['deptcolor'];

		if($dept->Name!=''){
			if($_POST['action']=='Create'){
				$dept->CreateDepartment();
			}else{
				$dept->UpdateDepartment();
			}
		}
		// Refresh object
		$dept->GetDeptByID();
	}
	$deptList=$dept->GetDepartmentList();
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Department Information</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.miniColors.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
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
	$('.color-picker').minicolors('destroy');
}
	$(document).ready( function() {
		$(".color-picker").minicolors({
			letterCase: 'uppercase',
			change: function(hex, rgb) {
				logData(hex, rgb);
			}
		});
	});
</script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( 'sidebar.inc.php' );
	echo '<div class="main">
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table centermargin">
<div>
   <div>',__("Department"),'</div>
   <div><input type="hidden" name="action" value="query"><select name="deptid" onChange="form.submit()">
   <option value=0>',__("New Department"),'</option>';

	foreach($deptList as $deptRow){
		if($dept->DeptID == $deptRow->DeptID){$selected=" selected";}else{$selected="";}
		print "   <option value=\"$deptRow->DeptID\"$selected>$deptRow->Name</option>\n";
	}

	echo '	</select></div>
</div>
<div>
   <div><label for="deptname">',__("Department Name"),'</label></div>
   <div><input type="text" size="50" name="name" id="deptname" maxlength="80" value="',$dept->Name,'"></div>
</div>
<div>
   <div><label for="deptsponsor">',__("Executive Sponsor"),'</label></div>
   <div><input type="text" size="50" name="execsponsor" id="deptsponsor" maxlength="80" value="',$dept->ExecSponsor,'"></div>
</div>
<div>
   <div><label for="deptmgr">',__("Account Manager"),'</label></div>
   <div><input type="text" size="50" name="sdm" id="deptmgr" maxlength="80" value="',$dept->SDM,'"></div>
</div>
<div>
   <div><label for="deptcolor">',__("Department Color"),'</label></div>
   <div><div class="cp"><input type="text" class="color-picker" size="50" name="deptcolor" id="deptcolor" maxlength="7" value="',$dept->DeptColor,'"></div></div>
</div>
<div>
   <div><label for="deptclass">',__("Classification"),'</label></div>
   <div><select name="classification" id="deptclass">';

  foreach($config->ParameterArray['ClassList'] as $className){
	  if($dept->Classification==$className){$selected=" selected";}else{$selected="";}
	  print "   <option value=\"$className\"$selected>$className</option>\n";
  }
?>
    </select>
   </div>
</div>
<div class="caption" id="controls">
<?php
	if($dept->DeptID > 0){
		echo '<button type="submit" name="action" value="Update">',__("Update"),'</button><button type="button" onClick="showgroup(',$dept->DeptID,')">',__("Assign Contacts"),'</button>';
	}else{
    	echo '<button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
<iframe name="groupadmin" id="groupadmin" frameborder=0 scrolling="no"></iframe>
<br>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div> <!-- END div.main -->
</div> <!-- END div.page -->
</body>
<script type="text/javascript">
$('iframe').load(function() {
    this.style.height =
    this.contentWindow.document.body.offsetHeight + 'px';
});
</script>
</html>
