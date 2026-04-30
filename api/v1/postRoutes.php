<?php

	/*	Even though we're including these files in to an upstream index.php that already declares
		the namespaces, PHP treats it as a difference context, so we have to redeclare in each
		included file.
	
	*/

	use Psr\Http\Message\ServerRequestInterface as Request;
	use Psr\Http\Message\ResponseInterface as Response;

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
	$personid = intval($args["personid"]);

	if(!$person->ContactAdmin){
		$r['error']=true;
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	} else {
		$r = array();
		$p=new People();
		$p->PersonID=$personid;
		if(!$p->GetPerson()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("UserID=" . $p->PersonID . " not found in database.");
		} else {	
			// Slim Framework will simply return null for any variables that were not passed, so this is safe to call without blowing up the script
			$vars = $request->getQueryParams() ?: $request->getParsedBody();
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
	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/people
//	Method: POST
//	Params:
//		Required: peopleid, newpeopleid
//	Returns: true / false on the updates being successful 
//

$app->post('/people/{peopleid}/transferdevicesto/{newpeopleid}', function( Request $request, Response $response, $args ) use ( $person) {
	$peopleid = intval($args["peopleid"] );
	$newpeopleid = intval($args["newpeopleid"]);

	if ( ! $person->ContactAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$r['error']=false;
		$r['errorcode']=200;

		// Verify the userids are real
		foreach(array('peopleid','newpeopleid') as $var){
			$p=new People();

			$p->UserID=$$var;
			if(!$p->GetPerson() && ($var!='newpeopleid' && $$var==0)){
				$r['error']=true;
				$r['message']="$var is not valid";
				continue;
			}
		}

		// If we error above don't attempt to make changes
		if(!$r['error']){
			$dev=new Device();
			$dev->PrimaryContact=$peopleid;
			$updated = 0;
			foreach($dev->Search() as $d){
				$d->PrimaryContact=$newpeopleid;
				if(!$d->UpdateDevice()){
					// If we encounter an error stop immediately
					$r['error']=true;
					$r['message']=__("Device update has failed");
					continue;
				} else {
					$updated++;
				}
			}

			if ( $r['error'] == false ) {
				$r['message'] = $updated." ".__("devices updated");
			}
		}
	}

	return $response->withJson($r, $r['errorcode']);
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
	$deviceid = intval($args["deviceid"]);

	if ( ! $person->WriteAccess ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$pp=new PowerPorts();
		$pp->DeviceID=$deviceid;
		$vars = $request->getQueryParams() ?: $request->getParsedBody();
		foreach($vars as $prop => $val){
			$pp->$prop=$val;
		}

		$r['error']=($pp->updatePort())?false:true;
		$r['errorcode']=200;
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//  URL:    /api/v1/cabinet/:cabinetid
//  Method:	POST
//  Params:
//  	Required: CabinetID
//  	Optional: All other fields to be changed
//  Returns: record as created
//  

$app->post( '/cabinet/{cabinetid}', function( Request $request, Response $response, $args ) use ($person) {
	$cabinetid = intval($args["cabinetid"]);

	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$cab = new Cabinet();
		$cab->CabinetID=$cabinetid;
		$vars = $request->getQueryParams() ?: $request->getParsedBody();

		foreach ($vars as $prop=>$val) {
			if ( property_exists($cab, $prop)) {
				$cab->$prop = $val;
			}
		}

		$cab->MakeSafe();

		if ( ! $cab->GetCabinet() ) {
			$r['error'] = true;
			$r['errorcode'] = 400;
			$r['message'] = __("The specified CabinetID does not exist.");
			$r['input'] = $vars;
		} else {
			// Reset the given variables since we pulled in the existing record, first.   This avoids blanking out non-specified variables.
			foreach ($vars as $prop=>$val) {
				if ( property_exists($cab, $prop)) {
					$cab->$prop = $val;
				}
			}

			if ( ! $cab->UpdateCabinet() ) {
				$r['error'] = true;
				$r['errorcode'] = 400;
				$r['message'] = __("Error updating cabinet.");
				$r['input'] = $vars;
			} else {
				$r['error'] = false;
				$r['errorcode'] = 200;
				$r['message'] = __("Cabinet updated successfully.");
				$r['cabinet'][$cab->CabinetID] = $cab;
			}
		}
	}

	return $response->withJson($r, $r['errorcode']);
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
	$colorid = intval($args["colorid"]);

	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$cc=new ColorCoding();
		$vars = $request->getQueryParams() ?: $request->getParsedBody();
		foreach($vars as $prop => $val){
			if ( property_exists($cc, $prop)) {
				$cc->$prop=$val;
			}
		}

		$cc->ColorID = $colorid;

		if ( $cc->UpdateCode() ) {
			$r['error']=false;
			$r['errorcode']=200;
		} else {
			$r['error'] = true;
			$r['errorcode'] = 400;
			$r['message'] = __("Error updating color code.");
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/colorcode/:colorid/replacewith/:newcolorid
//	Method:	POST
//	Params:	
//		required: ColorID, NewColorID
//		optional: DefaultNote, Name
//	Returns:  true/false on update operation
//

$app->post( '/colorcode/{colorid}/replacewith/{newcolorid}', function( Request $request, Response $response, $args ) use ( $person ) {
	$colorid = intval($args["colorid"]);
	$newcolorid = intval($args["newcolorid"]);

	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		if ( ColorCoding::ResetCode($colorid, $newcolorid)) {
			$r['error']=false;
			$r['errorcode']=200;
		} else {
			$r['error'] = true;
			$r['errorcode'] = 401;
			$r['message'] = __("Invalid operation");
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/device/:deviceid
//	Method:	POST
//	Params:	deviceid (passed in URL)
//	Returns:  true/false on update operation
//

$app->post( '/device/{deviceid}', function( Request $request, Response $response, $args ) {
	$deviceid = intval($args["deviceid"]);

	// Rights are handled in the back end classes based upon the UserID attached to $person, so skip checks here
	$dev=new Device();
	$dev->DeviceID=$deviceid;
	
	if(!$dev->GetDevice()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No device found with DeviceID")." $deviceid";
	}else{
		if($dev->Rights!="Write"){
			$r['error']=true;
			$r['errorcode']=401;
			$r['message']=__("Access Denied");
		}else{
			$vars = $request->getQueryParams() ?: $request->getParsedBody();
			foreach($vars as $prop => $val){
				if ( property_exists( $dev, $prop )) {
					$dev->$prop=$val;
				}
			}
			if(!$dev->UpdateDevice()){
				$r['error']=true;
				$r['errorcode']=401;
				$r['message']=__("Update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
			}
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

$app->post( '/device/{deviceid}/store', function( Request $request, Response $response, $args ) {
	$deviceid = intval($args["deviceid"]);

	// Have to process all the extra bits involved with moving something to storage
	// so that's why this is a different routine than simply updating a device

	$dev=new Device();
	$dev->DeviceID=$deviceid;

	if(!$dev->GetDevice()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No device found with DeviceID")." $deviceid";
	}else{
		if($dev->Rights!="Write"){
			$r['error']=true;
			$r['errorcode']=401;
			$r['message']=__("Access Denied");
		}else{
			if(!$dev->MoveToStorage()){
				$r['error']=true;
				$r['errorcode']=401;
				$r['message']=__("Update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
			}
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/project/:projectid/device
//	Method:	POST
//	Params:	DeviceID (required, body)
//	Returns:  true/false on update operation
//

$app->post( '/project/{projectid}/device', function( Request $request, Response $response, $args ) use ( $person ) {
	$projectid = intval( $args["projectid"] );

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
			$vars = $request->getQueryParams() ?: $request->getParsedBody();
			if ( ! isset( $vars["DeviceID"] ) ) {
				$r['error'] = true;
				$r['errorcode'] = 400;
				$r['message'] = __("DeviceID is required");
			} else {
				$deviceid = intval( $vars["DeviceID"] );
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

					if ( ! ProjectMembership::addMember( $projectid, $deviceid, "Device" ) ) {
						$r['error'] = true;
						$r['errorcode'] = 400;
						$r['message'] = __("Unable to link device to project.");
					} else {
						$r['error'] = false;
						$r['errorcode'] = 200;
						$r['message'] = $exists ? __("Device already linked to project.") : __("Device linked to project.");
						$r['project'] = $project;
						$r['device'] = $dev;
					}
				}
			}
		}
	}

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/project/:projectid/cabinet
//	Method:	POST
//	Params:	CabinetID (required, body)
//	Returns:  true/false on update operation
//

$app->post( '/project/{projectid}/cabinet', function( Request $request, Response $response, $args ) use ( $person ) {
	$projectid = intval( $args["projectid"] );

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
			$vars = $request->getQueryParams() ?: $request->getParsedBody();
			if ( ! isset( $vars["CabinetID"] ) ) {
				$r['error'] = true;
				$r['errorcode'] = 400;
				$r['message'] = __("CabinetID is required");
			} else {
				$cabinetid = intval( $vars["CabinetID"] );
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

					if ( ! ProjectMembership::addMember( $projectid, $cabinetid, "Cabinet" ) ) {
						$r['error'] = true;
						$r['errorcode'] = 400;
						$r['message'] = __("Unable to link cabinet to project.");
					} else {
						$r['error'] = false;
						$r['errorcode'] = 200;
						$r['message'] = $exists ? __("Cabinet already linked to project.") : __("Cabinet linked to project.");
						$r['project'] = $project;
						$r['cabinet'] = $cab;
					}
				}
			}
		}
	}

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/project/:projectid/sendToStorage
//	Method:	POST
//	Params:	StorageRoomID (optional, body)
//	Returns:  Summary of processed devices
//

$app->post( '/project/{projectid}/sendToStorage', function( Request $request, Response $response, $args ) use ( $person ) {
	$projectid = intval( $args["projectid"] );

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
			$vars = $request->getQueryParams() ?: $request->getParsedBody();
			$storageRoomID = null;

			if ( isset( $vars["StorageRoomID"] ) ) {
				$storageRoomID = intval( $vars["StorageRoomID"] );
				if ( $storageRoomID < 0 ) {
					$r['error'] = true;
					$r['errorcode'] = 400;
					$r['message'] = __("Invalid StorageRoomID");
					return $response->withJson( $r, $r['errorcode'] );
				}
				if ( $storageRoomID > 0 ) {
					$dc = new DataCenter();
					$dc->DataCenterID = $storageRoomID;
					if ( ! $dc->GetDataCenterbyID() ) {
						$r['error'] = true;
						$r['errorcode'] = 404;
						$r['message'] = __("Storage room not found.");
						return $response->withJson( $r, $r['errorcode'] );
					}
				}
			}

			$deviceList = ProjectMembership::getProjectMembership( $projectid, true, true );
			$summary = array(
				"totalDevices"=>sizeof( $deviceList ),
				"moved"=>0,
				"skipped"=>0,
				"skippedDetails"=>array(),
				"errors"=>array()
			);

			foreach ( $deviceList as $deviceID => $deviceRow ) {
				$dev = new Device();
				$dev->DeviceID = $deviceID;

				if ( ! $dev->GetDevice() ) {
					$summary["errors"][] = array(
						"DeviceID"=>$deviceID,
						"Reason"=>"Device not found"
					);
					continue;
				}

				if ( $dev->Rights != "Write" ) {
					$summary["skipped"]++;
					$summary["skippedDetails"][] = array(
						"DeviceID"=>$deviceID,
						"Reason"=>"Access denied"
					);
					continue;
				}

				$dev->MoveToStorage();

				if ( $storageRoomID !== null ) {
					$dev->Position = $storageRoomID;
					if ( ! $dev->UpdateDevice() ) {
						$summary["errors"][] = array(
							"DeviceID"=>$deviceID,
							"Reason"=>"Unable to update storage room"
						);
						continue;
					}
				}

				$summary["moved"]++;
			}

			$r['error'] = false;
			$r['errorcode'] = 200;
			$r['project'] = $project;
			$r['summary'] = $summary;
		}
	}

	return $response->withJson( $r, $r['errorcode'] );
});

//
//	URL:	/api/v1/devicetemplate/:templateid
//	Method:	POST
//	Params:	
//		Required: templateid
//		Optional: everything else
//	Returns: true/false on update operation 
//

$app->post( '/devicetemplate/{templateid}', function( Request $request, Response $response, $args ) use ($person) {
	$templateid = intval($args["templateid"]);

	$dt=new DeviceTemplate($templateid);
	if(!$person->WriteAccess){
		$r['error']=true;
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$dt->GetTemplateByID()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("No device template found with TemplateID: ").$templateid;
		}else{
			$vars = $request->getQueryParams() ?: $request->getParsedBody();
			foreach($vars as $prop => $val){
				if ( property_exists( $dt, $prop )) {
					$dt->$prop=$val;
				}
			}
			if(!$dt->UpdateTemplate()){
				$r['error']=true;
				$r['errorcode']=400;
				$r['message']=__("Device template update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
			}
		}
	}
	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/devicetemplate/:templateid/dataport/:portnumber
//	Method:	POST
//	Params:	
//		Required: templateid, portnumber, portlabel
//		Optional: everything else
//	Returns: true/false on update operation
//

$app->post( '/devicetemplate/{templateid}/dataport/{portnumber}', function( Request $request, Response $response, $args ) use ($person) {
	$templateid = intval($args["templateid"]);
	$portnumber = intval($args["portnumber"]);

	$tp=new TemplatePorts();
	$tp->TemplateID=$templateid;
	$tp->PortNumber=$portnumber;

	if(!$person->WriteAccess){
		$r['error']=true;
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$tp->getPort()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("Template port not found with id: ")." $templateid:$portnum";
		}else{
			$vars = $request->getQueryParams() ?: $request->getParsedBody();
			foreach($vars as $prop => $val){
				if ( property_exists( $tp, $prop )) {
					$tp->$prop=$val;
				}
			}
			if(!$tp->updatePort()){
				$r['error']=true;
				$r['errorcode']=400;
				$r['message']=__("Template port update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
				$r['dataport']=$tp;
			}
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/devicetemplate/:templateid/slot/:slotnum
//	Method:	POST
//	Params:	
//		Required: templateid, slutnum
//		Optional: everything else
//	Returns: true/false on update operation
//

$app->post( '/devicetemplate/{templateid}/slot/{slotnum}', function( Request $request, Response $response, $args ) use ($person) {
	$templateid = intval($args["templateid"]);
	$slotnum = intval($args["slotnum"]);

	$s=new Slot();
	$s->TemplateID=$templateid;
	$s->PortNumber=$slotnum;

	if(!$person->WriteAccess){
		$r['error']=true;
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$s->GetSlot()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("Template slot not found with id: ")." $templateid:$slotnum";
		}else{
			$vars = $request->getQueryParams() ?: $request->getParsedBody();
			foreach($vars as $prop => $val){
				if ( property_exists( $s, $prop )) {
					$s->$prop=$val;
				}
			}
			// Just to make sure 
			$s->TemplateID=$templateid;
			$s->PortNumber=$slotnum;
			if(!$s->UpdateSlot()){
				$r['error']=true;
				$r['errorcode']=400;
				$r['message']=__("Template slot update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
				$r['dataport']=$s;
			}
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/devicestatus/:statusid
//	Method:	POST
//	Params: 
//		Required: StatusID
//		Optional: Status, ColorCode
//	Returns: true/false on update operations 
//

$app->post( '/devicestatus/{statusid}', function( Request $request, Response $response, $args ) use ($person) {
	$statusid = intval($args["statusid"]);

	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$ds=new DeviceStatus($statusid);
		$vars = $request->getQueryParams() ?: $request->getParsedBody();

		foreach( $vars as $prop=>$val ) {
			if ( property_exists( $ds, $prop )) {
				$ds->$prop = $val;
			}
		}
		$ds->StatusID=$statusid;

		if(!$ds->updateStatus()){
			$r['error']=true;
			$r['errorcode']=400;
			$r['message']=__("Error creating new status.");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['message']=__("Status updated successfully.");
			$r['devicestatus']=$ds;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:	/api/v1/manufacturer
//	Method:	POST
//	Params:	none
//	Returns: true/false on update operation
//

$app->post( '/manufacturer/{manufacturerid}', function( Request $request, Response $response, $args ) use ($person) {
	$manufacturerid = intval($args["manufacturerid"]);

	$man=new Manufacturer();
	$man->ManufacturerID=$manufacturerid;
	
	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$man->GetManufacturerByID()){
			$r['errorcode'] = 404;
			$r['message']=__("Manufacturer not found with id: ").$args['manufacturerid'];
		}else{
			$vars = $request->getQueryParams() ?: $request->getParsedBody();
			foreach($vars as $prop => $val){
				if ( property_exists($man, $prop)) {
					$man->$prop=$val;
				}
			}
			if(!$man->UpdateManufacturer()){
				$r['message']=__("Manufacturer update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
			}
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:		/api/v1/sensorreadings/:sensorid
//	Method:	POST
//	Params:
//		Required:	sensorid
//		Optional:	Temperature, Humidity, LastRead
//	Returns:	true/false on update operation

$app->post( '/sensorreadings/{sensorid}', function( Request $request, Response $response, $args ) use ($person) {
	$sensorid = intval($args["sensorid"]);

	$sensorreadings=new SensorReadings();
	$sensorreadings->SensorID=$sensorid;

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		$vars = $request->getQueryParams() ?: $request->getParsedBody();
		foreach($vars as $prop => $val){
			if ( property_exists($sensorreadings, $prop)) {
				$sensorreadings->$prop=$val;
			}
		}
		if(!$sensorreadings->UpdateSensorReadings()){
			$r['message']=__("Sensor readings update failed");	
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//	URL:	/api/v1/pdustats/:pduid
//	Method: POST
//	Params: 
//		Required: pduid
//		Optional: Wattage, LastRead
//	Returns: true/false on update operation

$app->post( '/pdustats', function( Request $request, Response $response ) use ($person) {
	$pdustats=new PDUStats();

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		$vars = $request->getQueryParams() ?: $request->getParsedBody();
		foreach($vars as $prop => $val){
			if ( property_exists($pdustats, $prop)) {
				$pdustats->$prop=$val;
			}
		}

		if(!$pdustats->UpdatePDUStats()){
			$r['message']=__("PDU stats update failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//	URL:	/api/v1/vminventory/:vmindex
//	Method: POST
//	Params:
//		Required: vmindex
//		Optional: all other
//	Returns: true/false on update operation

$app->post( '/vminventory/{vmindex}', function( Request $request, Response $response, $args ) use ($person) {
	$vmindex = intval($args["vmindex"]);

	$vm=new VM();
	$vm->VMIndex=$vmindex;

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$vm->GetVMbyIndex()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("No VM found with VMIndex ").$vmindex;
		}else{
			$vars = $request->getQueryParams() ?: $request->getParsedBody();
			foreach($vars as $prop => $val){
				if ( property_exists($vm, $prop)) {
					$vm->$prop=$val;
				}
			}

			if(!$vm->UpdateVM()){
				$r['message']=__("VM update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
			}
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//	URL:	/api/v1/powerpanel/:panelid
//	Method:	POST
//	Params:
//	Required:	panelid
//	Optional:	all other
//	Returns:	true/false on update operation

$app->post( '/powerpanel/{panelid}', function( Request $request, Response $response, $args ) use ($person) {
	$panelid = intval($args["panelid"]);
	
	$pp=new PowerPanel();
	$pp->PanelID=$panelid;

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$pp->getPanel()){
			$r['error']=true;
			$r['errorcode']=404;
			$r['message']=__("No Powerpanel found with PanelID ").$panelid;
		}else{
			$vars = $request->getQueryParams() ?: $request->getParsedBody();
			foreach($vars as $prop => $val){
				if ( property_exists($pp, $prop)) {
					$pp->$prop=$val;
				}
			}

			if(!$pp->updatePanel()){
				$r['message']=__("Powerpanel update failed");
			}else{
				$r['error']=false;
				$r['errorcode']=200;
			}
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:      /api/v1/powerconnectortypes/:ConnectorID
//	Method:   POST
//	Params:
//	Required: ConnectorID, name
//	Returns:  true/false on update operation

$app->post( '/powerconnectortypes/{id}', function( Request $request, Response $response, $args ) use ($person) {
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	$id = intval($args["id"]);

	$pc=new PowerConnectors();
	$pc->ConnectorID=$id;
	$pc->ConnectorName=$vars["name"];

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$pc->updateConnector()){
			$r['message']=__("Connector update failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:      /api/v1/powerphases/:PhaseID
//	Method:   POST
//	Params:
//	Required: PhaseID, name
//	Returns:  true/false on update operation

$app->post( '/powerphases/{id}', function( Request $request, Response $response, $args ) use ($person) {
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	$id = intval($args["id"]);

	$pp=new PowerPhases();
	$pp->PhaseID=$id;
	$pp->PhaseName=$vars["name"];

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$pp->updatePhase()){
			$r['message']=__("Phase update failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:      /api/v1/powervoltages/:VoltageID
//	Method:   POST
//	Params:
//	Required: VoltageID, name
//	Returns:  true/false on update operation

$app->post( '/powervoltages/{id}', function( Request $request, Response $response, $args ) use ($person) {
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	$id = intval($args["id"]);

	$pv=new PowerVoltages();
	$pv->VoltageID=$id;
	$pv->VoltageName=$vars["name"];

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$pv->updateVoltage()){
			$r['message']=__("Voltage update failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

//
//	URL:      /api/v1/mediaconnectortypes/:ConnectorID
//	Method:   POST
//	Params:
//	Required: ConnectorID, name
//	Returns:  true/false on update operation

$app->post( '/mediaconnectors/{id}', function( Request $request, Response $response, $args ) use ($person) {
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	$id = intval($args["id"]);

	$mc=new MediaConnectors();
	$mc->ConnectorID=$id;
	$mc->ConnectorType=$vars["name"];

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$mc->updateConnector()){
			$r['message']=__("Connector update failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});
//
//	URL:      /api/v1/mediadatarates/:RateID
//	Method:   POST
//	Params:
//	Required: RateID, name
//	Returns:  true/false on update operation

$app->post( '/mediadatarates/{id}', function( Request $request, Response $response, $args ) use ($person) {
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	$id = intval($args["id"]);

	$mdr=new MediaDataRates();
	$mdr->RateID=$id;
	$mdr->RateText=$vars["name"];

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$mdr->updateRate()){
			$r['message']=__("Connector update failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});
//
//	URL:      /api/v1/mediaprotocols/:ProtocolID
//	Method:   POST
//	Params:
//	Required: ProtocolID, name
//	Returns:  true/false on update operation

$app->post( '/mediaprotocols/{id}', function( Request $request, Response $response, $args ) use ($person) {
	$vars = $request->getQueryParams() ?: $request->getParsedBody();
	$id = intval($args["id"]);

	$mp=new MediaProtocols();
	$mp->ProtocolID=$id;
	$mp->ProtocolName=$vars["name"];

	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$mp->updateProtocol()){
			$r['message']=__("Connector update failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
		}
	}

	return $response->withJson($r, $r['errorcode']);
});

?>
