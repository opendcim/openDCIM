<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$subheader=__("Data Center Statistics");

	$cab=new Cabinet();
	$dc=new DataCenter();
	$dev=new Device();
	
	//setting airflow
	if(isset($_POST["cabinetid"]) && isset($_POST["airflow"]) && $person->SiteAdmin){
		$cab->CabinetID=$_POST["cabinetid"];
		if ($cab->GetCabinet()){
			if ($cab->CabRowID>0 && isset($_POST["row"]) && $_POST["row"]=="true"){
				//update all row
				$cabinets=$cab->GetCabinetsByRow();
				foreach($cabinets as $index => $cabinet){
					if ( in_array( $_POST['airflow'], array( "Top", "Bottom", "Left", "Right"))) {
						// This is an update to the airflow
						$cabinet->FrontEdge=$_POST["airflow"];
					} else {
						// This is an alignment command
						switch( $_POST['airflow'] ) {
							case "ATop":
								$cabinet->MapY1 = $cab->MapY1;
								break;
							case "ALeft":
								$cabinet->MapX1 = $cab->MapX1;
								break;
							case "ABottom":
								$cabinet->MapY2 = $cab->MapY2;
								break;
							case "ARight":
								$cabinet->MapX2 = $cab->MapX2;
								break;
							default:
								// Update nothing, because invalid input was supplied
						}
					}
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
			$payload=array('cab'=>$cab->ListCabinetsByDC(true),'panel'=>PowerPanel::getPanelsForMap($_POST['dc']),'zone'=>$zone->GetZonesByDC(true));
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

	$rciStats = RCI::GetStatistics( "dc", $dc->DataCenterID );
	
	function MakeImageMap($dc){
		$mapHTML="";
	 
		if(strlen($dc->DrawingFileName)>0){
			$mapfile="drawings".DIRECTORY_SEPARATOR.$dc->DrawingFileName;
		   
			if(file_exists($mapfile)){
				if(mime_content_type($mapfile)=='image/svg+xml'){
					$svgfile = simplexml_load_file($mapfile);
					$width = substr($svgfile['width'],0,4);
					$height = substr($svgfile['height'],0,4);
				}else{
					list($width, $height, $type, $attr)=getimagesize($mapfile);
				}
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
			if(mime_content_type($mapfile)=='image/svg+xml'){
				$svgfile = simplexml_load_file($mapfile);
				$width = substr($svgfile['width'],0,4);
				$height = substr($svgfile['height'],0,4);
			}else{
				list($width, $height, $type, $attr)=getimagesize($mapfile);
			}
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
		$tempUnits = "F";
		$density = __("Watts per Square Foot");
	} else {
		$vol = __("Square Meters");
		$tempUnits = "C";
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
  <script type="text/javascript">
  	var js_outlinecabinets = <?php print $config->ParameterArray["OutlineCabinets"] == 'enabled'?1:0; ?>;
  	var js_labelcabinets = <?php print $config->ParameterArray["LabelCabinets"] == 'enabled'?1:0; ?>;
  </script>
  <!--[if lte IE 8]>
    <link rel="stylesheet"  href="css/ie.css" type="text/css">
    <?php if(isset($ie8fix)){print $ie8fix;} ?>
    <script src="scripts/excanvas.js"></script>
  <![endif]-->
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page dcstats" id="mapadjust">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<div class="center"><div>
<div class="centermargin" id="dcstats">
<div class="table border">
  <div class="title">',$dc->Name,'<span><a href="search_export.php?datacenterid=',$dc->DataCenterID,'">',__("Export"),'</a>&nbsp;,&nbsp;<a href="report_xml_CFD.php?datacenterid=',$dc->DataCenterID,'">',__("XML"),'</a></span><div class="hide" id="msg_show">',__("Click to show statistics"),'</div><div class="hide" id="msg_hide">',__("Click to hide statistics"),'</div></div>
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
    <div>',__("Delivery Address"),'</div>
    <div>',$dc->DeliveryAddress,'</div>
  </div>
  <div>
    <div>',__("Administrator"),'</div>
    <div>',$dc->Administrator,'</div>
  </div>
</div>
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
  	<div>',__("Total Cabinets"),'</div>
  	<div>',sprintf( "%d", $dcStats["TotalCabinets"] ),'</div>
  </div>
  <div>
        <div>',__("Minimum Cooling Tonnage (Based on Computed Watts)"),'</div>
        <div>',sprintf("%7d ".__("Tons"),$dcStats["ComputedWatts"]*3.412*1.15/12000),'</div>
  </div>
  <div>
        <div>',__("Average Temperature"),'</div>
        <div>',sprintf("%7d %s", $dcStats["AvgTemp"], __("°". $tempUnits)),'</div>
  </div>
  <div>
        <div>',__("Average Humidity"), '</div>
        <div>',sprintf("%7d %s", $dcStats["AvgHumidity"], __("%")),'</div>
  </div>
  <div>
		<div>',__("RCI Low Percentage (Overcooling)"), '</div>
		<div>',(($rciStats["TotalCabinets"])?sprintf("%7d %s",
					$rciStats["RCILowCount"] / $rciStats["TotalCabinets"] * 100,
		   			__("%")):"0 ".__("%")),
		'</div>
  </div>
  <div>
		<div>',__("RCI High Percentage (Cabinets Satisfied)"), '</div>
		<div>',(($rciStats["TotalCabinets"])?sprintf("%7d %s",
		   			(1-$rciStats["RCIHighCount"] / $rciStats["TotalCabinets"]) * 100,
					__("%")):"100 ". __("%")),
		'</div>
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
			<li><a href="#Top">',__("Top"),'</a></li>
			<li><a href="#Right">',__("Right"),'</a></li>
			<li><a href="#Bottom">',__("Bottom"),'</a></li>
			<li><a href="#Left">',__("Left"),'</a></li>
		</ul>
	</li>
	<li><a href="#alignment">',__("Alignment"),'</a>
		<ul data-context="alignment">
			<li><a href="#ATop">',__("Align Top"),'</a></li>
			<li><a href="#ALeft">',__("Align Left"),'</a></li>
			<li><a href="#ABottom">',__("Align Bottom"),'</a></li>
			<li><a href="#ARight">',__("Align Right"),'</a></li>
		</ul>
	</li>
</ul>';
?>

</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
	// Turn the stats box at the top into a lampshade
	$('#dcstats .title').css({'position':'relative'});
	$('#dcstats .title > div').css({'border':'0 none','bottom':'-3px','font-size':'xx-small','position':'absolute','right':0}).hide().removeClass('hide');
	$('#msg_hide').show();
	$('#dcstats').outerWidth($('#dcstats').outerWidth());
	$('#dcstats .title').on('click',function(e){
		// this check just prevents the shade from opening / closing
		// when you click the links in the box and it had a weird feel
		if(e.target.nodeName != 'A'){
			if($('#msg_hide').is(':visible')){
				$('#msg_hide').hide();
				$('#msg_show').show();
				$('#dcstats > div:nth-child(n+2)').hide();
				$('#dcstats > div:first-child > div:nth-child(n+2)').hide();
			}else{
				$('#msg_hide').show();
				$('#msg_show').hide();
				$('#dcstats > div:nth-child(n+2)').show();
				$('#dcstats > div:first-child > div:nth-child(n+2)').show();
			}
		}
	}).trigger('click');

	$(document).ready(function() {
		// Hard set widths to stop IE from being retarded
		$('#mapCanvas').css('width', $('.canvas > img[alt="clearmap over canvas"]').width()+'px');
		$('#mapCanvas').parent('.canvas').css('width', $('.canvas > img[alt="clearmap over canvas"]').width()+'px');

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
<?php
	if ( $person->SiteAdmin ) {
		// Only Site Administrators should even have the option to change the air flow
?>
		// Bind context menu to the cabinets
		$(".canvas > map").contextmenu({
			delegate: "area[name^=cab]",
			menu: "#options",
			select: function(event, ui) {
				var row=(ui.item.context.parentElement.getAttribute('data-context')=='row'||ui.item.context.parentElement.getAttribute('data-context')=='alignment')?true:false;
				var cabid=ui.target.context.attributes.name.value.substr(3);
				$.post('',{cabinetid: cabid, airflow: ui.cmd, row: row}).done(function(){startmap()}); 
    		},
			beforeOpen: function(event, ui) {
				$('#options').removeClass('hide');
				$('.center .nav > select').val('airflow').trigger('change');
				$(".canvas > map").contextmenu("showEntry", "row", $(ui.target.context).data('row'));
			}
		});
<?php
	}
?>
		// Bind tooltips, highlight functions to the map
		startmap();
		opentree();
	});
</script>
</body>
</html>
