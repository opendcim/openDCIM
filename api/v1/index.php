<?php

	require_once( "../../db.inc.php" );

	// Not really a loginPage, but this variable will keep us from getting redirected when
	// using LDAP auth and there's no session (so true RESTful API capability)
	//
	if ( AUTHENTICATION == "LDAP" || AUTHENTICATION == 'Saml' || AUTHENTICATION == "OIDC" ) {
		$loginPage = true;
	}

	require_once( "../../facilities.inc.php" );
	require_once( __DIR__ . "/../../classes/hdd.class.php" );

	use Psr\Http\Message\ServerRequestInterface as Request;
	use Psr\Http\Message\ResponseInterface as Response;

	$configuration = [
		'settings' => [
			'displayErrorDetails' => true,
		],
	];

	$c = new \Slim\Container($configuration);
	
	$app = new \Slim\App($c);

	// Import any local extensions to the API, which obviously will not be supported
	foreach( glob("../local/*.php") as $filename) {
		include_once( $filename );
	}

/*
 *
 *	General notes about the API
 *
 *  All API access will require a valid credential, and at a minimum will require that the supplied credential
 *	has global Read access.
 *
 *	Also, technically RESTful API should not require a session (such as login via Apache), but we will support
 *	either using an API Token/Key or a user:token authentication such as what Apache passes back to the
 *	environment.
 *
 */
 
	$user_id = NULL;

function specifyAttributes( $attrList, $objList ) {
	if ( sizeof( $attrList ) > 0 ) {
		$trimList = array();
		foreach( $objList as $o ) {
			$n = new StdClass();
			foreach( $attrList as $prop ) {
				if ( isset( $o->$prop )) {
					$n->$prop = $o->$prop;
				}
			}
			$trimList[] = $n;
		}

		return $trimList;
	} else {
		return $objList;
	}
}

// Framework v2 Specific - we have to do our own output formatting
function echoResponse( $response ) {
	$app = \Slim\Slim::getInstance();

	if ( array_key_exists( 'errorcode', $response )) {
		$app->status( $response['errorcode'] );
	}

	$app->contentType( 'application/json' );

	echo json_encode( $response );
}

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */

// Since Middleware is applied in reverse order that it is added, make sure the Authentication function below is always the last one

// Framework v3 Version of the Authentication Middleware
$app->add(function($request, $response, $next) use($person) {
	if ( AUTHENTICATION == "LDAP" || AUTHENTICATION == "AD" || AUTHENTICATION == "Saml" || AUTHENTICATION == "OIDC" ) {
	    // Getting request headers
	    $headers = $request->getHeaders();

	    $valid = false;
	 
	 	if ( isset( $_SESSION['userid'] ) ) {
	 		$valid = true;

	 		$person->UserID = $_SESSION['userid'];
	 		$person->GetPersonByUserID();

	 	} elseif ( isset($headers['HTTP_USERID']) && isset($headers['HTTP_APIKEY'])) {
	    	// Load up the $person variable - so at this point, everything else functions
	    	// the same way as with Apache authorization - using the $person class
	    	$person->UserID = $headers['HTTP_USERID'][0];
	    	$person->GetPersonByUserID();

	    	if ( $person->APIKey == $headers['HTTP_APIKEY'][0] ) {
	    		$valid = true;
	    	}
	    }

	    if ( ! $valid ) {
	        // api key is missing in header
	    	$response->getBody()->write( "Access Denied" );
	    } else {
	    	$response = $next( $request, $response );
	    }
	} else {
		$response = $next( $request, $response );
	}

	return $response;
});

include_once 'getRoutes.php';
include_once 'postRoutes.php';
include_once 'putRoutes.php';
include_once 'deleteRoutes.php';


$app->run();
?>
