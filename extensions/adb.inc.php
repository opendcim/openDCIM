<?php

	$dbhost = 'localhost';
	$dbname = 'accounts';
	$dbuser = 'accountsro';
	$dbpass = 'adbread';

	$adbbase = "https://intranet.network-i.net/accounts/customer_services/";

	$locale = "en_US";
	$codeset = "UTF-8";

	try {
			$pdoconnect = sprintf( "mysql:host=%s;dbname=%s", $dbhost, $dbname );
			$adbh = new PDO( $pdoconnect, $dbuser, $dbpass );
	} catch ( PDOException $e ) {
			printf( "Error!  %s\n", $e->getMessage() );
			die();
	}

//	require_once( 'config.inc.php');
//	$config=new Config();
	
?>
