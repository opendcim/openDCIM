<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
	
	$datacenter=new DataCenter();
	$dcList=$datacenter->GetDCList($facDB);
	
	$dev = new Device();
	
	$body="";

	if(isset($_REQUEST['datacenterid'])){
		$dc=isset($_POST['datacenterid'])?$_POST['datacenterid']:$_GET['datacenterid'];
		if($dc!=''){
			$dc=intval($dc);
			if($dc==0){
				$sql="select a.Name as DataCenter, e.Location, b.Position, b.Height, b.Label, b.DeviceType, c.Model, d.Name as Department, b.InstallDate from fac_DataCenter a, fac_Device b, fac_DeviceTemplate c, fac_Department d, fac_Cabinet e where b.Cabinet=e.CabinetID and e.DataCenterID=a.DataCenterID and b.TemplateID=c.TemplateID and b.Owner=d.DeptID order by DataCenter ASC, Location ASC, Position ASC";
			}else{
				$sql="select a.Name as DataCenter, e.Location, b.Position, b.Height, b.Label, b.DeviceType, c.Model, d.Name as Department, b.InstallDate from fac_DataCenter a, fac_Device b, fac_DeviceTemplate c, fac_Department d, fac_Cabinet e where b.Cabinet=e.CabinetID and e.DataCenterID=a.DataCenterID and b.TemplateID=c.TemplateID and b.Owner=d.DeptID and e.DataCenterID=$dc order by Location ASC, Position ASC";
			}
			$result=mysql_query($sql,$facDB);
		}else{
			$result=array();
		}

		// Left these expanded in case we need to add or remove columns.  Otherwise I would have just collapsed entirely.
		$body=sprintf( "<table id=\"export\" class=\"display\">\n\t<thead>\n\t\t<tr>\n
			\t<th>%s</th>
			\t<th>%s</th>
			\t<th>%s</th>
			\t<th>%s</th>
			\t<th>%s</th>
			\t<th>%s</th>
			\t<th>%s</th>
			\t<th>%s</th>
			\t<th>%s</th>
			\t<th>%s</th>
			\t<th>%s</th>
			</tr>\n\t</thead>\n\t<tbody>\n", __("Data Center"), __("Location"), __("Position"), __("Height"), 
			__("Name"), __("Serial Number"), __("Asset Tag"), __("Device Type"), __("Template"), __("Owner"),
			__("Installation Date") );

		// suppressing errors for when there is a fake data set in place
		while($row=@mysql_fetch_array($result)){
			// insert date formating later for regionalization settings
			$date=date("d M Y",strtotime($row["InstallDate"]));
			$body.="\t\t<tr>
			\t<td>{$row["DataCenter"]}</td>
			\t<td>{$row["Location"]}</td>
			\t<td>{$row["Position"]}</td>
			\t<td>{$row["Height"]}</td>
			\t<td>{$row["Label"]}</td>
			\t<td>{$row["SerialNo"]}</td>
			\t<td>{$row["AssetTag"]}</td>
			\t<td>{$row["DeviceType"]}</td>
			\t<td>{$row["Model"]}</td>
			\t<td>{$row["Department"]}</td>
			\t<td>{$date}</td>\n\t\t</tr>\n";
			
			
		}
		$body.="\t\t</tbody>\n\t</table>\n";
		if(isset($_REQUEST['ajax'])){
			echo $body;
			exit;
		}
	}
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
  <script type="text/javascript" src="scripts/jquery-migrate-1.0.0.js"></script>
  <script type="text/javascript" src="scripts/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="scripts/ColVis.min.js"></script>
  <script type="text/javascript" src="scripts/TableTools.min.js"></script>
  
  <script type="text/javascript">
	$(document).ready(function(){
		var rows;
		function dt(){
			$('#export').dataTable({
				"iDisplayLength": 25,
				"sDom": 'CT<"clear">lfrtip',
				"oTableTools": {
					"sSwfPath": "scripts/copy_csv_xls.swf",
					"aButtons": ["copy","csv","xls","print"]
				}
			});
		}
		dt();
		$('.main').width($('#header').innerWidth()-$('#sidebar').outerWidth-34);
		$('#datacenterid').change(function(){
			$.post('', {datacenterid: $(this).val(), ajax: ''}, function(data){
				$('#tablecontainer').html(data);
				dt();
			});
		});
	});
  </script>
</head>
<body>
	<div id="header"></div>
	<div class="page">
<?php
	include('sidebar.inc.php');
?>
		<div class="main">
			<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
			<h3><?php echo __("Data Center View/Export"); ?></h3>
			<label for="datacenterid"><?php echo __("Data Center").':'; ?></label>
			<select name="datacenterid" id="datacenterid">
				<option value=""><?php echo __("Select Data Center"); ?></option>
				<option value="0"><?php echo __("All Data Centers"); ?></option>
<?php foreach($dcList as $dc){print "\t\t\t\t<option value=\"$dc->DataCenterID\">$dc->Name</option>\n";} ?>
			</select>
			<br><br>
			<div class="center">
				<div id="tablecontainer">
<?php echo $body; ?>
				</div>
			</div>
		</div><!-- END div.main -->
	</div><!-- END div.page -->
</body>
</html>
