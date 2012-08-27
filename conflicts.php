<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	if(isset($_POST['DeviceID']) && isset($_POST['power'])){
		if(isset($_POST['con']) && isset($_POST['pduid'])){
			$pwrConnection=new PowerConnection();
			$pwrConnection->PDUID=$_POST['pduid'];
			$pwrConnection->PDUPosition=$_POST['con'];
			$pwrConnection->RemoveConnection($facDB);
			echo 'ok';
		}else{
			$dev=new Device();
			$pwrConnection=new PowerConnection();
			$pdu=new PowerDistribution();

			$dev->DeviceID=$_POST['DeviceID'];
			$dev->GetDevice($facDB);

			$pwrConnection->DeviceID=($dev->ParentDevice>0)?$dev->ParentDevice:$dev->DeviceID;
			$pwrCords=$pwrConnection->GetConnectionsByDevice($facDB);

			print "<span>Server Name: $dev->Label</span><span># Power Supplies: $dev->PowerSupplyCount</span><div class=\"table border\">\n			<div><div>"._('Power Strip')."</div><div>"._('Plug #')."</div><div>"._('Power Supply')."</div></div>";
			foreach($pwrCords as $cord){
				$pdu->PDUID=$cord->PDUID;
				$pdu->GetPDU($facDB);
				print "			<div><div data=\"$pdu->PDUID\"><a href=\"power_pdu.php?pduid=$pdu->PDUID\">$pdu->Label</a></div><div><a href=\"power_connection.php?pdu=$pdu->PDUID&conn=$cord->PDUPosition\">$cord->PDUPosition</a></div><div".(($cord->DeviceConnNumber==$_POST['power'])?' class="bold"':' class="disabled"').">$cord->DeviceConnNumber</div></div>\n";
			}
			print "</div>";
		}
		exit;
	}


	$body="";

	// This will only have a conflict if someone hand entered data.  These will be unique cases that we should look at by hand.
	$sql="SELECT PDUID, CONCAT(PDUID,'-',PDUPosition) AS KEY1, COUNT(PDUID) AS Count  FROM fac_PowerConnection GROUP BY KEY1 HAVING (COUNT(KEY1)>1) ORDER BY PDUID ASC;";
	$results=mysql_query($sql, $facDB);
	if(mysql_num_rows($results)>0){
		$body.="<p>This is a problem that will need a custom fix.  Please email the output below to wilbur@wilpig.org</p>";
		while($row=mysql_fetch_array($results)){
			$body.=print_r($row, TRUE);
		}
	}else{
		$body.="No collisions detected for Power Connections (PDUID,PDUPosition)<br>\n";
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
	}else{
		$body.="No collisions detected for Power Connections (DeviceID,DeviceConnNumber)<br>\n";
	}

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		$('.power > div:first-child ~ div').each(function(){
			$(this).append('<div>edit</div>');
		});
		$('.power > div:first-child ~ div > div:last-child').each(function(){
			var editbox=$(this);
			var devid=$(this).prev().prev().prev().text();
			var ps=$(this).prev().prev().attr('data');
			$(this).click(function(){
				$.ajax({
					type: 'POST',
					url: 'test.php',
					data: 'DeviceID='+devid+'&power='+ps,
					success: function(edit){
						editbox.unbind('click');
						var pduid;
						var con;
						// get an edit form
						editbox.html(edit);
						editbox.find('.table > div:first-child ~ div > div:last-child').each(function(){
							if($(this).hasClass('bold')){
								var row=$(this).parent();
								$(this).after('<div>Edit</div>');
								$(this).next().click(function(){
									$(this).prev().html('<input value="'+$(this).prev().text()+'"></input>');
								});
								$(this).children('input').change(function(){
									// issue ajax update command then change this back from an input to plain text
								});
							}
						});
						editbox.find('.table > div:first-child ~ div > div:last-child').each(function(){
							if($(this).prev().hasClass('bold')){
								var row=$(this).parent();
								$(this).after('<div>Delete</div>');
								$(this).next().click(function(){
									pduid=p.prev().prev().attr('data');
									con=p.prev().text();
									$.ajax({
										type: 'POST',
										url: 'test.php',
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
	});
</script>
<style type="text/css">
div.table > div > div {vertical-align: top;}
.bold {font-weight: bold;}
.disabled {background-color: lightGrey; font-style: italic;}
.disabled:after {
	content: " - ok";
}
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



<?php echo $body; ?>
<!-- CONTENT GOES HERE -->



</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
