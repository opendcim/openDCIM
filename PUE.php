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
				$list .= 'MP_'.$m;
			else
				$list .= ',MP_'.$m;
			$i++;
		}
		$name = "MPG_".$mpg->MPGID;
		$categoryList="";
		foreach($categories as $c) {
			$selected = (isset($_POST[$name]) && $c == $_POST[$name])?"selected":"";
			$categoryList .= '<option value="'.$c.'" '.$selected.'>'.$c.'</option>';
		}
		$equipmentList["MPG"] .= '<li class="equipmentBox">
							<label for="'.$name.'" style="float: left; margin-top: 6px;">'.$mpg->Name.'</label>
							<select name="'.$name.'" id="'.$name.'" list="'.$list.'" onChange="OnChangeCategory(this)">
								'.$categoryList.'
							</select>
						</li>';
	}

	//load measure points to update categories and calculate pie/PUE values
	$nbInput = 0;
	$nbOutput = 0;
	$mpDistribution = array("none"=>"", "IT"=>"", "Mech"=>"", "UPSI"=>"", "UPSO"=>"", "Other"=>"");
	foreach($mpList as $mp) {
		$name = "MP_".$mp->MPID;
		$category = (isset($_POST[$name]))?$_POST[$name]:$mp->Category;
		$categoryList = "";
		foreach($categories as $c) {
			if($c == $category)
				$categoryList .= "<option value=\"$c\" selected>$c</option>\n";
			else
				$categoryList .= "<option value=\"$c\">$c</option>\n";
		}
		$equipmentList["MP"] .= '<li class="equipmentBox">
					<label for="'.$name.'" style="float: left; margin-top: 6px;">'.$mp->Label.'</label>
					<select name="'.$name.'" id="'.$name.'">
						'.$categoryList.'
					</select>
				</li>';
		$mpDistribution[$category] .= (strlen($mpDistribution[$category]) > 0)?",".$mp->MPID:$mp->MPID;

	
		$measureList = new ElectricalMeasure();
		$measureList->MPID = $mp->MPID;
		$measureList = $measureList->GetMeasuresOnInterval($startdate, $enddate);

		$energyType = new EnergyType();
		$energyType->EnergyTypeID = $mp->EnergyTypeID;
		$energyType->GetEnergyType();

		$energy[$category] += $measureList[count($measureList)-1]->Energy - $measureList[0]->Energy;
		
		if($mp->UPSPowered == 0 && ($category == "IT" || $category == "Cooling" || $category == "Other Mechanical")) {
                        $energy['No UPS'] += $measureList[count($measureList)-1]->Energy - $measureList[0]->Energy;
                        $energy['CO2'] += ($measureList[count($measureList)-1]->Energy - $measureList[0]->Energy) * $energyType->GasEmissionFactor;
                } else if($category == "UPS Input") {
                        $energy['CO2'] += ($measureList[count($measureList)-1]->Energy - $measureList[0]->Energy) * $energyType->GasEmissionFactor;
                }
	
		if($category == "UPS Input")
			$nbInput++;
		else if($category == "UPS Output")
			$nbOutput++;
	}

	if($energy['IT'] != 0) {
		$pue = round(($energy['UPS Input'] + $energy['No UPS']) / $energy['IT'],2);
		$cue = round($energy['CO2'] / $energy['IT'], 2);
	} else {
		$pue = 0;
		$cue = 0;
	}

	if($energy['UPS Input'] !=0 || $energy['No UPS']) {
		$gec = round($energy['Renewable Energy'] / ($energy['UPS Input'] + $energy['No UPS']),2);
		$erf = round($energy['Energy Reuse'] / ($energy['UPS Input'] + $energy['No UPS']),2);
		$dcie = round($energy['IT'] / ($energy['UPS Input'] + $energy['No UPS']) * 100, 2);
	} else {
		$gec = 0;
		$erf = 0;
		$dcie = 0;
	}


	$mpDistributionGet = "&IT=".$mpDistribution["IT"]."&Cooling=".$mpDistribution["Cooling"]."&Mech=".$mpDistribution["Other Mechanical"]."&UPSI=".$mpDistribution["UPS Input"]."&UPSO=".$mpDistribution["UPS Output"];
	$pieDataGet = "&IT=".$energy['IT']."&Cooling=".$energy['Cooling']."&Mech=".$energy['Other Mechanical']."&UPSI=".$energy['UPS Input']."&UPSO=".$energy['UPS Output']."&noUPS=".$energy['No UPS'];

	if($nbInput != $nbOutput)
		$warning .= '<span TITLE="'.__("The number of UPS Inputs is diferent from the number of UPS Outputs.").'">
					<img src="images/warning.png" />
				</span>';
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
	height: 600px;
	width: 300px;
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
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
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
					',$warning,'
				</td></tr>
				<tr><td>
					<img src="PUEGraph.php?startdate=',$startdate,'&enddate=',$enddate,'&frequency=daily&height=600&width=900',$mpDistributionGet,'" alt="">
				</td>
				<td style="vertical-align: top;">
					<img src="pieGraph.php?startdate=',$startdate,'&enddate=',$enddate,'&puelevel=',$dc->PUELevel,'&pueperiod=',$pueperiod,'&puefrequency=',$dc->PUEFrequency,'&height=500',$pieDataGet,'" alt="">
					<table class="indicatorTable">
						<tr>
							<td>PUE <sub>'.$dc->PUELevel.','.$pueperiod.$dc->PUEFrequency.'</sub></td>
							<td>'.$pue.'</td>
						</tr>
						<tr>
							<td>DCiE</td>
							<td>'.$dcie.' %</td>
						</tr>
						<tr>
							<td>CUE</td>
							<td>'.$cue.' kgCO<sub>2</sub>e/kW.h</td>
						</tr>
						<tr>
							<td>ERF</td>
							<td>'.$erf.'</td>
						</tr>
						<tr>
							<td>GEC</td>
							<td>'.$gec.'</td>
						</tr>
					</table>
				</td></tr>
			</table>
			<div style="display: -moz-groupbox;">
				<ul class="scrollable equipmentList">
					<div id="none_MPG" style="background: beige;">
                                        	<li><div class="equipmentBox"><center>'.__("Measure Point Groups").'</center></div></li>
                                		',$equipmentList["MPG"],'
					</div>
					<div id="none_MP" style="background: bisque;">
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

function OnChangeCategory(element) {
	var list = element.getAttribute("list").split(",");
	for(var n=0; n<list.length; n++) {
		if(document.getElementById(list[n]))
			document.getElementById(list[n]).selectedIndex = element.selectedIndex;
	}
}
</script>
</body>
</html>
