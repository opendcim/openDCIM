<?php
	# catch for assholes that don't read the install instructions
	if(!file_exists("db.inc.php")){
		require_once( "preflight.inc.php" );
		exit;
	}
/*	if ( ! $_SERVER["HTTPS"] ) {
		printf( "<meta http-equiv='refresh' content='0; url='https://%s'>", $_SERVER["SERVER_NAME"] );
		exit();
	} */

	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );
	
	$subheader=__("Data Center Operations Metrics");

	$sql = 'select count(*) as DCs from fac_DataCenter';
	$row=$dbh->query($sql)->fetch();
	$DCs = $row['DCs'];

	// Overall Statistics
	$sql='SELECT SUM(NominalWatts) AS Power,
		(SELECT COUNT(*) FROM fac_Device WHERE DeviceType!="Server" LIMIT 1) AS Devices, 
		(SELECT COUNT(*) FROM fac_Device WHERE DeviceType="Server" LIMIT 1) AS Servers,
		(SELECT SUM(Height) FROM fac_Device LIMIT 1) AS Size,
		(SELECT COUNT(*) FROM fac_VMInventory LIMIT 1) AS VMcount,
		(select count(*) from fac_Device where Hypervisor!="None") as VMhosts,
		(select count(*) from fac_Cabinet) as CabinetCount
		FROM fac_Device LIMIT 1;';

	$row=$dbh->query($sql)->fetch();

	$StatsDevices=$row['Devices'];
	$StatsServers=$row['Servers'];
	$StatsSize=$row['Size'];
	$StatsVM=$row['VMcount'];
	$StatsHost=$row["VMhosts"];
	$StatsCabinet=$row["CabinetCount"];
	$StatsPower=$row['Power'];
	$StatsHeat=$StatsPower * 3.412 / 12000;
  
	$dc = new DataCenter();
	$dcList = $dc->GetDCList();

	// Build table to display pending rack requests for inclusion later
	$rackrequest='';
	if($config->ParameterArray["RackRequests"]=="enabled" && $person->RackAdmin){
		$rackrequest="<h3>".__("Pending Rack Requests")."</h3>\n<div class=\"table whiteborder rackrequest\">\n<div>\n  <div>".__("Submit Time")."</div>\n  <div>".__("Requestor")."</div>\n  <div>".__("System Name")."</div>\n  <div>".__("Department")."</div>\n  <div>".__("Due By")."</div>\n</div>\n";

		$rack=new RackRequest();
		$tmpContact=new People();
		$dept=new Department();
  
		$rackList=$rack->GetOpenRequests();
  
		foreach($rackList as $request){
			$tmpContact->PersonID=$request->RequestorID;
			$tmpContact->GetPerson();
    
			$dept->DeptID=$request->Owner;
			$dept->GetDeptByID();
    
			$reqDate=getdate(strtotime($request->RequestTime));
			$dueDate=date('M j Y H:i:s',mktime($reqDate['hours'],$reqDate['minutes'],$reqDate['seconds'],$reqDate['mon'],$reqDate['mday']+1,$reqDate['year']));
    
			if((strtotime($dueDate) - strtotime('now'))< intval( $config->ParameterArray['RackOverdueHours'] * 3600 ) ) {
				$colorCode='overdue';
			}elseif((strtotime($dueDate) - strtotime('now'))< intval( $config->ParameterArray['RackWarningHours'] * 3600 ) ) {
				$colorCode='soon';
			}else{
				$colorCode='clear';
			}
			$rackrequest.="<div class=\"$colorCode\"><div>".date("M j Y H:i:s",strtotime($request->RequestTime))."</div><div>$tmpContact->FirstName $tmpContact->LastName</div><div><a href=\"rackrequest.php?requestid=$request->RequestID\">$request->Label</a></div><div>$dept->Name</div><div>$dueDate</div></div>\n";
		}
		$rackrequest.='</div><!-- END div.table -->';
	}
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );
echo '
<div class="main">
<div class="center"><div>
',$rackrequest,'
<h3>',__("Data Center Inventory"),' <a href="search_export.php">(',__("Export Inventory"),')</a></h3>
<div class="table border centermargin">
<div class="title">
',__("Hosted Systems"),'
</div>';
echo '
<div>
  <div>',__("DC Count"),'</div>
  <div>',$DCs,'</div>
</div>';
echo '
<div>
  <div>',__("Physical Server Count"),'</div>
  <div>',$StatsServers,'</div>
</div>
<div>
  <div>',__("Other Device Count"),'</div>
  <div>',$StatsDevices,'</div>
</div>
<div>
  <div>',__("Space"),' (1U=1.75")</div>
  <div>',$StatsSize,' U</div>
</div>
<div>
  <div>',__("Power Consumption"),'</div>
  <div>',sprintf("%.2f kW",$StatsPower/1000),'</div>
</div>
<div>
  <div>',__("Heat Produced"),'</div>
  <div>',sprintf("%.2f Tons",$StatsHeat),'</div>
</div>
<div>
  <div>',__("Virtual Machines"),'</div>
  <div>',$StatsVM,'</div>
</div>
<div>
	<div>',__("VM Hosts"),'</div>
	<div>',$StatsHost,'</div>
</div>
<div>
	<div>',__("Virtualization Ratio"),'</div>
	<div>',intval($StatsVM/(($StatsHost==0)?1:$StatsHost)),':1</div>
</div>
<div>
	<div>',__("Total Cabinets"),'</div>
	<div>',$StatsCabinet,'</div>
</div>
</div> <!-- END div.table -->
	<div>';

if( file_exists("sitecontact.html") ) {
	include( "sitecontact.html" );
}
echo '	</div>
</div>
</div>
</div>
</body>
</html>';
?>
