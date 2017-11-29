<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$subheader=__("Rows of Cabinets");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$cabrow=new CabRow();
	$zone=new Zone();
	$DC=new DataCenter();
	
	$zoneList=$zone->GetZoneList();
	$formpatch="";
	$status="";

	if(isset($_POST['action']) && $_POST['action']=='Delete'){
		$cabrow->CabRowID=$_POST['cabrowid'];
		$cabrow->DeleteCabRow();
		header('Location: cabrow.php');
		exit;
	}

	if(isset($_REQUEST["cabrowid"])) {
		$cabrow->CabRowID=(isset($_POST['cabrowid'])?$_POST['cabrowid']:$_GET['cabrowid']);
		$cabrow->GetCabRow();
		
		if(isset($_POST["action"]) && (($_POST["action"]=="Create") || ($_POST["action"]=="Update"))){
			$cabrow->Name=$_POST["name"];
			$cabrow->DataCenterID=$_POST["datacenterid"];
			$cabrow->ZoneID=$_POST["zoneid"];
			
			if($_POST["action"]=="Create"){
				$cabrow->CreateCabRow();
			}else{
				if ($cabrow->UpdateCabRow())
					$status=__("Updated");
			}
		}
		$formpatch="?cabrowid={$_REQUEST['cabrowid']}";
	}

	$dcList=$DC->GetDCList();
	$idcList=$DC->GetDCList(true); //indexed by id
	$izoneList=$zone->GetZoneList(true); //indexed by id
	$cabrowList=$cabrow->GetCabRowList();

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Rows of Cabinets</title>

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
  <script type="text/javascript" src="scripts/common.js?v<?php echo filemtime('scripts/common.js');?>"></script>


<script type="text/javascript">
	$(document).ready(function() {
		// Enforce datacenter <-> zone relationship
		$('#zoneid').on('change',function(){
			if(this.value>0){
				$('#datacenterid').attr('disabled','');
				$('#datacenterid').val($(this.options[this.selectedIndex]).data('dcid'));
			}else{
				$('#datacenterid').removeAttr('disabled');
			}
		}).change();

		$("#cabrowid").combobox();
		$("#datacenterid").combobox();
		$("#zoneid").combobox();

		// Input options that are disabled don't submit
		$('.caption > button').on('click',function(e){
			$('#datacenterid').removeAttr('disabled');
		});

		// Don't attempt to open the datacenter tree until it is loaded
		function opentree(){
			if($('#datacenters .bullet').length==0){
				setTimeout(function(){
					opentree();
				},500);
			}else{
				expandToItem('datacenters','cr<?php echo $cabrow->CabRowID;?>');
			}
		}
		opentree();

		// Delete container confirmation dialog
		$('button[value="Delete"]').click(function(e){
			var form=$(this).parents('form');
			var btn=$(this);
<?php
print "		var dialog=$('<div>').prop('title','".__("Verify Delete Row")."').html('<p><span class=\"ui-icon ui-icon-alert\" style=\"float:left; margin:0 7px 20px 0;\"></span><span></span></p>');";
print "		dialog.find('span + span').html('".__("This Row will be deleted and there is no undo.  Assets within the row will remain as members of the Data Center.")."<br>".__("Are you sure?")."');"; 
?>
			dialog.dialog({
				resizable: false,
				modal: true,
				dialogClass: "no-close",
				buttons: {
<?php echo '				',__("Yes"),': function(){'; ?>
						$(this).dialog("destroy");
						form.append('<input type="hidden" name="'+btn.attr("name")+'" value="'+btn.val()+'">');
						form.submit();
					},
<?php echo '				',__("No"),': function(){'; ?>
						$(this).dialog("destroy");
					}
				}
			});
		});
	});
</script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page cabrow">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<h3>',$status,'</h3>
<div class="center"><div>
<form action="',$_SERVER["SCRIPT_NAME"].$formpatch,'" method="POST">
<div class="table">
<div>
   <div><label for="cabrowid">',__("Row"),'</label></div>
   <div><input type="hidden" name="action" value="query">
   <select name="cabrowid" id="cabrowid" onChange="form.submit()">
   <option value=0>',__("New Row"),'</option>';

	foreach($cabrowList as $cabrowRow){
		$selected=($cabrow->CabRowID==$cabrowRow->CabRowID)?" selected":"";
		// Suppressing errors because there shouldn't be any value for 0 in the dc and zone lists
		@print "<option value=\"$cabrowRow->CabRowID\"$selected>[{$idcList[$cabrowRow->DataCenterID]->Name}/{$izoneList[$cabrowRow->ZoneID]->Description}] $cabrowRow->Name</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="name">',__("Name"),'</label></div>
   <div><input type="text" size="50" name="name" id="name" value="',$cabrow->Name,'"></div>
</div>
<div>
   <div><label for="datacenterid">',__("Data Center"),'</label></div>
   <div><select name="datacenterid" id="datacenterid">
		<option value=0></option>';

	foreach($dcList as $dc){
		$selected=($cabrow->DataCenterID==$dc->DataCenterID)?" selected":"";
		print "<option value=\"$dc->DataCenterID\"$selected>$dc->Name</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="zoneid">',__("Data Center Zone"),'</label></div>
   <div><select name="zoneid" id="zoneid">
		<option value=0></option>';

	foreach($zoneList as $zoneRow){
		$selected=($cabrow->ZoneID==$zoneRow->ZoneID)?" selected":"";
		print "<option data-dcid=$zoneRow->DataCenterID value=\"$zoneRow->ZoneID\"$selected>[{$idcList[$zoneRow->DataCenterID]->Name}] $zoneRow->Description</option>\n";
	}

echo '	</select></div>
</div>';

	if($cabrow->CabRowID==0){
		echo '<div><div>&nbsp;</div><div></div></div>
		<div class="caption"><button type="submit" name="action" value="Create">',__("Create"),'</button></div>';
	}
	else{
		echo '<div><div>&nbsp;</div><div></div></div>
		<div class="caption"><button type="submit" name="action" value="Update">',__("Update"),'</button>
		<button type="button" name="action" value="Delete">',__("Delete"),'</button></div>';

	}
?>
</div><!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
