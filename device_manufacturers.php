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

	if(isset($_POST['setManufacturer'])){
		$mfg->ManufacturerID=$_POST['ManufacturerID'];
		$mfg->GetManufacturerByID();
		$mfg->GlobalID=$_POST['GlobalID'];
		$mfg->Name=$_POST['Name'];
		$mfg->UpdateManufacturer();

		header('Content-Type: application/json');
		echo json_encode($mfg);
		exit;
	}
	// END - AJAX

	if(isset($_REQUEST["manufacturerid"]) && $_REQUEST["manufacturerid"] >0){
		$mfg->ManufacturerID=(isset($_POST['manufacturerid']) ? $_POST['manufacturerid'] : $_GET['manufacturerid']);
		$mfg->GetManufacturerByID();
	}

	$status="";
	if(isset($_POST["action"])&&(($_POST["action"]=="Create")||($_POST["action"]=="Update"))){
		$mfg->ManufacturerID=$_POST["manufacturerid"];
		$mfg->Name=trim($_POST["name"]);

		if($mfg->Name != null && $mfg->Name != ""){
			if($_POST["action"]=="Create"){
					$mfg->AddManufacturer();
			}else{
				$status="Updated";
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
	div.table.border { background-color: white; }
	div.table.border > div:nth-child(2n) { background-color: lightgray; }
	div.table > div > div { padding: 0.2em; }
	.change .diff { color: red; border: 1px dotted grey; }

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
				data: {Name:row.local.Name},
				success: function(data){

				},
				error: function(data){

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

		// Check against master list
		MashLists();
	}

	function MashLists(){
		var ml=GetMasterList();
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
   <div><label for="manufacturerid">',__("Manufacturer"),'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="manufacturerid" id="manufacturerid" onChange="form.submit()">
   <option value=0>',__("New Manufacturer"),'</option>';

	foreach($mfgList as $mfgRow){
		if($mfg->ManufacturerID==$mfgRow->ManufacturerID){$selected=" selected";}else{$selected="";}
		echo "<option value=\"$mfgRow->ManufacturerID\"$selected>$mfgRow->Name</option>\n";
	}

echo '	</select></div>
</div>
<div>
   <div><label for="name">',__("Name"),'</label></div>
   <div><input type="text" class="validate[required,minSize[1],maxSize[40]]" name="name" id="name" maxlength="40" value="',$mfg->Name,'"></div>
</div>
<div class="caption">';

	if($mfg->ManufacturerID >0){
		echo '   <button type="submit" name="action" value="Update">',__("Update"),'</button>';
	}else{
		echo '   <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
?>
</div>
</div><!-- END div.table -->
</form>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
