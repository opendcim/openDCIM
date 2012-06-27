<div id="sidebar">
<input type="hidden" name="server" value="<?php echo $_SERVER['SERVER_ADDR']; ?>">
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="label">
<label for="searchname">Search by Name:</label><br>
<textarea id="searchname" name="search" rows=1></textarea><button class="iebug" type="submit"><img src="css/searchbutton.png"></button>
</form>
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="serial">
<label for="searchsn">Search by SN:</label><br>
<textarea id="searchsn" name="search" rows=1></textarea><button class="iebug" type="submit"><img src="css/searchbutton.png"></button>
</form>
<br>
<form action="search.php" method="post">
<input type="hidden" name="key" value="asset">
<label for="searchtag">Search by Asset Tag:</label><br>
<textarea id="searchtag" name="search" rows=1></textarea><button class="iebug" type="submit"><img src="css/searchbutton.png"></button>
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
	<form action="" method="POST">
	<input type=BUTTON onClick="location='reports.php'" value="Reports" style="height: 25px; width: 160px;">
<?php
	if ( $user->RackRequest ) {
		print "		<input type=BUTTON onClick=\"location='rackrequest.php'\" value=\"Rack Request Form\" style=\"height: 25px; width: 160px;\">\n";
	}

	if ( $user->ContactAdmin ) {
		print "		<input type=BUTTON onClick=\"location='contacts.php'\" value=\"Contact Administration\" style=\"height: 25px; width: 160px;\">\n
		<input type=BUTTON onClick=\"location='departments.php'\" value=\"Dept. Administration\" style=\"height: 25px; width: 160px;\">\n
		<input type=BUTTON onClick=\"location='timeperiods.php'\" value=\"Time Periods\" style=\"height: 25px; width: 160px;\">\n
		<input type=BUTTON onClick=\"location='escalations.php'\" value=\"Escalation Rules\" style=\"height: 25px; width: 160px;\">\n";
	}

	if ( $user->SiteAdmin ) {
		print "		<input type=BUTTON onClick=\"location='usermgr.php'\" value=\"Manage Users\" style=\"height: 25px; width: 160px;\">
	  	<input type=BUTTON onClick=\"location='datacenter.php'\" value=\"Edit Data Centers\" style=\"height: 25px; width: 160px;\">
	  	<input type=BUTTON onClick=\"location='cabinets.php'\" value=\"Edit Cabinets\" style=\"height: 25px; width: 160px;\">
	  	<input type=BUTTON onClick=\"location='powersource.php'\" value=\"Edit Power Sources\" style=\"height: 25px; width: 160px;\">
	  	<input type=BUTTON onClick=\"location='panelmgr.php'\" value=\"Edit Power Panels\" style=\"height: 25px; width: 160px;\">
	  	<input type=BUTTON onClick=\"location='device_classes.php'\" value=\"Edit Templates\" style=\"height: 25px; width: 160px;\">
	  	<input type=BUTTON onClick=\"location='manufacturers.php'\" value=\"Edit Manufacturers\" style=\"height: 25px; width: 160px;\">
		<input type=BUTTON onClick=\"location='configuration.php'\" value=\"Edit Configuration\" style=\"height: 25px; width: 160px;\">\n";
	}

	print "	</form>
	<hr>
	<a href=\"index.php\">Home</a>\n";
	
	$menucab = new Cabinet();
	echo $menucab->BuildCabinetTree( $facDB );
?>
</div>
