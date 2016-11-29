<?php
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
});

*/
?>
