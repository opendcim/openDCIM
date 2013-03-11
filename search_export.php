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
	
	$templ = new DeviceTemplate();
	$dept = new Department();
	
	$dev = new Device();
	
	$body="";

	if(isset($_REQUEST['datacenterid'])){
		$dc=isset($_POST['datacenterid'])?$_POST['datacenterid']:$_GET['datacenterid'];
		if($dc!=''){
			$dc=intval($dc);
			if($dc==0){
				$sql="select a.Name as DataCenter, b.DeviceID, c.Location, b.Position, b.Height, b.Label, b.DeviceType, b.AssetTag, b.SerialNo, b.InstallDate, b.TemplateID, b.Owner from fac_DataCenter a, fac_Device b, fac_Cabinet c where b.Cabinet=c.CabinetID and c.DataCenterID=a.DataCenterID order by DataCenter ASC, Location ASC, Position ASC";
			}else{
				$sql="select a.Name as DataCenter, b.DeviceID, c.Location, b.Position, b.Height, b.Label, b.DeviceType, b.AssetTag, b.SerialNo, b.InstallDate, b.TemplateID, b.Owner from fac_DataCenter a, fac_Device b, fac_Cabinet c where b.Cabinet=c.CabinetID and c.DataCenterID=a.DataCenterID and c.DataCenterID=$dc order by Location ASC, Position ASC";
			}
			$result=mysql_query($sql,$facDB);
		}else{
			$result=array();
		}

		// Left these expanded in case we need to add or remove columns.  Otherwise I would have just collapsed entirely.
		$body="<table id=\"export\" class=\"display\">\n\t<thead>\n\t\t<tr>\n
			\t<th>".__("Data Center")."</th>
			\t<th>".__("Location")."</th>
			\t<th>".__("Position")."</th>
			\t<th>".__("Height")."</th>
			\t<th>".__("Name")."</th>
			\t<th>".__("Serial Number")."</th>
			\t<th>".__("Asset Tag")."</th>
			\t<th>".__("Device Type")."</th>
			\t<th>".__("Template")."</th>
			\t<th>".__("Tags")."</th>
			\t<th>".__("Owner")."</th>
			\t<th>".__("Installation Date")."</th>
			</tr>\n\t</thead>\n\t<tbody>\n";

		// suppressing errors for when there is a fake data set in place
		while($row=@mysql_fetch_array($result)){
			// insert date formating later for regionalization settings
			$date=date("d M Y",strtotime($row["InstallDate"]));
				$Model="";
				$Department="";
			
			if($row["TemplateID"] >0){
				$templ->TemplateID=$row["TemplateID"];
				$templ->GetTemplateByID($facDB);
				$Model=$templ->Model;
			}
			
			if($row["Owner"] >0){
				$dept->DeptID=$row["Owner"];
				$dept->GetDeptByID($facDB);
				$Department=$dept->Name;
			}
			$dev->DeviceID=$row["DeviceID"];
			$tags=implode(",", $dev->GetTags());
			$body.="\t\t<tr>
			\t<td>{$row["DataCenter"]}</td>
			\t<td>{$row["Location"]}</td>
			\t<td>{$row["Position"]}</td>
			\t<td>{$row["Height"]}</td>
			\t<td>{$row["Label"]}</td>
			\t<td>{$row["SerialNo"]}</td>
			\t<td>{$row["AssetTag"]}</td>
			\t<td>{$row["DeviceType"]}</td>
			\t<td>$Model</td>
			\t<td>$tags</td>
			\t<td>$Department</td>
			\t<td>$date</td>\n\t\t</tr>\n";
			
			if($row["DeviceType"]=="Chassis"){
				// Find all of the children!
				$childList=$dev->GetDeviceChildren($facDB);
				
				foreach($childList as $child){
					$cdate=date("d M Y",strtotime($child->InstallDate));
					$cModel="";
					$cDepartment="";					

					$ctags=implode(",", $child->GetTags());
					if($child->TemplateID >0){
						$templ->TemplateID=$child->TemplateID;
						$templ->GetTemplateByID($facDB);
						$cModel=$templ->Model;
					}
					
					if($child->Owner >0){
						$dept->DeptID=$child->Owner;
						$dept->GetDeptByID($facDB);
						$cDepartment=$dept->Name;
					}

					$body .= "\t\t<tr>
					\t<td>{$row["DataCenter"]}</td>
					\t<td>{$row["Location"]}</td>
					\t<td>{$row["Position"]}</td>
					\t<td>[-Child-]</td>
					\t<td>$child->Label</td>
					\t<td>$child->SerialNo</td>
					\t<td>$child->AssetTag</td>
					\t<td>$child->DeviceType</td>
					\t<td>$cModel</td>
					\t<td>$ctags</td>
					\t<td>$cDepartment</td>
					\t<td>$cdate</td>\n\t\t</tr>\n";
				}
			}
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
			redraw();
		}
		function redraw(){
			if(($('#export').outerWidth()+$('#sidebar').outerWidth()+10)<$('.page').innerWidth()){
				$('.main').width($('#header').innerWidth()-$('#sidebar').outerWidth()-16);
			}else{
				$('.main').width($('#export').outerWidth()+40);
			}
			$('.page').width($('.main').outerWidth()+$('#sidebar').outerWidth()+10);
		}
		dt();
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
echo '		<div class="main">
			<h2>',$config->ParameterArray['OrgName'],'</h2>
			<h3>',__("Data Center View/Export"),'</h3>
			<label for="datacenterid">',__("Data Center:"),'</label>
			<select name="datacenterid" id="datacenterid">
				<option value="">',__("Select data center"),'</option>
				<option value="0">',__("All Data Centers"),'</option>';
foreach($dcList as $dc){print "\t\t\t\t<option value=\"$dc->DataCenterID\">$dc->Name</option>\n";} ?>
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
