<?php

	require_once( "../../db.inc.php" );
	require_once( "../../facilities.inc.php" );
	require_once( "../../Slim/Slim.php" );
	
	\Slim\Slim::registerAutoloader();
	
	$app = new \Slim\Slim();

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
	$response['errorcode'] = 200;
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
	$dc->DataCenterID = intval($DataCenterID);
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

//
//	URL:	/api/v1/cabinet
//	Method:	GET
//	Params: None
//	Returns: All cabinet information
//

$app->get( '/cabinet', function() use ($app) {
	$cab = new Cabinet;
	$dc = new DataCenter();
	foreach($app->request->get() as $prop => $val){
		$cab->$prop=$val;
	}
	$cList = $cab->Search();
	
	$response['error'] = false;
	$response['errorcode'] = 200;
	$response['cabinet'] = array();
	
	foreach( $cList as $c ) {
		$tmp = array();
		foreach( $c as $prop=>$value ) {
			$tmp[$prop] = $value;
		}
		if ( $dc->DataCenterID != $c->DataCenterID ) {
			$dc->DataCenterID = $c->DataCenterID;
			$dc->GetDataCenter();
		}
		
		$tmp['DataCenterName'] = $dc->Name;
		
		array_push( $response['cabinet'], $tmp );
	}
	
	echoRespnse( 200, $response );
});

//
//	URL:	/api/v1/cabinet/:cabinetid
//	Method:	GET
//	Params: cabinetid (passed in URL)
//	Returns: All cabinet information for given ID
//

$app->get( '/cabinet/:cabinetid', function($cabinetid) {
	$cab = new Cabinet;
	$dc = new DataCenter();
	if ( ! $cab->CabinetID = intval($cabinetid) ) {
		$response['error'] = true;
		$response['errorcode'] = 404;
		$response['message'] = 'No cabinet found with CabinetID of '. $cabinetid;
		echoRespnse( 404, $response );
	} else {
		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['cabinet'] = array();
		
		$tmp = array();
		foreach( $cab as $prop=>$value ) {
			$tmp[$prop] = $value;
		}
		$dc->DataCenterID = $cab->DataCenterID;
		$dc->GetDataCenter();
		
		$tmp['DataCenterName'] = $dc->Name;
		
		array_push( $response['cabinet'], $tmp );
		
		echoRespnse( 200, $response );
	}
});

//
//	URL:	/api/v1/cabinet/bydc/:datacenterid
//	Method:	GET
//	Params: datacenterid (passed in URL)
//	Returns: All cabinet information within the given data center, if any
//

$app->get( '/cabinet/bydc/:datacenterid', function($datacenterid) {
	$cab = new Cabinet;
	$dc = new DataCenter();
	$cab->DataCenterID = intval($datacenterid);
	$cList = $cab->ListCabinetsByDC();
	
	$response['error'] = false;
	$response['errorcode'] = 200;
	$response['cabinet'] = array();
	
	foreach( $cList as $c ) {
		$tmp = array();
		foreach( $c as $prop=>$value ) {
			$tmp[$prop] = $value;
		}
		if ( $dc->DataCenterID != $c->DataCenterID ) {
			$dc->DataCenterID = $c->DataCenterID;
			$dc->GetDataCenter();
		}
		
		$tmp['DataCenterName'] = $dc->Name;
		
		array_push( $response['cabinet'], $tmp );
	}
	
	echoRespnse( 200, $response );
});

//
//	URL:	/api/v1/cabinet/bydept/:deptid
//	Method:	GET
//	Params: deptid (passed in URL)
//	Returns: All cabinet information for cabinets assigned to supplied deptid
//

$app->get( '/cabinet/bydept/:deptid', function($deptid) {
	$cab = new Cabinet;
	$dc = new DataCenter();
	$cab->DeptID=$deptid;
	$cList = $cab->GetCabinetsByDept();
	
	$response['error'] = false;
	$response['errorcode'] = 200;
	$response['cabinet'] = array();
	
	foreach( $cList as $c ) {
		$tmp = array();
		foreach( $c as $prop=>$value ) {
			$tmp[$prop] = $value;
		}
		if ( $dc->DataCenterID != $c->DataCenterID ) {
			$dc->DataCenterID = $c->DataCenterID;
			$dc->GetDataCenter();
		}
		
		$tmp['DataCenterName'] = $dc->Name;
		
		array_push( $response['cabinet'], $tmp );
	}
	
	echoRespnse( 200, $response );
});

//
//	URL:	/api/v1/device
//	Method:	GET
//	Params:	none
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device', function() use ($app) {
	$dev=new Device();
	
	$response['error']=false;
	$response['errorcode']=200;
	foreach($app->request->get() as $prop => $val){
		$dev->$prop=$val;
	}
	$response['device']=$dev->Search();

	echoRespnse(200,$response);
});

//
//	URL:	/api/v1/device/:deviceid
//	Method:	GET
//	Params:	deviceid (passed in URL)
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device/:deviceid', function($deviceid) {
	$dev=new Device();
	$dev->DeviceID=intval($deviceid);
	
	if(!$dev->GetDevice()){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("No device found with DeviceID").$deviceid;
		echoRespnse(404,$response);
	}else{
		$response['error']=false;
		$response['errorcode']=200;
		$response['device']=$dev;
		
		echoRespnse(200,$response);
	}
});

//
//	URL:	/api/v1/device/bycabinet/:cabinetid
//	Method:	GET
//	Params:	cabinetid (passed in URL)
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device/bycabinet/:cabinetid', function( $cabinetid ) {
	$dev=new Device();
	$dev->Cabinet=intval($cabinetid);
	
	$response['error']=false;
	$response['errorcode']=200;
	$response['device']=$dev->ViewDevicesByCabinet(true);

	echoRespnse( 200, $response );
});

//
//	URL:	/api/v1/device/bydatacenter/:datacenterid
//	Method:	GET
//	Params:	datacenterid (passed in URL)
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device/bydatacenter/:datacenterid', function( $datacenterid ) {
	$dev=new Device();
	
	$response['error']=false;
	$response['errorcode']=200;
	$response['device']=$dev->GetDeviceList(intval($datacenterid));

	echoRespnse( 200, $response );
});

//
//	URL:	/api/v1/device/byowner/:departmentid
//	Method:	GET
//	Params:	departmentid (passed in URL)
//	Returns:  All devices owned by the specified department
//

$app->get( '/device/byowner/:departmentid', function( $departmentid ) {
	$dev=new Device();
	$dev->Owner=intval($departmentid);
	
	$response['error']=false;
	$response['errorcode']=200;
	$response['device']=$dev->GetDevicesByOwner();

	echoRespnse( 200, $response );
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
	verifyRequiredParams(array('UserID'));
	
	$response = array();
	$p = new People();
	$p->UserID = $app->request->post('UserID');
	if($p->GetPersonByUserID()){
		$response['error']=true;
		$response['errorcode']=403;
		$response['message']=__("UserID already in database.  Use the update API to modify record.");
		echoRespnse(403, $response );
	} else {	
		// Slim Framework will simply return null for any variables that were not passed, so this is safe to call without blowing up the script
		foreach($p as $prop){
			$p->$prop=$app->request->post($prop);
		}
		$p->Disabled = false;
		
		$p->CreatePerson();
		
		if($p->PersonID==false){
			$response['error']=true;
			$response['errorcode']=403;
			$response['message']=__("Unable to create People resource with the given parameters.");
			echoRespnse(403,$response);
		}else{
			$response['error']=false;
			$responde['errorcode']=200;
			$response['message']=__("People resource created successfully.");
			$response['people']=$p;

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
		$response['message'] = __("Insufficient privilege level");
		echoRespnse(400, $response);
		$app->stop();
	}

	$response = array();
	$p = new People();
	$p->UserID = $userid;
	if ( ! $p->GetPersonByUserID() ) {
		$response['error'] = true;
		$response['errorcode'] = 404;
		$response['message'] = __("UserID not found in database.");
		echoRespnse(404, $response );
	} else {	
		// Slim Framework will simply return null for any variables that were not passed, so this is safe to call without blowing up the script
		foreach($p as $prop){
			$p->$prop=$app->request->put($prop);
		}
		$p->Disabled = false;
		
		if(!$p->UpdatePerson()){
			$response['error']=true;
			$response['errorcode']=403;
			$response['message']=__("Unable to update People resource with the given parameters.");
			echoRespnse(403,$response);
		}else{
			$response['error']=false;
			$response['errorcode']=200;
			$response['message']=sprintf(__('People resource for UserID=%1$s updated successfully.'),$p->UserID);
			$response['people']=$p;

			echoRespnse(200,$response);
		}
	}
});

$app->run();
?>
