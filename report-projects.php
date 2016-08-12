<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Project View/Export");

	$pList = Projects::getProjectList();	
	$templ=new DeviceTemplate();
	$dept=new Department();
	$dev=new Device();
	
	$body="";

	if(isset($_REQUEST['projectid'])){
		$project=isset($_POST['projectid'])?$_POST['projectid']:$_GET['projectid'];
		if($project!=''){
			if ( $project == "all" ) {
				$projLimit = "";
			} else {
				$projLimit = "and a.ProjectID=" . intval($project);
			}
			$sql="SELECT a.ProjectID, a.ProjectName, b.DeviceID, c.Location, b.Position, 
				b.Height, b.Label, b.DeviceType, b.AssetTag, b.SerialNo, b.InstallDate, 
				b.TemplateID, b.Owner, e.Name as DataCenterName, c.CabinetID, e.DataCenterID 
				FROM fac_Projects a, fac_Device b, fac_Cabinet c, fac_ProjectMembership d, 
				fac_DataCenter e WHERE b.ParentDevice=0 AND a.ProjectID=d.ProjectID AND 
				d.DeviceID=b.DeviceID AND b.Cabinet=c.CabinetID AND 
				c.DataCenterID=e.DataCenterID $projLimit ORDER BY a.ProjectName ASC, 
				e.Name ASC, c.Location ASC, b.Position ASC";
			$result=$dbh->query($sql);
		}else{
			$result=array();
		}

		// Left these expanded in case we need to add or remove columns.  Otherwise I would have just collapsed entirely.
		$body="<table id=\"export\" class=\"display\">\n\t<thead>\n\t\t<tr>\n
			\t<th>".__("Project Name")."</th>
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
			</tr>\n\t</thead>\n\t<tbody>\n";

		// suppressing errors for when there is a fake data set in place
		foreach($result as $row){
			if($row["TemplateID"] >0){
				$templ->TemplateID=$row["TemplateID"];
				$templ->GetTemplateByID();
				$Model="<a href=\"device_templates.php?TemplateID=$templ->TemplateID\" target=\"template\">$templ->Model</a>";
			}
			
			if($row["Owner"] >0){
				$dept->DeptID=$row["Owner"];
				$dept->GetDeptByID();
				$Department=$dept->Name;
			}else{
				// Adding a placeholder to silence an error
				$Department='';
			}
			$dev->DeviceID=$row["DeviceID"];
			$tags=implode(",", $dev->GetTags());
			$body.="\t\t<tr>
			\t<td><a href=\"project_mgr.php?projectid={$row["ProjectID"]}\" target=\"project\">{$row["ProjectName"]}</a></td>
			\t<td><a href=\"dc_stats.php?dc={$row["DataCenterID"]}\" target=\"datacenter\">{$row["DataCenterName"]}</a></td>
			\t<td><a href=\"cabnavigator.php?cabinetid={$row["CabinetID"]}\" target=\"cabinet\">{$row["Location"]}</a></td>
			\t<td>{$row["Position"]}</td>
			\t<td>{$row["Height"]}</td>
			\t<td><a href=\"devices.php?DeviceID=$dev->DeviceID\" target=\"device\">{$row["Label"]}</a></td>
			\t<td>{$row["SerialNo"]}</td>
			\t<td>{$row["AssetTag"]}</td>
			\t<td><a href=\"search.php?key=dev&DeviceType={$row["DeviceType"]}&search\" target=\"search\">{$row["DeviceType"]}</a></td>
			\t<td>$Model</td>
			\t<td>$tags</td>
			\t<td>$Department</td>
			\n\t\t</tr>\n";
			
			if($row["DeviceType"]=="Chassis"){
				// Find all of the children!
				$childList=$dev->GetDeviceChildren();
				
				foreach($childList as $child){
					$cModel="";
					$cDepartment="";					

					$ctags=implode(",", $child->GetTags());
					if($child->TemplateID >0){
						$templ->TemplateID=$child->TemplateID;
						$templ->GetTemplateByID();
						$cModel="<a href=\"device_templates.php?TemplateID=$templ->TemplateID\" target=\"template\">$templ->Model</a>";
					}
					
					if($child->Owner >0){
						$dept->DeptID=$child->Owner;
						$dept->GetDeptByID();
						$cDepartment=$dept->Name;
					}

					// See if this is a direct member of the project, or just along for the ride (inherited from chassis)
					$mList = ProjectMembership::getDeviceMembership( $child->DeviceID );
					$lineage = "<a href=\"project_mgr.php?projectid={$row["ProjectID"]}\" target=\"project\">{$row["ProjectName"]} (Inherited)</a>";
					foreach( $mList as $member ) {
						if ( $member->ProjectID == $row["ProjectID"] ) {
								$lineage = "<a href=\"project_mgr.php?projectid={$row["ProjectID"]}\" target=\"project\">{$row["ProjectName"]}</a>";
								break;
						}
					}

					$body .= "\t\t<tr>
					\t<td>$lineage</td>
					\t<td><a href=\"dc_stats.php?dc={$row["DataCenterID"]}\" target=\"datacenter\">{$row["DataCenterName"]}</a></td>
					\t<td><a href=\"cabnavigator.php?cabinetid={$row["CabinetID"]}\" target=\"cabinet\">{$row["Location"]}</a></td>
					\t<td>{$row["Position"]}</td>
					\t<td>[-Child-]</td>
					\t<td><a href=\"devices.php?DeviceID=$child->DeviceID\" target=\"device\">$child->Label</a></td>
					\t<td>$child->SerialNo</td>
					\t<td>$child->AssetTag</td>
					\t<td><a href=\"search.php?key=dev&DeviceType=$child->DeviceType&search\" target=\"search\">$child->DeviceType</a></td>
					\t<td>$cModel</td>
					\t<td>$ctags</td>
					\t<td>$cDepartment</td>
					\n\t\t</tr>\n";
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
  <script type="text/javascript" src="scripts/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="scripts/ColVis.min.js"></script>
  <script type="text/javascript" src="scripts/TableTools.min.js"></script>
  
  <script type="text/javascript">
	$(document).ready(function(){
		var rows;
		function dt(){
			$('#export').dataTable({
				"iDisplayLength": 25,
				"ordering": false,
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
		$('#projectid').change(function(){
			$.post('', {projectid: $(this).val(), ajax: ''}, function(data){
				$('#tablecontainer').html(data);
				dt();
			});
		});
	});
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
	<div class="page">
<?php
	include('sidebar.inc.php');
echo '		<div class="main">
			<label for="projectid">',__("Project Name:"),'</label>
			<select name="projectid" id="projectid">
				<option value="">',__("Select Project"),'</option>
				<option value="all">',__("All Projects"),'</option>';
foreach($pList as $p){print "\t\t\t\t<option value=\"$p->ProjectID\">$p->ProjectName</option>\n";} ?>
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
