<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$subheader=__("Data Center Manufacturer Listing");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$mfg=new Manufacturer();

	// AJAX Start
	if(isset($_GET['getTemplateCount']) && isset($_GET['ManufacturerID'])){
		$temp=new DeviceTemplate();
		$temp->ManufacturerID=$_GET['ManufacturerID'];
		header('Content-Type: application/json');
		echo json_encode($temp->GetTemplateListByManufacturer());
		exit;
	}

	if(isset($_POST['action']) && $_POST["action"]=="Delete"){
		header('Content-Type: application/json');
		$response=false;
		if(isset($_POST["TransferTo"])){
			$mfg->ManufacturerID=$_POST['ManufacturerID'];
			if($mfg->DeleteManufacturer($_POST["TransferTo"])){
				$response=true;
			}
		}
		echo json_encode($response);
		exit;
	}

	// END - AJAX

	if(isset($_REQUEST["ManufacturerID"]) && $_REQUEST["ManufacturerID"] >0){
		$mfg->ManufacturerID=(isset($_POST['ManufacturerID']) ? $_POST['ManufacturerID'] : $_GET['ManufacturerID']);
		$mfg->GetManufacturerByID();
	}

	$status="";
	if(isset($_POST["action"])&&(($_POST["action"]=="Create")||($_POST["action"]=="Update"))){
		$mfg->ManufacturerID=$_POST["ManufacturerID"];
		$mfg->Name=trim($_POST["name"]);
		$mfg->SubscribeToUpdates = isset( $_POST['SubscribeToUpdates'] ) ? 1 : 0;

		if($mfg->Name != null && $mfg->Name != ""){
			if($_POST["action"]=="Create"){
				if($mfg->CreateManufacturer()){
					header('Location: '.redirect("device_manufacturers.php?ManufacturerID=$mfg->ManufacturerID"));
				}else{
					$status=__("Error adding new manufacturer");
				}
			}else{
				$status=__("Updated");
				$mfg->UpdateManufacturer();
			}
		}
		//We either just created a manufacturer or updated it so reload from the db
		$mfg->GetManufacturerByID();
	}
	$mfgList=$mfg->GetManufacturerList();
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Device Class Templates</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>

  <style type="text/css">
	#using { margin-top: 1em; }
  </style>

  <script type="text/javascript">
	$(document).ready(function() {
		$('#mform').validationEngine({});
		$('#ManufacturerID').change(function(e){
			location.href='device_manufacturers.php?ManufacturerID='+this.value;
		});
		// Show number of templates using manufacturer
		UpdateCount();

		$('button[name="action"][value="Delete"]').click(DeleteManufacturer);

	});

	function UpdateCount(e){
		var count;
		$.ajax({
			type:'get',
			async: false, 
			data:{getTemplateCount: $('#ManufacturerID').val()},
			success: function(data){
				$('#count').text(data.length);
				count=data.length;
			}
		});
		return count;
	}

	function DeleteManufacturer(){
		function DeleteNow(manufacturerid){
			// If manufacturerid unset then just delete 
			transferto=(typeof(manufacturerid)=='undefined')?0:manufacturerid;
			$.post('',{ManufacturerID: $('#ManufacturerID').val(), TransferTo: transferto, action: 'Delete'},function(data){
				if(data){
					location.href='';
				}else{
					alert("Something's gone horrible wrong");
				}
			});
		}

		// if there aren't any templates using this manufacturer just delete it.
		if(parseInt(UpdateCount())){
			$('#copy').replaceWith($('#ManufacturerID').clone().attr('id','copy'));
			$('#copy option[value=0]').remove();
			$('#copy option[value='+$('#ManufacturerID').val()+']').remove();
			$('#deletemodal').dialog({
				width: 600,
				modal: true,
				buttons: {
					Transfer: function(e){
						$('#doublecheck').dialog({
							width: 600,
							modal: true,
							buttons: {
								Yes: function(e){
									DeleteNow($('#copy').val());
								},
								No: function(e){
									$('#doublecheck').dialog('destroy');
									$('#deletemodal').dialog('destroy');
								}
							}
						});
					},
					No: function(e){
						$('#deletemodal').dialog('destroy');
					}
				}
			});
		}else{
			DeleteNow();
		}
	}

  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<h3>',$status,'</h3>
<div class="center"><div>
<form id="mform" method="POST">
<div class="table">
<div>
   <div><label for="ManufacturerID">',__("Manufacturer"),'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="ManufacturerID" id="ManufacturerID">
   <option value=0>',__("New Manufacturer"),'</option>';

	foreach($mfgList as $mfgRow){
		if($mfg->ManufacturerID==$mfgRow->ManufacturerID){$selected=" selected";}else{$selected="";}
		echo "<option value=\"$mfgRow->ManufacturerID\"$selected>$mfgRow->Name</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="gid">',__("Global ID"),'</label></div>
   <div><input type="text" id="gid" value="',$mfg->GlobalID,'" disabled></div>
</div>
<div>
   <div><label for="name">',__("Name"),'</label></div>
   <div><input type="text" class="validate[required,minSize[1],maxSize[40]]" name="name" id="name" maxlength="40" value="',$mfg->Name,'"></div>
</div>
<div>
   <div><label for="SubscribeToUpdates">',__("Subscribe to Repository"),'</label></div>
   <div><input type="checkbox" name="SubscribeToUpdates" id="SubscribeToUpdates" ', $mfg->SubscribeToUpdates == 1 ? 'checked' : '', $mfg->GlobalID>0 ? '' : ' disabled','></div>
</div>
<div class="caption">';

	if($mfg->ManufacturerID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>
	<button type="button" name="action" value="Delete">',__("Delete"),'</button>';
	}else{
		echo '   <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
?>
</div>
</div><!-- END div.table -->
<?php
	if($mfg->ManufacturerID >0){
		echo '	<div id="using">',__("Templates using this Manufacturer"),':<span id="count">0</span></div>';
	}
?>
</form>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Manufacturer delete confirmation"),'" id="deletemodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this Manufacturer?"),'
		<br><br>
		<div>Transfer all existing templates to <select id="copy"></select></div>
		</div>
	</div>
	<div title="',__("Are you REALLY sure?"),'" id="doublecheck">
		<div id="modaltext" class="warning"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure REALLY sure?  There is no undo!!"),'
		<br><br>
		</div>
	</div>
</div>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
