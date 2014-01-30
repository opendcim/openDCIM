

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
				$('#spn').append(generateportnames);
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

			// this will redraw the current ports based on the information given back from a json string
			function redrawports(portsarr){
				$.each(portsarr.ports, function(key,p){
					$('#spn'+p.PortNumber).text(p.Label);
					$('#n'+p.PortNumber).text(p.Notes);
					$('#mt'+p.PortNumber).text((p.MediaID>0)?portsarr.mt[p.MediaID].MediaType:'').data('default',p.MediaID);
					$('#cc'+p.PortNumber).text((p.ColorID>0)?portsarr.cc[p.ColorID].Name:'').data('default',p.ColorID);
				});
			}

			// Determine which controls to add to the page
			if(this.element.hasClass('switch')){
				massedit_mt();
				massedit_cc();
				massedit_pn();

			}else if(this.element.hasClass('patchpanel')){
				massedit_mt();
				massedit_rear();
			}

			// Nest the mass edit buttons inside of divs so they won't have to moved around
			$(this.element).find('div:first-child > div + div[id]').wrapInner($('<div>').css({'position':'relative','border':0,'margin':'-3px'}));

			// Adjust the button positions
			this.movebuttons();
		},

		movebuttons: function (){
			window.pos=$('#mt').offset();
			window.ccpos=$('#cc').offset();
			window.spnpos=$('#spn').offset();
			window.rearpos=$('#rear').offset();
			if(typeof pos!='undefined' && typeof setmediatype!='undefined'){setmediatype.css({top: '-3px', right: 0});}
			if(typeof ccpos!='undefined' && typeof setcolorcode!='undefined'){setcolorcode.css({top: '-3px', right: 0});}
			if(typeof spnpos!='undefined' && typeof generateportnames!='undefined'){generateportnames.css({top: '-3px', right: 0});}
			if(typeof rearpos!='undefined' && typeof rearedit!='undefined'){rearedit.css({top: '-3px', right: 0});}
		},

		hide: function(){
			this.element.find('div:first-child select').hide();
		},

		show: function(){
			var edit=true;
			$('.switch.table > div ~ div').each(function(){
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
			this.portnum     = this.element.data('port');
        	this.portname    = this.element.find('div[id^=spn]');
        	this.cdevice     = this.element.find('div[id^=d]:not([id^=dp])');
        	this.cdeviceport = this.element.find('div[id^=dp]');
        	this.cnotes      = this.element.find('div[id^=n]');
        	this.porttype    = this.element.find('div[id^=mt]');
        	this.portcolor   = this.element.find('div[id^=cc]');

			$.post('',{swdev: $('#deviceid').val(),pn: this.portnum}).done(function(data){
				var devlist=$("<select>").append('<option value=0>&nbsp;</option>');
				devlist.change(function(){
					row.getports();
				});

				$.each(data, function(devid,device){
					devlist.append('<option value='+device.DeviceID+'>'+device.Label+'</option>');
				});
				row.cdevice.html(devlist).find('select').val(row.cdevice.data('default'));
				devlist.combobox();
				devlist.change();
				row.cnotes.html('<input type="text" style="min-width: 200px;" value="'+row.cnotes.text()+'">');
				row.portname.html('<input type="text" style="min-width: 60px;" value="'+row.portname.text()+'">');
				row.getmediatypes();
				row.getcolortypes();
				row.element.data('edit',true);
			});

			// Row Controls
			var controls=$('<div>',({'id':'controls'+this.portnum}));
			var savebtn=$('<button>',{'type':'button'}).append('Save').on('click',function(){row.save()});
			var cancelbtn=$('<button>',{'type':'button'}).append('Cancel').on('click',function(){row.destroy()});
			var deletebtn=$('<button>',{'type':'button'}).append('Delete').click();
			controls.append(savebtn).append(cancelbtn).append(deletebtn);
			var minwidth=0;
			this.portcolor.after(controls);
			controls.children('button').each(function(){
				minwidth+=$(this).outerWidth()+14; // 14 padding and border
			});
			controls.css('min-width',minwidth);
			this.element.children('div ~ div:not([id^=st])').css({'padding': '0px', 'background-color': 'transparent'});
			setTimeout(function() {
				resize();
			},200);

			// Hide mass edit controls
			$('.switch.table, .patchpanel.table').massedit('hide');
		},

		getports: function(){
			var row=this;
			$.post('',{swdev: $('#deviceid').val(),pn: this.portnum,thisdev: this.cdevice.find('select').val(),listports: ''}).done(function(data){
				var portlist=$("<select>");
				$.each(data, function(key,port){
					var pn=port.PortNumber;
					port.Label=(port.Label=="")?pn:port.Label;

					// only allow positive values
					if(pn>0){
						portlist.append('<option value='+pn+'>'+port.Label+'</option>');
						portlist.data(pn, {MediaID: port.MediaID, ColorID: port.ColorID});
					}
				});
				portlist.change(function(){
					//Match Media type and color on incoming port
					row.porttype.children('select').val($(this).data($(this).val()).MediaID);
					row.portcolor.children('select').val($(this).data($(this).val()).ColorID);
				});
				row.cdeviceport.html(portlist).find('select').val(row.cdeviceport.data('default'));
				portlist.combobox();
			});

		},

		list: function() {
			console.log(this);
			console.log(this.portnum);
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
				row.porttype.html(mlist).find('select').val(row.porttype.data('default'));
				mlist.combobox();
			});
		},

		save: function() {
			var row=this;
			if(row.portname.children('input').val().trim().length){
				$.post('',{
					saveport: '',
					swdev: $('#deviceid').val(),
					pnum: row.portnum,
					pname: row.portname.children('input').val(),
					cdevice: row.cdevice.children('select').val(),
					cdeviceport: row.cdeviceport.children('select').val(),
					cnotes: row.cnotes.children('input').val(),
					porttype: row.porttype.children('select').val(),
					portcolor: row.portcolor.children('select').val()
				}).done(function(data){
					if(data.trim()==1){
						row.destroy();
					}else{
						// something broke
					}
				});
			}else{
				// No port name set, DENIED!
				row.effect('highlight', {color: 'salmon'}, 1500);
			}
		},

		_destroy: function() {
			var row=this;
			$.post('',{getport: '',swdev: $('#deviceid').val(),pnum: this.portnum}).done(function(data){
				row.portname.html(data.Label).data('default',data.Label);
				row.cdevice.html('<a href="devices.php?deviceid='+data.ConnectedDeviceID+'">'+data.ConnectedDeviceLabel+'</a>').data('default',data.ConnectedDeviceID);
				data.ConnectedPortLabel=(data.ConnectedPortLabel==null)?'':data.ConnectedPortLabel;
				row.cdeviceport.html('<a href="paths.php?deviceid='+data.ConnectedDeviceID+'&portnumber='+data.ConnectedPort+'">'+data.ConnectedPortLabel+'</a>').data('default',data.ConnectedPort);
				row.cnotes.html(data.Notes).data('default',data.Notes);
				row.porttype.html(data.MediaName).data('default',data.MediaID);
				row.portcolor.html(data.ColorName).data('default',data.ColorID);
				$('#controls'+row.portnum).remove();
				$(row.element[0]).children('div ~ div').removeAttr('style');
				$(row.element[0]).data('edit',false);
				// Attempt to show mass edit controls
				$('.switch.table, .patchpanel.table').massedit('show');
			});
		}
	});
})( jQuery );
