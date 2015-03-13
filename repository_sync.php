<?php

	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );
	
	$t = new DeviceTemplate();
	
	$tList = $t->GetTemplateShareList();
	
	$c = curl_init('https://repository.opendcim.org/api/devicetemplate');
	
	curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, 30 );
	curl_setopt( $c, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
	curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $c, CURLOPT_HEADER, true );
	curl_setopt( $c, CURLOPT_COOKIEFILE, "/tmp/repocookies.txt" );
	curl_setopt( $c, CURLOPT_COOKIEJAR, "/tmp/repocookies.txt" );
	curl_setopt( $c, CURLOPT_CUSTOMREQUEST, "PUT" );
	curl_setopt( $c, CURLOPT_FOLLOWLOCATION, 1 );
	curl_setopt( $c, CURLOPT_HTTPHEADER, array( "UserID: scott@themillikens.com", "APIKey: e9afc69c3df5c8d70647150cf1ad9fc0" ) );
	
	foreach ( $tList as $temp ) {
		$postData = http_build_query( $temp );
		
		curl_setopt( $c, CURLOPT_POSTFIELDS, $postData );
		
		$result = curl_exec( $c );
		$jr = json_decode( $result ) ;
		
		$postData = array();
		if ( $temp->FrontPictureFile != "" ) {
			$postData["front"] = curl_file_create( "pictures/" . $temp->FrontPictureFile );
		}
		
		if ( $temp->RearPictureFile != "" ) {
			$postData["rear"] = curl_file_create( "pictures/" . $temp->RearPictureFile );
		}
		
		if ( @$jr->errorcode == 200 && sizeof( $postData ) > 0 ) {
			$p = curl_init( 'https://repository.opendcim.org/api/devicetemplate/addpictures/' . $jr->template->RequestID );
			curl_setopt( $p, CURLOPT_CONNECTTIMEOUT, 30 );
			curl_setopt( $p, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt( $p, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $p, CURLOPT_POST, true );
			curl_setopt( $p, CURLOPT_HEADER, true );
			curl_setopt( $p, CURLOPT_COOKIEFILE, "/tmp/repocookies.txt" );
			curl_setopt( $p, CURLOPT_COOKIEJAR, "/tmp/repocookies.txt" );
			curl_setopt( $p, CURLOPT_FOLLOWLOCATION, 1 );
			curl_setopt( $p, CURLOPT_HTTPHEADER, array( "UserID: scott@themillikens.com", "APIKey: e9afc69c3df5c8d70647150cf1ad9fc0" ) );
			curl_setopt( $p, CURLOPT_POSTFIELDS, $postData );
			
			$result = curl_exec( $p );
			$pr = json_decode( $result );
		}
		
		if ( @$jr->errorcode == 200 && @$pr->errorcode == 200 ) {
			$temp->clearShareFlag();
		}
	}
	
	curl_close( $c );
?>
