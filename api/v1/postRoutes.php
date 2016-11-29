<?php

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
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
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
	if ( ! $person->WriteAccess ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$pp=new PowerPorts();
		$pp->DeviceID=$args['deviceid'];
		$vars = $request->getParsedBody();
		foreach($vars as $prop => $val){
			$pp->$prop=$val;
		}

		$r['error']=($pp->updatePort())?false:true;
		$r['errorcode']=200;
	}

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
	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$cc=new ColorCoding();
		$vars = $request->getParsedBody();
		foreach($vars as $prop => $val){
			$cc->$prop=$val;
		}

		$r['error']=($cc->UpdateCode())?false:true;
		$r['errorcode']=200;
	}

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$r['error']=(ColorCoding::ResetCode($args['colorid'],$args['newcolorid']))?false:true;
		$r['errorcode']=200;
	}

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
});

//
//	URL:	/api/v1/device/:deviceid
//	Method:	POST
//	Params:	deviceid (passed in URL)
//	Returns:  true/false on update operation
//

$app->post( '/device/{deviceid}', function( Request $request, Response $response, $args ) {
	// Rights are handled in the back end classes based upon the UserID attached to $person, so skip checks here
	$dev=new Device();
	$dev->DeviceID=$args['deviceid'];
	
	if(!$dev->GetDevice()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("No device found with DeviceID").$deviceid;
	}else{
		if($dev->Rights!="Write"){
			$r['error']=true;
			$r['errorcode']=401;
			$r['message']=__("Access Denied");
		}else{
			$vars = $request->getParsedBody();
			foreach($vars as $prop => $val){
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
	$dt=new DeviceTemplate($args['templateid']);
	// This should be in the commit data but if we get a smartass saying it's in the URL
	$dt->TemplateID=$templateid;
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
			$vars = $request->getParsedBody();
			foreach($vars as $prop => $val){
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
	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
	$tp=new TemplatePorts();
	$tp->TemplateID=$args['templateid'];
	$tp->PortNumber=$args['portnumber'];

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
			$vars = $request->getParsedBody();
			foreach($vars as $prop => $val){
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
	$s=new Slot();
	$s->TemplateID=$args['templateid'];
	$s->PortNumber=$args['slotnum'];

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
			$vars = $request->getParsedBody();
			foreach($vars as $prop => $val){
				$s->$prop=$val;
			}
			// Just to make sure 
			$s->TemplateID=$args['templateid'];
			$s->PortNumber=$args['slotnum'];
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
});

//
//	URL:	/api/v1/manufacturer
//	Method:	POST
//	Params:	none
//	Returns: true/false on update operation
//

$app->post( '/manufacturer/{manufacturerid}', function( Request $request, Response $response, $args ) use ($person) {
	$man=new Manufacturer();
	$man->ManufacturerID=$args['manufacturerid'];
	
	$r['error']=true;
	$r['errorcode']=404;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$man->GetManufacturerByID()){
			$r['message']=__("Manufacturer not found with id: ").$args['manufacturerid'];
		}else{
			$vars = $request->getParsedBody();
			foreach($vars as $prop => $val){
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
});

?>