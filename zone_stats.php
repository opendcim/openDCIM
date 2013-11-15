<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	if(!isset($_GET["zone"])){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$cab=new Cabinet();
	$zone=new Zone();
	$dc=new DataCenter();
	$dev=new Device();
	
	$zone->ZoneID=$_GET["zone"];
	if (!$zone->GetZone()){
		header('Location: '.redirect());
		exit;
	}
	$zoneStats=$zone->GetZoneStatistics();
	$dc->DataCenterID=$zone->DataCenterID;
	$dc->GetDataCenterbyID();
	
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
	//aproximate proportion between zone/DC 
	$prop_zone_dc=($zone->MapX2-$zone->MapX1)*($zone->MapY2-$zone->MapY1)/$width/$height;
	$prop_zone_dc=($prop_zone_dc>0)?$prop_zone_dc:1;

		if(isset($_POST['tooltip'])){
		
		$sql="SELECT C.*, T.Temp, T.Humidity, P.RealPower, T.LastRead, PLR.RPLastRead 
			FROM ((fac_Cabinet C LEFT JOIN fac_CabinetTemps T ON C.CabinetID = T.CabinetID) 
				LEFT JOIN (SELECT CabinetID, SUM(Wattage) RealPower FROM fac_PowerDistribution PD 
				LEFT JOIN fac_PDUStats PS ON PD.PDUID=PS.PDUID GROUP BY CabinetID) P ON C.CabinetID = P.CabinetID) 
			LEFT JOIN (SELECT CabinetID, MAX(LastRead) RPLastRead FROM fac_PowerDistribution PD 
				LEFT JOIN fac_PDUStats PS ON PD.PDUID=PS.PDUID GROUP BY CabinetID) PLR 
				ON C.CabinetID = PLR.CabinetID WHERE C.CabinetID=".intval($_POST['tooltip']);

		if($cabRow=$dbh->query($sql)->fetch()){
			$cab->CabinetID=$cabRow["CabinetID"];
			$cab->GetCabinet();
			$dev->Cabinet=$cab->CabinetID;
        	$devList=$dev->ViewDevicesByCabinet();
			$currentHeight = $cab->CabinetHeight;
        	$totalWatts = $totalWeight = $totalMoment =0;
			$currentTemperature=$cabRow["Temp"];
			$currentHumidity=$cabRow["Humidity"];
			$currentRealPower=$cabRow["RealPower"];
			$lastRead=(!is_null($cabRow["LastRead"]))?strftime('%c',strtotime(($cabRow["LastRead"]))):0;
			$RPlastRead=(!is_null($cabRow["RPLastRead"]))?strftime('%c',strtotime(($cabRow["RPLastRead"]))):0;
			$rs='red';
			$ys='yellow';
			$gs='green';
			$us='wtf';
			
			// get all limits for use with loop below
			$dc->dcconfig=new Config();
			$SpaceRed=intval($dc->dcconfig->ParameterArray["SpaceRed"]);
			$SpaceYellow=intval($dc->dcconfig->ParameterArray["SpaceYellow"]);
			$WeightRed=intval($dc->dcconfig->ParameterArray["WeightRed"]);
			$WeightYellow=intval($dc->dcconfig->ParameterArray["WeightYellow"]);
			$PowerRed=intval($dc->dcconfig->ParameterArray["PowerRed"]);
			$PowerYellow=intval($dc->dcconfig->ParameterArray["PowerYellow"]);
			$RealPowerRed=intval($dc->dcconfig->ParameterArray["PowerRed"]);
			$RealPowerYellow=intval($dc->dcconfig->ParameterArray["PowerYellow"]);
			
			// Temperature 
			$TemperatureYellow=intval($dc->dcconfig->ParameterArray["TemperatureYellow"]);
			$TemperatureRed=intval($dc->dcconfig->ParameterArray["TemperatureRed"]);
			
			// Humidity
			$HumidityMin=intval($dc->dcconfig->ParameterArray["HumidityRedLow"]);
			$HumidityMedMin=intval($dc->dcconfig->ParameterArray["HumidityYellowLow"]);			
			$HumidityMedMax=intval($dc->dcconfig->ParameterArray["HumidityYellowHigh"]);				
			$HumidityMax=intval($dc->dcconfig->ParameterArray["HumidityRedHigh"]);
						
			
			while(list($devID,$device)=each($devList)){
				$totalWatts+=$device->GetDeviceTotalPower();
				$DeviceTotalWeight=$device->GetDeviceTotalWeight();
				$totalWeight+=$DeviceTotalWeight;
				$totalMoment+=($DeviceTotalWeight*($device->Position+($device->Height/2)));
			}
				
        	$used=$cab->CabinetOccupancy($cab->CabinetID);
			// check to make sure the cabinet height is set to keep errors out of the logs
			if(!isset($cab->CabinetHeight)||$cab->CabinetHeight==0){$SpacePercent=100;}else{$SpacePercent=locale_number($used /$cab->CabinetHeight *100,0);}
			// check to make sure there is a weight limit set to keep errors out of logs
			if(!isset($cab->MaxWeight)||$cab->MaxWeight==0){$WeightPercent=0;}else{$WeightPercent=locale_number($totalWeight /$cab->MaxWeight *100,0);}
			// check to make sure there is a kilowatt limit set to keep errors out of logs
        	if(!isset($cab->MaxKW)||$cab->MaxKW==0){$PowerPercent=0;}else{$PowerPercent=locale_number(($totalWatts /1000 ) /$cab->MaxKW *100,0);}
			if(!isset($cab->MaxKW)||$cab->MaxKW==0){$RealPowerPercent=0;}else{$RealPowerPercent=locale_number(($currentRealPower /1000 ) /$cab->MaxKW *100,0);}
		
			//Decide which color to paint on the canvas depending on the thresholds
			if($SpacePercent>$SpaceRed){$scolor=$rs;}elseif($SpacePercent>$SpaceYellow){$scolor=$ys;}else{$scolor=$gs;}
			if($WeightPercent>$WeightRed){$wcolor=$rs;}elseif($WeightPercent>$WeightYellow){$wcolor=$ys;}else{$wcolor=$gs;}
			if($PowerPercent>$PowerRed){$pcolor=$rs;}elseif($PowerPercent>$PowerYellow){$pcolor=$ys;}else{$pcolor=$gs;}
			if($RPlastRead=='0'){$rpcolor=$us;}elseif($RealPowerPercent>$RealPowerRed){$rpcolor=$rs;}elseif($RealPowerPercent>$RealPowerYellow){$rpcolor=$ys;}else{$rpcolor=$gs;}
        	if($currentTemperature==0){$tcolor=$us;}elseif($currentTemperature>$TemperatureRed){$tcolor=$rs;}elseif($currentTemperature>$TemperatureYellow){$tcolor=$ys;}else{$tcolor=$gs;}
			
			if($currentHumidity==0){$hcolor=$us;}elseif($currentHumidity>$HumidityMax || $currentHumidity<$HumidityMin){$hcolor=$rs;
			}elseif($currentHumidity>$HumidityMedMax || $currentHumidity<$HumidityMedMin) {$hcolor=$ys;}else{$hcolor=$gs;}

				
			$labelsp=locale_number($used,0)." / ".$cab->CabinetHeight." U";
			$labelwe=locale_number($totalWeight,0)." / ".$cab->MaxWeight." Kg";
			$labelpo=locale_number($totalWatts/1000,2)." / ".$cab->MaxKW." kW";
			$labelte=(($currentTemperature>0)?locale_number($currentTemperature,0)."&deg; (".$lastRead.")":__("no data"));
			$labelhu=(($currentHumidity>0)?locale_number($currentHumidity,0)." % (".$lastRead.")":__("no data"));
			$labelrp=(($RPlastRead!='0')?locale_number($currentRealPower/1000,2)." / ".$cab->MaxKW." kW (".$RPlastRead.")":__("no data"));
			
			$tooltip="<span>$cab->Location</span><ul>\n";
			$tooltip.="<li class=\"$scolor\">".__("Space").": ".$labelsp."</li>\n";
			$tooltip.="<li class=\"$wcolor\">".__("Weight").": ".$labelwe."</li>\n";
			$tooltip.="<li class=\"$pcolor\">".__("Calculated Power").": ".$labelpo."</li>\n";
			$tooltip.="<li class=\"$rpcolor\">".__("Measured Power").": ".$labelrp."</li>\n";
			$tooltip.="<li class=\"$tcolor\">".__("Temperature").": ".$labelte."</li>\n";
			$tooltip.="<li class=\"$hcolor\">".__("Humidity").": ".$labelhu."</li></ul>\n";

			$tooltip="<div>$tooltip</div>";
			print $tooltip;
			exit;
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
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lte IE 8]>
    <link rel="stylesheet"  href="css/ie.css" type="text/css">
    <?php if(isset($ie8fix)){print $ie8fix;} ?>
    <script src="scripts/excanvas.js"></script>
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript">
	$(document).ready(function(){
		$('#mapCanvas').css('width', $('.canvas > img[alt="clearmap over canvas"]').width()+'px');
		$('#mapCanvas').parent('.canvas').css('width', $('.canvas > img[alt="clearmap over canvas"]').width()+'px');

		$('map[name="datacenter"] area').mouseenter(function(){
			var pos=$('.canvas').offset();
			var despl=$(this).attr('coords');
			var coor=despl.split(',');
			var tx=parseInt(pos.left)+parseInt(coor[2])+17;
			var ty=parseInt(pos.top)+(parseInt(coor[1])+parseInt(coor[3]))/2-17;
			var tooltip=$('<div />').css({
				'left':tx+'px',
				'top':ty+'px'
			}).addClass('arrow_left border cabnavigator tooltip').attr('id','tt').append('<span class="ui-icon ui-icon-refresh rotate"></span>');
			var id=$(this).attr('href');
			id=id.substring(id.lastIndexOf('=')+1,id.length);
			$.post('',{tooltip: id}, function(data){
				tooltip.html(data);
			});
			$('body').append(tooltip);
			$(this).mouseleave(function(){
				tooltip.remove();
			});
		});
	});
  </script>
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
	<h3>',__("Zone Statistics"),'</h3>
  </div>
</div>
<div class="center"><div>
<div class="centermargin" id="dcstats">
<div class="table border">
  <div class="title">',$zone->Description,' (',$dc->Name,')</div>
  <div>
	<div></div>
	<div>',__("Infrastructure"),'</div>
	<div>',__("Occupied"),'</div>
	<div>',__("Allocated"),'</div>
	<div>',__("Available"),'</div>
  </div>
  <div>
	<div>',sprintf(__("Total U")." %5d",$zoneStats["TotalU"]),'</div>
	<div>',sprintf("%3d",$zoneStats["Infrastructure"]),'</div>
	<div>',sprintf("%3d",$zoneStats["Occupied"]),'</div>
	<div>',sprintf("%3d",$zoneStats["Allocated"]),'</div>
	<div>',sprintf("%3d",$zoneStats["Available"]),'</div>
  </div>
  <div>
	<div>',__("Percentage"),'</div>
	<div>',(($zoneStats["TotalU"])?sprintf("%3.1f%%",$zoneStats["Infrastructure"]/$zoneStats["TotalU"]*100):"0"),'</div>
	<div>',(($zoneStats["TotalU"])?sprintf("%3.1f%%",$zoneStats["Occupied"]/$zoneStats["TotalU"]*100):"0"),'</div>
	<div>',(($zoneStats["TotalU"])?sprintf("%3.1f%%",$zoneStats["Allocated"]/$zoneStats["TotalU"]*100):"0"),'</div>
	<div>',(($zoneStats["TotalU"])?sprintf("%3.1f%%",$zoneStats["Available"]/$zoneStats["TotalU"]*100):"0"),'</div>
  </div>
  </div> <!-- END div.table -->
  <div class="table border">
  <div>
        <div>',__("Computed Wattage"),'</div>
        <div>',sprintf("%7d %s", $zoneStats["ComputedWatts"], __("Watts")),'</div>
  </div>
  <div>
		<div>',__("Measured Wattage"), '</div>
		<div>',sprintf("%7d %s", $zoneStats["MeasuredWatts"], __("Watts")),'</div>
  </div>
  <div>
        <div>',__("BTU Computation from Computed Watts"),'</div>
        <div>',sprintf("%8d ".__("BTU"),$zoneStats["ComputedWatts"]*3.412 ),'</div>
  </div>
  <div>
        <div>',__("Zone Size (approximate)"),'</div>
        <div>',sprintf("%8d %s",$dc->SquareFootage*$prop_zone_dc, $vol),'</div>
  </div>
  <div>
        <div>',$density,' (',__("approximate"),')</div>
        <div>',(($dc->SquareFootage)?sprintf("%8d ".__("Watts"),$zoneStats["ComputedWatts"]/$dc->SquareFootage/$prop_zone_dc):"0 ".__("Watts")),'</div>
  </div>
  <div>
        <div>',__("Minimum Cooling Tonnage (Based on Computed Watts)"),'</div>
        <div>',sprintf("%7d ".__("Tons"),$zoneStats["ComputedWatts"]*3.412*1.15/12000),'</div>
  </div>
</div> <!-- END div.table -->
</div> <!-- END div.centermargin -->
<br>
<div id="maptitle"><span></span><div class="nav">';

$select='<select>';
	foreach(array(
		'loadCanvas' => __("Overview"),
		'space' => __("Space"),
		'weight' => __("Weight"),
		'power' => __("Calculated Power"),
		'realpower' => __("Measured Power"),
		'temperatura' => __("Temperature"),
		'humedad' => __("Humidity")
		) as $value => $option){
		$select.='<option value="'.$value.'">'.$option.'</option>';
	}
$select.='</select>';

echo $select.'</div></div>'.$zone->MakeImageMap();

?>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
	$(document).ready(function() {
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

  <?php print $zone->DrawCanvas();?>

		$('#maptitle .nav > select').change(function(){
			eval($(this).val()+'()');
		});

		loadCanvas();
		opentree();
	});
</script>
</body>
</html>
