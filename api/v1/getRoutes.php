<?php

	/*	Even though we're including these files in to an upstream index.php that already declares
		the namespaces, PHP treats it as a difference context, so we have to redeclare in each
		included file.
	
	Framework v3 Specific

	use Psr\Http\Message\ServerRequestInterface as Request;
	use Psr\Http\Message\ResponseInterface as Response;

	*/

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
	if(!$person->ContactAdmin){
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = _("Access Denied");
	}else{
		$sp=new People();
		$loose = false;
		$outputAttr = array();
		$attrList = getParsedBody();
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
	}

	echoResponse( $r );
});

//
//	URL:  /api/v1/department
//	Method: GET
//	Params:  none
//	Returns:  List of all departments in the database
//

$app->get('/department', function() use($person) {
	$r = array();

	if ( !$person->ContactAdmin){
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = _("Access Denied");
	} else {
		$dList = array();
		$dept=new Department();
		$loose = false;
		$outputAttr = array();
		$attrList = getParsedBody();
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

	echoResponse( $r );
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

	$outputAttr = array();
	$loose = false;

	$vars = getParsedBody();

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

	echoResponse( $r );
});

//
//	URL:	/api/v1/datacenter/:id
//  Method: GET
//	Params:  DataCenterID (passed in URL as :id)
//	Returns: Details of specified datacenter
//

$app->get( '/datacenter/:id', function( $id ) {
	$dc = new DataCenter();
	$r = array();
	$dc->DataCenterID = $id;
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

	echoResponse( $r );
});

//
//	URL:	/api/v1/cabinet
//	Method:	GET
//	Params: None
//	Returns: All cabinet information
//

$app->get( '/cabinet', function() {
	$cab = new Cabinet;
	$dc = new DataCenter();
	$loose = false;
	$outputAttr = array();

	$vars = getParsedBody();

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
	
	echoResponse( $r );
});

//
//	URL:	/api/v1/cabinet/:cabinetid
//	Method:	GET
//	Params: cabinetid (passed in URL)
//	Returns: All cabinet information for given ID
//

$app->get( '/cabinet/:cabinetid', function( $cabinetid ) {
	$cab = new Cabinet;
	$dc = new DataCenter();
	if ( ! $cab->CabinetID = intval($cabinetid) ) {
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

	echoResponse( $r );
});

//
//	URL:	/api/v1/cabinet/bydc/:datacenterid
//	Method:	GET
//	Params: datacenterid (passed in URL)
//	Returns: All cabinet information within the given data center, if any
//

$app->get( '/cabinet/bydc/:datacenterid', function( $datacenterid ) {
	$cab = new Cabinet;
	$dc = new DataCenter();
	$cab->DataCenterID = intval($datacenterid);
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
	
	echoResponse( $r );
});

//
//	URL:	/api/v1/cabinet/:cabinetid/sensors
//	Method:	GET
//	Params: cabinetid (passed in URL)
//	Returns: All cabinet sensor information for the specified cabinet, if any
//

$app->get( '/cabinet/:cabinetid/sensor', function( $cabinetid ) {
	global $config;
	
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['sensors'] = array();

	if ( $m = CabinetMetrics::getMetrics($cabinetid) ) {
		$m->mUnits = $config->ParameterArray["mUnits"];
		$r['sensors'] = $m;
	}	

	echoResponse( $r );
});

//
//	URL:	/api/v1/cabinet/bydept/:deptid
//	Method:	GET
//	Params: deptid (passed in URL)
//	Returns: All cabinet information for cabinets assigned to supplied deptid
//

$app->get( '/cabinet/bydept/:deptid', function( $deptid ) {
	$cab = new Cabinet;
	$dc = new DataCenter();
	$cab->DeptID=intval($deptid);
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
	
	echoResponse( $r );
});

//
//	URL:	/api/v1/device
//	Method:	GET
//	Params:	none
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device', function() {
	$dev=new Device();
	
	$r['error']=false;
	$r['errorcode']=200;
	$loose = false;
	$outputAttr = array();

	$vars = getParsedBody();

	foreach($vars as $prop => $val){
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

	echoResponse( $r );
});

//
//	URL:	/api/v1/device/:deviceid
//	Method:	GET
//	Params:	deviceid (passed in URL)
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device/:deviceid', function( $deviceid ) {
	$dev=new Device();
	$dev->DeviceID=intval($deviceid);
	
	if(!$dev->GetDevice()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No device found with DeviceID").$deviceid;
		echoResponse( $r );
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['device']=$dev;
	}

	echoResponse( $r );

});

//
//	URL:	/api/v1/device/:deviceid/getpicture
//	Method:	GET
//	Params:	
//		required: deviceid (passed in URL)
//		optional: rear (will return the rear face of the device) 
//	Returns:  HTML representation of a device
//

$app->get( '/device/:deviceid/getpicture', function( $deviceid ) {
	$dev=new Device($deviceid);
	
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

	echoResponse( $r );
});

$app->get( '/device/:deviceid/getsensorreadings', function($deviceid) {
	$dev=new Device();
	$dev->DeviceID=intval($deviceid);
	
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

	echoResponse( $r );
});

// this is messy as all hell and i'm still thinking about how to do it better

//
//	URL:	/api/vi/deviceport/:deviceid
//
//	Method:	GET
//	Params:
//		Required:  :deviceid - DeviceID for which you wish to retrieve ports
//	Returns:	All ports for the given device

$app->get('/deviceport/:deviceid', function($deviceid) {
	$dp = new DevicePorts();
	
	$r['error'] = false;
	$r['errorcode'] = 200;
	$dp->DeviceID = $deviceid;
	$r['deviceport']=$dp->getPorts();

	echoResponse( $r );
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

$app->get( '/deviceport/:deviceid/patchcandidates', function($deviceid) {
	$s=new stdClass();
	$vars = getParsedBody();
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

	echoResponse( $r );
});

//
//	URL:	/api/v1/device/bydatacenter/:datacenterid
//	Method:	GET
//	Params:	datacenterid (passed in URL)
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device/bydatacenter/:datacenterid', function($datacenterid) {
	$dev=new Device();
	
	$r['error']=false;
	$r['errorcode']=200;
	$r['device']=$dev->GetDeviceList(intval($datacenterid));

	echoResponse( $r );
});

//
//	URL:	/api/v1/disposition
//	Method:	GET
//	Params:	None
//	Returns;	All disposition methods within the database
//

$app->get( '/disposition', function() {
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['disposition'] = Disposition::getDisposition();

	echoResponse( $r );
});

//
//	URL:	/api/v1/disposition/:dispositionid
//	Method:	GET
//	Params:	DispositionID
//	Returns;	All disposition methods within the database, along with all devices disposed via this method
//

$app->get( '/disposition/:dispositionid', function($dispositionid) {
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['disposition'] = Disposition::getDisposition( $dispositionid );
	$r['devices'] = DispositionMembership::getDevices( $dispositionid );
	echoResponse( $r );
});

//
//	URL:	/api/v1/devicestatus
//	Method:	GET
//	Params:	None
//	Returns;	All DeviceStatus values within the database
//

$app->get( '/devicestatus', function() {
	$r['error'] = false;
	$r['errorcode'] = 200;
	$r['devicestatus'] = DeviceStatus::getStatusList();

	echoResponse( $r );
});

//
//	URL:	/api/v1/devicestatus/:statusid
//	Method:	GET
//	Params:	StatusID
//	Returns;	All device status values within the database
//

$app->get( '/devicestatus/:statusid', function($statusid) {
	$r['error'] = false;
	$r['errorcode'] = 200;
	$ds=new DeviceStatus($statisid);
	if(!$ds->getStatus()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No status found with StatusID")." $ds->StatusID";
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['devicestatus'][$ds->StatusID]=$ds;
	}
	echoResponse( $r );
});


//
//	URL:	/api/v1/cabinet/byproject/:projectid
//	Method:	GET
//	Params:	projectid (passed in URL)
//	Returns:  All cabinets for which the user's rights have access to view
//

$app->get( '/cabinet/byproject/:projectid', function($projectid) {
	$r['error']=false;
	$r['errorcode']=200;
	$r['cabinet']=ProjectMembership::getProjectCabinets( $projectid );

	echoResponse( $r );
});

//
//	URL:	/api/v1/device/byproject/:projectid
//	Method:	GET
//	Params:	projectid (passed in URL)
//	Returns:  All devices for which the user's rights have access to view
//

$app->get( '/device/byproject/:projectid', function($projectid) {
	$r['error']=false;
	$r['errorcode']=200;
	$r['device']=ProjectMembership::getProjectMembership( $projectid );

	echoResponse( $r );
});


//
//	URL:	/api/v1/project
//	Method:	GET
//	Params:	None
//	Returns:  All project metadata
//

$app->get( '/project', function() {
	$r['error']=false;
	$r['errorcode']=200;
	$r['project']=Projects::getProjectList();

	echoResponse( $r );
});

//
//	URL:	/api/v1/project/bycabinet/:cabinetid
//	Method:	GET
//	Params:	CabinetID
//	Returns:  All project metadata for projects the cabinetid is a member of
//

$app->get( '/project/bycabinet/:cabinetid', function($cabinetid) {
	$r['error']=false;
	$r['errorcode']=200;
	$r['project']=ProjectMembership::getCabinetMembership( $cabinetid );

	echoResponse( $r );
});

//
//	URL:	/api/v1/project/bydevice/:deviceid
//	Method:	GET
//	Params:	DeviceID
//	Returns:  All project metadata for projects the deviceid is a member of
//

$app->get( '/project/bydevice/:deviceid', function($deviceid) {
	$r['error']=false;
	$r['errorcode']=200;
	$r['project']=ProjectMembership::getDeviceMembership( $deviceid );

	echoResponse( $r );
});


//
//	URL:	/api/v1/powerport/:deviceid
//	Method:	GET
//	Params:	deviceid (required), portnumber (optional)
//	Returns:  All power ports for a device or a specific port
//

$app->get( '/powerport/:deviceid', function($deviceid) {
	$pp=new PowerPorts();
	
	$r['error']=false;
	$r['errorcode']=200;
	$pp->DeviceID=$deviceid;

	$vars = getParsedBody();

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

	echoResponse( $r );
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
		
	echoResponse( $r );
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
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No color code found with ColorID")." $cc->ColorID";
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['colorcode'][$cc->ColorID]=$cc;
	}

	echoResponse( $r );
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
	
	echoResponse( $r );
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

	$vars = getParsedBody();

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

	echoResponse( $r );
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

$app->get( '/devicetemplate/:templateid', function($templateid) {
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
	echoResponse( $r );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/dataport
//	Method:	GET
//	Params: templateid
//	Returns: Data ports defined for device template with templateid
//

$app->get( '/devicetemplate/:templateid/dataport', function($templateid) {
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

	echoResponse( $r );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/dataport/:portnumber
//	Method:	GET
//	Params: templateid. portnumber
//	Returns: Single data port defined for device template with templateid and portnum
//

$app->get( '/devicetemplate/:templateid/dataport/:portnumber', function($templateid, $portnumber) {
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

	echoResponse( $r );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/powerport
//	Method:	GET
//	Params: templateid
//	Returns: Power ports defined for device template with templateid
//

$app->get( '/devicetemplate/:templateid/powerport', function($templateid) {
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

	echoResponse( $r );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/slot
//	Method:	GET
//	Params: templateid
//	Returns: Slots defined for device template with templateid
//

$app->get( '/devicetemplate/:templateid/slot', function($templateid) {
	if(!$slots=Slot::GetAll($templateid)){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No slots found for TemplateID: ")." ".$templateid;
	}else{
		$r['error']=false;
		$r['errorcode']=200;
		$r['slot']=$slots;
	}

	echoResponse( $r );
});

//
//	URL:	/api/v1/manufacturer
//	Method:	GET
//	Params:	none
//	Returns:  All defined manufacturers 
//

$app->get( '/manufacturer', function() {
	$man=new Manufacturer();
	
	$r['error']=false;
	$r['errorcode']=200;
	$vars = getParsedBody();
	foreach($vars as $prop => $val){
		$man->$prop=$val;
	}
	$r['manufacturer']=$man->GetManufacturerList();

	echoResponse( $r );
});

//
//	URL:	/api/v1/zone
//	Method:	GET
//	Params:	none
//	Returns:  All zones for which the user's rights have access to view
//

$app->get( '/zone', function() {
	$zone=new Zone();
	
	$r['error']=false;
	$r['errorcode']=200;
	$vars = getParsedBody();
	foreach($vars as $prop => $val){
		$zone->$prop=$val;
	}
	$r['zone']=$zone->Search(true);

	echoResponse( $r );
});

//
//	URL:	/api/v1/zone/:zoneid
//	Method:	GET
//	Params:	none
//	Returns: Zone identified by :zoneid 
//

$app->get( '/zone/:zoneid', function($zoneid) {
	$zone=new Zone();
	$zone->ZoneID=$zoneid;
	
	$r['error']=false;
	$r['errorcode']=200;
	$vars = getParsedBody();
	foreach($vars as $prop => $val){
		$dev->$prop=$val;
	}
	$r['zone']=$zone->GetZone();

	echoResponse( $r );
});

//
//	URL:	/api/v1/cabrow
//	Method:	GET
//	Params:	none
//	Returns:  All cabinet rows for which the user's rights have access to view
//

$app->get( '/cabrow', function() {
	$cabrow=new CabRow();
	
	$r['error']=false;
	$r['errorcode']=200;

	$vars = getParsedBody();

	foreach($vars as $prop => $val){
		$cabrow->$prop=$val;
	}
	$r['cabrow']=$cabrow->Search(true);

	echoResponse( $r );
});



?>
