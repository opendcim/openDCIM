<?php

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
//  URL:  		/api/v1/audit
//  Method: 	GET
//  Params:		DeviceID or CabinetID.   If both provided, DeviceID takes precedence.
//  Returns:	Last audit date of given parameter.
//

$app->get( '/audit', function(Request $request, Response $response) use ($person){
	$r = array();
	$error = false;

	if ( !$person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 403;
		$r['message'] = 'Forbidden';

		return $response->withJson( $r, $r['errorcode'] );
	}

	$attrList = $request->getQueryParams() ?: $request->getParsedBody();

	if ( isset( $attrList["DeviceID"] ) ) {
		$auditList = LogActions::getDeviceAudits( $attrList["DeviceID"] );
	} elseif ( isset( $attrList["CabinetID"] ) ) {
		$log = new LogActions();
		$log->ObjectID = $attrList["CabinetID"];
		$log->Class = "CabinetAudit";
		$log->Action = "CertifyAudit";
		$auditList = $log->Search();
	} else {
		$error = true;
	}

	if ( ! $error ) {
		$r['error'] = false;
		$r['errorcode'] = 200;
		$r['audit'] = $auditList;
	} else {
		$r['error'] = true;
		$r['errorcode'] = 404;
		$r["input"] = $attrList;
	}

	return $response->withJson($r, $r['errorcode']);
});

//	URL:  /api/v1/people
//	Method: GET
//	Params:  none
//	Returns:  List of all people in the database
//
$app->get('/people', function(Request $request, Response $response) use($person,$config) {	
	$person->GetUserRights();

	$sp=new People();
	$loose = false;
	$outputAttr = array();
	$attrList = $request->getQueryParams() ?: $request->getParsedBody();

	foreach($attrList as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif ( strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} elseif ( property_exists( $sp, $prop )) {
			$sp->$prop=$val;
		}
	}

	if(!$person->ContactAdmin && $config->ParameterArray["GDPRCountryIsolation"] == "disabled"){
		// Anybody that isn't an admin gets limited fields returned
		$outputAttr = array( 'PersonID', 'FirstName', 'LastName' );
	} elseif ($config->ParameterArray["GDPRCountryIsolation"] == "enabled" ) {
		$r = array();
		$r['error'] = true;
		$r['errorcode'] = 403;
		$r['message'] = "Forbidden";
		return $response->withJson($r, $r['errorcode']);
	}

	$r = array();
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['people'] = specifyAttributes( $outputAttr, $sp->Search( false, $loose ));

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:  /api/v1/department
//	Method: GET
//	Params:  none
//	Returns:  List of all departments in the database
//

$app->get('/department', function(Request $request, Response $response) use($person,$config) {
	$r = array();

	$dList = array();
	$dept=new Department();
	$loose = false;
	$outputAttr = array();
	$attrList = $request->getQueryParams() ?: $request->getParsedBody();
	foreach($attrList as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif ( strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} elseif( property_exists( $dept, $prop )) {
			$dept->$prop=$val;
		}
	}

	if ( $config->ParameterArray["GDPRCountryIsolation"] == "enabled" && !$person->ContactAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 403;
		$r['message'] = "Forbidden";
	} else {
		$r['error'] = false;
		$r['errorcode'] = 200;
		$r['department'] = specifyAttributes( $outputAttr, $dept->Search( false, $loose ));
	}

	return $response->withJson($r, $r['errorcode'] );
});

//
//	URL:	/api/v1/datacenter
//	Method: GET
//	Params:  none
//	Returns: List of all data centers in the database
//

$app->get('/datacenter', function(Request $request, Response $response) use ($person, $config) {
	// Don't have to worry about rights, other than basic connection, to get data center list
	
	$dc = new DataCenter();

	$outputAttr = array();
	$loose = false;

	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	if ( $config->ParameterArray["GDPRCountryIsolation"] == "enabled" && !$person->SiteAdmin ) {
		$vars["countryCode"] = $person->countryCode;		
	}

	foreach( $vars as $prop=>$val ) {
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
	$r['datacenter'] = specifyAttributes( $outputAttr, $dc->Search( false, $loose ));

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/datacenter/:id
//  Method: GET
//	Params:  DataCenterID (passed in URL as :id)
//	Returns: Details of specified datacenter
//

$app->get( '/datacenter/{id}', function( Request $request, Response $response, $args ) use ($config, $person) {
	$dc = new DataCenter();
	$r = array();
	$dc->DataCenterID = $args["id"];
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

	return $response->withJson($r, $r['errorcode'] );
});

//
//	URL:	/api/v1/cabinet
//	Method:	GET
//	Params: None
//	Returns: All cabinet information
//

$app->get( '/cabinet', function(Request $request, Response $response) use ($config,$person) {
	$cab = new Cabinet;
	$dc = new DataCenter();
	$loose = false;
	$outputAttr = array();

	$vars = $request->getQueryParams() ?: $request->getParsedBody();

	foreach($vars as $prop => $val){
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
	
	if ( $config->ParameterArray["GDPRCountryIsolation"] == "enabled" && !$person->SiteAdmin ) {
		$dcList = array();
		$tmpDCList = $dc->GetDCListByCountry($person->countryCode);
		foreach( $tmpDCList as $d ) {
			$dcList[] = $d->DataCenterID;
		}
	}

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
		
		// Only add in the values that match a DataCenterID from the list of good ones to use
		if ( $config->ParameterArray["GDPRCountryIsolation"] == "enabled" ) {
			if (( !$person->SiteAdmin  && in_array( $dc->DataCenterID, $dcList )) || $person->SiteAdmin ) {
				array_push( $r['cabinet'], $tmp );
			}
		} else {
			array_push( $r['cabinet'], $tmp );
		}
	}
	
	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/cabinet/:cabinetid/getpictures
//	Method:	GET
//	Params: cabinetid (passed in URL)
//	Returns:  HTML representation of a device
//

$app->get( '/cabinet/{cabinetid}/getpictures', function( Request $request, Response $response, array $args ) use ($config,$person) {
	$dc = new DataCenter();

	if ( array_key_exists( "cabinetid", $args ) ) {
		$cabinetid = $args["cabinetid"];
	} else {
		return $response->withJson(array("message"=>"Cabinet $cabinetid not found."), 404);
	}

	if ( $config->ParameterArray["GDPRCountryIsolation"] == "enabled" && !$person->SiteAdmin ) {
		$dcList = array();
		$tmpDCList = $dc->GetDCListByCountry($person->countryCode);
	} else {
		$tmpDCList = $dc->GetDCList();
	}

	foreach( $tmpDCList as $d ) {
		$dcList[] = $d->DataCenterID;
	}

	$cab=new Cabinet($cabinetid);
	
	$r['error']=true;
	$r['errorcode']=404;
	$r['message']=__("Unknown error");

	if(!$cab->GetCabinet()){
		$r['message']=__("Cabinet not found");
	}else{
		if ( in_array($cab->DataCenterID, $dcList )) {
			$r['error']=false;
			$r['errorcode']=200;
			$r['message']="";
			$r['pictures']=$cab->getPictures();			
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/cabinet/:cabinetid/sensors
//	Method:	GET
//	Params: cabinetid (passed in URL)
//	Returns: All cabinet sensor information for the specified cabinet, if any
//

$app->get( '/cabinet/{cabinetid}/sensor', function( Request $request, Response $response, array $args ) use ($config,$person) {
	$dc = new DataCenter();

	if ( $config->ParameterArray["GDPRCountryIsolation"] == "enabled" && !$person->SiteAdmin ) {
		$dcList = array();
		$tmpDCList = $dc->GetDCListByCountry($person->countryCode);
	} else {
		$tmpDCList = $dc->GetDCList();
	}

	foreach( $tmpDCList as $d ) {
		$dcList[] = $d->DataCenterID;
	}

	$cab = new Cabinet();
	$cab->CabinetID = $args['cabinetid'];
	$cab->GetCabinet();

	$r = array();
	$r['error']=true;
	$r['errorcode']=404;
	$r['message']=__("Unknown error");

	if ( in_array($cab->DataCenterID, $dcList ) ) {
		$r['error'] = false;
		$r['errorcode'] = 200;
		$r['sensors'] = array();

		if ( $m = CabinetMetrics::getMetrics($cabinetid) ) {
			$m->mUnits = $config->ParameterArray["mUnits"];
			$r['sensors'] = $m;
		}			
	}
	

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/device
//	Method:	GET
//	Params:	none
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device', function(Request $request, Response $response) {
	// Device class will already filter based on CountryIsolation if enabled, so no checks needed in here
	$dev=new Device();
	
	$r['error']=false;
	$r['errorcode']=200;
	$loose = false;
	$outputAttr = array();

	$vars = $request->getQueryParams() ?: $request->getParsedBody();

	foreach($vars as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif (strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} else {
			$dev->$prop=$val;
		}
	}
	
	$devList = $dev->Search( false, $loose, true );

	$r['device']=specifyAttributes( $outputAttr, $devList );

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/device/:deviceid
//	Method:	GET
//	Params:	deviceid (passed in URL)
//	Returns:  All devices for which the user's rights have access to view
//
/*
$app->get( '/device/:deviceid', function( $deviceid ) {
	$dev=new Device();
	$dev->DeviceID=intval($deviceid);
	
	if(!$dev->GetDevice()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No device found with DeviceID").$deviceid;
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['device']=$dev;
	}

	return $response->withJson( $r, $r['errorcode'] );

});
*/

//
//	URL:	/api/v1/device/:deviceid/getpicture
//	Method:	GET
//	Params:	
//		required: deviceid (passed in URL)
//		optional: rear (will return the rear face of the device) 
//	Returns:  HTML representation of a device
//

$app->get( '/device/{deviceid}/getpicture', function( Request $request, Response $response, $args ) {
	$dev=new Device(intval($args["deviceid"]));
	
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

	return $response->withJson($r, $r['errorcode']);
});

$app->get( '/device/{deviceid}/getsensorreadings', function( Request $request, Response $response, $args ) {
	$dev=new Device();
	$dev->DeviceID=intval($args["deviceid"]);
	
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

	return $response->withJson($r, $r['errorcode']);
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
	$dp->DeviceID = intval($args["deviceid"]);
	$r['deviceport']=$dp->getPorts();

	return $response->withJson($r, $r['errorcode']);
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
	$deviceid = intval($args["deviceid"]);
	$s=new stdClass();
	$vars = $request->getParsedBody();
	$s->portnumber=$vars['PortNumber'];
	$s->connectto=$vars['connectto'];
	$s->listports=$vars['listports'];
	$s->patchpanels=$vars['patchpanels'];
	$s->limiter=$vars['limiter'];

	$r['error']=false;
	$r['errorcode']=200;
	
	// I like nulls and wrote this function originally around them
	foreach($s as $prop => $val){
		$s->$prop=(!$val)?null:$val;
	}

	if(is_null($s->listports)){
		$r['device']=DevicePorts::getPatchCandidates($deviceid,$s->portnumber,null,$s->patchpanels,$s->limiter);
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

		$r['deviceport']=$list;
	}

	return $response->withJson($r, $r['errorcode']);
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
	$r['device']=$dev->GetDeviceList(intval($args["datacenterid"]));

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/disposition
//	Method:	GET
//	Params:	None
//	Returns;	All disposition methods within the database
//

$app->get( '/disposition', function(Request $request, Response $response) {
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['disposition'] = Disposition::getDisposition();

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/disposition/:dispositionid
//	Method:	GET
//	Params:	DispositionID
//	Returns;	All disposition methods within the database, along with all devices disposed via this method
//

$app->get( '/disposition/{dispositionid}', function( Request $request, Response $response, $args ) {
	$dispositionid = intval($args["dispositionid"]);
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['disposition'] = Disposition::getDisposition( $dispositionid );
	$r['devices'] = DispositionMembership::getDevices( $dispositionid );
	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/devicestatus
//	Method:	GET
//	Params:	None
//	Returns;	All DeviceStatus values within the database
//

$app->get( '/devicestatus', function(Request $request, Response $response) {
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['devicestatus'] = DeviceStatus::getStatusList();

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/devicestatus/:statusid
//	Method:	GET
//	Params:	StatusID
//	Returns;	All device status values within the database
//
/*
$app->get( '/devicestatus/:statusid', function($statusid) {
	$r['error'] = false;
	$r['errorcode'] = 200;
	$ds=new DeviceStatus($statusid);
	if(!$ds->getStatus()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No status found with StatusID")." $ds->StatusID";
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['devicestatus'][$ds->StatusID]=$ds;
	}
	return $response->withJson( $r, $r['errorcode'] );
});
*/

//
//	URL:	/api/v1/cabinet/byproject/:projectid
//	Method:	GET
//	Params:	projectid (passed in URL)
//	Returns:  All cabinets for which the user's rights have access to view
//

$app->get( '/cabinet/byproject/{projectid}', function( Request $request, Response $response, $args ) {
	$projectid = intval($args["projectid"]);
	$r['error']=false;
	$r['errorcode']=200;
	$r['cabinet']=ProjectMembership::getProjectCabinets( $projectid );

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/device/byproject/:projectid
//	Method:	GET
//	Params:	projectid (passed in URL)
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device/byproject/{projectid}', function( Request $request, Response $response, $args ) {
	$projectid = intval($args["projectid"]);
	$r['error']=false;
	$r['errorcode']=200;
	$r['device']=ProjectMembership::getProjectMembership( $projectid );

	return $response->withJson( $r, $r['errorcode'] );
});


//
//	URL:	/api/v1/project
//	Method:	GET
//	Params:	None
//	Returns:  All project metadata
//

$app->get( '/project', function(Request $request, Response $response) {
	$r['error']=false;
	$r['errorcode']=200;
	$r['project']=Projects::getProjectList();

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/project/bycabinet/:cabinetid
//	Method:	GET
//	Params:	CabinetID
//	Returns:  All project metadata for projects the cabinetid is a member of
//

$app->get( '/project/bycabinet/{cabinetid}', function( Request $request, Response $response, $args ) {
	$cabinetid = intval($args["cabinetid"]);
	$r['error']=false;
	$r['errorcode']=200;
	$r['project']=ProjectMembership::getCabinetMembership( $cabinetid );

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/project/bydevice/:deviceid
//	Method:	GET
//	Params:	DeviceID
//	Returns:  All project metadata for projects the deviceid is a member of
//

$app->get( '/project/bydevice/{deviceid}', function( Request $request, Response $response, $args ) {
	$deviceid = intval($args["deviceid"]);
	$r['error']=false;
	$r['errorcode']=200;
	$r['project']=ProjectMembership::getDeviceMembership( $deviceid );

	return $response->withJson( $r, $r['errorcode'] );
});


//
//	URL:	/api/v1/powerport/:deviceid
//	Method:	GET
//	Params:	deviceid (required), portnumber (optional)
//	Returns:  All power ports for a device or a specific port
//

$app->get( '/powerport/{deviceid}', function( Request $request, Response $response, $args ) {
	$deviceid=intval($args["deviceid"]);
	$pp=new PowerPorts();
	
	$r['error']=false;
	$r['errorcode']=200;
	$pp->DeviceID=$deviceid;

	$vars = $request->getQueryParams() ?: $request->getParsedBody();

	foreach($vars as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		} elseif (strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		} elseif (property_exists( $pp, $prop )) {
			$pp->$prop=$val;
		}
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

	return $response->withJson( $r, $r['errorcode'] );
});


//
//	URL:	/api/v1/colorcode
//	Method:	GET
//	Params:	none
//	Returns:  All defined color codes 
//

$app->get( '/colorcode', function(Request $request, Response $response) {
	$r['error']=false;
	$r['errorcode']=200;
	$r['colorcode']=ColorCoding::GetCodeList();;
		
	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/colorcode/:colorid
//	Method:	GET
//	Params:	colorid (passed in URL)
//	Returns:  All defined color codes matching :colorid 
//

$app->get( '/colorcode/{colorid}', function( Request $request, Response $response, $args ) {
	$colorid = intval($args["colorid"]);
	$cc=new ColorCoding();
	$cc->ColorID=$colorid;
	
	if(!$cc->GetCode()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No color code found with ColorID")." $cc->ColorID";
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['colorcode'][$cc->ColorID]=$cc;
	}

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/colorcode/:colorid/timesused
//	Method:	GET
//	Params:	colorid (passed in URL)
//	Returns:  Number of objects using :colorid 
//

$app->get( '/colorcode/{colorid}/timesused', function( Request $request, Response $response, $args ) {
	$colorid = intval($args["colorid"]);
	$r['error']=false;
	$r['errorcode']=200;
	$r['colorcode']=ColorCoding::TimesUsed($colorid);
	
	return $response->withJson( $r, $r['errorcode'] );
});


//
//	URL:	/api/v1/devicetemplate
//	Method:	GET
//	Params: none	
//	Returns: All available device templates
//

$app->get( '/devicetemplate', function(Request $request, Response $response) {
	$dt=new DeviceTemplate();
	$r['error']=false;
	$r['errorcode']=200;
	$loose = false;
	$outputAttr = array();

	$vars = $request->getQueryParams() ?: $request->getParsedBody();

	foreach($vars as $prop => $val){
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

	return $response->withJson( $r, $r['errorcode'] );
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
	$templateid = $args["templateid"];

	if($templateid=='image'){
		$r['error']=false;
		$r['errorcode']=200;
		$r['image']=DeviceTemplate::getAvailableImages();
	}else{
		$dt=new DeviceTemplate();
		$dt->TemplateID=$templateid;
		if(!$dt->GetTemplateByID()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("No template found with TemplateID: ")." ".$templateid;
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['template']=$dt;
		}
	}
	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/dataport
//	Method:	GET
//	Params: templateid
//	Returns: Data ports defined for device template with templateid
//

$app->get( '/devicetemplate/{templateid}/dataport', function( Request $request, Response $response, $args ) {
	$templateid = $args["templateid"];

	$tp=new TemplatePorts();
	$tp->TemplateID=$templateid;
	if(!$ports=$tp->getPorts()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No ports found for TemplateID: ")." ".$templateid;
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['dataport']=$ports;
	}

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/dataport/:portnumber
//	Method:	GET
//	Params: templateid. portnumber
//	Returns: Single data port defined for device template with templateid and portnum
//

$app->get( '/devicetemplate/{templateid}/dataport/{portnumber}', function( Request $request, Response $response, $args ) {
	$templateid = $args["templateid"];
	$portnumber = $args["portnumber"];

	$tp=new TemplatePorts();
	$tp->TemplateID=$templateid;
	$tp->PortNumber=$portnumber;
	if(!$tp->getPort()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("Port not found for TemplateID: ")." $templateid:$portnumber";
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['dataport']=$tp;
	}

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/powerport
//	Method:	GET
//	Params: templateid
//	Returns: Power ports defined for device template with templateid
//

$app->get( '/devicetemplate/{templateid}/powerport', function( Request $request, Response $response, $args ) {
	$templateid = intval($args["templateid"]);

	$tp=new TemplatePowerPorts();
	$tp->TemplateID=$templateid;
	if(!$ports=$tp->getPorts()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No ports found for TemplateID: ")." ".$templateid;
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['powerport']=$ports;
	}

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/slot
//	Method:	GET
//	Params: templateid
//	Returns: Slots defined for device template with templateid
//

$app->get( '/devicetemplate/{templateid}/slot', function( Request $request, Response $response, $args ) {
	$templateid = intval($args["templateid"]);

	if(!$slots=Slot::GetAll($templateid)){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No slots found for TemplateID: ")." ".$templateid;
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['slot']=$slots;
	}

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/manufacturer
//	Method:	GET
//	Params:	none
//	Returns:  All defined manufacturers 
//

$app->get( '/manufacturer', function(Request $request, Response $response) {
	$man=new Manufacturer();
	
	$r['error']=false;
	$r['errorcode']=200;
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	foreach($vars as $prop => $val){
		$man->$prop=$val;
	}
	$r['manufacturer']=$man->GetManufacturerList();

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/zone
//	Method:	GET
//	Params:	none
//	Returns:  All zones for which the user's rights have access to view
//

$app->get( '/zone', function(Request $request, Response $response) {
	$zone=new Zone();
	
	$r['error']=false;
	$r['errorcode']=200;
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	foreach($vars as $prop => $val){
		$zone->$prop=$val;
	}
	$r['zone']=$zone->Search(true);

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/zone/:zoneid
//	Method:	GET
//	Params:	none
//	Returns: Zone identified by :zoneid 
//

$app->get( '/zone/{zoneid}', function( Request $request, Response $response, $args ) {
	$zoneid = intval($args["zoneid"]);

	$zone=new Zone();
	$zone->ZoneID=$zoneid;
	
	$r['error']=false;
	$r['errorcode']=200;
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	foreach($vars as $prop => $val){
		$dev->$prop=$val;
	}
	$r['zone']=$zone->GetZone();

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/cabrow
//	Method:	GET
//	Params:	none
//	Returns:  All cabinet rows for which the user's rights have access to view
//

$app->get( '/cabrow', function(Request $request, Response $response) {
	$cabrow=new CabRow();
	
	$r['error']=false;
	$r['errorcode']=200;

	$vars = $request->getQueryParams() ?: $request->getParsedBody();

	foreach($vars as $prop => $val){
		$cabrow->$prop=$val;
	}
	$r['cabrow']=$cabrow->Search(true);

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/cabrow/:cabrowid/devices
//	Method:	GET
//	Params:	none
//	Returns:  All devices in the cabinet row 
//

$app->get( '/cabrow/{cabrowid}/devices', function( Request $request, Response $response, $args ) {
	$cabrowid = intval($args["cabrowid"]);

	$r['error']=false;
	$r['errorcode']=200;
	$r['device']=Device::SearchDevicebyCabRow($cabrowid);
	return $response->withJson( $r, $r['errorcode'] );
});


//
//	URL:		/api/v1/sensorreadings
//	Method:	GET
//	Params:	none
//	Returns:	Sensor readings for all sensors

$app->get( '/sensorreadings', function(Request $request, Response $response) {
	$sensorreadings=new SensorReadings();
	$outputAttr = array();
	$attrList = $request->getQueryParams() ?: $request->getParsedBody();
	$loose = false;

	foreach($attrList as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		}elseif(strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		}elseif (property_exists( $sensorreadings, $prop )) {
			$sensorreadings->$prop=$val;
		}
	}

	$r['error']=false;
	$r['errorcode']=200;
	$r['sensorreadings']=specifyAttributes($outputAttr, $sensorreadings->Search(false,$loose));
	return $response->withJson( $r, $r['errorcode'] );	
});

//
//	URL:	/api/v1/sensorreadings/:sensorid
//	Method:	GET
//	Params:	none
//	Returns:	Sensor readings for :sensorid

$app->get( '/sensorreadings/{sensorid}', function( Request $request, Response $response, $args ) {
	$sensorid = intval($args["sensorid"]);

	$sensorreadings=new SensorReadings();
	$sensorreadings->SensorID=$sensorid;

	if(!$sensorreadings->GetSensorReadingsByID()){
		$r['error']=true;
                $r['errorcode']=404;
                $r['message']=__("No sensor readings found with SensorID ").$sensorid;
	}else{
		$r['error']=false;
        	$r['errorcode']=200;
	        $r['sensorreadings']=$sensorreadings;
	}
	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/pdustats
//	Method:	GET
//	Params:	none
//	Returns:	PDU Stats reading for all pdus

$app->get( '/pdustats', function(Request $request, Response $response) use ($person) {
	$pdustats=new PDUStats();
	$outputAttr = array();
	$attrList = $request->getQueryParams() ?: $request->getParsedBody();
	$loose = false;

	foreach($attrList as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		}elseif(strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		}elseif (property_exists( $pdustats, $prop )) {
			$pdustats->$prop=$val;
		} 
	}

	$r['error']=false;
	$r['errorcode']=200;
	$r['pdustats']=specifyAttributes($outputAttr, $pdustats->Search(false,$loose, $person));
	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/pdustats/:pduid
//	Method:	GET
//	Params:	pduid
//	Returns:	PDU Stats reading for pduid

$app->get( '/pdustats/{pduid}', function( Request $request, Response $response, $args ) use ($person) {
	$pduid = intval($args["pduid"]);

	$pdustats=new PDUStats();
	$pdustats->PDUID=$pduid;

	if(!$pdustats->GetPDUStatsByID()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No PDU Stats found with PDUID ").$pduid;
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['pdustats']=$pdustats;
	}
	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/vminventory
//	Method:	GET
//	Params:	none
//	Returns:	All VMs info 

$app->get( '/vminventory', function(Request $request, Response $response) {
	$vm = new VM();
	$outputAttr = array();
	$attrList = $request->getQueryParams() ?: $request->getParsedBody();
	$loose = false;

	foreach($attrList as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		}elseif(strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		}elseif (property_exists( $vm, $prop )) {
			$vm->$prop=$val;
		}
	}

	$r['error']=false;
	$r['errorcode']=200;
	$r['vminventory']=specifyAttributes($outputAttr, $vm->SearchVM(false,$loose));
	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/vminventory/:vmindex
//	Method:	GET
//	Params:	vmindex
//	Returns:	VM Inventory data for vmindex

$app->get( '/vminventory/{vmindex}', function( Request $request, Response $response, $args ) {
	$vmindex = intval($args["vmindex"]);

	$vm=new VM();
	$vm->VMIndex=$vmindex;

	if(!$vm->GetVMbyIndex()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No VM information found with VMIndex ").$vmindex;
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['vminventory']=$vm;
	}
	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/powerpanel
//	Method:	GET
//	Params:	none
//	Returns:	All Powerpanel info

$app->get( '/powerpanel', function(Request $request, Response $response) {
	$pp = new PowerPanel();
	$outputAttr = array();
	$attrList = $request->getQueryParams() ?: $request->getParsedBody();
	$loose = false;

	foreach($attrList as $prop => $val){
		if ( strtoupper($prop) == "WILDCARDS" ) {
			$loose = true;
		}elseif(strtoupper($prop) == "ATTRIBUTES" ) {
			$outputAttr = explode( ",", $val );
		}elseif (property_exists( $pp, $prop )) {
			$pp->$prop=$val;
		}
	}

	$r['error']=false;
	$r['errorcode']=200;
	$r['powerpanel']=specifyAttributes($outputAttr, $pp->Search(false,$loose));
	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/powerpanel/:panelid
//	Method:	GET
//	Params:	panelid
//	Returns:	Data for panelid

$app->get( '/powerpanel/{panelid}', function( Request $request, Response $response, $args ) {
	$panelid = intval($args["panelid"]);
	
	$pp=new PowerPanel();
	$pp->PanelID=$panelid;

	if(!$pp->getPanel()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No Powerpanel information found for PanelID ").$panelid;
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['powerpanel']=$pp;
	}
	return $response->withJson( $r, $r['errorcode'] );
});

//
// URL: /api/v1/pollers/power
// Method: GET
// Params: Optionally filter by DataCenterID, ZoneID, RowID, CabinetID
// Returns: Device information for all polling power/CDU sensors that meet the filter criteria
$app->get( '/pollers/power', function(Request $request, Response $response) {
	$filters = $request->getQueryParams() ?: $request->getParsedBody();

	$dev = new Device();

	foreach( $filters as $prop=>$value ) {
		$dev->$prop = $value;
	}

	$dev->DeviceType="CDU";

	$tmpList = $dev->Search(false, false);
	$devList = array();
	$tmpDTList = array();
	$dtList = array();

	foreach( $tmpList as $tmpDev ) {
		if ( $tmpDev->PrimaryIP > "" && $tmpDev->TemplateID>0 && $tmpDev->SNMPFailureCount<3 ) {
			$devList[] = $tmpDev;
			if ( ! in_array( $tmpDev->TemplateID, $tmpDTList ) ) {
				$tmpDTList[] = $tmpDev->TemplateID;
			}
		}
	}

	foreach( $tmpDTList as $prop=>$value ) {
		$devTemplate = new DeviceTemplate();
		$devTemplate->TemplateID = $value;
		$devTemplate->GetTemplate();
		$dtList[] = $devTemplate;
	}

	$r["error"] = false;
	$r["errorcode"] = 200;
	$r["filters"] = $filters;
	$r["DeviceList"] = $devList;
	$r["TemplateList"] = $dtList;

	return $response->withJson( $r, $r['errorcode'] );

});

//
// URL: /api/v1/pollers/sensors
// Method: GET
// Params: Optionally filter by DataCenterID, ZoneID, RowID, CabinetID
// Returns: Device information for all polling sensors that meet the filter criteria
$app->get( '/pollers/sensors', function(Request $request, Response $response) {
	$filters = $request->getQueryParams() ?: $request->getParsedBody();

	$dev = new Device();

	foreach( $filters as $prop=>$value ) {
		$dev->$prop = $value;
	}

	$dev->DeviceType="Sensor";

	$tmpList = $dev->Search(false, false);
	$devList = array();
	$tmpDTList = array();
	$dtList = array();

	foreach( $tmpList as $tmpDev ) {
		if ( $tmpDev->PrimaryIP > "" && $tmpDev->TemplateID>0 && $tmpDev->SNMPFailureCount<3 ) {
			$devList[] = $tmpDev;
			if ( ! in_array( $tmpDev->TemplateID, $tmpDTList ) ) {
				$tmpDTList[] = $tmpDev->TemplateID;
			}
		}
	}

	foreach( $tmpDTList as $prop=>$value ) {
		$devTemplate = new DeviceTemplate();
		$devTemplate->TemplateID = $value;
		$devTemplate->GetTemplate();
		$dtList[] = $devTemplate;
	}

	$r["error"] = false;
	$r["errorcode"] = 200;
	$r["filters"] = $filters;
	$r["DeviceList"] = $devList;
	$r["TemplateList"] = $dtList;

	return $response->withJson( $r, $r['errorcode'] );

});

// URL: /api/v1/rackrequest
// Method: GET
// Params: None
// Returns: List of all rack requests in the database

$app->get('/rackrequest', function(Request $request, Response $response) use($person) {
    $rackRequest = new RackRequest();
    $loose = false;
    $outputAttr = array();
    $attrList = $request->getQueryParams() ?: $request->getParsedBody();

    foreach ($attrList as $prop => $val) {
        if (strtoupper($prop) == "WILDCARDS") {
            $loose = true;
        } elseif (strtoupper($prop) == "ATTRIBUTES") {
            $outputAttr = explode(",", $val);
        } elseif (property_exists($rackRequest, $prop)) {
            $rackRequest->$prop = $val;
        }
    }

    $r = array();
    $rackRequests = $rackRequest->Search(false, $loose);

    if ($rackRequests) {
        $r['error'] = false;
        $r['errorcode'] = 200;
        $r['rackrequest'] = specifyAttributes($outputAttr, $rackRequests);
    } else {
        $r['error'] = true;
        $r['errorcode'] = 404;
        $r['message'] = 'No rack requests found';
    }

    return $response->withJson($r, $r['errorcode']);
});

?>
