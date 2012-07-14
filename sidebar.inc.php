<div id="sidebar">
<input type="hidden" name="server" value="<?php echo $_SERVER['SERVER_ADDR']; ?>">
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="label">
<label for="searchname">Search by Name:</label><br>
<textarea id="searchname" name="search" rows=1 cols=30></textarea><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
</form>
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="serial">
<label for="searchsn">Search by SN:</label><br>
<textarea id="searchsn" name="search" rows=1 cols=30></textarea><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
</form>
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="asset">
<label for="searchtag">Search by Asset Tag:</label><br>
<textarea id="searchtag" name="search" rows=1 cols=30></textarea><button class="iebug" type="submit"><img src="css/searchbutton.png" alt="search"></button>
</form>
  <script type="text/javascript" src="scripts/jquery.TextExt.js"></script>
  <script type="text/javascript">
	$('#searchname')
		.textext({
			plugins : 'autocomplete ajax arrow',
			ajax : {
				url : 'json.php?name',
				dataType : 'json',
				cacheResults : false,
			}
		})
	;
	$('#searchsn')
		.textext({
			plugins : 'autocomplete ajax arrow',
			ajax : {
				url : 'json.php?serial',
				dataType : 'json',
				cacheResults : false,
			}
		})
	;
	$('#searchtag')
		.textext({
			plugins : 'autocomplete ajax arrow',
			ajax : {
				url : 'json.php?tag',
				dataType : 'json',
				cacheResults : false,
			}
		})
	;
  </script>
  <script type="text/javascript" src="scripts/mktree.js"></script> 
	<hr>
	<ul class="nav">
	<a href="reports.php"><li>Reports</li></a>
<?php
	if ( $user->RackRequest ) {
		echo '		<a href="rackrequest.php"><li>Rack Request Form</li></a>';
	}
	if ( $user->ContactAdmin ) {
		echo '		<a href="contacts.php"><li>Contact Administration</li></a>
		<a href="departments.php"><li>Dept. Administration</li></a>
		<a href="timeperiods.php"><li>Time Periods</li></a>
		<a href="escalations.php"><li>Escalation Rules</li></a>';
	}
	if ( $user->WriteAccess ) {
		echo '<a href="cabinets.php"><li>Edit Cabinets</li></a>
		<a href="device_classes.php"><li>Edit Templates</li></a>';
	}
	if ( $user->SiteAdmin ) {
		echo '		<a href="usermgr.php"><li>Manage Users</li></a>
		<a href="datacenter.php"><li>Edit Data Centers</li></a>
		<a href="powersource.php"><li>Edit Power Sources</li></a>
		<a href="panelmgr.php"><li>Edit Power Panels</li></a>
		<a href="manufacturers.php"><li>Edit Manufacturers</li></a>
		<a href="configuration.php"><li>Edit Configuration</li></a>';
	}

	print "	</ul>
	<hr>
	<a href=\"index.php\">Home</a>\n";
	
	$menucab = new Cabinet();
	echo $menucab->BuildCabinetTree( $facDB );
?>
<script type="text/javascript">
$("#sidebar .nav a").each(function(){
	if($(this).attr("href")=="<?php echo basename($_SERVER['PHP_SELF']);?>"){
		$(this).children().addClass("active");
	}
});

</script>

</div>
