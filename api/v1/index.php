<?php

	require_once( "../../db.inc.php" );

	// Not really a loginPage, but this variable will keep us from getting redirected when
	// using LDAP auth and there's no session (so true RESTful API capability)
	//
	if ( AUTHENTICATION == "LDAP" ) {
		$loginPage = true;
	}

	require_once( "../../facilities.inc.php" );
	require_once( "../../Slim/Slim.php" );
	
	\Slim\Slim::registerAutoloader();
	
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
        echoResponse(400, $response);
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
        echoResponse(400, $response);
        $app->stop();
    }
}
 
/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}

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

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
$app->hook('slim.before.dispatch', function () use($person) {
	if ( AUTHENTICATION == "LDAP" ) {
	    // Getting request headers
	    $headers = apache_request_headers();
	    $response = array();
	    $app = \Slim\Slim::getInstance();

	    $valid = false;
	 
	 	if ( isset( $_SESSION['userid'] ) ) {
	 		$valid = true;

	 		$person->UserID = $_SESSION['userid'];
	 		$person->GetPersonByUserID();
	 	} elseif ( isset($headers['UserID']) && isset($headers['APIKey'])) {
	    	// Load up the $person variable - so at this point, everything else functions
	    	// the same way as with Apache authorization - using the $person class
	    	$person->UserID = $headers['UserID'];
	    	$person->GetPersonByUserID();

	    	if ( $person->APIKey == $headers['APIKey'] ) {
	    		$valid = true;
	    	}
	    }

	    if ( ! $valid ) {
	        // api key is missing in header
	        $response["error"] = true;
	        $response["message"] = "API key is misssing or invalid";
	        echoResponse(200, $response);
	        $app->stop();
	    }
	}
});

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
$app->get('/people', function() use ($app) {
	global $person;
	
	$person->GetUserRights();
	if(!$person->ContactAdmin){
		$response['error'] = true;
		$response['errorcode'] = 400;
		$response['message'] = "Insufficient privilege level";
		echoResponse(200, $response);
	}else{
		$response['error'] = false;
		$response['errorcode'] = 200;
		$sp=new People();
		$loose = false;
		$outputAttr = array();
		foreach($app->request->get() as $prop => $val){
			if ( strtoupper($prop) == "WILDCARDS" ) {
				$loose = true;
			} elseif ( strtoupper($prop) == "ATTRIBUTES" ) {
				$outputAttr = explode( ",", $val );
			} else {
				$sp->$prop=$val;
			}
		}
		$response['people']=specifyAttributes( $outputAttr, $sp->Search( false, $loose ));
		
		echoResponse(200, $response);
	}
});

//
//	URL:  /api/v1/department
//	Method: GET
//	Params:  none
//	Returns:  List of all departments in the database
//
$app->get('/department', function() use ($app) {
	global $person;
	$dept=new Department();
	$loose = false;
	$outputAttr = array();

	if(!$person->ContactAdmin){
		$response['error']=true;
		$response['message'] = "Insufficient privilege level";
		$outputAttr = array( "DeptID", "DeptColor" );
	} else {
		$response['error'] = false;
		$response['errorcode'] = 200;
		foreach($app->request->get() as $prop => $val){
			if ( strtoupper($prop) == "WILDCARDS" ) {
				$loose = true;
			} elseif ( strtoupper($prop) == "ATTRIBUTES" ) {
				$outputAttr = explode( ",", $val );
			} else {
				$dept->$prop=$val;
			}
		}		
	}

	$response['department'] = specifyAttributes( $outputAttr, $dept->Search( false, $loose ));
	echoResponse(200, $response);
});

//
//	URL:	/api/v1/datacenter
//	Method: GET
//	Params:  none
//	Returns: List of all data centers in the database
//

$app->get('/datacenter', function() use ($app) {
	// Don't have to worry about rights, other than basic connection, to get data center list
	
	$dc = new DataCenter();
	$dcList = $dc->GetDCList();
	$response['error'] = false;
	$response['errorcode'] = 200;

	$outputAttr = array();
	$loose = false;

	foreach( $app->request->get() as $prop=>$val ) {
		if (strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif( strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} else {
			$dc->$prop = $val;
		}
	}

	$response['datacenter'] = specifyAttributes( $outputAttr, $dc->Search( false, $loose ));
	
	echoResponse( 200, $response );
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
		echoResponse(200, $response);
	} else {
		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['datacenter'] = array();
		$tmp = array();
		foreach( $dc as $prop=>$value ) {
			$tmp[$prop] = $value;
		}
		array_push( $response['datacenter'], $tmp );
		
		echoResponse(200, $response);
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
	$loose = false;
	$outputAttr = array();

	foreach($app->request->get() as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif ( strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} else {
			$cab->$prop=$val;
		}
	}

	$cList = specifyAttributes( $outputAttr, $cab->Search(false, $loose));
	
	$response['error'] = false;
	$response['errorcode'] = 200;
	$response['cabinet'] = array();
	
	foreach( $cList as $c ) {
		$tmp = array();
		foreach( $c as $prop=>$value ) {
			$tmp[$prop] = $value;
		}
		if ( isset( $c->DataCenterID ) ) {
			if ( $dc->DataCenterID != $c->DataCenterID ) {
				$dc->DataCenterID = $c->DataCenterID;
				$dc->GetDataCenter();
			}
		
			$tmp['DataCenterName'] = $dc->Name;
		}
		
		array_push( $response['cabinet'], $tmp );
	}
	
	echoResponse( 200, $response );
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
		echoResponse( 200, $response );
	} else {
		$response['error'] = false;
		$response['errorcode'] = 200;
		$response['cabinet'] = array();

		$cab->GetCabinet();
		
		$tmp = array();
		foreach( $cab as $prop=>$value ) {
			$tmp[$prop] = $value;
		}
		$dc->DataCenterID = $cab->DataCenterID;
		$dc->GetDataCenter();
		
		$tmp['DataCenterName'] = $dc->Name;
		
		array_push( $response['cabinet'], $tmp );
		
		echoResponse( 200, $response );
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
	
	echoResponse( 200, $response );
});

//
//	URL:	/api/v1/cabinet/:cabinetid/sensors
//	Method:	GET
//	Params: cabinetid (passed in URL)
//	Returns: All cabinet sensor information for the specified cabinet, if any
//

$app->get( '/cabinet/:cabinetid/sensor', function($cabinetid) {
	global $config;
	
	$response['error'] = false;
	$response['errorcode'] = 200;
	$response['sensors'] = array();

	if ( $m = CabinetMetrics::getMetrics($cabinetid) ) {
		$m->mUnits = $config->ParameterArray["mUnits"];
		$response['sensors'] = $m;
	}	

	echoResponse( 200, $response );
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
	
	echoResponse( 200, $response );
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
	$loose = false;
	$outputAttr = array();

	foreach($app->request->get() as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif (strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} else {
			$dev->$prop=$val;
		}
	}
	
	$devList = $dev->Search( false, $loose );

	$response['device']=specifyAttributes( $outputAttr, $devList );

	echoResponse(200,$response);
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
		echoResponse(200,$response);
	}else{
		if ( is_array( $dev->CustomValues ) ) {
			$cattr = new DeviceCustomAttribute();
			$newList = array();
			foreach ( $dev->CustomValues as $key=>$val ) {
				$cattr->AttributeID = $key;
				$cattr->GetDeviceCustomAttribute();
				$newList[$cattr->Label] = $val;
			}
			$dev->CustomValues = $newList;
		}
		$response['error']=false;
		$response['errorcode']=200;
		$response['device']=$dev;
		
		echoResponse(200,$response);
	}
});

//
//	URL:	/api/v1/device/:deviceid/getpicture
//	Method:	GET
//	Params:	
//		required: deviceid (passed in URL)
//		optional: rear (will return the rear face of the device) 
//	Returns:  HTML representation of a device
//

$app->get( '/device/:deviceid/getpicture', function($deviceid) {
	$dev=new Device($deviceid);
	
	$response['error']=true;
	$response['errorcode']=404;
	$response['message']=__("Unknown error");

	if(!$dev->GetDevice()){
		$response['message']=__("Device not found");
	}else{
		// we filter out most of the details if you don't have rights in the 
		// GetDevicePicture function so this might lead to some probing but 
		// should be minimal
		$response['error']=false;
		$response['errorcode']=200;
		$response['message']="";
		$response['picture']=$dev->GetDevicePicture(isset($_GET['rear']));
	}

	echoResponse(200,$response);
});

$app->get( '/device/:deviceid/getsensorreadings', function($deviceid) {
	$dev=new Device();
	$dev->DeviceID=intval($deviceid);
	
	if(!$dev->GetDevice(false)){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("Device not found");
	}else{
		$reading=$dev->GetSensorReading(false);
		if(!$reading){
			$response['error']=true;
			$response['errorcode']=404;
			$response['message']=__("Device is not a sensor or is missing a template");
		}else{
			$response['error']=false;
			$response['errorcode']=200;
			$response['sensor']=$reading;
		}
	}

	echoResponse(200,$response);
});

// this is messy as all hell and i'm still thinking about how to do it better

//
//	URL:	/api/vi/deviceport/:deviceid
//
//	Method:	GET
//	Params:
//		Required:  :deviceid - DeviceID for which you wish to retrieve ports
//	Returns:	All ports for the given device

$app->get('/deviceport/:deviceid', function($deviceid) use($app) {
	$dp = new DevicePorts();
	
	$response['error'] = false;
	$response['errorcode'] = 200;
	$dp->DeviceID = $deviceid;
	$response['deviceport']=$dp->getPorts();

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/deviceport/:deviceid/patchcandidates
//	Method:	GET
//	Params:
//		required: deviceid - deviceid you are connecting from
//		optional: portnumber (int) - id of the port on the device you're connecting from. required when listports is true
//				  connectto - deviceid you want to connect to
//				  listports (true) - will kick back ports that can be connected to instead of devices
//				  patchpanels (true) - limit the result to just patch panel devices
//				  limiter (cabinet/row/zone/datacenter) - limit the results by these containers
//	Returns:  All devices that this device can connect to
//

$app->get( '/deviceport/:deviceid/patchcandidates', function($deviceid) use ($app) {
	$s=new stdClass();
	$s->portnumber=$app->request->get('portnumber');
	$s->connectto=$app->request->get('connectto');
	$s->listports=$app->request->get('listports');
	$s->patchpanels=$app->request->get('patchpanels');
	$s->limiter=$app->request->get('limiter');

	$response['error']=false;
	$response['errorcode']=200;
	
	// I like nulls and wrote this function originally around them
	foreach($s as $prop => $val){
		$s->$prop=(!$val)?null:$val;
	}

	if(is_null($s->listports)){
		$response['device']=DevicePorts::getPatchCandidates($deviceid,$s->portnumber,null,$s->patchpanels,$s->limiter);
	}else{
		$dp=new DevicePorts();
		$dp->DeviceID=$s->connectto;
		$list=$dp->getPorts();

		foreach($list as $key => $port){
			if(!is_null($port->ConnectedDeviceID)){
				if($port->ConnectedDeviceID==$deviceid && $port->ConnectedPort==$s->portnumber){
					// This is what is currently connected so leave it in the list
				}else{
					// Remove any other ports that already have connections
					unset($list[$key]);
				}
			}
		}

		// S.U.T. #2342 I touch myself
		if($dp->DeviceID == $deviceid && isset($list[$s->portnumber])){
			unset($list[$s->portnumber]);
		}

		// Sort the ports so that all front ports will be first then the rear ports.
		$front=array();
		$rear=array();

		foreach($list as $pn => $port){
			if($pn>0){
				$front[$pn]=$port;
			}else{
				$rear[$pn]=$port;
			}
		}

		// Positive and negative numbers have different sorts to make sure that 1 is on top of the list
		ksort($front);
		krsort($rear);

		$list=array_replace($front,$rear);

		$response['deviceport']=$list;
	}

	echoResponse(200,$response);
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

	echoResponse( 200, $response );
});

//
//	URL:	/api/v1/device/byproject/:projectid
//	Method:	GET
//	Params:	projectid (passed in URL)
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device/byproject/:projectid', function( $projectid ) {
	$response['error']=false;
	$response['errorcode']=200;
	$response['device']=ProjectMembership::getProjectMembership( $projectid );

	echoResponse( 200, $response );
});


//
//	URL:	/api/v1/project
//	Method:	GET
//	Params:	None
//	Returns:  All project metadata
//

$app->get( '/project', function() {
	$response['error']=false;
	$response['errorcode']=200;
	$response['project']=Projects::getProjectList();

	echoResponse( 200, $response );
});

//
//	URL:	/api/v1/project/bydevice/:deviceid
//	Method:	GET
//	Params:	DeviceID
//	Returns:  All project metadata for projects the deviceid is a member of
//

$app->get( '/project/bydevice/:deviceid', function( $deviceid ) {
	$response['error']=false;
	$response['errorcode']=200;
	$response['project']=ProjectMembership::getDeviceMembership( $deviceid );

	echoResponse( 200, $response );
});


//
//	URL:	/api/v1/powerport/:deviceid
//	Method:	GET
//	Params:	deviceid (required), portnumber (optional)
//	Returns:  All power ports for a device or a specific port
//

$app->get( '/powerport/:deviceid', function($deviceid) use ($app) {
	$pp=new PowerPorts();
	
	$response['error']=false;
	$response['errorcode']=200;
	$pp->DeviceID=$deviceid;
	foreach($app->request->get() as $prop => $val){
		$pp->$prop=$val;
	}

	if($pp->PortNumber){
		if(!$pp->getPort()){
			$response['error']=true;
		}
		// This is to cut down on api calls to get the connected device and port names
		if($pp->ConnectedDeviceID){
			$dev=new Device();
			$dpp=new PowerPorts();
			$dev->DeviceID=$dpp->DeviceID=$pp->ConnectedDeviceID;
			$dpp->PortNumber=$pp->ConnectedPort;
			$dev->GetDevice();
			$dpp->getPort();
			$pp->ConnectedDeviceLabel=$dev->Label;
			$pp->ConnectedPortLabel=$dpp->Label;
		}

		$response['powerport'][$pp->PortNumber]=$pp;
	}else{
		$response['powerport']=$pp->getPorts();
	}

	echoResponse(200,$response);
});


//
//	URL:	/api/v1/colorcode
//	Method:	GET
//	Params:	none
//	Returns:  All defined color codes 
//

$app->get( '/colorcode', function() {
	$response['error']=false;
	$response['errorcode']=200;
	$response['colorcode']=ColorCoding::GetCodeList();;
		
	echoResponse(200,$response);
});

//
//	URL:	/api/v1/colorcode/:colorid
//	Method:	GET
//	Params:	colorid (passed in URL)
//	Returns:  All defined color codes matching :colorid 
//

$app->get( '/colorcode/:colorid', function($colorid) {
	$cc=new ColorCoding();
	$cc->ColorID=$colorid;
	
	if(!$cc->GetCode()){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("No color code found with ColorID")." $cc->ColorID";
	}else{
		$response['error']=false;
		$response['errorcode']=200;
		$response['colorcode'][$cc->ColorID]=$cc;
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/colorcode/:colorid/timesused
//	Method:	GET
//	Params:	colorid (passed in URL)
//	Returns:  Number of objects using :colorid 
//

$app->get( '/colorcode/:colorid/timesused', function($colorid) {
	$response['error']=false;
	$response['errorcode']=200;
	$response['colorcode']=ColorCoding::TimesUsed($colorid);
	
	echoResponse(200,$response);
});


//
//	URL:	/api/v1/devicetemplate
//	Method:	GET
//	Params: none	
//	Returns: All available device templates
//

$app->get( '/devicetemplate', function() use ($app) {
	$dt=new DeviceTemplate();
	$response['error']=false;
	$response['errorcode']=200;
	$loose = false;
	$outputAttr = array();

	foreach($app->request->get() as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif (strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} else {
			$dt->$prop=$val;
		}
	}
	
	$tmpList = $dt->Search( false, $loose );

	$response['devicetemplate']=specifyAttributes( $outputAttr, $tmpList );

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/devicetemplate/:templateid
//	Method:	GET
//	Params: templateid
//	Returns: Device template for templateid
//
//  While this will be non-standard it's gonna be necessary to support the existing
//  spec.  If we device to change the images function later the proposal for an images
//  path might be revisited.
//

$app->get( '/devicetemplate/:templateid', function($templateid) use ($app) {
	if($templateid=='image'){
		$response['error']=false;
		$response['errorcode']=200;
		$response['image']=DeviceTemplate::getAvailableImages();
	}else{
		$dt=new DeviceTemplate();
		$dt->TemplateID=$templateid;
		if(!$dt->GetTemplateByID()){
			$response['error']=true;
			$response['errorcode']=404;
			$response['message']=__("No template found with TemplateID: ")." $templateid";
		}else{
			$response['error']=false;
			$response['errorcode']=200;
			$response['template']=$dt;
		}
	}
	echoResponse(200,$response);
});

//
//	URL:	/api/v1/devicetemplate/:templateid/dataport
//	Method:	GET
//	Params: templateid
//	Returns: Data ports defined for device template with templateid
//

$app->get( '/devicetemplate/:templateid/dataport', function($templateid) use ($app) {
	$tp=new TemplatePorts();
	$tp->TemplateID=$templateid;
	if(!$ports=$tp->getPorts()){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("No ports found for TemplateID: ")." $templateid";
	}else{
		$response['error']=false;
		$response['errorcode']=200;
		$response['dataport']=$ports;
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/devicetemplate/:templateid/dataport/:portnumber
//	Method:	GET
//	Params: templateid. portnumber
//	Returns: Single data port defined for device template with templateid and portnum
//

$app->get( '/devicetemplate/:templateid/dataport/:portnumber', function($templateid,$portnumber) use ($app) {
	$tp=new TemplatePorts();
	$tp->TemplateID=$templateid;
	$tp->PortNumber=$portnumber;
	if(!$tp->getPort()){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("Port not found for TemplateID: ")." $templateid:$portnumber";
	}else{
		$response['error']=false;
		$response['errorcode']=200;
		$response['dataport']=$tp;
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/devicetemplate/:templateid/powerport
//	Method:	GET
//	Params: templateid
//	Returns: Power ports defined for device template with templateid
//

$app->get( '/devicetemplate/:templateid/powerport', function($templateid) use ($app) {
	$tp=new TemplatePowerPorts();
	$tp->TemplateID=$templateid;
	if(!$ports=$tp->getPorts()){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("No ports found for TemplateID: ")." $templateid";
	}else{
		$response['error']=false;
		$response['errorcode']=200;
		$response['powerport']=$ports;
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/devicetemplate/:templateid/slot
//	Method:	GET
//	Params: templateid
//	Returns: Slots defined for device template with templateid
//

$app->get( '/devicetemplate/:templateid/slot', function($templateid) use ($app) {
	if(!$slots=slot::GetAll($templateid)){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("No slots found for TemplateID: ")." $templateid";
	}else{
		$response['error']=false;
		$response['errorcode']=200;
		$response['slot']=$slots;
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/manufacturer
//	Method:	GET
//	Params:	none
//	Returns:  All defined manufacturers 
//

$app->get( '/manufacturer', function() use ($app) {
	$man=new Manufacturer();
	
	$response['error']=false;
	$response['errorcode']=200;
	foreach($app->request->get() as $prop => $val){
		$man->$prop=$val;
	}
	$response['manufacturer']=$man->GetManufacturerList();

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/zone
//	Method:	GET
//	Params:	none
//	Returns:  All zones for which the user's rights have access to view
//

$app->get( '/zone', function() use ($app) {
	$zone=new Zone();
	
	$response['error']=false;
	$response['errorcode']=200;
	foreach($app->request->get() as $prop => $val){
		$zone->$prop=$val;
	}
	$response['zone']=$zone->Search(true);

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/zone/:zoneid
//	Method:	GET
//	Params:	none
//	Returns: Zone identified by :zoneid 
//

$app->get( '/zone/:zoneid', function($zoneid) use ($app) {
	$zone=new Zone();
	$zone->ZoneID=$zoneid;
	
	$response['error']=false;
	$response['errorcode']=200;
	foreach($app->request->get() as $prop => $val){
		$dev->$prop=$val;
	}
	$response['zone']=$zone->GetZone();

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/cabrow
//	Method:	GET
//	Params:	none
//	Returns:  All cabinet rows for which the user's rights have access to view
//

$app->get( '/cabrow', function() use ($app) {
	$cabrow=new CabRow();
	
	$response['error']=false;
	$response['errorcode']=200;
	foreach($app->request->get() as $prop => $val){
		$cabrow->$prop=$val;
	}
	$response['cabrow']=$cabrow->Search(true);

	echoResponse(200,$response);
});


/**
  *
  *		API POST Methods go here
  *
  *		POST Methods are for updating existing records
  *
  **/

//
//	URL:	/api/v1/people
//	Method: POST
//	Params: userid (required)
//			lastname, firstname, phone1, phone2, phone3, email, adminowndevices, 
//			readaccess, writeaccess, deleteaccess, contactadmin, rackrequest, 
//			rackadmin, siteadmin
//	Returns: record as modified
//

$app->post('/people/:personid', function($personid) use ($app,$person) {
	if(!$person->ContactAdmin){
		$response['error']=true;
		$response['errorcode']=400;
		$response['message']=__("Insufficient privilege level");
		echoResponse(200,$response);
		$app->stop();
	}

	$response=array();
	$p=new People();
	$p->PersonID=$personid;
	if(!$p->GetPerson()){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("User not found in database.");
		echoResponse(200,$response);
	} else {	
		// Slim Framework will simply return null for any variables that were not passed, so this is safe to call without blowing up the script
		foreach($p as $prop => $val){
			$p->$prop=$app->request->post($prop);
		}
		$p->Disabled=false;
		
		if(!$p->UpdatePerson()){
			$response['error']=true;
			$response['errorcode']=403;
			$response['message']=__("Unable to update People resource with the given parameters.");
			echoResponse(200,$response);
		}else{
			$response['error']=false;
			$response['errorcode']=200;
			$response['message']=sprintf(__('People resource for UserID=%1$s updated successfully.'),$p->UserID);
			$response['people']=$p;

			echoResponse(200,$response);
		}
	}
});

//
//	URL:	/api/v1/people
//	Method: POST
//	Params:
//		Required: peopleid, newpeopleid
//	Returns: true / false on the updates being successful 
//

$app->post('/people/:peopleid/transferdevicesto/:newpeopleid', function($peopleid,$newpeopleid) use ($app, $person) {
	$response['error']=false;
	$response['errorcode']=200;

	// Verify the userids are real
	foreach(array('peopleid','newpeopleid') as $var){
		$p=new People();

		$p->UserID=$$var;
		if(!$p->GetPerson() && ($var!='newpeopleid' && $$var==0)){
			$response['error']=true;
			$response['message']="$var is not valid";
			continue;
		}
	}

	// If we error above don't attempt to make changes
	if(!$response['error']){
		$dev=new Device();
		$dev->PrimaryContact=$peopleid;
		foreach($dev->Search() as $d){
			$d->PrimaryContact=$newpeopleid;
			if(!$d->UpdateDevice()){
				// If we encounter an error stop immediately
				$response['error']=true;
				$response['message']=__("Device update has failed");
				continue;
			}
		}
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/powerport/:deviceid
//	Method:	POST
//	Params:	
//		required: DeviceID, PortNumber
//		optional: Label, ConnectedDeviceID, ConnectedPort, Notes
//	Returns:  true/false on update operation
//

$app->post( '/powerport/:deviceid', function($deviceid) use ($app, $person) {
	$pp=new PowerPorts();
	$pp->DeviceID=$deviceid;
	foreach($app->request->post() as $prop => $val){
		$pp->$prop=$val;
	}

	$response['error']=($pp->updatePort())?false:true;
	$response['errorcode']=200;

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/colorcode/:colorid
//	Method:	POST
//	Params:	
//		required: ColorID, Name
//		optional: DefaultNote 
//	Returns:  true/false on update operation
//

$app->post( '/colorcode/:colorid', function($colorid) use ($app, $person) {
	$cc=new ColorCoding();
	foreach($app->request->post() as $prop => $val){
		$cc->$prop=$val;
	}

	$response['error']=($cc->UpdateCode())?false:true;
	$response['errorcode']=200;

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/colorcode/:colorid/replacewith/:newcolorid
//	Method:	POST
//	Params:	
//		required: ColorID, NewColorID
//		optional: DefaultNote, Name
//	Returns:  true/false on update operation
//

$app->post( '/colorcode/:colorid/replacewith/:newcolorid', function($colorid,$newcolorid) use ($app) {
	$response['error']=(ColorCoding::ResetCode($colorid,$newcolorid))?false:true;
	$response['errorcode']=200;

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/device/:deviceid
//	Method:	POST
//	Params:	deviceid (passed in URL)
//	Returns:  true/false on update operation
//

$app->post( '/device/:deviceid', function($deviceid) use ($app) {
	$dev=new Device();
	$dev->DeviceID=$deviceid;
	
	if(!$dev->GetDevice()){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("No device found with DeviceID").$deviceid;
	}else{
		if($dev->Rights!="Write"){
			$response['error']=true;
			$response['errorcode']=403;
			$response['message']=__("Unauthorized");
		}else{
			foreach($app->request->post() as $prop => $val){
				$dev->$prop=$val;
			}
			if(!$dev->UpdateDevice()){
				$response['error']=true;
				$response['errorcode']=404;
				$response['message']=__("Update failed");
			}else{
				$response['error']=false;
				$response['errorcode']=200;
			}
		}
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/devicetemplate/:templateid
//	Method:	POST
//	Params:	
//		Required: templateid
//		Optional: everything else
//	Returns: true/false on update operation 
//

$app->post( '/devicetemplate/:templateid', function($templateid) use ($app,$person) {
	$dt=new DeviceTemplate($templateid);
	// This should be in the commit data but if we get a smartass saying it's in the URL
	$dt->TemplateID=$templateid;
	if(!$person->WriteAccess){
		$response['error']=true;
		$response['errorcode']=403;
		$response['message']=__("Unauthorized");
	}else{
		if(!$dt->GetTemplateByID()){
			$response['error']=true;
			$response['errorcode']=404;
			$response['message']=__("No device template found with TemplateID: ").$templateid;
		}else{
			foreach($app->request->post() as $prop => $val){
				$dt->$prop=$val;
			}
			if(!$dt->UpdateTemplate()){
				$response['error']=true;
				$response['errorcode']=404;
				$response['message']=__("Device template update failed");
			}else{
				$response['error']=false;
				$response['errorcode']=200;
			}
		}
	}
	echoResponse(200,$response);
});

//
//	URL:	/api/v1/devicetemplate/:templateid/dataport/:portnumber
//	Method:	POST
//	Params:	
//		Required: templateid, portnumber, portlabel
//		Optional: everything else
//	Returns: true/false on update operation
//

$app->post( '/devicetemplate/:templateid/dataport/:portnumber', function($templateid,$portnumber) use ($app,$person) {
	$tp=new TemplatePorts();
	$tp->TemplateID=$templateid;
	$tp->PortNumber=$portnumber;

	if(!$person->WriteAccess){
		$response['error']=true;
		$response['errorcode']=403;
		$response['message']=__("Unauthorized");
	}else{
		if(!$tp->getPort()){
			$response['error']=true;
			$response['errorcode']=404;
			$response['message']=__("Template port not found with id: ")." $templateid:$portnum";
		}else{
			foreach($app->request->post() as $prop => $val){
				$tp->$prop=$val;
			}
			if(!$tp->updatePort()){
				$response['error']=true;
				$response['errorcode']=404;
				$response['message']=__("Template port update failed");
			}else{
				$response['error']=false;
				$response['errorcode']=200;
				$response['dataport']=$tp;
			}
		}
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/devicetemplate/:templateid/slot/:slotnum
//	Method:	POST
//	Params:	
//		Required: templateid, slutnum
//		Optional: everything else
//	Returns: true/false on update operation
//

$app->post( '/devicetemplate/:templateid/slot/:slotnum', function($templateid,$slotnum) use ($app,$person) {
	$s=new Slot();
	$s->TemplateID=$templateid;
	$s->PortNumber=$slotnum;

	if(!$person->WriteAccess){
		$response['error']=true;
		$response['errorcode']=403;
		$response['message']=__("Unauthorized");
	}else{
		if(!$s->GetSlot()){
			$response['error']=true;
			$response['errorcode']=404;
			$response['message']=__("Template slot not found with id: ")." $templateid:$slotnum";
		}else{
			foreach($app->request->post() as $prop => $val){
				$s->$prop=$val;
			}
			// Just to make sure 
			$s->TemplateID=$templateid;
			$s->PortNumber=$slotnum;
			if(!$s->UpdateSlot()){
				$response['error']=true;
				$response['errorcode']=404;
				$response['message']=__("Template slot update failed");
			}else{
				$response['error']=false;
				$response['errorcode']=200;
				$response['dataport']=$s;
			}
		}
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/manufacturer
//	Method:	POST
//	Params:	none
//	Returns: true/false on update operation
//

$app->post( '/manufacturer/:manufacturerid', function($manufacturerid) use ($app,$person) {
	$man=new Manufacturer();
	$man->ManufacturerID=$manufacturerid;
	
	$response['error']=true;
	$response['errorcode']=404;

	if(!$person->SiteAdmin){
		$response['errorcode']=403;
		$response['message']=__("Unauthorized");
	}else{
		if(!$man->GetManufacturerByID()){
			$response['message']=__("Manufacturer not found with id: ")." $manufacturerid";
		}else{
			foreach($app->request->post() as $prop => $val){
				$man->$prop=$val;
			}
			if(!$man->UpdateManufacturer()){
				$response['message']=__("Manufacturer update failed");
			}else{
				$response['error']=false;
				$response['errorcode']=200;
			}
		}
	}

	echoResponse(200,$response);
});

/**
  *
  *		API PUT Methods go here
  *
  *		PUT Methods are for creating new records 
  *
  **/

//
//	URL:	/api/v1/people/:userid
//	Method: PUT
//	Params: userid (required, passed as :userid in URL)
//			lastname, firstname, phone1, phone2, phone3, email, adminowndevices, 
//			readaccess, writeaccess, deleteaccess, contactadmin, rackrequest, 
//			rackadmin, siteadmin
//	Returns: record as created
//
  
$app->put('/people/:userid', function($userid) use ($app,$person) {
	if ( !$person->ContactAdmin ) {
		$response['error'] = true;
		$response['errorcode'] = 400;
		$response['message'] = "Insufficient privilege level";
		echoResponse(200, $response);
		$app->stop();
	}
	
	$response = array();
	$p = new People();

	if ( $vars = json_decode( $app->request->getBody() )) {
		$p->UserID = $vars->UserID;
	} else {
		$p->UserID = $app->request->put('UserID');
	}

	if($p->GetPersonByUserID()){
		$response['error']=true;
		$response['errorcode']=403;
		$response['message']=__("UserID already in database.  Use the update API to modify record.");
		echoResponse(200, $response );
	} else {	
		if ( is_object( $vars )) {
			foreach ( $vars as $prop=>$val ) {
				$p->$prop = $val;
			}
		} else {
			// Slim Framework will simply return null for any variables that were not passed, so this is safe to call without blowing up the script
			foreach($p as $prop=>$val){
				$p->$prop=$app->request->put($prop);
			}
		}
		$p->Disabled = false;
		
		$p->CreatePerson();
		
		if($p->PersonID==false){
			$response['error']=true;
			$response['errorcode']=403;
			$response['message']=__("Unable to create People resource with the given parameters.");
			echoResponse(200,$response);
		}else{
			$response['error']=false;
			$responde['errorcode']=200;
			$response['message']=__("People resource created successfully.");
			$response['people']=$p;

			echoResponse(200,$response);
		}
	}
});

//
//	URL:	/api/v1/colorcode/:name
//	Method:	PUT
//	Params: 
//		Required: Name
//		Optional: DefaultNote
//	Returns: record as created
//

$app->put( '/colorcode/:colorname', function($colorname) use ($app) {
	$cc=new ColorCoding();

	// Allow for input as either PUT variables or a JSON payload
	if ( $vars = json_decode( $app->request->getBody() )) {
		foreach( $vars as $prop=>$val ) {
			$cc->$prop = $val;
		}
	} else {
		foreach( $cc as $prop=>$val ) {
			$cc->$prop=$app->request->put($prop);
		}
	}

	if(!$cc->CreateCode()){
		$response['error']=true;
		$response['errorcode']=403;
		$response['message']=__("Error creating new color.");
	}else{
		$response['error']=false;
		$response['errorcode']=200;
		$response['message']=__("New color created successfully.");
		$response['colorcode'][$cc->ColorID]=$cc;
	}
	echoResponse(200,$response);
});

//
//	URL:	/api/v1/device/:devicelabel
//	Method:	PUT
//	Params:	deviceid (passed in URL)
//		Required: Label, cabinetid
//		Optional: everything else
//	Returns: record as created 
//

$app->put( '/device/:devicelabel', function($devicelabel) use ($app) {
	$dev=new Device();

	// Allow for input as either PUT variables or a JSON payload
	if ( $vars = json_decode( $app->request->getBody() )) {
		foreach( $vars as $prop=>$val ) {
			$dev->$prop = $val;
		}
	} else {
		foreach( $dev as $prop=>$val ) {
			$dev->$prop=$app->request->put($prop);
		}
	}

	// We're creating a device and should load in the template values first
	// if requested.
	if(isset($dev->TemplateID) && $dev->TemplateID>0){
		$tmpl=new DeviceTemplate($dev->TemplateID);
		$tmpl->GetTemplateByID();
		foreach($tmpl as $prop => $val){
			$dev->$prop=$val;
		}
	}

	// Slurp all the data back in again, though, just in case someone specified an override from the template values
	// Yes, this is messy.
	if ( $vars = json_decode( $app->request->getBody() )) {
		foreach( $vars as $prop=>$val ) {
			$dev->$prop = $val;
		}
	} else {
		foreach( $dev as $prop=>$val ) {
			$dev->$prop=$app->request->put($prop);
		}
	}

	// This should be in the commit data but if we get a smartass saying it's in the URL
	$dev->Label=$devicelabel;

	$cab=new Cabinet();
	$cab->CabinetID=$dev->Cabinet;
	if(!$cab->GetCabinet()){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("Cabinet not found");
	}else{
		if($cab->Rights!="Write"){
			$response['error']=true;
			$response['errorcode']=403;
			$response['message']=__("Unauthorized");
		}else{
			if(!$dev->CreateDevice()){
				$response['error']=true;
				$response['errorcode']=404;
				$response['message']=__("Device creation failed");
			}else{
				// refresh the model in case we extended it elsewhere
				$dev=new Device($dev->DeviceID);
				$dev->GetDevice();
				$response['error']=false;
				$response['errorcode']=200;
				$response['device']=$dev;
			}
		}
	}

	echoResponse(200,$response);
});

//
//	Copy an existing device to the new position, adjusting the name automagically per rules in the CopyDevice method
//	URL:	/api/v1/device/:deviceid/copyto/:newposition
//	Method:	PUT
//	Params:	deviceid (passed in URL)
//		Required: Label, cabinetid
//		Optional: everything else
//	Returns: record as created 
//

$app->put( '/device/:deviceid/copyto/:newposition', function($deviceid, $newposition) use ($app) {
	$dev=new Device();
	$dev->DeviceID=$deviceid;
	$dev->GetDevice();

	$cab=new Cabinet();
	$cab->CabinetID=$dev->Cabinet;
	if(!$cab->GetCabinet()){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("Cabinet not found");
	}else{
		if($cab->Rights!="Write"){
			$response['error']=true;
			$response['errorcode']=403;
			$response['message']=__("Unauthorized");
		}else{
			if(!$dev->CopyDevice(null,$newposition,true)){
				$response['error']=true;
				$response['errorcode']=404;
				$response['message']=__("Device creation failed");
			}else{
				// refresh the model in case we extended it elsewhere
				$dev=new Device($dev->DeviceID);
				$dev->GetDevice();
				$response['error']=false;
				$response['errorcode']=200;
				$response['device']=$dev;
			}
		}
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/devicetemplate/:model
//	Method:	PUT
//	Params:	
//		Required: Label
//		Optional: everything else
//	Returns: record as created 
//

$app->put( '/devicetemplate/:model', function($model) use ($app,$person) {
	$dt=new DeviceTemplate();

	// Allow for input as either PUT variables or a JSON payload
	if ( $vars = json_decode( $app->request->getBody() )) {
		foreach( $vars as $prop=>$val ) {
			$dt->$prop = $val;
		}
	} else {
		foreach( $dt as $prop=>$val ) {
			$dt->$prop=$app->request->put($prop);
		}
	}

	// This should be in the commit data but if we get a smartass saying it's in the URL
	$dt->Model=$model;

	if(!$person->WriteAccess){
		$response['error']=true;
		$response['errorcode']=403;
		$response['message']=__("Unauthorized");
	}else{
		if(!$dt->CreateTemplate()){
			$response['error']=true;
			$response['errorcode']=404;
			$response['message']=__("Device template creation failed");
		}else{
			// refresh the model in case we extended it elsewhere
			$d=new DeviceTemplate($dt->TemplateID);
			$d->GetTemplateByID();
			$response['error']=false;
			$response['errorcode']=200;
			$response['devicetemplate']=$d;
		}
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/devicetemplate/:templateid/dataport/:portnum
//	Method:	PUT
//	Params:	
//		Required: templateid, portnum, portlabel
//		Optional: everything else
//	Returns: record as created 
//

$app->put( '/devicetemplate/:templateid/dataport/:portnum', function($templateid,$portnum) use ($app,$person) {
	$tp=new TemplatePorts();
	
	// Allow for input as either PUT variables or a JSON payload
	if ( $vars = json_decode( $app->request->getBody() )) {
		foreach( $vars as $prop=>$val ) {
			$tp->$prop = $val;
		}
	} else {
		foreach( $tp as $prop=>$val ) {
			$tp->$prop=$app->request->put($prop);
		}
	}

	// This should be in the commit data but if we get a smartass saying it's in the URL
	$tp->TemplateID=$templateid;
	$tp->PortNumber=$portnum;

	if(!$person->WriteAccess){
		$response['error']=true;
		$response['errorcode']=403;
		$response['message']=__("Unauthorized");
	}else{
		if(!$tp->CreatePort()){
			$response['error']=true;
			$response['errorcode']=404;
			$response['message']=__("Device template port creation failed");
		}else{
			$response['error']=false;
			$response['errorcode']=200;
			$response['dataport']=$tp;
		}
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/devicetemplate/:templateid/powerport/:portnum
//	Method:	PUT
//	Params:	
//		Required: templateid, portnum, portlabel
//		Optional: everything else
//	Returns: record as created 
//

$app->put( '/devicetemplate/:templateid/powerport/:portnum', function($templateid,$portnum) use ($app,$person) {
	$tp=new TemplatePowerPorts();

	// Allow for input as either PUT variables or a JSON payload
	if ( $vars = json_decode( $app->request->getBody() )) {
		foreach( $vars as $prop=>$val ) {
			$tp->$prop = $val;
		}
	} else {
		foreach( $tp as $prop=>$val ) {
			$tp->$prop=$app->request->put($prop);
		}
	}

	// This should be in the commit data but if we get a smartass saying it's in the URL
	$tp->TemplateID=$templateid;
	$tp->PortNumber=$portnum;

	if(!$person->WriteAccess){
		$response['error']=true;
		$response['errorcode']=403;
		$response['message']=__("Unauthorized");
	}else{
		if(!$tp->CreatePort()){
			$response['error']=true;
			$response['errorcode']=404;
			$response['message']=__("Device template port creation failed");
		}else{
			$response['error']=false;
			$response['errorcode']=200;
			$response['powerport']=$tp;
		}
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/devicetemplate/:templateid/slot/:slotnum
//	Method:	PUT
//	Params:	
//		Required: templateid, slotnum
//		Optional: everything else
//	Returns: record as created 
//

$app->put( '/devicetemplate/:templateid/slot/:slotnum', function($templateid,$slotnum) use ($app,$person) {
	$s=new Slot();
	foreach($app->request->put() as $prop => $val){
		$s->$prop=$val;
	}
	// This should be in the commit data but if we get a smartass saying it's in the URL
	$s->TemplateID=$templateid;
	$s->Position=$slotnum;

	if(!$person->WriteAccess){
		$response['error']=true;
		$response['errorcode']=403;
		$response['message']=__("Unauthorized");
	}else{
		if(!$s->CreateSlot()){
			$response['error']=true;
			$response['errorcode']=404;
			$response['message']=__("Device template slot creation failed");
		}else{
			$response['error']=false;
			$response['errorcode']=200;
			$response['powerport']=$s;
		}
	}

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/manufacturer/:name
//	Method:	PUT
//	Params:	none
//	Returns: Record as created 
//

$app->put( '/manufacturer/:name', function($name) use ($app,$person) {
	$man=new Manufacturer();

	// Allow for input as either PUT variables or a JSON payload
	if ( $vars = json_decode( $app->request->getBody() )) {
		foreach( $vars as $prop=>$val ) {
			$man->$prop = $val;
		}
	} else {
		foreach( $man as $prop=>$val ) {
			$man->$prop=$app->request->put($prop);
		}
	}

	$man->Name=$name;
	
	$response['error']=true;
	$response['errorcode']=404;

	if(!$person->SiteAdmin){
		$response['errorcode']=403;
		$response['message']=__("Unauthorized");
	}else{
		if(!$man->CreateManufacturer()){
			$response['message']=__("Manufacturer not created: ")." $manufacturerid";
		}else{
			$response['error']=false;
			$response['errorcode']=200;
			$response['manufacturer']=$man;
		}
	}

	echoResponse(200,$response);
});
/**
  *
  *		API DELETE Methods go here
  *
  *		DELETE Methods are for removing records 
  *
  **/

//
//	URL:	/api/v1/powerport/:deviceid
//	Method:	DELETE
//	Params:	
//		required: DeviceID, PortNumber
//		optional: Label, ConnectedDeviceID, ConnectedPort, Notes
//	Returns:  true/false on update operation
//

$app->delete( '/powerport/:deviceid', function($deviceid) use ($app, $person) {
	$pp=new PowerPorts();
	$pp->DeviceID=$deviceid;
	foreach($app->request->delete() as $prop => $val){
		$pp->$prop=$val;
	}

	function updatedevice($deviceid){
		$dev=new Device();
		$dev->DeviceID=$deviceid;
		$dev->GetDevice();
		$dev->PowerSupplyCount=$dev->PowerSupplyCount-1;
		$dev->UpdateDevice();
	}

	// If this port isn't the last port then we're gonna shuffle ports to keep the ids in orderish
	$lastport=end($pp->getPorts());
	if($lastport->PortNumber!=$pp->PortNumber){
		foreach($lastport as $prop=>$value){
			if($prop!="PortNumber"){
				$pp->$prop=$value;
			}
		}
		if($pp->updatePort()){
			if($lastport->removePort()){
				updatedevice($pp->DeviceID);
				$response['error']=false;
			}else{
				$response['error']=true;
			}
		}else{
			$response['error']=true;
		}
	}else{ // Last available port, just delete it.
		if($pp->removePort()){
			updatedevice($pp->DeviceID);
			$response['error']=false;
		}else{
			$response['error']=true;
		}
	}

	$response['errorcode']=200;

	echoResponse(200,$response);
});

//
//	URL:	/api/v1/colorcode/:colorid
//	Method:	DELETE
//	Params:	colorid (passed in URL)
//	Returns:  true/false on update operation
//

$app->delete( '/colorcode/:colorid', function($colorid) {
	$cc=new ColorCoding();
	$cc->ColorID=$colorid;
	
	if(!$cc->DeleteCode()){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("Failed to delete color with ColorID")." $cc->ColorID";
	}else{
		$response['error']=false;
		$response['errorcode']=200;
	}
	echoResponse(200,$response);
});

//
//	URL:	/api/v1/device/:deviceid
//	Method:	DELETE
//	Params:	deviceid (passed in URL)
//	Returns:  true/false on update operation
//

$app->delete( '/device/:deviceid', function($deviceid) {
	$dev=new Device();
	$dev->DeviceID=$deviceid;
	
	if(!$dev->GetDevice()){
		$response['error']=true;
		$response['errorcode']=404;
		$response['message']=__("Device doesn't exist");
	}else{
		if($dev->Rights!="Write"){
			$response['error']=true;
			$response['errorcode']=403;
			$response['message']=__("Unauthorized");
		}else{
			if(!$dev->DeleteDevice()){
				$response['error']=true;
				$response['errorcode']=404;
				$response['message']=__("An unknown error has occured");
			}else{
				$response['error']=false;
				$response['errorcode']=200;
			}
		}
	}
	echoResponse(200,$response);
});

$app->run();
?>
