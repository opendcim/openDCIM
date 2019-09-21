<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$searchKey=$_REQUEST['key'];
	//Remove control characters tab, enter, etc
	$searchTerm=preg_replace("/[[:cntrl:]]/","",$_REQUEST['search']);
	//Remove any extra quotes that could get passed in from some funky js or something
	$searchTerm=str_replace(array("'",'"'),"",$searchTerm);
	# prevent XSS script injection reported by mullaneywt
	$searchKey=sanitize($searchKey);
	# prevent script injection where we display the searchTerm in the title
	# reported by Jacob Senn, Capital One
	$searchTerm=sanitize($searchTerm);

	$dc=new DataCenter();
	$dcList=$dc->GetDCList();
	
	$dev=new Device();
	$vm=new VM();
	$cab=new Cabinet();
	$pdu=new PowerDistribution();
	$dept=new Department();
	$proj=new Projects();
	$tmpl=new DeviceTemplate();
	$resultcount=0;
	$title=__("Search Results");

	if($searchKey=='serial'){
		$dev->SerialNo=$searchTerm;
		$devList=$dev->SearchDevicebySerialNo();
		$resultcount=count($devList);
		$title=__("Serial number search results for")." &quot;$searchTerm&quot;";
	}elseif($searchKey=='ip'){
		$dev->PrimaryIP=$searchTerm;
		$devList=$dev->SearchDevicebyIP();
		$resultcount=count($devList);
		$title=__("PrimaryIP search results for")." &quot;$searchTerm&quot;";
	}elseif($searchKey=='label'){
		$dev->Label=$searchTerm;
		$devList=$dev->SearchDevicebyLabel();
		//Virtual machines will never be search via asset tags or serial numbers
		$vm->vmName=$dev->Label;
		$vmList=$vm->SearchByVMName();
		$cab->Location=$searchTerm;
		$cabList=$cab->LooseSearch(true);
		$resultcount=count($devList)+count($cabList)+count($vmList);
		$title=__("Name search results for")." &quot;$searchTerm&quot;";
	}elseif($searchKey=='owner'){
		$dept->Name=$searchTerm;
		if(isset($_REQUEST['deptid'])){
			$dept->DeptID=$_REQUEST['deptid'];
			$dept->GetDeptByID();
		}else{
			$dept->GetDeptByName();
		}
		$dev->Owner=$dept->DeptID;
		$devList=$dev->GetDevicesbyOwner();
		$vm->Owner=$dept->DeptID;
		$vmList=$vm->GetVMListbyOwner();
		$cab->AssignedTo=$dept->DeptID;
		$cabList=$cab->Search(true);
		//PDUs have no ownership information so don't search them
		$resultcount=count($devList)+count($cabList)+count($vmList);
		$title=__("Owner search results for")." &quot;$searchTerm&quot;";
	}elseif($searchKey=='asset'){
		$dev->AssetTag=$searchTerm;
		$devList=$dev->SearchDevicebyAssetTag();
		$resultcount=count($devList);
		$title=__("Asset tag search results for")." &quot;$searchTerm&quot;";
	}elseif($searchKey=="ctag"){
		// TODO: this could be enhanced to allow searching for a specific custom attribute
		$devList=$dev->SearchByCustomTag($searchTerm);
		$cabList=$cab->SearchByCustomTag($searchTerm);
		$resultcount=count($devList)+count($cabList);
		$title=__("Custom tag search results for")." &quot;$searchTerm&quot;";
	}elseif($searchKey=="project"){
		$proj->ProjectName=$searchTerm;
		$projList = $proj->Search();
		$devList = array();
		foreach( $projList as $p ) {
			$tmpList = ProjectMembership::getProjectMembership( $p->ProjectID, true );
			$devList = $devList + $tmpList;
		}
		$resultcount=count($devList);
		$title=__("Project Catalog search results for")." &quot;$searchTerm&quot;";
	}elseif($searchKey=="model"){
		$tmpl->Model = $searchTerm;
		$tmpList = $tmpl->Search(true,true);
		$devList = array();
		foreach( $tmpList as $t ) {
			$dev = new Device();
			$dev->TemplateID = $t->TemplateID;
			$tList = $dev->Search(true,false);
			$devList = $devList + $tList;
		}
		$resultcount=count($devList);
		$title=__("Device Model search results for")." &quot;$searchTerm&quot;";
	}elseif($searchKey=="notes"){
		$dev->Notes=$searchTerm;
		$devList=$dev->LooseSearch(true);
		$cab->Notes=$searchTerm;
		$cabList=$cab->LooseSearch(true);
		// DevicePorts and PowerPorts use the same structures and functions so 
		// we're just looping the search to not repeat code 
		foreach(array('DevicePorts','PowerPorts') as $pt){
			$p=new $pt();
			$p->Notes=$searchTerm;
			foreach($p->LooseSearch() as $port){
				// This is gonna be a little slow but this will cut down on duplicate lookups
				if(!isset($devList[$port->DeviceID])){
					$d=new Device($port->DeviceID);
					// These should all be valid but just in case, skip it
					if($d->GetDevice()){
						$devList[$d->DeviceID]=$d;
					}
				}
			}
		}
		$resultcount=count($devList)+count($cabList); 
		$title=__("Notes search results for")." &quot;$searchTerm&quot;";
	}elseif($searchKey=="dev"){
		// This is gonna be a generic catch all
		foreach($dev as $prop => $val){
			$dev->$prop=(isset($_GET[$prop]))?$_GET[$prop]:$val;
		}
		
		// Now check for any DCAs and add them
		$attrList=DeviceCustomAttribute::GetDeviceCustomAttributeList();
		foreach($attrList as $ca){
			$prop = $ca->Label;
			if(!empty($prop)){
				$dev->$prop=(isset($_GET[$prop]))?$_GET[$prop]:$dev->$prop;
			}
		}

		if ( isset( $_GET["loose"] )) {
			$devList = $dev->LooseSearch(true);
		} else {
			$devList=$dev->Search(true);
		}
		$resultcount=count($devList);
	}elseif($searchKey!=""){
		// This should be catching custom attribute searches
		$dev->$searchKey=$searchTerm;
		$devList=$dev->Search(true, true );
		$resultcount=count($devList);
		$title=__("Search results for")." $searchKey = &quot;$searchTerm&quot;";
	}

	$x=0;
	$temp=array(); // Store all devices for display
	$cabtemp=array(); // List of all cabinet ids for outerloop
	$childList=array(); // List of all blade devices
	$dctemp=array(); // List of datacenters involved with result set
	$pduList=array(); // List of CDUs
	foreach($devList as $devID => $device){
		if($device->DeviceType=="CDU"){
			$pduList[$devID]=$device;
		}else{
			$temp[$x]['devid']=$devID;
			$temp[$x]['label']=$device->Label;
			$temp[$x]['type']='srv'; // empty chassis devices need no special treatment leave them as a server
			$temp[$x]['cabinet']=$device->Cabinet;
			$temp[$x]['parent']=$device->ParentDevice;
			$temp[$x]['rights']=$device->Rights;
			$cabtemp[$device->Cabinet] = [];
			++$x;
			if($device->ParentDevice>0){
				foreach($uncleDaddy=$device->GetDeviceLineage() as $branches){
					if($branches->ParentDevice>0){
						$childList[$branches->ParentDevice]=""; // Create a list of chassis devices based on children present
					}
				}
			}
		}
	}
	if(isset($vmList)){
		foreach($vmList as $vmRow){
			$dev->DeviceID=$vmRow->DeviceID;
			// Prevent any vm's that might have been stranded from making bad links
			if($dev->GetDevice()){
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
					$temp[$x]['rights']=$dev->Rights;
					$cabtemp[$dev->Cabinet]['name']="";
					++$x;
					if($dev->ParentDevice!=0){
						$childList[$dev->ParentDevice]=""; // Create a list of chassis devices based on children present
					}
				}
			}else{
				// remove this reslult from the overall count
				$resultcount--;
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
				$dev->GetDevice();

				$temp[$x]['devid']=$dev->DeviceID;
				$temp[$x]['label']=$dev->Label;
				$temp[$x]['type']='chassis';
				$temp[$x]['cabinet']=$dev->Cabinet;
				$temp[$x]['parent']=$dev->ParentDevice;
				$temp[$x]['rights']=$dev->Rights;
				$cabtemp[$dev->Cabinet]['name']="";
				++$x;
			}
		}
	}

	// Add racks that matched the search term to the rack list
	if(isset($cabList)&&is_array($cabList)){
		foreach($cabList as $CabinetID => $row){
			$cabtemp[$CabinetID]['name']=$row->Location;
		}
	}

	// Add racks that are parents of the PDU devices to the rack list
	if(isset($pduList)&&is_array($pduList)){
		foreach($pduList as $key => $row){
			if(!isset($cabtemp[$row->Cabinet]['name'])){
				$cabtemp[$row->Cabinet]['name']="";
			}
		}
	}

	// Add Rack Names To Temp Cabinet Array
	foreach($cabtemp as $key => $row){
		if($key>0){
			$cab->Location='dc lookup error';
			$cab->DataCenterID='0';
			$cab->CabinetID=$key;
			if($cab->GetCabinet()){
				$cabtemp[$key]['name']=$cab->Location;
				$cabtemp[$key]['dc']=$cab->DataCenterID;
				$dctemp[$cab->DataCenterID]=''; // Add datacenter id to list for loop
			}else{
				unset($cabtemp[$key]);
			}
		} elseif( $key=="-1" ) {
			$cabtemp[$key]['name']="Storage Room";
			$cabtemp[$key]['dc']=0;
			$dctemp[0]='Storage Room'; // Add datacenter id to list for loop
		} else {
			$cabtemp[$key]['name']="Disposed";
			$cabtemp[$key]['dc']=0;
			$dctemp[0]="Disposed";
		}
	}
	// Add Datacenter names to temp array
	foreach($dctemp as $DataCenterID => $Name){
		if($DataCenterID>0){
			$dc->DataCenterID=$DataCenterID;
			$dc->GetDataCenter();
			$dctemp[$DataCenterID]=$dc->Name;
		}
	}
	/*  SORT ALL THE THINGS!  */
	// Sort the dc list
	if(!empty($dctemp)){
		natcasesort($dctemp);
	}
	// Sort cabinet array
	if(!empty($cabtemp)){
		$cabtemp=sort2d($cabtemp,'name');
	}
	// Sort array based on device label
	if(!empty($temp)){
		$devList=sort2d($temp,'label');
	}else{ 
		// it's possible to have cdu's and cabinets in the search results and no devices
		// this will clear the devList in case it contained special devices
		$devList=array();
	}
	if($resultcount>0){
		$searchresults=sprintf(__("Search complete. (%s) results."),"<span id=\"resultcount\">$resultcount</span>");
	}else{
		$searchresults=__("No matching devices found.");
	}

	// if json is set then return the device list as a json string
	if(isset($_REQUEST['json'])){
		header('Content-Type: application/json');
		echo json_encode($devList);
		exit;
	}

	// This is the looping portion that we'll call for each device
	function printDevice($row,$skip=false){
		global $devList,$vmList;

		if(!$skip){
			//In case of VMHost missing from inventory, this shouldn't ever happen
			if($row['label']=='' || is_null($row['label'])){$row['label']='VM Host Missing From Inventory';}
			if($row['rights']=="Write"){
				print "\t\t\t\t\t<li><a href=\"devices.php?DeviceID={$row['devid']}\">{$row['label']}</a>\n";
			}else{
				print "\t\t\t\t\t<li>{$row['label']}\n";
			}
		}
		// Created a nested list showing all blades residing in this chassis
		if($row['type']=='chassis'){
			print "\t\t\t\t\t\t<ul>\n";
			foreach($devList as $chKey => $chRow){
				if($chRow['parent']==$row['devid']){
					//In case of VMHost missing from inventory, this shouldn't ever happen
					if($chRow['label']=='' || is_null($chRow['label'])){$chRow['label']='VM Host Missing From Inventory';}
					$vmhost=($chRow['rights']=="Write")?"<a href=\"devices.php?DeviceID={$chRow['devid']}\">{$chRow['label']}</a>":$chRow['label'];
					print "\t\t\t\t\t\t\t<li><div><img src=\"images/blade.png\" alt=\"blade icon\"></div>$vmhost\n";
					// Create a nested list showing all VMs residing on this host.
					if($chRow['type']=='vm'){
						print "\t\t\t\t\t\t\t\t<ul>\n";
						foreach($vmList as $usedkey => $vm){
							if($vm->DeviceID==$chRow['devid']){
								print "\t\t\t\t\t\t\t\t\t<li><div><img src=\"images/vmcube.png\" alt=\"vm icon\"></div>$vm->vmName</li>\n";
								// Remove VMs that have already been processed
								unset($vmList[$usedkey]);
							}
						}
						print "\t\t\t\t\t\t\t\t</ul>\n";
					}
					if($chRow['type']=='chassis'){
						printDevice($chRow,$row['devid']==$chRow['parent']);
					}
					// Remove devices that we have already processed.
					unset($devList[$chKey]);
					print "\t\t\t\t\t\t\t</li>\n"; // Close out current list item
				}
			}
			print "\t\t\t\t\t\t</ul>\n";
		}
		// Create a nested list showing all VMs residing on this host.
		if($row['type']=='vm'){
			echo "\t\t\t\t\t\t<ul>\n";
			foreach($vmList as $usedkey => $vm){
				if($vm->DeviceID==$row['devid']){
					echo "\t\t\t\t\t\t\t<li><div><img src=\"images/vmcube.png\" alt=\"vm icon\"></div>$vm->vmName</li>\n";
					// Remove VMs that have already been processed
					unset($vmList[$usedkey]);
				}
			}
			echo "\t\t\t\t\t\t</ul>\n";
		}
		if(!$skip){
			echo "\t\t\t\t\t</li>\n";
		}
	}

	$subheader=(isset($title))?$title:"";				
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
  <script type="text/javascript" src="scripts/jquery.timer.js"></script>

<script type="text/javascript">
$(document).ready(function() {
	$('.datacenter').each(function(){
		$(this).prepend('<span class="bullet">&nbsp;</span>');
		$(this).find('.bullet').on('click', function(){
			if($(this).css('background-image')=='url(css/plus.gif)'){
				$(this).css('background-image', 'url(css/minus.gif)');
				$(this).parent('.datacenter').children('ol').toggle();
			}else{
				$(this).css('background-image', 'url(css/plus.gif)');
				$(this).parent('.datacenter').children('ol').toggle();
			}
		});
	});
	if($('#resultcount').text()=='1'){
		function pad(number, length) {
			var str = '' + number;
			while (str.length < length) {str = '0' + str;}
			return str;
		}
		function formatTime(time) {
			var min = parseInt(time / 6000),
				sec = parseInt(time / 100) - (min * 60),
				hundredths = pad(time - (sec * 100) - (min * 6000), 2);
			return pad(sec, 2);
		}
		var msg=$('<p>').append('<?php printf(__("Only one result, will autoforward in %s seconds."),'<span id="countdown"></span>'); ?>').click(function(){timer.stop();$(this).remove();})
		$('#resultcount').parent('p').append(msg);
		var currentTime=500,
		incrementTime=100,
		updateTimer=function(){
			$('#countdown').html(formatTime(currentTime));
			if(currentTime==0){
				// insert forward action here
				var lastlink=$('.datacenter').find('a').last().attr("href");
				location.href=lastlink;
				timer.stop();
			}
			currentTime -= incrementTime / 10;
			if (currentTime < 0) currentTime = 0;
		}

		var timer=$.timer(updateTimer, incrementTime, true);
	}
});
	function showall(){
		$('.center > div > ol').removeClass('hidecontents');
	}
	function hidedevices(){
		$('.center > div > ol').addClass('hidecontents');
	}
</script>

</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page search">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<?php echo '<div id="searchfilters"><button type="button" onclick="showall()">'.__("Show All").'</button><button type="button" onclick="hidedevices()">'.__("Racks Only").'</button></div>'; ?>
<div class="center"><div>
	<ol>
<?php
	// Since the number of Data Centers will be relatively small, simply cycle through all of them to see
	// if we have any cabinets with matches.
	foreach($dctemp as $DataCenterID=>$DataCenterName){
		print "\t\t<li class=\"datacenter\"><a href=\"dc_stats.php?dc=$DataCenterID\">$DataCenterName</a>\n\t\t<ol>\n";
		foreach($cabtemp as $cabID=>$cabRow){
			if($cabRow['dc']==$DataCenterID){
				print "\t\t\t<li class=\"cabinet\"><div><img src=\"images/serverrack.png\" alt=\"rack icon\"></div><a href=\"cabnavigator.php?cabinetid=$cabID\">{$cabRow['name']}</a>\n\t\t\t\t<ol>\n";
				//Always list PDUs directly after the cabinet device IF they exist
				if(isset($pduList)&&is_array($pduList)){
					// In theory this should be a short list so just parse the entire thing each time we read a cabinet.
					// if this ends up being a huge time sink, optimize this above then fix logic
					foreach($pduList as $key => $row){
						if($cabID == $row->Cabinet){
							$row->Label=($row->Label=="")?"&lt;".__("No label")."&gt;":$row->Label;
							print "\t\t\t\t\t<li class=\"pdu\"><a href=\"devices.php?DeviceID=$row->DeviceID\">$row->Label</a>\n";
						}
					}
				}
				if(!empty($devList)){
					foreach($devList as $key => $row){
						if($cabID==$row['cabinet'] && $row['parent']==0){
							printDevice($row);
						} 
					}
				}
				print "\t\t\t\t</ol>\n\t\t\t</li>\n";
			}
		}
		print "\t\t</ol>\n\t\t</li>\n";
	}
?>
	</ol>
<p><?php print $searchresults; ?></p>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
