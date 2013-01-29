<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );
	
	$datacenter = new DataCenter();
	$dcList = $datacenter->GetDCList( $facDB );
	
	
	
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <link rel="stylesheet" href="css/jHtmlArea.css" type="text/css">
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
  <script type="text/javascript" src="scripts/mdetect.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript" src="scripts/jHtmlArea-0.7.5.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.textext.js"></script>
  <script type="text/javascript" src="scripts/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="scripts/ColVis.min.js"></script>
  <script type="text/javascript" src="scripts/TableTools.min.js"></script>
  
</head>
<div id="header"></div>
<div>
<div class="main">
<h2>openDCIM</h2>
<h3>Data Center View/Export</h3>
<?php
if (!isset($_REQUEST['datacenterid'])) {
	printf( "<form action=\"%s\" method=\"post\">", $_SERVER['PHP_SELF'] );
	printf( "<table align=\"center\" border=0>" );

	if ( @$_REQUEST['datacenterid'] == 0 ) {
		printf( "<tr><td>Data Center:</td><td>\n" );
		printf( "<select name=\"datacenterid\" onChange=\"form.submit()\">\n" );
		printf( "<option value=\"\">Select data center</option>\n" );
		printf( "<option value=\"0\">All Data Centers</option>\n" );
		
		foreach ( $dcList as $dc )
			printf( "<option value=\"%d\">%s</option>\n", $dc->DataCenterID, $dc->Name );
		
		printf( "</td></tr>" );
		
		printf( "</table>\n</form>\n" );
	}
} else {
		$dc = $_REQUEST['datacenterid'];

		if ( intval( $dc ) == 0 )
			$sql = "select a.Name as DataCenter, e.Location, b.Position, b.Height, b.Label, b.DeviceType, c.Model, d.Name as Department, b.InstallDate from fac_DataCenter a, fac_Device b, fac_DeviceTemplate c, fac_Department d, fac_Cabinet e where b.Cabinet=e.CabinetID and e.DataCenterID=a.DataCenterID and b.TemplateID=c.TemplateID and b.Owner=d.DeptID order by DataCenter ASC, Location ASC, Position ASC";
		else 
			$sql = sprintf( "select a.Name as DataCenter, e.Location, b.Position, b.Height, b.Label, b.DeviceType, c.Model, d.Name as Department, b.InstallDate from fac_DataCenter a, fac_Device b, fac_DeviceTemplate c, fac_Department d, fac_Cabinet e where b.Cabinet=e.CabinetID and e.DataCenterID=a.DataCenterID and b.TemplateID=c.TemplateID and b.Owner=d.DeptID and e.DataCenterID=%d order by Location ASC, Position ASC", $dc );

		$result = mysql_query( $sql, $facDB );
?>
<table id="export" class="display">
	<thead>
		<tr>
			<th>Data Center</th>
			<th>Location</th>
			<th>Position</th>
			<th>Height</th>
			<th>Name</th>
			<th>Device Type</th>
			<th>Template</th>
			<th>Owner</th>
			<th>Installation Date</th>
		</tr>
	</thead>
	<tbody>
<?php
	while ( $row = mysql_fetch_array( $result ) ) {
		printf( "\t<tr>\n" );
		printf( "\t\t<td>%s</td>\n", $row["DataCenter"] );
		printf( "\t\t<td>%s</td>\n", $row["Location"] );
		printf( "\t\t<td>%s</td>\n", $row["Position"] );
		printf( "\t\t<td>%s</td>\n", $row["Height"] );
		printf( "\t\t<td>%s</td>\n", $row["Label"] );
		printf( "\t\t<td>%s</td>\n", $row["DeviceType"] );
		printf( "\t\t<td>%s</td>\n", $row["Model"] );
		printf( "\t\t<td>%s</td>\n", $row["Department"] );
		printf( "\t\t<td>%s</td>\n", date( "d M Y", strtotime( $row["InstallDate"] ) ) );
		printf( "\t</tr>\n" );
	}
?>
	</tbody>
</table>

<script type="text/javascript">
$(document).ready( function() {
	$( '#export' ).dataTable( {
		"iDisplayLength" : 25,
		"sDom" : 'CT<"clear">lfrtip',
		"oTableTools": {
			"sSwfPath": "scripts/copy_csv_xls.swf",
			"aButtons": [ "copy", "csv", "xls", "print" ]
		}
	} );
} );
</script>
<?php
}
?>
</div><!-- END div.page -->
</body>
</html>
