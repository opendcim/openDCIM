<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$user=new User();

	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$dc=new DataCenter();
	if(isset($_REQUEST['action'])&&(($_REQUEST['action']=='Create')||($_REQUEST['action']=='Update'))){
		$dc->DataCenterID=$_REQUEST['datacenterid'];
		$dc->Name=$_REQUEST['name'];
		$dc->SquareFootage=$_REQUEST['squarefootage'];
		$dc->DeliveryAddress=$_REQUEST['deliveryaddress'];
		$dc->Administrator=$_REQUEST['administrator'];
		$dc->DrawingFileName=$_REQUEST['drawingfilename'];
		
		if($_REQUEST['action']=='Create'){
			$dc->CreateDataCenter($facDB);
		}else{
			$dc->UpdateDataCenter($facDB);
		}
	}

	if(isset($_REQUEST['datacenterid'])&&$_REQUEST['datacenterid'] >0){
		$dc->DataCenterID=$_REQUEST['datacenterid'];
		$dc->GetDataCenter($facDB);
	}
	$dcList=$dc->GetDCList($facDB);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>openDCIM Data Center Inventory</title>
  
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',_("Data Center Detail"),'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div><label for="datacenterid">',_("Data Center ID"),'</label></div>
   <div><select name="datacenterid" id="datacenterid" onChange="form.submit()">
      <option value="0">',_("New Data Center"),'</option>';

	foreach($dcList as $dcRow){
		if($dcRow->DataCenterID == $dc->DataCenterID){$selected=" selected";}else{$selected="";}
		print "<option value=\"$dcRow->DataCenterID\"$seelcted>$dcRow->Name</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="dcname">',_("Name"),'</label></div>
   <div><input type="text" name="name" id="dcname" size="50" value="',$dc->Name,'"></div>
</div>
<div>
   <div><label for="sqfootage">',_("Square Footage"),'</label></div>
   <div><input type="text" name="squarefootage" id="sqfootage" size="10" value="',$dc->SquareFootage,'"></div>
</div>
<div>
   <div><label for="deliveryaddress">',_("Delivery Address"),'</label></div>
   <div><input type="text" name="deliveryaddress" id="deliveryaddress" size="60" value="',$dc->DeliveryAddress,'"></div>
</div>
<div>
   <div><label for="administrator">',_("Administrator"),'</label></div>
   <div><input type="text" name="administrator" id="administrator" size=60 value="',$dc->Administrator,'"></div>
</div>
<div>
   <div><label for="drawingfilename">',_("Drawing URL"),'</label></div>
   <div><input type="text" name="drawingfilename" id="drawingfilename" size=60 value="',$dc->DrawingFileName,'"></div>
</div>
<div class="caption">';

	if($dc->DataCenterID >0){
		echo '   <button type="submit" name="action" value="Update">',_("Update"),'</button>';
	}else{
		echo '   <button type="submit" name="action" value="Create">',_("Create"),'</button>';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',_("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
