<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Project Detail");

	if(!$person->ContactAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	// AJAX requests

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

	$project = new Projects();

	if(isset($_REQUEST['projectid'])&&($_REQUEST['projectid']>0)){
		$project->ProjectID=(isset($_POST['projectid']) ? $_POST['projectid'] : $_GET['projectid']);
		$project = Projects::getProject( $project->ProjectID );
	}

	if(isset($_POST['action'])&& (($_POST['action']=='Create') || ($_POST['action']=='Update'))){
		$project->ProjectID = $_POST['projectid'];
		$project->ProjectName = $_POST['projectname'];
		$project->ProjectSponsor = $_POST['projectsponsor'];
		$project->ProjectStartDate = $_POST['projectstartdate']>''?date( "Y-m-d", strtotime( $_POST['projectstartdate'])):null;
		$project->ProjectExpirationDate = $_POST['projectexpirationdate']>''?date( "Y-m-d", strtotime( $_POST['projectexpirationdate'])):null;
		$project->ProjectActualEndDate = $_POST['projectactualenddate']>''?date( "Y-m-d", strtotime( $_POST['projectactualenddate'])):null;

		if($project->ProjectName!=''){
			if($_POST['action']=='Create'){
				$project->createProject();
				
				header('Location: '.redirect("project_mgr.php?projectid=$project->ProjectID"));
				exit;
			}else{
				$project->updateProject();
			}
		}
		// Refresh object
		$project = Projects::getProject( $project->ProjectID );
	}

	$projectList=Projects::getProjectList();

	$title = __("openDCIM Project Information" );
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title><?php echo $title; ?></title>
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
		$('.main .center form :input:not([name="projectid"])').attr({readonly:'readonly',disabled:'disabled'})
		$('.color-picker').minicolors('destroy');
		$('.main .center form').validationEngine('hide');
	}
	$(document).ready(function(){
		$('#projectid').change(function(e){
			location.href='project_mgr.php?projectid='+this.value;
		});
		$('.main .center form').validationEngine();
		$('button[value="Delete"]').click(function(e){
			$('#copy').replaceWith($('#deptid').clone().attr('id','copy'));
			$('#copy option[value=0]').text('');
			$('#copy option[value='+$('#deptid').val()+']').remove();

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
   <div>',__("Project"),'</div>
   <div><input type="hidden" name="action" value="query"><select id="projectid" name="projectid">
   <option value=0>',__("New Project"),'</option>';

	foreach($projectList as $projectRow){
		$selected=($project->ProjectID==$projectRow->ProjectID)?" selected":"";
		print "   <option value=\"$projectRow->ProjectID\"$selected>$projectRow->ProjectName</option>\n";
	}

	echo '	</select></div>
</div>
<div>
   <div><label for="projectname">',__("Project Name"),'</label></div>
   <div><input type="text" class="validate[required]" size="50" name="projectname" id="projectname" maxlength="80" value="',$project->ProjectName,'"></div>
</div>
<div>
   <div><label for="projectsponsor">',__("Project Sponsor"),'</label></div>
   <div><input type="text" size="50" name="projectsponsor" id="projectsponsor" maxlength="80" value="',$project->ProjectSponsor,'"></div>
</div>
<div>
   <div><label for="projectstartdate">',__("Project Start Date"),'</label></div>
   <div><input type="text" size="50" name="projectstartdate" id="projectstartdate" maxlength="20" value="',$project->ProjectStartDate,'"></div>
</div>
<div>
   <div><label for="projectexpirationdate">',__("Project Expiration Date"),'</label></div>
   <div><div class="cp"><input type="text" size="50" name="projectexpirationdate" id="projectexpirationdate" maxlength="20" value="',$project->ProjectExpirationDate,'"></div></div>
</div>
<div>
   <div><label for="projectactualenddate">',__("Project Actual End Date"),'</label></div>
   <div><div class="cp"><input type="text" size="50" name="projectactualenddate" id="projectactualenddate" maxlength="20" value="',$project->ProjectActualEndDate,'"></div></div>
</div>';

?>
<div class="caption" id="controls">
<?php
	if($project->ProjectID > 0){
		echo '<button type="submit" name="action" value="Update">',__("Update"),'</button><button type="button" name="action" value="Delete">',__("Delete"),'</button><button type="button" onClick="showgroup(',$project->ProjectID,')">',__("Assign Devices"),'</button>';
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
