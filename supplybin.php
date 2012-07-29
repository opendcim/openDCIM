<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$user=new User();
	$user->UserID=$_SERVER["REMOTE_USER"];
	$user->GetUserRights($facDB);

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$bin=new SupplyBin();
	$bc=new BinContents();
	$sup=new Supplies();
	
	$supList=$sup->GetSuppliesList($facDB);
	$formpatch="";
	$status="";
	
	if(isset($_REQUEST["binid"])) {
		$bin->BinID=$_REQUEST["binid"];
		$bin->GetBin($facDB);
		
		$bc->BinID=$bin->BinID;

		if(isset($_POST["action"])&&(($_POST["action"]=="Create")||($_POST["action"]=="Update"))){
			$bin->Location=$_POST["location"];

			if($_POST["action"]=="Create"){
				if($bin->Location != null && $bin->Location != "") {
					$bin->AddBin($facDB);
				}
			}else{
				$binContents=$bc->GetBinContents($facDB);

				// We don't want someone changing the name of a bin to a blank anymore than we want them creating one as a blank name
				if($bin->Location != null && $bin->Location != "") {
					$status="Updated";
					$bin->UpdateBin($facDB);

					// only attempt to alter the contents of the bin if we have the proper elements
					if(isset($_POST['supplyid']) && count($_POST['supplyid']>0)){
						// process all of the submitted values into a new array to handle multiple instances of any part being added
						$cleansupplies=array();
						foreach($_POST['supplyid'] as $key => $value){
							if($_POST['count'][$key]!="" && $_POST['supplyid'][$key]!=0){
								if(!isset($cleansupplies[$_POST['supplyid'][$key]])){
									$cleansupplies[$_POST['supplyid'][$key]]=$_POST['count'][$key];
								}else{
									// Some prankster is trying to add the same part type multiple times so just add the values up
									$cleansupplies[$_POST['supplyid'][$key]]+=$_POST['count'][$key];
								}
							}
						}
						foreach($cleansupplies as $SupplyID => $count){
							// assume that each line will just be added. if found in the bin set to false because we updated the count.
							$ins=true;
							foreach($binContents as $key => $contents){
								if($contents->SupplyID==$SupplyID){
									$contents->Count=$count;
									$contents->updateCount($facDB);
									$ins=false;
								}
							}
							if($ins){
								$bc->SupplyID=$SupplyID;
								$bc->Count=$count;
								$bc->AddContents($facDB);
							}
						}
					}
				}else{
					$status="Error";
				}
			}
		}
		// Bin contents could have changed above so pull them again
		$binContents=$bc->GetBinContents($facDB);

		$formpatch="?binid={$_REQUEST['binid']}";
	}
	
	$binList=$bin->GetBinList($facDB);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Stockroom Supplies</title>

  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript">
	$(document).ready(function() {
		$('#newline').click(function (){
			$(this).parent().prev().clone().insertBefore($(this).parent()).children('div:first-child').html('<img src="images/del.gif">').click(function() {
				$(this).parent().remove();
			});
		});
	});
  </script>
</head>
<body>
<div id="header"></div>
<div class="page supply">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main">
<h2><?php echo $config->ParameterArray["OrgName"]; ?></h2>
<h3>Data Center Stockroom Supply Bins</h3>
<h3><?php echo $status; ?></h3>
<div class="center"><div>
<form action="<?php echo $_SERVER["PHP_SELF"].$formpatch; ?>" method="POST">
<div class="table">
<div>
   <div><label for="binid">Bin Location</label></div>
   <div><input type="hidden" name="action" value="query"><select name="binid" id="binid" onChange="form.submit()">
   <option value=0>New Bin</option>
<?php
	foreach ( $binList as $binRow ) {
		echo "<option value=\"$binRow->BinID\"";
		if($bin->BinID == $binRow->BinID){echo " selected";}
		echo ">$binRow->Location</option>\n";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="location">Location</label></div>
   <div><input type="text" name="location" id="location" value="<?php echo $bin->Location; ?>"></div>
<?php
	if($bin->BinID >0){
		echo '   <input type="submit" name="action" value="Update">';
	}else{
		echo '   <input type="submit" name="action" value="Create">';
	}
?>
</div>
</div><!-- END div.table -->


<?php
	if($bin->BinID >0){
?>
<div class="table">
	<div>
		<div></div>
		<div>Part Number</div>
		<div>Count</div>
	</div>
<?php
	foreach($binContents as $cnt){
		print "	<div>
		<div></div>
		<div><select name=\"supplyid[]\">
			<option value=\"$cnt->SupplyID\">{$supList[$cnt->SupplyID]->PartNum} ({$supList[$cnt->SupplyID]->PartName})</option>
		</select></div>
		<div><input class=\"quantity\" name=\"count[]\" type=\"text\" value=\"$cnt->Count\" maxlength=5 size=5></div>
	</div>\n";
	}
?>
	<div>
		<div></div>
		<div><select name="supplyid[]"><option value="0" selected>Select parts to add...</option>
<?php
	foreach($supList as $tmpSup){
		print "\t\t\t<option value=\"$tmpSup->SupplyID\">$tmpSup->PartNum ($tmpSup->PartName)</option>\n";
	}
?>
		</select></div>
		<div><input class="quantity" name="count[]" type="text" size=5 maxlength=5></div>
	</div>
  	<div>
		<div id="newline"><img src="images/add.gif" alt="add new row"></div>
		<div></div>
		<div></div>
	</div>
	<div class="caption">
		<button type="submit" name="action" value="Update">Submit</button>
	</div>
</div><!-- END div.table -->
<?php
	}
?>
</form>
</div></div>
<a href="index.php">[ Return to Main Menu ]</a>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
