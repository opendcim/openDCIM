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

		$equipmentList = "";
		$equipmentList .= '<div style="background: beige;">
					<li><div class="equipmentBox"><center>'.__("Measure Point Groups").'</center></div></li>';

		foreach($mpgList as $mpg) {
			$list="[";
			$i=0;
			foreach($mpg->MPList as $m) {
				if($i == 0)
					$list .= '\''.$side.$type.'_'.$m.'\'';
				else
					$list .= ',\''.$side.$type.'_'.$m.'\'';
				$i++;
			}

			$list .= "]";
			$name = $side.$type."G_".$mpg->MPGID;
			$checked = ($_POST[$name])?"checked":"";
			$equipmentList .= '<li><div class="equipmentBox">
						<label class="equipmentLabel" for="'.$name.'">'.$mpg->Name.'</label>
						<input type="checkbox" name="'.$name.'" id="'.$name.'" onChange="updateEQ(\''.$name.'\','.$list.')" '.$checked.'>
					</div></li>';
		}

		$equipmentList .= '</div>';
		
		$equipmentList .= '<div style="background: bisque;">
					<li><div class="equipmentBox"><center>'.__("Measure Points").'</center></div></li>';

		$i=0;
		foreach($mpList as $mp) {
			$name = $side.$type."_".$mp->MPID;
			$checked = ($_POST[$name])?"checked":"";
			$equipmentList .= '<li><div class="equipmentBox">
						<label class="equipmentLabel" for="'.$name.'">'.$mp->Label.'</label>
						<input type="checkbox" name="'.$name.'" id="'.$name.'" '.$checked.'>
					</div></li>';
		}

		$equipmentList .= '</div>';
		return $equipmentList;
	}

	function getIds($type, $side) {
		$class = MeasurePoint::$TypeTab[$type]."MeasurePoint";
		$mpList = new $class;
		$mpList = $mpList->GetMPList();

		$ids = "&".$side.$type."ids=";
		$n=0;
		foreach($mpList as $mp) {
			if(isset($_POST[$side.$type."_".$mp->MPID]) && $_POST[$side.$type."_".$mp->MPID] == "on") {
				if($n == 0)
					$ids .= $mp->MPID;
				else
					$ids .= ','.$mp->MPID;
				$n++;
			}
		}
		return $ids;
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
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page graphgenerator">
<?php
	include( "sidebar.inc.php" );

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
	$optionFrequency="";
	foreach($selectFrequency as $id => $val) {
		if($id == $_POST['frequency'])
			$optionFrequency.='<option value="'.$id.'" selected>'.$val.'</option>';
		else
			$optionFrequency.='<option value="'.$id.'">'.$val.'</option>';
	}

	if(isset($_POST['startdate']))
		$startdate = $_POST['startdate'];
	else
		$startdate = getStartDate($config->ParameterArray["TimeInterval"], false);
	if(isset($_POST['enddate']))
		$enddate = $_POST['enddate'];
	else
		$enddate = getEndDate($config->ParameterArray["TimeInterval"], false);
	if(isset($_POST['frequency']))
		$frequency = $_POST['frequency'];
	else
		$frequency = "hourly";


	$graphParam = "?lefttype=".$leftType;
	if($rightType!="none")
		$graphParam .= "&righttype=".$rightType;

	$graphParam .= "&startdate=".$startdate."&enddate=".$enddate."&frequency=".$frequency;
	if(isset($_POST['combinephases']))
		$graphParam .= "&combinephases=".$_POST['combinephases'];

	$graphParam .= getIds($measureTypeArray[$leftType], 'l');
	if($rightType != "none")
		$graphParam .= getIds($measureTypeArray[$rightType], 'r');

	$leftList=createEquipmentList($measureTypeArray[$leftType], 'l');
	if($rightType != "none")
		$rightList=createEquipmentList($measureTypeArray[$rightType], 'r');

	$graphOptions = "";

	 if($leftType == "power" || $rightType == "power") {
		$checked=($_POST['combinephases'])?"checked":"";
		 $graphOptions .= '	<label for="combinephases">'.__("Combine Power Phases").' : </label>
					<input type="checkbox" name="combinephases" id ="combinephases" '.$checked.'>';
	}
	if($leftType == "energy" || $rightType == "energy") {
		$graphOptions .=  '	<label for="frequency">'.__("Energy Measures Frequency").' : </label>
					<select id="frequency" name="frequency">
						'.$optionFrequency.'
					</select>';
	}

	echo '<div class="main">
		<form method="post"><br>
			<label for="startdate">',__("From"),' : </label>
			<input type="date" min="1970-01-01" max="9999-12-31" name="startdate" id="startdate" value="',$startdate,'"/>
			<label for="enddate">',__("to"),' : </label>
			<input type="date" min="1970-01-01" max="9999-12-31" name="enddate" id="enddate" value="',$enddate,'"/>
			<button type="submit" name="generate" value="true">',__("Generate"),'</button><br>
			',$graphOptions,'
			<br><br>
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
						<select name="lefttype" onChange="submit();">
							',$optionLeft,'
						</select>
						</center>
					</div>
					<div>
						<center>
						<select name="righttype" onChange="submit();">
							',$optionRight,'
						</select>
						</center>
					</div>
				</div>
				<div>
					<div>
						<ul class="scrollable" style="height: 700px; width: 220px; border: 1px solid grey;">
							',$leftList,'
						</ul>
					</div>
					<div>
						<ul class="scrollable" style="height: 700px; width: 220px; border: 1px solid grey;">
							',$rightList,'
						</ul>
					</div>';
	//if(isset($_POST['generate']))
		echo '			<div><img src="comboGraph.php',$graphParam,'&height=700&width=1200" align="left" alt="" style="border: 1px solid grey;"/></div>';
			
	echo '			</div>
			</div>
		</form>';
?>

</div>
</div>
<script type="text/javascript">

function updateEQ(obj, tab) {
	var n;
	var mp;
	var mpg = document.getElementById(obj);
	for(n=0; n<tab.length; n++) {
		mp = document.getElementById(tab[n]);
		if(mp != null)
			mp.checked = mpg.checked;
	}
}

$(function(){
	$('#startdate').datepicker({dateFormat: "yy-mm-dd"});
	$('#enddate').datepicker({dateFormat: "yy-mm-dd"});
});

</script>
</body>
</html>
