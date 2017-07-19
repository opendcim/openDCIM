<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$subheader=__("Map Selector");

	if ( !$person->SiteAdmin && !isset( $_REQUEST['cabinetid']) && !isset($_REQUEST['panelid'])) {
		header("Location: ".reirect());
		exit;
	}

	$dc=new DataCenter();
	$cab=new Cabinet();

	if ( isset( $_REQUEST['cabinetid'] )) {
		$cab=new Cabinet();

		$cab->CabinetID=$_REQUEST["cabinetid"];
		$cab->GetCabinet();

		$MapX1 = $cab->MapX1;
		$MapX2 = $cab->MapX2;
		$MapY1 = $cab->MapY1;
		$MapY2 = $cab->MapY2;

		$name = $cab->Location;

		$dc->DataCenterID=$cab->DataCenterID;
	} else {
		$pan = new PowerPanel();
		$pan->PanelID = $_REQUEST['panelid'];
		$pan->getPanel();

		$MapX1 = $pan->MapX1;
		$MapX2 = $pan->MapX2;
		$MapY1 = $pan->MapY1;
		$MapY2 = $pan->MapY2;

		$name = $pan->PanelLabel;

		$dc->DataCenterID = $pan->MapDataCenterID;
	}

	$dc->GetDataCenter();

	if(isset($_REQUEST["action"])&&($_REQUEST["action"]=="Submit")){
		if ( isset( $_REQUEST['cabinetid'] )) {
			$cab->MapX1=intval($_REQUEST["x1"]);
			$cab->MapX2=intval($_REQUEST["x2"]);
			$cab->MapY1=intval($_REQUEST["y1"]);
			$cab->MapY2=intval($_REQUEST["y2"]);
			$cab->FrontEdge=$_REQUEST["frontedge"];
			$cab->UpdateCabinet();

			$url=redirect("cabnavigator.php?cabinetid=$cab->CabinetID");
		} else {
			$pan->MapX1=intval($_REQUEST["x1"]);
			$pan->MapX2=intval($_REQUEST["x2"]);
			$pan->MapY1=intval($_REQUEST["y1"]);
			$pan->MapY2=intval($_REQUEST["y2"]);
			$pan->updatePanel();

			$url=redirect("power_panel.php?PanelID=$pan->PanelID");

		}
		header("Location: $url");
	}


	$height=0;
	$width=0;
	if(strlen($dc->DrawingFileName) >0){
		$mapfile="drawings/$dc->DrawingFileName";
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
  
  <title>openDCIM Data Center Information Management</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/imgareaselect-default.css" type="text/css">
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.imgareaselect.pack.js"></script>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
    <?php if(isset($ie8fix)){echo $ie8fix;} ?>
  <![endif]-->
  <?php if(isset($screenadjustment)){echo $screenadjustment;} ?>
 
<script type="text/javascript">
	$(document).keydown(function(event){ 
		if (event.ctrlKey && event.keyCode == 83) {
			$('input[name=action]').trigger('click');
			return false;
		}
	});
</script>
 
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page" id="mapadjust">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main">
<div class="mapmaker">
<div>
</div>

	<div class="table">
        <div class="title"><?php echo __("Coordinates"); ?></div> 
	<form method="POST">
    <div class="table"> 
<?php
	if ( isset($_REQUEST['cabinetid']) )  {
		print '<input type="hidden" name="cabinetid" value="'.$cab->CabinetID.'">';
	} else {
		print '<input type="hidden" name="panelid" value="'.$pan->PanelID.'">';
	}
?>
        <div> 
          <div><b>X<sub>1</sub>:</b></div> 
 		      <div><input type="text" name="x1" id="x1" value="<?php echo $MapX1; ?>"></div> 
        </div> 
        <div> 
          <div><b>Y<sub>1</sub>:</b></div> 
          <div><input type="text" name="y1" id="y1" value="<?php echo $MapY1; ?>"></div> 
        </div> 
        <div> 
          <div><b>X<sub>2</sub>:</b></div> 
          <div><input type="text" name="x2" id="x2" value="<?php echo $MapX2; ?>"></div> 
          <div></div> 
          <div></div> 
        </div> 
        <div> 
          <div><b>Y<sub>2</sub>:</b></div> 
          <div><input type="text" name="y2" id="y2" value="<?php echo $MapY2; ?>"></div> 
          <div></div> 
          <div></div> 
        </div>
<?php
	if ( isset( $_REQUEST['cabinetid'])) {
?>
		<div>
			<div><b><?php print __("Front Edge"); ?></b></div>
			<div><select name="frontedge">
<?php
				$edgearray=array('Top' => __("Top"),
						'Right' => __("Right"),
						'Bottom' => __("Bottom"),
						'Left' => __("Left"));
				foreach($edgearray as $edge => $translation){
					$selected=($edge==$cab->FrontEdge)?' SELECTED':'';
					print "\t\t\t\t<option value=\"$edge\"$selected>$translation</option>\n";
				}
?>
			</select></div>
			<div></div>
		</div>
	<div class="caption">
	  <input type="submit" name="action" value="Submit">
	  <button type="reset" onclick="document.location.href='cabnavigator.php?cabinetid=<?php echo $cab->CabinetID; ?>'; return false;">Cancel</button>
	</div>
<?php
	} else {
?>
	<div class="caption">
	  <input type="submit" name="action" value="Submit">
	  <button type="reset" onclick="document.location.href='power_panel.php?PanelID=<?php echo $pan->PanelID; ?>'; return false;">Cancel</button>
	</div>
<?php
	}
?>
    </div> <!-- END div.table --> 
	</form>
	</div>
</div> <!-- END div.mapmaper -->

<div class="center"><div>
<?php echo "<img src=\"css/blank.gif\" height=$height width=$width>"; ?>
<div class="container demo"> 
  <div style="float: left; width: 70%;"> 
    <p class="instructions"><?php echo __("Click and drag on the image to select an area for "),' ',$name; ?>.</p> 
 
    <div class="frame" style="margin: 0 0.3em; width: 300px; height: 300px;">
		<?php
			$errors=array();
			$mapfile="drawings/$dc->DrawingFileName";
			if(!strlen($dc->DrawingFileName)>0){$errors[]=__("You must configure an image for this datacenter before attempting to place a cabinet on its map.");}
			if(!is_file($mapfile)){$errors[]=sprintf(__("Please check that &quot;%s&quot; is actually a file."),$dc->DrawingFileName);}
			if(!is_readable($mapfile)){$errors[]=sprintf(__("Please check the permissions on %s and make sure it is readable."),$dc->DrawingFileName);}
			if(count($errors)>0){
				foreach($errors as $error){
					print "<p class=\"warning\">$error</p>\n";
				}
			}else{
				print "<img id=\"map\" src=\"drawings/$dc->DrawingFileName\">";
			}
		?>			
    </div> 
  </div> 
 
  <div style="float: left; width: 30%;"> 
    <p style="font-size: 110%; font-weight: bold; padding-left: 0.1em;"><?php echo __("Selection Preview"); ?></p> 
  
  </div> 
</div> 
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
		print "\t\tx1: $MapX1,
			x2: $MapX2,
			y1: $MapY1,
			y2: $MapY2,\n";
	?>
			handles: true,
			onSelectChange: preview
		});
		// Don't attempt to open the datacenter tree until it is loaded
		function opentree(){
			if($('#datacenters .bullet').length==0){
				setTimeout(function(){
					opentree();
				},500);
			}else{
				var firstcabinet=$('#dc<?php echo $dc->DataCenterID;?> > ul > li:first-child').attr('id');
				expandToItem('datacenters',firstcabinet);
			}
		}
		opentree();
	});
</script>
</body>
</html>
