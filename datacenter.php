<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Data Center Detail");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
	
	$status="";

	$dc=new DataCenter();
	
	// AJAX Action
	if(isset($_POST['confirmdelete']) && isset($_POST['datacenterid'])){
		// About the nuke this place from orbit
		$junkremoval=($_POST['junkremoval']=='delete')?true:false;
		$dc->DataCenterID=$_POST['datacenterid'];
		if($dc->DeleteDataCenter($junkremoval)){
			echo 'ok';
		}else{
			echo 'no';
		}
		exit;
	}
	
	if(isset($_POST['action'])&&(($_POST['action']=='Create')||($_POST['action']=='Update'))){
		$dc->DataCenterID=$_POST['datacenterid'];
		$dc->Name=trim($_POST['name']);
		$dc->SquareFootage=$_POST['squarefootage'];
		$dc->DeliveryAddress=$_POST['deliveryaddress'];
		$dc->Administrator=$_POST['administrator'];
		$dc->DrawingFileName=$_POST['drawingfilename'];
		$dc->MaxkW=$_POST['maxkw'];
		$dc->ContainerID=$_POST['container'];
		$dc->MapX=$_POST['x'];
		$dc->MapY=$_POST['y'];
		
		if($dc->Name!=""){
			if($_POST['action']=='Create'){
				$dc->CreateDataCenter();
			}else{
				$status=__("Updated");
				$dc->UpdateDataCenter();
			}
		}
	}

	if(isset($_POST['cambio_cont'])&& $_POST['cambio_cont']=='SI'){
		$dc->DataCenterID=$_POST['datacenterid'];
		$dc->Name=trim($_POST['name']);
		$dc->SquareFootage=$_POST['squarefootage'];
		$dc->DeliveryAddress=$_POST['deliveryaddress'];
		$dc->Administrator=$_POST['administrator'];
		$dc->DrawingFileName=$_POST['drawingfilename'];
		$dc->MaxkW=$_POST['maxkw'];
		$dc->ContainerID=$_POST['container'];
		if ($dc->ContainerID==0){
			$dc->MapX=0;
			$dc->MapY=0;
		}else{
			$dc->MapX=$_POST['x'];
			$dc->MapY=$_POST['y'];
		}
	}
	elseif(isset($_REQUEST['datacenterid'])&&$_REQUEST['datacenterid'] >0){
		$dc->DataCenterID=(isset($_POST['datacenterid']) ? $_POST['datacenterid'] : $_GET['datacenterid']);
		$dc->GetDataCenter();
	}
	$dcList=$dc->GetDCList();

	if ( $config->ParameterArray["mUnits"] == "english" )
		$vol = __("Square Feet");
	else
		$vol = __("Square Meters");

	$imageselect='<div id="preview"></div><div id="filelist">';

	$path='./'.$config->ParameterArray["drawingpath"];
	$dir=scandir($path);
	foreach($dir as $i => $f){
		if(is_file($path.DIRECTORY_SEPARATOR.$f)){
			$mimeType=mime_content_type($path.DIRECTORY_SEPARATOR.$f);
			if(preg_match('/^image/i', $mimeType)){
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
  <title>openDCIM Data Center Inventory</title>
  
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
		$('#datacenterform').validationEngine({});
		$('#drawingfilename').click(function(){
			$("#imageselection").dialog({
				resizable: false,
				height:500,
				width: 600,
				modal: true,
				buttons: {
<?php echo '					',__("Select"),': function() {'; ?>
						if($('#imageselection #preview').attr('image')!=""){
							$('#drawingfilename').val($('#imageselection #preview').attr('image'));
						}
						$(this).dialog("close");
					}
				}
			});
			$("#imageselection span").each(function(){
				var preview=$('#imageselection #preview');
				$(this).click(function(){
					preview.css({'border-width': '5px', 'width': '380px', 'height': '380px'});
					preview.html("<img src=\"<?php echo $config->ParameterArray["drawingpath"]; ?>"+$(this).text()+'" alt="preview">').attr('image',$(this).text());
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
				if($('#drawingfilename').val()==$(this).text()){
					$(this).click();
				}
			});
		});
		$('#delete-btn').click(function(){
				var defaultbutton={
				"<?php echo __("Yes"); ?>": function(){
					$.post('', {datacenterid: $('#datacenterid').val(),confirmdelete: '',junkremoval: $('#deletemodal select').val()}, function(data){
						if(data.trim()=='ok'){
							self.location=$('.main > a').last().attr('href');
							$(this).dialog("destroy");
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
	function coords(evento){
		mievento = evento || window.event;

		yo=document.getElementById("yo");
		x=mievento.layerX;
		y=mievento.layerY;
		yo.style.left=(x-12)+"px";
		yo.style.top=(y-12)+"px";
		yo.hidden=false;
		CoorX=document.getElementById("x");
		CoorX.value=x*2;
		CoorY=document.getElementById("y");
		CoorY.value=y*2;
	}
	function mueve(){
		tam=50;
		red=.5;
		tam=tam*red;
		yo=document.getElementById("yo");
		cont=document.getElementById("containerimg");
		CoorX=document.getElementById("x");
		CoorY=document.getElementById("y");
		if (CoorX.value<0) CoorX.value=0;
		if (CoorX.value*red>cont.offsetWidth) CoorX.value=cont.offsetWidth/red;
		if (CoorY.value<0) CoorY.value=0;
		if (CoorY.value*red>cont.offsetHeight) CoorY.value=cont.offsetHeight/red;
		yo.style.left=(CoorX.value*red-tam/2)+"px";
		yo.style.top=(CoorY.value*red-tam/2)+"px";
		if (CoorX.value<0 || CoorX.value*red>cont.offsetWidth
			|| CoorY.value<0 || CoorY.value*red>cont.offsetHeight)
			yo.hidden=true;
		else
			yo.hidden=false;
	}

	function cambio_container(){
		document.getElementById("cambio_cont").value="SI";
		document.getElementById("datacenterform").submit();
	}
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<h3>',$status,'</h3>
<div class="center"><div>
<form id="datacenterform" method="POST">
<div class="table">
<div>
   <div><label for="datacenterid">',__("Data Center ID"),'</label></div>
   <div><select name="datacenterid" id="datacenterid" onChange="form.submit()">
      <option value="0">',__("New Data Center"),'</option>';

	foreach($dcList as $dcRow){
		if($dcRow->DataCenterID == $dc->DataCenterID){$selected=" selected";}else{$selected="";}
		print "<option value=\"$dcRow->DataCenterID\"$selected>$dcRow->Name</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="dcname">',__("Name"),'</label></div>
   <div><input class="validate[required,minSize[3],maxSize[80]]" type="text" name="name" id="dcname" size="50" maxlength="80" value="',$dc->Name,'"></div>
</div>
<div>
   <div><label for="sqfootage">',$vol,'</label></div>
   <div><input class="validate[optional,custom[onlyNumberSp]]" type="text" name="squarefootage" id="sqfootage" size="10" maxlength="11" value="',$dc->SquareFootage,'"></div>
</div>
<div>
   <div><label for="deliveryaddress">',__("Delivery Address"),'</label></div>
   <div><input class="validate[optional,minSize[1],maxSize[200]]" type="text" name="deliveryaddress" id="deliveryaddress" size="60" maxlength="200" value="',$dc->DeliveryAddress,'"></div>
</div>
<div>
   <div><label for="administrator">',__("Administrator"),'</label></div>
   <div><input class="validate[optional,minSize[1],maxSize[80]]" type="text" type="text" name="administrator" id="administrator" size=60 maxlength="80" value="',$dc->Administrator,'"></div>
</div>
<div>
   <div><label for="drawingfilename">',__("Drawing URL"),'</label></div>
   <div><input type="text" name="drawingfilename" id="drawingfilename" size=60 value="',$dc->DrawingFileName,'"></div>
</div>
<div>
	<div><label for="maxkw">',__("Design Maximum (kW)"),'</label></div>
	<div><input class="validate[optional,custom[onlyNumberSp]]" type="text" name="maxkw" id="maxkw" size="8" maxlength="8" value="',$dc->MaxkW,'"></div>
</div>
<div><input type="hidden" name="cambio_cont" id="cambio_cont" value=""></div>
<div>
	<div><label for="container">',__("Container"),'</label></div>
  	<div><select name="container" id="container" onChange="cambio_container()">
      <option value="0">',__("None"),'</option>';

	$container=new Container();
	$cList=$container->GetContainerList();
	foreach($cList as $cRow){
		if($cRow->ContainerID == $dc->ContainerID){$selected=" selected";}else{$selected="";}
		print "<option value=\"$cRow->ContainerID\"$selected>$cRow->Name</option>\n";
	}

echo '	</select></div>
</div>
<div> 
	<div><b>X</b></div> 
 	<div><input type="text" name="x" id="x" value="',$dc->MapX,'" onblur="mueve()"></div> 
</div> 
<div> 
    <div><b>Y</b></div> 
    <div><input type="text" name="y" id="y" value="',$dc->MapY,'" onblur="mueve()"></div> 
</div>'; 

print "<div id=divcontainer>\n"; 
if ($dc->ContainerID>0){
	print "  <div><b>".__("Click on the image to select DC coordinates")."</b></div>"; 
	$container->ContainerID=$dc->ContainerID;
	$container->GetContainer();
	print "<div>";
	print $container->MakeContainerMiniImage("dc",$dc->DataCenterID);
	print "</div>"; 
}
print "</div>"; 

echo '<div class="caption">';

	if($dc->DataCenterID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>';
	}else{
		echo '   <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
	
	if ( $person->SiteAdmin && $dc->DataCenterID > 0 ) {
		echo '    <button type="button" id="delete-btn" name="action" value="Delete">',__("Delete"),'</button>';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
<?php echo '
			<div id="imageselection" title="Image file selector">
				',$imageselect,'
			</div>
</div></div>
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Data Center Deletion Confirmation"),'" id="deletemodal">
		<div id="modaltext"><img src="images/mushroom_cloud.jpg" class="floatleft">',__("Are you sure that you want to delete this data center and all contents within it?"),'
			<p><b>',__("Move the contents of this datacenter's storage room to the general storage or delete them?"),'</b> &nbsp;&nbsp;<select><option value="delete">',__("Delete"),'</option><option value="move">',__("Move"),'</option></select></p>
		</div>
	</div>
</div>

<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
