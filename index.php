<?php
/*	if ( ! $_SERVER["HTTPS"] ) {
		printf( "<meta http-equiv='refresh' content='0; url='https://%s'>", $_SERVER["SERVER_NAME"] );
		exit();
	} */

	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user = new User();
	$user->UserID = $_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );
	
	// ITSD Statistics
	$sql = 'select count(*) as Devices from fac_Device where DeviceType!=\'Server\'';
	$res = mysql_query( $sql, $facDB );
	$row = mysql_fetch_array( $res );
	$ITSdevices = $row['Devices'];
  
	$sql = 'select count(*) as Servers from fac_Device where DeviceType=\'Server\'';
	$res = mysql_query( $sql, $facDB );
	$row = mysql_fetch_array( $res );  
	$ITSservers = $row['Servers'];
  
	$sql = 'select sum(Height) as Size from fac_Device';
	$res = mysql_query( $sql, $facDB );
	$row = mysql_fetch_array( $res );
	$ITSsize = $row['Size'];

	$sql = 'select count(*) as VMcount from fac_VMInventory';
	$res = mysql_query( $sql, $facDB );
	$row = mysql_fetch_array( $res );
	$ITSVM = $row['VMcount'];
  
	$sql = 'select sum(NominalWatts) as Power from fac_Device';
	$res = mysql_query( $sql, $facDB );
	$row = mysql_fetch_array( $res );
	$ITSpower = $row['Power'];
	$ITSheat = $ITSpower * 3.412 / 12000;
  
	$dc = new DataCenter();
	$dcList = $dc->GetDCList( $facDB );

	// Build table to display pending rack requests for inclusion later
	$rackrequest='';
	if($user->RackAdmin){
		$rackrequest="<h3>"._('Pending Rack Requests')."</h3>\n<div class=\"table whiteborder rackrequest\">\n<div>\n  <div>"._('Submit Time')."</div>\n  <div>"._('Requestor')."</div>\n  <div>"._('System Name')."</div>\n  <div>"._('Department')."</div>\n  <div>"._('Due By')."</div>\n</div>\n";

		$rack=new RackRequest();
		$tmpContact=new Contact();
		$dept=new Department();
  
		$rackList=$rack->GetOpenRequests($facDB);
  
		foreach($rackList as $request){
			$tmpContact->ContactID=$request->RequestorID;
			$tmpContact->GetContactByID($facDB);
    
			$dept->DeptID=$request->Owner;
			$dept->GetDeptByID($facDB);
    
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );
echo '
<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h2>',_("Data Center Operations Metrics"),'</h2>
<div class="center"><div>
',$rackrequest,'
<h3>',_("Data Center Inventory"),' <a href="realtimemetrics.php">(',_("View as Excel"),')</a></h3>
<div class="table border centermargin">
<div class="title">
',_("Hosted Systems"),'
</div>
<div>
  <div>',_("Server Count"),'</div>
  <div>',$ITSservers,'</div>
</div>
<div>
  <div>',_("Other Device Count"),'</div>
  <div>',$ITSdevices,'</div>
</div>
<div>
  <div>',_("Space"),' (1U=1.75")</div>
  <div>',$ITSsize,' U</div>
</div>
<div>
  <div>',_("Power Consumption"),'</div>
  <div>',printf("%.2f kiloWatts/hour",$ITSpower/1000),'</div>
</div>
<div>
  <div>',_("Heat Produced"),'</div>
  <div>',printf("%.2f Tons",$ITSheat),'</div>
</div>
<div>
  <div>',_("Virtual Machines"),'</div>
  <div>',$ITSVM,'</div>
</div>
</div> <!-- END div.table -->
</div>
</div>
</div>
</body>
</html>';
?>
