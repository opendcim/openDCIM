<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$subheader=__("Graph Generator");

	$measureTypeArray = array(	"power" => "elec",
					"energy" => "elec",
					"temperature" => "air",
					"humidity" => "air",
					"cooling" => "cooling",
					"fanspeed" => "cooling");

	function createEquipmentList($type, $side) {
		$mpgList = new MeasurePointGroup();
		$mpgList = $mpgList->GetMeasurePointGroupsByType($type);

		$class = MeasurePoint::$TypeTab[$type]."MeasurePoint";
		$mpList = new $class;
		$mpList = $mpList->GetMPList();

		$equipmentList = '<div id="MPG_list_'.$side.'" style="background: beige;">
					<li><div class="equipmentBox"><center>'.__("Measure Point Groups").'</center></div></li>';

		foreach($mpgList as $mpg) {
			$list="";
			$i=0;
			foreach($mpg->MPList as $m) {
				if($i == 0)
					$list .= $m;
				else
					$list .= ','.$m;
				$i++;
			}

			$name = $side."MPG_".$mpg->MPGID;
			$checked = ($_POST[$name])?"checked":"";
			$equipmentList .= '<li><div class="equipmentBox">
						<label class="equipmentLabel" for="'.$name.'">'.$mpg->Name.'</label>
						<input type="checkbox" name="'.$name.'" id="'.$name.'" list="'.$list.'" onChange="OnCheckGroup(this,\''.$side.'\')" '.$checked.'>
					</div></li>';
		}

		$equipmentList .= '</div>';
		
		$equipmentList .= '<div id="MP_list_'.$side.'"style="background: bisque;">
					<li><div class="equipmentBox"><center>'.__("Measure Points").'</center></div></li>';

		foreach($mpList as $mp) {
			$name = $side."MP_".$mp->MPID;
			$checked = ($_POST[$name])?"checked":"";
			$equipmentList .= '<li><div class="equipmentBox">
						<input type="number" value="'.$mp->MPID.'" hidden>
						<input type="text" id="'.$name.'_label" value="'.$mp->Label.'" hidden>
						<label class="equipmentLabel" for="'.$name.'">'.$mp->Label.'</label>
						<input type="checkbox" name="'.$name.'" id="'.$name.'" '.$checked.' onChange="OnCheckMP(this, \''.$side.'\');">
					</div></li>';
		}

		$equipmentList .= '</div>';
		return $equipmentList;
	}

	$measureTypes = array(	"none" => __("None"),
				"power" => __("Power"), 
				"energy" => __("Energy"),
				"temperature" => __("Temperature"),
				"humidity" => __("Humidity"),
				"cooling" => __("Compressor usage"),
				"fanspeed" => __("Fan Speed"));

	if(isset($_POST["lefttype"]))
		$leftType = $_POST["lefttype"];
	else
		$leftType = "power";

	if(isset($_POST["righttype"]))
		$rightType = $_POST["righttype"];
	else
		$rightType = "none";

	//avoid to display the selected type of the left ordinate in the right list
	foreach($measureTypes as $id => $val) {
		if($id != $rightType && $id != "none")
			$selectLeft[$id] = $val;
		if($id != $leftType)
			$selectRight[$id] = $val;
	}

	$optionLeft="";
	foreach($selectLeft as $id => $val) {
		if($id == $_POST['lefttype'])
			$optionLeft.='<option value="'.$id.'" selected>'.$val.'</option>';
		else
			$optionLeft.='<option value="'.$id.'">'.$val.'</option>';
	}

	$optionRight="";
	foreach($selectRight as $id => $val) {
		if($id == $_POST['righttype'])
			$optionRight.='<option value="'.$id.'" selected>'.$val.'</option>';
		else
			$optionRight.='<option value="'.$id.'">'.$val.'</option>';
	}

	$selectFrequency = array(	"hourly" => __("Hourly"), 
					"daily" => __("Daily"), 
					"monthly" => __("Monthly"), 
					"yearly" => __("Yearly"));

	if(isset($_POST['frequency']))
		$frequency = $_POST['frequency'];
	else
		$frequency = "hourly";

	$optionFrequency="";
	foreach($selectFrequency as $id => $val) {
		if($id == $frequency)
			$optionFrequency.='<option value="'.$id.'" selected>'.$val.'</option>';
		else
			$optionFrequency.='<option value="'.$id.'">'.$val.'</option>';
	}

	if($leftType == "energy" || $rightType == "energy")
		$displayFrequency = "";
	else
		$displayFrequency = "display: none;";

	if($leftType == "power" || $rightType == "power")
		$displaySplitPhases = "";
        else
                $displaySplitPhases = "display: none;";

	if(isset($_POST['startdate']))
		$startdate = $_POST['startdate'];
	else
		$startdate = getStartDate($config->ParameterArray["TimeInterval"], false);

	if(isset($_POST['enddate']))
		$enddate = $_POST['enddate'];
	else
		$enddate = getEndDate($config->ParameterArray["TimeInterval"], false);


	$leftList=createEquipmentList($measureTypeArray[$leftType], 'l');
	if($rightType != "none")
		$rightList=createEquipmentList($measureTypeArray[$rightType], 'r');
?>
<!doctype html>
<html>
<link rel="stylesheet" href="css/inventory.php" type="text/css">
<link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
<style>
.scrollable
{
	overflow: hidden;
	overflow-y: scroll;
}
.equipmentBox
{
	border: 1px solid grey;
	text-align: right;
}
.equipmentLabel
{
	text-align: left;
	float: left;
	padding: 3px;
}
</style>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Graph Generator</title>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script language="javascript" type="text/javascript" src="scripts/jqplot/jquery.jqplot.min.js"></script>
  <script type="text/javascript" src="scripts/jqplot/plugins/jqplot.enhancedLegendRenderer.min.js"></script>
  <script type="text/javascript" src="scripts/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>
  <script type="text/javascript" src="scripts/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
  <script type="text/javascript" src="scripts/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>

  <link rel="stylesheet" type="text/css" href="scripts/jqplot/jquery.jqplot.css" />
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page graphgenerator">
<?php
	include( "sidebar.inc.php" );



	echo '<div class="main">
		<form method="post"><br>
			<label for="startdate">',__("From"),' : </label>
			<input type="date" min="1970-01-01" max="9999-12-31" name="startdate" id="startdate" value="',$startdate,'"/>
			<label for="enddate">',__("to"),' : </label>
			<input type="date" min="1970-01-01" max="9999-12-31" name="enddate" id="enddate" value="',$enddate,'"/>
			<button type="submit" name="generate" value="true">',__("Generate"),'</button><br>
			<div style="'.$displayFrequency.'">
				<label for="frequency">'.__("Energy Measures Frequency").' : </label>
                        	<select id="frequency" name="frequency" onChange="submit();">
                                	'.$optionFrequency.'
                       		</select>
			</div>
			<div style="'.$displaySplitPhases.'">
				<label for="splitphases">'.__("Split Power Phases").' : </label>
                        	<input type="checkbox" name="splitphases" id ="splitphases" '.(($_POST['splitphases'])?"checked":"").' onChange="submit();">
			</div>
			<div class="table">
				<div>
					<div>
						<h3>'.__("Left ordinate").'</h3>
					</div>
					<div>
						<h3>'.__("Right ordinate").'</h3>
					</div>
				</div>
				<div>
					<div>
						<center>
						<select name="lefttype" id="lefttype" onChange="submit();">
							',$optionLeft,'
						</select>
						</center>
					</div>
					<div>
						<center>
						<select name="righttype" id="righttype" onChange="submit();">
							',$optionRight,'
						</select>
						</center>
					</div>
				</div>
				<div>
					<div>
						<ul class="scrollable" style="height: 600px; width: 220px; border: 1px solid grey;">
							',$leftList,'
						</ul>
					</div>
					<div>
						<ul class="scrollable" style="height: 600px; width: 220px; border: 1px solid grey;">
							',$rightList,'
						</ul>
					</div>
					<div id="graph" style="width: 100%; margin: 50px;"></div>
				</div>
			</div>
		</form>';
?>

</div>
</div>
<script type="text/javascript">

$(function(){
	$('#startdate').datepicker({dateFormat: "yy-mm-dd"});
	$('#enddate').datepicker({dateFormat: "yy-mm-dd"});
});

function OnCheckGroup(element, side) {
	var mp;
	var mpList = element.getAttribute("list").split(",");
	var mpToLoad = new Array();
	var table;

	if(side == 'l')
                table = left_mpTab;
        else
                table = right_mpTab;

	for(var n=0; n<mpList.length; n++) {
		mp = document.getElementById(side+"MP_"+mpList[n]);
		if(mp !== null) {
			mp.checked = element.checked;
			if(mp.checked && !(mpList[n] in table))
				mpToLoad.push(mpList[n]);
		}
	}
	if(mpToLoad.length > 0) {
		MPData.initializeLoading(mpToLoad.length);
		for(var n in mpToLoad) {
			table[mpToLoad[n]] = new MPData(mpToLoad[n], side);
			table[mpToLoad[n]].loadData();
		}
	} else if(mpToLoad.length == 0) {
		renderGraph();
	}
}

function OnCheckMP(element, side) {
	var id = element.parentElement.children[0].value;
	var table;

	if(side == 'l')
		table = left_mpTab;
	else
		table = right_mpTab;

	if(element.checked && !(id in table)) {
		table[id] = new MPData(id, side);
		MPData.initializeLoading(1);
		table[id].loadData();
	} else {
		renderGraph();
	}
}

var linechart;

var start;
var end;
var frequency;
var splitPhases;

var leftType;
var rightType;

var left_mpTab;
var right_mpTab;

var typeTable = {<?php
			$n=0;	
			foreach($measureTypes as $id => $type) {
				if($n == 0)
					echo '"'.$id.'": "'.$type.'"';
				else
					echo ',"'.$id.'": "'.$type.'"';
				$n++;
			}
		?>};

var unitTable = {
			"energy": "kW.h",
			"power": "W",
			"temperature": "<?php echo ($config->ParameterArray["mUnits"] == "english")?"°F":"°C"; ?>",
			"humidity": "%",
			"cooling": "%",
			"fanspeed": "%"
		};

function MPData(id, side) {
	this.mpid = id;
	this.label = document.getElementById(side+"MP_"+id+"_label").value;
	this.side = side;
	this.data = new Array();
	this.checkbox = document.getElementById(side+"MP_"+id);
}
MPData.nbToLoad;
MPData.nbLoaded;
MPData.initializeLoading = function(nbElement) {
	MPData.nbToLoad = nbElement;
	MPData.nbLoaded = 0;
};
MPData.prototype.loadData = function() {
	var mp = this;
	var type;

	if(this.side == 'l')
		type = leftType;
	else
		type = rightType;

	this.data = new Array();
	$.ajax({url: 'scripts/ajax_graphs.php', 
		data: {type: type, id: this.mpid, startdate: start.value, enddate: end.value, graphtype: "line",
			frequency: frequency.options[frequency.selectedIndex].value, splitphases: splitPhases.checked, datestring: ""},
		type: "POST",
		success: function(data) {
			mp.data = JSON.parse(data);
			//alert(mp.data);
			MPData.nbLoaded++;
			if(MPData.nbLoaded == MPData.nbToLoad)
				renderGraph();
		}
	});
};

$(document).ready(function() {
	var leftMPList;
	var rightMPList;
	var id;

	start = document.getElementById("startdate");
	end = document.getElementById("enddate");
	frequency = document.getElementById("frequency");
	splitPhases = document.getElementById("splitphases");

	leftType = document.getElementById("lefttype");
	leftType = leftType.options[leftType.selectedIndex].value;
	rightType = document.getElementById("righttype");
	rightType = rightType.options[rightType.selectedIndex].value;

	leftMPList = document.getElementById("MP_list_l").children;
	if(rightType != "none")
		rightMPList = document.getElementById("MP_list_r").children;
	else
		rightMPList = new Array();

	left_mpTab = new Object();
	right_mpTab = new Object();

	for(var n=1; n<leftMPList.length; n++) {
		id = leftMPList[n].children[0].children[0].value;
		//alert(id);
		if(document.getElementById("lMP_"+id).checked)
			left_mpTab[id] = new MPData(id, 'l');
	}

	for(var n=1; n<rightMPList.length; n++) {
		id = rightMPList[n].children[0].children[0].value;
		if(document.getElementById("rMP_"+id).checked)
                	right_mpTab[id] = new MPData(id,'r');
        }

	MPData.initializeLoading(Object.keys(left_mpTab).length + Object.keys(right_mpTab).length);

	for(var n in left_mpTab) {
		left_mpTab[n].loadData();
	}
	for(var n in right_mpTab) {
                right_mpTab[n].loadData();
        }

	//renderGraph();
});

function renderGraph() {
	var data = new Array();
	var series = new Array();
	var dateFormat;

	if(linechart)
		linechart.destroy();

	for(var n in left_mpTab) {
		if(left_mpTab[n].checkbox.checked) {
			if(leftType == "power" && splitPhases.checked) {
				data.push(left_mpTab[n].data[0]);
				data.push(left_mpTab[n].data[1]);
				data.push(left_mpTab[n].data[2]);
				series.push({label: "["+typeTable[leftType]+"] "+left_mpTab[n].label+" (phase 1)"});
				series.push({label: "["+typeTable[leftType]+"] "+left_mpTab[n].label+" (phase 2)"});
				series.push({label: "["+typeTable[leftType]+"] "+left_mpTab[n].label+" (phase 3)"});
			} else {
				data.push(left_mpTab[n].data);
				series.push({label: "["+typeTable[leftType]+"] "+left_mpTab[n].label});
			}
		}
	}

	for(var n in right_mpTab) {
		if(right_mpTab[n].checkbox.checked)
			if(rightType == "power" && splitPhases.checked) {
                                data.push(right_mpTab[n].data[0]);
                                data.push(right_mpTab[n].data[1]);
                                data.push(right_mpTab[n].data[2]);
				series.push({label: "["+typeTable[rightType]+"] "+right_mpTab[n].label+" (phase 1)", yaxis: "y2axis"});
				series.push({label: "["+typeTable[rightType]+"] "+right_mpTab[n].label+" (phase 2)", yaxis: "y2axis"});
				series.push({label: "["+typeTable[rightType]+"] "+right_mpTab[n].label+" (phase 3)", yaxis: "y2axis"});
                        } else {
                		data.push(right_mpTab[n].data);
				series.push({label: "["+typeTable[rightType]+"] "+right_mpTab[n].label, yaxis: "y2axis"});
			}
        }

	if(leftType == "energy" || rightType == "energy")
		switch(frequency.options[frequency.selectedIndex].value) {
			case "hourly":
				dateFormat = "<center>%Y-%m-%d<br>%H:%M:%S</center>";
				break;
			case "daily":
				dateFormat = "%Y-%m-%d";
				break;
			case "monthly":
				dateFormat = "%Y-%m-%d";
                                break;
			case "yearly":
				dateFormat = "%Y";
                                break;
			default:
				dateFormat = "%Y-%m-%d";
                                break;
		}
	else
		dateFormat = "%Y-%m-%d";

	if(data.length == 0)
		data = [[null]];

	linechart = $.jqplot('graph', data, {
		seriesDefaults:{
                        showMarker: false
                },
		axes:{
                        yaxis:{
                                min: 0,
				label: typeTable[leftType]+" ("+unitTable[leftType]+")",
				labelRenderer: $.jqplot.CanvasAxisLabelRenderer
                        },
                        y2axis:{
                                min: 0,
				label: typeTable[rightType]+" ("+unitTable[rightType]+")",
				labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                                tickOptions:{
                                        showGridline: false
                                }
                        },
                        xaxis:{
                                renderer:$.jqplot.DateAxisRenderer,
                                tickOptions:{
                                        formatString: dateFormat
                                }
                        }
                },
		series: series,
		legend: {
			renderer: $.jqplot.EnhancedLegendRenderer,
			fontSize: "0.4cm",
                        show: true,
                        location: 'e',
			placement: 'outsideGrid'
		}
	});
}

</script>
</body>
</html>
