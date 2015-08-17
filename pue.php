<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$subheader=__("PUE");

	//saving new categories if asked
	if(isset($_POST['save']) && $_POST['save'] == "Save") {
		$mpList = new ElectricalMeasurePoint();
		$mpList = $mpList->GetMPList();
		foreach($mpList as $mp) {
			$name = "MP_".$mp->MPID;
			$mp->Category = $_POST[$name];
			$mp->UpdateMP();
		}
	}

	//get the dates if exist
	if(isset($_POST['startdate'])) {
		$startdate = $_POST['startdate'];
		setcookie('startdate', $startdate);
	} else {
		$startdate = getStartDate($config->ParameterArray["TimeInterval"], false);
	}
	if(isset($_POST['enddate'])) {
		$enddate = $_POST['enddate'];
		setcookie('enddate', $enddate);
	} else {
		$enddate = getEndDate($config->ParameterArray["TimeInterval"], false);
	}

	//create the dc list
	$dcList = new DataCenter();
	$dcList = $dcList->GetDCList();

	$dcSelect = "";
	$dc = $dcList[0];
	foreach($dcList as $row) {
		if(isset($_POST["datacenterid"]) && $row->DataCenterID == $_POST["datacenterid"]) {
			$dcSelect .= '<option value="'.$row->DataCenterID.'" selected>'.$row->Name.'</option>';
			$dc = $row;
		}
		else
			$dcSelect .= '<option value="'.$row->DataCenterID.'">'.$row->Name.'</option>';
	}	

	//choose the appropriate PUE index for the period
	$interval = strtotime($enddate) - strtotime($startdate);
	if($interval <= strtotime("1970-01-02 01:00:00")) {
		//for a day
		$pueperiod = 'D';
		$validPUEfrequencies = array('D', 'C');
	} else if($interval <= strtotime("1970-01-08 01:00:00")) {
		//for a week
		$pueperiod = 'W';
		$validPUEfrequencies = array('D', 'C');
	} else if($interval <= strtotime("1970-02-01 01:00:00")) {
		//for a month
		$pueperiod = 'M';
		$validPUEfrequencies = array('W', 'D', 'C');
	} else {
		//for a year
		$pueperiod = 'Y';
		$validPUEfrequencies = array('M', 'W', 'D', 'C');
	}

	if($dc->PUEFrequency == '-')
		//no frequency
		$pueperiod = '';
	else if(!in_array($dc->PUEFrequency, $validPUEfrequencies))
		$warning .= '<span TITLE="'.__("Interval choosed is too small compared to measurement frequency.").'">
					<img src="images/warning.png" />
				</span>';

	//load measure point groups to update categories
	$mpgList = new MeasurePointGroup();
	$mpgList = $mpgList->GetMeasurePointGroupsByDC($dc->DataCenterID);
	$mpList = new ElectricalMeasurePoint();
	$mpList->DataCenterID = $dc->DataCenterID;
	$mpList = $mpList->GetMeasurePointsByDC();

	$categories = array('none', 'IT', 'Cooling', 'Other Mechanical', 'UPS Input', 'UPS Output', 'Energy Reuse', 'Renewable Energy');

	foreach($mpgList as $mpg) {
		$i=0;
		$list="";
		foreach($mpg->MPList as $m) {
			if($i == 0)
				$list .= $m;
			else
				$list .= ','.$m;
			$i++;
		}
		$name = "MPG_".$mpg->MPGID;
		$categoryList="";
		foreach($categories as $c) {
			$selected = (isset($_POST[$name]) && $c == $_POST[$name])?"selected":"";
			$categoryList .= '<option value="'.$c.'" '.$selected.'>'.__($c).'</option>';
		}
		$equipmentList["MPG"] .= '<li class="equipmentBox">
							<label for="'.$name.'" style="float: left; margin-top: 6px;">'.$mpg->Name.'</label>
							<select name="'.$name.'" id="'.$name.'" list="'.$list.'" onChange="OnChangeGroupCategory(this)">
								'.$categoryList.'
							</select>
						</li>';
	}

	//load measure points to update categories and calculate pie/PUE values
	foreach($mpList as $mp) {
		$name = "MP_".$mp->MPID;
		$category = (isset($_POST[$name]))?$_POST[$name]:$mp->Category;
		$upsPowered = (isset($_POST[$name]))?isset($_POST[$name."_box"]):$mp->UPSPowered;
		$categoryList = "";
		foreach($categories as $c) {
			if($c == $category)
				$categoryList .= "<option value=\"$c\" selected>".__($c)."</option>\n";
			else
				$categoryList .= "<option value=\"$c\">".__($c)."</option>\n";
		}
		if($upsPowered && $category != "UPS Input" && $category != "UPS Output")
			$checkboxOptions = "checked";
		else
			$checkboxOptions = "";
		if($category != "IT" && $category != "Cooling" && $category != "Other Mechanical")
			$checkboxOptions .= " readOnly disabled";

		$equipmentList["MP"] .= '<li class="equipmentBox">
					<input type="number" value="'.$mp->MPID.'" hidden>
					<input type="number" value="'.$mp->EnergyTypeID.'" hidden>
					<label for="'.$name.'" style="float: left; margin-top: 6px;">'.$mp->Label.'</label>
					<select name="'.$name.'" id="'.$name.'" onChange="OnChangeCategory(this, true)">
						'.$categoryList.'
					</select><br>
					<label for="'.$name.'_box">'.__("UPS Powered").'</label>
					<input type="checkbox" name="'.$name.'_box" id="'.$name.'_box" mpid="'.$mp->MPID.'" '.$checkboxOptions.' onChange="OnChangeUPSPowered();">
				</li>';
	}
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
.equipmentList
{
	border: 1px solid grey;
	height: 500px;
	width: 320px;
}
.equipmentBox
{
        border: 1px solid grey;
	padding: 2px;
	text-align: right;
}
.graphTable
{
	background-color: white;
	float: left;
}
.indicatorTable
{
	width: 100%;
	border: 2px solid darkgrey;
	font-size: 16px;
	text-align: center;
}
.indicatorTable > tbody > tr > td
{
	padding: 1px;
	border: 1px solid grey;

}
sub
{
        vertical-align: text-bottom;
        font-size: smaller;
}

</style>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM PUE</title>
  <!--[if lt IE 9]>
  <link rel="styilesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script language="javascript" type="text/javascript" src="scripts/jqplot/jquery.jqplot.min.js"></script>
  <script type="text/javascript" src="scripts/jqplot/plugins/jqplot.enhancedLegendRenderer.min.js"></script>
  <script type="text/javascript" src="scripts/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>
  <script type="text/javascript" src="scripts/jqplot/plugins/jqplot.pieRenderer.min.js"></script>

  <link rel="stylesheet" type="text/css" href="scripts/jqplot/jquery.jqplot.css" />
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page PUE">
<?php include( "sidebar.inc.php" );?>
	<div class="main">
		<form method="POST">
			<table class="graphTable">
<?php

echo '				<tr><td>
					<labelfor="startdate">'.__("Start date").' : </label>
					<input type="text" id="startdate" name ="startdate" value="',$startdate,'" />
					<label for="enddate">'.__("End date").' : </label>
					<input type="text" id="enddate" name="enddate" value="',$enddate,'" />
					<select name="datacenterid" onChange="submit();">',$dcSelect,'</select>
					<button type="submit">',__("Load measures"),'</button>
					<span TITLE="'.__("The number of UPS Inputs is diferent from the number of UPS Outputs.").'" id="warningspan">
                                        	<img src="images/warning.png" />
                                	</span>
				</td></tr>
				<tr><td>
					<div id="linechart" style="height: 500px; width: 900px; margin: 3px;"></div>
				</td>
				<td style="vertical-align: top;">
					<div id="piechart" style="height: 400px; width: 400px; margin: 3px;"></div>
					<table class="indicatorTable">
						<tr>
							<td>PUE <sub>'.$dc->PUELevel.','.$pueperiod.$dc->PUEFrequency.'</sub></td>
							<td><metric id="pue">0</metric></td>
						</tr>
						<tr>
							<td>DCiE</td>
							<td><metric id="dcie">0</metric> %</td>
						</tr>
						<tr>
							<td>CUE</td>
							<td><metric id="cue">0</metric> kgCO<sub>2</sub>e/kW.h</td>
						</tr>
						<tr>
							<td>ERF</td>
							<td><metric id="erf">0</metric></td>
						</tr>
						<tr>
							<td>GEC</td>
							<td><metric id="gec">0</metric></td>
						</tr>
					</table>
				</td></tr>
			</table>
			<div style="/*display: -moz-groupbox;*/">
				<ul class="scrollable equipmentList">
					<div id="MPG_list" style="background: beige;">
                                        	<li><div class="equipmentBox"><center>'.__("Measure Point Groups").'</center></div></li>
                                		',$equipmentList["MPG"],'
					</div>
					<div id="MP_list" style="background: bisque;">
                                        	<li><div class="equipmentBox"><center>'.__("Measure Points").'</center></div></li>
						',$equipmentList["MP"],'
					</div>
                       		</ul>
				<div>
					<center><button type="submit" name="save" value="Save">',__("Save modifications"),'</button></center>
				</div>
			</div>';
?>
		</form>

<?php echo '<br><a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>

	</div>
</div>
<script type="text/javascript">
$(function(){
	$('#startdate').datepicker({dateFormat: "yy-mm-dd"});
	$('#enddate').datepicker({dateFormat: "yy-mm-dd"});
});

//events

function OnChangeGroupCategory(element) {
	var list = element.getAttribute("list").split(",");
	var subElement;

	for(var n=0; n<list.length; n++) {
		if(subElement = document.getElementById('MP_'+list[n])) {
			subElement.selectedIndex = element.selectedIndex;
			OnChangeCategory(subElement, false);
		}
	}
	renderLinechart();
	renderPiechart();
	renderMetricTable();
	checkUPS_IO();
}

function OnChangeCategory(element, render) {
	var mpid = element.parentElement.children[0].value;
	var category = categories[mpTab[mpid].categoryElement.options[mpTab[mpid].categoryElement.selectedIndex].value];

	if(category == categories["UPS Input"] || category == categories["UPS Output"] || category < 0) {
		mpTab[mpid].UPSPoweredElement.checked = false;
		mpTab[mpid].UPSPoweredElement.disabled = true;
		mpTab[mpid].UPSPoweredElement.readOnly = true;
	} else {
		mpTab[mpid].UPSPoweredElement.disabled = false;
                mpTab[mpid].UPSPoweredElement.readOnly = false;
	}

	if(render) {
		renderLinechart();
		renderPiechart();
		renderMetricTable();
		checkUPS_IO();
	}
}

function OnChangeUPSPowered() {
	renderLinechart();
        renderPiechart();
	renderMetricTable();
}

//some variables to create graphs

var start;	//start date
var end;	//end date

var mpTab;	//measure points data table

var linechart;	//line graph
var piechart;	//pie graph

//values used to identify each category
var categories = {	"none": -1,
			"UPS Input": 0,
                        "UPS Output": 1,
                        "Other Mechanical": 2,
                        "Cooling": 3,
                        "IT": 4,
                        "Energy Reuse": -2,
                        "Renewable Energy": -3};

//loading CO2 for each energyType
<?php
$energyTypeList = new EnergyType();
$energyTypeList = $energyTypeList->GetEnergyTypeList();

echo "var energyTypes = {";

$n=0;
foreach($energyTypeList as $energyType) {
	$coma=($n!=0)?' ,':'';
	echo $coma.$energyType->EnergyTypeID.': '.$energyType->GasEmissionFactor;
	$n++;
}

echo "};";
?>

//MPData class
function MPData(id, energyType) {
	this.mpid = id;								//measurePoint's id
	this.energyTypeId = energyType;						//EnergyTypeID
	this.lineData = new Array();						//data for the line plot
	this.pieData = 0;							//data for the pie plot
	this.categoryElement = document.getElementById("MP_"+id);		//reference for the select box (category)
	this.UPSPoweredElement = document.getElementById("MP_"+id+"_box");	//reference for the check box (UPSPowered)
}
//static attributes to synchronize ajax execution (we wait for each query to end before rendering graphs)
MPData.nbToLoad;
MPData.nbLineLoaded;
MPData.nbPieLoaded;
//initialize static attributes
MPData.initializeLoading = function(nbElement) {
	MPData.nbToLoad = nbElement;
	MPData.nbLineLoaded = 0;
	MPData.nbPieLoaded = 0;
};
//ajax queries
MPData.prototype.loadData = function() {
	var mp = this;

	var category = categories[this.categoryElement.options[this.categoryElement.selectedIndex].value];
	if(category == categories["none"]) {
		this.lineData = new Array();
		this.pieData = 0;

		MPData.nbLineLoaded++;
		MPData.nbPieLoaded++;
		return;
	}

	$.ajax({url: 'scripts/ajax_graphs.php', 
		data: {type: "energy", id: this.mpid, startdate: start.value, enddate: end.value, graphtype: "line", frequency: "daily"},
		type: "POST",
		success: function(data) {
			mp.lineData = JSON.parse(data);
			MPData.nbLineLoaded++;
			if(MPData.nbLineLoaded == MPData.nbToLoad)
				renderLinechart();
		}
	});

	$.ajax({url: 'scripts/ajax_graphs.php', 
                data: {type: "energy", id: this.mpid, startdate: start.value, enddate: end.value, graphtype: "pie"},
                type: "POST",
                success: function(data) {
                        mp.pieData = parseInt(data);
                        MPData.nbPieLoaded++;
                        if(MPData.nbPieLoaded == MPData.nbToLoad) {
                                renderPiechart();
				renderMetricTable();
			}
                }
        });

};

//page initialization
$(document).ready(function() {
	var mpList = document.getElementById("MP_list").children;

	start = document.getElementById("startdate");
	end = document.getElementById("enddate");

	mpTab = new Object();

	for(var n=1; n<mpList.length; n++) {
		mpTab[mpList[n].children[0].value] = new MPData(mpList[n].children[0].value, mpList[n].children[1].value);
	}

	MPData.initializeLoading(Object.keys(mpTab).length);
	for(var n in mpTab) {
		mpTab[n].loadData();
	}

	checkUPS_IO();
});

//render line plot
function renderLinechart() {
	var linechartData = Array();
	var cntTab = new Array();
	var cntData;
	var end;

	var firstDate = Infinity;
	var lastDate = -Infinity;
	var currentDate;
	var nextDate;
	var dateString = "";

	var category;
	var value;

	var nbTicks;

	if(linechart) {
		linechart.destroy();
	}

	//before we need to compute data

	for(var n in mpTab) {
		if(0 in mpTab[n].lineData) {
			if(mpTab[n].lineData[0][0] < firstDate)
				firstDate = mpTab[n].lineData[0][0];
			cntTab[n] = 0;
		} else {
			delete mpTab[n];
		}
	}

	for(var n=0; n<= categories["IT"]; n++) {
		linechartData[n] = new Array();
        }

	end = false;
	nextDate = firstDate;
	cntData = 0;

	while(!end) {
		end = true;
		currentDate = nextDate;
		dateString = new Date(nextDate * 1000).toString();
                lastDate = nextDate;
		nextDate = Infinity;

		for(var n=0; n<= categories["IT"]; n++) {
			linechartData[n][cntData] = new Array();
			linechartData[n][cntData][0] = dateString;
			linechartData[n][cntData][1] = 0;
		}

		for(var n in mpTab) {
			category = categories[mpTab[n].categoryElement.options[mpTab[n].categoryElement.selectedIndex].value];
			if(category >= 0) {
				if(cntTab[n] in mpTab[n].lineData) {
					if(mpTab[n].lineData[cntTab[n]][0] == currentDate) {
						value = mpTab[n].lineData[cntTab[n]][1];
						linechartData[category][cntData][1] += value;

						if(!mpTab[n].UPSPoweredElement.checked && category != categories["UPS Input"] && category != categories["UPS Output"]) {
							linechartData[categories["UPS Input"]][cntData][1] += value;
                                                        linechartData[categories["UPS Output"]][cntData][1] += value;
						}

						if(category == categories["Cooling"]) {
							linechartData[categories["Other Mechanical"]][cntData][1] += value;
						}

						if(category == categories["IT"]) {
                                                        linechartData[categories["Other Mechanical"]][cntData][1] += value;
							linechartData[categories["Cooling"]][cntData][1] += value;
                                                }

						if(cntTab[n]+1 in mpTab[n].lineData) {
							if(mpTab[n].lineData[cntTab[n]+1][0] < nextDate)
								nextDate = mpTab[n].lineData[cntTab[n]+1][0];
							end = false;
						}
						cntTab[n]++;
					}
				}
			}
		}
		cntData++;
	}

	//firstDate = new Date(firstDate * 1000).toString();
	nbTicks = Math.round((lastDate - firstDate) / 86400)+1;
	if(nbTicks > 12)
		nbTicks = 12;
	
	n=0;
	var pueMax = -Infinity;
	var pueMin = Infinity;
	linechartData[5] = new Array();
	while((n in linechartData[categories["UPS Input"]]) && (n in linechartData[categories["IT"]])) {
		linechartData[5][n] = new Array();
		linechartData[5][n][0] = linechartData[categories["UPS Input"]][n][0];

		if(linechartData[categories["IT"]][n][1] != 0)
			linechartData[5][n][1] = linechartData[categories["UPS Input"]][n][1] / linechartData[categories["IT"]][n][1];
		else
			linechartData[5][n][1] = 0;

		if(linechartData[5][n][1] > pueMax)
			pueMax = linechartData[5][n][1];

		if(linechartData[5][n][1] < pueMin)
                        pueMin = linechartData[5][n][1];
		n++;
	}
	pueMax = Math.ceil(pueMax + 0.1);
	pueMin = Math.floor(pueMin - 0.1);

	//lets create the line plot

	linechart=$.jqplot('linechart',  linechartData, {
		seriesDefaults:{
			showMarker: false,
			fill:true,
			fillAndStroke:true,
		},
		axes:{
			yaxis:{
				min:0
			},
			y2axis:{
				min:pueMin,
				max: pueMax,
				tickInterval: (pueMax - pueMin) / 10,
				tickOptions:{
					showGridline: false
				}
			},
			xaxis:{
				min: new Date(firstDate * 1000).toString(),
				max: new Date(lastDate * 1000).toString(),
				numberTicks: nbTicks,
				renderer:$.jqplot.DateAxisRenderer,
				tickOptions:{
					formatString: "%Y-%m-%d"
				}
			}
		},
		series: [{
				label: "<?php echo __("Power sources loss");?>",
				color: 'lightseagreen'
			},
			{
				label: "<?php echo __("Other");?>",
			 	color: 'wheat'
			},
			{
				label: "<?php echo __("Other Mechanical Devices");?>",
				color: 'lightcoral'
			},
			{
				label: "<?php echo __("Cooling");?>",
				color: 'gold'
			},
			{
				label: "<?php echo __("IT");?>",
				color: 'mediumpurple'
			},
			{
				label: "<?php echo __("PUE");?>",
				color: 'black',
				yaxis: 'y2axis',
				fill: false,
				fillAndStroke: false
			}],
		legend:{
			renderer: $.jqplot.EnhancedLegendRenderer,
			border: "none",
			fontSize: "0.4cm",
			show: true,
			location: 'n',
			placement: 'outsideGrid',
			rendererOptions: {
        			numberRows: 1,
				seriesToggle: false
    			}
		}
	});
}

//render the pie plot
function renderPiechart() {
	var piechartData = new Array();
	var total = 0;
	var labelTab = new Array();
	var category;

	//compute the data

	if(piechart) {
                piechart.destroy();
        }

	for(var n=0; n<=categories["IT"]; n++)
		piechartData[n] = 0;

	for(var n in mpTab) {
		category = categories[mpTab[n].categoryElement.options[mpTab[n].categoryElement.selectedIndex].value];
		if(category >= 0) {
			piechartData[category] += mpTab[n].pieData;

			if(!mpTab[n].UPSPoweredElement.checked && category != categories["UPS Input"] && category != categories["UPS Output"]) {
				piechartData[categories["UPS Input"]] += mpTab[n].pieData;
				piechartData[categories["UPS Output"]] += mpTab[n].pieData;
			}
		}
	}

	piechartData[categories["UPS Input"]] -= piechartData[categories["UPS Output"]];
	piechartData[categories["UPS Output"]] -= piechartData[categories["Other Mechanical"]] + piechartData[categories["Cooling"]] + piechartData[categories["IT"]];
	piechartData.reverse();

	for(var n in piechartData) {
		if(piechartData[n] < 0)
			piechartData[n] = 0;
		total += piechartData[n];
	}

	for(var n in piechartData) {
		labelTab[n] = piechartData[n]+" kW.h<br>"+(piechartData[n] * 100 / total).toFixed(2)+" %";
	}

	//create the pie plot

	piechart = $.jqplot('piechart', [piechartData], {
		seriesDefaults:{
			renderer: $.jqplot.PieRenderer,
			rendererOptions: {
				showDataLabels: true,
				dataLabels: labelTab
			}
		},
		grid:{
			borderWidth: 0,
			shadow: false
		},
		seriesColors:[
			'mediumpurple',
			'gold',
			'lightcoral',
			'wheat',
			'lightseagreen']
	});
}

//update the values of the table under the pie plot
function renderMetricTable() {
	var pue = document.getElementById("pue");
	var dcie = document.getElementById("dcie");
	var cue = document.getElementById("cue");
	var gec = document.getElementById("gec");
	var erf = document.getElementById("erf");

	var category;

	var carbon = 0;
	var renewable = 0;
	var reuse = 0;
	var it = 0;
	var upsi = 0;

	for(var n in mpTab) {
		category = categories[mpTab[n].categoryElement.options[mpTab[n].categoryElement.selectedIndex].value];

		if((!mpTab[n].UPSPoweredElement.checked && category != categories["UPS Output"] && category >= 0) || category == categories["UPS Input"])
			carbon += mpTab[n].pieData * energyTypes[mpTab[n].energyTypeId];

		if(category == categories["Renewable Energy"])
			renewable += mpTab[n].pieData;
		else if(category == categories["Energy Reuse"])
                        reuse += mpTab[n].pieData;
		else if(category == categories["IT"])
			it += mpTab[n].pieData;
		else if(category == categories["UPS Input"])
			upsi += mpTab[n].pieData;

		if(!mpTab[n].UPSPoweredElement.checked && category != categories["UPS Output"] && category >= 0 && category != categories["UPS Input"])
			upsi += mpTab[n].pieData;
	}

	if(it != 0) {
		pue.innerHTML = (upsi / it).toFixed(2);
		cue.innerHTML = (carbon / it).toFixed(2);
	} else {
		pue.innerHTML = "0";
		cue.innerHTML = "0";
	}

	if(upsi != 0) {
		dcie.innerHTML = (it / upsi * 100).toFixed(2);
		gec.innerHTML = (renewable / upsi).toFixed(2);
		erf.innerHTML = (reuse / upsi).toFixed(2);
	} else {
		dcie.innerHTML = "0";
		gec.innerHTML = "0";
		erf.innerHTML = "0";
	}
}

//display a warning if number of UPS inputs is different from UPS outputs
function checkUPS_IO(){
	var warningSpan = document.getElementById("warningspan");
	var nbUPSI = 0;
	var nbUPSO = 0;

	for(var n in mpTab) {
		if(categories[mpTab[n].categoryElement.options[mpTab[n].categoryElement.selectedIndex].value] == categories["UPS Input"])
			nbUPSI++;
		if(categories[mpTab[n].categoryElement.options[mpTab[n].categoryElement.selectedIndex].value] == categories["UPS Output"])
			nbUPSO++;
	}

	if(nbUPSI != nbUPSO)
		warningSpan.style.display = "";
	else
		warningSpan.style.display = "none";
}
</script>
</body>
</html>
