<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$searchKey=$_REQUEST['key'];
	//Remove control characters tab, enter, etc
	$searchTerm=preg_replace("/[[:cntrl:]]/","",$_REQUEST['search']);
	//Remove any extra quotes that could get passed in from some funky js or something
	$searchTerm=str_replace(array("'",'"'),"",$searchTerm);

	$dev=new Device();
	$parDev = new Device();
	$esx=new ESX();
	$cab=new Cabinet();
	$pdu=new PowerDistribution();

	if($searchKey=='serial'){
		$dev->SerialNo=$searchTerm;
		$devList=$dev->SearchDevicebySerialNo($facDB);
	}elseif($searchKey=='label'){
		$dev->Label=$searchTerm;
		$devList=$dev->SearchDevicebyLabel($facDB);
		//Virtual machines will never be search via asset tags or serial numbers
		$esx->vmName=$dev->Label;
		$vmList=$esx->SearchByVMName($facDB);
		$cab->Location=$searchTerm;
		$cabList=$cab->SearchByCabinetName($facDB);
		$pdu->Label=$searchTerm;
		$pduList=$pdu->SearchByPDUName($facDB);
	}elseif($searchKey=='asset'){
		$dev->AssetTag=$searchTerm;
		$devList=$dev->SearchDevicebyAssetTag($facDB);
	} else {
		$devList='';
	}

	$x=0;
	$temp=array(); // Store all devices for display
	$cabtemp=array(); // List of all cabinet ids for outerloop
	$childList = array();
	while(list($devID,$device)=each($devList)){
		// Child devices don't have cabinet assignments
		if ( $device->ParentDevice == 0 ) {
			$temp[$x]['devid']=$devID;
			$temp[$x]['label']=$device->Label;
			if ( $device->DeviceType == "Chassis" )
				$temp[$x]['type'] = 'chassis';
			else
				$temp[$x]['type']='srv';
				
			$temp[$x]['cabinet']=$device->Cabinet;
			$cabtemp[$device->Cabinet]="";
			++$x;
		// But the parents of child devices do
		} else {
			$parDev->DeviceID = $device->ParentDevice;
			$parDev->GetDevice( $facDB );
			
			// See if the parent device is already in the array
			$a = ArraySearchRecursive( $device->ParentDevice, $temp, 'devid' );
			if ( ! is_array($a)) {
				$temp[$x]['devid'] = $parDev->DeviceID;
				$temp[$x]['label'] = $parDev->Label;
				$temp[$x]['type'] = 'chassis';
				$temp[$x]['cabinet'] = $parDev->Cabinet;
				$cabtemp[$device->Cabinet]="";
				$x++;
			}	

			$childNum = sizeof( $childList );
			$childList[$childNum] = new Device();
			$childList[$childNum]->DeviceID = $device->DeviceID;
			$childList[$childNum]->GetDevice( $facDB );
		}
		
	}
	
	if(isset($vmList)){
		foreach($vmList as $vmRow){
			$dev->DeviceID=$vmRow->DeviceID;
			$dev->GetDevice($facDB);
			// $dev is an object for the ESX host, but that could be a blade on a chassis, so we need to check that, too
			if ( $dev->ParentDevice > 0 ) {
				// We have a ParentDevice, so we must be a child
				$parDev->DeviceID = $dev->ParentDevice;
				$parDev->GetDevice( $facDB );
				
				$a = ArraySearchRecursive( $parDev->DeviceID, $temp, 'devid' );
				if ( ! is_array( $a ) ) {
					// We need to add the parent device to the list
					$temp[$x]['devid'] = $parDev->DeviceID;
					$temp[$x]['label'] = $parDev->Label;
					$temp[$x]['type'] = 'chassis';
					$temp[$x]['cabinet'] = $parDev->Cabinet;
					
					$cabtemp[$parDev->Cabinet]="";
					$x++;
				}
				
				$childNum = sizeof( $childList );
				$childList[$childNum] = new Device();
				$childList[$childNum]->DeviceID = $vmRow->DeviceID;
				$childList[$childNum]->GetDevice( $facDB );				
			} else {
				$a=ArraySearchRecursive($vmRow->DeviceID,$temp,'devid');
				// if we find a matching server in the existing list set it to type vm so it will nest in the results
				if(is_array($a)){
					$temp[$a[0]]['label']=$dev->Label;
					$temp[$a[0]]['type']='vm';
				}else{
					// We didn't find the host server of this vm so we're gonna add it to the list
					$temp[$x]['devid']=$dev->DeviceID;
					$temp[$x]['label']=$dev->Label;
					$temp[$x]['type']='vm';
					
					$temp[$x]['cabinet']=$dev->Cabinet;
					$cabtemp[$dev->Cabinet]="";
					++$x;
				}
			}
		}
	}

	// Add racks that matched the search term to the rack list
	if(isset($cabList)&&is_array($cabList)){
		foreach($cabList as $CabinetID => $row){
			$cabtemp[$CabinetID]=$row->Location;
		}
	}

	// Add racks that are parents of the PDU devices to the rack list
	if(isset($pduList)&&is_array($pduList)){
		foreach($pduList as $key => $row){
			if(!isset($cabtemp[$row->CabinetID])){
				$cabtemp[$row->CabinetID]="";
			}
		}
	}

	// Add Rack Names To Temp Cabinet Array
	foreach($cabtemp as $key => $row){
		if($key!=-1){
			$cab->CabinetID=$key;
			$cab->GetCabinet($facDB);
			$cabtemp[$key]=$cab->Location;
		}else{
			$cabtemp[$key]="Storage Room";
		}
	}

	// Sort array based on device label
	if(!empty($temp)){
		$devList=sort2d($temp,'label');
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Search Results</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page search">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Search Results</h3>
<div class="center"><div>
	<ol>
<?php
	foreach ($cabtemp as $cabID => $cabLocation){
		print "		<li class=\"cabinet\"><div><img src=\"images/serverrack.png\" alt=\"rack icon\"></div><a href=\"cabnavigator.php?cabinetid=$cabID\">$cabLocation</a>\n			<ol>\n";
		//Always list PDUs directly after the cabinet device IF they exist
		if(isset($pduList)&&is_array($pduList)){
			// In theory this should be a short list so just parse the entire thing each time we read a cabinet.
			// if this ends up being a huge time sink, optimize this above then fix logic
			foreach($pduList as $key => $row){
				if($cabID == $row->CabinetID){
					print "					<li class=\"pdu\"><a href=\"pduinfo.php?pduid=$row->PDUID\">$row->Label</a>\n";
				}
			}
		}
		if(!empty($devList)){
			foreach ($devList as $key => $row){
				if($cabID == $row['cabinet']){
					//In case of VMHost missing from inventory, this shouldn't ever happen
					if($row['label']=='' || is_null($row['label'])){$row['label']='VM Host Missing From Inventory';}
					echo "				<li><a href=\"devices.php?deviceid={$row['devid']}\">{$row['label']}</a>\n";
					// Created a nested list showing all blades residing in this chassis
					if ( $row['type'] == 'chassis' ) {
						printf( "\t\t\t<ul>\n" );
						foreach ( $childList as $chDev ) {
							if ( $chDev->ParentDevice == $row['devid'] ) {
								printf( "\t\t\t\t<li><div><img src=\"images/blade.png\" alt=\"blade icon\"></div><a href=\"devices.php?deviceid=%d\">%s</a></li>\n", $chDev->DeviceID, $chDev->Label );
								
								// A blade can easily be an ESX server, too, so display any matching VMs
								if ( $chDev->ESX == true ) {
									printf( "\t\t\t\t\t<ul>\n" );
									foreach ( $vmList as $vm ) {
										if ( $vm->DeviceID == $chDev->DeviceID ) {
											printf( "\t\t\t\t\t<li><div><img src=\"images/vmcube.png\" alt=\"vm icon\"></div>$vm->vmName</li>\n" );
										}
									}
									printf( "\t\t\t\t\t</ul>\n" );
								}
								printf( "\t\t\t\t</li>\n" );
							}
						}
						printf( "\t\t\t</ul>\n" );
					}
					
					// Create a nested list showing all VMs residing on this host.
					if($row['type']=='vm'){
						echo "					<ul>\n";
						foreach($vmList as $vm){
							if($vm->DeviceID==$row['devid']){
								echo "						<li><div><img src=\"images/vmcube.png\" alt=\"vm icon\"></div>$vm->vmName</li>";
							}
						}
						echo "					</ul>\n";
					}
					echo "				</li>\n";
				} 
			}
		}
		print "			</ol>\n		</li>\n";
	}

?>
	</ol>

<p>Search complete.</p>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
