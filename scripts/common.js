//Notes render function
function editnotes(button){
	button.val('preview').text('Preview');
	var a=button.next('div');
	button.next('div').remove();
	button.next('textarea').htmlarea({
		toolbar: [
		"link", "unlink", "image"
		],
		css: 'css/jHtmlArea.Editor.css'
	});
	$('.jHtmlArea div iframe').height(a.innerHeight());
}

function rendernotes(button){
	button.val('edit').text('Edit');
	var w=button.next('div').outerWidth();
	var h=$('.jHtmlArea').outerHeight();
	if(h>0){
		h=h+'px';
	}else{
		h="auto";
	}
	$('#notes').htmlarea('dispose');
	button.after('<div id="preview">'+$('#notes').val()+'</div>');
	button.next('div').css({'width': w+'px', 'height' : h}).find('a').each(function(){
		$(this).attr('target', '_new');
	});
	$('#notes').html($('#notes').val()).hide(); // we still need this field to submit it with the form
	h=0; // recalculate height in case they added an image that is gonna hork the layout
	// need a slight delay here to allow the load of large images before the height calculations are done
	setTimeout(function(){
		$('#preview').find("*").each(function(){
			h+=$(this).outerHeight();
		});
		$('#preview').height(h);
	},2000);
}
$(document).ready(function(){
	$('#notes').each(function(){
		$(this).before('<button type="button" id="editbtn"></button>');
		if($(this).val()!=''){
			rendernotes($('#editbtn'));
		}else{
			editnotes($('#editbtn'));
		}
	});

	$('#editbtn').click(function(){
		var button=$(this);
		if($(this).val()=='edit'){
			editnotes(button);
		}else{
			rendernotes(button);
		}
	});
});

// draw arrows on a canvas
function drawArrow(canvas,startx,starty,width,height,direction){
	var arrowW = 0.20 * width;
	var arrowH = 0.70 * height;
    
	switch(direction){
		case 'Top':
			var p1={x: startx+arrowH, y: starty};
			var p2={x: startx+arrowH, y: starty+height-arrowW};
			var p3={x: startx+(arrowH/2), y: starty+height-arrowW};
			var p4={x: startx+(width/2), y: starty+height};
			var p5={x: startx+width-(arrowH/2), y: starty+height-arrowW};
			var p6={x: startx+(width-arrowH), y: starty+height-arrowW};
			var p7={x: startx+(width-arrowH), y: starty};
			break;
		case 'Bottom':
			var p1={x: startx+arrowH, y: starty+height};
			var p2={x: startx+arrowH, y: starty+arrowW};
			var p3={x: startx+(arrowH/2), y: starty+arrowW};
			var p4={x: startx+(width/2), y: starty};
			var p5={x: startx+width-(arrowH/2), y: starty+arrowW};
			var p6={x: startx+(width-arrowH), y: starty+arrowW};
			var p7={x: startx+(width-arrowH), y: starty+height};
			break;
		case 'Right':
			var p1={x: startx+width,  y: starty+(height-arrowH)};
			var p2={x: startx+arrowW, y: starty+(height-arrowH)};
			var p3={x: startx+arrowW, y: starty};
			var p4={x: startx,        y: starty+(height/2)};
			var p5={x: startx+arrowW, y: starty+height};
			var p6={x: startx+arrowW, y: starty+(height-((height-arrowH)))};
			var p7={x: startx+width,  y: starty+(height-((height-arrowH)))};
			break;
		default:
			var p1={x: startx,              y: starty+(height-arrowH)};
			var p2={x: startx+(width-arrowW), y: starty+(height-arrowH)};
			var p3={x: startx+(width-arrowW), y: starty};
			var p4={x: startx+width,          y: starty+(height/2)};
			var p5={x: startx+(width-arrowW), y: starty+height};
			var p6={x: startx+(width-arrowW), y: starty+(height-((height-arrowH)))};
			var p7={x: startx,              y: starty+(height-((height-arrowH)))};
			break;
    }
	canvas.save();

	canvas.globalCompositeOperation="source-over";
	canvas.fillStyle="rgba(255, 0, 0, .35)";
	canvas.strokeStyle="rgba(0, 0, 0, .55)";

	canvas.beginPath();

	canvas.moveTo(p1.x, p1.y);
	canvas.lineTo(p2.x, p2.y); // end of main block
	canvas.lineTo(p3.x, p3.y); // topmost point     
	canvas.lineTo(p4.x, p4.y); // endpoint 
	canvas.lineTo(p5.x, p5.y); // bottommost point 
	canvas.lineTo(p6.x, p6.y); // end at bottom point 
	canvas.lineTo(p7.x, p7.y);

	canvas.closePath();
	canvas.fill();
	canvas.stroke();

	canvas.restore();
}

// Image management
	function makeThumb(path,file){
		var device=$('<div>').css('background-image', 'url("'+path+'/'+file+'")');
		var image=$('<div>').append(device).append($('<div>').addClass('filename').text(file));
		var del=$('<div>').addClass('del').hide();
		del.on('click', function(){
			if(delimage(path,file)){
				image.remove();
			}
		});
		device.on('click', function(){
			$('<div>').append($('<img>').attr('src',path+'/'+file)).attr('title',path+'/'+file).dialog({
				width: 450,
				modal: true
			});
		});

		image.append(del);
		image.mouseover(function(){del.toggle()});
		image.mouseout(function(){del.toggle()});
		return image; 
	}
	function delimage(path,file){
		var exit=1;
		$.post('scripts/check-exists.php',{dir: path, filename: file}).done(function(e){
			if(e==1){
				$.post('scripts/uploadifive.php',{dir: path, filename: file, timestamp : '<?php echo $timestamp;?>', token : '<?php echo $salt;?>'}).done(function(e){
					if(e){
					}else{
						// if the file doesn't delete for some reason return false
						alert("Blarg! File didn't delete");
						exit=0;
					}
				});
			}else{
				alert("File doesn't exist");
			}
		});
		return exit;
	}
	function reload(target){
		$('#'+target).children().remove();
		$.post('',{dir: target}).done(function(a){
			$.each(a,function(dir,files){
				$.each(files,function(i,file){
					$('#'+target).append(makeThumb(dir,file));
				});
			});
		});
	}

// END - Image Management

(function( $ ) {
    $.widget( "opendcim.massedit", {
		_create: function(){
			// mass media type change controls
			setmediatype=$('<select>').append($('<option>'));
			setmediatype.append($('<option>').val('clear').text('Clear'));
			setmediatype.change(function(){
				var dialog=$('<div />', {id: 'modal', title: 'Override all types?'}).html('<div id="modaltext"></div><br><div id="modalstatus" class="warning">Do you want to override all media types?</div>');
				dialog.dialog({
					resizable: false,
					modal: true,
					dialogClass: "no-close",
					buttons: {
						Yes: function(){
							$(this).dialog("destroy");
							doit(true);
						},
						No: function(){
							$(this).dialog("destroy");
							doit(false);
						},
						Cancel: function(){
							$(this).dialog("destroy");
							setmediatype.val('');
						}
					}
				});
				function doit(override){
					// set all the media types to the one selected from the drop down
					$.post('',{
						setall: override, 
						devid: $('#deviceid').val(), 
						mt:setmediatype.val(), 
						cc: setmediatype.data(setmediatype.val())
					}).done(function(data){
						// setall kicked back every port run through them all and update note, media type, and color code
						redrawports(data);
					});
					setmediatype.val('');
				}
			}).css('z-index','3');

			// Populate media type choices
			function massedit_mt(){
				$.get('',{mt:''}).done(function(data){
					$.each(data, function(key,mt){
						var option=$("<option>",({'value':mt.MediaID})).append(mt.MediaType);
						setmediatype.append(option).data(mt.MediaID,mt.ColorID);
					});
				});
				$('#mt').append(setmediatype);
			}

			// color codes change controls
			setcolorcode=$('<select>').append($('<option>'));
			setcolorcode.append($('<option>').val('clear').text('Clear'));
			setcolorcode.change(function(){
				var dialog=$('<div />', {id: 'modal', title: 'Override all types?'}).html('<div id="modaltext"></div><br><div id="modalstatus" class="warning">Do you want to override all the color codes?</div>');
				dialog.dialog({
					resizable: false,
					modal: true,
					dialogClass: "no-close",
					buttons: {
						Yes: function(){
							$(this).dialog("destroy");
							doit(true);
						},
						No: function(){
							$(this).dialog("destroy");
							doit(false);
						},
						Cancel: function(){
							$(this).dialog("destroy");
							setcolorcode.val('');
						}
					}
				});
				function doit(override){
					// set all the color codes to the one selected from the drop down
					$.post('',{
						setall: override,
						devid: $('#deviceid').val(),
						cc: setcolorcode.val()
					}).done(function(data){
						// setall kicked back every port run through them all and update note, media type, and color code
						redrawports(data);
					});
					setcolorcode.val('');
				}
			});

			// Populate color code choices
			function massedit_cc(){
				$.get('',{cc:''}).done(function(data){
					$.each(data, function(key,cc){
						var option=$("<option>",({'value':cc.ColorID})).append(cc.Name);
						setcolorcode.append(option).data(cc.ColorID,cc.Name);
					});
				});
				$('#cc').append(setcolorcode);
			}

			// port name generation change controls
			generateportnames=$('<select>').append($('<option>'));
			generateportnames.change(function(){
				var dialog=$('<div />', {id: 'modal', title: 'Override all names?'}).html('<div id="modaltext"></div><br><div id="modalstatus" class="warning">Do you want to override all the port names?</div>');
				if($(this).val()=='Custom'){
					dialog.find('#modalstatus').prepend('<p>Custom pattern: <input></input></p><p><a href="http://opendcim.org/wiki/index.php?title=NetworkConnections#Custom_Port_Name_Generator_Example_Patterns" target=_blank>Pattern Examples</a></p>');
				}
				dialog.dialog({
					resizable: false,
					modal: true,
					dialogClass: "no-close",
					buttons: {
						Yes: function(){
							doit(true);
							$(this).dialog("destroy");
						},
						No: function(){
							doit(false);
							$(this).dialog("destroy");
						},
						Cancel: function(){
							$(this).dialog("destroy");
							generateportnames.val('');
						}
					}
				});
				function doit(override){
					var portpattern;
					portpattern=(generateportnames.val()=='Custom')?dialog.find('input').val():generateportnames.val();
					// gnerate port names based on the selected pattern for all the ports
					$.post('',{setall: override, devid: $('#deviceid').val(), spn: portpattern}).done(function(data){
						// setall kicked back every port run through them all and update note, media type, and color code
						redrawports(data);
					});
					generateportnames.val('');
				}
			});

			// Populate name generation choices
			function massedit_pn(){
				$.get('',{spn:''}).done(function(data){
					$.each(data, function(key,spn){
						var option=$("<option>",({'value':spn.Pattern})).append(spn.Pattern.replace('(1)','x'));
						generateportnames.append(option);
					});
				});
				$('#spn,#pp').append(generateportnames.css('z-index','4'));
			}


			// get list of other patch panels
			rearedit=$('<select>').append($('<option>'));
			rearedit.append($('<option>').val('clear').text('Clear Connections'));
			rearedit.change(function(){
				var dialog=$('<div />', {id: 'modal', title: 'Override all connections?'}).html('<div id="modaltext"></div><br><div id="modalstatus" class="warning"><h2>WARNING: This will detach any existing connections on the other device as well</h2>Do you want to override existing connections?</div>');
				dialog.dialog({
					resizable: false,
					modal: true,
					dialogClass: "no-close",
					buttons: {
						Yes: function(){
							$(this).dialog("destroy");
							doit(true);
						},
						No: function(){
							$(this).dialog("destroy");
							doit(false);
						},
						Cancel: function(){
							$(this).dialog("destroy");
							rearedit.val('');
						}
					}
				});
				function doit(override){
					var modal=$('<div />', {id: 'modal', title: 'Please wait...'}).html('<div id="modaltext"><img src="images/connectcable.gif" style="width: 100%;"><br>Connecting ports...</div><br><div id="modalstatus" class="warning"></div>').dialog({
						appendTo: 'body',
						minWidth: 500,
						closeOnEscape: false,
						dialogClass: "no-close",
						modal: true
					});
					$.post('',{swdev: $('#deviceid').val(), rear: '', cdevice: rearedit.val(), override: override}).done(function(data){
						modal.dialog('destroy');
						$.each($.parseJSON(data), function(key,pp){
							rear=(key>0)?false:true;
							fr=(rear)?'r':'f';
							pp.ConnectedDeviceLabel=(pp.ConnectedDeviceLabel==null)?'':pp.ConnectedDeviceLabel;
							pp.ConnectedPortLabel=(pp.ConnectedPortLabel==null || pp.ConnectedPortLabel=='')?(pp.ConnectedPort==null)?'':Math.abs(pp.ConnectedPort):pp.ConnectedPortLabel;
							var dev=$('<a>').prop('href','devices.php?deviceid='+pp.ConnectedDeviceID).text(pp.ConnectedDeviceLabel);
							var port=$('<a>').prop('href','paths.php?deviceid='+pp.ConnectedDeviceID+'&portnumber='+pp.ConnectedPort).text(pp.ConnectedPortLabel);
							$('#'+fr+'d'+Math.abs(key)).html(dev).data('default',pp.ConnectedDeviceID);
							$('#'+fr+'p'+Math.abs(key)).html(port).data('default',pp.ConnectedPort);
							$('#'+fr+'n'+Math.abs(key)).text(pp.Notes).data('default',pp.Notes);
						});
					});
					rearedit.val('');
				}
			});

			// Generate list of other patch panel devices that can be used for rear connections
			function massedit_rear(){
				if(portrights.admin){
					$.post('', {swdev: $('#deviceid').val(), rear: ''}, function(data){
						$.each(data, function(key,pp){
							var option=$("<option>").val(pp.DeviceID).append(pp.Label);
							rearedit.append(option);
							var rack=$('#datacenters a[href$="cabinetid='+pp.CabinetID+'"]');
							option.prepend('['+rack.text()+'] ');
						});
					});
					$('#rear').append(rearedit);
				}
			}

			// this will redraw the current ports based on the information given back from a json string
			function redrawports(portsarr){
				$.each(portsarr.ports, function(key,p){
					var row=($('#pp'+p.PortNumber).parent('div').length)?$('#pp'+p.PortNumber).parent('div'):($('#sp'+p.PortNumber).parent('div'))?$('#sp'+p.PortNumber).parent('div'):0;
					row.row('destroy');
				});
			}

			// Add controls the page
			massedit_mt();
			massedit_cc();
			massedit_pn();
			massedit_rear();

			// Nest the mass edit buttons inside of divs so they won't have to moved around
			$(this.element).find('div:first-child > div + div[id]').wrapInner($('<div>'));
		},

		hide: function(){
			this.element.find('div:first-child select').hide();
		},

		show: function(){
			var edit=true;
			$('.switch.table > div ~ div, .patchpanel.table > div ~ div').each(function(){
				edit=($(this).data('edit'))?false:edit;
			});
			if(edit){
				this.element.find('div:first-child select').show();
			}
		}
	});

	// Network Connections Management
	$.widget( "opendcim.row", {
		_create: function() {
			var row=this;

			// Determine if we are looking at a patch panel or a device
			var pp=this.element.find('div[id^="pp"]');
			var ct=(pp.length==0)?this.element.find('div[id^="sp"]:not([id^="spn"])'):pp;
			ct.css({'text-decoration':'underline','cursor':'pointer'});

			// Create a button to delete the row if the number of ports on a device is 
			// decreased
			var del=$('<div>').addClass('delete').append($('<span>').addClass('ui-icon status down')).hide();
			this.element.prepend(del);

			// Define all the ports we might need later
			this.portnum     = this.element.data('port');
			this.portname    = this.element.find('div[id^=spn],div[id^=pp]');
			this.cdevice     = this.element.find('div[id^=d]:not([id^=dp]),div[id^=fd]');
			this.cdeviceport = this.element.find('div[id^=dp],div[id^=fp]');
			this.cnotes      = this.element.find('div[id^=n],div[id^=fn]');
			this.rdevice     = this.element.find('div[id^=rd]');
			this.rdeviceport = this.element.find('div[id^=rp]');
			this.rnotes      = this.element.find('div[id^=rn]');
			this.porttype    = this.element.find('div[id^=mt]');
			this.portcolor   = this.element.find('div[id^=cc]');

			// Create a blank row for controls on a patch panel
			this.btnrow      = $('<div>');
			for(var a=0; a < this.element[0].children.length; a++){
				this.btnrow.append($('<div>'));
			}
			this.btnrow.find('div:first-child').addClass('delete').hide();
			this.btnrow.front = this.btnrow.find('div:nth-child(2)');
			this.btnrow.rear  = this.btnrow.find('div:nth-child('+(Math.ceil(this.element[0].children.length/2)+2)+')');

			// Row Controls
			var controls=$('<div>',({'id':'controls'+this.portnum}));
			var savebtn=$('<button>',{'type':'button'}).append('Save').on('click',function(e){row.save(e)});
			var cancelbtn=$('<button>',{'type':'button'}).append('Cancel').on('click',function(e){row.checkredraw(e)});
			var deletebtn=$('<button>',{'type':'button'}).append('Delete').on('click',function(e){row.delete(e)});
			controls.append(savebtn).append(cancelbtn).append(deletebtn);
			var minwidth=0;
			controls.children('button').each(function(){
				minwidth+=$(this).outerWidth()+14; // 14 padding and border
			});
			controls.css('min-width',minwidth);

			this.controls	= controls;

			// Bind edit event to the click target
			ct.click(function(e){
				if(!row.element.data('edit')){
					row.edit();
				}
			});

			// Bind popup event to the device port for pathing
			row.showpath();
		
		},
		edit: function() {
			var row=this;
			row.getdevices(this.cdevice);
			row.cnotes.html('<input type="text" style="min-width: 200px;" value="'+row.cnotes.text()+'">');
			row.portname.html('<input type="text" style="min-width: 60px;" value="'+row.portname.text()+'">');
			row.getmediatypes();
			row.getcolortypes();

			// rear panel edit
			if(portrights.admin){
				row.getdevices(this.rdevice);
				row.rnotes.html('<input type="text" style="min-width: 200px;" value="'+row.rnotes.text()+'">');
			}

			row.element.data('edit',true);

			// Adjust the spacer if the delete row option has been triggered
			if(this.element.find('.delete:visible').length){
				this.btnrow.find('.delete').show();
			}else{
				this.btnrow.find('.delete').hide();
			}

			if($(this.element[0]).parent().hasClass('patchpanel')){
				this.btnrow.front.append(this.controls.clone(true).data('rear',false));
				if(portrights.admin){
					this.btnrow.rear.append(this.controls.clone(true).data('rear',true));
				}
				this.element.after(this.btnrow);
			}else if($(this.element[0]).parent().hasClass('switch')){
				this.portcolor.after(this.controls.clone(true));
			}

			this.element.children('div ~ div:not([id^=st])').css({'padding': '0px', 'background-color': 'transparent'});
			setTimeout(function() {
				resize();
			},200);
			// Hide mass edit controls
			$('.switch.table, .patchpanel.table').massedit('hide');
		},

		getdevices: function(target){
			var row=this;
			var postoptions={swdev: $('#deviceid').val(),pn: this.portnum};
			if(target===this.rdevice){
				postoptions=$.extend(postoptions,{rear: ''});
			}
			$.post('',postoptions).done(function(data){
				var devlist=$("<select>").append('<option value=0>&nbsp;</option>');
				devlist.change(function(e){
					row.getports(e);
				});

				$.each(data, function(devid,device){
					devlist.append('<option value='+device.DeviceID+'>'+device.Label+'</option>');
				});
				target.html(devlist).find('select').val(target.data('default'));
				devlist.combobox();
				devlist.change();
			});

		},

		getports: function(e){
			var row=this;
			var rear=(e.target.parentElement!=null)?(e.target.parentElement.id.indexOf('r')==0)?true:false:false;
			var postoptions={swdev: $('#deviceid').val(),listports: ''};
			if(rear){
				postoptions=$.extend(postoptions, {thisdev: this.rdevice.find('select').val(), pn: (this.portnum)*-1});
			}else{
				postoptions=$.extend(postoptions, {thisdev: this.cdevice.find('select').val(), pn: this.portnum});
			}
			
			$.post('',postoptions).done(function(data){
				var portlist=$("<select>");
				$.each(data, function(key,port){
					// If no label is specified use the absolute value of the port number
					port.Label=(port.Label=="")?Math.abs(port.PortNumber):port.Label;

					// only allow positive values
					if(rear){
						if(port.PortNumber<0){
							portlist.prepend('<option value='+port.PortNumber+'>'+port.Label+'</option>');
							portlist.data(port.PortNumber, {MediaID: port.MediaID, ColorID: port.ColorID});
						}
					}else{
						if(port.PortNumber>0){
							portlist.append('<option value='+port.PortNumber+'>'+port.Label+'</option>');
							portlist.data(port.PortNumber, {MediaID: port.MediaID, ColorID: port.ColorID});
						}
					}
				});
				portlist.change(function(){
					//Match Media type and color on incoming port
					row.porttype.children('select').val($(this).data($(this).val()).MediaID);
					row.portcolor.children('select').val($(this).data($(this).val()).ColorID);
				});
				if(rear){
					row.rdeviceport.html(portlist).find('select').val(row.rdeviceport.data('default'));
				}else{
					row.cdeviceport.html(portlist).find('select').val(row.cdeviceport.data('default'));
				}
				portlist.combobox();
			});

		},

		delete: function(e) {
			var row=this;
			var rear=(e.target.parentElement!=null)?(e.target.parentElement.id.indexOf('r')==0)?true:false:false;
			if(rear){
				$(row.rdevice).find('input').val('');
				row.rdevice.children('select').val(0).trigger('change');
			}else{
				$(row.cdevice).find('input').val('')
				row.cdevice.children('select').val(0).trigger('change');
			}
			$(e.currentTarget.parentNode.children[0]).click();
		},

		list: function() {
			var row=this;
			console.log(this);
			console.log(this.portnum);
			if (typeof resize=='undefined'){
				console.log('resize not detected');
			}else{
				console.log(typeof resize);
				resize();
			}
		},

		getcolortypes: function(){
			var row=this;
			$.get('',{cc:''}).done(function(data){
				var clist=$("<select>").append('<option value=0>&nbsp;</option>');
				$.each(data, function(key,cc){
					var option=$("<option>",({'value':cc.ColorID})).append(cc.Name);
					clist.append(option).data(cc.ColorID,cc.DefaultNote);
				});
				clist.change(function(){
					// default note is associated with this color so set it
					if($(this).data($(this).val())!=""){
						row.cnotes.children('input').val($(this).data($(this).val()));
					}
				});
				row.portcolor.html(clist).find('select').val(row.portcolor.data('default'));
				clist.combobox();
			});
		},

		getmediatypes: function() {
			var row=this;
			$.get('',{mt:''}).done(function(data){
				var mlist=$("<select>").append('<option value=0>&nbsp;</option>');
				$.each(data, function(key,mt){
					var option=$("<option>",({'value':mt.MediaID})).append(mt.MediaType);
					mlist.append(option).data(mt.MediaID,mt.ColorID);
				});
				mlist.change(function(){
					// default color is associated with this type so set it
                    if($(this).data($(this).val())!=""){
						row.portcolor.children('select').val($(this).data($(this).val()));
					}
				});
				if(portrights.admin){
					row.porttype.html(mlist);
				}else{
					row.porttype.append(mlist);
					mlist.hide();
				}
				row.porttype.find('select').val(row.porttype.data('default'));
				mlist.combobox();
			});
		},

		save: function(e) {
			var row=this;
			var rear=1;
			var device=row.cdevice;
			var deviceport=row.cdeviceport;
			var notes=row.cnotes;

			var check=$(e.target.parentElement).data('rear');

			// If this is any type of device other than a patch panel the save is straight forward
			if(check){
				// if we're dealing with the back side of a patch panel the port number is negative
				rear=-1;
				device=row.rdevice;
				deviceport=row.rdeviceport;
				notes=row.rnotes;
			}

			// save the port
			// if not a rear port make sure the port name isn't blank
			if(check || row.portname.children('input').val().trim().length){
				$.post('',{
					saveport: '',
					swdev: $('#deviceid').val(),
					pnum: row.portnum*rear,
					pname: row.portname.children('input').val(),
					cdevice: device.children('select').val(),
					cdeviceport: deviceport.children('select').val(),
					cnotes: notes.children('input').val(),
					porttype: row.porttype.children('select').val(),
					portcolor: row.portcolor.children('select').val()
				}).done(function(data){
					if(data.trim()==1){
						row.checkredraw(e);
					}else{
						// something broke
					}
				});
			}else{
				// No port name set, DENIED!
				row.element.effect('highlight', {color: 'salmon'}, 1500);
			}
		},

		// Display modal with path information if a device port is clicked.
		showpath: function(){
			var row=this;
			$([row.cdeviceport,row.rdeviceport]).each(function(){
				$(this).find('a').each(function(i){
					$(this).unbind('click');
					$(this).click(function(e){
						e.preventDefault();
						$.get($(e.target).attr('href'),{pathonly: ''}).done(function(data){
							var modal=$('<div />', {id: 'modal'}).html('<div id="modaltext">'+data+'</div><br><div id="modalstatus"></div>').dialog({
								appendTo: 'body',
								modal: true,
								minWidth: 400,
								close: function(){$(this).dialog('destroy');}
							});
							$('#modal').dialog("option", "width", $('#parcheos').width()+75);
						});
					});
				});
			});
		},

		checkredraw: function(e) {
			var row=this;
			var check=$(e.target.parentElement).data('rear');
			function editcheck(e){
				if(!row.btnrow.front[0].childNodes.length && !row.btnrow.rear[0].childNodes.length){
					$(row.element[0]).data('edit',false);
					row.btnrow.remove();
					row.destroy(e);
				}
			}

			if(check){ // true = rear of patch panel
				$(row.btnrow.rear[0].childNodes[0]).remove(); //stupid ie
				editcheck(e);
			}else if(check!==undefined){ // false = front of patch panel
				$(row.btnrow.front[0].childNodes[0]).remove();
				editcheck(e);
			}
			row.destroy(check);
		},

		destroy: function(check) {
			var row=this;

			function front(){
				$.post('',{getport: '',swdev: $('#deviceid').val(),pnum: row.portnum}).done(function(data){
					row.portname.html(data.Label).data('default',data.Label);
					row.cdevice.html('<a href="devices.php?deviceid='+data.ConnectedDeviceID+'">'+data.ConnectedDeviceLabel+'</a>').data('default',data.ConnectedDeviceID);
					data.ConnectedPortLabel=(data.ConnectedPortLabel==null)?'':data.ConnectedPortLabel;
					row.cdeviceport.html('<a href="paths.php?deviceid='+data.ConnectedDeviceID+'&portnumber='+data.ConnectedPort+'">'+data.ConnectedPortLabel+'</a>').data('default',data.ConnectedPort);
					row.cnotes.html(data.Notes).data('default',data.Notes);
					row.porttype.html(data.MediaName).data('default',data.MediaID);
					row.portcolor.html(data.ColorName).data('default',data.ColorID);
					$(row.element[0]).children('div ~ div').removeAttr('style');
					// Attempt to show mass edit controls
					$('.switch.table, .patchpanel.table').massedit('show');
					row.showpath();
				});
			}

			function rear(){
				$.post('',{getport: '',swdev: $('#deviceid').val(),pnum: row.portnum*-1}).done(function(data){
					data.ConnectedPortLabel=(data.ConnectedPortLabel==null)?'':data.ConnectedPortLabel;
					row.rdevice.html('<a href="devices.php?deviceid='+data.ConnectedDeviceID+'">'+data.ConnectedDeviceLabel+'</a>').data('default',data.ConnectedDeviceID);
					row.rdeviceport.html('<a href="paths.php?deviceid='+data.ConnectedDeviceID+'&portnumber='+data.ConnectedPort+'">'+data.ConnectedPortLabel+'</a>').data('default',data.ConnectedPort);
					row.rnotes.html(data.Notes).data('default',data.Notes);
					row.porttype.html(data.MediaName).data('default',data.MediaID);
					// Attempt to show mass edit controls
					$('.switch.table, .patchpanel.table').massedit('show');
					row.showpath();
				});
			}
			if(check===undefined || check==false){
				front();
				if($(row.element[0]).parent().hasClass('switch')){
					$(row.element[0]).data('edit',false);
					$('#controls'+row.portnum).remove();
				}
			}else{
				rear();
			}
		}
	});
})( jQuery );
