<div id="sidebar">
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="label">
<?php echo'
<label for="searchname">',__("Search by Name:"),'</label><br>
<input class="search" id="searchname" name="search"><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
</form>
<span id="advsrch">',__("Advanced"),'</span>
<br>
<form action="search.php" method="post" class="hide advsearch">
<br>
<label for="searchadv">',__("Advanced Search:"),'</label><br>
<input class="search" id="searchadv" name="search"><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
<select name="key">
	<option value="label">',__("Label"),'</option>
	<option value="ctag">',__("Custom Tag"),'</option>
	<option value="serial">',__("Serial Number"),'</option>
	<option value="asset">',__("Asset Tag"),'</option>
	<option value="owner">',__("Owner"),'</option>
</select>';
?>
<div class="ui-icon ui-icon-close"></div>
</form>
  <script type="text/javascript">
	function addlookup(inputobj,lookuptype){
		// clear any existing autocompletes
		if(inputobj.hasClass('ui-autocomplete-input')){inputobj.autocomplete('destroy');}
		// clear out previous search arrows
		inputobj.next('.text-arrow').remove();
		// Position the arrow
		var inputpos=inputobj.position();
		var arrow=$('<div />').addClass('text-arrow');
		arrow.css({'top': inputpos.top+'px', 'left': inputpos.left+inputobj.width()-(arrow.width()/2)});
		arrow.click(function(){
			inputobj.autocomplete("search", "");
		});
		// add the autocomplete
		inputobj.autocomplete({
			minLength: 0,
			autoFocus: true,
			source: function(req, add){
				$.getJSON('scripts/ajax_search.php?'+lookuptype, {q: req.term}, function(data){
					var suggestions=[];
					$.each(data, function(i,val){
						suggestions.push(val);
					});
					add(suggestions);
				});
			},
			open: function(){
				$(this).autocomplete("widget").css({'width': inputobj.width()+6+'px'});
			}
		}).next().after(arrow);
	}
	addlookup($('#searchname'),'name');
	$('#searchadv ~ select[name="key"]').change(function(){
		addlookup($('#searchadv'),$(this).val())
	}).height($('#searchadv').outerHeight());
	$('#advsrch, #searchadv ~ .ui-icon.ui-icon-close').click(function(){
		var here=$(this).position();
		$('#searchadv, #searchname').val('');
		$('#searchadv').parents('form').height(here.top).toggle('slide',200);
		if($('#searchadv').hasClass('ui-autocomplete-input')){$('#searchadv').autocomplete('destroy');}
		if($(this).text()=='<?php echo __("Advanced");?>'){$(this).text('<?php echo __("Basic");?>');$('#searchadv ~ select[name="key"]').trigger('change');}else{$(this).text('<?php echo __("Advanced");?>');}
	});
  </script>
  <script type="text/javascript" src="scripts/mktree.js"></script> 
  <script type="text/javascript" src="scripts/konami.js"></script> 
	<hr>
	<ul class="nav">
<?php
echo '	<a href="reports.php"><li>',__("Reports"),'</li></a>';

	if ( $user->RackRequest ) {
		echo '		<a href="rackrequest.php"><li>',__("Rack Request Form"),'</li></a>';
	}
	if ( $user->ContactAdmin ) {
		echo '		<a href="contacts.php"><li>',__("Contact Administration"),'</li></a>
		<a href="departments.php"><li>',__("Dept. Administration"),'</li></a>
		<a href="timeperiods.php"><li>',__("Time Periods"),'</li></a>
		<a href="escalations.php"><li>',__("Escalation Rules"),'</li></a>';
	}
	if ( $user->WriteAccess ) {
		echo '<a href="cabinets.php"><li>',__("Edit Cabinets"),'</li></a>
		<a href="device_templates.php"><li>',__("Edit Device Templates"),'</li></a>';
	}
	if ( $user->SiteAdmin ) {
		echo '		<a href="usermgr.php"><li>',__("Manage Users"),'</li></a>
		<a href="supplybin.php"><li>',__("Manage Supply Bins"),'</li></a>
		<a href="supplies.php"><li>',__("Manage Supplies"),'</li></a>
		<a href="container.php"><li>',_("Edit Containers"),'</li></a>
		<a href="datacenter.php"><li>',__("Edit Data Centers"),'</li></a>
		<a href="power_source.php"><li>',__("Edit Power Sources"),'</li></a>
		<a href="power_panel.php"><li>',__("Edit Power Panels"),'</li></a>
		<a href="device_manufacturers.php"><li>',__("Edit Manufacturers"),'</li></a>
		<a href="cdu_templates.php"><li>',__("Edit CDU Templates"),'</li></a>
		<a href="configuration.php"><li>',__("Edit Configuration"),'</li></a>';
	}

	print "	</ul>
	<hr>
	<a href=\"index.php\">".__("Home")."</a>\n";

	$lang=GetValidTranslations();
	//strip any encoding info and keep just the country lang pair
	$locale=explode(".",$locale);
	$locale=$locale[0];
	echo '	<div class="langselect">
		<label for="language">Language</label>
		<select name="language" id="language" current="'.$locale.'">';
		foreach($lang as $cc => $translatedname){
			// This is for later. For now just display list
			//$selected=""; //
			if($locale==$cc){$selected=" selected";}else{$selected="";}
			print "\t\t\t<option value=\"$cc\"$selected>$translatedname</option>";
		}
	echo '		</select>
	</div>';

	$container = new Container();
	echo $container->BuildMenuTree( $facDB );
	
?>
<script type="text/javascript">
$("#sidebar .nav a").each(function(){
	if($(this).attr("href")=="<?php echo basename($_SERVER['PHP_SELF']);?>"){
		$(this).children().addClass("active");
	}
});
function resize(){
	var pw=$('html').innerWidth(),pnw=$('#pandn').width(),hw=$('#header').width(),maindiv=$('div.main').width(),sbw=$('#sidebar').width(),width;
	var mw=$('div.left').width()+$('div.right').width()+22;// 10 for padding, 2 for border, 10 for magic
	if((maindiv+sbw)<pw){
		$('div.main').width(pw-sbw-40); // 40 is the magic number
	}
/*	console.log(maindiv+sbw+'<'+pw);
	console.log('page width: '+pw);
	console.log('power width: '+pnw);
	console.log('header width: '+hw);
	console.log('main.div width: '+maindiv);
	console.log('sidebar width: '+sbw);
	console.log('left + right width:'+mw); */
	mw=(mw>maindiv)?mw:maindiv+20;
	if(mw>pnw){width=sbw+mw;}else{width=pnw+mw;}
	width=(width<hw)?hw:width;
	$('div.page').width(width);
}
$(document).ready(function(){
	resize();
	var top = (($("#header").height() / 2)-($(".langselect").height() / 2));
	$(".langselect").css({"top": top+"px", "right": "40px", "z-index": "99", "left": "auto"}).appendTo("#header");
	$("#language").change(function(){
		$.ajax({
			type: 'POST',
			url: 'scripts/ajax_language.php',
			data: 'sl='+$("#language").val(),
			success: function(){
				// new cookie was set. reload the page for the translation.
				location.reload();
			}
		});

	});
});

</script>

</div>


<noscript>
<div id="gandalf"><div>
                                  .--.
                                 ,' |
                                 '  |
                               ,'    \
  __   __                     .'      |
  \ \ / /__ _  _              '       |
   \ V / _ \ || |            |        |              -.
    |_|\___/\_,_|         ___'        |             / |--
                     _,,-'            '--'''--._    | || |.-'\
                    /        ..._______         `:. |       ,'
                   | _,.,---'|         `.`^`--..   '.      /
 ___ _         _ _ `'      | | <o>   <o> | |    ``''`     /
/ __| |_  __ _| | |      .' ,;      \    |  \      |     /
\__ \ ' \/ _` | | |     /    \    (__)   /   `.    |    |
|___/_||_\__,_|_|_|  ,-'      | ________       \   ;    |
                    |         )/ _.--.__ \     /  /    /
                     `-._   ,./_/ `--'  \_\   /   |__ /
    _  _     _           '.'|              | '  ,'   `
   | \| |___| |_            |              |    (      \
   | .` / _ \  _|            |             |    (       \
   |_|\_\___/\__|             \            |    '`.._   /
                              |            |    /  / `./
                              \           /    |   |
   ___                         `|        |     |  /
  | _ \__ _ ______              |       /      /  |
  |  _/ _` (_-<_-<              \   _.-`      |  /
  |_| \__,_/__/__/               \./          |  |
                                              /  /
                                             |   |
                                             /  /
                                            /   |
                                            |  |
                                           /   /
                                           |  .'
                                           |  |
                                          |  .'
                                          '  |
                                          | |
                                         /  /
                                        |_,'

</div><p>Please enable javascript to continue</p></div>

</noscript>
