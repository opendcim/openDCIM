<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');
	$subheader=__("Data Center Measure Point Group Detail");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
	
	// AJAX

	$mpg = new MeasurePointGroup();

	$mpList = new MeasurePoint();
	$mpList=$mpList->GetMPList();

	if(isset($_POST['deletemeasurepointgroup'])){
		$mpg->MPGID = $_REQUEST["mpgid"];
		$return='no';
		if($mpg->GetMPG()){
			$mpg->DeleteMPG();
			$return='ok';
		}
		echo $return;
		exit;
	}
	if(isset($_REQUEST['action']) && (($_REQUEST['action']=='Create')||($_REQUEST['action']=='Update'))){
		$mpg->MPGID=$_REQUEST['mpgid'];
		$mpg->Name=$_REQUEST['name'];
		foreach($mpList as $mp) {
			if($_REQUEST["MP_".$mp->MPID])
				$mpg->MPList[] = $mp->MPID;	
		}
		if($_REQUEST['action']=='Create'){
			$mpg->CreateMPG();
		}else{
			$mpg->UpdateMPG();
		}
	}
	if(isset($_REQUEST['mpgid']) && $_REQUEST['mpgid'] >0){
		$mpg->MPGID = $_REQUEST['mpgid'];
		$mpg->GetMPG();
	}
	$mpgList = new MeasurePointGroup();
	$mpgList = $mpgList->GetMPGList();
	
	$selectedMP = array();
	$nonSelectedMP = array();

	foreach($mpList as $mp) {
		if(in_array($mp->MPID, $mpg->MPList))
			$selectedMP[] = $mp;
		else
			$nonSelectedMP[] = $mp;
	}

	$colorTab = array(	"elec" => "palegoldenrod",
				"cooling" => "darkseagreen",
				"air" => "lavender");
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
		.box
		{
			border: 1px solid grey;
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

echo '		<div class="main">
			<div class="center"><div>
				<form action="',$_SERVER['PHP_SELF'],'" method="POST" name="form1">
					<div class="table">
						<div>
							<div><label for="mpgid">',__("Measure Point Group ID"),'</label></div>
							<div><select name="mpgid" id="mpgid" onChange="form.submit()">
								<option value="0">',__("New Measure Point Group"),'</option>';

	foreach($mpgList as $mpgRow){
		if($mpgRow->MPGID==$mpg->MPGID){$selected=' selected';}else{$selected='';}
		print "\t\t\t\t\t\t\t\t<option value=\"$mpgRow->MPGID\"$selected>$mpgRow->Name</option>\n";
	}

echo '							</select></div>
						</div>
						<div>
							<div><label for="name">'.__("Name").'</label></div>
							<div><input type="text" name="name" id="name" value="',$mpg->Name,'"></div>
						</div>
						<br><br>
						<div>
							<div><center><label>',__("Non Selected"),'</label></center></div>
							<div><center><label>',__("Selected"),'</label></center></div>
						</div>
						<div>
							<div>
								<ul class="scrollable" id="nonSelected" style="height: 500px; width: 220px; border: 1px solid grey;">';
	foreach($nonSelectedMP as $mp) {
		echo '	<li class="box" style="background-color: '.$colorTab[$mp->Type].';" onClick="changeSide(this,',$mp->MPID,');">
				<label>'.$mp->Label.'</label>
				<input type="checkbox" id="checkbox_',$mp->MPID,'" name="MP_',$mp->MPID,'" hidden>
			</li>';
	}
echo '								</ul>
							</div>
							<div>
								<ul class="scrollable" id="selected" style="height: 500px; width: 220px; border: 1px solid grey;">';
	foreach($selectedMP as $mp) {
		echo '	<li class="box" style="background-color: '.$colorTab[$mp->Type].';" onClick="changeSide(this,',$mp->MPID,');">
				<label>'.$mp->Label.'</label>
				<input type="checkbox" id="checkbox_',$mp->MPID,'" name="MP_',$mp->MPID,'" hidden checked>
			</li>';
	}
echo '								</ul>
							</div>
						</div>
						<div class="caption">';

	if($mpg->MPGID >0){
		echo '								<button type="submit" name="action" value="Update">',__("Update"),'</button>
										<button type="button" name="action" value="Delete">',__("Delete"),'</button>';
	} else {
		echo '							<button type="submit" name="action" value="Create">',__("Create"),'</button>';
}
echo '						</div>
					</div> <!-- END div.table -->
				</form>';

?>
			</div></div>

<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Measure Point Group delete confirmation"),'" id="deletemodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this measure point Group (Measure Points won't be destroyed)?"),'
		</div>
	</div>
</div>'; ?>
		</div><!-- END div.main -->
	</div><!-- END div.page -->
<script type="text/javascript">
$('button[value=Delete]').click(function(){
	var defaultbutton={
		"<?php echo __("Yes"); ?>": function(){
			$.post('', {mpgid: $('#mpgid').val(),deletemeasurepointgroup: '' }, function(data){
				if(data.trim()=='ok'){
					self.location=$('.main > a').last().attr('href');
					$(this).dialog("destroy");
				}else{
					alert("Danger, Will Robinson! DANGER!  Something didn't go as planned.");
				}
			});
		}
	}
	var cancelbutton={
		"<?php echo __("No"); ?>": function(){
			$(this).dialog("destroy");
		}
	}
	var modal=$('#deletemodal').dialog({
		dialogClass: 'no-close',
		modal: true,
		width: 'auto',
		buttons: $.extend({}, defaultbutton, cancelbutton)
	});
});

function changeSide(test, mpid) {
	if(test.parentElement.id == "nonSelected") {
		document.getElementById("selected").appendChild(test);
		document.getElementById("checkbox_"+mpid).checked = true;
	} else {
		document.getElementById("nonSelected").appendChild(test);
		document.getElementById("checkbox_"+mpid).checked = false;
	}
}

//window.onload=OnTypeChange;

</script>
</body>
</html>
