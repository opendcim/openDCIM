<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Project Detail");

	if(!$person->WriteAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	// AJAX requests

	if(isset($_POST["delete"]) && $_POST["delete"]=="yes" && $person->ContactAdmin ) {
		Projects::deleteProject( $_POST["projectid"] );
		$status['code']=200;
		$status['msg']=redirect("project_mgr.php?projectid=0");
		header('Content-Type: application/json');
		echo json_encode($status);
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
	function showdevs(obj){
		self.frames['projectadmin'].location.href='project_members.php?membertype=Device&projectid='+obj;
		document.getElementById('projectadmin').style.display = "block";
		document.getElementById('controls').id = "displaynone";
		$('.main .center form :input:not([name="projectid"])').attr({readonly:'readonly',disabled:'disabled'})
		$('.color-picker').minicolors('destroy');
		$('.main .center form').validationEngine('hide');
	}
	function showcabs(obj){
		self.frames['projectadmin'].location.href='project_members.php?membertype=Cabinet&projectid='+obj;
		document.getElementById('projectadmin').style.display = "block";
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
		$(':input[id$=date]').datepicker({dateFormat: "yy-mm-dd"});
		$('button[value=Delete]').click(function(){
			var defaultbutton={
				"<?php echo __("Yes"); ?>": function(){
					$.post('', {projectid: $('select[name=projectid]').val(),delete: 'yes' }, function(data){
						if(data.code==200){
							window.location.assign(data.msg);
						}else{
							alert("Danger, Will Robinson! DANGER!  Something didn't go as planned.");
						}
					});
				}
			}
			var cancelbutton={
				"<?php echo __("No"); ?>": function(){
					$(this).dialog("destroy");
				}
			}
			var modal=$('#deletemodal').dialog({
				dialogClass: 'no-close',
				modal: true,
				width: 'auto',
				buttons: $.extend({}, defaultbutton, cancelbutton)
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
		echo '<button type="submit" name="action" value="Update">',__("Update"),'</button><button type="button" name="action" value="Delete">',__("Delete"),'</button><button type="button" onClick="showdevs(',$project->ProjectID,')">',__("Assign Devices"),'</button><button type="button" onClick="showcabs(',$project->ProjectID,')">',__("Assign Cabinets"),'</button>';
	}else{
    	echo '<button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
<iframe name="projectadmin" id="projectadmin" width="700px" frameborder=0 scrolling="no"></iframe>
<br>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Project delete confirmation"),'" id="deletemodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this project? (Devices will be unassociated, but will not be deleted)."),'<br><br><b>',__("THERE IS NO UNDO"),'</b>
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
