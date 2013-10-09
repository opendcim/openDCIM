<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights();

	if(!$user->SiteAdmin){
		// No soup for you.
		header("Location: ".redirect());
		exit;
	}

	$dc=new DataCenter();
	$cab=new Cabinet();

	$cab->CabinetID=$_REQUEST["cabinetid"];
	$cab->GetCabinet();

	$dc->DataCenterID=$cab->DataCenterID;
	$dc->GetDataCenter();

	if(isset($_REQUEST["action"])&&($_REQUEST["action"]=="Submit")){
		$cab->MapX1=intval($_REQUEST["x1"]);
		$cab->MapX2=intval($_REQUEST["x2"]);
		$cab->MapY1=intval($_REQUEST["y1"]);
		$cab->MapY2=intval($_REQUEST["y2"]);
		$cab->UpdateCabinet();

		$url=redirect("cabnavigator.php?cabinetid=$cab->CabinetID");
		header("Location: $url");
	}
	$height=0;
	$width=0;
	if(strlen($dc->DrawingFileName) >0){
		$mapfile="drawings/$dc->DrawingFileName";
		if(file_exists($mapfile)){
			list($width, $height, $type, $attr)=getimagesize($mapfile);
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
function preview(img, selection) {
    if (!selection.width || !selection.height)
        return;
    $('#x1').val(selection.x1);
    $('#y1').val(selection.y1);
    $('#x2').val(selection.x2);
    $('#y2').val(selection.y2);
}
$(document).ready(function() {
	$('#map').imgAreaSelect( {
<?php
	printf( "x1: %d, x2: %d, y1: %d, y2: %d,\n", $cab->MapX1, $cab->MapX2, $cab->MapY1, $cab->MapY2 );
?>
		handles: true,
		onSelectChange: preview
	});
});
</script>
</head>
<body>
<div id="header"></div>
<div class="page" id="mapadjust">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main">
<div class="mapmaker">
<div>
<h2><?php echo $config->ParameterArray["OrgName"]; ?></h2>
<h3><?php echo __("Map Selector"); ?></h3>
</div>

	<div class="table">
        <div class="title"><?php echo __("Coordinates"); ?></div> 
	<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
    <div class="table"> 
	<input type="hidden" name="cabinetid" value="<?php printf( "%d", $cab->CabinetID ); ?>">
        <div> 
          <div><b>X<sub>1</sub>:</b></div> 
 		      <div><input type="text" name="x1" id="x1" value="<?php echo $cab->MapX1; ?>"></div> 
        </div> 
        <div> 
          <div><b>Y<sub>1</sub>:</b></div> 
          <div><input type="text" name="y1" id="y1" value="<?php echo $cab->MapY1; ?>"></div> 
        </div> 
        <div> 
          <div><b>X<sub>2</sub>:</b></div> 
          <div><input type="text" name="x2" id="x2" value="<?php echo $cab->MapX2; ?>"></div> 
          <div></div> 
          <div></div> 
        </div> 
        <div> 
          <div><b>Y<sub>2</sub>:</b></div> 
          <div><input type="text" name="y2" id="y2" value="<?php echo $cab->MapY2; ?>"></div> 
          <div></div> 
          <div></div> 
        </div>
	<div class="caption">
	  <input type="submit" name="action" value="Submit">
	  <button type="reset" onclick="document.location.href='cabnavigator.php?cabinetid=<?php echo $cab->CabinetID; ?>'; return false;">Cancel</button>
	</div>
    </div> <!-- END div.table --> 
	</form>
	</div>
</div> <!-- END div.mapmaper -->

<div class="center"><div>
<?php echo "<img src=\"css/blank.gif\" height=$height width=$width>"; ?>
<div class="container demo"> 
  <div style="float: left; width: 70%;"> 
    <p class="instructions"><?php echo __("Click and drag on the image to select an area for cabinet"),' ',$cab->Location; ?>.</p> 
 
    <div class="frame" style="margin: 0 0.3em; width: 300px; height: 300px;"> 
      <?php 
        $mapfile = "drawings/$dc->DrawingFileName";
        if (strlen($mapfile)) {
            if (is_file($mapfile)) {
                if (is_readable($mapfile)) {  
                    echo "<img id=\"map\" src=\"$mapfile\" />";
                } else {
                    echo '<p class="warning">Please check the permissions on '.$dc->DrawingFileName.'.</p>';
                }
            } else {
                echo '<p class="warning">Please check that '.$dc->DrawingFileName.' is actually a file.</p>';
            }
        } else { ?>
          <p class="warning">Please configure an imagemap for this datacenter before setting coordinates.</p>
          <p class="instructions">(Edit Data Centers&rarr;Data Center&rarr;Drawing URL)</p>
      <?php } ?>
    </div> 
  </div> 
 
  <div style="float: left; width: 30%;"> 
    <p style="font-size: 110%; font-weight: bold; padding-left: 0.1em;"><?php echo __("Selection Preview"); ?></p> 
  
  </div> 
</div> 
</body>
</html>
