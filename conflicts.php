<?php
	$devMode = true;

	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights();

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	if(isset($_POST['DeviceID']) && isset($_POST['power'])){
		if(isset($_POST['con']) && isset($_POST['pduid'])){
			$pwrConnection=new PowerConnection();
			$pwrConnection->DeviceID=$_POST['DeviceID'];
			$pwrConnection->PDUID=$_POST['pduid'];
			$pwrConnection->PDUPosition=$_POST['con'];
			$pwrConnection->DeviceConnNumber=$_POST['power'];
			if(isset($_POST['e'])){
				$pwrConnection->CreateConnection();
			}else{
				$pwrConnection->RemoveConnection();
			}
			echo 'ok';
		}else{
			$dev=new Device();
			$pwrConnection=new PowerConnection();
			$pdu=new PowerDistribution();

			$dev->DeviceID=$_POST['DeviceID'];
			$dev->GetDevice($facDB);

			$pwrConnection->DeviceID=($dev->ParentDevice>0)?$dev->ParentDevice:$dev->DeviceID;
			$pwrCords=$pwrConnection->GetConnectionsByDevice($facDB);

			print "<span>Server Name: $dev->Label</span><span># Power Supplies: $dev->PowerSupplyCount</span><div class=\"table border\">\n			<div><div>".__('Power Strip')."</div><div>".__('Plug #')."</div><div>".__('Power Supply')."</div></div>";
			foreach($pwrCords as $cord){
				$pdu->PDUID=$cord->PDUID;
				$pdu->GetPDU();
				print "			<div><div data=\"$pdu->PDUID\"><a href=\"power_pdu.php?pduid=$pdu->PDUID\">$pdu->Label</a></div><div><a href=\"power_connection.php?pdu=$pdu->PDUID&conn=$cord->PDUPosition\">$cord->PDUPosition</a></div><div".(($cord->DeviceConnNumber==$_POST['power'])?' class="bold"':' class="disabled"').">$cord->DeviceConnNumber</div></div>\n";
			}
			print "</div>";
		}
		exit;
	}


	if(isset($_POST['EndpointDeviceID'])){
		$networkPatches=new SwitchConnection();
		$networkPatches->EndpointDeviceID=$_POST['EndpointDeviceID'];
		if(isset($_POST['SwitchDeviceID']) && isset($_POST['SwitchPortNumber'])){
			$networkPatches->SwitchDeviceID=$_POST['SwitchDeviceID'];
			$networkPatches->SwitchPortNumber=$_POST['SwitchPortNumber'];
			if(isset($_POST['EndpointPort'])){ // Update Connection
				$networkPatches->GetSwitchPortConnector($facDB);
				$networkPatches->EndpointPort=$_POST['EndpointPort'];
				$networkPatches->UpdateConnection($facDB);
				print "ok";
			}else{ // Delete Connection
				$networkPatches->RemoveConnection($facDB);
				print "ok";
			}
		}else{
			$patchList=$networkPatches->GetEndpointConnections($facDB);
			$tmpDev=new Device();
			$tmpDev->DeviceID=$networkPatches->EndpointDeviceID;
			$tmpDev->GetDevice($facDB);

			print "<span>Server Name: <a href=\"devices.php?deviceid=$tmpDev->DeviceID\">$tmpDev->Label</a></span><span># Data Ports: $tmpDev->Ports</span><div class=\"table border\">\n				<div><div>".__('Switch')."</div><div>".__('Switch Port')."</div><div>".__('Device Port')."</div><div>".__('Notes')."</div></div>\n";

				foreach($patchList as $patchConn){
					$tmpDev->DeviceID=$patchConn->SwitchDeviceID;
					$tmpDev->GetDevice($facDB);
					print "\t\t\t\t<div><div data=\"$patchConn->SwitchDeviceID\"><a href=\"devices.php?deviceid=$patchConn->SwitchDeviceID\">$tmpDev->Label</a></div><div><a href=\"changepatch.php?switchid=$patchConn->SwitchDeviceID&portid=$patchConn->SwitchPortNumber\">$patchConn->SwitchPortNumber</a></div><div>$patchConn->EndpointPort</div><div>$patchConn->Notes</div></div>\n";
				}
			print "</div><!-- END div.table -->\n";
		}
		exit;
	}



	$body="";
	$conflicts=0;
	// This will only have a conflict if someone hand entered data.  These will be unique cases that we should look at by hand.
	$sql="SELECT PDUID, CONCAT(PDUID,'-',PDUPosition) AS KEY1, COUNT(PDUID) AS Count  FROM fac_PowerConnection GROUP BY KEY1 HAVING (COUNT(KEY1)>1) ORDER BY PDUID ASC;";
	$results=mysql_query($sql, $facDB);
	if(mysql_num_rows($results)>0){
		$body.="<p>This is a problem that will need a custom fix.  Please email the output below to wilbur@wilpig.org</p>";
		while($row=mysql_fetch_array($results)){
			$body.=print_r($row, TRUE);
		}
		$conflicts+=0;
	}else{
		$body.="<p>No collisions detected for Power Connections (PDUID,PDUPosition)</p>\n";
		$conflicts+=1;
	}

	$sql="SELECT DeviceID, CONCAT(DeviceID,'-',DeviceConnNumber) AS KEY2, DeviceConnNumber, COUNT(DeviceID) AS Count FROM fac_PowerConnection GROUP BY KEY2 HAVING (COUNT(KEY2)>1) ORDER BY DeviceID ASC;";
	$results=mysql_query($sql, $facDB);

	if(mysql_num_rows($results)>0){
		$body.="<p>The list below are devices that have multiple connections to the same power supply.</p>";
		$body.='<div class="table border power"><div><div>DeviceID</div><div>KEY2</div><div>Count</div></div>';
		while($row=mysql_fetch_array($results)){
			$body.="<div><div>{$row['DeviceID']}</div><div data={$row['DeviceConnNumber']}>{$row['KEY2']}</div><div>{$row['Count']}</div></div>";
		}
		$body.='</div>';
		$conflicts+=0;
	}else{
		$body.="No collisions detected for Power Connections (DeviceID,DeviceConnNumber)<br>\n";
		$conflicts+=1;
	}

	// Check for duplicated switch ports same as initial power check this should only have a conflict and hand altered data.
	$sql="SELECT SwitchDeviceID, CONCAT(SwitchDeviceID,'-',SwitchPortNumber) AS KEY1, COUNT(SwitchDeviceID) AS Count FROM fac_SwitchConnection GROUP BY KEY1 HAVING (COUNT(KEY1)>1) ORDER BY SwitchDeviceID ASC;";
	$results=mysql_query($sql, $facDB);
	if(mysql_num_rows($results)>0){
		$body.="<p>This is a problem that will need a custom fix.  Please email the output below to wilbur@wilpig.org</p>";
		while($row=mysql_fetch_array($results)){
			$body.=print_r($row, TRUE);
		}
		$conflicts+=0;
	}else{
		$body.="<p>No collisions detected for Switch Connections (SwitchDeviceID,SwitchPortNumber)</p>\n";
		$conflicts+=1;
	}


	$sql="SELECT SwitchDeviceID, SwitchPortNumber, EndpointDeviceID, EndpointPort, CONCAT(EndpointDeviceID,'-',EndpointPort) AS KEY2, COUNT(EndpointDeviceID) AS Count FROM fac_SwitchConnection GROUP BY KEY2 HAVING (COUNT(KEY2)>1) ORDER BY EndpointDeviceID ASC;";
	$results=mysql_query($sql, $facDB);
	if(mysql_num_rows($results)>0){
		$body.="<p>The list below are devices that have multiple connections to the same network card.</p>";
		$body.='<div class="table border network"><div><div>DeviceID</div><div>KEY2</div><div>Count</div></div>';
		while($row=mysql_fetch_array($results)){
			$body.="<div><div>{$row['EndpointDeviceID']}</div><div>{$row['KEY2']}</div><div data=\"{$row['EndpointPort']}\">{$row['Count']}</div></div>";
		}
		$body.='</div>';
		$conflicts+=0;
	}else{
		$body.="<p>No collisions detected for Switch Connections (EndpointDeviceID,EndpointPort)</p>\n";
		$conflicts+=1;
	}

	if($conflicts==4){
			header('Location: '.redirect("install.php"));
			exit;
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
<script type="text/javascript">
	$(document).ready(function() {
		$('.power > div:first-child ~ div, .network > div:first-child ~ div').each(function(){
			$(this).append('<div>edit</div>');
		});
		$('.power > div:first-child ~ div > div:last-child').each(function(){
			var editbox=$(this);
			var devid=$(this).prev().prev().prev().text();
			var ps=$(this).prev().prev().attr('data');
			$(this).click(function(){
				$.ajax({
					type: 'POST',
					url: 'conflicts.php',
					data: 'DeviceID='+devid+'&power='+ps,
					success: function(edit){
						editbox.unbind('click');
						var pduid;
						var con;
						// get an edit form
						editbox.html(edit);
						editbox.find('.table > div:first-child ~ div > div:last-child').each(function(){
							if($(this).hasClass('bold')){
								var p=$(this).prev();
								var row=$(this).parent();
								$(this).after('<div>Edit</div>');
								$(this).next().click(function(){
									$(this).prev().html('<input value="'+$(this).prev().text()+'"></input>');
									$(this).prev().children('input').change(function(){
										// issue ajax update command then change this back from an input to plain text
										var par=$(this).parent();
										pduid=p.prev().attr('data');
										con=p.text();
										ps=$(this).val();
										$.ajax({
											type: 'POST',
											url: 'conflicts.php',
											data: 'DeviceID='+devid+'&power='+ps+'&pduid='+pduid+'&con='+con+'&e=1',
											success: function(data){
												if(data=='ok'){
													par.text(ps);
												//	row.remove();
												}
											}
										}); 
									});
								});
							}
						});
						editbox.find('.table > div:first-child ~ div > div:last-child').each(function(){
							if($(this).prev().hasClass('bold')){
								var p=$(this).prev();
								var row=$(this).parent();
								$(this).after('<div>Delete</div>');
								$(this).next().click(function(){
									pduid=p.prev().prev().attr('data');
									con=p.prev().text();
									$.ajax({
										type: 'POST',
										url: 'conflicts.php',
										data: 'DeviceID='+devid+'&power='+ps+'&pduid='+pduid+'&con='+con,
										success: function(data){
											if(data=='ok'){
												row.remove();
											}
										}
									});
								});
							}
						});
					}
				});
			});
		});
		$('.network > div:first-child ~ div > div:last-child').each(function(){
			var editbox=$(this);
			var devid=$(this).prev().prev().prev().text();
			var dp=$(this).prev().attr('data');
			$(this).click(function(){
				$.ajax({
					type: 'POST',
					url: 'conflicts.php',
					data: 'EndpointDeviceID='+devid,
					success: function(edit){
						editbox.unbind('click');
						var sw;
						var sp;
						// get an edit form
						editbox.html(edit);
						editbox.find('.table > div:first-child ~ div > div:nth-child(3)').each(function(){
							var nic=$(this);
							if(nic.text()==dp){
								var row=$(this).parent();
								row.append('<div>Edit</div>');
								$(this).next().next().click(function(){
									sw=row.find('div:first-child').attr('data');
									sp=$(this).prev().prev().prev().text();
									nic.html('<input value="'+nic.text()+'"></input>');
									nic.children('input').change(function(){
										var change=$(this).val();
										$.ajax({
											type: 'POST',
											url: 'conflicts.php',
											data: 'EndpointDeviceID='+devid+'&SwitchDeviceID='+sw+'&SwitchPortNumber='+sp+'&EndpointPort='+$(this).val(),
											success: function(data){
												if(data=='ok'){
													nic.text(change);
												}
											}
										});
									});
								});
							}
						});
						editbox.find('.table > div:first-child ~ div > div:nth-child(3)').each(function(){
							if($(this).text()==dp){
								var row=$(this).parent();
								row.append('<div>Delete</div>');
								row.find('div:last-child').click(function(){
									sw=row.find('div:first-child').attr('data');
									sp=row.find('div:nth-child(2)').text();
									$.ajax({
										type: 'POST',
										url: 'conflicts.php',
										data: 'EndpointDeviceID='+devid+'&SwitchDeviceID='+sw+'&SwitchPortNumber='+sp,
										success: function(data){
											if(data=='ok'){
												row.remove();
											}
										}
									});
								});
							}
						});
					}
				});
			});
		});
	});
</script>
<style type="text/css">
div.table > div > div {vertical-align: top;}
.bold {font-weight: bold;}
.disabled {background-color: lightGrey; font-style: italic;}
.disabled:after {
	content: " - ok";
}
.center > div > p { max-width: 400px;}
.center > div > hr ~ p { display: list-item; }
</style>

</head>
<body>
<div id="header"></div>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<div class="center"><div>
<p>The tables below show devices that are currently sharing resources and will need to be resolved before the new database update can be applied.</p>
<p>The Key in each table is made up of the DeviceID and the ID of resource that is currently being shared incorrectly.</p>
<p>Click &quot;edit&quot; in each row to display the records that are in conflict.  Either use the word &quot;Delete&quot; to remove the connection outright or use the &quot;Edit&quot; option to change the value in the box.</p>
<p>After you have finished making changes <a href="conflicts.php">reload this page</a> until there are no conflicts remaing.  It will then automatically put you back to the installer and finish applying the update.</p>
<hr>


<?php echo $body; ?>
<!-- CONTENT GOES HERE -->



</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
