<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

if(!$person->ReadAccess){
    // No soup for you.
    header('Location: '.redirect());
    exit;
}

	$subheader=__("Inventory Reporting By Tag");

	$tagsList=Tags::FindAll();
	$body="";

	if(isset($_REQUEST['tagid'])){
		$tag=isset($_POST['tagid'])?$_POST['tagid']:$_GET['tagid'];
		if($tag!=''){
			$tag=intval($tag);
			if($tag==0){
				$sql="SELECT dev.Label, dc.Name as DC, c.Location as Rack, dept.Name as Owner, t.Name as Tag, dev.Notes FROM fac_Tags t, fac_Device dev, fac_Cabinet c, fac_DataCenter dc, fac_DeviceTags dt, fac_Department dept WHERE t.TagID=dt.TagID AND dt.DeviceID=dev.DeviceID AND dev.Cabinet=c.CabinetID AND dc.DataCenterID=c.DataCenterID AND dept.DeptID=dev.Owner ORDER BY dev.Label ASC, Tag ASC;";
			}else{
				$sql="SELECT dev.Label, dc.Name as DC, c.Location as Rack, dept.Name as Owner, t.Name as Tag, dev.Notes FROM fac_Tags t, fac_Device dev, fac_Cabinet c, fac_DataCenter dc, fac_DeviceTags dt, fac_Department dept WHERE t.TagID=dt.TagID AND dt.DeviceID=dev.DeviceID AND dev.Cabinet=c.CabinetID AND dc.DataCenterID=c.DataCenterID AND dept.DeptID=dev.Owner AND dt.TagID=$tag ORDER BY dev.Label ASC, Tag ASC;";
			}
			$result=$dbh->query($sql);
		}else{
			$result=array();
		}

		// Left these expanded in case we need to add or remove columns.  Otherwise I would have just collapsed entirely.
		$body="<table id=\"export\" class=\"display\">\n\t<thead>\n\t\t<tr>\n
			\t<th>".__("Device Label")."</th>
			\t<th>".__("Data Center")."</th>
			\t<th>".__("Rack")."</th>
			\t<th>".__("Owner")."</th>
			\t<th>".__("Tag")."</th>
			\t<th>".__("Notes")."</th>
			</tr>\n\t</thead>\n\t<tbody>\n";

		// suppressing errors for when there is a fake data set in place
		foreach($result as $row){
			$body.="\t\t<tr>
			\t<td>{$row["Label"]}</td>
			\t<td>{$row["DC"]}</td>
			\t<td>{$row["Rack"]}</td>
			\t<td>{$row["Owner"]}</td>
			\t<td>{$row["Tag"]}</td>
			\t<td>".strip_tags($row["Notes"])."</td>
			\t\t</tr>\n";
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
			$('#export').dataTable({
				dom: 'B<"clear">lfrtip',
				buttons:{
					buttons: [
						'copy', 'excel', 'pdf', 'csv', 'colvis', 'print'
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
		$('#tagid').change(function(){
			$.post('', {tagid: $(this).val(), ajax: ''}, function(data){
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
			<label for="tagid">',__("Tag:"),'</label>
			<select name="tagid" id="tagid">
				<option value="">',__("Select Tag"),'</option>
				<option value="0">',__("All Tags"),'</option>';
foreach($tagsList as $tagid => $tagname){print "\t\t\t\t<option value=\"$tagid\">$tagname</option>\n";} ?>
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
