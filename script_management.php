<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');
	$subheader=__("Data Center Polling Scripts Management");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
	
	// AJAX

	$mpgList = new MeasurePointGroup();
	$mpgList = $mpgList->GetMPGList();
	
	$pollDir = __DIR__."/poll_scripts/";

	if(isset($_POST["create"])) {
		$mpg = new MeasurePointGroup();
		$mpg->MPGID = $_POST["create"];
		$mpg->GetMPG();

		$name = $mpg->Name."_".$mpg->MPGID.".php";

		$fileCode='<?php
	require( \''.__DIR__.'/db.inc.php\' );
	require( \''.__DIR__.'/facilities.inc.php\' );

	$mpg = new MeasurePointGroup();
	$mpg->MPGID='.$mpg->MPGID.';
	$mpg->GetMPG();

	foreach($mpg->MPList as $mpid) {
        	$mp = new MeasurePoint();
		$mp->MPID = $mpid;
        	$mp = $mp->GetMP();
        	$mp->Poll();
	}
?>';
		file_put_contents($pollDir.$name, $fileCode);
	}

	if(isset($_POST["delete"])) {
		$mpg = new MeasurePointGroup();
		$mpg->MPGID = $_POST["delete"];
		$mpg->GetMPG();

		$name = $mpg->Name."_".$mpg->MPGID.".php";

		unlink($pollDir.$name);
	}

	if(isset($_POST["updatecron"])) {
		$tmp = "tmp.txt";
		$text = str_replace("\r", "", $_POST["crontab"]); //cron doesn't like \r

		file_put_contents($pollDir.$tmp, $text);
		exec("crontab ".$pollDir.$tmp);
		unlink($pollDir.$tmp);
	
		$crontab = shell_exec('crontab -l');
	} else {
		$crontab = (isset($_POST["crontab"]))?$_POST["crontab"]:shell_exec('crontab -l');
	}

	$fileList = scandir($pollDir);
?>
<!doctype html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	
	<title>openDCIM Data Center Management</title>
	<link rel="stylesheet" href="css/inventory.php" type="text/css">
	<link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
	<style>
		.scrollable
		{
			overflow: hidden;
			overflow-y: scroll;
			background-color: beige;
		}
		.block
		{
			height: 400px;
			min-width: 150px;
			margin: 1px;
		}
		.box
		{
			border: 1px solid grey;
			background-color: bisque;
		}
		.fileBox
		{
			padding-left: 5px;
			padding-right: 5px;
			padding-top: 2px;
			padding-bottom: 2px;
		}
		.colorBox
		{
			border: 1px solid lightgrey;
			height: 100%;
			width: 1px;
			padding: 2px;
		}
	</style>
	<!--[if lt IE 9]>
	<link rel="stylesheet"  href="css/ie.css" type="text/css">
	<![endif]-->
	<script type="text/javascript" src="scripts/jquery.min.js"></script>
	<script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
	<div class="page">
<?php
	include( 'sidebar.inc.php' );

$mpgBoxList = "";
foreach($mpgList as $mpg) {
	$name = $mpg->Name."_".$mpg->MPGID.".php";
	$action=(is_file($pollDir.$name))?"delete":"create";
	$buttonText=(is_file($pollDir.$name))?__("Delete"):__("Create");
	$color = (is_file($pollDir.$name))?"lightgreen":"lightcoral";

	$mpgBoxList.= '	<li class="box"><div class="table" style="width: 100%;">
				<div>
					<div class="colorBox" style="background-color: '.$color.'"></div>
					<div style="padding-right: 15px; max-width: 200px;">
						<label>'.$mpg->Name.'</label>
					</div>
					<div style="text-align: right;">
						<button name="'.$action.'" value="'.$mpg->MPGID.'">'.$buttonText.'</button>
					</div>
				</div>
			</div></li>';
}

$fileBoxList = "";
foreach($fileList as $file) {
	if($file != "." && $file != "..")
		$fileBoxList .= '<li class="box fileBox" onClick="addScript(\''.$pollDir.$file.'\');"><label>'.$file.'</label></li>';
}


echo '		<div class="main">
			<form action="',$_SERVER['PHP_SELF'],'" method="POST" name="form1">
				<div class="table">
					<div>
						<div><h3>'.__("Measure Point Groups").'</h3></div>
						<div><h3>'.__("Poll Scripts").'</h3></div>
						<div><h3>'.__("Crontab Editor").'</h3></div>
					</div>
					<div>
						<div>
							<ul class="scrollable block">
								'.$mpgBoxList.'
							</ul>
						</div>
						<div>
							<ul class="scrollable block">
								'.$fileBoxList.'
							</ul>
						</div>
						<div>
							<textarea cols="100" name="crontab" id="crontab" class="block" style="resize: none;">'.$crontab.'</textarea>
						</div>		
					</div>
					<div>
						<div></div>
						<div></div>
						<div>
							<button name="updatecron">'.__("Update Crontab").'</button>
						</div>
					</div>
				</div> <!-- END div.table -->
			</form>';
?>

<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
		</div><!-- END div.main -->
	</div><!-- END div.page -->
<script type="text/javascript">

function addScript(fileName) {
	var line = "0 * * * *    php "+fileName+"\n";
	document.getElementById("crontab").value += line;
}

//window.onload=OnTypeChange;

</script>
</body>
</html>
