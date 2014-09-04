<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$cab=new Cabinet();
	$dc=new DataCenter();
	$dev=new Device();
	
	//setting airflow
	if(isset($_POST["cabinetid"]) && isset($_POST["airflow"])){
		$cab->CabinetID=$_POST["cabinetid"];
		if ($cab->GetCabinet()){
			if ($cab->CabRowID>0 && isset($_POST["row"]) && $_POST["row"]=="true"){
				//update all row
				$cabinets=$cab->GetCabinetsByRow();
				foreach($cabinets as $index => $cabinet){
					$cabinet->FrontEdge=$_POST["airflow"];
					$cabinet->UpdateCabinet();
				}
			}else{
				//update cabinet
				$cab->FrontEdge=$_POST["airflow"];
				$cab->UpdateCabinet();
			}
		}
		exit;
	}
	
	if(isset($_POST['dc']) && (isset($_POST['getobjects']) || isset($_POST['getoverview']))){
		$payload=array();
		if(isset($_POST['getobjects'])){
			$cab->DataCenterID=$_POST['dc'];
			$zone=new Zone();
			$zone->DataCenterID=$cab->DataCenterID;
			$payload=array('cab'=>$cab->ListCabinetsByDC(true),'zone'=>$zone->GetZonesByDC(true));
		}else{
			$dc->DataCenterID=$_POST['dc'];
			$dc->GetDataCenterByID();
			$payload=$dc->GetOverview();
		}

		header('Content-Type: application/json');
		echo json_encode($payload);
		exit;
	}


	if(!isset($_GET["dc"])){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
	
	$dc->DataCenterID=$_GET["dc"];
	$dc->GetDataCenterbyID();
	$dcStats=$dc->GetDCStatistics();
	
	function MakeImageMap($dc){
		$mapHTML="";
	 
		if(strlen($dc->DrawingFileName)>0){
			$mapfile="drawings".DIRECTORY_SEPARATOR.$dc->DrawingFileName;
		   
			if(file_exists($mapfile)){
				list($width, $height, $type, $attr)=getimagesize($mapfile);
				$mapHTML="<div class=\"canvas\" style=\"background-image: url('drawings/$dc->DrawingFileName')\">
	<img src=\"css/blank.gif\" usemap=\"#datacenter\" width=\"$width\" height=\"$height\" alt=\"clearmap over canvas\">
	<map name=\"datacenter\" data-dc=$dc->DataCenterID data-zoom=1 data-x1=0 data-y1=0>
	</map>
	<canvas id=\"mapCanvas\" width=\"$width\" height=\"$height\"></canvas>
\n</div>\n";
			}
		}
		return $mapHTML;
	}

	$height=0;
	$width=0;
	$ie8fix="";
	if(strlen($dc->DrawingFileName) >0){
		$mapfile="drawings/$dc->DrawingFileName";
		if(file_exists($mapfile)){
			list($width, $height, $type, $attr)=getimagesize($mapfile);
			// There is a bug in the excanvas shim that can set the width of the canvas to 10x the width of the image
			$ie8fix="
<script type=\"text/javascript\">
	function uselessie(){
		document.getElementById(\'mapCanvas\').className = \"mapCanvasiefix\";
	}
$(document).ready(function() {
	uselessie();
});
</script>
<style type=\"text/css\">
.mapCanvasiefix {
	    width: {$width}px !important;
}
</style>";
		}
	}
	// If no mapfile is set then we don't need the buttons to control drawing the map.  Adjust the CSS to hide them and make the heading centered
	if(strlen($dc->DrawingFileName) <1 || !file_exists("drawings/$dc->DrawingFileName")){
		$screenadjustment="<style type=\"text/css\">.dcstats .heading > div { width: 100% !important;} .dcstats .heading > div + div { display: none; }</style>";
	}
		
	if ( $config->ParameterArray["mUnits"] == "english" ) {
		$vol = __("Square Feet");
		$density = __("Watts per Square Foot");
	} else {
		$vol = __("Square Meters");
		$density = __("Watts per Square Meter" );
	}
	
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title><?php echo __("openDCIM Data Center Information Management");?></title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/common.js"></script>
  <script type="text/javascript" src="scripts/jquery.ui-contextmenu.js"></script>
  <!--[if lte IE 8]>
    <link rel="stylesheet"  href="css/ie.css" type="text/css">
    <?php if(isset($ie8fix)){print $ie8fix;} ?>
    <script src="scripts/excanvas.js"></script>
  <![endif]-->
</head>
<body>
<div id="header"></div>
<div class="page dcstats" id="mapadjust">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<div class="heading">
  <div>
	<h2>',$config->ParameterArray["OrgName"],'</h2>
	<h3>',__("Data Center Statistics"),'</h3>
  </div>
</div>
<div class="center"><div>
<div class="centermargin" id="dcstats">
<div class="table border">
  <div class="title">',$dc->Name,'<span><a href="search_export.php?datacenterid=',$dc->DataCenterID,'">',__("Export"),'</a>&nbsp;,&nbsp;<a href="report_xml_CFD.php?datacenterid=',$dc->DataCenterID,'">',__("XML"),'</a></span></div>
  <div>
	<div></div>
	<div>',__("Infrastructure"),'</div>
	<div>',__("Occupied"),'</div>
	<div>',__("Allocated"),'</div>
	<div>',__("Available"),'</div>
  </div>
  <div>
	<div>',sprintf(__("Total U")." %5d",$dcStats["TotalU"]),'</div>
	<div>',sprintf("%3d",$dcStats["Infrastructure"]),'</div>
	<div>',sprintf("%3d",$dcStats["Occupied"]),'</div>
	<div>',sprintf("%3d",$dcStats["Allocated"]),'</div>
	<div>',sprintf("%3d",$dcStats["Available"]),'</div>
  </div>
  <div>
	<div>',__("Percentage"),'</div>
	<div>',(($dcStats["TotalU"])?sprintf("%3.1f%%",$dcStats["Infrastructure"]/$dcStats["TotalU"]*100):"0"),'</div>
	<div>',(($dcStats["TotalU"])?sprintf("%3.1f%%",$dcStats["Occupied"]/$dcStats["TotalU"]*100):"0"),'</div>
	<div>',(($dcStats["TotalU"])?sprintf("%3.1f%%",$dcStats["Allocated"]/$dcStats["TotalU"]*100):"0"),'</div>
	<div>',(($dcStats["TotalU"])?sprintf("%3.1f%%",$dcStats["Available"]/$dcStats["TotalU"]*100):"0"),'</div>
  </div>
</div> <!-- END div.table -->
<div class="table border">
  <div>
        <div>',__("Computed Wattage"),'</div>
        <div>',sprintf("%7d %s", $dcStats["ComputedWatts"], __("Watts")),'</div>
  </div>
  <div>
		<div>',__("Measured Wattage"), '</div>
		<div>',sprintf("%7d %s", $dcStats["MeasuredWatts"], __("Watts")),'</div>
  </div>
  <div>
		<div>',__("Design Maximum (kW)"),'</div>
		<div>',sprintf("%7d kW",$dc->MaxkW ),'</div>
  </div>
  <div>
        <div>',__("BTU Computation from Computed Watts"),'</div>
        <div>',sprintf("%8d ".__("BTU"),$dcStats["ComputedWatts"]*3.412 ),'</div>
  </div>
  <div>
        <div>',__("Data Center Size"),'</div>
        <div>',sprintf("%8d %s",$dc->SquareFootage, $vol),'</div>
  </div>
  <div>
        <div>',$density,'</div>
        <div>',(($dc->SquareFootage)?sprintf("%8d ".__("Watts"),$dcStats["ComputedWatts"]/$dc->SquareFootage):"0 ".__("Watts")),'</div>
  </div>
  <div>
        <div>',__("Minimum Cooling Tonnage (Based on Computed Watts)"),'</div>
        <div>',sprintf("%7d ".__("Tons"),$dcStats["ComputedWatts"]*3.412*1.15/12000),'</div>
  </div>
</div> <!-- END div.table -->
</div> <!-- END div.centermargin -->
<br>
<div id="maptitle"><span></span><div class="nav">';

$select="\n\t<select>\n";
	foreach(array(
		'overview' => __("Overview"),
		'space' => __("Space"),
		'weight' => __("Weight"),
		'power' => __("Calculated Power"),
		'realpower' => __("Measured Power"),
		'temperature' => __("Temperature"),
		'humidity' => __("Humidity"),
		'airflow' => __("Air Flow")
		) as $value => $option){
		$select.="\t\t<option value=\"$value\">$option</option>\n";
	}
$select.="\t</select>\n";

echo $select."</div></div>\n".MakeImageMap($dc);

echo '
</div></div>

<ul id="options" class="hide"> 
	<li class="ui-state-disabled">',__("Set the air intake direction"),'</li>
	<li>----</li>
	<li><a>',__("Cabinet"),'</a>
		<ul data-context="cabinet">
			<li><a href="#Top">',__("Top"),'</a></li>
			<li><a href="#Right">',__("Right"),'</a></li>
			<li><a href="#Bottom">',__("Bottom"),'</a></li>
			<li><a href="#Left">',__("Left"),'</a></li>
		</ul>
	</li>
	<li><a href="#row">',__("Row"),'</a>
		<ul data-context="row">
			<li><a href="#Test">',__("Top"),'</a></li>
			<li><a href="#Right">',__("Right"),'</a></li>
			<li><a href="#Bottom">',__("Bottom"),'</a></li>
			<li><a href="#Left">',__("Left"),'</a></li>
		</ul>
	</li>
</ul>';
?>

</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
	$(document).ready(function() {
		// Hard set widths to stop IE from being retarded
		$('#mapCanvas').css('width', $('.canvas > img[alt="clearmap over canvas"]').width()+'px');
		$('#mapCanvas').parent('.canvas').css('width', $('.canvas > img[alt="clearmap over canvas"]').width()+'px');

		var firstcabinet=$('#dc<?php echo $dc->DataCenterID;?> > ul > li:first-child').attr('id');
		// Don't attempt to open the datacenter tree until it is loaded
		function opentree(){
			if($('#datacenters .bullet').length==0){
				setTimeout(function(){
					opentree();
				},500);
			}else{
				expandToItem('datacenters',firstcabinet);
			}
		}

		// Bind context menu to the cabinets
		$(".canvas > map").contextmenu({
			delegate: "area[name^=cab]",
			menu: "#options",
			select: function(event, ui) {
				var row=(ui.item.context.parentElement.getAttribute('data-context')=='row')?true:false;
				var cabid=ui.target.context.attributes.name.value.substr(3);
				$.post('',{cabinetid: cabid, airflow: ui.cmd, row: row}).done(function(){startmap()}); 
    		},
			beforeOpen: function(event, ui) {
				$('#options').removeClass('hide');
				$('.center .nav > select').val('airflow').trigger('change');
				$(".canvas > map").contextmenu("showEntry", "row", $(ui.target.context).data('row'));
			}
		});

		// Bind tooltips, highlight functions to the map
		startmap();
		opentree();
	});
</script>
</body>
</html>
