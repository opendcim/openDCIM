<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Data Center View/Export");

	$datacenter=new DataCenter();
	$dcList=$datacenter->GetDCList();
	
	$dept=new Department();
	$dev=new Device();
	
	$body="";

	/* Preloading the entire array of templates and manufacturers should fit into memory, even for large
		installations.   It reduces the complexity of the SQL query, too.   Previous SQL that we had
		was doing a RIGHT JOIN, meaning devices with no template specified were missed in the result set.
		This is much simpler than making multiple complicated LEFT JOIN statements and stitching the
		result set together.
	*/

	$tpList=DeviceTemplate::GetTemplateList(true);
	$mfList=Manufacturer::GetManufacturerList(true);
	$cList=$person->GetUserList(true);

	/* This is a helper function to deal with nested devices aka russian nesting dolls */
	function processChassis($dev,$dept,$row,$ca_result,$tpList,$cList){
		// Find all of the children!
		$childList=$dev->GetDeviceChildren();
		
		$body="";
		foreach($childList as $child){
			$cinsDate=date("Y-m-d",strtotime($child->InstallDate));
			$cwarrantyDate=date("Y-m-d",strtotime($child->WarrantyExpire));
			$cMfgDate=date("Y-m-d",strtotime($child->MfgDate));
			$cModel="";
			$cDepartment="";
			$cAuditStampDate=((strtotime($child->AuditStamp)>0)?date('r',strtotime($child->AuditStamp)):NULL);
			$cHalfDepth=((($child->HalfDepth)>0)?__("True"):__("False"));
			$cBackSide=((($child->BackSide)>0)?__("True"):__("False"));

			$ctags=implode(",", $child->GetTags());
			if($child->TemplateID >0){
				$cModel="<a href=\"device_templates.php?TemplateID=".$child->TemplateID."\" target=\"template\">" . $tpList[$child->TemplateID]->Model . "</a>";
			}
			
			if($child->Owner >0){
				$dept->DeptID=$child->Owner;
				$dept->GetDeptByID();
				$cDepartment=$dept->Name;
			}

			if($child->PrimaryContact >0){
				$contact="<a href=\"usermgr.php?PersonID=$child->PrimaryContact\">{$cList[$child->PrimaryContact]->LastName}, {$cList[$child->PrimaryContact]->FirstName}</a>";
			}else{
				$contact=__("Unassigned");
			}

			$ca_cells = '';
			foreach($ca_result as $ca_row){
				if($ca_row["AttributeType"] == "date" && is_null($child->$ca_row["Label"]) == FALSE){
					$ca_date = date("d M Y",strtotime($child->$ca_row["Label"]));
					$ca_cells .= "\t<td>{$ca_date}</td>";
				}else{
					$cadata=(isset($child->$ca_row["Label"]) && !is_null($child->$ca_row["Label"]))?$child->$ca_row["Label"]:"";
					$ca_cells .= "\t<td>$cadata</td>";
				}
			}

			$body .= "\t\t<tr>
			\t<td><a href=\"devices.php?DeviceID=$child->DeviceID\" target=\"device\">{$child->DeviceID}</a></td>
			\t<td><a href=\"dc_stats.php?dc={$row["DataCenterID"]}\" target=\"datacenter\">{$row["DataCenter"]}</a></td>
			\t<td><a href=\"cabnavigator.php?cabinetid={$row["CabinetID"]}\" target=\"cabinet\">{$row["Location"]}</a></td>
			\t<td>{$row["Position"]}</td>
			\t<td>[-Child-]</td>
			\t<td><a href=\"devices.php?DeviceID=$child->DeviceID\" target=\"device\">$child->Label</a></td>
			\t<td>$child->SerialNo</td>
			\t<td>$child->AssetTag</td>
			\t<td>$child->Status</td>
			\t<td>$child->PrimaryIP</td>
			\t<td><a href=\"search.php?key=dev&DeviceType=$child->DeviceType&search\" target=\"search\">$child->DeviceType</a></td>
			\t<td>$cModel</td>
			\t<td>$ctags</td>
			\t<td>$cDepartment</td>
			\t<th>$contact</th>
			\t<td>$cwarrantyDate</td>
			\t<td>$child->WarrantyCo</td>
			\t<td>$cMfgDate</td>
			\t<td>$cinsDate</td>
			\t<td>$child->NominalWatts</td>
			\t<td>$child->Weight</td>
			\t<td>$child->Ports</td>
			\t<td>$cAuditStampDate</td>
			\t<td>$cHalfDepth</td>
			\t<td>$cBackSide</td>
			\t{$ca_cells}\n\t\t</tr>\n";

			if($child->DeviceType=="Chassis"){
				$chassis=processChassis($child,$dept,$row,$ca_result,$tpList,$cList);
				$body.=$chassis;
			}
		}
		return $body;
	}

	if(isset($_REQUEST['datacenterid'])){
		$dc=isset($_POST['datacenterid'])?$_POST['datacenterid']:$_GET['datacenterid'];
		$ca_headers = '';
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
				b.Height, b.Label, b.DeviceType, b.AssetTag, b.SerialNo, b.InstallDate, 
				b.Status, b.PrimaryIP, b.NominalWatts, b.Weight, b.Ports, b.AuditStamp, b.HalfDepth, b.BackSide,  
				b.WarrantyExpire, b.WarrantyCo, b.MfgDate, b.ParentDevice, b.PrimaryContact, b.TemplateID, 
				b.Owner, c.CabinetID, c.DataCenterID $custom_concat FROM fac_DataCenter a,
				fac_Cabinet c, fac_Device b  LEFT OUTER JOIN fac_DeviceCustomValue d on
				b.DeviceID=d.DeviceID WHERE b.Cabinet=c.CabinetID AND c.DataCenterID=a.DataCenterID
				$dclimit
				GROUP BY DeviceID ORDER BY DataCenter ASC, Location ASC, Position ASC;";
			$result=$dbh->query($sql);

			foreach($ca_result as $ca_row){
				$ca_headers .= "\t<th>{$ca_row["Label"]}</th>";
			}
		}else{
			$result=array();
		}

		// Left these expanded in case we need to add or remove columns.  Otherwise I would have just collapsed entirely.
		$body="<table id=\"export\" class=\"display\">\n\t<thead>\n\t\t<tr>\n
			\t<th>".__("DeviceID")."</th>
			\t<th>".__("Data Center")."</th>
			\t<th>".__("Location")."</th>
			\t<th>".__("Position")."</th>
			\t<th>".__("Height")."</th>
			\t<th>".__("Name")."</th>
			\t<th>".__("Serial Number")."</th>
			\t<th>".__("Asset Tag")."</th>
			\t<th>".__("Status")."</th>
			\t<th>".__("Primary IP / Host Name")."</th>
			\t<th>".__("Device Type")."</th>
			\t<th>".__("Template")."</th>
			\t<th>".__("Tags")."</th>
			\t<th>".__("Owner")."</th>
			\t<th>".__("Primary Contact")."</th>
			\t<th>".__("Warranty Expiration")."</th>
			\t<th>".__("Warranty Company")."</th>
			\t<th>".__("Manufacture Date")."</th>
			\t<th>".__("Installation Date")."</th>
			\t<th>".__("Power")."</th>
			\t<th>".__("Weight")."</th>
			\t<th>".__("Ports")."</th>
			\t<th>".__("Audit Stamp")."</th>
			\t<th>".__("Half Depth")."</th>
			\t<th>".__("Back Side")."</th>
			{$ca_headers}
			</tr>\n\t</thead>\n\t<tbody>\n";

		// suppressing errors for when there is a fake data set in place
		foreach($result as $row){
			// Dont show devices in chassis, they are shown under each chassiss as a child device
			if($row["ParentDevice"]=="0"){
				// insert date formating later for regionalization settings
				$insDate=date("Y-m-d",strtotime($row["InstallDate"]));
				$warrantyDate=date("Y-m-d",strtotime($row["WarrantyExpire"]));
				$MfgDate=date("Y-m-d",strtotime($child->MfgDate));
				$Model="";
				$Department="";
				$AuditStampDate=((strtotime($row["AuditStamp"])>0)?date('r',strtotime($row["AuditStamp"])):NULL);
				$HalfDepth=((($row["HalfDepth"])>0)?__("True"):__("False"));
				$BackSide=((($row["BackSide"])>0)?__("True"):__("False"));

				if($row["TemplateID"]>0 && array_key_exists( $row["TemplateID"], $tpList )){
					$Model="<a href=\"device_templates.php?TemplateID=".$row["TemplateID"]."\" target=\"template\">" . $tpList[$row["TemplateID"]]->Model . "</a>";
				}
				
				if($row["Owner"] >0){
					$dept->DeptID=$row["Owner"];
					$dept->GetDeptByID();
					$Department=$dept->Name;
				}

				if($row["PrimaryContact"] >0){
					$contact="<a href=\"usermgr.php?PersonID={$row["PrimaryContact"]}\">{$cList[$row["PrimaryContact"]]->LastName}, {$cList[$row["PrimaryContact"]]->FirstName}</a>";
				}else{
					$contact=__("Unassigned");
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
				\t<td><a href=\"devices.php?DeviceID=$dev->DeviceID\" target=\"device\">{$dev->DeviceID}</a></td>
				\t<td><a href=\"dc_stats.php?dc={$row["DataCenterID"]}\" target=\"datacenter\">{$row["DataCenter"]}</a></td>
				\t<td><a href=\"cabnavigator.php?cabinetid={$row["CabinetID"]}\" target=\"cabinet\">{$row["Location"]}</a></td>
				\t<td>{$row["Position"]}</td>
				\t<td>{$row["Height"]}</td>
				\t<td><a href=\"devices.php?DeviceID=$dev->DeviceID\" target=\"device\">{$row["Label"]}</a></td>
				\t<td>{$row["SerialNo"]}</td>
				\t<td>{$row["AssetTag"]}</td>
				\t<td>{$row["Status"]}</td>
				\t<td>{$row["PrimaryIP"]}</td>
				\t<td><a href=\"search.php?key=dev&DeviceType={$row["DeviceType"]}&search\" target=\"search\">{$row["DeviceType"]}</a></td>
				\t<td>$Model</td>
				\t<td>$tags</td>
				\t<td>$Department</td>
				\t<th>$contact</th>
				\t<td>$warrantyDate</td>
				\t<td>{$row["WarrantyCo"]}</td>
				\t<td>$MfgDate</td>
				\t<td>$insDate</td>				\t<td>{$row["NominalWatts"]}</td>
				\t<td>{$row["Weight"]}</td>
				\t<td>{$row["Ports"]}</td>
				\t<td>{$AuditStampDate}</td>
				\t<td>{$HalfDepth}</td>
				\t<td>{$BackSide}</td>
				{$ca_cells}\t\n\t\t</tr>\n";

				if($row["DeviceType"]=="Chassis"){
					$chassis=processChassis($dev,$dept,$row,$ca_result,$tpList,$cList);
					$body.=$chassis;
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
				"drawCallback": function( settings ) {
					redraw();resize();
				},
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
