<?php

	require_once( "../../db.inc.php" );

	// Not really a loginPage, but this variable will keep us from getting redirected when
	// using LDAP auth and there's no session (so true RESTful API capability)
	//
	if ( AUTHENTICATION == "LDAP" || AUTHENTICATION == 'Saml' ) {
		$loginPage = true;
	}

	require_once( "../../facilities.inc.php" );

/*	Slim Framework v3 Specific Code

	We had to roll back due to PHP version requirements.

	use Psr\Http\Message\ServerRequestInterface as Request;
	use Psr\Http\Message\ResponseInterface as Response;

	$configuration = [
		'settings' => [
			'displayErrorDetails' => true,
		],
	];

	$c = new \Slim\Container($configuration);
	
	$app = new \Slim\App($c);
*/

	$app = new \Slim\Slim();

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

/* Framework v3 Version of the Authentication Middleware
$app->add(function($request, $response, $next) use($person) {
	if ( AUTHENTICATION == "LDAP" ) {
	    // Getting request headers
	    $headers = $request->getServerParams();

	    $valid = false;
	 
	 	if ( isset( $_SESSION['userid'] ) ) {
	 		$valid = true;

	 		$person->UserID = $_SESSION['userid'];
	 		$person->GetPersonByUserID();

	 	} elseif ( isset($headers['HTTP_USERID']) && isset($headers['HTTP_APIKEY'])) {
	    	// Load up the $person variable - so at this point, everything else functions
	    	// the same way as with Apache authorization - using the $person class
	    	$person->UserID = $headers['HTTP_USERID'];
	    	$person->GetPersonByUserID();

	    	if ( $person->APIKey == $headers['HTTP_APIKEY'] ) {
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

*/

//	Slim Framework 2 middleware
$app->hook( 'slim.before.dispatch', function() use($person) {
	if ( AUTHENTICATION == "LDAP" || AUTHENTICATION == "AD" || AUTHENTICATION == "Saml" ) {
		// Getting request headers
		$headers = apache_request_headers();
		$response = array();
		$app = \Slim\Slim::getInstance();

		$valid = false;

		if ( isset( $_SESSION['userid'] )) {
			$valid = true;

			$person->UserID = $_SESSION['userid'];
			$person->GetPersonByUserID();
		} elseif ( isset( $headers['UserID']) && isset( $headers['APIKey'])) {
			// Load up the $person variable
			$person->UserID = $headers['UserID'];
			$person->GetPersonByUserID();

			// Now verify that their key matches
			if ( $person->APIKey == $headers['APIKey'] ) {
				$valid = true;
			}
		}

		if ( ! $valid ) {
				// API Key is missing in the header
			$response['error'] = true;
			$response['message'] = _("Access Denied");
			$response['errorcode'] = 401;
			echoResponse($response );
			$app->stop();

		}
	}

	// Nothing to do if using Apache Authentication
});

// Another Framework v2 function to simulate some of the v3 stuff
function getParsedBody() {
	$app = \Slim\Slim::getInstance();

	if ( ! $vars = json_decode( $app->request->getBody(), true )) {
		$vars = $app->request->params();		
	}

	return $vars;
}

include_once 'getRoutes.php';
include_once 'postRoutes.php';
include_once 'putRoutes.php';
include_once 'deleteRoutes.php';


$app->run();
?>
