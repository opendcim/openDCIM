<?php
require_once( "db.inc.php" );
require_once( "facilities.inc.php" );

$subheader=__("DCEM");

if(isset($_POST['startdate'])) {
        $startDate = $_POST['startdate'];
        setcookie('startdate', $startDate);
} else {
        $startDate = getStartDate($config->ParameterArray["TimeInterval"], false);
}

if(isset($_POST['enddate'])) {
        $endDate = $_POST['enddate'];
        setcookie('enddate', $endDate);
} else {
        $endDate = getEndDate($config->ParameterArray["TimeInterval"], false);
}

$weightReuse = isset($_POST["WREUSE"])?$_POST["WREUSE"]:0.5;
$weightRenewable = isset($_POST["WREN"])?$_POST["WREN"]:0.8;

$dcList = new DataCenter();
$dcList = $dcList->GetDCList();

$warningMsg = __("The difference between the start of measurement and the first measure or between the end of measurement and the last measure is too big!");
$intervalMax = (strtotime($endDate) - strtotime($startDate)) / 50 ; //time max between measurement start and first measure or measurement end and last measure

$minDate = INF;
$dataList = array();
foreach($dcList as $dc) {
	if(strtotime($dc->CreationDate) < $minDate)
                $minDate = strtotime($dc->CreationDate);

	$dataList["Reuse_Ratio"][$dc-DataCenterID] = (isset($_POST["Reuse_".$dc->DataCenterID."_Ratio"]))?$_POST["Reuse_".$dc->DataCenterID."_Ratio"]:0;

	$mpList = new ElectricalMeasurePoint();
        $mpList->DataCenterID = $dc->DataCenterID;
        $mpList = $mpList->GetMeasurePointsByDC();

	foreach($mpList as $mp) {
		$measureList = new ElectricalMeasure();
                $measureList->MPID = $mp->MPID;
                $measureList = $measureList->GetMeasuresOnInterval($startDate, $endDate);

		switch($mp->Category) {
			case "UPS Input":
				$category = "UPS";
				$addPenalty = true;
				break;
			case "IT":
				$category = "IT";
				$addPenalty = true;
				break;
			case "Energy Reuse":
				$category = "Reuse";
				$addPenalty = false;
				break;
			case "Renewable Energy":
				$category = "Renewable";
				$addPenalty = false;
				break;
			default:
				$category = "";
				$addPenalty = true;
				break;
		}

		if($category != "") {
			$energyName = $category."_".$dc->DataCenterID."_".$mp->MPID;		//UPS_1_15
			$penaltyName = "P".$category."_".$dc->DataCenterID."_".$mp->MPID;	//PUPS_1_15
			$labelName = $category."name_".$dc->DataCenterID."_".$mp->MPID;		//UPSname_1_15
			$updateFuncName = "update".$category;					//updateUPS

			$energy = $measureList[count($measureList)-1]->Energy - $measureList[0]->Energy;
			$penalty = isset($_POST[$penaltyName])?$_POST[$penaltyName]:0;

			$startGap = strtotime($measureList[0]->Date) - strtotime($startDate);
			$endGap = strtotime($endDate) - strtotime($measureList[count($measureList)-1]->Date);

			$dataList[$category][$dc->DataCenterID][$mp->MPID] = '<div><label for="'.$energyName.'">'.$mp->Label.'</label></div>
					<div><nobr><input type="number" id="'.$energyName.'" name="'.$energyName.'" value="'.$energy.'" step="0.001" min="0" onChange="'.$updateFuncName.'('.$dc->DataCenterID.')">kW.h</nobr></div>';

			if($addPenalty)
				$dataList[$category][$dc->DataCenterID][$mp->MPID] .= '<div><input type="number" id="'.$penaltyName.'" name="'.$penaltyName.'" value="'.$penalty.'" step="0.001" min="0" onChange="'.$updateFuncName.'('.$dc->DataCenterID.')"></div>';
			if(($startGap > $intervalMax || $endGap > $intervalMax) && count($measureList) >= 2)
				$dataList[$category][$dc->DataCenterID][$mp->MPID] .= '<div><span TITLE="'.$warningMsg.'"><img src="images/warning.png" alt="_!_"/></span></div>';

			$dataList[$category][$dc->DataCenterID][$mp->MPID] .= '	<input type="hidden" name="'.$labelName.'" value="'.$mp->Label.'">';
		}

		if($mp->UPSPowered == 0 && ($mp->Category == "IT" || $mp->Category == "Cooling" || $mp->Category == "Other Mechanical")) {
			$energyName = "noUPS_".$dc->DataCenterID."_".$mp->MPID;
                        $penaltyName = "PnoUPS_".$dc->DataCenterID."_".$mp->MPID;
                        $labelName = "noUPSname_".$dc->DataCenterID."_".$mp->MPID;
                        $updateFuncName = "updateUPS";

                        $energy = $measureList[count($measureList)-1]->Energy - $measureList[0]->Energy;
                        $penalty = isset($_POST[$penaltyName])?$_POST[$penaltyName]:0;

			$startGap = strtotime($measureList[0]->Date) - strtotime($startDate);
			$endGap = strtotime($endDate) - strtotime($measureList[count($measureList)-1]->Date);

                        $dataList["noUPS"][$dc->DataCenterID][$mp->MPID] = '<div><label for="'.$energyName.'">'.$mp->Label.'</label></div>
                                               	<div><nobr><input type="number" id="'.$energyName.'" name="'.$energyName.'" value="'.$energy.'" step="0.001" min="0" onChange="'.$updateFuncName.'('.$dc->DataCenterID.')">kW.h</nobr></div>
                        			<div><input type="number" id="'.$penaltyName.'" name="'.$penaltyName.'" value="'.$penalty.'" step="0.001" min="0" onChange="'.$updateFuncName.'('.$dc->DataCenterID.')"></div>
                                                <input type="hidden" name="'.$labelName.'" value="'.$mp->Label.'">';
			if(($startGap > $intervalMax || $endGap > $intervalMax) && count($measureList) >= 2)
				$dataList["noUPS"][$dc->DataCenterID][$mp->MPID] .= '<div><span TITLE="'.$warningMsg.'"><img src="images/warning.png" alt="_!_"/></span></div>';
		}
	}
}

?>

<!doctype html>
<html>

<link rel="stylesheet" href="css/inventory.php" type="text/css">
<link rel="stylesheet" href="css/jquery-ui.css" type="text/css">

<style>
sub
{
        vertical-align: text-bottom;
        font-size: smaller;
}
div.subtable
{
	width: 100%;
}
div.subtable > div > div
{
	width: 100%;
}
div.frame
{
	border: 1px solid grey;
	background-color: beige;
}
div.table > div > div
{
	vertical-align: top;
}
table.dcpTable
{
	width: 100%;
	margin-top: 2px;
	border: none;
}
table.dcpTable > tbody > tr > td
{
	padding: 2px;
}
input[type=number]
{
	width: 120px;
}
</style>

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM DCEM</title>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page DCEM">
<?php include( "sidebar.inc.php" );?>
	<div class="main" id="main">
		<form method="post" id="form">
			<div class="table">
				<div>
					<div>
						<div class="frame">
							<div class="table subtable">
								<div>
									<div><label for="startdate"><?php echo __("Start date"); ?> : </label></div>
									<div><input type="text" name="startdate" id="startdate" value="<?php echo $startDate; ?>"/></div>
								</div>
								<div>
									<div><label for="enddate"><?php echo __("End date"); ?> : </label></div>
									<div><input type="text" name="enddate" id="enddate" value="<?php echo $endDate; ?>"/></div>
								</div>
								<div class="caption">
									<button type="submit"><?php echo __("Load measures"); ?></button>
									<button type="submit" formaction="DCEM_PDF.php"><?php echo __("Generate PDF"); ?></button>
								</div>
							</div>
							<hr/>
							<h2><?php echo __("DCEM calculation"); ?></h2>
							<hr/>
							<div class="table subtable">
								<div>
									<div><label for="KPI_EC">KPI<sub>EC</sub></label></div>
									<div><input type="number" id="KPI_EC" name="KPI_EC" step="0.001" readonly></div>
								</div>
								<div>
									<div><label for="KPI_TE">KPI<sub>TE</sub></label></div>
									<div><input type="number" id="KPI_TE" name="KPI_TE" step="0.001" readonly></div>
								</div>
								<div>
									<div><label for="KPI_REUSE">KPI<sub>REUSE</sub></label></div>
									<div><input type="number" id="KPI_REUSE" name="KPI_REUSE" step="0.001" readonly></div>
								</div>
								<div>
									<div><label for="KPI_REN">KPI<sub>REN</sub></label></div>
									<div><input type="number" id="KPI_REN" name="KPI_REN" step="0.001" readonly></div>
								</div>
								<div>
									<div><label for="WREUSE"><?php echo __("Re-used energy weight"); ?></label></div>
									<div><input type="number" id="WREUSE" name="WREUSE" value="<?php echo $weightReuse; ?>" step="0.001" min="0" max="1" onChange="updateKPI_EM()"></div>
								</div>
								<div>
									<div><label for="WREN"><?php echo __("Renewable energy weight"); ?></label></div>
									<div><input type="number" id="WREN" name="WREN" value="<?php echo $weightRenewable; ?>" step="0.001" min="0" onChange="updateKPI_EM()"></div>
								</div>
								<div>
									<div><label for="KPI_EM">KPI<sub>EM</sub></label></div>
									<div><input type="number" id="KPI_EM" name="KPI_EM" step="0.001" readonly></div>
								</div>
							</div>
							<hr/>
							<div class="table subtable">
								<div>
									<div><label for="DCG">DC<sub>G</sub></label></div>
									<div><input type="text" id="DCG" name="DCG" step="0.001" style="width: 120px;" readonly></div>
								</div>
								<div>
									<div><label for="DCP">DC<sub>P</sub></label></div>
									<div><input type="number" id="DCP" name="DCP" step="0.001" readonly></div>
									<input type="hidden" id="DCPclass" name="DCPclass" value="A">
								</div>
							</div>
							<table class="dcpTable">
								<tr>
									<td bgcolor="#00AA00" width="88%"><center>A</center></td>
									<td><img src="images/arrow.png" alt="<" id="img_A"/></td>
								</tr>
								<tr>
									<td bgcolor="#00FF00"><center>B</center></td>
									<td><img src="images/arrow.png" alt="<" id="img_B"/></td>
								</tr>
								<tr>
									<td bgcolor="#BBFF55"><center>C</center></td>
									<td><img src="images/arrow.png" alt="<" id="img_C" /></td>
								</tr>
								<tr>
									<td bgcolor="#FFFF00"><center>D</center></td>
									<td><img src="images/arrow.png" alt="<" id="img_D" /></td>
								</tr>
								<tr>
									<td bgcolor="#FFCC00"><center>E</center></td>
									<td><img src="images/arrow.png" alt="<" id="img_E" /></td>
								</tr>
								<tr>
									<td bgcolor="#EE9900"><center>F</center></td>
									<td><img src="images/arrow.png" alt="<" id="img_F" /></td>
								</tr>
								<tr>
									<td bgcolor="#FF0000"><center>G</center></td>
									<td><img src="images/arrow.png" alt="<" id="img_G" /></td>
								</tr>
								<tr>
									<td bgcolor="#888888"><center>H</center></td>
									<td><img src="images/arrow.png" alt="<" id="img_H" /></td>
								</tr>
								<tr>
									<td bgcolor="#000000"><center><font color="#FFFFFF">I</font></center></td>
									<td><img src="images/arrow.png" alt="<" id="img_I" /></td>
								</tr>
							</table>
							<hr/>
							<div class="table subtable">
								<div>
									<div><label for="KPI_DCEM">KPI<sub>DCEM</sub></label></div>
									<div> <input type="text" id="KPI_DCEM" name="KPI_DCEM" step="0.001" style="width: 120px;" readonly></div>
								</div>
							</div>
						</div>
					</div> 
<?php
	$dcidTab = "";
	$i=0;
	foreach($dcList as $dc) {
		if($i == 0)
			$dcidTab .= $dc->DataCenterID;
		else
			$dcidTab .= ','.$dc->DataCenterID;
		$i++;

		$idTab = "";
		echo '			<div>
						<div class="frame">
							<h2>'.$dc->Name.'</h2>
							<hr/>
							<div class="table subtable" id="UPS_'.$dc->DataCenterID.'">
								<div>
									<div><h3>'.__("UPS").'</h3></div>
									<div><h3>'.__("Energy").'</h3></div>
									<div><h3>'.__("Penalty").'</h3></div>
								</div>';
		$n=0;
		foreach($dataList["UPS"][$dc->DataCenterID] as $mpid => $line) {
			if($n == 0)
				$idTab .= $mpid;
			else
				$idTab .= ','.$mpid;
			$n++;
			echo '					<div>
									'.$line.'
								</div>';
		}
		$idTab .= ';';
		echo '					</div>
							<br>
							<div class="table subtable" id="noUPS_'.$dc->DataCenterID.'">
								<div>
									<div><h3>'.__("Non UPS Sources").'</h3></div>
                                                                        <div><h3>'.__("Energy").'</h3></div>
                                                                        <div><h3>'.__("Penalty").'</h3></div>
                                                                </div>';
		$n=0;
		foreach($dataList["noUPS"][$dc->DataCenterID] as $mpid => $line) {
			if($n == 0)
				$idTab .= $mpid;
                        else
                                $idTab .= ','.$mpid;
                        $n++;
			echo '					<div>
									'.$line.'
								</div>';
		}
		$idTab .= ';';
		echo '					</div>
							<br>
							<div class="table subtable">
								<div>
									<div><label for="UPS_'.$dc->DataCenterID.'_tot">'.__("Total Energy Consumption").'</label></div>
									<div><nobr><input type="number" id="UPS_',$dc->DataCenterID,'_tot" name="UPS_',$dc->DataCenterID,'_tot" readonly> kW.h</nobr></div>
								</div>
							</div>
							<hr/>
							<div class="table subtable" id="IT_'.$dc->DataCenterID.'">
								<div>
									<div><h3>'.__("IT").'</h3></div>
									<div><h3>'.__("Energy").'</h3></div>
                                                                        <div><h3>'.__("Penalty").'</h3></div>
								</div>';
		$n=0;
		foreach($dataList["IT"][$dc->DataCenterID] as $mpid => $line) {
			if($n == 0)
                                $idTab .= $mpid;
                        else
                                $idTab .= ','.$mpid;
                        $n++;
			echo '					<div>
									'.$line.'
								</div>';
		}
		$idTab .= ';';
		echo '					</div>
							<br>
							<div class="table subtable">
								<div>
                                                                        <div><label for="IT_'.$dc->DataCenterID.'_tot">'.__("IT Energy Consumption").'</label></div>
                                                                        <div><nobr><input type="number" id="IT_',$dc->DataCenterID,'_tot" name="IT_',$dc->DataCenterID,'_tot" readonly> kW.h</nobr></div>
                                                                </div>
							</div>
							<hr/>
							<div class="table subtable" id="Reuse_'.$dc->DataCenterID.'">
								<div>
									<div><h3>'.__("Energy Reuse").'</h3></div>
                                                                        <div><h3>'.__("Energy").'</h3></div>
								</div>';
		$n=0;
		foreach($dataList["Reuse"][$dc->DataCenterID] as $mpid => $line) {
			if($n == 0)
                                $idTab .= $mpid;
                        else
                                $idTab .= ','.$mpid;
                        $n++;
			echo '					<div>
									'.$line.'
								</div>';
		}
		$idTab .=';';
		echo '					</div>
							<br>
							<div class="table subtable">
								<div>
									<div><label for="Reuse_'.$dc->DataCenterID.'_Ratio">'.__("Ratio used for IT").'</label></div>
									<div><input type="number" id="Reuse_'.$dc->DataCenterID.'_Ratio" name="Reuse_'.$dc->DataCenterID.'_Ratio" value="',$dataList["Reuse_Ratio"][$dc->DataCenterID],'" step="0.001" min="0" onChange="updateReuse()"></div>
								</div>
								<div>
									<div><br></div>
								</div>
								<div>
									<div><label for="Reuse_',$dc->DataCenterID,'_tot">',__("Total Energy Reused"),'</label></div>
                                					<div><nobr><input type="number" id="Reuse_',$dc->DataCenterID,'_tot" name="Reuse_',$dc->DataCenterID,'_tot" readonly>kW.h</nobr></div>
								</div>
							</div>
							<hr/>
							<div class="table subtable" id="Renewable_'.$dc->DataCenterID.'">
								<div>
									<div><h3>'.__("Renewable Energy").'</h3></div>
                                                                        <div><h3>'.__("Energy").'</h3></div>
								</div>';
		$n=0;
		foreach($dataList["Renewable"][$dc->DataCenterID] as $mpid => $line) {
			if($n == 0)
                                $idTab .= $mpid;
                        else
                                $idTab .= ','.$mpid;
                        $n++;
			echo '					<div>
									'.$line.'
								</div>';
		}
		echo '					</div>
							<br>
							<div class="table subtable">
								<div>
                                                                        <div><label for="Renewable_',$dc->DataCenterID,'_tot">',__("Total Renewable Energy"),'</label></div>
                                                                        <div><nobr><input type="number" id="Renewable_',$dc->DataCenterID,'_tot" name="Renewable_',$dc->DataCenterID,'_tot" readonly>kW.h</nobr></div>
                                                                </div>
							</div>
						</div>
					<input type="hidden" name="MPID_'.$dc->DataCenterID.'" value="'.$idTab.'"/>
					</div>';					
	}
	echo '			<input type="hidden" name="DCID" value="'.$dcidTab.'"/>';
?>
				</div>
			</div><!-- end div.table -->
		</form>
		<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
		<script type="text/javascript">

		var dcIDList = [<?php 	$n=0;
					foreach($dcList as $dc) {
						if($n == 0)
							echo $dc->DataCenterID;
						else
							echo ",".$dc->DataCenterID;
						$n++;
					} ?>];
		var energyID = 1;
		var penaltyID = 2;

		function updateUPS(dc) {
			var n=1;
			var upsTab = document.getElementById("UPS_"+dc);
			var noUPSTab = document.getElementById("noUPS_"+dc);
			var energy=0;

			while(upsTab.children[n] != undefined) {
				energy += parseFloat(upsTab.children[n].children[energyID].children[0].children[0].value) * (1 + parseFloat(upsTab.children[n].children[penaltyID].children[0].value));
				n++;
			}

			n=1;
			while(noUPSTab.children[n] != undefined) {
                                energy += parseFloat(noUPSTab.children[n].children[energyID].children[0].children[0].value) * (1 + parseFloat(noUPSTab.children[n].children[penaltyID].children[0].value));
                                n++;
                        }
			
			document.getElementById('UPS_'+dc+'_tot').value=energy.toFixed(3);
			updateKPI_EC();
			updateKPI_TE();
			updateKPI_REN();
			updateKPI_REUSE();
		}

		function updateIT(dc) {
			var n=1;
			var itTab = document.getElementById("IT_"+dc);
			var energy=0;

			while(itTab.children[n] != undefined) {
                                energy += parseFloat(itTab.children[n].children[energyID].children[0].children[0].value) * (1 + parseFloat(itTab.children[n].children[penaltyID].children[0].value));
                                n++;
                        }

			document.getElementById('IT_'+dc+'_tot').value=energy.toFixed(3);
			updateKPI_TE();
			updateKPI_REUSE();
		}

		function updateReuse(dc) {
			var n=1;
			var reuseTab = document.getElementById("Reuse_"+dc);
			var energy=0;

			while(reuseTab.children[n] != undefined) {
                                energy += parseFloat(reuseTab.children[n].children[energyID].children[0].children[0].value);
                                n++;
                        }

			document.getElementById('Reuse_'+dc+'_tot').value=energy.toFixed(3);
			updateKPI_REUSE();
		}

		function updateRenewable(dc) {
			var n=1;
			var renewableTab = document.getElementById("Renewable_"+dc);
			var energy=0;

			while(renewableTab.children[n] != undefined) {
                                energy += parseFloat(renewableTab.children[n].children[energyID].children[0].children[0].value);
                                n++;
                        }

			document.getElementById('Renewable_'+dc+'_tot').value=energy.toFixed(3);
			updateKPI_REN();
		}

		function updateKPI_EC() {
			var energy=0;
			for(dc of dcIDList) {
				energy += parseFloat(document.getElementById('UPS_'+dc+'_tot').value);
			}
			document.getElementById('KPI_EC').value=energy.toFixed(3);
			updateKPI_EM();
			updateDCG();
		}

		function updateKPI_TE() {
			var energy=0;
			for(dc of dcIDList) {
				energy += parseFloat(document.getElementById('UPS_'+dc+'_tot').value / document.getElementById('IT_'+dc+'_tot').value);
			}
			document.getElementById('KPI_TE').value=energy.toFixed(3);
			updateKPI_EM();
		}

		function updateKPI_REN() {
			var energy=0;
			for(dc of dcIDList) {
				energy += parseFloat(document.getElementById('Renewable_'+dc+'_tot').value / document.getElementById('UPS_'+dc+'_tot').value);
			}
			document.getElementById('KPI_REN').value=energy.toFixed(3);
			updateKPI_EM();
		}

		function updateKPI_REUSE() {
			var energy=0;
			var RU;
			var L;
			var W;
			var C;
			for(dc of dcIDList) {
				RU = document.getElementById('Reuse_'+dc+'_tot').value;
				L = document.getElementById('IT_'+dc+'_tot').value;
				W = document.getElementById('Reuse_'+dc+'_Ratio').value;
				C = document.getElementById('UPS_'+dc+'_tot').value;
				energy += parseFloat((Math.min(RU, L) + W * Math.max(0, RU - L)) / C);
			}
			document.getElementById('KPI_REUSE').value=energy.toFixed(3);
			updateKPI_EM();
		}

		function updateKPI_EM() {
			var EC = document.getElementById('KPI_EC').value;
			var TE = document.getElementById('KPI_TE').value;
			var REN = document.getElementById('KPI_REN').value;
			var REUSE = document.getElementById('KPI_REUSE').value;
			var WREN = document.getElementById('WREN').value;
			var WREUSE = document.getElementById('WREUSE').value;

			var val = parseFloat(EC * TE * (1 - (REN * WREN)) * (1 - (REUSE * WREUSE)));
			document.getElementById('KPI_EM').value = val.toFixed(3);
			updateDCP();
		}

		function updateDCP() {
			var creationYear = <?php echo $minDate; ?>;
			var TE = document.getElementById('KPI_TE').value;
			var REUSE = document.getElementById('KPI_REUSE').value;
			var REN = document.getElementById('KPI_REN').value;
			var WREUSE = document.getElementById('WREUSE').value;
			var WREN = document.getElementById('WREN').value;
			var DCP;
			var classes = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
			var steps = [0.70, 1.00, 1.30, 1.50, 1.70, 1.90, 2.10, 2.40];
			var n=0;
			
			if(creationYear < <?php echo strtotime("2005-01-01 00:00:00") ?>) {
				steps = [1.00, 1.40, 1.70, 1.90, 2.10, 2.30, 2.50, 2.70];
			}

			DCP = parseFloat(TE * (1 - (REN * WREN)) * (1 - (REUSE * WREUSE)));
			document.getElementById('DCP').value = DCP.toFixed(3);
			for(n=0; n<9; n++)
				document.getElementById('img_'+classes[n]).style.display = "none";
			n=0;
			while(DCP >= steps[n] && n < steps.length)
				n++;
			document.getElementById('img_'+classes[n]).style.display = "block";
			document.getElementById('DCPclass').value = n;

			updateKPI_DCEM();
		}

		function updateDCG() {
			var gauge = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXl'];
			var steps = [40000, 200000, 1000000, 5000000, 25000000, 120000000];
			var EC = document.getElementById('KPI_EC').value;
			var n=0;
			while(EC >= steps[n] && n < steps.length)
				n++;
			document.getElementById('DCG').value = gauge[n];
			
			updateKPI_DCEM();
		}

		function updateKPI_DCEM() {
			document.getElementById('KPI_DCEM').value = document.getElementById('DCG').value + ',' + String.fromCharCode(parseInt(document.getElementById('DCPclass').value) + 65);
		}

		function Load() {
			for(dc of dcIDList) {
				updateUPS(dc);
				updateIT(dc);
				updateReuse(dc);
				updateRenewable(dc);
			}
		}

		$(function(){
			$('#startdate').datepicker({dateFormat: "yy-mm-dd"});
			$('#enddate').datepicker({dateFormat: "yy-mm-dd"});
		});

		window.onload = Load;

		</script>
	</div>
</div>
</body>
</html>
