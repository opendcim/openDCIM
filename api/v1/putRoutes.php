<?php
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
			$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
		}else{
			$r['error']=false;
			$responde['errorcode']=200;
			$r['message']=__("People resource created successfully.");
			$r['people']=$p;

			$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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
	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
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

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;
});
*/
?>