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
	$esx=new ESX();
	$cab=new Cabinet();

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
	}elseif($searchKey=='asset'){
		$dev->AssetTag=$searchTerm;
		$devList=$dev->SearchDevicebyAssetTag($facDB);
	} else {
		$devList='';
	}

	$x=0;
	$temp=array(); // Store all devices for display
	$cabtemp=array(); // List of all cabinet ids for outerloop
	while(list($devID,$device)=each($devList)){
		$temp[$x]['devid']=$devID;
		$temp[$x]['label']=$device->Label;
		$temp[$x]['type']='srv';
		$temp[$x]['cabinet']=$device->Cabinet;
		$cabtemp[$device->Cabinet]="";
		++$x;
	}
	if(isset($vmList)){
		foreach($vmList as $vmRow){
			$dev->DeviceID=$vmRow->DeviceID;
			$dev->GetDevice($facDB);
			$a=ArraySearchRecursive($vmRow->DeviceID,$temp,'devid');
			// if we find a matching server in the exisiting list set it to type vm so it will nest in the results
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

	// Add racks that matched the search term to the rack list
	if(isset($cabList)&&is_array($cabList)){
		foreach($cabList as $CabinetID => $row){
			$cabtemp[$CabinetID]=$row->Location;
		}
	}

	// Add Rack Names To Temp Cabinet Array
	foreach($cabtemp as $key => $row){
		$cab->CabinetID=$key;
		$cab->GetCabinet($facDB);
		$cabtemp[$key]=$cab->Location;
	}

	// Sort array based on device label
	$devList=sort2d($temp,'label');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Search Results</title>
  <link rel="stylesheet" href="css/inventory.css" type="text/css">
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
//print_r($devList);
//print_r($vmList);

	foreach ($cabtemp as $cabID => $cabLocation){
		print "		<li class=\"cabinet\"><div><img src=\"images/serverrack.png\" alt=\"rack icon\"></div><a href=\"cabnavigator.php?cabinetid=$cabID\">$cabLocation</a>\n			<ol>\n";
		if(count($devList > 0)){
			foreach ($devList as $key => $row){
				if($cabID == $row['cabinet']){
					//In case of VMHost missing from inventory, this shouldn't ever happen
					if($row['label']=='' || is_null($row['label'])){$row['label']='VM Host Missing From Inventory';}
					echo "				<li><a href=\"devices.php?deviceid={$row['devid']}\">{$row['label']}</a>\n";
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
