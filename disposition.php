<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Disposal Method Management");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
	
	$status = "";
	$disp = new Disposition;

	if(isset($_POST['action'])&&(($_POST['action']=='Create')||($_POST['action']=='Update'))){
		$disp->DispositionID = $_POST['dispositionid'];
		$disp->Name = $_POST['name'];
		$disp->Description = $_POST['description'];
		$disp->ReferenceNumber = $_POST['referencenumber'];
		$disp->Status = $_POST['status'];

		if($disp->Name!=""){
			if($_POST['action']=='Create'){
				$status=__("Created");
				$disp->createDisposition();
			}else{
				$status=__("Updated");
				$disp->updateDisposition();
			}
		}
	}

	$dispList = Disposition::getDisposition();

	if ( isset( $_POST['dispositionid'] )) {
		$tmp = Disposition::getDisposition( $_POST['dispositionid']);
		if ( count($tmp) == 1 ) {
			$disp = $tmp[0];
		} else {
			$disp = new Disposition;
		}
	}

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>openDCIM Data Center Inventory</title>
  
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript" src="scripts/common.js"></script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<h3>',$status,'</h3>
<div class="center"><div>
<form id="dispositionform" method="POST">
<h2>'.__("Disposal Mechanism").'</h2>
<div class="table">
<div>
   <div><label for="dispositionid">',__("Disposition ID"),'</label></div>
   <div><select name="dispositionid" id="dispositionid" onChange="form.submit()">
      <option value="0">',__("New Disposal Method"),'</option>';

	foreach($dispList as $d){
		if($d->DispositionID == $disp->DispositionID){$selected=" selected";}else{$selected="";}
		print "<option value=\"$d->DispositionID\"$selected>$d->Name</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="name">',__("Name"),'</label></div>
   <div><input class="validate[required,minSize[3],maxSize[80]]" type="text" name="name" id="name" size="50" maxlength="80" value="',$disp->Name,'"></div>
</div>
<div>
   <div><label for="description">',__("Description"),'</label></div>
   <div><input type="text" name="description" id="description" size="50" maxlength="255" value="',$disp->Description,'"></div>
</div>
<div>
   <div><label for="referencenumber">',__("Reference Number (Contract)"),'</label></div>
   <div><input type="text" name="referencenumber" id="referencenumber" size="50" maxlength="80" value="',$disp->ReferenceNumber,'"></div>
</div>
<div>
	<div><label for="status">',__("Status"),'</label></div>
	<div><select name="status" id="status">';
	foreach( array( "Active", "Inactive" ) as $stat ) {
		if ( $disp->Status == $stat ) { $selected="selected";} else { $selected=""; }
		print "<option value=\"$stat\" $selected>" . __($stat) . "</option>\n";
	}
echo '	</select></div>
</div>
<div>
	<div></div>
	<div>',__("You must always have at least one Active disposal method."),'
<div class="caption">';
	if($disp->DispositionID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>';
	}else{
		echo '   <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
echo '</div>
</form>
<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
