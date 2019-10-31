<?php
	define( "VERSION", "19.01" );
	$version="19.01";

	if ( VERSION == "19.01" )
		echo "literal match";
	else
		echo "literal mismatch";

	if ( VERSION == $version )
		echo "string match";
	else
		echo "string mismatch";

?>
