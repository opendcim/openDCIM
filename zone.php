<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$subheader=__("Data Center Zones");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$zone=new Zone();
	$dc_zone=new DataCenter();
	$dc=new DataCenter();
	
	$DCList=$dc->GetDCList();
	$formpatch="";
	$status="";
	
	if(isset($_POST['action']) && $_POST['action']=='Delete'){
		$zone->ZoneID=$_POST['zoneid'];
		$zone->DeleteZone();
		header('Location: '.redirect('zone.php'));
		exit;
	}
	
	if(isset($_REQUEST["zoneid"])) {
		$zone->ZoneID=(isset($_POST['zoneid'])?$_POST['zoneid']:$_GET['zoneid']);
		$zone->GetZone();
		
		if(isset($_POST["action"]) && (($_POST["action"]=="Create") || ($_POST["action"]=="Update"))){
			$zone->Description=$_POST["description"];
			$zone->DataCenterID=$_POST["datacenterid"];
			$zone->MapX1=$_POST["x1"];
			$zone->MapY1=$_POST["y1"];
			$zone->MapX2=$_POST["x2"];
			$zone->MapY2=$_POST["y2"];
			$zone->MapZoom=$_POST["mapzoom"];
			
			if($_POST["action"]=="Create"){
				$zone->CreateZone();
			}else{
				$status=__("Updated");
				$zone->UpdateZone();
			}
		}
		$formpatch="?zoneid=$zone->ZoneID";
	}

	$zone->MapZoom=($zone->ZoneID==0)?100:$zone->MapZoom;

	$dc_zone->DataCenterID=$zone->DataCenterID;
	$dc_zone->GetDataCenterbyID();
	
	$zoneList=$zone->GetZoneList();
	$height=0;
	$width=0;
	if(strlen($dc_zone->DrawingFileName) >0){
		$mapfile="drawings/$dc_zone->DrawingFileName";
		if(file_exists($mapfile)){
			if(mime_content_type($mapfile)=='image/svg+xml'){
				$svgfile = simplexml_load_file($mapfile);
				$width = substr($svgfile['width'],0,4);
				$height = substr($svgfile['height'],0,4);
			}else{
				list($width, $height, $type, $attr)=getimagesize($mapfile);
			}
			// There is a bug in the excanvas shim that can set the width of the canvas to 10x the width of the image
			$ie8fix='
<script type="text/javascript">
	function uselessie(){
		document.getElementById(\'mapCanvas\').className = "mapCanvasiefix";
	}
</script>';
		}
	}
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title><?php echo __("openDCIM Data Center Zones"); ?></title>

  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/imgareaselect-default.css" type="text/css">
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.imgareaselect.pack.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript" src="scripts/common.js?v<?php echo filemtime('scripts/common.js');?>"></script>

  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
    <?php if(isset($ie8fix)){echo $ie8fix;} ?>
  <![endif]-->
  
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page" id="mapadjust">
<?php
	include( "sidebar.inc.php" );

echo '
<div class="main">
	<div class="zonemaker">
		<h3>',$status,'</h3>
		<div class="center" style="min-height: 0px;"><div>
			<form action="',$_SERVER["SCRIPT_NAME"].$formpatch,'" method="POST">
				<div class="table">
					<div>
						<div><label for="zoneid">',__("Zone"),'</label></div>
						<div><input type="hidden" name="action" value="query">
							<select name="zoneid" id="zoneid" onChange="form.submit()">
							<option value=0>',__("New Zone"),'</option>';

	foreach($zoneList as $zoneRow){
		if($zone->ZoneID==$zoneRow->ZoneID){$selected=" selected";}else{$selected="";}
		$dc->DataCenterID=$zoneRow->DataCenterID;
		$dc->GetDataCenter();
		print "\t\t\t\t\t\t\t<option value=\"$zoneRow->ZoneID\"$selected>[".$dc->Name."] ".$zoneRow->Description."</option>\n";
	}

echo '
							</select>
						</div>
					</div>
					<div>
						<div><label for="description">',__("Description"),'</label></div>
						<div><input type="text" size="50" name="description" id="description" value="',$zone->Description,'"></div>
					</div>
					<div>
						<div><label for="datacenterid">',__("Data Center"),'</label></div>
						<div>
							<select name="datacenterid" id="datacenterid">';

foreach($DCList as $DCRow){
		if($zone->DataCenterID==$DCRow->DataCenterID){$selected=" selected";}else{$selected="";}
		print "\t\t\t\t\t\t\t<option value=\"$DCRow->DataCenterID\"$selected>$DCRow->Name</option>\n";
	}

echo '
							</select>
						</div>
					</div>
					<div>
						<div><label for="x1">X1</label></div>
						<div><input type="text" name="x1" id="x1" value="',$zone->MapX1,'"></div>
						</div>
					<div>
						<div><label for="y1">Y1</label></div>
						<div><input type="text" name="y1" id="y1" value="',$zone->MapY1,'"></div>
					</div>
					<div>
						<div><label for="x2">X2</label></div>
						<div><input type="text" name="x2" id="x2" value="',$zone->MapX2,'"></div>
					</div>
					<div>
						<div><label for="y2">Y2</label></div>
						<div><input type="text" name="y2" id="y2" value="',$zone->MapY2,'"></div>
					</div>
					<div>
						<div><label for="zoom">',__("Zoom"),' (%)</label></div>
						<div><input type="text" name="mapzoom" id="mapzoom" value="',$zone->MapZoom,'"></div>
					</div>';

	if($zone->ZoneID==0){
		echo '
					<div><div>&nbsp;</div><div></div></div>
					<div class="caption"><button type="submit" name="action" value="Create">',__("Create"),'</button></div>';
	}
	else{
		echo '
					<div><div>&nbsp;</div><div></div></div>
					<div class="caption"><button type="submit" name="action" value="Update">',__("Update"),'</button>
					<button type="button" name="action" value="Delete">',__("Delete"),'</button></div>';
	}

echo '
				</div><!-- END div.table -->
			</form>
		</div></div><!-- END div.center -->
	</div><!-- END div.zonemaker -->';

if(strlen($dc_zone->DrawingFileName) >0){
	echo '
	<div class="center"><div>
		<img src="css/blank.gif" height=',$height,' width=',$width,'>
		<div class="container demo">
			<div style="float: left; ">
				<p class="instructions">',__("Click and drag on the image to select an area for this zone"),'</p>
				<div class="frame" style="margin: 0 0.3em; width: 300px; height: 300px;"> 
			  	    <img id="map" height=',$height,' width=',$width,' src="drawings/',$dc_zone->DrawingFileName,'"> 
		 		</div>
		 	</div>
		</div>
	</div></div><!-- END div.center -->';
}

echo '
<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
</div><!-- END div.main -->'; 

 ?>

</div><!-- END div.page -->
<script type="text/javascript">
	$(document).ready(function() {
		function preview(img, selection) {
			if (!selection.width || !selection.height){
				return;
			}
			$('#x1').val(selection.x1);
			$('#y1').val(selection.y1);
			$('#x2').val(selection.x2);
			$('#y2').val(selection.y2);
		}
		$('#map').imgAreaSelect( {
	<?php
		printf( "\t\tx1: %d,\n\tx2: %d,\n\ty1: %d,\n\ty2: %d,\n", $zone->MapX1, $zone->MapX2, $zone->MapY1, $zone->MapY2 );
	?>
			handles: true,
			onSelectChange: preview
		});
		
		// Delete container confirmation dialog
		$('button[value="Delete"]').click(function(e){
			var form=$(this).parents('form');
			var btn=$(this);
<?php
print "		var dialog=$('<div>').prop('title','".__("Verify Delete Zone")."').html('<p><span class=\"ui-icon ui-icon-alert\" style=\"float:left; margin:0 7px 20px 0;\"></span><span></span></p>');";
print "		dialog.find('span + span').html('".__("This Zone will be deleted and there is no undo.  Assets within the zone will remain as members of the Data Center.")."<br><br>".__("Are you sure?")."');"; 
?>
			dialog.dialog({
				resizable: false,
				modal: true,
				width: 'auto',
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

		$("#zoneid").combobox();
		$("#datacenterid").combobox();
	});
</script>
</body>
</html>

