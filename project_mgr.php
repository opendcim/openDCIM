<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');
	require_once('classes/MaitriseType.class.php');

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

	// Handle Maitrise Type management actions (admin only)
	if(isset($_POST['mt_action']) && $person->ContactAdmin){
		if($_POST['mt_action']==='AddType' && isset($_POST['newmaitrise'])){
			$name=trim($_POST['newmaitrise']);
			if($name!==''){
				MaitriseType::Insert($name);
			}
		}
		if($_POST['mt_action']==='DeleteType' && isset($_POST['deletetypeid'])){
			$id=intval($_POST['deletetypeid']);
			if($id>0){
				MaitriseType::Delete($id);
			}
		}
		if($_POST['mt_action']==='UpdateType' && isset($_POST['updatetypeid'])){
			$id=intval($_POST['updatetypeid']);
			$name=isset($_POST['updatename'])?trim($_POST['updatename']):'';
			if($id>0 && $name!==''){
				MaitriseType::Update($id,$name);
			}
		}
		// Refresh to avoid resubmission
		header('Location: '.redirect('project_mgr.php'.(isset($_REQUEST['projectid'])?'?projectid='.(int)$_REQUEST['projectid']:'')));
		exit;
	}

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
				// Save Maitrise rows for the new project
				if($project->ProjectID>0){
					saveProjectMaitriseRows($project->ProjectID);
				}
				header('Location: '.redirect("project_mgr.php?projectid=$project->ProjectID"));
				exit;
			}else{
				$project->updateProject();
				// Save Maitrise rows for existing project
				saveProjectMaitriseRows($project->ProjectID);
			}
		}
		// Refresh object
		$project = Projects::getProject( $project->ProjectID );
	}

	$projectList=Projects::getProjectList();

	// Helper to check if a table exists
	function tableExists($name){
		global $dbh;
		try{
			$st=$dbh->prepare("SHOW TABLES LIKE :t");
			$st->execute(array(":t"=>$name));
			return ($st->rowCount()>0);
		}catch(Exception $e){ return false; }
	}

	// Fetch Maitrise types and existing rows for this project
	$maitriseTypes = array();
	if(function_exists('tableExists') && tableExists('fac_MaitriseType')){
		$maitriseTypes = MaitriseType::GetAll();
	}
	$projectMaitriseRows = array();
	if(isset($project->ProjectID) && $project->ProjectID>0 && tableExists('fac_ProjectMaitrise')){
		$st=$dbh->prepare("SELECT pm.ProjectMaitriseID, pm.MaitriseTypeID, pm.BureauName, pm.BureauEmail FROM fac_ProjectMaitrise pm WHERE pm.ProjectID=:pid ORDER BY pm.ProjectMaitriseID ASC");
		$st->execute(array(":pid"=>$project->ProjectID));
		$projectMaitriseRows=$st->fetchAll(PDO::FETCH_ASSOC);
	}

	// Helper function to save Maitrise rows
	function saveProjectMaitriseRows($projectID){
		global $dbh;
		// Ensure required table exists; if not, skip gracefully
		try{
			$chk=$dbh->query("SHOW TABLES LIKE 'fac_ProjectMaitrise'");
			if(!$chk || $chk->rowCount()==0){
				return; // table not present yet
			}
		}catch(Exception $ex){
			return;
		}
		$types = isset($_POST['maitrise_type']) && is_array($_POST['maitrise_type']) ? $_POST['maitrise_type'] : array();
		$names = isset($_POST['maitrise_bureau']) && is_array($_POST['maitrise_bureau']) ? $_POST['maitrise_bureau'] : array();
		$emails = isset($_POST['maitrise_email']) && is_array($_POST['maitrise_email']) ? $_POST['maitrise_email'] : array();
		// Clear existing
		$del=$dbh->prepare("DELETE FROM fac_ProjectMaitrise WHERE ProjectID=:pid");
		$del->execute(array(":pid"=>$projectID));
		// Insert new
		$ins=$dbh->prepare("INSERT INTO fac_ProjectMaitrise SET ProjectID=:pid, MaitriseTypeID=:mtid, BureauName=:bname, BureauEmail=:bemail");
		for($i=0;$i<count($types);$i++){
			$mtid=intval($types[$i]);
			$bname=isset($names[$i])?trim($names[$i]):'';
			$bemail=isset($emails[$i])?trim($emails[$i]):'';
			// Allow empty email: save NULL when not provided
			if($mtid>0 && $bname!==''){
				$bemailParam = ($bemail==='')? null : $bemail;
				$ins->execute(array(":pid"=>$projectID, ":mtid"=>$mtid, ":bname"=>$bname, ":bemail"=>$bemailParam));
			}
		}
	}

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
  <script type="text/javascript" src="scripts/common.js?v<?php echo filemtime('scripts/common.js');?>"></script>
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
		// Make project list searchable (type to filter)
		$('#projectid').combobox();
		$('span.custom-combobox').width($('span.custom-combobox').width()+2);
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

<input type="hidden" name="deletetypeid" value="">
<input type="hidden" name="updatetypeid" value="">
<input type="hidden" name="updatename" value="">

<div class="caption"><?php echo __("Maitrise"); ?><?php if($person->ContactAdmin){ ?>
  <button type="button" id="manage-maitrise-types" style="float:right;">&nbsp;<?php echo __("Manage Maitrise Types"); ?></button>
<?php } ?></div>
<div>
  <div><label><?php echo __("Maitrise Entries"); ?></label></div>
  <div>
    <div id="maitrise-rows">
    <?php
    // Render existing rows
    if(!empty($projectMaitriseRows)){
      foreach($projectMaitriseRows as $row){
        echo '<div class="maitrise-row">';
        echo '<select name="maitrise_type[]">';
        foreach($maitriseTypes as $mt){
          $sel = ($mt->MaitriseTypeID==$row['MaitriseTypeID'])? ' selected' : '';
          echo '<option value="'.$mt->MaitriseTypeID.'"'.$sel.'>'.htmlspecialchars($mt->MaitriseName).'</option>';
        }
        echo '<option value="__more__">'.__("More").'</option>';
        echo '</select> ';
        echo '<input type="text" name="maitrise_bureau[]" placeholder="'.__("Bureau Name").'" value="'.htmlspecialchars($row['BureauName']).'"> ';
        echo '<input type="email" name="maitrise_email[]" placeholder="'.__("Email").'" value="'.htmlspecialchars($row['BureauEmail']).'"> ';
        echo '<button type="button" class="removemaitrise">'.__("Remove").'</button>';
        echo '</div>';
      }
    }
    ?>
    </div>
    <button type="button" id="add-maitrise"><?php echo __("Add Maitrise"); ?></button>
    <div id="maitrise-template" style="display:none;">
      <div class="maitrise-row">
        <select name="maitrise_type[]">
          <?php foreach($maitriseTypes as $mt){ echo '<option value="'.$mt->MaitriseTypeID.'">'.htmlspecialchars($mt->MaitriseName).'</option>'; } ?>
          <option value="__more__"><?php echo __("More"); ?></option>
        </select>
        <input type="text" name="maitrise_bureau[]" placeholder="<?php echo __("Bureau Name"); ?>">
        <input type="email" name="maitrise_email[]" placeholder="<?php echo __("Email"); ?>">
        <button type="button" class="removemaitrise"><?php echo __("Remove"); ?></button>
      </div>
    </div>
  </div>
</div>
<?php if($person->ContactAdmin){ ?>
<!-- Maitrise Types modal moved outside form; uses hidden fields in main form -->
<div id="maitrisetypesmodal" title="<?php echo __("Maitrise Types"); ?>" style="display:none;">
  <div>
    <div><strong><?php echo __("Existing Types"); ?></strong></div>
    <div>
      <table class="dcim-table" style="width:100%;">
        <thead>
          <tr><th><?php echo __("Type Name"); ?></th><th style="width:160px;">&nbsp;</th></tr>
        </thead>
        <tbody>
        <?php foreach($maitriseTypes as $mt){ ?>
          <tr>
            <td>
              <input type="text" name="mt_name_<?php echo $mt->MaitriseTypeID; ?>" value="<?php echo htmlspecialchars($mt->MaitriseName); ?>" style="width:100%;">
            </td>
            <td>
              <button type="button" class="mt-save" data-id="<?php echo $mt->MaitriseTypeID; ?>" data-action="UpdateType"><?php echo __("Save"); ?></button>
              <button type="button" class="mt-delete" data-id="<?php echo $mt->MaitriseTypeID; ?>" data-action="DeleteType"><?php echo __("Delete"); ?></button>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <div style="margin-top:10px;">
    <div><strong><?php echo __("Add New Maitrise Type"); ?></strong></div>
    <div>
      <input type="text" id="newmaitrise" maxlength="100" style="width:60%;">
      <button type="button" id="add-maitrise-type" data-action="AddType"><?php echo __("Add"); ?></button>
    </div>
  </div>
  <p class="note"><?php echo __("Changes save immediately when you click Save/Delete."); ?></p>
</div>
<?php } ?>
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
<iframe name="projectadmin" id="projectadmin" width="700px" frameborder=0 scrolling="no" height="400px"></iframe>
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
$('iframe').on('load', function() {
    this.style.height =
    this.contentWindow.document.body.offsetHeight + 'px';
});
<?php if($person->ContactAdmin){ ?>
// Open modal for Maitrise Types
$('#manage-maitrise-types').on('click', function(){
  $('#maitrisetypesmodal').dialog({
    modal:true,
    width: 'auto'
  });
});
<?php } ?>
// Vanilla JS for Maitrise rows
document.addEventListener('DOMContentLoaded', function(){
  var addBtn = document.getElementById('add-maitrise');
  var rows = document.getElementById('maitrise-rows');
  var tmpl = document.getElementById('maitrise-template');
  if(addBtn && rows && tmpl){
    addBtn.addEventListener('click', function(){
      var node = tmpl.firstElementChild.cloneNode(true);
      rows.appendChild(node);
    });
    rows.addEventListener('click', function(e){
      if(e.target && e.target.classList.contains('removemaitrise')){
        var r = e.target.closest('.maitrise-row');
        if(r){ r.remove(); }
      }
    });
  }
  // Open modal when selecting "More" in type dropdown
  $(document).on('change','select[name="maitrise_type[]"]', function(){
    if(this.value==='__more__'){
      // reset to first real option after opening
      var firstVal = $(this).find('option').not('[value="__more__"]').first().val();
      // Open the modal (same settings as the button)
      $('#maitrisetypesmodal').dialog({ modal:true, width:'auto' });
      // Revert selection
      if(firstVal!==undefined){ $(this).val(firstVal); }
    }
  });
  // Maitrise Types modal buttons - submit main form
  var modal = document.getElementById('maitrisetypesmodal');
  if(modal){
    modal.addEventListener('click', function(e){
      var form = document.querySelector('.main .center form');
      if(!form){ return; }
      if(e.target && e.target.classList.contains('mt-save')){
        var id = e.target.getAttribute('data-id');
        var input = modal.querySelector('input[name="mt_name_'+id+'"]');
        if(input){
          form.querySelector('input[name=updatetypeid]').value = id;
          form.querySelector('input[name=updatename]').value = input.value;
          var a = document.createElement('input');
          a.type='hidden'; a.name='mt_action'; a.value='UpdateType';
          form.appendChild(a);
          form.submit();
        }
      } else if(e.target && e.target.classList.contains('mt-delete')){
        var idd = e.target.getAttribute('data-id');
        form.querySelector('input[name=deletetypeid]').value = idd;
        var a2 = document.createElement('input');
        a2.type='hidden'; a2.name='mt_action'; a2.value='DeleteType';
        form.appendChild(a2);
        form.submit();
      }
    });
    var addTypeBtn = document.getElementById('add-maitrise-type');
    if(addTypeBtn){
      addTypeBtn.addEventListener('click', function(){
        var form = document.querySelector('.main .center form');
        var val = document.getElementById('newmaitrise').value.trim();
        if(form && val!==''){
          var a3 = document.createElement('input');
          a3.type='hidden'; a3.name='mt_action'; a3.value='AddType';
          var nv = document.createElement('input');
          nv.type='hidden'; nv.name='newmaitrise'; nv.value=val;
          form.appendChild(a3); form.appendChild(nv);
          form.submit();
        }
      });
    }
  }
});
</script>
</html>
