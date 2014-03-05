<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	if(isset($_POST['getslots']) && isset($_POST['templateid'])){
		$slots=Slot::GetAll($_POST['templateid']);

		header('Content-Type: application/json');
		echo json_encode($slots);  
		exit;
	}

	if(!$user->WriteAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$template=new DeviceTemplate();
	$manufacturer=new Manufacturer();

	$status='';

	if(isset($_FILES['templateFile']) && $_FILES['templateFile']['error']==0 && $_FILES['templateFile']['type']='text/xml'){
		$result=$template->ImportTemplate($_FILES['templateFile']['tmp_name']);
		$status=($result["status"]=="")?__("Template File Imported"):$result["status"].'<a id="import_err" style="margin-left: 1em;" title="'.__("View errors").'" href="#"><img src="images/info.png"></a>';
	}
	
	if(isset($_REQUEST['templateid']) && $_REQUEST['templateid'] >0){
		//get template
		$template->TemplateID=$_REQUEST['templateid'];
		$template->GetTemplateByID();
	}
	
	if(isset($_POST['action'])){
		$template->ManufacturerID=$_POST['manufacturerid'];
		$template->Model=transform($_POST['model']);
		$template->Height=$_POST['height'];
		$template->Weight=$_POST['weight'];
		$template->Wattage=$_POST['wattage'];
		$template->DeviceType=$_POST['devicetype'];
		$template->PSCount=$_POST['pscount'];
		$template->NumPorts=$_POST['numports'];
		$template->Notes=trim($_POST['notes']);
		$template->Notes=($template->Notes=="<br>")?"":$template->Notes;
		$template->FrontPictureFile=$_POST['FrontPictureFile'];
		$template->RearPictureFile=$_POST['RearPictureFile'];
		$template->ChassisSlots=($template->DeviceType=="Chassis")?$_POST['ChassisSlots']:0;
		$template->RearChassisSlots=($template->DeviceType=="Chassis")?$_POST['RearChassisSlots']:0;
        
		function UpdateSlotsPorts($template,$status){
			//Update slots
			$template->DeleteSlots();
			for ($i=1; $i<=$template->ChassisSlots;$i++){
				$slot=new Slot();
				$slot->TemplateID=$template->TemplateID;
				$slot->Position=$i;
				$slot->BackSide=False;
				$slot->X=isset($_POST["XF".$i])?$_POST["XF".$i]:0;
				$slot->Y=isset($_POST["YF".$i])?$_POST["YF".$i]:0;
				$slot->W=isset($_POST["WF".$i])?$_POST["WF".$i]:0;
				$slot->H=isset($_POST["HF".$i])?$_POST["HF".$i]:0;
				$status=($slot->CreateSlot())?$status:__('Error updating front slots');
			}
			for ($i=1; $i<=$template->RearChassisSlots;$i++){
				$slot=new Slot();
				$slot->TemplateID=$template->TemplateID;
				$slot->Position=$i;
				$slot->BackSide=True;
				$slot->X=isset($_POST["XR".$i])?$_POST["XR".$i]:0;
				$slot->Y=isset($_POST["YR".$i])?$_POST["YR".$i]:0;
				$slot->W=isset($_POST["WR".$i])?$_POST["WR".$i]:0;
				$slot->H=isset($_POST["HR".$i])?$_POST["HR".$i]:0;
				$status=($slot->CreateSlot())?$status:__('Error updating rear slots');
			}
			//update template ports
			$template->DeletePorts();
			for ($i=1; $i<=$template->NumPorts;$i++){
				$tport=new TemplatePorts();
				$tport->TemplateID=$template->TemplateID;
				$tport->PortNumber=$i;
				$tport->Label=isset($_POST["label".$i])?$_POST["label".$i]:"";
				$tport->MediaID=(isset($_POST["mt".$i]) && $_POST["mt".$i]>0)?$_POST["mt".$i]:0;
				$tport->ColorID=(isset($_POST["cc".$i]) && $_POST["cc".$i]>0)?$_POST["cc".$i]:0;
				$tport->PortNotes=isset($_POST["portnotes".$i])?$_POST["portnotes".$i]:"";
				$status=($tport->CreatePort())?$status:__('Error updating template ports');
			}

			return $status;
		}

		switch($_POST['action']){
			case 'Create':
				$status=(!$template->CreateTemplate())?__('An error has occured, template not created'):'';
				break;
			case 'Update':
				$status=($template->UpdateTemplate())?__('Updated'):__('Error updating template');
				if ($status==__('Updated')){
					$status=UpdateSlotsPorts($template,$status);
				}
				break;
			case 'Device':
				// someone will inevitibly try to update the template values and just click 
				// update devices so make sure the template will update before trying to 
				// update the device values to match the template
				$status=($template->UpdateTemplate())?__('Updated'):__('Error updating template');
				if ($status==__('Updated')){
					$status=UpdateSlotsPorts($template,$status);
				}
				if($status==__('Updated')){
					$status=($template->UpdateDevices())?__('Updated'):__('Error updating devices');
				}
				break;
			case 'Export':
				//update template before export
				$status=($template->UpdateTemplate())?__('Updated'):__('Error');
				if ($status==__('Updated')){
					$status=UpdateSlotsPorts($template,$status);
				}
				if($status==__('Updated')){
					$status=($template->ExportTemplate())?__('Exported'):__('Error');
				}
				break;
			default:
				// do nothing if the value isn't one we expect
		}
	}

	$templateList=$template->GetTemplateList();
	$ManufacturerList=$manufacturer->GetManufacturerList();
	$mtList=MediaTypes::GetMediaTypeList();
	$ccList=ColorCoding::GetCodeList();
			
	$imageselect='<div id="preview"></div><div id="filelist">';

	$path='./pictures';
	$dir=scandir($path);
	foreach($dir as $i => $f){
		if(is_file($path.DIRECTORY_SEPARATOR.$f)){
			// Suppress the getimagesize because it will error out on non-image files
			@$imageinfo=getimagesize($path.DIRECTORY_SEPARATOR.$f);
			if(preg_match('/^image/i', $imageinfo['mime'])){
				$imageselect.="<span>$f</span>\n";
			}
		}
	}
	$imageselect.="</div>";
	
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Device Class Templates</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/imgareaselect-default.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <link rel="stylesheet" href="css/jHtmlArea.css" type="text/css">
  <link rel="stylesheet" type="text/css" href="css/jquery.lightbox.css" media="screen" />
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript" src="scripts/jHtmlArea-0.8.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.textext.js"></script>
  <script type="text/javascript" src="scripts/jquery.imgareaselect.pack.js"></script>
  <script type="text/javascript" src="scripts/jquery.lightbox.min.js"></script>
  <script type="text/javascript" src="scripts/common.js"></script>
  <script type="text/javascript">
	$(document).ready(function(){
		var oModel=$('#model').val();
		var chgmsg='<?php echo __('This value must be different than'); ?>'+' '+oModel;
		$('#deviceform').validationEngine();
		$('#model').change(function(){
			if($('#model').val()==oModel){
				setTimeout(function(){
					$('#model').validationEngine('showPrompt',chgmsg,'','',true);
				},500);
			}
		});
        
		$('#clone').click(function(){
			$('#templateid').val(0);
			$('button[name="action"]').val('Create').text('<?php echo __('Create');?>');
			$('#model').trigger('change');
			$('#device, #clone').remove();
		});

		$('#FrontPictureFile,#RearPictureFile').click(function(){
			var input=this;
			$("#imageselection").dialog({
				resizable: false,
				height:500,
				width: 600,
				modal: true,
				buttons: {
<?php echo '					',__("Select"),': function() {'; ?>
						if($('#imageselection #preview').attr('image')!=""){
							$(input).val($('#imageselection #preview').attr('image'));
						}
						$(this).dialog("close");
					}
				}
			});
			$("#imageselection span").each(function(){
				var preview=$('#imageselection #preview');
				$(this).click(function(){
					preview.css({'border-width': '5px', 'width': '380px', 'height': '380px'});
					preview.html('<img src="pictures/'+$(this).text()+'" alt="preview">').attr('image',$(this).text());
					preview.children('img').load(function(){
						var topmargin=0;
						var leftmargin=0;
						if($(this).height()<$(this).width()){
							$(this).width(preview.innerHeight());
							$(this).css({'max-width': preview.innerWidth()+'px'});
							topmargin=Math.floor((preview.innerHeight()-$(this).height())/2);
						}else{
							$(this).height(preview.innerHeight());
							$(this).css({'max-height': preview.innerWidth()+'px'});
							leftmargin=Math.floor((preview.innerWidth()-$(this).width())/2);
						}
						$(this).css({'margin-top': topmargin+'px', 'margin-left': leftmargin+'px'});
					});
					$("#imageselection span").each(function(){
						$(this).removeAttr('style');
					});
					$(this).css('border','1px dotted black')
				});
				if($(input).val()==$(this).text()){
					$(this).click();
				}
			});
		});  
		

		$('#devicetype').change(function(){
			if($('#devicetype').val()=="Chassis"){
				$('#DivChassisSlots').css({'display': 'table-row'});
				$('#DivRearChassisSlots').css({'display': 'table-row'});
			}else{
				$('#DivChassisSlots').css({'display': 'none'});
				$('#DivRearChassisSlots').css({'display': 'none'});
			}
		});

		$( "#importButton" ).click(function() {
			$("#dlg_importfile").dialog({
				resizable: false,
				width: 400,
				height: 200,
				modal: true,					
				buttons: {	
					<?php echo __("Import");?>: function() {  			
						//  Llamamos al Formulario 
						$('#frmImport').submit();
					},
					<?php echo __("Cancel");?>: function() {  							     				       
					    $("#dlg_importfile").dialog("close");
					}
				}
			});
		});

		$( "#import_err" ).click(function() {
			$("#dlg_import_err").dialog({
				resizable: false,
				width: 500,
				height: 400,
				modal: true,					
				buttons: {	
					<?php echo __("Close");?>: function() {  							     				       
					    $("#dlg_import_err").dialog("close");  								   
					}
				}
			});
		});
		
	});/* END of $(document).ready function() */

	function show(slot) {
		if ($('#lightbox-image').is(':visible')) {
	    	var x=parseInt($('#X'+slot).val());
	    	var y=parseInt($('#Y'+slot).val());
			var w=parseInt($('#W'+slot).val());
			var h=parseInt($('#H'+slot).val());
			
			$('#lightbox-image').imgAreaSelect({
				x1: x,
				x2: (x+w),
				y1: y,
				y2: (y+h),
				handles: true
				,parent: '#jquery-lightbox'
				,onSelectEnd: function (img, selection) {
					$('#X'+slot).val(selection.x1);
					$('#Y'+slot).val(selection.y1);
					$('#W'+slot).val(selection.width);
					$('#H'+slot).val(selection.height);            
		        }
	        });
	        $('#jquery-lightbox').unbind('click');
	        $('#lightbox-nav').remove();
	    }else{
	        setTimeout(function(){show(slot)}, 50);
	    }
	}

$(document).ready(function(){
	FetchSlots();
	$('.templatemaker input + button').each(function(){
		var front=($(this).prev('input').attr('id')=='ChassisSlots')?true:false;
		InsertCoordsTable(front,$(this));
		$(this).on('click',function(){
			FetchSlots();

			// Fill in the coords table
			InsertCoordsTable(front,$(this));

			// Open the dialog
			Poopup(front);
		});
	});

	$('#FrontPictureFile,#RearPictureFile,#ChassisSlots,#RearChassisSlots').on('change keyup keydown', function(){ TemplateButtons(); });

});
</script>
</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<div class="templatemaker">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',__("Data Center Device Templates"),'</h3>
<h3>',$status,'</h3>
<div class="center"><div>
<form id="deviceform" action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
	<div>
		<div><label for="templateid">',__("Template"),'</label></div>
		<div><input type="hidden"><select name="templateid" id="templateid" onChange="form.submit()">
		<option value=0>',__("New Template"),'</option>';

	foreach($templateList as $templateRow){
		$manufacturer->ManufacturerID=$templateRow->ManufacturerID;
		$manufacturer->GetManufacturerByID();
		if($template->TemplateID==$templateRow->TemplateID){$selected=" selected";}else{$selected="";}
		print "		<option value=\"$templateRow->TemplateID\"$selected>[$manufacturer->Name] $templateRow->Model</option>\n";
	}

echo '	</select></div>
</div>
<div>
	<div><label for="manufacturerid">',__("Manufacturer"),'</label></div>
	<div><select name="manufacturerid" id="manufacturerid">';

	foreach($ManufacturerList as $ManufacturerRow){
		if($template->ManufacturerID==$ManufacturerRow->ManufacturerID){$selected=" selected";}else{$selected="";}
		print "		<option value=\"$ManufacturerRow->ManufacturerID\"$selected>$ManufacturerRow->Name</option>\n";
	}

echo '    </select>    
   </div>
</div>
<div>
   <div><label for="model">',__("Model"),'</label></div>
   <div><input type="text" name="model" id="model" class="validate[required]" value="',$template->Model,'"></div>
</div>
<div>
   <div><label for="height">',__("Height"),'</label></div>
   <div><input type="text" name="height" id="height" value="',$template->Height,'"></div>
</div>
<div>
   <div><label for="weight">',__("Weight"),'</label></div>
   <div><input type="text" name="weight" id="weight" value="',$template->Weight,'"></div>
</div>
<div>
   <div><label for="wattage">',__("Wattage"),'</label></div>
   <div><input type="text" name="wattage" id="wattage" value="',$template->Wattage,'"></div>
</div>
<div>
   <div><label for="devicetype">',__("Device Type"),'</label></div>
   <div><select name="devicetype" id="devicetype">';

	foreach(array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure') as $DevType){
		if($DevType==$template->DeviceType){$selected=" selected";}else{$selected="";}
		print "		<option value=\"$DevType\"$selected>$DevType</option>\n";
	}

echo '	</select>
   </div>
</div>
<div>
   <div><label for="pscount">',__("No. Power Supplies"),'</label></div>
   <div><input type="text" name="pscount" id="pscount" value="',$template->PSCount,'"></div>
</div>
<div>
   <div><label for="numports">',__("No. Ports"),'</label></div>
   <div><input type="text" name="numports" id="numports" value="',$template->NumPorts,'"></div>
</div>
<div>
   <div><label for="FrontPictureFile">',__("Front Picture File"),'</label></div>
   <div><input type="text" name="FrontPictureFile" id="FrontPictureFile" value="',$template->FrontPictureFile,'"></div>
</div>
<div>
   <div><label for="RearPictureFile">',__("Rear Picture File"),'</label></div>
   <div><input type="text" name="RearPictureFile" id="RearPictureFile" value="',$template->RearPictureFile,'"></div>
</div>
<div id="DivChassisSlots" style="display: ',(($template->DeviceType=="Chassis")?'table-row':'none'),';">
   <div><label for="ChassisSlots">',__("Chassis Slots"),'</label></div>
   <div><input type="text" name="ChassisSlots" id="ChassisSlots" value="',$template->ChassisSlots,'"><button type="button">',__("Edit Coordinates"),'</button></div>
</div>
<div id="DivRearChassisSlots" style="display: ',(($template->DeviceType=="Chassis")?'table-row':'none'),';">
   <div><label for="RearChassisSlots">',__("Rear Chassis Slots"),'</label></div>
   <div><input type="text" name="RearChassisSlots" id="RearChassisSlots" value="',$template->RearChassisSlots,'"><button type="button">',__("Edit Coordinates"),'</button></div>
</div>';

echo '<div id="DivPorts" style="display: ',(($template->NumPorts>0)?'table-row':'none'),';">
	<div><label for="Ports">',__("Features of ports"),'</label></div>';
print "<div><table class='coordinates' style='margin-top: 3px; margin-left: 0px; margin-bottom: 3px;'>";
print "<tr><th>".__("Port Number")."</th><th>".__("Label")."</th><th>".__("Media")."</th><th>".__("Color")."</th><th>".__("Notes")."</th></tr>\n";
for ($i=1; $i<=$template->NumPorts;$i++){
	$tport=new TemplatePorts();
	$tport->TemplateID=$template->TemplateID;
	$tport->PortNumber=$i;
	$tport->GetPort();
	print "<td>".$i."</td>\n"; 
	print "<td><input type='text' name='label".$i."' id='label".$i."' value='".$tport->Label."' size='6' maxlength='40'></td>\n"; 
	print "<td><select name='mt".$i."' id='mt".$i."'>";
	print "<option value=0></option>";
	foreach($mtList as $mtRow){
		if($tport->MediaID==$mtRow->MediaID){$selected=" selected";}else{$selected="";}
		print "		<option value=\"$mtRow->MediaID\"$selected>$mtRow->MediaType</option>\n";
	}
	print "</select></td>";    
	
	print "<td><select name='cc".$i."' id='cc".$i."'>";
	print "<option value=0></option>";
	foreach($ccList as $ccRow){
		if($tport->ColorID==$ccRow->ColorID){$selected=" selected";}else{$selected="";}
		print "		<option value=\"$ccRow->ColorID\"$selected>$ccRow->Name</option>\n";
	}
	print "</select></td>";    
	print "<td><input type='text' name='portnotes".$i."' id='portnotes".$i."' value='".$tport->PortNotes."' size='20' maxlength='80'></td>\n"; 
	print "</tr>\n";
}
print "</table></div></div>";

echo '
<div>
   <div><label for="notes">',__('Notes'),'</label></div>
   <div><textarea name="notes" id="notes" cols="40" rows="8">',$template->Notes,'</textarea></div>
</div>
<div class="caption">';

	if($template->TemplateID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update Template"),'</button><button type="button" id="clone">',__("Clone Template"),'</button><button type="submit" name="action" value="Device" id="device">',__("Update Devices"),'</button><button type="submit" name="action" value="Export">',__("Export"),'</button>';
	}else{
		echo '	 <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
	echo '<button type="button" name="importButton" id="importButton" value="Import">',__("Import"),'</button>';
?>
</div>
</div><!-- END div.table -->


<div id="hiddencoords">
	<div class="table front">
		<div>
			<div>
				<div id="previewimage">
				</div>
			</div>
			<div id="coordstable">
			</div>
		</div>
	</div>
	<div class="table rear">
		<div>
			<div>
				<div id="previewimage">
				</div>
			</div>
			<div id="coordstable">
			</div>
		</div>
	</div>
</div>

</form>
</div></div><!-- END div.center -->
</div> <!-- END div.templatemaker-->

<?php 
echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>';

echo '<div id="imageselection" title="',__("Image file selector"),'">
	',$imageselect,'
</div>';
?>

</div><!-- END div.main -->
</div><!-- END div.page -->

<!-- dialog: importFile -->  
<div id='dlg_importfile' style='display:none;' title='<?php echo __("Import Template From File");?>'>  
	<br>
	<form enctype="multipart/form-data" name="frmImport" id="frmImport" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST">
		<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />			
		<input type="file" size="60" id="templateFile" name="templateFile" />
	</form>  
</div>
<!-- end dialog: importFile -->  
<!-- dialog: import_err -->  
<div id='dlg_import_err' style='display:none;' title='<?php echo __("Import log");?>'>  
<?php 	
if (isset($result["log"])){
	print "<ul style='list-style-type:disc; padding: 5px;'>";
	foreach($result["log"] as $logline){
		print "<li style='padding: 5px;'>".$logline."</li>";
	}
	print "</ul>";
}
?>
</div>
<!-- end dialog: importFile -->  

</body>
</html>
