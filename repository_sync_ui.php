<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
	$subheader=__("Data Center Operations Metrics");

// AJAX Start
	// Return JSON array indexed by templateid that contains the last date and time it was modified
	if(isset($_POST['getModifiedTimes'])){
		$results=array();

		$sql='SELECT ObjectID, MAX(Time) AS Time FROM fac_GenericLog WHERE Class="DeviceTemplate" GROUP BY ObjectID;';
		foreach($dbh->query($sql) as $row){
			$results[$row['ObjectID']]=$row['Time'];
		}

		header('Content-Type: application/json');
		echo json_encode($results);
		exit;
	}

// AJAX End

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
	.imagegoeshere { position: relative; }
	.slotcover { position: absolute; border: 1px solid; border-color: rgba(255,0,0,0.5); }
	.slotcover span { background-color: white; border: 1px solid black; padding: 1px; }

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

	var arr_localmanf=new Array();
	var arr_localmodified=new Array();

	$(document).ready(function(){
		var select_manufacturerid=$('<select>').prop({'id':'slct_ManufacturerID'});
		var div_results=$('<div>').prop({'id':'results'});
		var tbl_results=$('<div>').addClass('table');

		$.post('',{'getModifiedTimes':''},function(data){
			arr_localmodified=data;
		});

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

					// Get the local LastModified time from the logging data
					var localvalue=(typeof dev[props[i]]=='undefined' && props[i]=='LastModified')?arr_localmodified[dev['TemplateID']]:dev[props[i]];

					var cellcontent=$('<div>').text(localvalue).appendTo(row);
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

			// Make the template name into a link for easy reference
			if(type=="LocalID"){
				row['Model'].html('<a href="device_templates.php?TemplateID='+dev.TemplateID+'" target="template">'+row['Model'].text()+'</a>');
			}

			// Extend the table row by one field for a button to create/sync/push this template
			row['command']=$('<div>').appendTo(row);
			// Create button for btn_command
			$('<button>').text('pull').appendTo(row['command']);

			// Make a dialog to show how the data ports are named
			row['DataPorts']=$('<div>').addClass('hiddenports');
			row.NumPorts.click(function(e){
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

			// Make a dialog to show how the power ports are named
			row['PowerPorts']=$('<div>').addClass('hiddenports');
			row.PSCount.click(function(e){
				row['PowerPorts'].dialog({
					width: 350,
					modal: true,
					open: function(){
						$('.ui-widget-overlay').bind('click',function(){
							row['PowerPorts'].dialog('close');
						});
					}
				});
			});

			// Make a dialog to show how the slots are laid out
			row['Front']=$('<div>').addClass('hiddenports');
			row.ChassisSlots.click(function(e){
				row['Front'].dialog({
					width: 740,
					modal: true,
					open: function(){
						$('.ui-widget-overlay').bind('click',function(){
							row['Front'].dialog('close');
						});

						// clone the current device image
						row['Front'].find('.imagegoeshere').each(function(){
							var img=row['FrontPictureFile'].find('img').clone().css('width','400px');
							$(this).html(img);
							// stupid browser won't give a size until the image loads
							img.on('load',function(e){
								var ratio=img[0].width/img[0].naturalWidth;
								var table=img.parent('div').next('div').find('.table');
								table.find('> div:nth-child(2) ~ div').each(function(e){
									var num=this.childNodes[0].textContent;
									var x=parseInt(this.childNodes[1].textContent);
									var y=parseInt(this.childNodes[2].textContent);
									var w=parseInt(this.childNodes[3].textContent);
									var h=parseInt(this.childNodes[4].textContent);
									var slotcover=$('<div>').css({
										'left':x*ratio+'px',
										'top':y*ratio+'px',
										'width':w*ratio+'px',
										'height':h*ratio+'px'
									}).addClass('slotcover');
									slotcover.append($('<span>').text(num));
									img.parent('div').append(slotcover);
									// add a mouseover for this to highlight slotcover
									$(this).on('mouseover',function(e){
										slotcover.effect("highlight",{color:"#ff0000"},1000,false);
									});
								});
							});
						});


					}
				});
			});

			// Make a dialog to show how the slots are laid out
			row['Rear']=$('<div>').addClass('hiddenports');
			row.RearChassisSlots.click(function(e){
				row['Rear'].dialog({
					width: 740,
					modal: true,
					open: function(){
						$('.ui-widget-overlay').bind('click',function(){
							row['Front'].dialog('close');
						});

						// clone the current device image
						row['Rear'].find('.imagegoeshere').each(function(){
							var img=row['RearPictureFile'].find('img').clone().css('width','400px');
							$(this).html(img);
							// stupid browser won't give a size until the image loads
							img.on('load',function(e){
								var ratio=img[0].width/img[0].naturalWidth;
								var table=img.parent('div').next('div').find('.table');
								table.find('> div:nth-child(2) ~ div').each(function(e){
									var num=this.childNodes[0].textContent;
									var x=parseInt(this.childNodes[1].textContent);
									var y=parseInt(this.childNodes[2].textContent);
									var w=parseInt(this.childNodes[3].textContent);
									var h=parseInt(this.childNodes[4].textContent);
									var slotcover=$('<div>').css({
										'left':x*ratio+'px',
										'top':y*ratio+'px',
										'width':w*ratio+'px',
										'height':h*ratio+'px'
									}).addClass('slotcover');
									slotcover.append($('<span>').text(num));
									img.parent('div').append(slotcover);
									// add a mouseover for this to highlight slotcover
									$(this).on('mouseover',function(e){
										slotcover.effect("highlight",{color:"#ff0000"},1000,false);
									});
								});
							});
						});
					}
				});
			});

			// Store the ports at the row level for easy access later
			(type=='GlobalID')?row.data('globaldataports',dev.ports):row.data('localdataports',dev.ports);
			(type=='GlobalID')?row.data('globalpowerports',dev.powerports):row.data('localpowerports',dev.powerports);
			(type=='GlobalID')?row.data('globalslots',dev.slots):row.data('localslots',dev.slots);
			// Add the data ports table to the dialog made above
			MakePortsTable(dev.ports,row,'Repository','data');
			MakePortsTable(dev.powerports,row,'Repository','power');
			MakeSlotsTable(dev.slots,row,'Repository','front');
			MakeSlotsTable(dev.slots,row,'Repository','rear');

			// Call page resize function since we just inserted something to the dom
			resize();

			return row;
		}

		// Function to sync current global template with local api
		function pulltoapi(row){
			var postorput=(typeof row.data('localdev')=='undefined')?'put':'post';
			var nameorid=(typeof row.data('localdev')=='undefined')?row.data('globaldev').Model:row.data('localdev').TemplateID;
<?php
		// This code is ONLY added to the javascript if the local site is set for metric
		// otherwise there is no mangling to worry about
		if ( $config->ParameterArray["mUnits"] != "english" ) {
			echo "row.data('globaldev').Weight = row.data('globaldev').Weight / 2.2;";
		}
?>
			// Move data off the globaldev object into something we can parse easier
			var ports=row.data("globaldev").ports;
			var powerports=row.data("globaldev").powerports;
			var slots=row.data("globaldev").slots;

			// Check for dolemite's nesting and undo it
			// Is this a CDU?
			if(typeof row.data("globaldev").cdutemplate!='undefined'){
				var dev=row.data("globaldev");
				for(var i in dev.cdutemplate){
					dev[i]=dev.cdutemplate[i];
				}
				delete dev.cdutemplate;
			}

			// Is this a Sensor?
			if(typeof row.data("globaldev").sensortemplate!='undefined'){
				var dev=row.data("globaldev");
				for(var i in dev.sensortemplate){
					dev[i]=dev.sensortemplate[i];
				}
				delete dev.sensor;
			}

			// Are there any dataports defined?
			if(typeof row.data("globaldev").ports!='undefined'){
				delete row.data("globaldev").ports;
			}

			// Are there any power ports defined?
			if(typeof row.data("globaldev").powerports!='undefined'){
				delete row.data("globaldev").powerports;
			}

			// Are there any slots defined?
			if(typeof row.data("globaldev").slots!='undefined'){
				delete row.data("globaldev").slots;
			}

			// Add the front file name
			if(row.data("globaldev").FrontPictureFile.search('http')!='-1'){
				row.data("globaldev").FrontPictureFile=row.data("globaldev").FrontPictureFile.split('/').pop();
			}

			// Alter the rear file name
			if(row.data("globaldev").RearPictureFile.search('http')!='-1'){
				row.data("globaldev").RearPictureFile=row.data("globaldev").RearPictureFile.split('/').pop();
			}

			// Set the global id
			row.data("globaldev").GlobalID=row.data("globaldev").TemplateID;

			// Set the templateid back to the local value
			if(postorput==='post'){
				row.data("globaldev").TemplateID=row.data("localdev").TemplateID;
			}

			// Find the local manufacturer id from the global value
			for(var key in arr_localmanf){
				if(arr_localmanf[key] === row.data("globaldev").ManufacturerID){
					row.data("globaldev").ManufacturerID=key;
					// Prevent further matches
					break;
				}
			}

			// Slice and dice the image data, if it is set
			function AddImage(imageURL){
				var uploadform = new FormData();
				uploadform.append("dir", "pictures");
				uploadform.append("filename", imageURL.name);
				uploadform.append("token", token);
				uploadform.append("timestamp", now);
				uploadform.append("Filedata",imageURL,imageURL.name);
				var request = new XMLHttpRequest();
				request.open("POST", "scripts/uploadifive.php");
				request.send(uploadform);
			}

			$.ajax({
				type: postorput,
				url: 'api/v1/devicetemplate/'+nameorid,
				async: false,
				dataType: "JSON",
				data: row.data("globaldev"),
				success: function(data){
					if(!data.error){
						// Template has been created now we need to make the extra bits
						// Ports, slots, pictures, etc

						if(postorput==='put'){
							// Linkify the template name
							row.data('object')['Model'].html('<a href="device_templates.php?TemplateID='+data.devicetemplate.TemplateID+'" target="template">'+row.data('object')['Model'].text()+'</a>');

							// Add front image
							if(row.data("globaldev").FrontPictureFile!=''){
								AddImage(row.FrontPictureFile.find('img').data('file'));
							}

							// Add rear image
							if(row.data("globaldev").RearPictureFile!=''){
								AddImage(row.data("object").RearPictureFile.find('img').data('file'));
							}

							// Create ports
							for(var i in row.data("globaldataports")){
								$.ajax({type: postorput,url: 'api/v1/devicetemplate/'+data.devicetemplate.TemplateID+'/dataport/'+(parseInt(i)+1),async: false,data: row.data("globaldataports")[i]}).complete(function(data){});
							}
							for(var i in row.data("globalpowerports")){
								$.ajax({type: postorput,url: 'api/v1/devicetemplate/'+data.devicetemplate.TemplateID+'/powerport/'+(parseInt(i)+1),async: false,data: row.data("globalpowerports")[i]}).complete(function(data){});
							}

							// Create slots
							for(var i in row.data("globalslots")){
								$.ajax({type: postorput,url: 'api/v1/devicetemplate/'+data.devicetemplate.TemplateID+'/slot/'+(parseInt(i)+1),async: false,data: row.data("globalslots")[i]}).complete(function(data){});
							}
						}else{
							row.removeClass('change');
						}

						var btn_command=row.data('object').command.find('button');
						btn_command.hide();
					}
				}
			});
		}

		// Function to send current template to the repository
		function pushtorepo(row){
<?php
		// Do the Metric->English conversion if the local site is configured for Metric
		if ( $config->ParameterArray["mUnits"] != "english" ) {
			echo "row.data('localdev').Weight=row.data('localdev').Weight*2.2;";
		}
?>
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

						var btn_command=row.data('object').command.find('button');
						btn_command.hide();
					}
				},
				error: function(data){
				}
			});
		}

		/*
		 * Simple function to generate a table to display the port names
		 *
		 * ports: array of ports
		 * insertTarget: jquery row object
		 * label: Local or Repository
		 * type: data or power
		 */

		function MakePortsTable(ports,insertTarget,label,type){
			// Make a table to embed in the dialog we established above 
			var tbl_dataports=$('<div>').addClass('table');
			var porttype=(type=='data')?'DataPorts':'PowerPorts';
			insertTarget[porttype].append(tbl_dataports);
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

		/*
		 * Simple function to generate a table to display the device image
		 * and a small table of the slot coordinates
		 *
		 * slotss: array of slots
		 * insertTarget: jquery row object
		 * label: Local or Repository
		 * type: front or rear
		 */

		function MakeSlotsTable(slots,insertTarget,label,type){
			// Make a table to embed in the dialog we established above 
			var tbl_slots=$('<div>').addClass('table');
			var slottype=(type=='front')?'Front':'Rear';
			insertTarget[slottype].append(tbl_slots);
			var imgholder=$('<div>',{'class':'imagegoeshere'}).insertBefore(tbl_slots);
			// wrap the table with another div so we can do an inline-block
			tbl_slots.wrap('<div></div>');
			var linebreak=$('<div>').css('width','100%').addClass('clear');
			insertTarget[slottype].append(linebreak);
			var portheader=$('<div>');
			$('<div>').text('Position').appendTo(portheader);
			$('<div>').text('X').appendTo(portheader);
			$('<div>').text('Y').appendTo(portheader);
			$('<div>').text('W').appendTo(portheader);
			$('<div>').text('H').appendTo(portheader);
			tbl_slots.append(portheader);
			$('<div>').text(label).addClass('caption').appendTo(tbl_slots);
			if(typeof slots!='undefined'){
				for(var i in slots){
					if(slottype=='Front' && slots[i].BackSide==1){
						continue;
					}
					if(slottype=='Rear' && slots[i].BackSide==0){
						continue;
					}
					var slotrow=$('<div>');
					$('<div>').text(slots[i].Position).appendTo(slotrow);
					$('<div>').text(slots[i].X).appendTo(slotrow);
					$('<div>').text(slots[i].Y).appendTo(slotrow);
					$('<div>').text(slots[i].W).appendTo(slotrow);
					$('<div>').text(slots[i].H).appendTo(slotrow);
					tbl_slots.append(slotrow);
				}
			}
		}

		function PullLocalTemplates(){
			$.get('api/v1/devicetemplate?ManufacturerID='+$('#slct_ManufacturerID').val()).done(function(data){
				if(!data.error){
					for(var i in data.devicetemplate){
						if(data.devicetemplate[i].GlobalID>0){
							var row=$('.GlobalID'+data.devicetemplate[i].GlobalID).data('object');
							// compare to existing template to see if anything has changed
							for(var p in props=['Model','Height','Weight','Wattage','DeviceType','PSCount','NumPorts','ChassisSlots','RearChassisSlots']){
								if(row[props[p]].text()!=data.devicetemplate[i][props[p]]){
									row.addClass('change');
									row[props[p]].addClass('diff').prop('title',data.devicetemplate[i][props[p]]);
									var btn_command=row['command'].find('button');
									btn_command.text('sync');
								}
							}

							// check and see if we need to hide the button because the templates are in sync
							if(!row.hasClass('change')){
								row['command'].find('button').hide();
							}	
							// Store the template at the row level so we have easy access later
							row.data('localdev',data.devicetemplate[i]);
							$.ajax({url:'api/v1/devicetemplate/'+data.devicetemplate[i].TemplateID+'/dataport',type:'get',async:false}).done(function(data){
								row.data('localdataports',data.dataport);
								MakePortsTable(data.dataport,row,'Local','data');
							});
							$.ajax({url:'api/v1/devicetemplate/'+data.devicetemplate[i].TemplateID+'/powerport',type:'get',async:false}).done(function(data){
								row.data('localpowerports',data.powerport);
								MakePortsTable(data.powerport,row,'Local','power');
							});
							if(data.devicetemplate[i].ChassisSlots>0 || data.devicetemplate[i].RearChassisSlots>0){
								$.ajax({url: 'api/v1/devicetemplate/'+data.devicetemplate[i].TemplateID+'/slot', type:'get',async: false}).done(function(data){
									// Make these into a single list and a standard array
									var frontslots=(typeof data.slot[0]!='undefined')?data.slot[0]:{};
									var rearslots=(typeof data.slot[1]!='undefined')?data.slot[1]:{};
									frontslots=Object.keys(frontslots).map(function (key) {return frontslots[key]});
									rearslots=Object.keys(rearslots).map(function (key) {return rearslots[key]});
									row.data('localslots', frontslots.concat(rearslots));
									MakeSlotsTable(frontslots.concat(rearslots),row,'Local','front');
									MakeSlotsTable(frontslots.concat(rearslots),row,'Local','rear');
								});
							}
							// Make sure we have a button first, then add the click functionality to it
							if(typeof btn_command!='undefined'){
								btn_command.unbind('click').click(function(e){
									pulltoapi($(e.currentTarget.parentElement.parentElement).data('object'));
								});
							}

							// Make the template name into a link for easy reference
							row['Model'].html('<a href="device_templates.php?TemplateID='+data.devicetemplate[i].TemplateID+'" target="template">'+row['Model'].text()+'</a>');
						}else{
							// compare to existing templates that might match, or add a new row
							var row=MakeRow(data.devicetemplate[i],'LocalID');
							if(data.devicetemplate[i].NumPorts>0){
								$.ajax({url: 'api/v1/devicetemplate/'+data.devicetemplate[i].TemplateID+'/dataport', type:'get',async: false}).done(function(data){
									row.data('localdataports',data.dataport);
									MakePortsTable(data.dataport,row,'Local','data');
								});
							}
							if(data.devicetemplate[i].PSCount>0){
								$.ajax({url: 'api/v1/devicetemplate/'+data.devicetemplate[i].TemplateID+'/powerport', type:'get',async: false}).done(function(data){
									row.data('localpowerports',data.powerport);
									MakePortsTable(data.powerport,row,'Local','power');
								});
							}
							if(data.devicetemplate[i].ChassisSlots>0 || data.devicetemplate[i].RearChassisSlots>0){
								$.ajax({url: 'api/v1/devicetemplate/'+data.devicetemplate[i].TemplateID+'/slot', type:'get',async: false}).done(function(data){
									// Make these into a single list and a standard array
									var frontslots=(typeof data.slot[0]!='undefined')?data.slot[0]:{};
									var rearslots=(typeof data.slot[1]!='undefined')?data.slot[1]:{};
									frontslots=Object.keys(frontslots).map(function (key) {return frontslots[key]});
									rearslots=Object.keys(rearslots).map(function (key) {return rearslots[key]});
									row.data('localslots', frontslots.concat(rearslots));
									MakeSlotsTable(frontslots.concat(rearslots),row,'Local','front');
									MakeSlotsTable(frontslots.concat(rearslots),row,'Local','rear');
								});
							}
							var btn_command=row['command'].find('button');
							if(window.APIKey=="" || window.UserID==""){
								btn_command.hide();
							}else{
								// Bind a click event to the button
								btn_command.text('push').click(function(e){
									pushtorepo($(e.currentTarget.parentElement.parentElement).data('object'));
								});
							}
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
						var row=MakeRow(data.templates[i]);
						var btn_command=row['command'].find('button');
						btn_command.on('click',function(e){
							pulltoapi($(e.currentTarget.parentElement.parentElement).data('object'));
						});
					}
				}
			}).then(PullLocalTemplates);
		}

		$('#btn_pull_templates').click(function(){
			// redefine the select box here so we can redraw it and maintain the change event
			select_manufacturerid=$('<select>').prop({'id':'slct_ManufacturerID'});
			// reset the select list
			select_manufacturerid.html('').append($('<option>').val(0)).on('change',PullGlobalTemplates);
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

						// S.U.T. #4831 - Submit a manufacturer with a / to the api name
						nameorid=encodeURIComponent(nameorid);

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
