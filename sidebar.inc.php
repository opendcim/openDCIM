<div id="sidebar">
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="label">
<label for="searchname"><?php print _("Search by Name:"); ?></label><br>
<textarea id="searchname" name="search" rows=1 cols=30></textarea><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
</form>
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="serial">
<label for="searchsn"><?php print _("Search by SN:"); ?></label><br>
<textarea id="searchsn" name="search" rows=1 cols=30></textarea><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
</form>
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="asset">
<label for="searchtag"><?php print _("Search by Asset Tag:"); ?></label><br>
<textarea id="searchtag" name="search" rows=1 cols=30></textarea><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
</form>
  <script type="text/javascript" src="scripts/jquery.TextExt.js"></script>
  <script type="text/javascript">
	$('#searchname')
		.textext({
			plugins : 'autocomplete ajax arrow',
			ajax : {
				url : 'scripts/ajax_search.php?name',
				dataType : 'json',
				cacheResults : false,
			}
		})
	;
	$('#searchsn')
		.textext({
			plugins : 'autocomplete ajax arrow',
			ajax : {
				url : 'scripts/ajax_search.php?serial',
				dataType : 'json',
				cacheResults : false,
			}
		})
	;
	$('#searchtag')
		.textext({
			plugins : 'autocomplete ajax arrow',
			ajax : {
				url : 'scripts/ajax_search.php?tag',
				dataType : 'json',
				cacheResults : false,
			}
		})
	;
  </script>
  <script type="text/javascript" src="scripts/mktree.js"></script> 
	<hr>
	<ul class="nav">
<?php
echo '	<a href="reports.php"><li>',_("Reports"),'</li></a>';

	if ( $user->RackRequest ) {
		echo '		<a href="rackrequest.php"><li>',_("Rack Request Form"),'</li></a>';
	}
	if ( $user->ContactAdmin ) {
		echo '		<a href="contacts.php"><li>',_("Contact Administration"),'</li></a>
		<a href="departments.php"><li>',_("Dept. Administration"),'</li></a>
		<a href="timeperiods.php"><li>',_("Time Periods"),'</li></a>
		<a href="escalations.php"><li>',_("Escalation Rules"),'</li></a>';
	}
	if ( $user->WriteAccess ) {
		echo '<a href="cabinets.php"><li>',_("Edit Cabinets"),'</li></a>
		<a href="device_classes.php"><li>',_("Edit Templates"),'</li></a>';
	}
	if ( $user->SiteAdmin ) {
		echo '		<a href="usermgr.php"><li>',_("Manage Users"),'</li></a>
		<a href="supplybin.php"><li>',_("Manage Supply Bins"),'</li></a>
		<a href="supplies.php"><li>',_("Manage Supplies"),'</li></a>
		<a href="datacenter.php"><li>',_("Edit Data Centers"),'</li></a>
		<a href="power_source.php"><li>',_("Edit Power Sources"),'</li></a>
		<a href="power_panel.php"><li>',_("Edit Power Panels"),'</li></a>
		<a href="manufacturers.php"><li>',_("Edit Manufacturers"),'</li></a>
		<a href="configuration.php"><li>',_("Edit Configuration"),'</li></a>';
	}

	print "	</ul>
	<hr>
	<a href=\"index.php\">"._("Home")."</a>\n";

	$lang=GetValidTranslations();
	echo '	<div class="langselect">
		<label for="language">Language</label>
		<select name="language" id="language" current="'.$locale.'">';
		foreach($lang as $cc => $translatedname){
			// This is for later. For now just display list
			$selected=""; //if($locale==$cc){$selected=" selected";}else{$selected="";}
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
				document.location.href=$(location).attr('href');
			}
		});

	});
});

</script>

</div>
