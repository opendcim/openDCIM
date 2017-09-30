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

// $app->delete( '/powerport/{deviceid}', function( Request $request, Response $response, $args ) use ($person) {
$app->delete( '/powerport/:deviceid', function( $deviceid ) use ($app) {
	$pp=new PowerPorts();
	$pp->DeviceID=$deviceid;
	$vars = getParsedBody();

	foreach($vars as $prop => $val){
		if ( property_exists( $pp, $prop )) {
			$pp->$prop=$val;
		}
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

	echoResponse( $r );
});

//
//	URL:	/api/v1/colorcode/:colorid
//	Method:	DELETE
//	Params:	colorid (passed in URL)
//	Returns:  true/false on update operation
//

$app->delete( '/colorcode/:colorid', function( $colorid ) use($person) {
	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
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
	}

	echoResponse( $r );
});

//
//	URL:	/api/v1/device/:deviceid
//	Method:	DELETE
//	Params:	deviceid (passed in URL)
//	Returns:  true/false on update operation
//

$app->delete( '/device/:deviceid', function( $deviceid ) {
	$dev=new Device();
	$dev->DeviceID=$args['deviceid'];
	
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
	echoResponse( $r );
});

//
//	URL:	/api/v1/devicestatus/:statusid
//	Method:	DELETE
//	Params: 
//		Required: StatusID
//	Returns: true/false on update operations 
//

$app->delete( '/devicestatus/:statusid', function($statusid) use ($person) {
	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$ds=new DeviceStatus($statusid);

		if(!$ds->removeStatus()){
			$r['error']=true;
			$r['errorcode']=400;
			$r['message']=__("Error removing status, check to make sure it isn't in use on any devices.");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['message']=__("Status removed successfully.");
			$r['devicestatus'][$ds->StatusID]=$ds;
		}
	}

	echoResponse( $r );
});

?>
