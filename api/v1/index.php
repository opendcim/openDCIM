<?php

	require_once( "../../db.inc.php" );

	// Not really a loginPage, but this variable will keep us from getting redirected when
	// using LDAP auth and there's no session (so true RESTful API capability)
	//
	if ( AUTHENTICATION == "LDAP" ) {
		$loginPage = true;
	}

	require_once( "../../facilities.inc.php" );
	
	use Psr\Http\Message\ServerRequestInterface as Request;
	use Psr\Http\Message\ResponseInterface as Response;

	$configuration = [
		'settings' => [
			'displayErrorDetails' => true,
		],
	];

	$c = new \Slim\Container($configuration);
	
	$app = new \Slim\App($c);

	$container = $app->getContainer();

	$container['view'] = function( $c ) {
		return new \Slim\Views\JsonView();
	};

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

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */

// Since Middleware is applied in reverse order that it is added, make sure the Authentication function below is always the last one

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
$app->get('/people', function(Request $request, Response $response) {
	global $person;
	
	$person->GetUserRights();
	if(!$person->ContactAdmin){
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = "Insufficient privilege level.";
		return $this->view->render( $response, $r, 401 );
	}else{
		$sp=new People();
		$loose = false;
		$outputAttr = array();
		$attrList = $request->getQueryParams();
		foreach($attrList as $prop => $val){
			if ( strtoupper($prop) == "WILDCARDS" ) {
				$loose = true;
			} elseif ( strtoupper($prop) == "ATTRIBUTES" ) {
				$outputAttr = explode( ",", $val );
			} elseif ( property_exists( $sp, $prop )) {
				$sp->$prop=$val;
			}
		}

		$r = array();
		$r['error'] = false;
		$r['errorcode'] = 200;
		$r['people'] = specifyAttributes( $outputAttr, $sp->Search( false, $loose ));
		return $this->view->render( $response, $r, 200 );		
	}
});

//
//	URL:  /api/v1/department
//	Method: GET
//	Params:  none
//	Returns:  List of all departments in the database
//

$app->get('/department', function(Request $request, Response $response) {
	global $person;

	if ( !$person->ContactAdmin){
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = "Insufficient privilege level.";
		return $this->view->render( $response, $r, 401 );
	}

	$dList = array();
	$dept=new Department();
	$loose = false;
	$outputAttr = array();
	$attrList = $request->getQueryParams();
	foreach($attrList as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif ( strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} elseif( property_exists( $dept, $prop )) {
			$dept->$prop=$val;
		}
	}		

	$r = array();
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['department'] = specifyAttributes( $outputAttr, $dept->Search( false, $loose ));
	return $this->view->render( $response, $r, 200 );
});

//
//	URL:	/api/v1/datacenter
//	Method: GET
//	Params:  none
//	Returns: List of all data centers in the database
//

$app->get('/datacenter', function(Request $request, Response $response) {
	// Don't have to worry about rights, other than basic connection, to get data center list
	
	$dc = new DataCenter();
	$dcList = $dc->GetDCList();

	$outputAttr = array();
	$loose = false;

	foreach( $request->getQueryParams() as $prop=>$val ) {
		if (strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif( strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} else {
			$dc->$prop = $val;
		}
	}

	$r = array();
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['department'] = specifyAttributes( $outputAttr, $dc->Search( false, $loose ));
	return $this->view->render( $response, $r, 200 );

});

//
//	URL:	/api/v1/datacenter/:id
//  Method: GET
//	Params:  DataCenterID (passed in URL as :id)
//	Returns: Details of specified datacenter
//

$app->get( '/datacenter/{id}', function( Request $request, Response $response, $args ) {
	$dc = new DataCenter();
	$r = array();
	$dc->DataCenterID = intval($args['id']);
	if ( ! $dc->GetDataCenter() ) {
		$r['error'] = true;
		$r['errorcode'] = 400;
		$r['message'] = 'The requested resource does not exist.';
	} else {
		$r['error'] = false;
		$r['errorcode'] = 200;
		$r['datacenter'] = array();
		$tmp = array();
		foreach( $dc as $prop=>$value ) {
			$tmp[$prop] = $value;
		}
		array_push( $r['datacenter'], $tmp );
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/cabinet
//	Method:	GET
//	Params: None
//	Returns: All cabinet information
//

$app->get( '/cabinet', function(Request $request, Response $response) {
	$cab = new Cabinet;
	$dc = new DataCenter();
	$loose = false;
	$outputAttr = array();

	foreach($request->getQueryParams() as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif ( strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} elseif( property_exists( $cab, $prop ) ) {
			$cab->$prop=$val;
		}
	}

	$cList = specifyAttributes( $outputAttr, $cab->Search(false, $loose));
	
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['cabinet'] = array();
	
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
		
		array_push( $r['cabinet'], $tmp );
	}
	
	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/cabinet/:cabinetid
//	Method:	GET
//	Params: cabinetid (passed in URL)
//	Returns: All cabinet information for given ID
//

$app->get( '/cabinet/{cabinetid}', function(Request $request, Response $response, $args) {
	$cab = new Cabinet;
	$dc = new DataCenter();
	if ( ! $cab->CabinetID = intval($args['cabinetid']) ) {
		$r['error'] = true;
		$r['errorcode'] = 400;
		$r['message'] = 'No cabinet found with CabinetID of '. $cabinetid;
	} else {
		$r['error'] = false;
		$r['errorcode'] = 200;
		$r['cabinet'] = array();

		$cab->GetCabinet();
		
		$tmp = array();
		foreach( $cab as $prop=>$value ) {
			$tmp[$prop] = $value;
		}
		$dc->DataCenterID = $cab->DataCenterID;
		$dc->GetDataCenter();
		
		$tmp['DataCenterName'] = $dc->Name;
		
		array_push( $r['cabinet'], $tmp );
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/cabinet/bydc/:datacenterid
//	Method:	GET
//	Params: datacenterid (passed in URL)
//	Returns: All cabinet information within the given data center, if any
//

$app->get( '/cabinet/bydc/{datacenterid}', function(Request $request, Response $response, $args ) {
	$cab = new Cabinet;
	$dc = new DataCenter();
	$cab->DataCenterID = intval($args['datacenterid']);
	$cList = $cab->ListCabinetsByDC();
	
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['cabinet'] = array();
	
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
		
		array_push( $r['cabinet'], $tmp );
	}
	
	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/cabinet/:cabinetid/sensors
//	Method:	GET
//	Params: cabinetid (passed in URL)
//	Returns: All cabinet sensor information for the specified cabinet, if any
//

$app->get( '/cabinet/{cabinetid}/sensor', function( Request $request, Response $response, $args ) {
	global $config;
	
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['sensors'] = array();

	if ( $m = CabinetMetrics::getMetrics($args['cabinetid']) ) {
		$m->mUnits = $config->ParameterArray["mUnits"];
		$r['sensors'] = $m;
	}	

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/cabinet/bydept/:deptid
//	Method:	GET
//	Params: deptid (passed in URL)
//	Returns: All cabinet information for cabinets assigned to supplied deptid
//

$app->get( '/cabinet/bydept/{deptid}', function( Request $request, Response $response, $args ) {
	$cab = new Cabinet;
	$dc = new DataCenter();
	$cab->DeptID=intval($args['deptid']);
	$cList = $cab->GetCabinetsByDept();
	
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['cabinet'] = array();
	
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
		
		array_push( $r['cabinet'], $tmp );
	}
	
	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/device
//	Method:	GET
//	Params:	none
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device', function( Request $request, Response $response ) {
	$dev=new Device();
	
	$r['error']=false;
	$r['errorcode']=200;
	$loose = false;
	$outputAttr = array();

	foreach($request->getQueryParams() as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif (strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} else {
			$dev->$prop=$val;
		}
	}
	
	$devList = $dev->Search( false, $loose );

	$r['device']=specifyAttributes( $outputAttr, $devList );

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/device/:deviceid
//	Method:	GET
//	Params:	deviceid (passed in URL)
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device/{deviceid}', function( Request $request, Response $response, $args ) {
	$dev=new Device();
	$dev->DeviceID=intval($args['deviceid']);
	
	if(!$dev->GetDevice()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No device found with DeviceID").$deviceid;
		return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=false;
		$r['errorcode']=200;
		$r['device']=$dev;
	}

	return $this->view->render( $response, $r, $r['errorcode'] );

});

//
//	URL:	/api/v1/device/:deviceid/getpicture
//	Method:	GET
//	Params:	
//		required: deviceid (passed in URL)
//		optional: rear (will return the rear face of the device) 
//	Returns:  HTML representation of a device
//

$app->get( '/device/{deviceid}/getpicture', function( Request $request, Response $response, $args ) {
	$dev=new Device($args['deviceid']);
	
	$r['error']=true;
	$r['errorcode']=404;
	$r['message']=__("Unknown error");

	if(!$dev->GetDevice()){
		$r['message']=__("Device not found");
	}else{
		// we filter out most of the details if you don't have rights in the 
		// GetDevicePicture function so this might lead to some probing but 
		// should be minimal
		$r['error']=false;
		$r['errorcode']=200;
		$r['message']="";
		$r['picture']=$dev->GetDevicePicture(isset($_GET['rear']));
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});

$app->get( '/device/{deviceid}/getsensorreadings', function( Request $request, Response $response, $args ) {
	$dev=new Device();
	$dev->DeviceID=intval($args['deviceid']);
	
	if(!$dev->GetDevice(false)){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("Device not found");
	}else{
		$reading=$dev->GetSensorReading(false);
		if(!$reading){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("Device is not a sensor or is missing a template");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['sensor']=$reading;
		}
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});

// this is messy as all hell and i'm still thinking about how to do it better

//
//	URL:	/api/vi/deviceport/:deviceid
//
//	Method:	GET
//	Params:
//		Required:  :deviceid - DeviceID for which you wish to retrieve ports
//	Returns:	All ports for the given device

$app->get('/deviceport/{deviceid}', function( Request $request, Response $response, $args ) {
	$dp = new DevicePorts();
	
	$r['error'] = false;
	$r['errorcode'] = 200;
	$dp->DeviceID = $args['deviceid'];
	$r['deviceport']=$dp->getPorts();

	return $this->view->render( $response, $r, $r['errorcode'] );
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

$app->get( '/deviceport/{deviceid}/patchcandidates', function( Request $request, Response $response, $args ) {
	$s=new stdClass();
	$s->portnumber=$request->getQueryParams['portnumber'];
	$s->connectto=$request->getQueryParams['connectto'];
	$s->listports=$request->getQueryParams['listports'];
	$s->patchpanels=$request->getQueryParams['patchpanels'];
	$s->limiter=$request->getQueryParams['limiter'];

	$r['error']=false;
	$r['errorcode']=200;
	
	// I like nulls and wrote this function originally around them
	foreach($s as $prop => $val){
		$s->$prop=(!$val)?null:$val;
	}

	if(is_null($s->listports)){
		$r['device']=DevicePorts::getPatchCandidates($args['deviceid'],$s->portnumber,null,$s->patchpanels,$s->limiter);
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
		if($dp->DeviceID == $args['deviceid'] && isset($list[$s->portnumber])){
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

		$r['deviceport']=$list;
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/device/bydatacenter/:datacenterid
//	Method:	GET
//	Params:	datacenterid (passed in URL)
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device/bydatacenter/{datacenterid}', function( Request $request, Response $response, $args ) {
	$dev=new Device();
	
	$r['error']=false;
	$r['errorcode']=200;
	$r['device']=$dev->GetDeviceList(intval($args['datacenterid']));

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/device/byproject/:projectid
//	Method:	GET
//	Params:	projectid (passed in URL)
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device/byproject/{projectid}', function( Request $request, Response $response, $args ) {
	$r['error']=false;
	$r['errorcode']=200;
	$r['device']=ProjectMembership::getProjectMembership( $args['projectid'] );

	return $this->view->render( $response, $r, $r['errorcode'] );
});


//
//	URL:	/api/v1/project
//	Method:	GET
//	Params:	None
//	Returns:  All project metadata
//

$app->get( '/project', function( Request $request, Response $response ) {
	$r['error']=false;
	$r['errorcode']=200;
	$r['project']=Projects::getProjectList();

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/project/bydevice/:deviceid
//	Method:	GET
//	Params:	DeviceID
//	Returns:  All project metadata for projects the deviceid is a member of
//

$app->get( '/project/bydevice/{deviceid}', function( Request $request, Response $response, $args ) {
	$r['error']=false;
	$r['errorcode']=200;
	$r['project']=ProjectMembership::getDeviceMembership( $args['deviceid'] );

	return $this->view->render( $response, $r, $r['errorcode'] );
});


//
//	URL:	/api/v1/powerport/:deviceid
//	Method:	GET
//	Params:	deviceid (required), portnumber (optional)
//	Returns:  All power ports for a device or a specific port
//

$app->get( '/powerport/{deviceid}', function( Request $request, Response $response, $args ) {
	$pp=new PowerPorts();
	
	$r['error']=false;
	$r['errorcode']=200;
	$pp->DeviceID=$args['deviceid'];
	foreach($app->request->get() as $prop => $val){
		$pp->$prop=$val;
	}

	if($pp->PortNumber){
		if(!$pp->getPort()){
			$r['error']=true;
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

		$r['powerport'][$pp->PortNumber]=$pp;
	}else{
		$r['powerport']=$pp->getPorts();
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});


//
//	URL:	/api/v1/colorcode
//	Method:	GET
//	Params:	none
//	Returns:  All defined color codes 
//

$app->get( '/colorcode', function() {
	$r['error']=false;
	$r['errorcode']=200;
	$r['colorcode']=ColorCoding::GetCodeList();;
		
	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/colorcode/:colorid
//	Method:	GET
//	Params:	colorid (passed in URL)
//	Returns:  All defined color codes matching :colorid 
//

$app->get( '/colorcode/{colorid}', function( Request $request, Response $response, $args ) {
	$cc=new ColorCoding();
	$cc->ColorID=$args['colorid'];
	
	if(!$cc->GetCode()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No color code found with ColorID")." $cc->ColorID";
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['colorcode'][$cc->ColorID]=$cc;
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/colorcode/:colorid/timesused
//	Method:	GET
//	Params:	colorid (passed in URL)
//	Returns:  Number of objects using :colorid 
//

$app->get( '/colorcode/:colorid/timesused', function($colorid) {
	$r['error']=false;
	$r['errorcode']=200;
	$r['colorcode']=ColorCoding::TimesUsed($colorid);
	
	return $this->view->render( $response, $r, $r['errorcode'] );
});


//
//	URL:	/api/v1/devicetemplate
//	Method:	GET
//	Params: none	
//	Returns: All available device templates
//

$app->get( '/devicetemplate', function() {
	$dt=new DeviceTemplate();
	$r['error']=false;
	$r['errorcode']=200;
	$loose = false;
	$outputAttr = array();

	foreach($request->getQueryParams() as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif (strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} elseif (property_exists( $dt, $prop )) {
			$dt->$prop=$val;
		}
	}
	
	$tmpList = $dt->Search( false, $loose );

	$r['devicetemplate']=specifyAttributes( $outputAttr, $tmpList );

	return $this->view->render( $response, $r, $r['errorcode'] );
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

$app->get( '/devicetemplate/{templateid}', function( Request $request, Response $response, $args ) {
	if($templateid=='image'){
		$r['error']=false;
		$r['errorcode']=200;
		$r['image']=DeviceTemplate::getAvailableImages();
	}else{
		$dt=new DeviceTemplate();
		$dt->TemplateID=$args['templateid'];
		if(!$dt->GetTemplateByID()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("No template found with TemplateID: ")." ".$args['templateid'];
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['template']=$dt;
		}
	}
	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/dataport
//	Method:	GET
//	Params: templateid
//	Returns: Data ports defined for device template with templateid
//

$app->get( '/devicetemplate/{templateid}/dataport', function( Request $request, Response $response, $args ) {
	$tp=new TemplatePorts();
	$tp->TemplateID=$args['templateid'];
	if(!$ports=$tp->getPorts()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No ports found for TemplateID: ")." ".$args['templateid'];
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['dataport']=$ports;
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/dataport/:portnumber
//	Method:	GET
//	Params: templateid. portnumber
//	Returns: Single data port defined for device template with templateid and portnum
//

$app->get( '/devicetemplate/{templateid}/dataport/{portnumber}', function( Request $request, Response $response, $args ) {
	$tp=new TemplatePorts();
	$tp->TemplateID=$args['templateid'];
	$tp->PortNumber=$args['portnumber'];
	if(!$tp->getPort()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("Port not found for TemplateID: ")." ".$args['templateid'].":".$args['portnumber'];
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['dataport']=$tp;
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/powerport
//	Method:	GET
//	Params: templateid
//	Returns: Power ports defined for device template with templateid
//

$app->get( '/devicetemplate/{templateid}/powerport', function( Request $request, Response $response, $args ) {
	$tp=new TemplatePowerPorts();
	$tp->TemplateID=$args['templateid'];
	if(!$ports=$tp->getPorts()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No ports found for TemplateID: ")." ".$args['templateid'];
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['powerport']=$ports;
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/slot
//	Method:	GET
//	Params: templateid
//	Returns: Slots defined for device template with templateid
//

$app->get( '/devicetemplate/{templateid}/slot', function( Request $request, Response $response, $args ) {
	if(!$slots=slot::GetAll($args['templateid'])){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No slots found for TemplateID: ")." ".$args['templateid'];
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['slot']=$slots;
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/manufacturer
//	Method:	GET
//	Params:	none
//	Returns:  All defined manufacturers 
//

$app->get( '/manufacturer', function( Request $request, Response $response ) {
	$man=new Manufacturer();
	
	$r['error']=false;
	$r['errorcode']=200;
	foreach($request->getQueryParams() as $prop => $val){
		$man->$prop=$val;
	}
	$r['manufacturer']=$man->GetManufacturerList();

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/zone
//	Method:	GET
//	Params:	none
//	Returns:  All zones for which the user's rights have access to view
//

$app->get( '/zone', function( Request $request, Response $response ) {
	$zone=new Zone();
	
	$r['error']=false;
	$r['errorcode']=200;
	foreach($request->getQueryParams() as $prop => $val){
		$zone->$prop=$val;
	}
	$r['zone']=$zone->Search(true);

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/zone/:zoneid
//	Method:	GET
//	Params:	none
//	Returns: Zone identified by :zoneid 
//

$app->get( '/zone/{zoneid}', function( Request $request, Response $response, $args ) {
	$zone=new Zone();
	$zone->ZoneID=$args['zoneid'];
	
	$r['error']=false;
	$r['errorcode']=200;
	foreach($request->getQueryParams() as $prop => $val){
		$dev->$prop=$val;
	}
	$r['zone']=$zone->GetZone();

	return $this->view->render( $response, $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/cabrow
//	Method:	GET
//	Params:	none
//	Returns:  All cabinet rows for which the user's rights have access to view
//

$app->get( '/cabrow', function( Request $request, Response $response ) {
	$cabrow=new CabRow();
	
	$r['error']=false;
	$r['errorcode']=200;
	foreach($request->getQueryParams() as $prop => $val){
		$cabrow->$prop=$val;
	}
	$r['cabrow']=$cabrow->Search(true);

	return $this->view->render( $response, $r, $r['errorcode'] );
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

$app->post('/people/{personid}', function( Request $request, Response $response, $args ) use ($person) {
	if(!$person->ContactAdmin){
		$r['error']=true;
		$r['errorcode']=400;
		$r['message']=__("Insufficient privilege level");
	} else {
		$r = array();
		$p=new People();
		$p->PersonID=$args['personid'];
		if(!$p->GetPerson()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("UserID=" . $p->PersonID . " not found in database.");
		} else {	
			// Slim Framework will simply return null for any variables that were not passed, so this is safe to call without blowing up the script
			$vars = $request->getParsedBody();
			foreach($p as $prop => $val){
				if ( isset( $vars[$prop] ) ){
					$p->$prop=$vars[$prop];
				}
			}
			$p->Disabled=false;
			
			if(!$p->UpdatePerson()){
				$r['error']=true;
				$r['errorcode']=403;
				$r['message']=__("Unable to update People resource with the given parameters.");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
				$r['message']=sprintf(__('People resource for UserID=%s updated successfully.'),$p->UserID);
				$r['people']=$p;
			}
		}
	}

	// Possible to-do list for someone to figure out...  why the $app->view scope isn't included
	// when you have the use($person) clause - also doesn't work if you make it use ($app, $person)
	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
});

//
//	URL:	/api/v1/people
//	Method: POST
//	Params:
//		Required: peopleid, newpeopleid
//	Returns: true / false on the updates being successful 
//

$app->post('/people/{peopleid}/transferdevicesto/{newpeopleid}', function( Request $request, Response $response, $args ) use ( $person) {
	$r['error']=false;
	$r['errorcode']=200;

	// Verify the userids are real
	foreach(array('peopleid','newpeopleid') as $var){
		$p=new People();

		$p->UserID=$args[$var];
		if(!$p->GetPerson() && ($var!='newpeopleid' && $args[$var]==0)){
			$r['error']=true;
			$r['message']="$var is not valid";
			continue;
		}
	}

	// If we error above don't attempt to make changes
	if(!$r['error']){
		$dev=new Device();
		$dev->PrimaryContact=$args['peopleid'];
		foreach($dev->Search() as $d){
			$d->PrimaryContact=$args['newpeopleid'];
			if(!$d->UpdateDevice()){
				// If we encounter an error stop immediately
				$r['error']=true;
				$r['message']=__("Device update has failed");
				continue;
			}
		}
	}

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
});

//
//	URL:	/api/v1/powerport/:deviceid
//	Method:	POST
//	Params:	
//		required: DeviceID, PortNumber
//		optional: Label, ConnectedDeviceID, ConnectedPort, Notes
//	Returns:  true/false on update operation
//

$app->post( '/powerport/{deviceid}', function( Request $request, Response $response, $args ) use ($person) {
	$pp=new PowerPorts();
	$pp->DeviceID=$args['deviceid'];
	$vars = $request->getParsedBody();
	foreach($vars as $prop => $val){
		$pp->$prop=$val;
	}

	$r['error']=($pp->updatePort())?false:true;
	$r['errorcode']=200;

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
});

//
//	URL:	/api/v1/colorcode/:colorid
//	Method:	POST
//	Params:	
//		required: ColorID, Name
//		optional: DefaultNote 
//	Returns:  true/false on update operation
//

$app->post( '/colorcode/{colorid}', function( Request $request, Response $response, $args ) use ($person) {
	$cc=new ColorCoding();
	$vars = $request->getParsedBody();
	foreach($vars as $prop => $val){
		$cc->$prop=$val;
	}

	$r['error']=($cc->UpdateCode())?false:true;
	$r['errorcode']=200;

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
});
/*
//
//	URL:	/api/v1/colorcode/:colorid/replacewith/:newcolorid
//	Method:	POST
//	Params:	
//		required: ColorID, NewColorID
//		optional: DefaultNote, Name
//	Returns:  true/false on update operation
//

$app->post( '/colorcode/:colorid/replacewith/:newcolorid', function($colorid,$newcolorid) use ($app) {
	$r['error']=(ColorCoding::ResetCode($colorid,$newcolorid))?false:true;
	$r['errorcode']=200;

	return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No device found with DeviceID").$deviceid;
	}else{
		if($dev->Rights!="Write"){
			$r['error']=true;
			$r['errorcode']=403;
			$r['message']=__("Unauthorized");
		}else{
			foreach($app->request->post() as $prop => $val){
				$dev->$prop=$val;
			}
			if(!$dev->UpdateDevice()){
				$r['error']=true;
				$r['errorcode']=404;
				$r['message']=__("Update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
			}
		}
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=403;
		$r['message']=__("Unauthorized");
	}else{
		if(!$dt->GetTemplateByID()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("No device template found with TemplateID: ").$templateid;
		}else{
			foreach($app->request->post() as $prop => $val){
				$dt->$prop=$val;
			}
			if(!$dt->UpdateTemplate()){
				$r['error']=true;
				$r['errorcode']=404;
				$r['message']=__("Device template update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
			}
		}
	}
	return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=403;
		$r['message']=__("Unauthorized");
	}else{
		if(!$tp->getPort()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("Template port not found with id: ")." $templateid:$portnum";
		}else{
			foreach($app->request->post() as $prop => $val){
				$tp->$prop=$val;
			}
			if(!$tp->updatePort()){
				$r['error']=true;
				$r['errorcode']=404;
				$r['message']=__("Template port update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
				$r['dataport']=$tp;
			}
		}
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=403;
		$r['message']=__("Unauthorized");
	}else{
		if(!$s->GetSlot()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("Template slot not found with id: ")." $templateid:$slotnum";
		}else{
			foreach($app->request->post() as $prop => $val){
				$s->$prop=$val;
			}
			// Just to make sure 
			$s->TemplateID=$templateid;
			$s->PortNumber=$slotnum;
			if(!$s->UpdateSlot()){
				$r['error']=true;
				$r['errorcode']=404;
				$r['message']=__("Template slot update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
				$r['dataport']=$s;
			}
		}
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
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
	
	$r['error']=true;
	$r['errorcode']=404;

	if(!$person->SiteAdmin){
		$r['errorcode']=403;
		$r['message']=__("Unauthorized");
	}else{
		if(!$man->GetManufacturerByID()){
			$r['message']=__("Manufacturer not found with id: ")." $manufacturerid";
		}else{
			foreach($app->request->post() as $prop => $val){
				$man->$prop=$val;
			}
			if(!$man->UpdateManufacturer()){
				$r['message']=__("Manufacturer update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
			}
		}
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});

/**
  *
  *		API PUT Methods go here
  *
  *		PUT Methods are for creating new records 
  *
  **/

/*
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
		$r['error'] = true;
		$r['errorcode'] = 400;
		$r['message'] = "Insufficient privilege level";
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
		$r['error']=true;
		$r['errorcode']=403;
		$r['message']=__("UserID already in database.  Use the update API to modify record.");
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
			$r['error']=true;
			$r['errorcode']=403;
			$r['message']=__("Unable to create People resource with the given parameters.");
			return $this->view->render( $response, $r, $r['errorcode'] );
		}else{
			$r['error']=false;
			$responde['errorcode']=200;
			$r['message']=__("People resource created successfully.");
			$r['people']=$p;

			return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=403;
		$r['message']=__("Error creating new color.");
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['message']=__("New color created successfully.");
		$r['colorcode'][$cc->ColorID]=$cc;
	}
	return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("Cabinet not found");
	}else{
		if($cab->Rights!="Write"){
			$r['error']=true;
			$r['errorcode']=403;
			$r['message']=__("Unauthorized");
		}else{
			if(!$dev->CreateDevice()){
				$r['error']=true;
				$r['errorcode']=404;
				$r['message']=__("Device creation failed");
			}else{
				// refresh the model in case we extended it elsewhere
				$dev=new Device($dev->DeviceID);
				$dev->GetDevice();
				$r['error']=false;
				$r['errorcode']=200;
				$r['device']=$dev;
			}
		}
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("Cabinet not found");
	}else{
		if($cab->Rights!="Write"){
			$r['error']=true;
			$r['errorcode']=403;
			$r['message']=__("Unauthorized");
		}else{
			if(!$dev->CopyDevice(null,$newposition,true)){
				$r['error']=true;
				$r['errorcode']=404;
				$r['message']=__("Device creation failed");
			}else{
				// refresh the model in case we extended it elsewhere
				$dev=new Device($dev->DeviceID);
				$dev->GetDevice();
				$r['error']=false;
				$r['errorcode']=200;
				$r['device']=$dev;
			}
		}
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=403;
		$r['message']=__("Unauthorized");
	}else{
		if(!$dt->CreateTemplate()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("Device template creation failed");
		}else{
			// refresh the model in case we extended it elsewhere
			$d=new DeviceTemplate($dt->TemplateID);
			$d->GetTemplateByID();
			$r['error']=false;
			$r['errorcode']=200;
			$r['devicetemplate']=$d;
		}
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=403;
		$r['message']=__("Unauthorized");
	}else{
		if(!$tp->CreatePort()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("Device template port creation failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['dataport']=$tp;
		}
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=403;
		$r['message']=__("Unauthorized");
	}else{
		if(!$tp->CreatePort()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("Device template port creation failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['powerport']=$tp;
		}
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=403;
		$r['message']=__("Unauthorized");
	}else{
		if(!$s->CreateSlot()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("Device template slot creation failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['powerport']=$s;
		}
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
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
	
	$r['error']=true;
	$r['errorcode']=404;

	if(!$person->SiteAdmin){
		$r['errorcode']=403;
		$r['message']=__("Unauthorized");
	}else{
		if(!$man->CreateManufacturer()){
			$r['message']=__("Manufacturer not created: ")." $manufacturerid";
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['manufacturer']=$man;
		}
	}

	return $this->view->render( $response, $r, $r['errorcode'] );
});
/**
  *
  *		API DELETE Methods go here
  *
  *		DELETE Methods are for removing records 
  *
  **/

/*

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
				$r['error']=false;
			}else{
				$r['error']=true;
			}
		}else{
			$r['error']=true;
		}
	}else{ // Last available port, just delete it.
		if($pp->removePort()){
			updatedevice($pp->DeviceID);
			$r['error']=false;
		}else{
			$r['error']=true;
		}
	}

	$r['errorcode']=200;

	return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("Failed to delete color with ColorID")." $cc->ColorID";
	}else{
		$r['error']=false;
		$r['errorcode']=200;
	}
	return $this->view->render( $response, $r, $r['errorcode'] );
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
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("Device doesn't exist");
	}else{
		if($dev->Rights!="Write"){
			$r['error']=true;
			$r['errorcode']=403;
			$r['message']=__("Unauthorized");
		}else{
			if(!$dev->DeleteDevice()){
				$r['error']=true;
				$r['errorcode']=404;
				$r['message']=__("An unknown error has occured");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
			}
		}
	}
	return $this->view->render( $response, $r, $r['errorcode'] );
});

*/
$app->run();
?>
