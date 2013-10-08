<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	if(!$user->WriteAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$template=new DeviceTemplate();
	$manufacturer=new Manufacturer();

	if(isset($_REQUEST['templateid']) && $_REQUEST['templateid'] >0){
		$template->TemplateID=$_REQUEST['templateid'];
		$template->GetTemplateByID();
	}

	$status='';
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

		switch($_POST['action']){
			case 'Create':
				$status=(!$template->CreateTemplate())?__('An error has occured, template not created'):'';
				break;
			case 'Update':
				$status=($template->UpdateTemplate())?__('Updated'):__('Error');
				break;
			case 'Device':
				// someone will inevitibly try to update the template values and just click 
				// update devices so make sure the template will update before trying to 
				// update the device values to match the template
				if($template->UpdateTemplate()){
					$status=($template->UpdateDevices())?__('Updated'):__('Error');
				}else{
					$status=__('Error');
				}
				break;
			default:
				// do nothing if the value isn't one we expect
		}
	}

	$templateList=$template->GetTemplateList();
	$ManufacturerList=$manufacturer->GetManufacturerList();
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

        $('#notes').each(function(){
            $(this).before('<button type="button" id="editbtn"></button>');
            if($(this).val()!=''){
                rendernotes($('#editbtn'));
            }else{
                editnotes($('#editbtn'));
            }
        });
    
        function editnotes(button){
            button.val('preview').text('<?php echo __("Preview");?>');
            var a=button.next('div');
            button.next('div').remove();
            button.next('textarea').htmlarea({
                toolbar: [
                "link", "unlink", "image"
                ],
                css: 'css/jHtmlArea.Editor.css'
            });
            $('.jHtmlArea div iframe').height(a.innerHeight());
        }

        function rendernotes(button){
            button.val('edit').text('<?php echo __("Edit");?>');
            var w=button.next('div').outerWidth();
            var h=$('.jHtmlArea').outerHeight();
            if(h>0){
                h=h+'px';
            }else{
                h="auto";
            }
            $('#notes').htmlarea('dispose');
            button.after('<div id="preview">'+$('#notes').val()+'</div>');
            button.next('div').css({'width': w+'px', 'height' : h}).find('a').each(function(){
                $(this).attr('target', '_new');
            });
            $('#notes').html($('#notes').val()).hide(); // we still need this field to submit it with the form
            h=0; // recalculate height in case they added an image that is gonna hork the layout
            // need a slight delay here to allow the load of large images before the height calculations are done
            setTimeout(function(){
                $('#preview').find("*").each(function(){
                    h+=$(this).outerHeight();
                });
                $('#preview').height(h);
            },2000);
        }

        $('#editbtn').click(function(){
            var button=$(this);
            if($(this).val()=='edit'){
                editnotes(button);
            }else{
                rendernotes(button);
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

echo '<div class="main">
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
		if($template->ManufacturerID==$ManufacturerRow->ManufacturerID){$selected="selected";}else{$selected="";}
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
   <div><select name="devicetype" id="select">';

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
   <div><label for="notes">',__('Notes'),'</label></div>
   <div><textarea name="notes" id="notes" cols="40" rows="8">',$template->Notes,'</textarea></div>
</div>
<div class="caption">';

	if($template->TemplateID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update Template"),'</button><button type="button" id="clone">',__("Clone Template"),'</button><button type="submit" name="action" value="Device" id="device">',__("Update Devices"),'</button>';
	}else{
		echo '	 <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
?>
</div>
</div><!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
