<?php

	require_once( "../../db.inc.php" );
	require_once( "../../facilities.inc.php" );
	require_once( "../../Slim/Slim.php" );
	
	\Slim\Slim::registerAutoloader();
	
	$app = new \Slim\Slim();

	$user_id = NULL;
	
/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}
 
/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}
 
/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
 
    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        // get the api key
        $apikey = $headers['Authorization'];
        // validating api key
		
		/*
        if (!APIKey::isValidKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user = APIKey::getUserId($api_key);
            if ($user != NULL)
                $user_id = $user["id"];
        }
		*/
		
		global $user_id;
		$user_id='dcim';
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
  *
  *		API GET Methods go here
  *
  *		GET Methods should be for retrieving single values or a collection of values.  Not to be used for
  *			any functions that would modify data within the database.
  *
  **/

//
//	URL:  /api/v1/people
//	Method: GET
//	Params:  none
//	Returns:  List of all people in the database
//
$app->get('/people', function() {
	global $person;
	
	$person->GetUserRights();
	if ( !$person->ContactAdmin ) {
		$response['error'] = true;
		$response['errorcode'] = 400;
		$response['message'] = "Insufficient privilege level";
		echoRespnse(400, $response);
	} else {
		$pList = $person->GetUserList();
		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['people'] = array();
		foreach ( $pList as $p ) {
			$tmp = array();
			foreach ( $p as $prop=>$value ) {
				$tmp[$prop] = $value;
			}
			array_push( $response['people'], $tmp );
		}
		
		echoRespnse(200, $response);
	}
});

//
//	URL:	/api/v1/datacenter
//	Method: GET
//	Params:  none
//	Returns: List of all data centers in the database
//

$app->get('/datacenter', function() {
	// Don't have to worry about rights, other than basic connection, to get data center list
	
	$dc = new DataCenter();
	$dcList = $dc->GetDCList();
	$response['error'] = false;
	$responde['errorcode'] = 200;
	$response['datacenter'] = array();
	foreach ( $dcList as $d ) {
		$tmp = array();
		foreach( $d as $prop=>$value ) {
			$tmp[$prop] = $value;
		}
		array_push( $response['datacenter'], $tmp );
	}
	
	echoRespnse( 200, $response );
});

//
//	URL:	/api/v1/datacenter/:id
//  Method: GET
//	Params:  DataCenterID (passed in URL as :id)
//	Returns: Details of specified datacenter
//

$app->get( '/datacenter/:id', function( $DataCenterID ) {
	$dc = new DataCenter();
	$dc->DataCenterID = $DataCenterID;
	if ( ! $dc->GetDataCenter() ) {
		$response['error'] = true;
		$response['errorcode'] = 404;
		$response['message'] = 'The requested resource does not exist.';
		echoRespnse(404, $response);
	} else {
		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['datacenter'] = array();
		$tmp = array();
		foreach( $dc as $prop=>$value ) {
			$tmp[$prop] = $value;
		}
		array_push( $response['datacenter'], $tmp );
		
		echoRespnse(200, $response);
	}
});
	

/**
  *
  *		API POST Methods go here
  *
  *		POST Methods are for creating new records
  *
  **/

//
//	URL:	/api/v1/people
//	Method: POST
//	Params: userid (required)
//			lastname, firstname, phone1, phone2, phone3, email, adminowndevices, readaccess, writeaccess,
//			deleteaccess, contactadmin, rackrequest, rackadmin, siteadmin
//	Returns: record as created
//

$app->post('/people', function() use ($app) {
	global $person;
	
	$person->GetUserRights();
	if ( !$person->ContactAdmin ) {
		$response['error'] = true;
		$response['errorcode'] = 400;
		$response['message'] = "Insufficient privilege level";
		echoRespnse(400, $response);
		$app->stop();
	}
	
	// Only one field is required - all others are optional
	verifyRequiredParams(array('userid'));
	
	$response = array();
	$p = new People();
	$p->UserID = $app->request->post('userid');
	if ( $p->GetPersonByUserID() ) {
		$response['error'] = true;
		$response['errorcode'] = 403;
		$response['message'] = 'UserID already in database.  Use the update API to modify record.';
		echoRespnse(403, $response );
	} else {	
		// Slim Framework will simply return null for any variables that were not passed, so this is safe to call without blowing up the script
		$p->LastName = $app->request->post('lastname');
		$p->FirstName = $app->request->post('firstname');
		$p->Phone1 = $app->request->post('phone1');
		$p->Phone2 = $app->request->post('phone2');
		$p->Phone3 = $app->request->post('phone3');
		$p->Email = $app->request->post('email');
		$p->AdminOwnDevices = $app->request->post('adminowndevices');
		$p->ReadAccess = $app->request->post('readaccess');
		$p->WriteAccess = $app->request->post('writeaccess');
		$p->DeleteAccess = $app->request->post('deleteaccess');
		$p->ContactAdmin = $app->request->post('contactadmin');
		$p->RackRequest = $app->request->post('rackrequest');
		$p->RackAdmin = $app->request->post('rackadmin');
		$p->SiteAdmin = $app->request->post('siteadmin');
		$p->Disabled = false;
		
		$p->CreatePerson();
		
		if ( $p->PersonID == false ) {
			$response['error'] = true;
			$response['errorcode'] = 403;
			$response['message'] = 'Unable to create People resource with the given parameters.';
			echoRespnse(403,$response);
		} else {
			$response['error'] = false;
			$responde['errorcode'] = 200;
			$response['message'] = 'People resource created successfully.';
			$response['people'] = array();
			foreach( $p as $prop=>$value ) {
				$tmp[$prop] = $value;
			}
			array_push( $response['people'], $tmp );
			echoRespnse(200,$response);
		}
	}
});


/**
  *
  *		API PUT Methods go here
  *
  *		PUT Methods are for updating existing records
  *
  **/

//
//	URL:	/api/v1/people/:userid
//	Method: PUT
//	Params: userid (required, passed as :userid in URL)
//			lastname, firstname, phone1, phone2, phone3, email, adminowndevices, readaccess, writeaccess,
//			deleteaccess, contactadmin, rackrequest, rackadmin, siteadmin
//	Returns: record as modified
//
  
$app->put('/people/:userid', function($userid) use ($app) {
	global $person;
	
	$person->GetUserRights();
	if ( !$person->ContactAdmin ) {
		$response['error'] = true;
		$response['errorcode'] = 400;
		$response['message'] = "Insufficient privilege level";
		echoRespnse(400, $response);
		$app->stop();
	}

	$response = array();
	$p = new People();
	$p->UserID = $userid;
	if ( ! $p->GetPersonByUserID() ) {
		$response['error'] = true;
		$response['errorcode'] = 404;
		$response['message'] = 'UserID not found in database.';
		echoRespnse(404, $response );
	} else {	
		// Slim Framework will simply return null for any variables that were not passed, so this is safe to call without blowing up the script
		$p->LastName = $app->request->put('lastname');
		$p->FirstName = $app->request->put('firstname');
		$p->Phone1 = $app->request->put('phone1');
		$p->Phone2 = $app->request->put('phone2');
		$p->Phone3 = $app->request->put('phone3');
		$p->Email = $app->request->put('email');
		$p->AdminOwnDevices = $app->request->put('adminowndevices');
		$p->ReadAccess = $app->request->put('readaccess');
		$p->WriteAccess = $app->request->put('writeaccess');
		$p->DeleteAccess = $app->request->put('deleteaccess');
		$p->ContactAdmin = $app->request->put('contactadmin');
		$p->RackRequest = $app->request->put('rackrequest');
		$p->RackAdmin = $app->request->put('rackadmin');
		$p->SiteAdmin = $app->request->put('siteadmin');
		$p->Disabled = false;
		
		if ( ! $p->UpdatePerson() ) {
			$response['error'] = true;
			$response['errorcode'] = 403;
			$response['message'] = 'Unable to update People resource with the given parameters.';
			echoRespnse(403,$response);
		} else {
			$response['error'] = false;
			$response['errorcode'] = 200;
			$response['message'] = 'People resource for UserID=' . $p->UserID . ' updated successfully.';
			$response['people'] = array();
			foreach( $p as $prop=>$value ) {
				$tmp[$prop] = $value;
			}
			array_push( $response['people'], $tmp );
			echoRespnse(200,$response);
		}
	}
});

$app->run();
?>