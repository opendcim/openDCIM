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
		$template->FrontPictureFile=$_POST['FrontPictureFile'];
        $template->RearPictureFile=$_POST['RearPictureFile'];
        $template->ChassisSlots=($template->DeviceType=="Chassis")?$_POST['ChassisSlots']:0;
        $template->RearChassisSlots=($template->DeviceType=="Chassis")?$_POST['RearChassisSlots']:0;
        
		switch($_POST['action']){
			case 'Create':
				$status=(!$template->CreateTemplate())?__('An error has occured, template not created'):'';
				break;
			case 'Update':
				$status=($template->UpdateTemplate())?__('Updated'):__('Error');
				if ($status==__('Updated')){
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
						$status=($slot->CreateSlot())?$status:__('Error');
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
						$status=($slot->CreateSlot())?$status:__('Error');
					}
				}
				break;
			case 'Device':
				// someone will inevitibly try to update the template values and just click 
				// update devices so make sure the template will update before trying to 
				// update the device values to match the template
				$status=($template->UpdateTemplate())?__('Updated'):__('Error');
				if ($status==__('Updated')){
					//Update slots
					$template->DeleteSlots();
					for ($i=1; $i<=$template->ChassisSlots;$i++){
						$slot=new Slot();
						$slot->TemplateID=$template->TemplateID;
						$slot->Position=$i;
						$slot->BackSide=False;
						$slot->X=$_POST["XF".$i];
						$slot->Y=$_POST["YF".$i];
						$slot->W=$_POST["WF".$i];
						$slot->H=$_POST["HF".$i];
						$status=($slot->CreateSlot())?$status:__('Error');
					}
					for ($i=1; $i<=$template->RearChassisSlots;$i++){
						$slot=new Slot();
						$slot->TemplateID=$template->TemplateID;
						$slot->Position=$i;
						$slot->BackSide=True;
						$slot->X=$_POST["XR".$i];
						$slot->Y=$_POST["YR".$i];
						$slot->W=$_POST["WR".$i];
						$slot->H=$_POST["HR".$i];
						$status=($slot->CreateSlot())?$status:__('Error');
					}
				}
				if($status==__('Updated')){
					$status=($template->UpdateDevices())?__('Updated'):__('Error');
				}
				break;
			default:
				// do nothing if the value isn't one we expect
		}
	}

	$templateList=$template->GetTemplateList();
	$ManufacturerList=$manufacturer->GetManufacturerList();
	
	$imageselect='<div id="preview"></div><div id="filelist">';

	$path='./pictures';
	$dir=scandir($path);
	foreach($dir as $i => $f){
		if(is_file($path.DIRECTORY_SEPARATOR.$f)){
			$imageinfo=getimagesize($path.DIRECTORY_SEPARATOR.$f);
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

		$('#FrontPictureFile').click(function(){
			$("#imageselection").dialog({
				resizable: false,
				height:500,
				width: 600,
				modal: true,
				buttons: {
<?php echo '					',__("Select"),': function() {'; ?>
						if($('#imageselection #preview').attr('image')!=""){
							$('#FrontPictureFile').val($('#imageselection #preview').attr('image'));
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
				if($('#FrontPictureFile').val()==$(this).text()){
					$(this).click();
				}
			});
		});
		
		$('#RearPictureFile').click(function(){
			$("#imageselection").dialog({
				resizable: false,
				height:500,
				width: 600,
				modal: true,
				buttons: {
<?php echo '					',__("Select"),': function() {'; ?>
						if($('#imageselection #preview').attr('image')!=""){
							$('#RearPictureFile').val($('#imageselection #preview').attr('image'));
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
				if($('#RearPictureFile').val()==$(this).text()){
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

		$('#ChassisSlots').change(function(){
			if($('#ChassisSlots').val()+$('#RearChassisSlots').val()==0 && $('#FrontPictureFile').val().length>0){
				$('#DivSlots').css({'display': 'none'});
			}
		});

		$('#RearChassisSlots').change(function(){
			if($('#ChassisSlots').val()+$('#RearChassisSlots').val()==0 && $('#RearPictureFile').val().length>0){
				$('#DivSlots').css({'display': 'none'});
			}
		});

		/*
		var lbsettings = {
				containerBorderSize: 10,
				txtImage:'<?php print __("Slot"); ?>',
				txtOf:'<?php print __("of"); ?>'}
		*/
<?php 
if (isset($template->ChassisSlots) && $template->ChassisSlots>0){
?>		
		//init individual lightBoxes
		for (var i=1; i<=<?php print $template->ChassisSlots; ?>;i++){
			$('a[id=F'+i+']').lightBox();
		}
<?php
}
if (isset($template->RearChassisSlots) && $template->RearChassisSlots>0){
?>
		for (var i=1; i<=<?php print $template->RearChassisSlots; ?>;i++){
			$('a[id=R'+i+']').lightBox();
		}
<?php
}
?>
	});

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
   <div><input type="text" name="ChassisSlots" id="ChassisSlots" value="',$template->ChassisSlots,'"></div>
</div>
<div id="DivRearChassisSlots" style="display: ',(($template->DeviceType=="Chassis")?'table-row':'none'),';">
   <div><label for="RearChassisSlots">',__("Rear Chassis Slots"),'</label></div>
   <div><input type="text" name="RearChassisSlots" id="RearChassisSlots" value="',$template->RearChassisSlots,'"></div>
</div>';
echo '<div id="DivSlots" style="display: ',(($template->ChassisSlots+$template->RearChassisSlots>0)?'table-row':'none'),';">
	<div><label for="Slots">',__("Coordinates of Slots"),'</label></div>';
print "<div><table class='coordinates' style='margin-top: 3px; margin-left: 0px; margin-bottom: 3px;'>";
print "<tr><th>".__("Position")."</th><th>".__("X")."</th><th>".__("Y")."</th><th>".__("W")."</th><th>".__("H")."</th><th></th></tr>\n";
for ($i=1; $i<=$template->ChassisSlots;$i++){
	$slot=new Slot();
	$slot->TemplateID=$template->TemplateID;
	$slot->Position=$i;
	$slot->BackSide=False;
	$slot->GetSlot();
	print "<td>".$i." ".__("Front")."</td>\n"; 
	print "<td><input type='text' name='XF".$i."' id='XF".$i."' value='".$slot->X."' size='4'></td>\n"; 
	print "<td><input type='text' name='YF".$i."' id='YF".$i."' value='".$slot->Y."' size='4'></td>\n"; 
	print "<td><input type='text' name='WF".$i."' id='WF".$i."' value='".$slot->W."' size='4'></td>\n"; 
	print "<td><input type='text' name='HF".$i."' id='HF".$i."' value='".$slot->H."' size='4'></td>\n"; 
	print "<td><a id='F".$i."' href='pictures/".$template->FrontPictureFile."' title='".__("Edit coordinates of front slot")." ".$i."' onClick='show(\"F".$i."\")' >".__("Edit")."</a></td>\n</tr>\n"; 
}
for ($i=1; $i<=$template->RearChassisSlots;$i++){
	$slot=new Slot();
	$slot->TemplateID=$template->TemplateID;
	$slot->Position=$i;
	$slot->BackSide=True;
	$slot->GetSlot();
	print "<td>".$i." ".__("Rear")."</td>\n"; 
	print "<td><input type='text' name='XR".$i."' id='XR".$i."' value='".$slot->X."' size='4'></td>\n"; 
	print "<td><input type='text' name='YR".$i."' id='YR".$i."' value='".$slot->Y."' size='4'></td>\n"; 
	print "<td><input type='text' name='WR".$i."' id='WR".$i."' value='".$slot->W."' size='4'></td>\n"; 
	print "<td><input type='text' name='HR".$i."' id='HR".$i."' value='".$slot->H."' size='4'></td>\n";
	print "<td><a id='R".$i."' href='pictures/".$template->RearPictureFile."' title='".__("Edit coordinates of rear slot")." ".$i."' onClick='show(\"R".$i."\")' >".__("Edit")."</a></td>\n</tr>\n"; 
}

print "</table></div>";
echo '</div>
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
</div></div><!-- END div.center -->
</div> <!-- END div.templatemaker-->

<?php 
echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>';

echo '<div id="imageselection" title="Image file selector">
	',$imageselect,'
</div>';
?>

</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
