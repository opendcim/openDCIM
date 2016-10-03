<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	if(!$person->WriteAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	// This page has no function without a valid project id
	if(!isset($_REQUEST['projectid'])){
		echo "How'd you get here without a referral?";
		exit;
	}

	if ( ! isset($_REQUEST['datacenterid'] )) {
		$DataCenterID = 0;
	} else {
		$DataCenterID = $_REQUEST['datacenterid'];
	}

	$proj = Projects::getProject( $_REQUEST['projectid'] );

	// Update if form was submitted and action is set
	if(isset($_POST['action']) && $_POST['action']=="Submit"){
		$grpMembers=$_POST['chosen'];
		if ( sizeof( $grpMembers > 0 ) && isset( $_REQUEST['projectid'] )) {
			$ProjectID = $_REQUEST['projectid'];
			ProjectMembership::clearMembership( $ProjectID );
			foreach ( $grpMembers as $devID ) {
				ProjectMembership::addMember( $ProjectID, $devID );
			}
		}
	}

	$dcList = DataCenter::GetDCList();
	$memberList = ProjectMembership::getProjectMembership( $proj->ProjectID );
	$devList = Device::getDevicesByDC( $DataCenterID );

	// Build an array of DeviceIDs that are in the memberList
	$dev = new Device();
	$memberKeys = array();
	foreach ( $memberList as $mem ) {
		$memberKeys[$mem->DeviceID] = $mem->Label;
	}

	natsort( $memberKeys );
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title><?php __("openDCIM Project Membership Maintenance"); ?></title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.ui.multiselect.css" type="text/css">
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.ui.multiselect.js"></script>

  <script type="text/javascript">
	$(document).ready(function(){
		$('#chosenList').multiselect();

		$('#datacenterid').change(function(e){
			document.location.href='project_members.php?projectid='+$('#projectid').val()+'&datacenterid='+this.value;
		});
	});
  </script>

</head>
<body id="projectgroup">
<div class="centermargin">
<?php
echo '<form id="projectform" method="POST">
<input type="hidden" name="projectid" id="projectid" value="',$proj->ProjectID,'">
<h3>',__("Project to Administer"),': ',$proj->ProjectName,'</h3>
<select name="datacenterid" id="datacenterid" width="200px"><option value="0">Choose a Data Center</option>';
foreach ( $dcList as $dc ) {
	if ( $dc->DataCenterID == $DataCenterID ) {
		$selected = "SELECTED";
	} else {
		$selected = "";
	}
	print "<option value=\"$dc->DataCenterID\" $selected>$dc->Name</option>";
}

echo '</select>
<div>
	<select name="chosen[]" id="chosenList" size="15" multiple="multiple">';
	foreach($memberKeys as $DeviceID=>$Label) {
		print "<option value='$DeviceID' selected>$Label</option>";
	}
	foreach($devList as $devRow){
		if ( ! array_key_exists( $devRow->DeviceID, $memberKeys )) {
			print "\t\t<option value=\"$devRow->DeviceID\">$devRow->Label</option>\n";
		}
	}
echo '</select>
	<button type="submit" value="Submit" name="action">',__("Submit"),'</button>';
?>
</div>
</form>
</div>
</body>
</html>
