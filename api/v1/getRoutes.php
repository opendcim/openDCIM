<?php

	/*	Even though we're including these files in to an upstream index.php that already declares
		the namespaces, PHP treats it as a difference context, so we have to redeclare in each
		included file.
	*/
	use Psr\Http\Message\ServerRequestInterface as Request;
	use Psr\Http\Message\ResponseInterface as Response;

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

$app->get('/department', function(Request $request, Response $response) use($person) {
	$r = array();

	if ( !$person->ContactAdmin){
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = "Insufficient privilege level.";
	} else {
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

		$r['error'] = false;
		$r['errorcode'] = 200;
		$r['department'] = specifyAttributes( $outputAttr, $dept->Search( false, $loose ));
	}

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
	
	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
	
	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
	
	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
});

//
//	URL:	/api/v1/device
//	Method:	GET
//	Params:	none
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device', function( Request $request, Response $response, $args ) {
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
		$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;

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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
});


//
//	URL:	/api/v1/colorcode
//	Method:	GET
//	Params:	none
//	Returns:  All defined color codes 
//

$app->get( '/colorcode', function( Request $request, Response $response ) {
	$r['error']=false;
	$r['errorcode']=200;
	$r['colorcode']=ColorCoding::GetCodeList();;
		
	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
	
	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
});



?>