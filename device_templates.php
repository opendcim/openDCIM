<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Data Center Device Templates");

	if((isset($_POST['getslots']) || isset($_POST['getports']) || isset($_POST['getpowerports'])) && isset($_POST['templateid'])){
		$returndata=array();
		if(isset($_POST['getports']) || isset($_POST['getpowerports'])){
			$tport=(isset($_POST['getports']))?new TemplatePorts():new TemplatePowerPorts();
			$tport->TemplateID=$_POST['templateid'];
			$returndata=$tport->GetPorts();
		}else{
			$returndata=Slot::GetAll($_POST['templateid']);
		}

		header('Content-Type: application/json');
		echo json_encode($returndata);  
		exit;
	}
	// Get list of color codes
	if(isset($_GET['cc'])){
		header('Content-Type: application/json');
		echo json_encode(ColorCoding::GetCodeList());
		exit;
	}
	// Get list of media types
	if(isset($_GET['mt'])){
		header('Content-Type: application/json');
		echo json_encode(MediaTypes::GetMediaTypeList());
		exit;
	}

	if(!$person->WriteAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$template=new DeviceTemplate();
	$manufacturer=new Manufacturer();

	if(isset($_POST['deleteme'])){
		$template->TemplateID=$_POST['templateid'];
		if($template->GetTemplateByID()){
			// First deal with the case that we are transferring
			if($template->TemplateID!=$_POST['transferid'] && $_POST['transferid']==0){
				// We should do this in bulk,  this has potential to be a real time sink
				foreach(Device::GetDevicesByTemplate($template->TemplateID) as $dev){
					$dev->TemplateID->$_POST['transferid'];
					$dev->UpdateDevice();
				}
			}
			// Transfers are done, delete this shit
			$template->DeleteTemplate();
		}
		echo '1';
		exit;
	}

	$status='';

	if(isset($_FILES['templateFile']) && $_FILES['templateFile']['error']==0 && $_FILES['templateFile']['type']='text/xml'){
		$result=$template->ImportTemplate($_FILES['templateFile']['tmp_name']);
		$status=($result["status"]=="")?__("Template File Imported"):$result["status"].'<a id="import_err" style="margin-left: 1em;" title="'.__("View errors").'" href="#"><img src="images/info.png"></a>';
	}
	
	if(isset($_REQUEST['templateid']) && $_REQUEST['templateid'] >0){
		//get template
		$template->TemplateID=$_REQUEST['templateid'];
		$template->GetTemplateByID();
		$deviceList = Device::GetDevicesByTemplate( $template->TemplateID );
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
				$status=($slot->CreateSlot())?$status:__("Error updating front slots");
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
				$status=($slot->CreateSlot())?$status:__("Error updating rear slots");
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
				$status=($tport->CreatePort())?$status:__("Error updating template ports");
			}
			$template->DeletePowerPorts();
			//update template power connections
			for ($i=1; $i<=$template->PSCount;$i++){
				$tport=new TemplatePowerPorts();
				$tport->TemplateID=$template->TemplateID;
				$tport->PortNumber=$i;
				$tport->Label=isset($_POST["powerlabel".$i])?$_POST["powerlabel".$i]:"";
				$tport->PortNotes=isset($_POST["powerportnotes".$i])?$_POST["powerportnotes".$i]:"";
				$status=($tport->CreatePort())?$status:__("Error updating template power connections");
			}



			return $status;
		}

		function UpdateCustomValues($template,$status) {
			/* Note: if a user changes the value or required status of an attribute that is not "all devices" and does not
				enable it on the device_templates screen, it won't update here. it is a weird ui experience, but it
				seems like the proper functionality since we aren't saving data for anything that isn't enabled */
			$dcaList=DeviceCustomAttribute::GetDeviceCustomAttributeList();
			$template->DeleteCustomValues();
			foreach($_POST["tdca"] as $dcaid=>$currentdca) {
				if((isset($currentdca["enabled"]) && $currentdca["enabled"]==="on")) {
					$insertval = '';
					$requiredval = 0;
					if(isset($currentdca["value"]) && trim($currentdca["value"] != '')) {
						$insertval = trim($currentdca["value"]);
					}
					if(isset($currentdca["required"]) && $currentdca["required"] == "on") {
						$requiredval = 1;
					}
					$status=($template->InsertCustomValue($dcaid, $insertval,$requiredval))?$status:__('Error updating device template custom values');
				} elseif(array_key_exists($dcaid, $dcaList) && $dcaList[$dcaid]->AllDevices==1) {
				/* since the enabled checkbox for attributes marked as "all devices" is disabled, it doesn't get passed in with the form,
					so parse through and if the value or required status are different than the defaults, add a row for them as well. this
					helps keep the table clean of a bunch of default values too */
					$insertval = $dcaList[$dcaid]->DefaultValue;
					$requiredval = $dcaList[$dcaid]->Required;
					// don't check if the value is empty - this lets us overwrite a default value from the config page with empty here
					if(isset($currentdca["value"])) {
						$insertval = trim($currentdca["value"]);
					}
					if(isset($currentdca["required"]) && $currentdca["required"] == "on") {
						$requiredval = 1;
					}
					if(($insertval != $dcaList[$dcaid]->DefaultValue) || ($requiredval != $dcaList[$dcaid]->Required)) {
						$status=($template->InsertCustomValue($dcaid, $insertval, $requiredval))?$status:__('Error updating device template custom values');
					}
				}
			}
			return $status;
		}

		switch($_POST['action']){
			case 'Create':
				if($template->CreateTemplate()){
					$oldstatus = $status;
					$status=UpdateSlotsPorts($template,$status);
					if($oldstatus == $status) {
						$status=UpdateCustomValues($template,$status);
					}
				}else{
					$status=__("An error has occured, template not created");
				}
				break;
			case 'Update':
				$status=($template->UpdateTemplate())?__("Updated"):__("Error updating template");
				if ($status==__("Updated")){
					$status=UpdateSlotsPorts($template,$status);
				}
				if ($status==__("Updated")){
					$status=UpdateCustomValues($template,$status);
				}
				break;
			case 'Device':
				// someone will inevitibly try to update the template values and just click
				// update devices so make sure the template will update before trying to
				// update the device values to match the template
				$status=($template->UpdateTemplate())?__("Updated"):__("Error updating template");
				if ($status==__("Updated")){
					$status=UpdateSlotsPorts($template,$status);
				}
				if ($status==__("Updated")){
					$status=UpdateCustomValues($template,$status);
				}
				if($status==__("Updated")){
					$status=($template->UpdateDevices())?__("Updated"):__("Error updating devices");
				}
				break;
			case 'Export':
				//update template before export
				$status=($template->UpdateTemplate())?__("Updated"):__("Error");
				if ($status==__("Updated")){
					$status=UpdateSlotsPorts($template,$status);
				}
				if ($status==__("Updated")){
					$status=UpdateCustomValues($template,$status);
				}
				if($status==__("Updated")){
					$status=($template->ExportTemplate())?__("Exported"):__("Error");
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
	$dcaList=DeviceCustomAttribute::GetDeviceCustomAttributeList();

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
  <script type="text/javascript" src="scripts/common.js"></script>
  <script type="text/javascript">
	$(document).ready(function(){
		var oModel=$('#model').val();
		var chgmsg="<?php echo __("This value must be different than"); ?>"+" "+oModel;
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
			$('button[name="action"]').val('Create').text("<?php echo __("Create");?>");
			$('#model').trigger('change');
			$('#device, #clone').remove();
		});

		$('#FrontPictureFile,#RearPictureFile').click(function(){
			var input=this;
			$("#imageselection").dialog({
				resizable: false,
				height:500,
				width: 670,
				modal: true,
				buttons: {
<?php echo '					',__("Select"),': function() {'; ?>
						if($('#imageselection #preview').attr('image')!=""){
							$(input).val($('#imageselection #preview').attr('image')).trigger('change');
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

		FetchSlots();
		TemplateButtons();
		$('.templatemaker input[id*=Chassis] + button').each(function(){
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

		buildportstable();
		$('.templatemaker input#numports + button').each(function(){
			$(this).on('click',function(){
				// Fill in the ports table
				buildportstable();

				// Open the dialog
				PortsPoopup();
			});
		});

		buildpowerportstable();
		$('.templatemaker input#pscount + button').each(function(){
			$(this).on('click',function(){
				// Fill in the ports table
				buildpowerportstable();

				// Open the dialog
				PowerPortsPoopup();
			});
		});

		$('#FrontPictureFile,#RearPictureFile,#ChassisSlots,#RearChassisSlots,#numports,#pscount').on('change keyup keydown', function(){ TemplateButtons(); });


	$('#delete').on('click',function(){
		// setup shit we might reuse
		var dlg_content=$('<div>');
		var dlg_select=$('<select>').append($('<option>').text('No').val('false')).append($('<option>').text('Yes').val('true'));
		var dlg_transfer=$('<div>');

		// make the dialog
		dlg_content.append($('<p>').append("There is no undo").addClass('warning'));
		dlg_content.append($('<label>').html("Would you like to transfer all devices using this template to another template?&nbsp;&nbsp;"));
		dlg_content.append(dlg_select);
		dlg_content.append(dlg_transfer);

		var templateid2;

		// logic for dealing someone saying yes they want to swap to another template
		dlg_select.on('change',function(){
			// Clone the existing device template list
			templateid2=$('#templateid').clone().prop('id','templateid2').removeAttr('onchange');
			// Remove the 'New Template' option
			templateid2.find('option:first-child').remove();

			if(eval(this.value)){
				dlg_transfer.append('<br><br>');
				dlg_transfer.append($('<label>').prop('for','templateid2').html("Transfer all devices using this template to:&nbsp;&nbsp;"));
				dlg_transfer.append(templateid2);
			}else{
				dlg_transfer.html('');
			}

		});

		var dialog=$('<div>').attr('title',"Are you sure you want to delete this template?").html(dlg_content);
			dialog.dialog({
				closeOnEscape: false,
				minHeight: 250,
				width: 840,
				modal: true,
				resizable: false,
				position: { my: "center", at: "top", of: window },
				show: { effect: "blind", duration: 800 },
				beforeClose: function(event,ui){
				},
				buttons: {
					Yes: function(){
						$.post('',{templateid: $('#templateid').val(), transferid: ((typeof templateid2=='undefined')?0:templateid2.val()), deleteme: ''}).done(function(data){
							if(data.trim()==1){
								dialog.dialog("destroy");
								window.location=window.location.href;
							}else{
								alert("something is broken");
							}
						});
					},
					No: function(){
						$(this).dialog("destroy");
					}
				}
			});
	});


	});/* END of $(document).ready function() */
</script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<div class="templatemaker">
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
		$selected=($template->TemplateID==$templateRow->TemplateID)?" selected":"";
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
   <div><label for="pscount">',__("No. Power Connections"),'</label></div>
   <div><input type="text" name="pscount" id="pscount" value="',$template->PSCount,'"><button type="button">',__("Edit Ports"),'</button></div>
</div>
<div>
   <div><label for="numports">',__("No. Ports"),'</label></div>
   <div><input type="text" name="numports" id="numports" value="',$template->NumPorts,'"><button type="button">',__("Edit Ports"),'</button></div>
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
foreach($dcaList as $dca) {
	$templatedcaChecked = "";
	$templatedcaDisabled = "";
	$templatedcaValue = "";
	$templatedcaRequired = "";
	if($dca->AllDevices) {
		$templatedcaChecked=" checked";
		$templatedcaDisabled=" disabled";
	}
	//TODO the ui seems weird if the default value gets set and this row isn't enabled
	if($dca->DefaultValue != '') {
		$templatedcaValue=$dca->DefaultValue;
	}
	if($dca->Required == 1) {
		$templatedcaRequired = " checked";
	}
	// this check goes after the all devices check so that the template value overwrites any system-wide default value
	if(isset($template->CustomValues) && array_key_exists($dca->AttributeID, $template->CustomValues)){
		$templatedcaChecked=" checked";
		$templatedcaValue=$template->CustomValues[$dca->AttributeID]["value"];
		$templatedcaRequired=($template->CustomValues[$dca->AttributeID]["required"]==1)?" checked":"";
	}
	echo '<div>
		<div>',$dca->Label,'</div>';
	echo '<div>';
	if($dca->AttributeType=="checkbox") {
		$checked="";
		if($templatedcaValue=="1" || $templatedcaValue=="on"){
			$checked=" checked";
		}
		echo __("Default:").' <input type="checkbox" name="tdca[',$dca->AttributeID,'][value]" ',$checked,'>';
	} else {
		$validation="";
		if($templatedcaChecked != "" && $dca->AttributeType!="string" && $dca->AttributeType!="checked") {
			$validation=' class="validate[custom['.$dca->AttributeType.']]"';
		}
		echo '<input type="text" name="tdca[',$dca->AttributeID,'][value]"',$validation,' id="tdca[',$dca->AttributeID,'][value]" value="',$templatedcaValue,'">';
		echo '<input type="button" name=tdca[',$dca->AttributeID,'][revert]" id="tdca[',$dca->AttributeID,'][revert]" value="'.__("Revert").'" title="'.__("Revert to default value from configuration page").'" data-val="',$dca->DefaultValue,'" data-id="',$dca->AttributeID,'">';
		echo '<script>
			$("#tdca\\\[',$dca->AttributeID,'\\\]\\\[revert\\\]").click(function(){
				var defaultVal = this.getAttribute("data-val");
				var id = this.getAttribute("data-id");
				var inputid = "tdca\\\["+id+"\\\]\\\[value\\\]";
				$("#"+inputid).val(defaultVal);
			});
			</script>';
	}
	echo __("Enabled?").'<input type="checkbox" name="tdca[',$dca->AttributeID,'][enabled]" id="tdca[',$dca->AttributeID,'][enabled]" data=',$dca->AttributeID,' title="'.__("Enabled?").'"',$templatedcaChecked,$templatedcaDisabled,'>
		'.__("Required?").'<input type="checkbox" name="tdca[',$dca->AttributeID,'][required]" title="'.__("Required?").'"',$templatedcaRequired,'>
		<input type="hidden" name="tdca[',$dca->AttributeID,'][attributetype]" id="tdca[',$dca->AttributeID,'][attributetype]" value="',$dca->AttributeType,'">
		<script>
			$("#tdca\\\[',$dca->AttributeID,'\\\]\\\[enabled\\\]").click(function(){
				var id = this.getAttribute("data");
				var typeid = "tdca\\\["+id+"\\\]\\\[attributetype\\\]";
				var inputid = "tdca\\\["+id+"\\\]\\\[value\\\]";
				var type = $("#"+typeid).val();
				if(this.checked){
					$("#"+inputid).removeClass();
					if(type!="checkbox" && type!="string"){
						$("#"+inputid).addClass("validate[custom["+type+"]]");
					}
				} else {
					$("#"+inputid).removeClass();
				}
			});
		</script>
		</div>
	</div>';
}

if ( $template->TemplateID > 0 ) {
	echo '
<div>
	<div>&nbsp;</div>
	<div>' . __("Number of Devices Using This Template:") . ' ' . sizeof( $deviceList ) . '</div>
</div>';
}
	echo '
<div>
   <div><label for="notes">',__("Notes"),'</label></div>
   <div><textarea name="notes" id="notes" cols="40" rows="8">',$template->Notes,'</textarea></div>
</div>
<div class="caption">';

	if($template->TemplateID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update Template"),'</button><button type="button" id="clone">',__("Clone Template"),'</button><button id="delete" type="button">',__("Delete Template"),'</button><button type="submit" name="action" value="Device" id="device">',__("Update Devices"),'</button><button type="submit" name="action" value="Export">',__("Export"),'</button>';
	}else{
		echo '	 <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
	echo '<button type="button" name="importButton" id="importButton" value="Import">',__("Import"),'</button>';
?>
</div>
</div><!-- END div.table -->

<div id="hiddenports">
</div>

<div id="hiddenpowerports">
</div>

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
<div id="dlg_importfile" style="display:none;" title="<?php echo __("Import Template From File");?>">  
	<br>
	<form enctype="multipart/form-data" name="frmImport" id="frmImport" method="POST">
		<input type="file" size="60" id="templateFile" name="templateFile" />
	</form>  
</div>
<!-- end dialog: importFile -->  
<!-- dialog: import_err -->  
<div id="dlg_import_err" style="display:none;" title="<?php echo __("Import log");?>">  
<?php 	
if (isset($result["log"])){
	print '<ul style="list-style-type:disc; padding: 5px;">';
	foreach($result["log"] as $logline){
		print "<li style=\"padding: 5px;\">$logline</li>";
	}
	print "</ul>";
}
?>
</div>
<!-- end dialog: importFile -->  
</body>
</html>
