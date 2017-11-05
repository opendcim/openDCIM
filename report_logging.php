<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

if(!$person->SiteAdmin){
    // No soup for you.
    header('Location: '.redirect());
    exit;
}


if(isset($_POST['refresh'])){
	$log=new LogActions();
	foreach($log as $prop => $val){
		if(isset($_POST[$prop])){
			$log->$prop=$_POST[$prop];
		}
	}

	$data_array=array();
	if(isset($_POST['ListUnique'])){
		$data_array=$log->ListUnique($_POST['ListUnique']);
	}

	if(isset($_POST['BuildTable'])){
		echo BuildDataTable($log);
		exit;
	}

	header('Content-Type: application/json');
	echo json_encode($data_array);
	exit;
}


	$subversion=__("Logging View/Export");

	$datacenter=new DataCenter();
	$dcList=$datacenter->GetDCList();
	
	$templ=new DeviceTemplate();
	$dept=new Department();
	$dev=new Device();
	$log=new LogActions();
	
	function BuildDataTable($log_object){
		$limit=(isset($_REQUEST['Limit']))?$_REQUEST['Limit']:1000;
		$result=$log_object->Search($limit);

		// Left these expanded in case we need to add or remove columns.  Otherwise I would have just collapsed entirely.
		$body="<table id=\"export\" class=\"display\">\n\t<thead>\n\t\t<tr>\n
			\t<th>".__("Time")."</th>
			\t<th>".__("UserID")."</th>
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
			switch($logitem->Action){
				case 1:
					$logitem->Action=__("Create");
					break;
				case 2:
					$logitem->Action=__("Delete");
					break;
				case 3:
					$logitem->Action=__("Update");
					break;
				default:
			}
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

		return $body;
	}

	$body=BuildDataTable($log);

	function buildselect($prop){
		$return_html="<select name=\"$prop\"></select>";

		return $return_html;
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
		// Start DataTables functions
		dt();
		
		$('.table :input').change(function(){
			GetTableData();
			$('.table :input').each(function(){
				refreshValues(this);
			});
		}).each(function(){
			refreshValues(this);
		});
	});

	function GetTableData(){
		// Get current data table instance
		var tab=$('#export').DataTable();
		// Get the search options
		var formdata=$('.table :input').serializeArray();
		formdata.push({name:'refresh',value:''});
		formdata.push({name:'BuildTable',value:''});
		// Post and build the new table
		$.post('',formdata).done(function(data){
			var num_rows=$('select[name=export_length]').val();
			// Supposedly the get table was successful so kill the previous instance
			tab.destroy(true);
			// Insert the new table
			$('#tablecontainer').html(data);
			// Init the new table
			dt(num_rows);
		});
	}

	function dt(rows){
		var num_rows=(typeof rows=="undefined")?25:rows;
		$('#export').dataTable({
			"iDisplayLength": num_rows,
			"sDom": 'CT<"clear">lfrtip',
			"order": [[ 0, 'desc' ]],
			"columnDefs": [{"width": "115px", "targets": 0}],
			"oTableTools": {
				"sSwfPath": "scripts/copy_csv_xls.swf",
				"aButtons": ["copy","csv","xls","print"]
			}
		});
		resize();
	}

	function refreshValues(inputobject){
		var ov=inputobject.value;
		// Get the search options
		var formdata=$('.table :input').serializeArray();
		formdata.push({name:'refresh',value:''});
		formdata.push({name:'ListUnique',value:inputobject.name});
		if(inputobject.name!="OldVal" && inputobject.name!="NewVal" || ($('select[name=Class]').val() && $('select[name=UserID]').val())){
			$.post('',formdata).done(function(data){
				if(data){
					$(inputobject).html('').append($("<option>"));
					for(var i in data){
						var label=data[i];
						if(inputobject.name=="Action"){
							labels=['','Create','Delete','Update'];
							if(typeof labels[label]!='undefined'){
								label=labels[label];
							}
						}
						$(inputobject).append($("<option>").val(data[i]).html(label));
					}
					inputobject.value=ov;
				}
			});
		}
	}
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
	<div class="page">
<?php
	include('sidebar.inc.php');
echo '		<div class="main">
			<div class="table">
				<div>
					<div>UserID</div>
					<div>Class</div>
					<div>ObjectID</div>
					<div>ChildID</div>
					<div>Action</div>
					<div>Property</div>
					<div>OldVal</div>
					<div>NewVal</div>
					<div>Limit</div>
				</div>
				<div>
					<div>',buildselect("UserID"),'</div>
					<div>',buildselect("Class"),'</div>
					<div>',buildselect("ObjectID"),'</div>
					<div>',buildselect("ChildID"),'</div>
					<div>',buildselect("Action"),'</div>
					<div>',buildselect("Property"),'</div>
					<div>',buildselect("OldVal"),'</div>
					<div>',buildselect("NewVal"),'</div>
					<div><select name="Limit"><option value=0>No Limit</option><option value=100>100</option><option value=1000 selected>1000</option><option value=5000>5000</option><option value=10000>10000</option></select></div>
				</div>
			</div>
			<br><br>
			<div class="center">
				<div id="tablecontainer">',$body,'
				</div>
			</div>
		</div><!-- END div.main -->
	</div><!-- END div.page -->
</body>
</html>';
