<?php
require_once('db.inc.php');
require_once('facilities.inc.php');

$logs = "";
$message = "";
$ids = "";
if(isset($_FILES["import"])) {
	switch($_POST["action"]) {
		case "Create":
			$dcid = (isset($_POST["datacenterid"]))?$_POST["datacenterid"]:0;
			$result = Device::CreateDevices_CSV($_FILES["import"]["tmp_name"], $dcid);

			foreach($result['log'] as $line)
				$logs .= $line.'\n';
			break;
		case "Update":
			$result = Device::UpdateDevices_CSV($_FILES["import"]["tmp_name"]);

			foreach($result['log'] as $line)
				$logs .= $line.'\n';
			break;
		case "Delete":
			$result = Device::DeleteDevices_CSV($_FILES["import"]["tmp_name"]);

			foreach($result['confirmation'] as $line)
				$message .= $line.'\n';

			$message .= '\n'.'\n';
			foreach($result['log'] as $line)
				$message .= $line.'\n';
		
			foreach($result['ids'] as $key => $id)
				if($key == 0)
					$ids .= $id;
				else
					$ids .= ','.$id;
			break;
		case "DeleteConfirmed":
			$idList = explode(",", $_POST["ids"]);
			foreach($idList as $id) {
				$device = new Device();
				$device->DeviceID = $id;
				$device->GetDevice();
				$device->DeleteDevice();
			}
			$logs = __("Devices deleted.");
			break;
		default:
			break;
	}
}

$actionList = array("Create", "Update", "Delete");

$actionOpt = "";
foreach($actionList as $action) {
	if(isset($_POST['action']) && $_POST['action'] == $action)
                $selected = " selected";
        else
                $selected = "";
	$actionOpt .= "<option value=\"$action\"$selected>".__($action)."</option>";
}

$dcList = new DataCenter();
$dcList = $dcList->GetDCList();

$dcOpt = "";
foreach($dcList as $dc) {
	if(isset($_POST['datacenterid']) && $_POST['datacenterid'] == $dc->DataCenterID)
                $selected = " selected";
        else
                $selected = "";
	$dcOpt .= "<option value=\"$dc->DataCenterID\">$dc->Name</option>";
}
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
        .block
        {
                height: 400px;
                min-width: 150px;
                margin: 1px;
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
include( "sidebar.inc.php" );

echo '	<div class="main">
		<div class="center"><div>
			<form action="',$_SERVER['PHP_SELF'],'" name="form" method="post" enctype="multipart/form-data">
				<div class="table">
					<div>
						<div><label for="action">'.__("Action").'</label></div>
						<div><select name="action" id="action" onChange="OnActionChange();">
							'.$actionOpt.'
						</select></div>
					</div>
					<div id="div_datacenterid">
						<div><label for="datacenterid">'.__("Data Center").'</label></div>
						<div><select name="datacenterid">
							<option value="0">',__("General Storage Room"),'</option>
							'.$dcOpt.'
						</select></div>
					</div>
					<div>
						<div><label for="import">',__("File"),'</label></div>
						<div><input type="file" name="import"></div>
					</div>
					<input type="hidden" name="ids" value="'.$ids.'">
					<div class="caption">
						<button type="submit">'.__("Submit").'</button>
					</div>
				</div>
			</form>
		</div></div>

		<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
		<!-- hiding modal dialogs here so they can be translated easily -->
		<div class="hide">
        		<div title="',__("Measure Point delete confirmation"),'" id="deletemodal">
                		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this measure point?"),'</div>
        		</div>
		</div>
	</div>';
?>
</div>

<script type="text/javascript">
function OnActionChange() {
	var action = document.form.action.options[document.form.action.selectedIndex].value;
        if( action != 'Create') {
                document.getElementById("div_datacenterid").style.display = "none";
        } else
                document.getElementById("div_datacenterid").style.display = "";
}

function confirmDelete() {
	if(confirm("<?php echo __("Are you sure you want to delete those devices?").'\n'.$message; ?>") == true) {
		var select = document.getElementById("action");
		select.options[select.options.length] = new Option('DeleteConfirmed', 'DeleteConfirmed');
		select.options.selectedIndex = select.options.length - 1;
		document.form.submit();
	}
}

<?php
if(isset($_FILES["import"])) {
	if($_POST["action"] != "Delete")
		echo 'alert("'.$logs.'")';
}
?>

function Load() {
	OnActionChange();
	<?php
		if(isset($_POST["action"]) && $_POST["action"] == "Delete" && $result["status"])
			echo "confirmDelete();";
		else if(isset($result["ids"]) && count($result["ids"]) < 1)
			echo 'alert("'.$message.'\n'.__("Error: There is not any valid IDs.").'");';
	?>
}

window.onload= Load;
</script>

</body>
</html>
