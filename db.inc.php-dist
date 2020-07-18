<?php

	// Not everyone will have the ability to (or, let's face it, attention span) set the PHP directive session.auto_start = 1
	// so we will simply start a session here (which will not cause any issues if auto_start is already set to 1) unless
	// we are being invoked from the command line


	// If the php-redis module is loaded AND the environment variable to enable it has been set, use it
	if ( extension_loaded( 'redis' ) && strtoupper(getenv('OPENDCIM_REDIS_ENABLED'))=='TRUE' ) {
		$redishost = getenv('OPENDCIM_REDIS_HOST') ? getenv('OPENDCIM_REDIS_HOST'):'redis';
		$redispass = getenv('OPENDCIM_REDIS_PASS') ? getenv('OPENDCIM_REDIS_PASS'):'dcim';

		ini_set( 'session.save_handler', 'redis' );
		ini_set( 'session.save_path', 'tcp://'.$redishost.':6379?auth='.$redispass );
	}

	if ( php_sapi_name() != "cli" ) {
	      session_start();
	}

	// Set to true if you want to skip the installer check
	$devMode = strtoupper(getenv('OPENDCIM_DEVMODE'))=="TRUE" ? true:false;
	$dbhost = getenv('OPENDCIM_DB_HOST') ? getenv('OPENDCIM_DB_HOST'):'localhost';
	$dbname = getenv('OPENDCIM_DB_NAME') ? getenv('OPENDCIM_DB_NAME'):'dcim';
	$dbuser = getenv('OPENDCIM_DB_USER') ? getenv('OPENDCIM_DB_USER'):'dcim';
	$dbpass = getenv('OPENDCIM_DB_PASS') ? getenv('OPENDCIM_DB_PASS'):'dcim';
	$dbport = getenv('OPENDCIM_DB_PORT') ? getenv('OPENDCIM_DB_PORT'):'3306';
	$initialAdminUser = getenv('OPENDCIM_ADMIN_USER') ? getenv('OPENDCIM_ADMIN_USER'):'dcim';

	$locale = "en_US";
	$codeset = "UTF-8";

	$pdo_options=array(
		PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
		PDO::ATTR_PERSISTENT => true
	);

	try{
		$pdoconnect="mysql:host=$dbhost;port=$dbport;dbname=$dbname";
		$dbh=new PDO($pdoconnect,$dbuser,$dbpass,$pdo_options);
		$dbh->exec("SET @@SESSION.sql_mode = ''");
	}catch(PDOException $e){
		printf( "Error!  %s\n", $e->getMessage() );
		die();
	}
	
	// Make sure that you only have ONE of these authentication types uncommented.  You will get an error if you
	// try to define the same name twice.
	if ( !defined( "AUTHENTICATION") ) {
		if ( getenv( 'OPENDCIM_AUTH')) {
			define('AUTHENTICATION', getenv('OPENDCIM_AUTH'));
		}

		@define( "AUTHENTICATION", "Apache" );

	/* If you want to use Oauth authentication, comment the above 3 lines, uncomment the next 4 lines
	   and place your authentication handler in login.php (create symbolic link). 
	   ex.  cp oauth/login_with_google.php oauth/login.php
	*/
		// define( "AUTHENTICATION", "Oauth" );

	/* If you want to use Saml authentication, comment the above defines for AUTHENTICATION,
           uncomment the Saml define below
	*/
		// define( "AUTHENTICATION", "Saml" );

	/* 	LDAP authentication and authorization, which is far from simple.
		Don't even try to enable this unless you know how to query
		your LDAP server.  */
		// define( "AUTHENTICATION", "LDAP" );
	}

	require_once( 'config.inc.php');
	$config=new Config();
	
?>
