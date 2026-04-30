<?php

	/*	Even though we're including these files in to an upstream index.php that already declares
		the namespaces, PHP treats it as a difference context, so we have to redeclare in each
		included file.
	*/

	use Psr\Http\Message\ServerRequestInterface as Request;
	use Psr\Http\Message\ResponseInterface as Response;

/**
  *
  *		API DELETE Methods go here
  *
  *		DELETE Methods are for removing records 
  *
  **/

//
//     URL:    /api/v1/cabinet/:cabinetid
//     Method: DELETE
//     Params: cabinetid (passed in URL)
//     Returns:  true/false on update operation
//

$app->delete( '/cabinet/{cabinetid}', function( Request $request, Response $response, $args ) use($person) {
	$cabinetid = intval($args["cabinetid"]);

	if ( ! $person->SiteAdmin ) {
			$r['error'] = true;
			$r['errorcode'] = 401;
			$r['message'] = __("Access Denied");
	} else {
			$cab=new Cabinet();
			$cab->CabinetID=$cabinetid;

			if(!$cab->DeleteCabinet()){
					$r['error']=true;
					$r['errorcode']=404;
					$r['message']=__("Failed to delete cabinet with CabinetID")." $cab->CabinetID";
			}else{
					$r['error']=false;
					$r['errorcode']=200;
			}
	}

	return $response->withJson($r, $r['errorcode']);
});


//
//	URL:	/api/v1/powerport/:deviceid
//	Method:	DELETE
//	Params:	
//		required: DeviceID, PortNumber
//		optional: Label, ConnectedDeviceID, ConnectedPort, Notes
//	Returns:  true/false on update operation
//

$app->delete( '/powerport/{deviceid}', function( Request $request, Response $response, $args ) use ($app) {
	$deviceid = intval($args["deviceid"]);

	$pp=new PowerPorts();
	$pp->DeviceID=$deviceid;
	$vars = $request->getQueryParams() ?: $request->getParsedBody();

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
	$portlist=$pp->getPorts();
	$lastport=end($portlist);
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

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/colorcode/:colorid
//	Method:	DELETE
//	Params:	colorid (passed in URL)
//	Returns:  true/false on update operation
//

$app->delete( '/colorcode/{colorid}', function( Request $request, Response $response, $args ) use($person) {
	$colorid = intval($args["colorid"]);

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

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/device/:deviceid
//	Method:	DELETE
//	Params:	deviceid (passed in URL)
//	Returns:  true/false on update operation
//

$app->delete( '/device/{deviceid}', function( Request $request, Response $response, $args ) {
	$deviceid = intval($args["deviceid"]);

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
	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/project/:projectid/device/:deviceid
//	Method:	DELETE
//	Params:	ProjectID, DeviceID
//	Returns:  true/false on update operation
//

$app->delete( '/project/{projectid}/device/{deviceid}', function( Request $request, Response $response, $args ) use ( $person ) {
	$projectid = intval( $args["projectid"] );
	$deviceid = intval( $args["deviceid"] );

	if ( ! $person->WriteAccess ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$project = Projects::getProject( $projectid );
		if ( ! $project ) {
			$r['error'] = true;
			$r['errorcode'] = 404;
			$r['message'] = __("Project not found");
		} else {
			$dev = new Device();
			$dev->DeviceID = $deviceid;
			if ( ! $dev->GetDevice() ) {
				$r['error'] = true;
				$r['errorcode'] = 404;
				$r['message'] = __("Device not found");
			} elseif ( $dev->Rights != "Write" ) {
				$r['error'] = true;
				$r['errorcode'] = 401;
				$r['message'] = __("Access Denied");
			} else {
				global $dbh;
				$st = $dbh->prepare( "select 1 from fac_ProjectMembership where ProjectID=:ProjectID and MemberType='Device' and MemberID=:MemberID" );
				$st->execute( array( ":ProjectID"=>$projectid, ":MemberID"=>$deviceid ) );
				$exists = $st->fetchColumn();

				if ( ! ProjectMembership::removeMember( $deviceid, "Device", $projectid ) ) {
					$r['error'] = true;
					$r['errorcode'] = 400;
					$r['message'] = __("Unable to unlink device from project.");
				} else {
					$r['error'] = false;
					$r['errorcode'] = 200;
					$r['message'] = $exists ? __("Device unlinked from project.") : __("Device link not found.");
				}
			}
		}
	}

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/project/:projectid/cabinet/:cabinetid
//	Method:	DELETE
//	Params:	ProjectID, CabinetID
//	Returns:  true/false on update operation
//

$app->delete( '/project/{projectid}/cabinet/{cabinetid}', function( Request $request, Response $response, $args ) use ( $person ) {
	$projectid = intval( $args["projectid"] );
	$cabinetid = intval( $args["cabinetid"] );

	if ( ! $person->WriteAccess ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$project = Projects::getProject( $projectid );
		if ( ! $project ) {
			$r['error'] = true;
			$r['errorcode'] = 404;
			$r['message'] = __("Project not found");
		} else {
			$cab = new Cabinet();
			$cab->CabinetID = $cabinetid;
			if ( ! $cab->GetCabinet() ) {
				$r['error'] = true;
				$r['errorcode'] = 404;
				$r['message'] = __("Cabinet not found");
			} elseif ( $cab->Rights != "Write" ) {
				$r['error'] = true;
				$r['errorcode'] = 401;
				$r['message'] = __("Access Denied");
			} else {
				global $dbh;
				$st = $dbh->prepare( "select 1 from fac_ProjectMembership where ProjectID=:ProjectID and MemberType='Cabinet' and MemberID=:MemberID" );
				$st->execute( array( ":ProjectID"=>$projectid, ":MemberID"=>$cabinetid ) );
				$exists = $st->fetchColumn();

				if ( ! ProjectMembership::removeMember( $cabinetid, "Cabinet", $projectid ) ) {
					$r['error'] = true;
					$r['errorcode'] = 400;
					$r['message'] = __("Unable to unlink cabinet from project.");
				} else {
					$r['error'] = false;
					$r['errorcode'] = 200;
					$r['message'] = $exists ? __("Cabinet unlinked from project.") : __("Cabinet link not found.");
				}
			}
		}
	}

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/project/:projectid
//	Method:	DELETE
//	Params:	ProjectID
//	Returns:  true/false on update operation
//

$app->delete( '/project/{projectid}', function( Request $request, Response $response, $args ) use ( $person ) {
	$projectid = intval( $args["projectid"] );

	if ( ! $person->ContactAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$project = Projects::getProject( $projectid );
		if ( ! $project ) {
			$r['error'] = true;
			$r['errorcode'] = 404;
			$r['message'] = __("Project not found");
		} else {
			global $dbh;
			try {
				$st = $dbh->prepare( "select MemberType, count(*) as Total from fac_ProjectMembership where ProjectID=:ProjectID group by MemberType" );
				$st->execute( array( ":ProjectID"=>$projectid ) );
				$counts = array( "Device"=>0, "Cabinet"=>0 );
				while ( $row = $st->fetch( PDO::FETCH_ASSOC ) ) {
					$counts[$row["MemberType"]] = intval( $row["Total"] );
				}

				$dbh->beginTransaction();

				$st = $dbh->prepare( "delete from fac_ProjectMembership where ProjectID=:ProjectID" );
				$st->execute( array( ":ProjectID"=>$projectid ) );

				$st = $dbh->prepare( "delete from fac_Projects where ProjectID=:ProjectID" );
				$st->execute( array( ":ProjectID"=>$projectid ) );

				if ( $st->rowCount() < 1 ) {
					throw new Exception("Delete failed");
				}

				$dbh->commit();

				(class_exists('LogActions'))?LogActions::LogThis($project):'';

				$r['error'] = false;
				$r['errorcode'] = 200;
				$r['removedDeviceLinks'] = $counts["Device"];
				$r['removedCabinetLinks'] = $counts["Cabinet"];
			} catch ( Exception $e ) {
				if ( $dbh->inTransaction() ) {
					$dbh->rollBack();
				}
				$r['error'] = true;
				$r['errorcode'] = 400;
				$r['message'] = __("Project deletion failed.");
			}
		}
	}

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/devicestatus/:statusid
//	Method:	DELETE
//	Params: 
//		Required: StatusID
//	Returns: true/false on update operations 
//

$app->delete( '/devicestatus/{statusid}', function( Request $request, Response $response, $args ) use ($person) {
	$statusid = intval($args["statusid"]);

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

	return $response->withJson($r, $r['errorcode']);
});

//	URL:	/api/v1/sensorreadings/:sensorid
//	Method:	DELETE
//	Params:
//		Required: sensorid
//	Returns: true/false on delete operation
$app->delete( '/sensorreadings/{sensorid}', function( Request $request, Response $response, $args ) use ($person) {
	$sensorid = intval($args["sensorid"]);

	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$sr=new SensorReadings();
		$sr->SensorID=$sensorid;
		
		if(!$sr->DeleteSensorReadings()){
			$r['error']=true;
			$r['errorcode']=400;
			$r['message']=__("Error removing sensor readings for SensorID ").$sensorid;
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//	URL:	/api/v1/pdustats/:pduid
//	Method:	DELETE
//	Params:
//		Required:	pduid
//	Returns: true/false on delete operation
$app->delete( '/pdustats/{pduid}', function( Request $request, Response $response, $args ) use ($person) {
	$pduid = intval($args["pduid"]);

	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$ps=new PDUStats();
		$ps->PDUID=$pduid;
		if(!$ps->DeletePDUStats()){
			$r['error']=true;
			$r['errorcode']=400;
			$r['message']=__("Error removing pdu stats for PDUID ").$pduid;
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//	URL:	/api/v1/vminventory/:vmindex
//	Method:	DELETE
//	Params:
//		Required:	vmindex
//	Returns: true/false on delete operation
$app->delete( '/vminventory/{vmindex}', function( Request $request, Response $response, $args ) use ($person) {
	$vmindex = intval($args["vmindex"]);

	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$vm=new VM();
		$vm->VMIndex=$vmindex;
		if(!$vm->DeleteVM()){
			$r['error']=true;
			$r['errorcode']=400;
			$r['message']=__("Error removing VM from VMInventory.");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//	URL:	/api/v1/vminventory/expirevms/:days
//	Method:	DELETE
//	Params:
//		Required:	days
//	Returns: true
$app->delete( '/vminventory/expirevms/{days}', function( Request $request, Response $response, $args ) use ($person) {
	$days = intval($args["days"]);

	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		if (!intval($days)) {
			$r['error'] = true;
			$r['errorcode'] = 401;
			$r['message'] = __("Invalid parameter (days)");
		} else {
			$vm=new VM();
			$vm->ExpireVMs($days);
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//	URL:	/api/v1/powerpanel/:panelid
//	Method:	DELETE
//	Params:
//		Required:	panelid
//	Returns: true/false on delete operation
$app->delete( '/powerpanel/{panelid}', function( Request $request, Response $response, $args ) use ($person) {
	$panelid = intval($args["panelid"]);
	
	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$pp=new PowerPanel();
		$pp->PanelID=$panelid;
		if(!$pp->deletePanel()){
			$r['error']=true;
			$r['errorcode']=400;
			$r['message']=__("Error removing Powerpanel with PanelID ").$panelid;
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});


//
//	URL:      /api/v1/powerconnectortypes/:ConnectorID
//	Method:   DELETE
//	Params:
//		Required: ConnectorID
//		Optional: NewID
//	Returns:  true/false on delete operation

$app->delete( '/powerconnectortypes/{id}', function( Request $request, Response $response, $args ) use ($person) {
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	$id = intval($args["id"]);

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!PowerConnectors::deleteConnector($id,(isset($vars['NewID']))?$vars['NewID']:0)){
			$r['message']=__("Connector deletion failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:      /api/v1/powerphases/:PhaseID
//	Method:   DELETE
//	Params:
//		Required: PhaseID
//		Optional: NewID
//	Returns:  true/false on delete operation

$app->delete( '/powerphases/{id}', function( Request $request, Response $response, $args ) use ($person) {
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	$id = intval($args["id"]);

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!PowerPhases::deletePhase($id,(isset($vars['NewID']))?$vars['NewID']:0)){
			$r['message']=__("Phase deletion failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:      /api/v1/powervoltages/:VoltageID
//	Method:   DELETE
//	Params:
//		Required: VoltageID
//		Optional: NewID
//	Returns:  true/false on delete operation

$app->delete( '/powervoltages/{id}', function( Request $request, Response $response, $args ) use ($person) {
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	$id = intval($args["id"]);

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!PowerVoltages::deleteVoltage($id,(isset($vars['NewID']))?$vars['NewID']:0)){
			$r['message']=__("Voltage deletion failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:      /api/v1/mediaconnectors/:ConnectorID
//	Method:   DELETE
//	Params:
//		Required: ConnectorID
//		Optional: NewID
//	Returns:  true/false on delete operation

$app->delete( '/mediaconnectors/{id}', function( Request $request, Response $response, $args ) use ($person) {
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	$id = intval($args["id"]);

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!MediaConnectors::deleteConnector($id,(isset($vars['NewID']))?$vars['NewID']:0)){
			$r['message']=__("Connector deletion failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:      /api/v1/mediadatarates/:RateID
//	Method:   DELETE
//	Params:
//		Required: RateID
//		Optional: NewID
//	Returns:  true/false on delete operation

$app->delete( '/mediadatarates/{id}', function( Request $request, Response $response, $args ) use ($person) {
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	$id = intval($args["id"]);

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!MediaDataRates::deleteRate($id,(isset($vars['NewID']))?$vars['NewID']:0)){
			$r['message']=__("Rate deletion failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:      /api/v1/mediaprotocols/:ProtocolID
//	Method:   DELETE
//	Params:
//		Required: ProtocolID
//		Optional: NewID
//	Returns:  true/false on delete operation

$app->delete( '/mediaprotocols/{id}', function( Request $request, Response $response, $args ) use ($person) {
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	$id = intval($args["id"]);

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!MediaProtocols::deleteProtocol($id,(isset($vars['NewID']))?$vars['NewID']:0)){
			$r['message']=__("Protocol deletion failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});
?>
