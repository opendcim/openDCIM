<?php
	require_once "db.inc.php";
	require_once "facilities.inc.php";

	$subheader=__("Data Center Operations Work Order Builder");
	$error = '';
	$result = 0;

	if(!isset($_COOKIE["workOrder"]) || (isset($_COOKIE["workOrder"]) && $_COOKIE["workOrder"]=="" )){
		header("Location: ".redirect());
		exit;
	}

	$devList=array();
	$woList=json_decode($_COOKIE["workOrder"]);
	foreach($woList as $woDev){
		$dev=new Device();
		$dev->DeviceID=$woDev;
		if($dev->GetDevice()){
			$devList[]=$dev;
		}
	}

	if (isset($_POST['action']) && $_POST['action'] == 'Send'){

		require_once( "connections_spreadsheet.php" );

		$mediaIDList = array();
		if(isset($_COOKIE['connectionsMediaList'])){
			$mediaIDList = json_decode($_COOKIE['connectionsMediaList']);
		}else{
			$mediaIDList[]='-1';
			foreach(MediaTypes::GetMediaTypeList() as $mt){
				$mediaIDList[]=''.$mt->MediaID;
			}
		}
		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx(generate_spreadsheet($devList,$mediaIDList));

		$tmpName = @tempnam(\PhpOffice\PhpSpreadsheet\Shared\File::sys_get_temp_dir(),'tmpcnxs');
		$writer->save($tmpName);

		$mail = new DCIMMail(true);
		$mail->Subject = __("openDCIM-workorder-".date( "YmdHis" )."-connections");
		$mail->addAttachment( $config->ParameterArray["PDFLogoFile"], "logo.png" );

		$htmlMessage = sprintf( "<!doctype html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>Device Port Connections</title></head><body><div id=\"header\" style=\"padding: 5px 0;background: %s;\"><center><img src=\"%s\"></center></div><div class=\"page\">\n", $config->ParameterArray["HeaderColor"], "logo.png" );
		
		$htmlMessage .= sprintf("<h3>Work Order %s</h3><p>UID: %s</p><p>Name: %s, %s</p><p>%s %s has requested this work order. Details are attached to this message.</p>",date( "YmdHis" ),$person->UserID,$person->LastName,$person->FirstName,$person->FirstName,$person->LastName);
		
		$attachment = Swift_Attachment::fromPath($tmpName,"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
		if ( $_REQUEST["deviceid"] == "wo" ) {
			$mail->addAttachment( $tmpName, "openDCIM-workorder-".date( "YmdHis" )."-connections.xlsx", "", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" );
		} else {
			$mail->addAttachment( $tmpName, "openDCIM-dev" . $dev->DeviceID . "-connections.xlsx", "", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" );
		}
		
		$mail->Body = $htmlMessage;
		try {
			$mail->send();
		} catch (Exception $e) {
			error_log( "Mailer error: {$mail->ErrorInfo}" );
		}
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
  <script type="text/javascript" src="scripts/jquery.cookie.js"></script>
<script>
	$(document).ready(function(){
		$('#clear').click(function(){
			$.removeCookie('workOrder');
			alert(clearedMessage);
			location.href="index.php";
		});
		$('#unreserve').click(function(){
			var workList = JSON.parse($.cookie("workOrder"));
			for(var x in workList) {
				if ( workList[x] != 0 ) {
					// We are only updating one field, so basically we don't
					// give a damn about pulling the existing data in
					$.ajax({
						type: "POST",
						url: "/api/v1/device/"+workList[x],
						data: { "Reservation" : "0" }
					});
				}
			}
		});
		$('#storage').click(function(){
			if (!confirm(confirmMoveMessage)) {
				return;
			}
			var workList = JSON.parse($.cookie("workOrder"));
			let successCount = 0;
			let errorCount = 0;

			let promises = workList.map(function(devID){
			if (devID != 0) {
				return $.ajax({
					type: "POST",
					url: "/api/v1/device/" + devID + "/store"
				}).done(function(){
					successCount++;
				}).fail(function(jqXHR){
					console.error("Error for device ID " + devID + ": " + jqXHR.responseText);
					errorCount++;
				});
			}
			});

			Promise.allSettled(promises).then(function(){
				let message = `${moveSuccessMessage}: ${successCount} ${moveSuccessCountMessage}, ${errorCount} ${moveErrorCountMessage}.`;
				alert(message);
			});
		});
		$('#audit').click(function(){
	let workList = JSON.parse($.cookie("workOrder"));
	if (!workList || workList.length === 0) {
		$('#auditResults').html('<div class="error-message"><?php echo __("No devices selected."); ?></div>');
		return;
	}

	$('#auditResults').html('<p><?php echo __("Running audit..."); ?></p>');

	let auditPromises = workList
		.filter(devID => devID != 0)
		.map(function(devID){
			return $.ajax({
				type: "PUT",
				url: `/api/v1/audit?DeviceID=${devID}`
			}).then(function(response){
				return { id: devID, result: response };
			}).catch(function(){
				return { id: devID, error: true };
			});
		});

	Promise.all(auditPromises).then(function(results){
		let html = '<h4><?php echo __("Audit Results"); ?></h4><ul>';
		results.forEach(function(r){
			if (r.error) {
				html += `<li>Device ${r.id}: <strong><?php echo __("Error during audit"); ?></strong></li>`;
			} else {
				html += `<li>Device ${r.id}: OK</li>`;
			}
		});
		html += '</ul>';
		$('#auditResults').html(html);
	});
});

		storeMediaList();
	});
</script>
<script type="text/javascript">
	// Select all the elements in the list of Media Types
	function selectAll(){
		var sel = document.getElementById("mediaTypes");
		for (i=0;i<sel.length;i++){
			sel.options[i].selected = true;
		}
		storeMediaList();
	}
	// Store the desired Media IDs in a cookie
	function storeMediaList(){
		var sel = document.getElementById("mediaTypes");
		var connectionsMediaList = new Array();
		for(i=0;i<sel.length;i++){
			if(sel.options[i].selected){
				connectionsMediaList.push(sel.options[i].value);
			}
		}
		$.cookie('connectionsMediaList',JSON.stringify(connectionsMediaList));
	}
	// Messages for the move to storage dialog
	let confirmMoveMessage = "<?php echo __("Are you sure you want to move the selected items to storage?"); ?>";
	let moveSuccessMessage = "<?php echo __("Move completed"); ?>";
	let moveSuccessCountMessage = "<?php echo __("Successes"); ?>";
	let moveErrorCountMessage = "<?php echo __("Errors"); ?>";
	// Message #clear
	let clearedMessage = "<?php echo __("Work order cleared successfully."); ?>";
</script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">

<?php
	if($error!=""){echo '<fieldset class="exception border error"><legend>Errors</legend>'.$error.'</fieldset>';}
	else if($result == 1) {echo '<h3 id="messages">Work Order Sent</h3>';}
?>
<div class="center"><div>
<!-- CONTENT GOES HERE -->
<?php
	echo '<form name="orderform" id="orderform" method="POST">';
	print "<h2>".__("Work Order Contents")."</h2>
<div style=\"width: 100%; overflow: hidden;\"><div class=\"table\" style=\"float: left;\">
	<div><div>".__("Cabinet")."</div><div>".__("Position")."</div><div>".__("Label")."</div><div>".__("Image")."</div></div>\n";
	
	foreach($devList as $dev){
		// including the $cab and $devTempl in here so it gets reset each time and there 
		// is no chance for phantom data
		$cab=new Cabinet();
		if($dev->ParentDevice>0){
			$pdev=new Device();
			$pdev->DeviceID=$dev->GetRootDeviceID();
			$pdev->GetDevice();
			$cab->CabinetID=$pdev->Cabinet;
		}else{
			$cab->CabinetID=$dev->Cabinet;
		}
		$cab->GetCabinet();
		
		$devTmpl=new DeviceTemplate();
		$devTmpl->TemplateID=$dev->TemplateID;
		$devTmpl->GetTemplateByID();

		$position=($dev->Height==1)?$dev->Position:$dev->Position."-".($dev->Position+$dev->Height-1);

		print "<div><div>$cab->Location</div><div>$position</div><div>$dev->Label</div><div>".$dev->GetDevicePicture('','','nolinks')."</div></div>\n";
	}
	
	print '</div><div style="display: inline-block; height: 100%; margin: 0pt 0pt 0pt 24pt;">';

	$checklist='<a style="background:#fff; width: 100%; text-align: center; display: block;">Media Types</a>
<select id="mediaTypes" name="Media Types" multiple="multiple" onchange="storeMediaList()"
 style="width: 100%" size=15>\n';
	$checklist .= '<option value="-1" selected>Power Connections</option>\n';
	foreach(MediaTypes::GetMediaTypeList() as $mt){
		$checklist .= '<option value="'.$mt->MediaID.'" selected>'.$mt->MediaType.'</option>\n';
	}
	$checklist.='</select><br/><button type="button" style="width: 100%;" onclick="selectAll()">'.__("Select All").'</button>';

	print $checklist.'</div></div><br/><div style="display: block; margin: auto;">
<a href="export_port_connections.php?deviceid=wo"><button type="button">'.__("Export Connections").'</button></a>
<button type="submit" name="action" value="Send">'.__("Email to DC Team Address").'</button>';
?>
<button type="button" id="unreserve"><?php print __("Clear Reservation Flag"); ?></button>
<button type="button" id="storage"><?php print __("Move Items to Storage"); ?></button>
<button type="button" id="clear"><?php print __("Clear"); ?></button>
<button type="button" id="audit"><?php print __("Audit Selected Devices"); ?></button></div>
</form>
<div id="auditResults" style="margin-top: 1em;"></div>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>