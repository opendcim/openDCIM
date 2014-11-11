<?php
	require_once("db.inc.php");
	require_once("facilities.inc.php");

	$subheader=__("Data Center Stockroom Supply Bins");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$bin=new SupplyBin();
	$bc=new BinContents();
	$sup=new Supplies();
	
	$supList=$sup->GetSuppliesList(true);
	$formpatch="";
	$status="";

	if(isset($_REQUEST["binid"])) {
		$bin->BinID=(isset($_POST['binid'])?$_POST['binid']:$_GET['binid']);
		$bin->GetBin();
		
		$bc->BinID=$bin->BinID;

		if(isset($_POST["action"])&&(($_POST["action"]=="Create")||($_POST["action"]=="Update"))&&($_POST["location"]!=null&&$_POST["location"]!="")){
			$bin->Location=$_POST["location"];

			if($_POST["action"]=="Create"){
				$bin->CreateBin();
			}else{
				$binContents=$bc->GetBinContents();

				// We don't want someone changing the name of a bin to a blank anymore than we want them creating one as a blank name
				$status=__("Updated");
				$bin->UpdateBin();

				// only attempt to alter the contents of the bin if we have the proper elements
				if(isset($_POST['supplyid']) && count($_POST['supplyid']>0)){
					// process all of the submitted values into a new array to handle multiple instances of any part being added
					$cleansupplies=array();
					foreach($_POST['supplyid'] as $key => $value){
						if($_POST['count'][$key]!="" && $_POST['supplyid'][$key]!=0){
							if(!isset($cleansupplies[$_POST['supplyid'][$key]])){
								$cleansupplies[$_POST['supplyid'][$key]]=$_POST['count'][$key];
							}else{
								// we're using -1 to remove an item from the bin.
								if($_POST['count'][$key]!=-1 && $cleansupplies[$_POST['supplyid'][$key]]!=-1){
									// Some prankster is trying to add the same part type multiple times so just add the values up
									$cleansupplies[$_POST['supplyid'][$key]]+=$_POST['count'][$key];
								}elseif($cleansupplies[$_POST['supplyid'][$key]]==-1){
									// Smart ass clicked the x to remove the part from the bin the added it back with a line below that.
									$cleansupplies[$_POST['supplyid'][$key]]+=$_POST['count'][$key]+1;
								}else{
									$cleansupplies[$_POST['supplyid'][$key]]=-1;
								}
							}
						}
					}
					foreach($cleansupplies as $SupplyID => $count){
						// assume that each line will just be added. if found in the bin set to false because we updated the count.
						$ins=true;
						foreach($binContents as $key => $contents){
							if($contents->SupplyID==$SupplyID){
								$contents->Count=$count;
								// if we manually set supply to zero remove it from the bin?
								if($count==-1){
									$contents->RemoveContents();
								}else{
									$contents->UpdateCount();
								}
								$ins=false;
							}
						}
						if($ins){
							$bc->SupplyID=$SupplyID;
							$bc->Count=$count;
							$bc->AddContents();
						}
					}
				}
			}
		}
		// Bin contents could have changed above so pull them again
		$binContents=$bc->GetBinContents();

		$formpatch="?binid={$_REQUEST['binid']}";
	}
	
	$binList=$bin->GetBinList();

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Stockroom Supplies</title>

  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript">
	$(document).ready(function() {
		$('#newline').click(function (){
			$(this).parent().prev().clone().insertBefore($(this).parent()).children('div:first-child').html('<img src="images/del.gif">').click(function() {
				$(this).parent().remove();
			});
		});
		$('.remove').click(function (){
			if(!$(this).next().next().children('input').attr('oldcount')){
				$(this).children('img').after('<input type="hidden" name="'+$(this).next().children("select").attr("name")+'" value="'+$(this).next().children("select").val()+'">');
				$(this).children('img').after('<input type="hidden" name="'+$(this).next().next().children("input").attr("name")+'" value="-1">');
				$(this).next().children('select').attr('disabled','disabled');
				$(this).next().next().children('input').attr({
					'oldcount': $(this).next().next().children('input').val(),
					'value': '-1',
					'disabled': 'disabled'
				});
			}else{
				$(this).children('input').remove();
				$(this).next().children('select').removeAttr('disabled');
				$(this).next().next().children('input').val($(this).next().next().children('input').attr('oldcount')).removeAttr('oldcount').removeAttr('disabled');
			}
		});
	});
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page supply">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<h3>',$status,'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"].$formpatch,'" method="POST">
<div class="table">
<div>
   <div><label for="binid">',__("Bin Location"),'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="binid" id="binid" onChange="form.submit()">
   <option value=0>',__("New Bin"),'</option>';

	foreach($binList as $binRow){
		if($bin->BinID==$binRow->BinID){$selected=" selected";}else{$selected="";}
		print "<option value=\"$binRow->BinID\"$selected>$binRow->Location</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="location">',__("Location"),'</label></div>
   <div><input type="text" name="location" id="location" value="',$bin->Location,'"></div>
</div>';

	if($bin->BinID==0){
		echo '<div><div>&nbsp;</div><div></div></div>
		<div class="caption"><button type="submit" name="action" value="Create">',__("Create"),'</button></div>';
	}
?>
</div><!-- END div.table -->

<?php
	if($bin->BinID >0){

		echo '<div class="table">
	<div>
		<div></div>
		<div>',__("Part Number"),'</div>
		<div>',__("Count"),'</div>
	</div>';

		foreach($binContents as $cnt){
			print "	<div>
			<div class=\"remove\"><img src=\"images/x.gif\" alt=\"Remove this item from the bin\"></div>
			<div><select name=\"supplyid[]\">
				<option value=\"$cnt->SupplyID\">{$supList[$cnt->SupplyID]->PartNum} ({$supList[$cnt->SupplyID]->PartName})</option>
			</select></div>
			<div><input class=\"quantity\" name=\"count[]\" type=\"text\" value=\"$cnt->Count\" maxlength=5 size=5></div>
		</div>\n";
		}

		echo '	<div>
		<div></div>
		<div><select name="supplyid[]"><option value="0" selected>',__("Select parts to add..."),'</option>';

		foreach($supList as $tmpSup){
			print "\t\t\t<option value=\"$tmpSup->SupplyID\">$tmpSup->PartNum ($tmpSup->PartName)</option>\n";
		}

		echo '		</select></div>
		<div><input class="quantity" name="count[]" type="text" size=5 maxlength=5></div>
	</div>
  	<div>
		<div id="newline"><img src="images/add.gif" alt="add new row"></div>
		<div></div>
		<div></div>
	</div>
	<div class="caption">
		<button type="submit" name="action" value="Update">',__("Submit"),'</button>
	</div>
</div><!-- END div.table --> ';
	}
?>
</form>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
