<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
	$subheader=__("Data Center Operations Metrics");

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">

  <style type="text/css">
	#results { display: block; }
	#results .table > div:first-child {border-bottom: 1px solid black;font-weight: bold;}
	#results .table > div:nth-child(2n), #mfglist.table > div:nth-child(2n) { background-color: lightgrey; }
	#results .table > div > div:first-child,
	#results .table > div > div:nth-child(5),
	#results .table > div > div:nth-child(12),
	#results .table > div > div:last-child {white-space: nowrap; }
	#results .table img {max-width: 100px; max-height: 75px; }
	.change .diff { color: red; border: 1px dotted grey; }
	.change .good { color: green; border: 1px dotted grey; }
	.imagepreview img { max-height: 100%;max-width: 100%; }
	.hiddenports > div { display: inline-block; vertical-align: top; }

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

  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>

  <script type="text/javascript">
	// Store creds for quick access
	window.APIKey="<?php print $config->ParameterArray["APIKey"]; ?>";
	window.UserID="<?php print $config->ParameterArray["APIUserID"]; ?>";
<?php echo '
	var token="'.md5('unique_salt' . time()).'";
	var now="'.time().'";'
 ?>

/*
 * Function to read in an image file from either the repo
 * or the local install of dcim and convert the images to a base64
 * string that will be used as the src.  It also makes a file object
 * and stores that in .data('file') that we can use later to attach
 * to a form for submission to the repo or creation as a local save
 */

function convertImgToBase64(url, imgobj) {
	var canvas = document.createElement('CANVAS');
	var ctx = canvas.getContext('2d');
	var img = new Image;
	img.crossOrigin = "Anonymous";
	img.src = url;
	img.onload = function() {
		canvas.height = img.height;
		canvas.width = img.width;
		ctx.drawImage(img, 0, 0);
		var dataURL = canvas.toDataURL('image/png');
		// Add the image data to the jquery img object
		imgobj.prop('src',dataURL);
		// Below here is converting the base64 data into a file object
		var datas = dataURL.split(',', 2);
		var adata = atob(datas[1]);
		var arr = new Uint8Array(adata.length);
		for(var m = 0; m < adata.length; m++){
			arr[m] = adata.charCodeAt(m);
		}
		var blob = new Blob([arr.buffer] , { type:'image/png', extension:'png'});
		blob.name = url.split('/').pop();
		// Store the newly created file object in the data structure
		imgobj.data('file',blob);
		// Clean up 
		canvas = null;
	};
}
function test(imageURL){
	upload=function(data){
		//.......do something with file
		var uploadform = new FormData();
		uploadform.append("dir", "pictures");
		uploadform.append("filename", imageURL.split('/').pop());
		uploadform.append("token", token);
		uploadform.append("timestamp", now);
		uploadform.append("Filedata",data,imageURL.split('/').pop());
		var request = new XMLHttpRequest();
		request.open("POST", "scripts/uploadifive.php");
		request.send(uploadform);
	}
	convertImgToBase64(imageURL, upload);
}

	$(document).ready(function(){
		var select_manufacturerid=$('<select>').prop({'id':'slct_ManufacturerID'}).change(PullGlobalTemplates);
		var div_results=$('<div>').prop({'id':'results'});
		var tbl_results=$('<div>').addClass('table');
		var arr_localmanf=new Array();

		function MakeRow(dev,type){
			type=(typeof type=='undefined')?'GlobalID':'LocalID';
			var row=$('<div>');
			for(var i in props=['Model','Height','Weight','Wattage','DeviceType','PSCount','NumPorts','FrontPictureFile','RearPictureFile','ChassisSlots','RearChassisSlots','LastModified']){
				if(typeof dev=='undefined'){
					//header row
					row[props[i]]=$('<div>').text(props[i]).appendTo(row);
				}else{
					// Store the template at the row level so we have easy access later
					(type=='GlobalID')?row.data('globaldev',dev):row.data('localdev',dev);
					// Swap local ManufacturerID for global
					dev.ManufacturerID=arr_localmanf[select_manufacturerid.val()];
					row.data('TemplateID',dev.TemplateID).addClass(type+dev.TemplateID);
					// nest the object so we can use it later
					row.data('object',row);
					var cellcontent=$('<div>').text(dev[props[i]]).appendTo(row);
					row[props[i]]=cellcontent;
					// convert url to an actual image
					if(props[i].search('PictureFile')!='-1' && dev[props[i]]!=''){
						// Adjust picture url for local images vs those from the repository
						if(dev[props[i]].search('repository')=='-1'){
							dev[props[i]]='pictures/'+dev[props[i]];
						}
						var devimage=$('<img>');
						// Function that converts the image into a datagram
						convertImgToBase64(dev[props[i]], devimage);
						// Strip 'picture/' back out of the file name
						dev[props[i]]=dev[props[i]].replace("pictures/","");
						devimage.on('click',function(e){
						var dlg_height=($(window).innerHeight() > devimage[0].naturalHeight)?devimage[0].naturalHeight+35:$(window).innerHeight();
						var dlg_width=($(window).innerWidth() > devimage[0].naturalWidth)?devimage[0].naturalWidth+35:$(window).innerWidth();
							var closeme=$('<div>').append($(e.currentTarget).clone()).dialog({
								dialogClass: 'imagepreview',
								height: dlg_height,
								width: dlg_width,
								modal: true,
								open: function(){
									$('.ui-widget-overlay').bind('click',function(){
										closeme.dialog('close');
									});
								}
							});
						});
						row[props[i]].html(devimage);
					}
				}
			}
			tbl_results.append(row);
			// If dev isn't defined we just used this to make a header row, stop processing
			if(typeof dev=='undefined'){
				return false;
			}

			// Extend the table row by one field for a button to create/sync/push this template
			row['command']=$('<div>').appendTo(row);
			var btn_command=$('<button>').text('pull').appendTo(row['command']);

			// Bind a click event to the button
			btn_command.on('click',function(e){
				$.ajax({
					type: 'put',
					url: 'https://repository.opendcim.org/api/templatealt',
					async: false,
					dataType: "JSON",
					headers: {
						'APIKey':window.APIKey,
						'UserID':window.UserID
					},
					data: {
						template: row.data("localdev"), 
						templateports: row.data("localdataports"), 
						templatepowerports: row.data("localpowerports"),
						slots: row.data("localslots")
					},
					success: function(data){
						if(!data.error){
							var filedata;
							// make these easier to reference
							var fp=row.FrontPictureFile.find('img').data('file');
							var rp=row.RearPictureFile.find('img').data('file');

							filedata=new FormData();
							if(typeof fp!='undefined'){
								filedata.append("front",fp,fp.name);
							}
							if(typeof rp!='undefined'){
								filedata.append("rear",rp,rp.name);
							}
							var request = new XMLHttpRequest();
							request.open("POST", "https://repository.opendcim.org/api/template/addpictures/"+data.template.RequestID);
							request.setRequestHeader("APIKey",window.APIKey );
							request.setRequestHeader("UserID",window.UserID );
							request.send(filedata);
						}
					},
					error: function(data){
					}
				});
			});

			// Make a dialog to show how the ports are named
			row['DataPorts']=$('<div>').addClass('hiddenports');
			row.NumPorts.click(function(){
				row['DataPorts'].dialog({
					width: 350,
					modal: true,
					open: function(){
						$('.ui-widget-overlay').bind('click',function(){
							row['DataPorts'].dialog('close');
						});
					}
				});
			});

			// Store the ports at the row level for easy access later
			(type=='GlobalID')?row.data('globaldataports',dev.ports):row.data('localdataports',dev.ports);
			// Add the data ports table to the dialog made above
			MakeDataPortsTable(dev.ports,row,'Repository');

			// Call page resize function since we just inserted something to the dom
			resize();

			return row;
		}

		function MakeDataPortsTable(ports,insertTarget,label){
			// Make a table to embed in the dialog we established above 
			var tbl_dataports=$('<div>').addClass('table');
			insertTarget['DataPorts'].append(tbl_dataports);
			// wrap the table with another div so we can do an inline-block
			tbl_dataports.wrap('<div></div>');
			var portheader=$('<div>');
			$('<div>').text('PortNumber').appendTo(portheader);
			$('<div>').text('Label').appendTo(portheader);
			tbl_dataports.append(portheader);
			$('<div>').text(label).addClass('caption').appendTo(tbl_dataports);
			if(typeof ports!='undefined'){
				for(var i in ports){	
					var portrow=$('<div>');
					$('<div>').text(ports[i].PortNumber).appendTo(portrow);
					$('<div>').text(ports[i].Label).appendTo(portrow);
					tbl_dataports.append(portrow);
				}
			}
		}

		function PullLocalTemplates(){
			$.get('api/v1/devicetemplate?ManufacturerID='+$('#slct_ManufacturerID').val()).done(function(data){
				if(!data.error){
					for(var i in data.devicetemplate){
						if(data.devicetemplate[i].GlobalID>0){
							var row;
							// compare to existing template to see if anything has changed
							for(var p in props=['Model','Height','Weight','Wattage','DeviceType','PSCount','NumPorts','ChassisSlots','RearChassisSlots']){
								row=$('.GlobalID'+data.devicetemplate[i].GlobalID).data('object');
								if(row[props[p]].text()!=data.devicetemplate[i][props[p]]){
									row.addClass('change');
									row[props[p]].addClass('diff').prop('title',data.devicetemplate[i][props[p]]);
									var btn_command=row['command'].find('button');
									btn_command.text('sync');
								}
							}
							// Store the template at the row level so we have easy access later
							row.data('localdev',data.devicetemplate[i]);
							$.get('api/v1/devicetemplate/'+data.devicetemplate[i].TemplateID+'/dataports').done(function(data){
								row.data('localdataports',data.dataports);
								MakeDataPortsTable(data.dataports,row,'Local');
							});
							$.get('api/v1/devicetemplate/'+data.devicetemplate[i].TemplateID+'/powerports').done(function(data){
								row.data('localpowerports',data.powerports);
//								MakeDataPortsTable(data.dataports,row,'Local');
							});
						}else{
							// compare to existing templates that might match, or add a new row
							var row=MakeRow(data.devicetemplate[i],'LocalID');
							if(data.devicetemplate[i].NumPorts>0){
								$.ajax({url: 'api/v1/devicetemplate/'+data.devicetemplate[i].TemplateID+'/dataports', type:'get',async: false}).done(function(data){
									row.data('localdataports',data.dataports);
									MakeDataPortsTable(data.dataports,row,'Local');
								});
							}
							if(data.devicetemplate[i].PSCount>0){
								$.ajax({url: 'api/v1/devicetemplate/'+data.devicetemplate[i].TemplateID+'/powerports', type:'get',async: false}).done(function(data){
									row.data('localpowerports',data.powerports);
	//								MakeDataPortsTable(data.dataports,row,'Local');
								});
							}
							if(data.devicetemplate[i].ChassisSlots>0 || data.devicetemplate[i].RearChassisSlots>0){
								$.ajax({url: 'api/v1/devicetemplate/'+data.devicetemplate[i].TemplateID+'/slots', type:'get',async: false}).done(function(data){
									// Make these into a single list and a standard array
									var frontslots=(typeof data.slots[0]!='undefined')?data.slots[0]:{};
									var rearslots=(typeof data.slots[1]!='undefined')?data.slots[1]:{};
									frontslots=Object.keys(frontslots).map(function (key) {return frontslots[key]});
									rearslots=Object.keys(rearslots).map(function (key) {return rearslots[key]});
									row.data('localslots', frontslots.concat(rearslots));
	//								MakeDataPortsTable(data.dataports,row,'Local');
								});
							}
							var btn_command=row['command'].find('button');
							btn_command.text('push');
						}
					}
				}
			});
		}

		function PullGlobalTemplates(e){
			div_results.insertAfter(select_manufacturerid);
			div_results.append(tbl_results);
			tbl_results.html('');
			$.get('https://repository.opendcim.org/api/template/bymanufacturer/'+arr_localmanf[e.currentTarget.value]).done(function(data){
				if(!data.error){
					MakeRow();
					for(var i in data.templates){
						MakeRow(data.templates[i]);
					}
				}
			}).then(PullLocalTemplates);
		}

		$('#btn_pull_templates').click(function(){
			// reset the select list
			select_manufacturerid.html('').append($('<option>').val(0));
			$.get('api/v1/manufacturer').done(function(data){
				if(data.error){
					alert('api error');
				}else{
					for(var i in data.manufacturer){
						var option=$('<option>').val(data.manufacturer[i].ManufacturerID).text(data.manufacturer[i].Name);
						arr_localmanf[data.manufacturer[i].ManufacturerID]=data.manufacturer[i].GlobalID;
						if(data.manufacturer[i].GlobalID==0){
							option.prop({'disabled':true,'title':'Missing GlobalID'}).addClass('diff');
						}
						select_manufacturerid.append(option);
					}
					$('.main > div.center').html('').prepend(select_manufacturerid);
				}
			});
		});


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


		$('#btn_syncrepo').click(BuildTable);
	});


	$.widget( "opendcim.mrow", {
		_create: function(){
			var row=this;

			this.local=this.element.data('local');
			this.id=this.element.find('div:nth-child(1)');
			this.name=this.element.find('div:nth-child(2)');
			this.gid=this.element.find('div:nth-child(3)');
			this.button=this.element.find('div:nth-child(4) > button');

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
						var nameorid=(row.local.ManufacturerID==0)?row.local.Name:row.local.ManufacturerID;
						var postorput=(row.local.ManufacturerID==0)?'put':'post';

						// Update local record with data from master
						$.ajax({
							type: postorput,
							url: 'api/v1/manufacturer/'+nameorid,
							async: false,
							data: {ManufacturerID:row.local.ManufacturerID,GlobalID:data.manufacturers[0].ManufacturerID,Name:data.manufacturers[0].Name},
							success: function(data){
								// Update the screen with the new data 
								row.local.ManufacturerID=data.ManufacturerID;
								row.local.GlobalID=data.GlobalID;
								row.local.Name=data.Name;
								row.BuildRow();
								row.button.hide();
								row.element.removeClass('change');
							}
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
		var table=$('<div>').addClass('table border').prop('id','mfglist');
		var header={ManufacturerID:'id',Name:'name',GlobalID:'gid'};
		table.append(BuildRow(header));

		var ll=GetLocalList();
		for(var i in ll){
			table.append(BuildRow(ll[i]));
		}
//findme
		$('.main > div.center').html(table);
		$('#using').remove();

		// Check against master list
		MashLists();
	}

	function MashLists(){
		var ml=GetMasterList();
		var pl=GetPendingList();
		if($('.main h3').text().length == 0){
			$('.main > .center > .table > div:first-child ~ div').each(function(){
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
			var gm={ManufacturerID:0,Name:ml[i].Name,GlobalID:ml[i].ManufacturerID};
			$('.main > .center > .table').append(BuildRow(gm)).find('div:last-child > div:last-child').removeClass('hide').find('button').text('Pull from master').data('action','pull');
		}
	}

	function BuildRow(manf){
		var row=$('<div>').data("local",manf);
		row.id=$('<div>').text(manf.ManufacturerID);
		row.name=$('<div>').text(manf.Name);
		row.gid=$('<div>').text(manf.GlobalID);
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
			url: 'api/v1/manufacturer',
			dataType: 'json',
			async: false,
			success: function(data){
				if(!data.error){
					ll = data.manufacturer;
				}
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
		function DeleteNow(manufacturerid){
			// If manufacturerid unset then just delete 
			transferto=(typeof(manufacturerid)=='undefined')?0:manufacturerid;
			$.post('',{ManufacturerID: $('#ManufacturerID').val(), TransferTo: transferto, action: 'Delete'},function(data){
				if(data){
					location.href='';
				}else{
					alert("Something's gone horrible wrong");
				}
			});
		}

		// if there aren't any templates using this manufacturer just delete it.
		if(parseInt(UpdateCount())){
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
									DeleteNow($('#copy').val());
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
		}else{
			DeleteNow();
		}
	}



  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<button type="button" id="btn_pull_templates">Get Template List</button>
<button type="button" id="btn_syncrepo">Sync Manufacturers</button>
<div class="center"><div>




<!-- CONTENT GOES HERE -->



</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
