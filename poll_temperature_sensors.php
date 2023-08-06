<?php
	require("db.inc.php");
	require("facilities.inc.php");

	if ( php_sapi_name() != "cli" ) {
	echo "This script may only be run from the command line.";
	header( "Refresh: 5; url=" . redirect());    
	}
  	
	Device::UpdateSensors();
?>
