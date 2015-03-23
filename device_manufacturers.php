<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$subheader=__("Data Center Manufacturer Listing");

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$mfg=new Manufacturer();

	// AJAX Start
	if(isset($_GET['getManufacturers'])){
		header('Content-Type: application/json');
		echo json_encode($mfg->GetManufacturerList());
		exit;
	}

	if(isset($_GET['getTemplateCount']) && isset($_GET['ManufacturerID'])){
		$temp=new DeviceTemplate();
		$temp->ManufacturerID=$_GET['ManufacturerID'];
		header('Content-Type: application/json');
		echo json_encode($temp->GetTemplateListByManufacturer());
		exit;
	}

	if(isset($_POST['setManufacturer'])){
		$mfg->ManufacturerID=$_POST['ManufacturerID'];
		$mfg->GetManufacturerByID();
		$mfg->GlobalID=$_POST['GlobalID'];
		$mfg->Name=$_POST['Name'];
		if($mfg->ManufacturerID==""){
			$mfg->CreateManufacturer();
		}else{
			$mfg->UpdateManufacturer();
		}

		header('Content-Type: application/json');
		echo json_encode($mfg);
		exit;
	}


	if(isset($_POST['action']) && $_POST["action"]=="Delete"){
		header('Content-Type: application/json');
		$response=false;
		if(isset($_POST["TransferTo"])){
			$mfg->ManufacturerID=$_POST['ManufacturerID'];
			if($mfg->DeleteManufacturer($_POST["TransferTo"])){
				$response=true;
			}
		}
		echo json_encode($response);
		exit;
	}

	// END - AJAX

	if(isset($_REQUEST["ManufacturerID"]) && $_REQUEST["ManufacturerID"] >0){
		$mfg->ManufacturerID=(isset($_POST['ManufacturerID']) ? $_POST['ManufacturerID'] : $_GET['ManufacturerID']);
		$mfg->GetManufacturerByID();
	}

	$status="";
	if(isset($_POST["action"])&&(($_POST["action"]=="Create")||($_POST["action"]=="Update"))){
		$mfg->ManufacturerID=$_POST["ManufacturerID"];
		$mfg->Name=trim($_POST["name"]);

		if($mfg->Name != null && $mfg->Name != ""){
			if($_POST["action"]=="Create"){
				if($mfg->CreateManufacturer()){
					header('Location: '.redirect("device_manufacturers.php?ManufacturerID=$mfg->ManufacturerID"));
				}else{
					$status=__("Error adding new manufacturer");
				}
			}else{
				$status=__("Updated");
				$mfg->UpdateManufacturer();
			}
		}
		//We either just created a manufacturer or updated it so reload from the db
		$mfg->GetManufacturerByID();
	}
	$mfgList=$mfg->GetManufacturerList();
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Device Class Templates</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>

  <style type="text/css">
	#using { margin-top: 1em; }

	div.table.border { background-color: white; }
	div.table.border > div:nth-child(2n) { background-color: lightgray; }
	div.table > div > div { padding: 0.2em; }
	.change .diff { color: red; border: 1px dotted grey; }
	.change .good { color: green; border: 1px dotted grey; }

	.ui-tooltip, .arrow:after {
		background: white;
		border: 2px solid black;
	}
	.ui-tooltip {
		padding: 10px 20px;
		border-radius: 20px;
		color: black;
		box-shadow: 0 0 7px black;
	}
	.ui-tooltip-content {
		color: red;
	}
	.ui-tooltip-content:before {
		content: "Current local value: ";
		color: black;
	}
	.arrow {
		width: 70px;
		min-height: auto;
		height: 16px;
		overflow: hidden;
		position: absolute;
		left: 50%;
		margin-left: -35px;
		bottom: -16px;
	}
	.arrow.top {
		top: -16px;
		bottom: auto;
	}
	.arrow.left {
		left: 20%;
	}
	.arrow:after {
		content: "";
		position: absolute;
		left: 20px;
		top: -20px;
		width: 25px;
		height: 25px;
		box-shadow: 6px 5px 9px -9px black;
		-webkit-transform: rotate(45deg);
		-ms-transform: rotate(45deg);
		transform: rotate(45deg);
	}
	.arrow.top:after {
		bottom: -20px;
		top: auto;
	}
  </style>

  <script type="text/javascript">
	// Store creds for quick access
	window.APIKey="e807f1fcf82d132f9bb018ca6738a19f";
	window.UserID="wilbur@wilpig.org";

	$(function() {
		$( document ).tooltip({
			items: ".change .diff",
			position: {
			my: "center bottom-20",
			at: "center top",
			using: function( position, feedback ) {
				$( this ).css( position );
				$( "<div>" )
					.addClass( "arrow" )
					.addClass( feedback.vertical )
					.addClass( feedback.horizontal )
					.appendTo( this );
				}
			}
		});
	});

	$(document).ready(function() {
		$('#mform').validationEngine({});
		$('#btn_syncrepo').click(BuildTable);
		$('#ManufacturerID').change(function(e){
			location.href='device_manufacturers.php?ManufacturerID='+this.value;
		});
		// Show number of templates using manufacturer
		$.get('',{getTemplateCount: $('#ManufacturerID').val()},function(data){
			$('#count').text(data.length);
		});

		$('button[name="action"][value="Delete"]').click(DeleteManufacturer);

	});

	$.widget( "opendcim.mrow", {
		_create: function(){
			var row=this;

			this.local=this.element.data('local');
			this.id=this.element.find('div:nth-child(1)');
			this.name=this.element.find('div:nth-child(2)');
			this.gid=this.element.find('div:nth-child(3)');
			this.share=this.element.find('div:nth-child(4)');
			this.keep=this.element.find('div:nth-child(5)');
			this.button=this.element.find('div:nth-child(6) > button');

			this.button.click(function(e){
				row.ButtonPress(e);
			});
		},
		BuildRow: function(manf){
			if(manf==undefined){
				manf=this.local;
			}

			this.id.text(manf.ManufacturerID);
			this.name.text(manf.Name);
			this.gid.text(manf.GlobalID);
			this.share.text(manf.ShareToRepo);
			this.keep.text(manf.KeepLocal);
		},
		ButtonPress: function(e){
			// We're not gonna bind a specific function to the button but check it at 
			// the time it is clicked to see what it should do
			var pushpull=this.button.data('action');
			if(pushpull=='pull'){
				this.PullFromMaster();
			}else if(pushpull=='push'){
				this.SubmitToMaster();
			}else{
				alert ("What you tryin' to pull Willis?!");
			}
		},
		SubmitToMaster: function(){
			var row=this;
			// Submit local version to master for possible inclusion
			$.ajax({
				type: 'put',
				url: 'https://repository.opendcim.org/api/manufacturer',
				async: false,
				headers:{
					'APIKey':window.APIKey,
					'UserID':window.UserID
				},
				data: {Name:row.local.Name},
				success: function(data){
					row.button.context.className="change";
					$(row.button[0].parentElement).addClass('good').text(data.message);
				},
				error: function(data){
					row.button.context.className="change";
					$(row.button[0].parentElement).addClass('diff').text(data.responseJSON.message);
				}
			});
		},
		PullFromMaster: function(){
			var row=this;
			// Get the most current data from the master
			$.ajax({
				type: 'get',
				url: 'https://repository.opendcim.org/api/manufacturer/'+this.gid.text(),
				async: false,
				success: function(data){
					// If there wasn't a server error continue
					if(data.errorcode==200 && !data.error){
						// Update local record with data from master
						$.post('',{setManufacturer:'',ManufacturerID:row.local.ManufacturerID,GlobalID:data.manufacturers[0].ManufacturerID,Name:data.manufacturers[0].Name}, function(data){
							// Update the screen with the new data 
							row.local.ManufacturerID=data.ManufacturerID;
							row.local.GlobalID=data.GlobalID;
							row.local.Name=data.Name;
							row.BuildRow();
							row.button.hide();
							row.element.removeClass('change');
						});
					}
				},
				error: function(data){
					console.log(data);
					alert ('well fuck');
				}
			});
		},

	});

	function BuildTable(){
		var table=$('<div>').addClass('table border');
		var header={ManufacturerID:'id',Name:'name',GlobalID:'gid',ShareToRepo:'share',KeepLocal:'local'};
		table.append(BuildRow(header));

		var ll=GetLocalList();
		for(var i in ll){
			table.append(BuildRow(ll[i]));
		}

		$('#mform > .table').replaceWith(table);
		$('#using').remove();

		// Check against master list
		MashLists();
	}

	function MashLists(){
		var ml=GetMasterList();
		var pl=GetPendingList();
		if($('.main h3').text().length == 0){
			$('#mform > .table > div:first-child ~ div').each(function(){
				var row=$(this);
				// No globalid set so let's try to compare our shit with the master to find a match
				if(row.data('local').GlobalID==0){
					var last=row.find('div:last-child').removeClass('hide');
					for(var i in ml){
						if(ml[i].Name.toLowerCase().replace(' ','') == row.data('local').Name.toLowerCase().replace(' ','')){
							row.addClass('change');
							row.find('div:nth-child(3)').addClass('diff').text(ml[i].ManufacturerID).attr('title',row.data('local').GlobalID);
							last.find('button').text('Pull from master').data('action','pull');
							if(ml[i].Name != row.data('local').Name){
								row.find('div:nth-child(2)').addClass('diff').text(ml[i].Name).attr('title',row.data('local').Name);
							}

							ml.splice(i, 1);
							break;
						}
					}
					// If they have access to the pending list then let's match shit up and remove the controls
					if(typeof pl != "undefined" && !pl.error){
						for(var i in pl.manufacturersqueue){
							if(pl.manufacturersqueue[i].Name.toLowerCase().replace(' ','') == row.data('local').Name.toLowerCase().replace(' ','')){
								row.addClass('change');
								row.find('div:nth-child(3)').addClass('diff').text(pl.manufacturersqueue[i].RequestID).attr('title',row.data('local').GlobalID);
								last.text("Pending: "+pl.manufacturersqueue[i].SubmissionDate).addClass("good");

								pl.manufacturersqueue.splice(i, 1);
								break;
							}
						}	
					}
				// ELSE we have a GlobalID already set so we need to pull that specific record and compare all the fields
				}else{
					for(var i in ml){
						if(ml[i].ManufacturerID == row.data('local').GlobalID){
							if(ml[i].Name !== row.data('local').Name){
								var last=row.find('div:last-child').removeClass('hide');
								row.addClass('change');
								row.find('div:nth-child(2)').addClass('diff').text(ml[i].Name).attr('title',row.data('local').Name);
								last.find('button').text('Pull from master').data('action','pull');
							}

							ml.splice(i, 1);
							break;
						}
					}
				}
			});
		}
		// Add global hits that didn't match to the end of the list
		for(var i in ml){
			var gm={ManufacturerID:0,Name:ml[i].Name,GlobalID:ml[i].ManufacturerID,ShareToRepo:0,KeepLocal:0};
			$('#mform > .table').append(BuildRow(gm)).find('div:last-child > div:last-child').removeClass('hide').find('button').text('Pull from master').data('action','pull');
		}
	}

	function BuildRow(manf){
		var row=$('<div>').data("local",manf);
		row.id=$('<div>').text(manf.ManufacturerID);
		row.name=$('<div>').text(manf.Name);
		row.gid=$('<div>').text(manf.GlobalID);
		row.share=$('<div>').text(manf.ShareToRepo);
		row.local=$('<div>').text(manf.KeepLocal);
		row.sync=$('<div>').addClass('hide').append($('<button>').attr('type','button').text('Send to master').data('action','push'));
		row.append(row.id,row.name,row.gid,row.share,row.local,row.sync);

		row.mrow();

		return row;
	}

	function GetMasterList(){
		var ml;
		$.ajax({
			type: 'get',
			url: 'https://repository.opendcim.org/api/manufacturer',
			dataType: 'json',
			async: false,
			success: function(data){
				ml = data;
			},
			error: function(data){
				$('.main h3').append($('<p>').text('Pull from repo: '+data.status+' - '+data.statusText));
				ml = [];
			}
		});

		return ml.manufacturers;
	}

	function GetLocalList(){
		var ll;
		$.ajax({
			type: 'get',
			data: {
				getManufacturers: '',
				},
			dataType: 'json',
			async: false,
			success: function(data){
				ll = data;
			}
		});

		return ll;
	}

	function GetPendingList(){
		var pl;
		$.ajax({
			type: 'get',
			url: 'https://repository.opendcim.org/api/manufacturer/pending/',
			async: false,
			headers:{
				'APIKey':window.APIKey,
				'UserID':window.UserID
			},
			success: function(data){
				pl = data;
			}
		});

		return pl;
	}

	function DeleteManufacturer(){
		$('#copy').replaceWith($('#ManufacturerID').clone().attr('id','copy'));
		$('#copy option[value=0]').remove();
		$('#copy option[value='+$('#ManufacturerID').val()+']').remove();
		$('#deletemodal').dialog({
			width: 600,
			modal: true,
			buttons: {
				Transfer: function(e){
					$('#doublecheck').dialog({
						width: 600,
						modal: true,
						buttons: {
							Yes: function(e){
								$.post('',{ManufacturerID: $('#ManufacturerID').val(), TransferTo: $('#copy').val(), action: 'Delete'},function(data){
									if(data){
										location.href='';
									}else{
										alert("Something's gone horrible wrong");
									}
								});
							},
							No: function(e){
								$('#doublecheck').dialog('destroy');
								$('#deletemodal').dialog('destroy');
							}
						}
					});
				},
				No: function(e){
					$('#deletemodal').dialog('destroy');
				}
			}
		});
	}

  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<h3>',$status,'</h3>
<div class="center"><div>
<form id="mform" method="POST">
<div class="table">
<div>
   <div><label for="ManufacturerID">',__("Manufacturer"),'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="ManufacturerID" id="ManufacturerID">
   <option value=0>',__("New Manufacturer"),'</option>';

	foreach($mfgList as $mfgRow){
		if($mfg->ManufacturerID==$mfgRow->ManufacturerID){$selected=" selected";}else{$selected="";}
		echo "<option value=\"$mfgRow->ManufacturerID\"$selected>$mfgRow->Name</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="gid">',__("Global ID"),'</label></div>
   <div><input type="text" id="gid" value="',$mfg->GlobalID,'" disabled></div>
</div>
<div>
   <div><label for="name">',__("Name"),'</label></div>
   <div><input type="text" class="validate[required,minSize[1],maxSize[40]]" name="name" id="name" maxlength="40" value="',$mfg->Name,'"></div>
</div>
<div class="caption">';

	if($mfg->ManufacturerID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>
	<button type="button" name="action" value="Delete">',__("Delete"),'</button>';
	}else{
		echo '   <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
	echo '	<button type="button" id="btn_syncrepo">',__("Sync with repository"),'</button>';
?>
</div>
</div><!-- END div.table -->
<?php
	if($mfg->ManufacturerID >0){
		echo '	<div id="using">',__("Templates using this Manufacturer"),':<span id="count">0</span></div>';
	}
?>
</form>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Manufacturer delete confirmation"),'" id="deletemodal">
		<div id="modaltext"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure that you want to delete this Manufacturer?"),'
		<br><br>
		<div>Transfer all existing templates to <select id="copy"></select></div>
		</div>
	</div>
	<div title="',__("Are you REALLY sure?"),'" id="doublecheck">
		<div id="modaltext" class="warning"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure REALLY sure?  There is no undo!!"),'
		<br><br>
		</div>
	</div>
</div>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
