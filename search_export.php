<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Data Center View/Export");

	$datacenter=new DataCenter();
	$dcList=$datacenter->GetDCList();
	
	$templ=new DeviceTemplate();
	$dept=new Department();
	$dev=new Device();
	
	$body="";

	if(isset($_REQUEST['datacenterid'])){
		$dc=isset($_POST['datacenterid'])?$_POST['datacenterid']:$_GET['datacenterid'];
		if($dc!=''){
			$dc=intval($dc);
			$dclimit=($dc==0)?'':" and c.DataCenterID=$dc ";
			$ca_sql="SELECT AttributeID, Label, AttributeType from fac_DeviceCustomAttribute ORDER BY AttributeID ASC;";
			$custom_concat = '';
			$ca_result=$dbh->query($ca_sql)->fetchAll();
			foreach($ca_result as $ca_row){
				$custom_concat .= ", GROUP_CONCAT(IF(d.AttributeID={$ca_row["AttributeID"]},value,NULL)) AS Attribute{$ca_row["AttributeID"]} ";
			} 

			$sql="SELECT a.Name AS DataCenter, b.DeviceID, c.Location, b.Position, 
				b.Height, b.Label, b.DeviceType, b.AssetTag, b.SerialNo, b.InstallDate, b.WarrantyExpire, b.PrimaryIP, b.ParentDevice,
				b.TemplateID, b.Owner, c.CabinetID, c.DataCenterID, f.Name as Manufacturer $custom_concat FROM fac_DataCenter a,
				fac_Cabinet c, fac_DeviceTemplate e, fac_Manufacturer f, fac_Device b  LEFT OUTER JOIN fac_DeviceCustomValue d on
				b.DeviceID=d.DeviceID WHERE b.Cabinet=c.CabinetID AND c.DataCenterID=a.DataCenterID AND b.TemplateID=e.TemplateID
				AND e.ManufacturerID=f.ManufacturerID AND f.Name!='Virtual' $dclimit
				GROUP BY DeviceID ORDER BY DataCenter ASC, Location ASC, Position ASC;";
			$result=$dbh->query($sql);
		
			$ca_headers = '';
			foreach($ca_result as $ca_row){
				$ca_headers .= "\t<th>{$ca_row["Label"]}</th>";
			}
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
      			\t<th>".__("Primary IP / Host Name")."</th>
			\t<th>".__("Device Type")."</th>
			\t<th>".__("Template")."</th>
			\t<th>".__("Tags")."</th>
			\t<th>".__("Owner")."</th>
			\t<th>".__("Installation Date")."</th>
			\t<th>".__("Warranty Expiration")."</th>
			{$ca_headers}
			</tr>\n\t</thead>\n\t<tbody>\n";

		// suppressing errors for when there is a fake data set in place
		foreach($result as $row){
			// Dont show devices in chassis, they are shown under each chassiss as a child device
			if($row["ParentDevice"]=="0"){
			// insert date formating later for regionalization settings
			$date=date("Y-m-d",strtotime($row["InstallDate"]));
      			$warranty=date("Y-m-d",strtotime($row["WarrantyExpire"]));
			$Model="";
			$Department="";
			
			if($row["TemplateID"] >0){
				$templ->TemplateID=$row["TemplateID"];
				$templ->GetTemplateByID();
				$Model="<a href=\"device_templates.php?TemplateID=$templ->TemplateID\" target=\"template\">$templ->Model</a>";
			}
			
			if($row["Owner"] >0){
				$dept->DeptID=$row["Owner"];
				$dept->GetDeptByID();
				$Department=$dept->Name;
			}

			$ca_cells = '';
			foreach($ca_result as $ca_row){
				$ca_num = "Attribute".$ca_row["AttributeID"];
                                if($ca_row["AttributeType"] == "date" && is_null($row[$ca_num]) == FALSE){
					$ca_date = date("d M Y",strtotime($row[$ca_num]));
					$ca_cells .= "\t<td>{$ca_date}</td>";
				}else{
				$ca_cells .= "\t<td>{$row[$ca_num]}</td>";
				}
			}

			$dev->DeviceID=$row["DeviceID"];
			$tags=implode(",", $dev->GetTags());
			$body.="\t\t<tr>
			\t<td><a href=\"dc_stats.php?dc={$row["DataCenterID"]}\" target=\"datacenter\">{$row["DataCenter"]}</a></td>
			\t<td><a href=\"cabnavigator.php?cabinetid={$row["CabinetID"]}\" target=\"cabinet\">{$row["Location"]}</a></td>
			\t<td>{$row["Position"]}</td>
			\t<td>{$row["Height"]}</td>
			\t<td><a href=\"devices.php?DeviceID=$dev->DeviceID\" target=\"device\">{$row["Label"]}</a></td>
			\t<td>{$row["SerialNo"]}</td>
			\t<td>{$row["AssetTag"]}</td>
      \t<td>{$row["PrimaryIP"]}</td>
			\t<td><a href=\"search.php?key=dev&DeviceType={$row["DeviceType"]}&search\" target=\"search\">{$row["DeviceType"]}</a></td>
			\t<td>$Model</td>
			\t<td>$tags</td>
			\t<td>$Department</td>
      \t<td>$warranty</td>
			\t<td>$date</td>
			{$ca_cells}\t\n\t\t</tr>\n";
			
			if($row["DeviceType"]=="Chassis"){
				// Find all of the children!
				$childList=$dev->GetDeviceChildren();
				
				foreach($childList as $child){
					$cdate=date("Y-m-d",strtotime($child->InstallDate));
          $cwarranty=date("Y-m-d",strtotime($child->WarrantyExpire));
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

					$body .= "\t\t<tr>
					\t<td><a href=\"dc_stats.php?dc={$row["DataCenterID"]}\" target=\"datacenter\">{$row["DataCenter"]}</a></td>
					\t<td><a href=\"cabnavigator.php?cabinetid={$row["CabinetID"]}\" target=\"cabinet\">{$row["Location"]}</a></td>
					\t<td>{$row["Position"]}</td>
					\t<td>[-Child-]</td>
					\t<td><a href=\"devices.php?DeviceID=$child->DeviceID\" target=\"device\">$child->Label</a></td>
					\t<td>$child->SerialNo</td>
					\t<td>$child->AssetTag</td>
          \t<td>$child->PrimaryIP</td>
					\t<td><a href=\"search.php?key=dev&DeviceType=$child->DeviceType&search\" target=\"search\">$child->DeviceType</a></td>
					\t<td>$cModel</td>
					\t<td>$ctags</td>
					\t<td>$cDepartment</td>
          			\t<td>$cwarranty</td>
					\t<td>$cdate</td>
					\t{$ca_cells}\n\t\t</tr>\n";
				}
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
  <link rel="stylesheet" href="css/jquery.dataTables.min.css" type="text/css">
  <style type="text/css"></style>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="scripts/pdfmake.min.js"></script>
  <script type="text/javascript" src="scripts/vfs_fonts.js"></script>
  <script type="text/javascript">
	$(document).ready(function(){
		var rows;
		function dt(){
			var title=$("#datacenterid option:selected").text() + ' export'
			$('#export').dataTable({
				dom: 'B<"clear">lfrtip',
				buttons:{
					buttons: [
						'copy',
						{
							extend: 'excel',
							title: title
						},
						{
							extend: 'pdf',
							title: title
						},'csv', 'colvis', 'print'
					]
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
<?php include( 'header.inc.php' ); ?>
	<div class="page">
<?php
	include('sidebar.inc.php');
echo '		<div class="main">
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
