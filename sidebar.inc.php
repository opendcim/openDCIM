<div id="sidebar">
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="label">
<label for="searchname"><?php print __("Search by Name:"); ?></label><br>
<input class="search" id="searchname" name="search"><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
</form>
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="ctag">
<label for="searchsn"><?php print __("Search by Custom Tag:"); ?></label><br>
<input class="search" id="searchctag" name="search"><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
</form>
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="serial">
<label for="searchsn"><?php print __("Search by SN:"); ?></label><br>
<input class="search" id="searchsn" name="search"><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
</form>
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="asset">
<label for="searchtag"><?php print __("Search by Asset Tag:"); ?></label><br>
<input class="search" id="searchtag" name="search"><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
</form>
  <script type="text/javascript">
	$('#searchname').autocomplete({
		minLength: 0,
		autoFocus: true,
		source: function(req, add){
			$.getJSON('scripts/ajax_search.php?name', {q: req.term}, function(data){
				var suggestions=[];
				$.each(data, function(i,val){
					suggestions.push(val);
				});
				add(suggestions);
			});
		},
		open: function(){
			$(this).autocomplete("widget").css({'width': $('#searchname').width()+6+'px'});
		}
	}).next().after('<div class="text-arrow"></div>');
	$('#searchsn').autocomplete({
		minLength: 0,
		autoFocus: true,
		source: function(req, add){
			$.getJSON('scripts/ajax_search.php?serial', {q: req.term}, function(data){
				var suggestions=[];
				$.each(data, function(i,val){
					suggestions.push(val);
				});
				add(suggestions);
			});
		},
		open: function(){
			$(this).autocomplete("widget").css({'width': $('#searchname').width()+6+'px'});
		}
	}).next().after('<div class="text-arrow"></div>');
	$('#searchtag').autocomplete({
		minLength: 0,
		autoFocus: true,
		source: function(req, add){
			$.getJSON('scripts/ajax_search.php?tag', {q: req.term}, function(data){
				var suggestions=[];
				$.each(data, function(i,val){
					suggestions.push(val);
				});
				add(suggestions);
			});
		},
		open: function(){
			$(this).autocomplete("widget").css({'width': $('#searchname').width()+6+'px'});
		}
	}).next().after('<div class="text-arrow"></div>');
	$('.text-arrow').each(function(){
		var inputpos=$(this).prev().prev().position();
		$(this).css({'top': inputpos.top+'px', 'left': inputpos.left+$(this).prev().prev().width()-($(this).width()/2)});
		$(this).click(function(){
			$(this).prev().prev().autocomplete("search", "");
		});
	});
  </script>
  <script type="text/javascript" src="scripts/mktree.js"></script> 
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

	$menucab = new Cabinet();
	echo $menucab->BuildCabinetTree( $facDB );
?>
<script type="text/javascript">
$("#sidebar .nav a").each(function(){
	if($(this).attr("href")=="<?php echo basename($_SERVER['PHP_SELF']);?>"){
		$(this).children().addClass("active");
	}
});
$(document).ready(function(){
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
