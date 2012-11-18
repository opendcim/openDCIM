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
	$resultcount=0;

	if($searchKey=='serial'){
		$dev->SerialNo=$searchTerm;
		$devList=$dev->SearchDevicebySerialNo($facDB);
		$resultcount=count($devList);
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
		$resultcount=count($devList)+count($cabList)+count($pduList)+count($vmList);
	}elseif($searchKey=='asset'){
		$dev->AssetTag=$searchTerm;
		$devList=$dev->SearchDevicebyAssetTag($facDB);
		$resultcount=count($devList);
	} else {
		$devList='';
	}

	$x=0;
	$temp=array(); // Store all devices for display
	$cabtemp=array(); // List of all cabinet ids for outerloop
	$childList=array(); // List of all blade devices
	while(list($devID,$device)=each($devList)){
		$temp[$x]['devid']=$devID;
		$temp[$x]['label']=$device->Label;
		$temp[$x]['type']='srv'; // empty chassis devices need no special treatment leave them as a server
		$temp[$x]['cabinet']=$device->Cabinet;
		$temp[$x]['parent']=$device->ParentDevice;
		$cabtemp[$device->Cabinet]="";
		++$x;
		if($device->ParentDevice!=0){
			$childList[$device->ParentDevice]=""; // Create a list of chassis devices based on children present
		}
	}
	
	if(isset($vmList)){
		foreach($vmList as $vmRow){
			$dev->DeviceID=$vmRow->DeviceID;
			$dev->GetDevice($facDB);
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
				$temp[$x]['parent']=$dev->ParentDevice;
				$cabtemp[$dev->Cabinet]="";
				++$x;
				if($dev->ParentDevice!=0){
					$childList[$dev->ParentDevice]=""; // Create a list of chassis devices based on children present
				}
			}
		}
	}

	// Anything in the childList is assumed to be chassis device with children present.
	// Change type to chassis
	if(isset($childList)){
		foreach($childList as $key => $blank){
			$a=ArraySearchRecursive($key,$temp,'devid');
			if(is_array($a)){
				$temp[$a[0]]['type']='chassis'; // Device already in the list so set it to chassis
			}else{
				// Device doesn't exist so we need to add it to the list for display purposes
				$dev->DeviceID=$key;
				$dev->GetDevice($facDB);

				$temp[$x]['devid']=$dev->DeviceID;
				$temp[$x]['label']=$dev->Label;
				$temp[$x]['type']='chassis';
				$temp[$x]['cabinet']=$dev->Cabinet;
				$temp[$x]['parent']=$dev->ParentDevice;
				$cabtemp[$dev->Cabinet]="";
				++$x;
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

	// Since children have empty cabinet identifiers we'll have an empty row get rid of it
	if(isset($cabtemp[0])){unset($cabtemp[0]);}

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

	if(!empty($devList)){
		$searchresults="Search complete. ($resultcount) results.";
	}else{
		$searchresults="No matching devices found.";
	}

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Search Results</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
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
					print "\t\t\t\t<li class=\"pdu\"><a href=\"power_pdu.php?pduid=$row->PDUID\">$row->Label</a>\n";
				}
			}
		}
		if(!empty($devList)){
			foreach($devList as $key => $row){
				if($cabID==$row['cabinet']){
					//In case of VMHost missing from inventory, this shouldn't ever happen
					if($row['label']=='' || is_null($row['label'])){$row['label']='VM Host Missing From Inventory';}
					print "\t\t\t\t<li><a href=\"devices.php?deviceid={$row['devid']}\">{$row['label']}</a>\n";
					// Created a nested list showing all blades residing in this chassis
					if($row['type']=='chassis'){
						print "\t\t\t\t\t<ul>\n";
						foreach($devList as $chKey => $chRow){
							if($chRow['parent']==$row['devid']){
								//In case of VMHost missing from inventory, this shouldn't ever happen
								if($chRow['label']=='' || is_null($chRow['label'])){$chRow['label']='VM Host Missing From Inventory';}
								print "\t\t\t\t\t\t<li><div><img src=\"images/blade.png\" alt=\"blade icon\"></div><a href=\"devices.php?deviceid={$chRow['devid']}\">{$chRow['label']}</a>\n";
								// Create a nested list showing all VMs residing on this host.
								if($chRow['type']=='vm'){
									print "\t\t\t\t\t\t\t<ul>\n";
									foreach($vmList as $usedkey => $vm){
										if($vm->DeviceID==$chRow['devid']){
											print "\t\t\t\t\t\t\t\t<li><div><img src=\"images/vmcube.png\" alt=\"vm icon\"></div>$vm->vmName</li>\n";
											// Remove VMs that have already been processed
											unset($vmList[$usedkey]);
										}
									}
									print "\t\t\t\t\t\t\t</ul>\n";
								}
								// Remove devices that we have already processed.
								unset($devList[$chKey]);
								print "\t\t\t\t\t\t</li>\n"; // Close out current list item
							}
						}
						print "\t\t\t\t\t</ul>\n";
					}
					// Create a nested list showing all VMs residing on this host.
					if($row['type']=='vm'){
						echo "\t\t\t\t\t<ul>\n";
						foreach($vmList as $usedkey => $vm){
							if($vm->DeviceID==$row['devid']){
								echo "\t\t\t\t\t\t<li><div><img src=\"images/vmcube.png\" alt=\"vm icon\"></div>$vm->vmName</li>\n";
								// Remove VMs that have already been processed
								unset($vmList[$usedkey]);
							}
						}
						echo "\t\t\t\t\t</ul>\n";
					}
					echo "\t\t\t\t</li>\n";
				} 
			}
		}
		print "\t\t\t</ol>\n\t\t</li>\n";
	}

?>
	</ol>

<p><?php print $searchresults; ?></p>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
