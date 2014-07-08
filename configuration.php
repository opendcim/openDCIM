<?php
	// Allow the installer to link to the config page
	$devMode=true;

	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	function BuildFileList(){
		$imageselect='<div id="preview"></div><div id="filelist">';

		$path='./images';
		$dir=scandir($path);
		foreach($dir as $i => $f){
			if(is_file($path.DIRECTORY_SEPARATOR.$f) && round(filesize($path.DIRECTORY_SEPARATOR.$f) / 1024, 2)>=4 && $f!="serverrack.png" && $f!="gradient.png"){
				$imageinfo=getimagesize($path.DIRECTORY_SEPARATOR.$f);
				if(preg_match('/^image/i', $imageinfo['mime'])){
					$imageselect.="<span>$f</span>\n";
				}
			}
		}
		$imageselect.="</div>";
		return $imageselect;
	}

	// AJAX Requests
	if(isset($_GET['fl'])){
		echo BuildFileList();
		exit;
	}
	if(isset($_POST['fe'])){ // checking that a file exists
		echo(is_file($_POST['fe']))?1:0;
		exit;
	}
	if(isset($_POST['cc'])){  // Cable color codes
		$col=new ColorCoding();
		$col->Name=trim($_POST['cc']);
		$col->DefaultNote=trim($_POST['ccdn']);
		if(isset($_POST['cid'])){ // If set we're updating an existing entry
			$col->ColorID=$_POST['cid'];
			if(isset($_POST['original'])){
				$col->GetCode();
			    header('Content-Type: application/json');
				echo json_encode($col);
				exit;
			}
			if(isset($_POST['clear']) || isset($_POST['change'])){
				$newcolorid=0;
				if(isset($_POST['clear'])){
					ColorCoding::ResetCode($col->ColorID);
				}else{
					$newcolorid=$_POST['change'];
					ColorCoding::ResetCode($col->ColorID,$newcolorid);
				}
				$mediatypes=MediaTypes::GetMediaTypeList();
				foreach($mediatypes as $mt){
					if($mt->ColorID==$col->ColorID){
						$mt->ColorID=$newcolorid;
						$mt->UpdateType();
					}
				}
				if($col->DeleteCode()){
					echo 'u';
				}else{
					echo 'f';
				}
				exit;
			}
			if($col->UpdateCode()){
				echo 'u';
			}else{
				echo 'f';
			}
		}else{
			if($col->CreateCode()){
				echo $col->ColorID;
			}else{
				echo 'f';
			}
		}
		exit;
	}
	if(isset($_POST['ccused'])){
		$count=ColorCoding::TimesUsed($_POST['ccused']);
		if($count==0){
			$col=new ColorCoding();
			$col->ColorID=$_POST['ccused'];
			$col->DeleteCode();
		}
		echo $count;
		exit;
	}
	if(isset($_POST['mt'])){ // Media Types
		$mt=new MediaTypes();
		$mt->MediaType=trim($_POST['mt']);
		$mt->ColorID=$_POST['mtcc'];
		if(isset($_POST['mtid'])){ // If set we're updating an existing entry
			$mt->MediaID=$_POST['mtid'];
			if(isset($_POST['original'])){
				$mt->GetType();
			    header('Content-Type: application/json');
				echo json_encode($mt);
				exit;
			}
			if(isset($_POST['clear']) || isset($_POST['change'])){
				if(isset($_POST['clear'])){
					MediaTypes::ResetType($mt->MediaID);
				}else{
					$newmediaid=$_POST['change'];
					MediaTypes::ResetType($mt->MediaID,$newmediaid);
				}
				if($mt->DeleteType()){
					echo 'u';
				}else{
					echo 'f';
				}
				exit;
			}
			if($mt->UpdateType()){
				echo 'u';
			}else{
				echo 'f';
			}
		}else{
			if($mt->CreateType()){
				echo $mt->MediaID;
			}else{
				echo 'f';
			}
			
		}
		exit;
	}
	if(isset($_POST['mtused'])){
		$count=MediaTypes::TimesUsed($_POST['mtused']);
		if($count==0){
			$mt=new MediaTypes();
			$mt->MediaID=$_POST['mtused'];
			$mt->DeleteType();
		}
		echo $count;
		exit;
	}
	if(isset($_POST['mtlist'])){
		$codeList=MediaTypes::GetMediaTypeList();
		$output='<option value=""></option>';
		foreach($codeList as $mt){
			$output.="<option value=\"$mt->MediaID\">$mt->MediaType</option>";
		}
		echo $output;
		exit;		
	}
	if(isset($_POST['cclist'])){
		$codeList=ColorCoding::GetCodeList();
		$output='<option value=""></option>';
		foreach($codeList as $cc){
			$output.="<option value=\"$cc->ColorID\">$cc->Name</option>";
		}
		echo $output;
		exit;		
	}
	// END AJAX Requests

	if(isset($_REQUEST["action"]) && $_REQUEST["action"]=="Update"){
		foreach($config->ParameterArray as $key=>$value){
			if($key=="ClassList"){
				$List=explode(", ",$_REQUEST[$key]);
				$config->ParameterArray[$key]=$List;
			}else{
				$config->ParameterArray[$key]=$_REQUEST[$key];
			}
		}
		$config->UpdateConfig();

		//Disable all tooltip items and clear the SortOrder
		$dbh->exec("UPDATE fac_CabinetToolTip SET SortOrder = NULL, Enabled=0;");
		if(isset($_POST["tooltip"]) && !empty($_POST["tooltip"])){
			$p=$dbh->prepare("UPDATE fac_CabinetToolTip SET SortOrder=:sortorder, Enabled=1 WHERE Field=:field LIMIT 1;");
			foreach($_POST["tooltip"] as $order => $field){
				$p->bindParam(":sortorder",$order);
				$p->bindParam(":field",$field);
				$p->execute();
			}
		}

		//Disable all cdu tooltip items and clear the SortOrder
		$dbh->exec("UPDATE fac_CDUToolTip SET SortOrder = NULL, Enabled=0;");
		if(isset($_POST["cdutooltip"]) && !empty($_POST["cdutooltip"])){
			$p=$dbh->prepare("UPDATE fac_CDUToolTip SET SortOrder=:sortorder, Enabled=1 WHERE Field=:field LIMIT 1;");
			foreach($_POST["cdutooltip"] as $order => $field){
				$p->bindParam(":sortorder",$order);
				$p->bindParam(":field",$field);
				$p->execute();
			}
		}
	}

	// make list of department types
	$i=0;
	$classlist="";
	foreach($config->ParameterArray["ClassList"] as $item){
		$classlist .= $item;
		if($i+1 != count($config->ParameterArray["ClassList"])){
			$classlist.=", ";
		}
		$i++;
	}

	$imageselect=BuildFileList();

	function formatOffset($offset) {
			$hours = $offset / 3600;
			$remainder = $offset % 3600;
			$sign = $hours > 0 ? '+' : '-';
			$hour = (int) abs($hours);
			$minutes = (int) abs($remainder / 60);

			if ($hour == 0 AND $minutes == 0) {
				$sign = ' ';
			}
			return 'GMT' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) 
					.':'. str_pad($minutes,2, '0');

	}

	$regions=array();
	foreach(DateTimeZone::listIdentifiers() as $line){
		$pieces=explode("/",$line);
		if(isset($pieces[1])){
			$regions[$pieces[0]][]=$line;
		}
	}

	$tzmenu='<ul id="tzmenu">';
	foreach($regions as $country => $cityarray){
		$tzmenu.="\t<li>$country\n\t\t<ul>";
		foreach($cityarray as $key => $city){
			$z=new DateTimeZone($city);
			$c=new DateTime(null, $z);
			$adjustedtime=$c->format('H:i a');
			$offset=formatOffset($z->getOffset($c));
			$tzmenu.="\t\t\t<li><a href=\"#\" data=\"$city\">$adjustedtime - $offset $city</a></li>\n";
		}
		$tzmenu.="\t\t</ul>\t</li>";
	}
	$tzmenu.='</ul>';

	// Build list of cable color codes
	$cablecolors="";
	$colorselector='<select name="mediacolorcode[]"><option value=""></option>';

	$codeList=ColorCoding::GetCodeList();
	if(count($codeList)>0){
		foreach($codeList as $cc){
			$colorselector.='<option value="'.$cc->ColorID.'">'.$cc->Name.'</option>';
			$cablecolors.='<div>
					<div><img src="images/del.gif"></div>
					<div><input type="text" name="colorcode[]" data='.$cc->ColorID.' value="'.$cc->Name.'"></div>
					<div><input type="text" name="ccdefaulttext[]" value="'.$cc->DefaultNote.'"></div>
				</div>';
		}
	}
	$colorselector.='</select>';

	// Build list of media types
	$mediatypes="";
	$mediaList=MediaTypes::GetMediaTypeList();

	if(count($mediaList)>0){
		foreach($mediaList as $mt){
			$mediatypes.='<div>
					<div><img src="images/del.gif"></div>
					<div><input type="text" name="mediatype[]" data='.$mt->MediaID.' value="'.$mt->MediaType.'"></div>
					<div><select name="mediacolorcode[]"><option value=""></option>';
			foreach($codeList as $cc){
				$selected=($mt->ColorID==$cc->ColorID)?' selected':'';
				$mediatypes.="<option value=\"$cc->ColorID\"$selected>$cc->Name</option>";
			}
			$mediatypes.='</select></div>
				</div>';
		}
	}

	// Figure out what the URL to this page
	$href="";
	$href.=(array_key_exists('HTTPS', $_SERVER)) ? 'https://' : 'http://';
	$href.=$_SERVER['SERVER_NAME'];
	$href.=substr($_SERVER['REQUEST_URI'], 0, -strlen(basename($_SERVER['REQUEST_URI'])));

	// Build up the list of items available for the tooltips
	$tooltip="<select id=\"tooltip\" name=\"tooltip[]\" multiple=\"multiple\">\n";
	$sql="SELECT * FROM fac_CabinetToolTip ORDER BY SortOrder ASC, Enabled DESC, Label ASC;";
	foreach($dbh->query($sql) as $row){
		$selected=($row["Enabled"])?" selected":"";
		$tooltip.="<option value=\"".$row['Field']."\"$selected>".__($row["Label"])."</option>\n";
	}
	$tooltip.="</select>";

	// Build up the list of items available for the tooltips
	$cdutooltip="<select id=\"cdutooltip\" name=\"cdutooltip[]\" multiple=\"multiple\">\n";
	$sql="SELECT * FROM fac_CDUToolTip ORDER BY SortOrder ASC, Enabled DESC, Label ASC;";
	foreach($dbh->query($sql) as $row){
		$selected=($row["Enabled"])?" selected":"";
		$cdutooltip.="<option value=\"".$row['Field']."\"$selected>".__($row["Label"])."</option>\n";
	}
	$cdutooltip.="</select>";
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery.miniColors.css" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.ui.multiselect.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.miniColors.js"></script>
  <script type="text/javascript" src="scripts/jquery.ui.multiselect.js"></script>
  <script type="text/javascript">
	$(document).ready(function(){
		// ToolTips

		$('#tooltip, #cdutooltip').multiselect();
		$("select:not('#tooltip, #cdutooltip')").each(function(){
			if($(this).attr('data')){
				$(this).val($(this).attr('data'));
			}
		});

		// Applies to everything

		$("#configtabs").tabs();
		$('#configtabs input[defaultvalue],#configtabs select[defaultvalue]').each(function(){
			$(this).parent().after('<div><button type="button">&lt;--</button></div><div><span>'+$(this).attr('defaultvalue')+'</span></div>');
		});
		$("#configtabs input").each(function(){
			$(this).attr('id', $(this).attr('name'));
			$(this).removeAttr('defaultvalue');
		});
		$("#configtabs button").each(function(){
			var a = $(this).parent().prev().find('input,select');
			$(this).click(function(){
				a.val($(this).parent().next().children('span').text());
				if(a.hasClass('color-picker')){
					a.minicolors('value', $(this).parent().next().children('span').text()).trigger('change');
				}
				a.triggerHandler("paste");
				a.focus();
				$('input[name="OrgName"]').focus();
			});
		});

		// Style - Site

		function colorchange(hex,id){
			if(id==='HeaderColor'){
				$('#header').css('background-color',hex);
			}else if(id==='BodyColor'){
				$('.main').css('background-color',hex);
			}
		}
		$(".color-picker").minicolors({
			letterCase: 'uppercase',
			change: function(hex, rgb){
					colorchange($(this).val(),$(this).attr('id'));
			}
		}).change(function(){colorchange($(this).val(),$(this).attr('id'));});
		$('input[name="LinkColor"]').blur(function(){
			$("head").append("<style type=\"text/css\">a:link, a:hover, a:visited:hover {color: "+$(this).val()+";}</style>");
		});
		$('input[name="VisitedLinkColor"]').blur(function(){
			$("head").append("<style type=\"text/css\">a:visited {color: "+$(this).val()+";}</style>");
		});

		// Reporting

		$('#PDFLogoFile').click(function(){
			$.get('',{fl: '1'}).done(function(data){
				$("#imageselection").html(data);


				$("#imageselection").dialog({
					resizable: false,
					height:300,
					width: 400,
					modal: true,
					buttons: {
	<?php echo '					',__("Select"),': function() {'; ?>
							if($('#imageselection #preview').attr('image')!=""){
								$('#PDFLogoFile').val($('#imageselection #preview').attr('image'));
							}
							$(this).dialog("close");
						}
					}
				});
				$("#imageselection span").each(function(){
					var preview=$('#imageselection #preview');
					$(this).click(function(){
						preview.html('<img src="images/'+$(this).text()+'" alt="preview">').attr('image',$(this).text()).css('border-width', '5px');
						preview.children('img').load(function(){
							var topmargin=0;
							var leftmargin=0;
							if($(this).height()<$(this).width()){
								$(this).width(preview.innerHeight());
								$(this).css({'max-width': preview.innerWidth()+'px'});
								topmargin=Math.floor((preview.innerHeight()-$(this).height())/2);
							}else{
								$(this).height(preview.innerHeight());
								$(this).css({'max-height': preview.innerWidth()+'px'});
								leftmargin=Math.floor((preview.innerWidth()-$(this).width())/2);
							}
							$(this).css({'margin-top': topmargin+'px', 'margin-left': leftmargin+'px'});
						});
						$("#imageselection span").each(function(){
							$(this).removeAttr('style');
						});
						$(this).css('border','1px dotted black')
						$('#header').css('background-image', 'url("images/'+$(this).text()+'")');
					});
					if($('#PDFLogoFile').val()==$(this).text()){
						$(this).click();
					}
				});
			});
		});

		// General - Time and Measurements

		$("#tzmenu").menu();
		$("#tzmenu ul > li").click(function(e){
			e.preventDefault();
			$("#timezone").val($(this).children('a').attr('data'));
			$("#tzmenu").toggle();
		});
		$("#tzmenu").focusout(function(){
			$("#tzmenu").toggle();
		});
		$('<button type="button">').attr({
				id: 'btn_tzmenu'
		}).appendTo("#general");
		$('#btn_tzmenu').each(function(){
			var input=$("#timezone");
			var offset=input.position();
			var height=input.outerHeight();
			$(this).css({
				'height': height+'px',
				'width': height+'px',
				'position': 'absolute',
				'left': offset.left+input.width()-height-((input.outerHeight()-input.height())/2)+'px',
				'top': offset.top+'px'
			}).click(function(){
				$("#tzmenu").toggle();
				$("#tzmenu").focus().click();
			});
			offset=$(this).position();
			$("#tzmenu").css({
				'position': 'absolute',
				'left': offset.left+(($(this).outerWidth()-$(this).width())/2)+'px',
				'top': offset.top+height+'px'
			});
			$(this).addClass('text-arrow');
		});

		// Cabling - Media Types
		function removemedia(row){
			$.post('',{mtused: row.find('div:nth-child(2) input').attr('data')}).done(function(data){
				if(data.trim()==0){
					row.effect('explode', {}, 500, function(){
						$(this).remove();
					});
				}else{
					var defaultbutton={
						"<?php echo __("Clear all"); ?>": function(){
							$.post('',{mtid: row.find('div:nth-child(2) input').attr('data'),mt: '', mtcc: '', clear: ''}).done(function(data){
								if(data.trim()=='u'){ // success
									$('#modal').dialog("destroy");
									row.effect('explode', {}, 500, function(){
										$(this).remove();
									});
								}else{ // failed to delete
									$('#modaltext').html('AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>');
									$('#modal').dialog('option','buttons',cancelbutton);
								}
							});
						}
					}
					var replacebutton={
						"<?php echo __("Replace"); ?>": function(){
							// send command to replace all connections with x
							$.post('',{mtid: row.find('div:nth-child(2) input').attr('data'),mt: '', mtcc: '', change: $('#modal select').val()}).done(function(data){
								if(data.trim()=='u'){ // success
									$('#modal').dialog("destroy");
									row.effect('explode', {}, 500, function(){
										$(this).remove();
									});
								}else{ // failed to delete
									$('#modaltext').html('AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>');
									$('#modal').dialog('option','buttons',cancelbutton);
								}
							});
						}
					}
					var cancelbutton={
						"<?php echo __("Cancel"); ?>": function(){
							$(this).dialog("destroy");
						}
					}
<?php echo "					var modal=$('<div />', {id: 'modal', title: '".__("Media Type Delete Override")."'}).html('<div id=\"modaltext\">".__("This media type is in use somewhere. Select an alternate type to assign to all the records to or choose clear all.")."<select id=\"replaceme\"></select></div>').dialog({"; ?>
						dialogClass: 'no-close',
						appendTo: 'body',
						modal: true,
						buttons: $.extend({}, defaultbutton, cancelbutton)
					});
					$.post('',{mtlist: ''}).done(function(data){
						var choices=$('<select />');
						choices.html(data);
						choices.find('option').each(function(){
							if($(this).val()==row.find('div:nth-child(2) input').attr('data')){$(this).remove();}
						});
						choices.change(function(){
							if($(this).val()==''){ // clear all
								modal.dialog('option','buttons',$.extend({}, defaultbutton, cancelbutton));
							}else{ // replace
								modal.dialog('option','buttons',$.extend({}, replacebutton, cancelbutton));
							}
						});
						modal.find($('#replaceme')).replaceWith(choices);
						
					});
				}
			});

		}

		var blankmediarow=$('<div />').html('<div><img src="images/del.gif"></div><div><input id="mediatype[]" name="mediatype[]" type="text"></div><div><select name="mediacolorcode[]"></select></div>');
		function bindmediarow(row){
			var addrem=row.find('div:first-child');
			var mt=row.find('div:nth-child(2) input');
			var mtcc=row.find('div:nth-child(3) select');
			if(mt.val().trim()!='' && addrem.attr('id')!='newline'){
				addrem.click(function(){
					removemedia(row);
				});
			}
			mt.keypress(function(event){
				if(event.keyCode==10 || event.keyCode==13){
					event.preventDefault();
					mt.change();
				}
			});
			function update(inputobj){
				if(mt.val().trim()==''){
					// reset value to previous
					$.post('',{mt: mt.val(), mtid: mt.attr('data'), mtcc: mtcc.val(),original:''}).done(function(jsondata){
						mt.val(jsondata.MediaType);
						mtcc.val(jsondata.ColorID);
					});
					mt.effect('highlight', {color: 'salmon'}, 1500);
					mtcc.effect('highlight', {color: 'salmon'}, 1500);
				}else{
					// attempt to update
					$.post('',{mt: mt.val(), mtid: mt.attr('data'), mtcc: mtcc.val()}).done(function(data){
						if(data.trim()=='f'){ // fail
							$.post('',{mt: mt.val(), mtid: mt.attr('data'), mtcc: mtcc.val(),original:''}).done(function(jsondata){
								mt.val(jsondata.MediaType);
								mtcc.val(jsondata.ColorID);
							});
							mt.effect('highlight', {color: 'salmon'}, 1500);
							mtcc.effect('highlight', {color: 'salmon'}, 1500);
						}else if(data.trim()=='u'){ // updated
							mt.effect('highlight', {color: 'lightgreen'}, 2500);
							mtcc.effect('highlight', {color: 'lightgreen'}, 2500);
						}else{ // created
							var newitem=blankmediarow.clone();
							newitem.find('div:nth-child(2) input').val(mt.val()).attr('data',data.trim());
							newitem.find('div:nth-child(3) select').replaceWith(mtcc.clone());
							bindmediarow(newitem);
							row.before(newitem);
							newitem.find('div:nth-child(3) select').val(mtcc.val()).focus();
							if(addrem.attr('id')=='newline'){
								mt.val('');
								mtcc.val('');
							}else{
								row.remove();
							}
						}
					});
				}
			}
			mt.change(function(){
				update($(this));
			});
			mtcc.change(function(){
				var row=$(this).parent('div').parent('div');
				if(row.find('div:first-child').attr('id')!='newline'){
					update($(this));
				}else if(row.find('div:nth-child(2) input').val().trim()!=''){
					update($(this));
				}
			});
		}

		// Add a new blank row
		$('#mediatypes > div ~ div > div:first-child').each(function(){
			if($(this).attr('id')=='newline'){
				var row=$(this).parent('div');
				$(this).click(function(){
					var newitem=blankmediarow.clone();
					// Clone the current dropdown list
					newitem.find('select[name="mediacolorcode[]"]').replaceWith((row.find('select[name="mediacolorcode[]"]').clone()));
					newitem.find('div:first-child').click(function(){
						removecolor($(this).parent('div'),false);
					});
					bindmediarow(newitem);
					row.before(newitem);
				});
			}
			bindmediarow($(this).parent('div'));
		});

		// Update color drop lists
		function updatechoices(){
			$.post('',{cclist: ''}).done(function(data){
				$('#mediatypes > div ~ div').each(function(){
					var list=$(this).find('select[name="mediacolorcode[]"]');
					var dc=list.val();
					list.html(data);
					$(this).find('select[name="mediacolorcode[]"]').val(dc);
				});
			});
		}

		// Cabling - Cable Colors

		function removecolor(rowobject,lookup){
			if(!lookup){
				rowobject.remove();
			}else{
				$.post('',{ccused: rowobject.find('div:nth-child(2) input').attr('data')}).done(function(data){
					if(data.trim()==0){
						updatechoices();
						rowobject.effect('explode', {}, 500, function(){
							$(this).remove();
						});
					}else{
						var defaultbutton={
							"<?php echo __("Clear all"); ?>": function(){
								$.post('',{cid: rowobject.find('div:nth-child(2) input').attr('data'),cc: '', ccdn: '', clear: ''}).done(function(data){
									if(data.trim()=='u'){ // success
										$('#modal').dialog("destroy");
										updatechoices();
										rowobject.effect('explode', {}, 500, function(){
											$(this).remove();
										});
									}else{ // failed to delete
										$('#modaltext').html('AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>');
										$('#modal').dialog('option','buttons',cancelbutton);
									}
								});
							}
						}
						var replacebutton={
							"<?php echo __("Replace"); ?>": function(){
								// send command to replace all connections with x
								$.post('',{cid: rowobject.find('div:nth-child(2) input').attr('data'),cc: '', ccdn: '', change: $('#modal select').val()}).done(function(data){
									if(data.trim()=='u'){ // success
										$('#modal').dialog("destroy");
										updatechoices();
										rowobject.effect('explode', {}, 500, function(){
											$(this).remove();
										});
										// Need to trigger a reload of any of the media types that had this 
										// color so they will display the new color
										$('#mediatypes > div ~ div:not(:last-child) input').val('').change();
									}else{ // failed to delete
										$('#modaltext').html('AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>');
										$('#modal').dialog('option','buttons',cancelbutton);
									}
								});
							}
						}
						var cancelbutton={
							"<?php echo __("Cancel"); ?>": function(){
								$(this).dialog("destroy");
							}
						}
<?php echo "						var modal=$('<div />', {id: 'modal', title: '".__("Code Delete Override")."'}).html('<div id=\"modaltext\">".__("This code is in use somewhere. You can either choose to clear all instances of this color being used or choose to have them replaced with another color.")." <select id=\"replaceme\"></select></div>').dialog({"; ?>
							dialogClass: 'no-close',
							appendTo: 'body',
							modal: true,
							buttons: $.extend({}, defaultbutton, cancelbutton)
						});
						var choices=$('div#mediatypes.table div:last-child div select').clone();
						choices.find('option').each(function(){
							if($(this).val()==rowobject.find('div:nth-child(2) input').attr('data')){$(this).remove();}
						});
						choices.change(function(){
							if($(this).val()==''){ // clear all
								modal.dialog('option','buttons',$.extend({}, defaultbutton, cancelbutton));
							}else{ // replace
								modal.dialog('option','buttons',$.extend({}, replacebutton, cancelbutton));
							}
						});
						modal.find($('#replaceme')).replaceWith(choices);
					}
				});
			}
		}
		var blankrow=$('<div />').html('<div><img src="images/del.gif"></div><div><input type="text" name="colorcode[]"></div><div><input type="text" name="ccdefaulttext[]"></div>');
		function bindrow(row){
			var addrem=row.find('div:first-child');
			var cc=row.find('div:nth-child(2) input');
			var ccdn=row.find('div:nth-child(3) input');
			if(cc.val().trim()!='' && addrem.attr('id')!='newline'){
				addrem.click(function(){
					removecolor(row,true);
				});
			}
			cc.keypress(function(event){
				if(event.keyCode==10 || event.keyCode==13){
					event.preventDefault();
					cc.change();
				}
			});
			ccdn.keypress(function(event){
				if(event.keyCode==10 || event.keyCode==13){
					event.preventDefault();
					ccdn.change();
				}
			});
			row.find('div > input').each(function(){
				// If a value changes then check it for conflicts, if no conflict update
				$(this).change(function(){
					if(cc.val().trim()!=''){
						$.post('',{cid: cc.attr('data'),cc: cc.val(), ccdn: ccdn.val()}).done(function(data){
							if(data.trim()=='f'){ // fail
								$.post('',{cid: cc.attr('data'),cc: cc.val(), ccdn: ccdn.val(),original:data.trim()}).done(function(jsondata){
									cc.val(jsondata.Name);
									ccdn.val(jsondata.DefaultNote);
								});
								cc.effect('highlight', {color: 'salmon'}, 1500);
								ccdn.effect('highlight', {color: 'salmon'}, 1500);
							}else if(data.trim()=='u'){ // updated
								cc.effect('highlight', {color: 'lightgreen'}, 2500);
								ccdn.effect('highlight', {color: 'lightgreen'}, 2500);
								// update media type color pick lists
								updatechoices();
							}else{ // created
								var newitem=blankrow.clone();
								newitem.find('div:nth-child(2) input').val(cc.val()).attr('data',data.trim());
								bindrow(newitem);
								row.before(newitem);
								newitem.find('div:nth-child(3) input').val(ccdn.val()).focus();
								if(addrem.attr('id')=='newline'){
									cc.val('');
									ccdn.val('');
								}else{
									row.remove();
								}
								// update media type color pick lists
								updatechoices();
							}
						});
					}else if(cc.val().trim()=='' && ccdn.val().trim()=='' && addrem.attr('id')!='newline'){
						// If both blanks are emptied of values and they were an existing data pair
						$.post('',{cid: cc.attr('data'),cc: cc.val(), ccdn: ccdn.val(),original:''}).done(function(jsondata){
							cc.val(jsondata.Name);
							ccdn.val(jsondata.DefaultNote);
						});
						cc.effect('highlight', {color: 'salmon'}, 1500);
						ccdn.effect('highlight', {color: 'salmon'}, 1500);
					}
				});
			});
		}
		$('#cablecolor > div ~ div > div:first-child').each(function(){
			if($(this).attr('id')=='newline'){
				var row=$(this).parent('div');
				$(this).click(function(){
					var newitem=blankrow.clone();
					newitem.find('div:first-child').click(function(){
						removecolor($(this).parent('div'),false);
					});
					bindrow(newitem);
					row.before(newitem);
				});
			}
			bindrow($(this).parent('div'));
		});

		// Reporting - Utilities

		$('input[id^="snmp"],input[id="cut"],input[id="dot"]').each(function(){
			var a=$(this);
			var icon=$('<span>',{style: 'float:right;margin-top:5px;'}).addClass('ui-icon').addClass('ui-icon-info');
			a.parent('div').append(icon);
			$(this).keyup(function(){
				var b=a.next('span');
				$.post('',{fe: $(this).val()}).done(function(data){
					if(data==1){
						a.effect('highlight', {color: 'lightgreen'}, 1500);
						b.addClass('ui-icon-circle-check').removeClass('ui-icon-info').removeClass('ui-icon-circle-close');
					}else{
						a.effect('highlight', {color: 'salmon'}, 1500);
						b.addClass('ui-icon-circle-close').removeClass('ui-icon-info').removeClass('ui-icon-circle-check');
					}
				});
			});
			$(this).trigger('keyup');
		});
	});

  </script>
</head>
<body>
<div id="header"></div>
<div class="page config">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',__("Data Center Configuration"),'</h3>
<h3>',__("Database Version"),': ',$config->ParameterArray["Version"],'</h3>
<div class="center"><div>
<form enctype="multipart/form-data" action="',$_SERVER["PHP_SELF"],'" method="POST">
   <input type="hidden" name="Version" value="',$config->ParameterArray["Version"],'">

	<div id="configtabs">
		<ul>
			<li><a href="#general">',__("General"),'</a></li>
			<li><a href="#style">',__("Style"),'</a></li>
			<li><a href="#email">',__("Email"),'</a></li>
			<li><a href="#reporting">',__("Reporting"),'</a></li>
			<li><a href="#tt">',__("ToolTips"),'</a></li>
			<li><a href="#cc">',__("Cabling"),'</a></li>
		</ul>
		<div id="general">
			<div class="table">
				<div>
					<div><label for="OrgName">',__("Organization Name"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["OrgName"],'" name="OrgName" value="',$config->ParameterArray["OrgName"],'"></div>
				</div>
				<div>
					<div><label for="Locale">',__("Locale"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["Locale"],'" name="Locale" value="',$config->ParameterArray["Locale"],'"></div>
				</div>
				<div>
					<div><label for="DefaultPanelVoltage">',__("Default Panel Voltage"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["DefaultPanelVoltage"],'" name="DefaultPanelVoltage" value="',$config->ParameterArray["DefaultPanelVoltage"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Time and Measurements"),'</h3>
			<div class="table" id="timeandmeasurements">
				<div>
					<div><label for="timezone">',__("Time Zone"),'</label></div>
					<div><input type="text" readonly="readonly" id="timezone" defaultvalue="',$config->defaults["timezone"],'" name="timezone" value="',$config->ParameterArray["timezone"],'"></div>
				</div>
				<div>
					<div><label for="mDate">',__("Manufacture Date"),'</label></div>
					<div><select id="mDate" name="mDate" defaultvalue="',$config->defaults["mDate"],'" data="',$config->ParameterArray["mDate"],'">
							<option value="blank"',(($config->ParameterArray["mDate"]=="blank")?' selected="selected"':''),'>',__("Blank"),'</option>
							<option value="now"',(($config->ParameterArray["mDate"]=="now")?' selected="selected"':''),'>',__("Now"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="wDate">',__("Warranty Date"),'</label></div>
					<div><select id="wDate" name="wDate" defaultvalue="',$config->defaults["wDate"],'" data="',$config->ParameterArray["wDate"],'">
							<option value="blank"',(($config->ParameterArray["wDate"]=="blank")?' selected="selected"':''),'>',__("Blank"),'</option>
							<option value="now"',(($config->ParameterArray["wDate"]=="now")?' selected="selected"':''),'>',__("Now"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="mUnits">',__("Measurement Units"),'</label></div>
					<div><select id="mUnits" name="mUnits" defaultvalue="',$config->defaults["mUnits"],'" data="',$config->ParameterArray["mUnits"],'">
							<option value="english"',(($config->ParameterArray["mUnits"]=="english")?' selected="selected"':''),'>',__("English"),'</option>
							<option value="metric"',(($config->ParameterArray["mUnits"]=="metric")?' selected="selected"':''),'>',__("Metric"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="PageSize">',__("Page Size"),'</label></div>
					<div><select id="PageSize" name="PageSize" defaultvalue="',$config->defaults["PageSize"],'" data="',$config->ParameterArray["PageSize"],'">
							<option value="A4"',(($config->ParameterArray["PageSize"]=="A4")?' selected="selected"':''),'>',__("A4"),'</option>
							<option value="A3"',(($config->ParameterArray["PageSize"]=="A3")?' selected="selected"':''),'>',__("A3"),'</option>
							<option value="Letter"',(($config->ParameterArray["PageSize"]=="Letter")?' selected="selected"':''),'>',__("Letter"),'</option>
							<option value="Legal"',(($config->ParameterArray["PageSize"]=="Legal")?' selected="selected"':''),'>',__("Legal"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Users"),'</h3>
			<div class="table">
				<div>
					<div><label for="ClassList">',__("Department Types"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["ClassList"],'" name="ClassList" value="',$classlist,'"></div>
				</div>
				<div>
					<div><label for="UserLookupURL">',__("User Lookup URL"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["UserLookupURL"],'" name="UserLookupURL" value="',$config->ParameterArray["UserLookupURL"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Rack Requests"),'</h3>
			<div class="table">
				<div>
					<div><label for="MailSubject">',__("Mail Subject"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["MailSubject"],'" name="MailSubject" value="',$config->ParameterArray["MailSubject"],'"></div>
				</div>
				<div>
					<div><label for="RackWarningHours">',__("Warning (Hours)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["RackWarningHours"],'" name="RackWarningHours" value="',$config->ParameterArray["RackWarningHours"],'"></div>
				</div>
				<div>
					<div><label for="RackOverdueHours">',__("Critical (Hours)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["RackOverdueHours"],'" name="RackOverdueHours" value="',$config->ParameterArray["RackOverdueHours"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Rack Usage"),'</h3>
			<div class="table" id="rackusage">
				<div>
					<div><label for="SpaceRed">',__("Space Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SpaceRed"],'" name="SpaceRed" value="',$config->ParameterArray["SpaceRed"],'"></div>
					<div></div>
					<div><label for="TemperatureRed">',__("Temperature Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["TemperatureRed"],'" name="TemperatureRed" value="',$config->ParameterArray["TemperatureRed"],'"></div>
				</div>
				<div>
					<div><label for="SpaceYellow">',__("Space Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SpaceYellow"],'" name="SpaceYellow" value="',$config->ParameterArray["SpaceYellow"],'"></div>
					<div></div>
					<div><label for="TemperatureYellow">',__("Temperature Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["TemperatureYellow"],'" name="TemperatureYellow" value="',$config->ParameterArray["TemperatureYellow"],'"></div>
				</div>
				<div>
					<div><label for="WeightRed">',__("Weight Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["WeightRed"],'" name="WeightRed" value="',$config->ParameterArray["WeightRed"],'"></div>
					<div></div>
					<div><label for="HumidityRedHigh">',__("High Humidity Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["HumidityRedHigh"],'" name="HumidityRedHigh" value="',$config->ParameterArray["HumidityRedHigh"],'"></div>
				</div>
				<div>
					<div><label for="WeightYellow">',__("Weight Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["WeightYellow"],'" name="WeightYellow" value="',$config->ParameterArray["WeightYellow"],'"></div>
					<div></div>
					<div><label for="HumidityRedLow">',__("Low Humidity Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["HumidityRedLow"],'" name="HumidityRedLow" value="',$config->ParameterArray["HumidityRedLow"],'"></div>
				</div>
				<div>
					<div><label for="PowerRed">',__("Power Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PowerRed"],'" name="PowerRed" value="',$config->ParameterArray["PowerRed"],'"></div>
					<div></div>
					<div><label for="HumidityYellowHigh">',__("High Humidity Caution"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["HumidityYellowHigh"],'" name="HumidityYellowHigh" value="',$config->ParameterArray["HumidityYellowHigh"],'"></div>
				</div>
				<div>
					<div><label for="PowerYellow">',__("Power Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PowerYellow"],'" name="PowerYellow" value="',$config->ParameterArray["PowerYellow"],'"></div>
					<div></div>
					<div><label for="HumidityYellowLow">',__("Low Humidity Caution"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["HumidityYellowLow"],'" name="HumidityYellowLow" value="',$config->ParameterArray["HumidityYellowLow"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Virtual Machines"),'</h3>
			<div class="table" id="rackusage">
				<div>
					<div><label for="VMExpirationTime">',__("Expiration Time (Days)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["VMExpirationTime"],'" name="VMExpirationTime" value="',$config->ParameterArray["VMExpirationTime"],'"></div>
				</div>
			</div> <!-- end table -->
			',$tzmenu,'
		</div>
		<div id="style">
			<h3>',__("Racks & Maps"),'</h3>
			<div class="table">
				<div>
					<div><label for="CriticalColor">',__("Critical Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="CriticalColor" value="',$config->ParameterArray["CriticalColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["CriticalColor"]),'</span></div>
				</div>
				<div>
					<div><label for="CautionColor">',__("Caution Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="CautionColor" value="',$config->ParameterArray["CautionColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["CautionColor"]),'</span></div>
				</div>
				<div>
					<div><label for="GoodColor">',__("Good Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="GoodColor" value="',$config->ParameterArray["GoodColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["GoodColor"]),'</span></div>
				</div>
				<div>
					<div>&nbsp;</div>
					<div></div>
					<div></div>
					<div></div>
				</div>
				<div>
					<div><label for="ReservedColor">',__("Reserved Devices"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="ReservedColor" value="',$config->ParameterArray["ReservedColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["ReservedColor"]),'</span></div>
				</div>
				<div>
					<div><label for="FreeSpaceColor">',__("Unused Spaces"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="FreeSpaceColor" value="',$config->ParameterArray["FreeSpaceColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["FreeSpaceColor"]),'</span></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Devices"),'</h3>
			<div class="table">
				<div>
					<div><label for="LabelCase">',__("Device Labels"),'</label></div>
					<div><select id="LabelCase" name="LabelCase" defaultvalue="',$config->defaults["LabelCase"],'" data="',$config->ParameterArray["LabelCase"],'">
							<option value="upper">',transform(__("Uppercase"),'upper'),'</option>
							<option value="lower">',transform(__("Lowercase"),'lower'),'</option>
							<option value="initial">',transform(__("Initial caps"),'initial'),'</option>
							<option value="none">',__("Don't touch my labels"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Site"),'</h3>
			<div class="table">
				<div>
					<div><label for="HeaderColor">',__("Header Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="HeaderColor" value="',$config->ParameterArray["HeaderColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["HeaderColor"]),'</span></div>
				</div>
				<div>
					<div><label for="BodyColor">',__("Body Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="BodyColor" value="',$config->ParameterArray["BodyColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["BodyColor"]),'</span></div>
				</div>
				<div>
					<div><label for="LinkColor">',__("Link Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="LinkColor" value="',$config->ParameterArray["LinkColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["LinkColor"]),'</span></div>
				</div>
				<div>
					<div><label for="VisitedLinkColor">',__("Viewed Link Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="VisitedLinkColor" value="',$config->ParameterArray["VisitedLinkColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["VisitedLinkColor"]),'</span></div>
				</div>
			</div> <!-- end table -->
		</div>
		<div id="email">
			<div class="table">
				<div>
					<div><label for="SMTPServer">',__("SMTP Server"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPServer"],'" name="SMTPServer" value="',$config->ParameterArray["SMTPServer"],'"></div>
				</div>
				<div>
					<div><label for="SMTPPort">',__("SMTP Port"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPPort"],'" name="SMTPPort" value="',$config->ParameterArray["SMTPPort"],'"></div>
				</div>
				<div>
					<div><label for="SMTPHelo">',__("SMTP Helo"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPHelo"],'" name="SMTPHelo" value="',$config->ParameterArray["SMTPHelo"],'"></div>
				</div>
				<div>
					<div><label for="SMTPUser">',__("SMTP Username"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPUser"],'" name="SMTPUser" value="',$config->ParameterArray["SMTPUser"],'"></div>
				</div>
				<div>
					<div><label for="SMTPPassword">',__("SMTP Password"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPPassword"],'" name="SMTPPassword" value="',$config->ParameterArray["SMTPPassword"],'"></div>
				</div>
				<div>
					<div><label for="MailToAddr">',__("Mail To"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["MailToAddr"],'" name="MailToAddr" value="',$config->ParameterArray["MailToAddr"],'"></div>
				</div>
				<div>
					<div><label for="MailFromAddr">',__("Mail From"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["MailFromAddr"],'" name="MailFromAddr" value="',$config->ParameterArray["MailFromAddr"],'"></div>
				</div>
				<div>
					<div><label for="ComputerFacMgr">',__("Facility Manager"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["ComputerFacMgr"],'" name="ComputerFacMgr" value="',$config->ParameterArray["ComputerFacMgr"],'"></div>
				</div>
				<div>
					<div><label for="FacMgrMail">',__("Facility Manager Email"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["FacMgrMail"],'" name="FacMgrMail" value="',$config->ParameterArray["FacMgrMail"],'"></div>
				</div>
			</div> <!-- end table -->
		</div>
		<div id="reporting">
			<div id="imageselection" title="Image file selector">
				',$imageselect,'
			</div>
			<div class="table">
				<div>
					<div><label for="annualCostPerUYear">',__("Annual Cost Per Rack Unit (Year)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["annualCostPerUYear"],'" name="annualCostPerUYear" value="',$config->ParameterArray["annualCostPerUYear"],'"></div>
				</div>
				<div>
					<div><label for="annualCostPerWattYear">',__("Annual Cost Per Watt (Year)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["annualCostPerWattYear"],'" name="annualCostPerWattYear" value="',$config->ParameterArray["annualCostPerWattYear"],'"></div>
				</div>
				<div>
					<div><label for="PDFLogoFile">',__("Logo file for headers"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PDFLogoFile"],'" name="PDFLogoFile" value="',$config->ParameterArray["PDFLogoFile"],'"></div>
				</div>
				<div>
					<div><label for="PDFfont">',__("Font"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PDFfont"],'" name="PDFfont" value="',$config->ParameterArray["PDFfont"],'"></div>
				</div>
				<div>
					<div><label for="NewInstallsPeriod">',__("New Installs Period"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["NewInstallsPeriod"],'" name="NewInstallsPeriod" value="',$config->ParameterArray["NewInstallsPeriod"],'"></div>
				</div>
				<div>
					<div><label for="InstallURL">',__("Base URL for install"),'</label></div>
					<div><input type="text" defaultvalue="',$href,'" name="InstallURL" value="',$config->ParameterArray["InstallURL"],'"></div>
				</div>
				<div>
					<div><label for="SNMPCommunity">',__("Default SNMP Community"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SNMPCommunity"],'" name="SNMPCommunity" value="',$config->ParameterArray["SNMPCommunity"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Capacity Reporting"),'</h3>
			<div class="table">
				<div>
					<div><label for="NetworkCapacityReportOptIn">',__("Switches"),'</label></div>
					<div>
						<select id="NetworkCapacityReportOptIn" defaultvalue="',$config->defaults["NetworkCapacityReportOptIn"],'" name="NetworkCapacityReportOptIn" data="',$config->ParameterArray["NetworkCapacityReportOptIn"],'">
							<option value="OptIn">',__("OptIn"),'</option>
							<option value="OptOut">',__("OptOut"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="NetworkThreshold">',__("Switch Capacity Threshold"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["NetworkThreshold"],'" name="NetworkThreshold" value="',$config->ParameterArray["NetworkThreshold"],'"></div>
				</div>
			</div>
			<h3>',__("Utilities"),'</h3>
			<div class="table">
				<div>
					<div><label for="snmpget">',__("snmpget"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["snmpget"],'" name="snmpget" value="',$config->ParameterArray["snmpget"],'"></div>
				</div>
				<div>
					<div><label for="snmpwalk">',__("snmpwalk"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["snmpwalk"],'" name="snmpwalk" value="',$config->ParameterArray["snmpwalk"],'"></div>
				</div>
				<div>
					<div><label for="cut">',__("cut"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["cut"],'" name="cut" value="',$config->ParameterArray["cut"],'"></div>
				</div>
				<div>
					<div><label for="dot">',__("dot"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["dot"],'" name="dot" value="',$config->ParameterArray["dot"],'"></div>
				</div>
			</div> <!-- end table -->
		</div>
		<div id="tt">
			<div class="table">
				<div>
					<div><label for="ToolTips">',__("Cabinet ToolTips"),'</label></div>
					<div><select id="ToolTips" name="ToolTips" defaultvalue="',$config->defaults["ToolTips"],'" data="',$config->ParameterArray["ToolTips"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<br>
			',$tooltip,'
			<br>
			<div class="table">
				<div>
					<div><label for="CDUToolTips">',__("CDU ToolTips"),'</label></div>
					<div><select id="CDUToolTips" name="CDUToolTips" defaultvalue="',$config->defaults["CDUToolTips"],'" data="',$config->ParameterArray["CDUToolTips"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<br>
			',$cdutooltip,'
		</div>
		<div id="cc">
			<h3>',__("Media Types"),'</h3>
			<div class="table">
				<div>
					<!-- <div><label for="MediaEnforce">',__("Media Type Matching"),'</label></div>
					<div><select id="MediaEnforce" name="MediaEnforce" defaultvalue="',$config->defaults["MediaEnforce"],'" data="',$config->ParameterArray["MediaEnforce"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enforce"),'</option>
						</select>
					</div> -->
					<input type="hidden" name="MediaEnforce" value="disabled">
				</div>
			</div> <!-- end table -->
			<br>
			<div class="table" id="mediatypes">
				<div>
					<div></div>
					<div>',__("Media Type"),'</div>
					<div>',__("Default Color"),'</div>
				</div>
				',$mediatypes,'
				<div>
					<div id="newline"><img title="',__("Add new row"),'" src="images/add.gif"></div>
					<div><input type="text" name="mediatype[]"></div>
					<div>',$colorselector,'</div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Cable Colors"),'</h3>
			<div class="table" id="cablecolor">
				<div>
					<div></div>
					<div>',__("Color"),'</div>
					<div>',__("Default Note"),'</div>
				</div>
				',$cablecolors,'
				<div>
					<div id="newline"><img title="',__("Add new row"),'" src="images/add.gif"></div>
					<div><input type="text" name="colorcode[]"></div>
					<div><input type="text" name="ccdefaulttext[]"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Connection Pathing"),'</h3>
			<div class="table" id="pathweights">
				<div>
					<div><label for="path_weight_cabinet">',__("Cabinet Weight"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["path_weight_cabinet"],'" name="path_weight_cabinet" value="',$config->ParameterArray["path_weight_cabinet"],'"></div>
				</div>
				<div>
					<div><label for="path_weight_rear">',__("Weight for rear connections between panels"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["path_weight_rear"],'" name="path_weight_rear" value="',$config->ParameterArray["path_weight_rear"],'"></div>
				</div>
				<div>
					<div><label for="path_weight_row">',__("Weight for patches in the same row"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["path_weight_row"],'" name="path_weight_row" value="',$config->ParameterArray["path_weight_row"],'"></div>
				</div>
			</div> <!-- end table -->
		</div>
	</div>';

?>

<div class="table centermargin">
<div>
	<div>&nbsp;</div>
</div>
<div>
   <div><input type="submit" name="action" value="Update"></div>
</div>
</div> <!-- END div.table -->
</form>
</div>
</div>
   <a href="index.php">Return to Main Menu</a>
  </div>
  </div>
</body>
</html>
