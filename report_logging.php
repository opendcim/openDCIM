<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$datacenter=new DataCenter();
	$dcList=$datacenter->GetDCList();
	
	$templ=new DeviceTemplate();
	$dept=new Department();
	$dev=new Device();
	
	$body="";

	$result=LogActions::GetLog();

	// Left these expanded in case we need to add or remove columns.  Otherwise I would have just collapsed entirely.
	$body="<table id=\"export\" class=\"display\">\n\t<thead>\n\t\t<tr>\n
		\t<th>".__("Time")."</th>
		\t<th>".__("User")."</th>
		\t<th>".__("Class")."</th>
		\t<th>".__("ObjectID")."</th>
		\t<th>".__("ChildID")."</th>
		\t<th>".__("Action")."</th>
		\t<th>".__("Property")."</th>
		\t<th>".__("Old Value")."</th>
		\t<th>".__("New Value")."</th>
		</tr>\n\t</thead>\n\t<tbody>\n";

	// suppressing errors for when there is a fake data set in place
	foreach($result as $logitem){
		$body.="\t\t<tr>
		\t<td>$logitem->Time</td>
		\t<td>$logitem->UserID</td>
		\t<td>$logitem->Class</td>
		\t<td>$logitem->ObjectID</td>
		\t<td>$logitem->ChildID</td>
		\t<td>$logitem->Action</td>
		\t<td>$logitem->Property</td>
		\t<td>$logitem->OldVal</td>
		\t<td>$logitem->NewVal</td>\n\t\t</tr>\n";
	}
	$body.="\t\t</tbody>\n\t</table>\n";
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.dataTables.css" type="text/css">
  <link rel="stylesheet" href="css/ColVis.css" type="text/css">
  <link rel="stylesheet" href="css/TableTools.css" type="text/css">
  <style type="text/css"></style>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="scripts/ColVis.min.js"></script>
  <script type="text/javascript" src="scripts/TableTools.min.js"></script>
  
  <script type="text/javascript">
	$(document).ready(function(){
		function dt(){
			$('#export').dataTable({
				"iDisplayLength": 25,
				"sDom": 'CT<"clear">lfrtip',
				"oTableTools": {
					"sSwfPath": "scripts/copy_csv_xls.swf",
					"aButtons": ["copy","csv","xls","print"]
				}
			});
			resize();
		}
		dt();
	});
  </script>
</head>
<body>
	<div id="header"></div>
	<div class="page">
<?php
	include('sidebar.inc.php');
echo '		<div class="main">
			<h2>',$config->ParameterArray['OrgName'],'</h2>
			<h3>',__("Logging View/Export"),'</h3>
			<br><br>
			<div class="center">
				<div id="tablecontainer">',$body,'
				</div>
			</div>
		</div><!-- END div.main -->
	</div><!-- END div.page -->
</body>
</html>';
