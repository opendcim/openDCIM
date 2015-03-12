<?php

	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );
	
	$t = new DeviceTemplate();
	
	$tList = $t->GetTemplateShareList();
	
	$c = curl_init('https://repository.opendcim.org/api/devicetemplate');
	
	curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, 30 );
	curl_setopt( $c, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
	curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $c, CURLOPT_SSLVERIFYPEER, true );
	curl_setopt( $c, CURLOPT_FOLLOWLOCATION, 1 );
	curl_setopt( $c, CURLOPT_HTTPHEADER, array( "UserID: scott@themillikens.com", "APIKey: e9afc69c3df5c8d70647150cf1ad9fc0" ) );
	
	foreach ( $tList as $temp ) {
		$postData = http_build_query( $temp );
		
		curl_setopt( $c, CURLOPT_POSTFIELDS, $postData );
		
		$result = curl_exec( $c );
		print_r( curl_getinfo($c) );
	}
	
	curl_close( $c );
?>