function getISODateTime(d){
	// padding function
	var s = function(a,b){return(1e15+a+"").slice(-b)};

	// default date parameter
	if (typeof d === 'undefined'){
		d = new Date();
	};

	// return ISO datetime
	return d.getFullYear() + '-' +
		s(d.getMonth()+1,2) + '-' +
		s(d.getDate(),2) + ' ' +
		s(d.getHours(),2) + ':' +
		s(d.getMinutes(),2) + ':' +
		s(d.getSeconds(),2);
}

// Function to set a cookie
function setCookie(c_name, value) {
	var exdate=new Date();
	exdate.setDate(exdate.getDate() + 365);
	var c_value=escape(value) + ";expires="+exdate.toUTCString();
	document.cookie=c_name + "=" + c_value;
}

// a way too specific function for scrolling a div
function scrollolog(){
	var olog=$('#olog .table').parent('div');
	olog[0].scrollTop=olog[0].scrollHeight;
}

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

/*!
 * Scroll Sneak
 * http://mrcoles.com/scroll-sneak/
 *
 * Copyright 2010, Peter Coles
 * Licensed under the MIT licenses.
 * http://mrcoles.com/media/mit-license.txt
 *
 * Date: Mon Mar 8 10:00:00 2010 -0500
 */
var ScrollSneak = function(prefix, wait) {
	// clean up arguments (allows prefix to be optional - a bit of overkill)
	if (typeof(wait) == 'undefined' && prefix === true) prefix = null, wait = true;
	prefix = (typeof(prefix) == 'string' ? prefix : window.location.host).split('_').join('');
	var pre_name;

	// scroll function, if window.name matches, then scroll to that position and clean up window.name
	this.scroll = function() {
		if (window.name.search('^'+prefix+'_(\\d+)_(\\d+)_') == 0) {
			var name = window.name.split('_');
			window.scrollTo(name[1], name[2]);
			window.name = name.slice(3).join('_');
		}
	}
	// if not wait, scroll immediately
	if (!wait) this.scroll();

	this.sneak = function() {
		// prevent multiple clicks from getting stored on window.name
		if (typeof(pre_name) == 'undefined') pre_name = window.name;
		// get the scroll positions
		var top = 0, left = 0;
		if (typeof(window.pageYOffset) == 'number') { // netscape
			top = window.pageYOffset, left = window.pageXOffset;
		} else if (document.body && (document.body.scrollLeft || document.body.scrollTop)) { // dom
			top = document.body.scrollTop, left = document.body.scrollLeft;
		} else if (document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop)) { // ie6
			top = document.documentElement.scrollTop, left = document.documentElement.scrollLeft;
		}
		// store the scroll
		if (top || left) window.name = prefix + '_' + left + '_' + top + '_' + pre_name;
		return true;
	}
}

$(document).ready(function(){
	// Scroll to function for a page reload
	(function() {
		sneaky = new ScrollSneak(location.hostname);
	})();

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

	// The document has to be loaded before the event handler can be bound for the map
	bindmaptooltips();
});

// draw arrows on a canvas
function drawArrow(canvas,startx,starty,width,height,direction){
	// math is retarded
	startx=parseInt(startx);
	starty=parseInt(starty);
	width=parseInt(width);
	height=parseInt(height);

	var arrowW = 0.30 * width;
	var arrowH = 0.30 * height;
 
	switch(direction){
		case 'Top':
			var p1={x: startx+arrowW, y: starty};
			var p2={x: startx+arrowW, y: starty+height-arrowH};
			var p3={x: startx+(arrowW/2), y: starty+height-arrowH};
			var p4={x: startx+(width/2), y: starty+height};
			var p5={x: startx+width-(arrowW/2), y: starty+height-arrowH};
			var p6={x: startx+(width-arrowW), y: starty+height-arrowH};
			var p7={x: startx+(width-arrowW), y: starty};
			var my_gradient=canvas.createLinearGradient(0,starty,0,starty+height);
			break;
		case 'Bottom':
			var p1={x: startx+arrowW, y: starty+height};
			var p2={x: startx+arrowW, y: starty+arrowH};
			var p3={x: startx+(arrowW/2), y: starty+arrowH};
			var p4={x: startx+(width/2), y: starty};
			var p5={x: startx+width-(arrowW/2), y: starty+arrowH};
			var p6={x: startx+(width-arrowW), y: starty+arrowH};
			var p7={x: startx+(width-arrowW), y: starty+height};
			var my_gradient=canvas.createLinearGradient(0,starty+height,0,starty);
			break;
		case 'Right':
			var p1={x: startx+width,  y: starty+(height-arrowH)};
			var p2={x: startx+arrowW, y: starty+(height-arrowH)};
			var p3={x: startx+arrowW, y: starty+height};
			var p4={x: startx,        y: starty+(height/2)};
			var p5={x: startx+arrowW, y: starty};
			var p6={x: startx+arrowW, y: starty+arrowH};
			var p7={x: startx+width,  y: starty+arrowH};
			var my_gradient=canvas.createLinearGradient(startx+width,0,startx,0);
			break;
		default:
			var p1={x: startx,              y: starty+(height-arrowH)};
			var p2={x: startx+(width-arrowW), y: starty+(height-arrowH)};
			var p3={x: startx+(width-arrowW), y: starty+height};
			var p4={x: startx+width,          y: starty+(height/2)};
			var p5={x: startx+(width-arrowW), y: starty};
			var p6={x: startx+(width-arrowW), y: starty+arrowH};
			var p7={x: startx,              y: starty+arrowH};
			var my_gradient=canvas.createLinearGradient(startx,0,startx+width,0);
			break;
    }
	my_gradient.addColorStop(0.2,"rgba(0, 0, 255, .35)");
	my_gradient.addColorStop(0.8,"rgba(255, 0, 0, .35)");
	canvas.save();
	canvas.globalCompositeOperation="source-over";
	canvas.fillStyle=my_gradient;
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

// Template management  device_template.php
function Poopup(front){
	var target=(front)?'.front':'.rear';
	$('<div>').append($('#hiddencoords > div'+target)).
		dialog({
			closeOnEscape: false,
			minHeight: 500,
			width: 740,
			modal: true,
			resizable: false,
			position: { my: "center", at: "top", of: window },
			show: { effect: "blind", duration: 800 },
			beforeClose: function(event,ui){
				$('#hiddencoords').append($(this).children('div'));
			}
		});
}

function PortsPoopup(){
	$('<div>').append($('#hiddenports > div')).
		dialog({
			closeOnEscape: false,
			minHeight: 500,
			width: 740,
			modal: true,
			resizable: false,
			dialogClass: 'hiddenports',
			position: { my: "center", at: "top", of: window },
			show: { effect: "blind", duration: 800 },
			beforeClose: function(event,ui){
				$('#hiddenports').append($(this).children('div'));
			}
		});
}

function CoordinateRow(slot,front){
	front=(front=='undefined' || front)?0:1;
	var fr=(front==0)?'F':'R';
	var row=$('<div>').data('change',false);
	var input=$('<input>').attr({'size':'4','type':'number'});
	var label=$('<div>').text(slot).append((front=='0')?' Front':' Rear');
	var x=input.clone().attr('name','X'+fr+slot);
	var y=input.clone().attr('name','Y'+fr+slot);
	var w=input.clone().attr('name','W'+fr+slot);
	var h=input.clone().attr('name','H'+fr+slot);
	var edit=$('<button>').attr('type','button').append('Edit');
	row.append(label).
		append($('<div>').append(x)).
		append($('<div>').append(y)).
		append($('<div>').append(w)).
		append($('<div>').append(h)).
		append($('<div>').append(edit));

	// mark the coordinates as changed to snag manual entries and not just selections
	row.find('input').on('change',function(){
		row.data('change',true);
	});

	// If a slot has been defined already set the values
	// This is will value changes to the table over the values from the json if the button is hit a second time
	var rrow=$('input[name=X'+fr+slot+']').parent('div').parent('div');
	if((typeof slots[front]!='undefined' && typeof slots[front][slot]!='undefined') || ($('input[name=X'+fr+slot+']').val()!='undefined' && rrow.data('change'))){
		var xval=$('input[name=X'+fr+slot+']').val();
		var yval=$('input[name=Y'+fr+slot+']').val();
		var wval=$('input[name=W'+fr+slot+']').val();
		var hval=$('input[name=H'+fr+slot+']').val();
		x.val(((xval!='undefined' && rrow.data('change'))?xval:slots[front][slot].X));
		y.val(((yval!='undefined' && rrow.data('change'))?yval:slots[front][slot].Y));
		w.val(((wval!='undefined' && rrow.data('change'))?wval:slots[front][slot].W));
		h.val(((hval!='undefined' && rrow.data('change'))?hval:slots[front][slot].H));
	}

	// Update change status on the row assholes clicking buttons multiple times
	row.data('change',rrow.data('change'));

	edit.on('click',function(){
		$(this).closest('#coordstable').find('.table > div').removeClass('greybg');
		var templateimage=$(this).closest('#coordstable').prev('div').children('#previewimage').children('img');
		var modal=templateimage.parent('div');

		// This will take into account the size of the image on the screen
		// vs the actual size of the image
		var zoom=templateimage.width()/templateimage.naturalWidth();

		templateimage.imgAreaSelect({
			x1: parseInt((x.val())*zoom),
			x2: (parseInt(x.val()) + parseInt(w.val()))*zoom,
			y1: parseInt((y.val())*zoom),
			y2: (parseInt(y.val()) + parseInt(h.val()))*zoom,
			parent: modal,
			handles: true,
			show: true,
			onSelectEnd: function (img, selection) {
				x.val(parseInt(selection.x1/zoom));
				y.val(parseInt(selection.y1/zoom));
				w.val(parseInt(selection.width/zoom));
				h.val(parseInt(selection.height/zoom));
				row.data('change',true);
			}
		});
		row.addClass('greybg');
	});

	return row;
}

function InsertCoordsTable(front,btn){
	var picture=(front)?$('#FrontPictureFile'):$('#RearPictureFile');
	var targetdiv=(front)?'.front':'.rear';

	var table=$('<div>').addClass('table');
	table.append($('<div>').
		append($('<div>').append('Position')).
		append($('<div>').append('X')).
		append($('<div>').append('Y')).
		append($('<div>').append('W')).
		append($('<div>').append('H')).
		append($('<div>')));

	var front=(btn.prev('input').attr('id')=='ChassisSlots')?true:false;
	var picture=(front)?$('#FrontPictureFile'):$('#RearPictureFile');
	$(targetdiv+' #previewimage').html($('<img>').attr('src','pictures/'+picture.val()).width(400));

	for(var i=1;i<=btn.prev('input').val(); i++){
		table.append(CoordinateRow(i,front));
	}

	// moved this to the end so that the previous values could be read in the case that the 
	// edit coordinates button is pressed again
	$(targetdiv+' #coordstable').html(table);
}

function FetchSlots(){
	// fetch all the existing slots and put them in a global variable
	$.ajax({
		url: '',
		type: "post",
		async: false,
		data: {getslots:'',templateid: $('#templateid').val()},
		success: function(d){
			slots=d;
		}
	});
}

function TemplateButtons(){
	var pf=$('#FrontPictureFile');
	var rf=$('#RearPictureFile');
	var cs=$('#ChassisSlots');
	var rs=$('#RearChassisSlots');
	var np=$('#numports');

	if(np.val()>0){np.next('button').show();}else{np.next('button').hide();}
	if(pf.val()!='' && cs.val()>0){cs.next('button').show();}else{cs.next('button').hide();}
	if(rf.val()!='' && rs.val()>0){rs.next('button').show();}else{rs.next('button').hide();}
}

function buildportstable(){
	var table=$('<div>').addClass('table');
	table.append('<div><div>Port Number</div><div>Label</div><div>Media Type</div><div>Color</div><div>Notes</div></div>');
	var colorcodes=$('<select>').append($('<option>').val(0));
	var mediatypes=$('<select>').append($('<option>').val(0));
	var ports=[];

	function buildrow(TemplatePortObj){
		var rrow=$('.table input[name=label'+TemplatePortObj.PortNumber+']').parent('div').parent('div');
		var pn=TemplatePortObj.PortNumber;
		var label=(rrow.data('change'))?rrow.find('input[name^=label]').val():(typeof TemplatePortObj.Label=='undefined')?'':TemplatePortObj.Label;
		var mt=(rrow.data('change'))?rrow.find('select[name^=mt]').val():(typeof TemplatePortObj.MediaID=='undefined')?'0':TemplatePortObj.MediaID;
		var c=(rrow.data('change'))?rrow.find('select[name^=cc]').val():(typeof TemplatePortObj.ColorID=='undefined')?'0':TemplatePortObj.ColorID;
		var n=(rrow.data('change'))?rrow.find('input[name^=portnotes]').val():(typeof TemplatePortObj.PortNotes=='undefined')?'':TemplatePortObj.PortNotes;

		var row=$('<div>').
			append($('<div>').html(pn)).
			append($('<div>').html($('<input>').val(label).text(label).attr('name','label'+pn))).
			append($('<div>').html(mediatypes.clone().val(mt).attr('name','mt'+pn))).
			append($('<div>').html(colorcodes.clone().val(c).attr('name','cc'+pn))).
			append($('<div>').html($('<input>').val(n).text(n).attr('name','portnotes'+pn))).
			data('change',((rrow.data('change'))?true:false));

		// mark the port as changed to snag manual entries
		row.find('input,select').on('change keyup',function(){
			row.data('change',true);
		});

		return row;
	}

	function buildrows(){
		for(var i=1;i<=$('#numports').val();i++){
			if(typeof ports[i]!='undefined'){
				table.append(buildrow(ports[i]));
			}else{
				table.append(buildrow({PortNumber: i}));
			}
		}
	}

	$.ajax({url: '',type: "get",async: false,data: {cc: ''},success: function(data){
			$.each(data, function(i,color){
				colorcodes.append($('<option>').val(color.ColorID).text(color.Name));
			});
		}
	});

	$.ajax({url: '',type: "get",async: false,data: {mt: ''},success: function(data){
			$.each(data, function(i,mediatype){
				mediatypes.append($('<option>').val(mediatype.MediaID).text(mediatype.MediaType));
			});
		}
	});

	$.ajax({url: '',type: "post",async: false,data: {templateid: $('#templateid').val(), getports: ''},
		success: function(data){
			ports=data;
		}
	});

	// Add rows to the table
	buildrows();
	// Add the table to the page
	$('#hiddenports').html(table);
}


// Image management
	function makeThumb(path,file){
		var device=$('<div>').css('background-image', 'url("'+path+'/'+file+'")');
		var image=$('<div>').append(device).append($('<div>').addClass('filename').text(file));
		var del=$('<div>').addClass('del').hide();
		del.on('click', function(){
			$('#delete-confirm').dialog({
				resizable: false,
				height: 170,
				modal: true,
				buttons: {
					"Yes": function(){
						var rc=delimage(path,file);
						if(rc){
							image.remove();
						}else{
							// file check failed so we have a problem
							alert("File doesn't exist, how did that happen?");
						}
						$(this).dialog("close");
					},
					Cancel: function(){
						$(this).dialog("close");
					}
				}
			}).removeClass('hide');
		});
		device.on('click', function(){
			$('<div>').append($('<img>').attr('src',path+'/'+file).css({'max-width':'600px','max-height':'400px'})).attr('title',path+'/'+file).dialog({
				width: 'auto',
				modal: true
			});
		});

		image.append(del);
		image.mouseover(function(){del.toggle()});
		image.mouseout(function(){del.toggle()});
		return image; 
	}
	function delimage(path,file){
		var test=1;
		$.ajax({
			url: 'scripts/check-exists.php',
			type: "post",
			async: false,
			data: {dir: path, filename: file},
			success: function(d){
				if(d==1){
					$.ajax({
						url: 'scripts/uploadifive.php',
						data: {dir: path, filename: file, timestamp : timestamp, token : token},
						async: false,
						type: "post",
						success: function(e){
							if(e.status==1){
								// if the file doesn't delete for some reason return false
								alert(e.msg);
								test=0;
							}else{
								test=1;
							}
						}
					});
				}else{
					test=0;
				}
			},
		});
		return test;
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


// DataCenter map / cabinet information
function startmap(){
	var maptitle=$('#maptitle span');
	var mycanvas=document.getElementById("mapCanvas");
	var context=mycanvas.getContext('2d');
	var zoom=$('.canvas > map').data('zoom');
	var zx1=$('.canvas > map').data('x1');
	var zy1=$('.canvas > map').data('y1');


	function background(){
		var bgcanvas=document.getElementById("background");
		var bgcontext=bgcanvas.getContext('2d');
		bgcontext.globalCompositeOperation='destination-over';

		bgcontext.clearRect(0,0, bgcanvas.width, bgcanvas.height);
		var bgimg=new Image();
		bgimg.onload=function(){
			bgcontext.drawImage(bgimg,-zx1*zoom,-zy1*zoom,bgimg.width*zoom,bgimg.height*zoom);
		}
		bgimg.src=$(bgcanvas).data('image');
	}
	($('canvas#background').data('image'))?background():'';

	// arrays used for tracking states
	var stat;
	var areas={'cabs':[],'zones':[]};
	var defaultstate={'cabs':[],'zones':[]};
	var currentstate={'cabs':[],'zones':[]};

	context.globalCompositeOperation='destination-over';
	context.save();

	function clearcanvas(){
		// erase anything on the canvas
		context.clearRect(0,0, mycanvas.width, mycanvas.height);
	}

	// Remove all the existing cabinets and zones
	$('.canvas > map > area').remove();

	// Get a list of areas for the DC
	$.ajax({
		url: '',
		type: "post",
		async: false,
		data: {dc: $('map[name=datacenter]').data('dc'), getobjects: ''}, 
		success: function(data){
			var temp={'cabs':[],'zones':[]}; // array of areas we're using
			var temphilight={'cabs':[],'zones':[]}; // array of areas and their outline state

			var map=$('.canvas > map');
			$(data.cab).each(function(){
				map.append(buildarea(this));
				temp.cabs.push({'CabinetID':this.CabinetID,'MapX1':this.MapX1,'MapX2':this.MapX2,'MapY1':this.MapY1,'MapY2':this.MapY2});
				temphilight.cabs[this.CabinetID]=false;
			});
			$(data.zone).each(function(){
				map.append(buildarea(this));
				temp.zones.push({'ZoneID':this.ZoneID,'MapX1':this.MapX1,'MapX2':this.MapX2,'MapY1':this.MapY1,'MapY2':this.MapY2});
				temphilight.zones[this.ZoneID]=false;
			});

			// Move these arrays out to where they can be used.
			areas=$.extend(true,{},temp);
			defaultstate=$.extend(true,{},temphilight);
		}
	});

	// Get the status of all the cabinets
	$.ajax({
		url: '',
		type: "post",
		async: false,
		data: {dc: $('map[name=datacenter]').data('dc'), getoverview: ''}, 
		success: function(d){
			stat=$.extend(true,{},d);
		}
	});

	// Clear the canvas and redraw the outlines
	function ToggleHilight(e){
		$('#maptitle .nav > select').change();
		$.each(currentstate,function(i,cabszones){
			$.each(cabszones,function(x,obj){
				if(obj){
					if(i=='zones'){
						Hilight($('.canvas > map > area[name=zone'+x+']'));
					}else{
						Hilight($('.canvas > map > area[name=cab'+x+']'));
					}
				}	
			});
		});
	}

	// Watch the mouse when it is over the canvas element
	$('.canvas').mousemove(function(e){
		var cpos=$('#mapCanvas').offset();
		// clone the index of all the highlights
		var tempstate=$.extend(true,{},defaultstate);
		// modify the cloned index to add in the elements under the pointer currently
		$.each(areas,function(i,cabszones){
			$.each(cabszones,function(x,obj){
				var x1=parseInt(obj.MapX1-zx1)*zoom;
				var x2=parseInt(obj.MapX2-zx1)*zoom;
				var y1=parseInt(obj.MapY1-zy1)*zoom;
				var y2=parseInt(obj.MapY2-zy1)*zoom;
				// Check to see if we're over any of the objects we defined.
				if(e.pageX>(cpos.left+x1) && e.pageX<(cpos.left+x2) && e.pageY>(cpos.top+y1) && e.pageY<(cpos.top+y2)){
					var id=(i=='zones')?'ZoneID':'CabinetID';
					if(i=='zones'){
						tempstate.zones[obj.ZoneID]=true;
					}else{
						tempstate.cabs[obj.CabinetID]=true;
					}
				}
			});
		});
		// compare the cloned index to the current state of the canvas
		if(currentstate!=tempstate){
			// the states don't match so move the clone to the live index
			currentstate=$.extend(true,{},tempstate);
			// redraw the canvas with the objects highlighted
			ToggleHilight();
		}
	});

	// Draw an outline over an area
	function Hilight(obj,c){
		//there has to be a better way to do this.  stupid js
		area=obj.prop('coords').split(',');
		var x=(area[0]);
		var y=(area[1]);
		var w=(area[2]-area[0]);
		var h=(area[3]-area[1]);

		// if color isn't given then just outline the object
		if(typeof c=='undefined'){
			context.save();
			context.globalCompositeOperation='source-over';
			context.lineWidth='4';
			context.strokeStyle="rgba(255,0,0,1)";
			context.strokeRect(x,y,w,h);
			context.restore();
		}else if(typeof c=='string'){
			// draw arrow
			drawArrow(context,x,y,w,h,c);
		}else{
			context.save();
			context.fillStyle="rgba("+c.r+", "+c.g+", "+c.b+", 0.35)";
			context.fillRect(x,y,w,h);
			context.restore();
		}
	}

	// Build the area objects
	function buildarea(obj){
		var zone=typeof obj.Location=='undefined';
		var label=(zone)?obj.Description:obj.Location;
		var name=(zone)?'zone'+obj.ZoneID:'cab'+obj.CabinetID;
		var href=(zone)?'zone_stats.php?zone='+obj.ZoneID:'cabnavigator.php?cabinetid='+obj.CabinetID;
		var row=(zone)?false:(obj.CabRowID==0)?false:true;
		var x1=(obj.MapX1-zx1)*zoom;
		var x2=(obj.MapX2-zx1)*zoom;
		var y1=(obj.MapY1-zy1)*zoom;
		var y2=(obj.MapY2-zy1)*zoom;
		return $('<area>').attr({'shape':'rect','coords':x1+','+y1+','+x2+','+y2,'alt':label,'href':href,'name':name}).data({'hilight':false,'zone':obj.ZoneID,'row':row});
	}

	// Color the map
	function showstatus(state){
		clearcanvas();
		$.each(eval('stat.'+state),function(i,s){
			if(!isNaN(i)){
				var p=(state=='airflow')?stat.airflow[i]:stat.colors[s];
				Hilight($('.canvas > map > area[name=cab'+i+']'),p);
			}
		});
		maptitle.text(eval("stat."+state+"['title']"));

	}

	// Draw the map and bind the change action to the menu
	$('#maptitle .nav > select').change(function(){
		showstatus($(this).val());
	}).trigger('change');
}

function bindmaptooltips(){
	$('map[name="datacenter"]').on('mouseenter','area[name^="cab"]',function(){
		var pos=$('.canvas').offset();
		var coor=$(this).attr('coords').split(',');
		var tx=pos.left+parseInt(coor[2])+17;
		var ty=pos.top+(parseInt(coor[1])+parseInt(coor[3]))/2-17;
		var cx1=parseInt(coor[0])+parseInt(pos.left);
		var cx2=parseInt(coor[2])+parseInt(pos.left)
		var cy1=parseInt(coor[1])+parseInt(pos.top);
		var cy2=parseInt(coor[3])+parseInt(pos.top);
		var tooltip=$('<div />').css({
			'left':tx+'px',
			'top':ty+'px'
		}).addClass('arrow_left border cabnavigator tooltip').attr('id','tt').append('<span class="ui-icon ui-icon-refresh rotate"></span>');
		var id=$(this).attr('href');
		id=id.substring(id.lastIndexOf('=')+1,id.length);
		$.post('scripts/ajax_tooltip.php',{tooltip: id, cab: 1}, function(data){
			tooltip.html(data);
		});
		$('body').append(tooltip);
		
		$(this).mouseleave(function(e){
			tooltip.remove();
			if (cx1>0 && e.shiftKey && $('#maptitle .nav > select').val()=="airflow"){
				var frontedge;
				if(e.pageX<=cx1){
					frontedge="Right";
				}else if (e.pageX>=cx2){
					frontedge="Left";
				}else if (e.pageY<=cy1){
					frontedge="Bottom";
				}else if (e.pageY>=cy2){
					frontedge="Top";
				}
				$.post("",{cabinetid: id, airflow: frontedge, row: e.ctrlKey}).done(function(){startmap();});
			}
			cx1=0;
		});
	});
}
// END - DataCenter map / cabinet information

// Cabinet image / label controls
function cabinetimagecontrols(){
	var controlrow=$('<tr>').append($('<td>').attr('colspan','4').css('text-align','left')).addClass('noprint');
	controlrow.td=controlrow.find('td');
	var imgbtn=$('<button>').attr('type','button').css({'line-height': '1em', 'height': '1.5em'}).data('show',false).text('Images');
	var lblbtn=imgbtn.clone().text('Labels');
	controlrow.td.append(imgbtn);
	controlrow.td.append(lblbtn);

	imgbtn.on('click',function(){
		if($(this).data('show')){
			serutciPoN();
		}else{
			NoPictures();
		}
	});

	lblbtn.on('click',function(){
		if($(this).data('show')){
			slebaLoN();
		}else{
			NoLabels();
		}
	});

	function NoLabels(){
		lblbtn.data('show',true);
		setCookie('devlabels', 'hide');
		$('.picture .label').hide();
	}

	function slebaLoN(){
		lblbtn.data('show',false);
		setCookie('devlabels', 'show');
		$('.picture .label').show();
	}

	// Read the cookie and do stuff
	if(typeof $.cookie('devlabels')=='undefined' || $.cookie('devlabels')=='show'){
		slebaLoN();
	}else{
		NoLabels();
	}

	// TODO : Clean this shit up.  Make it more generic and get it into the common.js and outta here

	// Read the cookie and do stuff
	if(typeof $.cookie('cabpics')=='undefined' || $.cookie('cabpics')=='show'){
		serutciPoN();
	}else{
		NoPictures();
	}
		
	function serutciPoN(){
		// We're showing device images so labels are optional
		lblbtn.show();

		imgbtn.data('show',false);
		setCookie('cabpics', 'show');
		$('div.picture, .picture > div:not(.label)').css({'border':''});
		$('.picture img').each(function(){
			var pic=$(this);
			if($(this).attr('src')=='css/blank.gif'){
				$(this).attr('src',$(this).data('src'));
				pic.width(pic.width());
				pic.height(pic.height());
			}
		});
		$('.picture .label > div').css({'color':'','text-shadow':'','font-family':'','font-weight':'','text-decoration':''});
	}
	function NoPictures(){
		// We're hiding the device pictures so the labels are a must.
		slebaLoN();
		lblbtn.hide();

		imgbtn.data('show',true);
		setCookie('cabpics', 'hide');
		$('div.label').css('display','block');
		$('.picture > div:not(.label)').css({'border':'1px inset black'});
		$('.picture img').each(function(){
			var pic=$(this);
			if(pic.attr('src')!='css/blank.gif'){
				pic.data('src',pic.attr('src'));
				pic.attr('src','css/blank.gif');
				pic.width(pic.width());
				pic.height(pic.height());
			}
		});
		$('.picture img').attr('src','css/blank.gif');
		$('.picture .label > div').css({'color':'#000','text-shadow':'0 0 0','font-family':'helvetica, arial','font-weight':'100','text-decoration':'underline'});
	}

	var btnprint=$('<span>').addClass('ui-icon ui-icon-print').css('float','right').on('click',printcab);
	controlrow.td.append(btnprint);
	function printcab(){
		$('div#infopanel,div#sidebar,div#header,h2,h3,.center ~ a').hide();
		$('.cabinet').css({'transform':'scale(1.5)','transform-origin':'left top'});
		$('.main').css({'width':'','border':'0','background-color':'#fff'});
		$('.cabinet td').css('border','1px solid black');
		$('.cabinet').insertBefore('.center');
		window.print();
		$('#centeriehack').prepend($('.cabinet'));
		$('.cabinet td').css('border','');
		$('.main').css({'border':'','background-color':''});
		$('div#infopanel,div#sidebar,div#header,h2,h3,.center ~ a').show();
		$('.cabinet').css('transform','');
		resize();
	}

	// Add controls to the rack
	$('.center .cabinet:first-child table').prepend(controlrow);
}
// END = Cabinet image / label controls

// logging functions
function LameLogDisplay(){
	var test=$('<button>').attr('type','button').text('Log View').click(function(){
		var table=$('<div>').addClass('table').attr('id','logtable');
		var header='<div><div>Time</div><div>User</div><div>Action</div><div>Property</div><div>Old Value</div><div>New Value</div></div>';
		table.append(header);
		$.post('',{devid: $('#deviceid').val(), logging: true}).done(function(data){
			$.each(data, function(i, logitem){
				switch(logitem.Action){
					case '1':
						logitem.Action='Create';
						break;
					case '2':
						logitem.Action='Delete';
						break;
					case '3':
						logitem.Action='Change';
						break;
					default:
						break;
				}

				logitem.Property=(logitem.ChildID!=null)?'[Port '+logitem.ChildID+']   '+logitem.Property:logitem.Property;

				var row=$('<div>');
				row.append($('<div>').text(logitem.Time));
				row.append($('<div>').text(logitem.UserID));
				row.append($('<div>').text(logitem.Action));
				row.append($('<div>').text(logitem.Property));
				row.append($('<div>').text(logitem.OldVal));
				row.append($('<div>').text(logitem.NewVal));
				table.append(row);
			});
			$('<div>').append(table).dialog({
				width: $('#pandn').width(),
				height: $(window).height()-50,
				modal: true,
				dialogClass: 'logtable'
			})
		});
	});
	// the tabs are to match the existing page layout
	$('.caption').last().append("\t\t").append(test);
}

// ENG - Logging functions

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

	// searchable combobox
	$.widget( "custom.combobox", {
		_create: function() {
			this.element.parent(0).width($(this.element.parent(0)).width());
			this.wrapper=$("<span>").width(this.element.parent(0).width()-3).addClass("custom-combobox").insertAfter(this.element);

			if(this.element.is(":visible")){ 
				this.element.hide();
				this._createAutocomplete();
				this._createShowAllButton();
			}
		},
 
		_createAutocomplete: function() {
			var selected=this.element.children(":selected"),
				value=selected.val()?selected.text():"";

			this.input=$("<input>").css('width',this.wrapper.width()-24+'px').appendTo(this.wrapper).val(value).attr("title","")
				.addClass("custom-combobox-input ui-widget ui-widget-content ui-state-default")
				.autocomplete({
					delay: 0,
					minLength: 0,
					source: $.proxy( this, "_source" ),
					open: function(){
						$(this).autocomplete("widget").css({'width': $(this).width()+26+'px'}).addClass('monospace');
					}
				}).tooltip({tooltipClass: "ui-state-highlight"});

			this._on(this.input,{
				autocompleteselect: function(event, ui){
					ui.item.option.selected=true;
					this._trigger("select", event,{
						item: ui.item.option
					});
					$(ui.item.option).parent('select').change();
				},

				autocompletechange: "_removeIfInvalid"
			});
		},
 
		_createShowAllButton: function() {
			var input=this.input,
			wasOpen=false;
 
			$("<a>").attr("tabIndex", -1)
				.height($(this.wrapper).children('input').height())
				.css({'width':'18px','vertical-align':'top','padding':$(this.wrapper).children('input').css('padding')})
				.tooltip()
				.appendTo(this.wrapper)
				.button({icons:{primary: "ui-icon-triangle-1-s"},text: false})
				.removeClass("ui-corner-all")
				.addClass("custom-combobox-toggle")
				.mousedown(function(){wasOpen=input.autocomplete("widget").is(":visible");})
				.click(function(){
					input.focus();
 
					// Close if already visible
					if(wasOpen){return;}

					// Pass empty string as value to search for, displaying all results
					input.autocomplete( "search", "" );
				});
		},
 
		_source: function(request,response){
			var matcher=new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
			response(this.element.children("option").map(function() {
				var text=$(this).text();
				if(this.value && (!request.term || matcher.test(text))){
					return {
						label: text,
						value: text,
						option: this
					};
				}
			}));
		},
 
		_removeIfInvalid: function(event,ui){
			// Selected an item, nothing to do
			if(ui.item){
				return;
			}
 
			// Search for a match (case-insensitive)
			var value=this.input.val(),
				valueLowerCase=value.toLowerCase(),
				valid=false;

			this.element.children("option").each(function(){
				if($(this).text().toLowerCase()===valueLowerCase){
					this.selected=valid=true;
					return false;
				}
			});
 
			// Found a match, nothing to do
			if(valid){return;}
 
			// Remove invalid value
			this.input.val("").attr("title", value+" didn't match any item").tooltip("open");
			this.element.val("");
			this._delay(function(){
				this.input.tooltip("close").attr("title","");
			},2500);
			this.input.data("ui-autocomplete").term="";
		},

		_destroy: function() {
			this.wrapper.remove();
			this.element.show();
		}
	});

	// adds .naturalWidth() and .naturalHeight() methods to jQuery
	// for retreaving a normalized naturalWidth and naturalHeight.
	var props=['Width', 'Height'], prop;

	while (prop = props.pop()) {
		(function (natural, prop) {
			$.fn[natural] = (natural in new Image()) ? 
			function () {
			return this[0][natural];
			} : 
			function () {
			var 
			node = this[0],
			img,
			value;

			if (node.tagName.toLowerCase() === 'img') {
				img = new Image();
				img.src = node.src,
				value = img[prop];
			}
			return value;
			};
		}('natural' + prop, prop.toLowerCase()));
	}

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
			var del=$('<div>').addClass('delete').append($('<span>').addClass('ui-icon status down').on('click',function(e){row.deleteport()})).hide();
			this.element.prepend(del);

			// Define all the ports we might need later
			this.ct			 = ct;
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

			// Create a blank row for showing patch panel connections on devices
			this.pathrow	= $('<div>').addClass('path');
			for(var a=0; a < this.element[0].children.length; a++){
				this.pathrow.append($('<div>'));
			}
			this.pathrow.path = $('<div>');
			this.pathrow.find('div:first-child').addClass('delete').hide();
			this.pathrow.find('div:nth-child(2)').append($('<div>').append(this.pathrow.path));

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

			// Be lazy and redraw the port to see if we need to add path data
			if(this.cdevice.data('default')>0){
				this.destroy();
			}
		
		},
		edit: function() {
			var row=this;
			row.getdevices(this.cdevice);
			row.cnotes.html('<input type="text" style="min-width: 200px;" value="'+row.cnotes.text()+'">');
			row.portname.html('<input type="text" style="min-width: 60px;" value="'+row.portname.text()+'">');
			row.getmediatypes();
			row.getcolortypes();

			// rear panel edit
			if(portrights.admin && row.rdevice.length>0){
				row.getdevices(this.rdevice);
				row.rnotes.html('<input type="text" style="min-width: 200px;" value="'+row.rnotes.text()+'">');
			}

			// Flag row as being in edit mode
			row.element.data('edit',true);

			// Hide the patch path if there is one
			row.pathrow.hide();

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

		updatepath: function(e){
			var row=this;
			if($(this.element[0]).parent().hasClass('switch')){
				function makespan(label,port){
					return $('<span>').append(label+'['+port+']');
				}

				// Add the path row to the dom
				this.element.after(this.pathrow);

				// Add this device as the start of the connection chain
				row.pathrow.path.html(makespan($('#label').val(),row.portname.data('default')));

				// Retreive the path
				$.get('',{path: '', ConnectedDeviceID: row.cdevice.data('default'), ConnectedPort: row.cdeviceport.data('default')}).done(function(data){
					if(data[(data.length - 1)].DeviceID ==$('#deviceid').val() && data.length==2){
						// remove the last item in the chain to prevent a display loop if a device is only connected to a patch panel and nothing else
						data.pop();
					}
					$.each(data, function(port){
						// Add the next link in the chain
						row.pathrow.path.append(makespan(data[port].DeviceName,(data[port].Label=='')?Math.abs(data[port].PortNumber):data[port].Label));
					});
				});
			}

			// Assuming we added something show it
			row.pathrow.show();
		},

		adjustdisplay: function(e){
			var row=this;
			// We might need to expand the table if this is really long
			// this is really hacky but it's working, surely there is a cleaner method than a timer
			setTimeout(function(){
				var pw=row.pathrow.path[0].getBoundingClientRect().width;
				var tw=row.element.parent('.table')[0].getBoundingClientRect().width;
				if(tw < pw){row.element.parent('.table').width(pw+10);resize();}
			},500);
		},

		getdevices: function(target){
			var row=this;
			var postoptions={swdev: $('#deviceid').val(),pn: this.portnum};
			if(target===this.rdevice){
		//	Uncomment this line to restrict the device list to just patch panels
		//		postoptions=$.extend(postoptions,{rear: ''});
			}
			$.post('',postoptions).done(function(data){
				var devlist=$("<select>").append('<option value=0>&nbsp;</option>');
				devlist.change(function(e){
					row.getports(e);
				});

				$.each(data, function(devid,device){
/*
					var rack=$('#datacenters a[href$="cabinetid='+device.CabinetID+'"]');
					var dc=rack.parentsUntil('li[id^=dc]').last().prev('a').text();
					devlist.append('<option value='+device.DeviceID+'>'+dc+' '+rack.text()+' '+device.Label+'</option>');
*/
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
					port.Label=(port.PortNumber>0)?port.Label:port.Label+' (rear)';

					// Add the port to the list of options
					portlist.append('<option value='+port.PortNumber+'>'+port.Label+'</option>');
					portlist.data(port.PortNumber, {MediaID: port.MediaID, ColorID: port.ColorID});
				});
				portlist.change(function(){
					//Match Media type and color on incoming port
					row.porttype.children('select').val($(this).data($(this).val()).MediaID);
					row.portcolor.children('select').val($(this).data($(this).val()).ColorID);
				});
				// set the value of the select list to the current connection
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
			var rear=$(e.target.parentElement).data('rear');
			if(rear){
				$(row.rdevice).find('input').val('');
				row.rdevice.children('select').val(0).trigger('change');
			}else{
				$(row.cdevice).find('input').val('')
				row.cdevice.children('select').val(0).trigger('change');
			}
			$(e.currentTarget.parentNode.children[0]).click();
		},

		deleteport: function(e){
			var row=this;
			var lastrow=$('.switch > div:last-child, .patchpanel > div:last-child');
			$.post('',{delport: '',swdev: $('#deviceid').val(),pnum: row.portnum}).done(function(data){
				if(data.trim()==1){
					if($(document).data('ports')>$('#ports').val()){
						// if this is the last port just remove it
						if(row.element[0]==lastrow[0]){
							row.element.remove();
						// else redraw this port and remove the last one
						}else{
							row.destroy();
							lastrow.remove();
						}
						// decrease counter
						$(document).data('ports',$(document).data('ports')-1);
						if($(document).data('ports')==$('#ports').val()){$('#ports').change();}
					}else{
						$('#ports').change();
					}
				}
			});
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
					pname: (row.portname.children('input').length==0)?row.portname.data('default'):row.portname.children('input').val(),
					cdevice: device.children('select').val(),
					cdeviceport: deviceport.children('select').val(),
					cnotes: notes.children('input').val(),
					porttype: (row.porttype.children('select').length==0)?row.porttype.data('default'):row.porttype.children('select').val(),
					portcolor: (row.portcolor.length==0)?row.porttype.data('color'):row.portcolor.children('select').val()
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
				$(this).find('a:not([class])').each(function(i){
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
					row.ct.css('padding','');
					row.portname.html(data.Label).data('default',data.Label);
					row.cdevice.html('<a href="devices.php?deviceid='+data.ConnectedDeviceID+'">'+data.ConnectedDeviceLabel+'</a>').data('default',data.ConnectedDeviceID);
					data.ConnectedPortLabel=(data.ConnectedPortLabel==null)?'':data.ConnectedPortLabel;
					row.cdeviceport.html('<a href="paths.php?deviceid='+data.ConnectedDeviceID+'&portnumber='+data.ConnectedPort+'">'+data.ConnectedPortLabel+'</a>').data('default',data.ConnectedPort);
					row.cnotes.html(data.Notes).data('default',data.Notes);
					row.porttype.html(data.MediaName).data('default',data.MediaID);
					row.portcolor.html(data.ColorName).data('default',data.ColorID);
					$(row.element[0]).children('div ~ div:not([id^=sp])').removeAttr('style');
					// Attempt to show mass edit controls
					$('.switch.table, .patchpanel.table').massedit('show');
					if(data.ConnectedDeviceType=='Patch Panel'){
						row.updatepath();
						row.adjustdisplay();
					}
					row.showpath();
				});
			}

			function rear(){
				$.post('',{getport: '',swdev: $('#deviceid').val(),pnum: row.portnum*-1}).done(function(data){
					data.ConnectedPortLabel=(data.ConnectedPortLabel==null)?'':data.ConnectedPortLabel;
					row.rdevice.html('<a href="devices.php?deviceid='+data.ConnectedDeviceID+'">'+data.ConnectedDeviceLabel+'</a>').data('default',data.ConnectedDeviceID);
					row.rdeviceport.html('<a href="paths.php?deviceid='+data.ConnectedDeviceID+'&portnumber='+data.ConnectedPort+'">'+data.ConnectedPortLabel+'</a>').data('default',data.ConnectedPort);
					row.rnotes.html(data.Notes).data('default',data.Notes);
					row.porttype.html(data.MediaName).data('default',data.MediaID).data('color',data.ColorID);
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
