<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Data Center Department Detail");

	if(!$person->ContactAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	// AJAX requests

	if(isset($_GET['objectcount'])){
		$cab=new Cabinet();
		$dev=new Device();
		$cab->AssignedTo=$dev->Owner=$_GET['deptid'];

		$return=array();
		$return['cabinets']=count($cab->GetCabinetsByDept());
		$return['devices']=count($dev->GetDevicesbyOwner());
		$return['people']=count($person->GetPeopleByDepartment($dev->Owner));

		header('Content-Type: application/json');
		echo json_encode($return);
		exit;
	}

	if(isset($_POST['action']) && $_POST["action"]=="Delete"){
		header('Content-Type: application/json');
		$response=false;
		if(isset($_POST["TransferTo"])){
			$dept=new Department();
			$dept->DeptID=$_POST['deptid'];
			if($dept->DeleteDepartment($_POST["TransferTo"])){
				$response=true;
			}
		}
		echo json_encode($response);
		exit;
	}
	// END - AJAX requests

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
				
				header('Location: '.redirect("departments.php?deptid=$dept->DeptID"));
				exit;
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
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.miniColors.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.miniColors.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
<script type="text/javascript">
	function showgroup(obj){
		self.frames['groupadmin'].location.href='dept_groups.php?deptid='+obj;
		document.getElementById('groupadmin').style.display = "block";
		document.getElementById('controls').id = "displaynone";
		$('.main .center form :input:not([name="deptid"])').attr({readonly:'readonly',disabled:'disabled'})
		$('.color-picker').minicolors('destroy');
		$('.main .center form').validationEngine('hide');
	}
	$(document).ready(function(){
		$('#deptid').change(function(e){
			location.href='departments.php?deptid='+this.value;
		});
		$(".color-picker").minicolors({
			letterCase: 'uppercase',
			change: function(hex, rgb) {
				logData(hex, rgb);
			}
		});
		$('.main .center form').validationEngine();
		$('button[value="Delete"]').click(function(e){
			$('#copy').replaceWith($('#deptid').clone().attr('id','copy'));
			$('#copy option[value=0]').text('');
			$('#copy option[value='+$('#deptid').val()+']').remove();

			// Get a count of objects owned by this department.
			$.get('',{objectcount: '',deptid: $('#deptid').val()},function(data){
				function newtab(e){
					var poopup=window.open('search.php?key=owner&deptid='+$('#deptid').val()+'&search='+encodeURIComponent($('#deptid option:selected').text()),'search');
					poopup.focus();
				}

				$('#cnt_cabinets').text(data.cabinets).click(newtab);
				$('#cnt_devices').text(data.devices).click(newtab);
				$('#cnt_users').text(data.people).click(newtab);
			});

			$('#deletemodal').dialog({
				width: 900,
				modal: true,
				buttons: {
					Transfer: function(e){
						$('#doublecheck').dialog({
							width: 600,
							modal: true,
							buttons: {
								Yes: function(e){
									$.post('',{action:'Delete',deptid:$('#deptid').val(),TransferTo:$('#copy').val()},function(data){
										if(data){
											location.href='departments.php?deptid='+$('#copy').val();
										}else{
											alert('something stupid has happened');
										}
									});
								}
							}
						});
					},
					No: function(e){
						$('#deletemodal').dialog('destroy');
					}
				}
			});
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
<form method="POST">
<div class="table centermargin">
<div>
   <div>',__("Department"),'</div>
   <div><input type="hidden" name="action" value="query"><select id="deptid" name="deptid">
   <option value=0>',__("New Department"),'</option>';

	foreach($deptList as $deptRow){
		$selected=($dept->DeptID==$deptRow->DeptID)?" selected":"";
		print "   <option value=\"$deptRow->DeptID\"$selected>$deptRow->Name</option>\n";
	}

	echo '	</select></div>
</div>
<div>
   <div><label for="deptname">',__("Department Name"),'</label></div>
   <div><input type="text" class="validate[required]" size="50" name="name" id="deptname" maxlength="80" value="',$dept->Name,'"></div>
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
	  $selected=($dept->Classification==$className)?" selected":"";
	  print "   <option value=\"$className\"$selected>$className</option>\n";
  }
?>
    </select>
   </div>
</div>
<div class="caption" id="controls">
<?php
	if($dept->DeptID > 0){
		echo '<button type="submit" name="action" value="Update">',__("Update"),'</button><button type="button" name="action" value="Delete">',__("Delete"),'</button><button type="button" onClick="showgroup(',$dept->DeptID,')">',__("Assign Contacts"),'</button>';
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
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Department delete confirmation"),'" id="deletemodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this Department?"),'
		<br><br>
		<div>',__("Cabinets"),': <span id="cnt_cabinets"></span>&nbsp;&nbsp;&nbsp;',__("Devices"),': <span id="cnt_devices"></span>&nbsp;&nbsp;&nbsp;',__("Users"),': <span id="cnt_users"></span></div>
		<br>
		<div>Transfer all existing equipment and users to <select id="copy"></select></div>
		</div>
	</div>
	<div title="',__("Are you REALLY sure?"),'" id="doublecheck">
		<div id="modaltext" class="warning"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure REALLY sure?  There is no undo!!"),'
		<br><br>
		</div>
	</div>
</div>'; ?>
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
