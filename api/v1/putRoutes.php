<?php

	/*	Even though we're including these files in to an upstream index.php that already declares
		the namespaces, PHP treats it as a difference context, so we have to redeclare in each
		included file.
	*/
	use Psr\Http\Message\ServerRequestInterface as Request;
	use Psr\Http\Message\ResponseInterface as Response;


/**
  *
  *		API PUT Methods go here
  *
  *		PUT Methods are for creating new records 
  *
  **/


//
//	URL:	/api/v1/people/:userid
//	Method: PUT
//	Params: userid (required, passed as :userid in URL)
//			lastname, firstname, phone1, phone2, phone3, email, adminowndevices, 
//			readaccess, writeaccess, deleteaccess, contactadmin, rackrequest, 
//			rackadmin, siteadmin
//	Returns: record as created
//
  
$app->put('/people/{userid}', function( Request $request, Response $response, $args ) use ($person) {
	$r = array();

	if ( !$person->ContactAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	}
	
	$p = new People();
	$vars = $request->getParsedBody();
	$p->UserID = $args['userid'];

	if($p->GetPersonByUserID()){
		$r['error']=true;
		$r['errorcode']=403;
		$r['message']=__("UserID already in database.  Use the update API to modify record.");
	} else {	
		foreach ( $vars as $prop=>$val ) {
			if ( property_exists( $p, $prop ) ) {
				$p->$prop = $val;
			}
		}
		$p->Disabled = false;
		
		$p->CreatePerson();
		
		if($p->PersonID==false){
			$r['error']=true;
			$r['errorcode']=403;
			$r['message']=__("Unable to create People resource with the given parameters.");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['message']=__("People resource created successfully.");
			$r['people']=$p;
		}
	}

	$response = $response->withJson( $r, $r['errorcode'] );
	return $response;

});

//
//	URL:	/api/v1/colorcode/:name
//	Method:	PUT
//	Params: 
//		Required: Name
//		Optional: DefaultNote
//	Returns: record as created
//

$app->put( '/colorcode/{colorname}', function( Request $request, Response $response, $args ) use ($person) {
	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$cc=new ColorCoding();
		$vars = $request->getParsedBody();

		foreach( $vars as $prop=>$val ) {
			if ( property_exists( $cc, $prop )) {
				$cc->$prop = $val;
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

$app->put( '/device/{devicelabel}', function( Request $request, Response $response, $args ) {
	$dev=new Device();

	$vars = $request->getParsedBody();

	foreach( $vars as $prop=>$val ) {
		if ( property_exists( $dev, $prop )) {
			$dev->$prop = $val;
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
	foreach( $vars as $prop=>$val ) {
		if ( property_exists( $dev, $prop )) {
			$dev->$prop = $val;
		}
	}

	// This should be in the commit data but if we get a smartass saying it's in the URL
	$dev->Label=$args['devicelabel'];

	$cab=new Cabinet();
	$cab->CabinetID=$dev->Cabinet;
	if(!$cab->GetCabinet()){
		$r['error']=true;
		$r['errorcode']=404;
		$r['message']=__("Cabinet not found");
	}else{
		if($cab->Rights!="Write"){
			$r['error']=true;
			$r['errorcode']=401;
			$r['message']=__("Access Denied");
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

$app->put( '/device/{deviceid}/copyto/{newposition}', function( Request $request, Response $response, $args ) {
	$dev=new Device();
	$dev->DeviceID=$args['deviceid'];
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
			if(!$dev->CopyDevice(null,$args['newposition'],true)){
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

$app->put( '/devicetemplate/{model}', function( Request $request, Response $response, $args ) use ($person) {
	$dt=new DeviceTemplate();

	$vars = $request->getParsedBody();

	foreach( $vars as $prop=>$val ) {
		if ( property_exists( $dt, $prop )) {
			$dt->$prop = $val;
		}
	}

	// This should be in the commit data but if we get a smartass saying it's in the URL
	$dt->Model=$args['model'];

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

$app->put( '/devicetemplate/{templateid}/dataport/{portnum}', function( Request $request, Response $response, $args ) use($person) {
	$tp=new TemplatePorts();
	
	$vars = $request->getParsedBody();

	foreach( $vars as $prop=>$val ) {
		if ( property_exists($tp, $prop)) {
			$tp->$prop = $val;
		}
	}

	// This should be in the commit data but if we get a smartass saying it's in the URL
	$tp->TemplateID=$args['templateid'];
	$tp->PortNumber=$args['portnum'];

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

$app->put( '/devicetemplate/{templateid}/powerport/{portnum}', function( Request $request, Response $response, $args ) use ($person) {
	$tp=new TemplatePowerPorts();

	$vars = $request->getParsedBody();

	foreach( $vars as $prop=>$val ) {
		if ( property_exists($tp, $prop)) {
			$tp->$prop = $val;
		}
	}

	// This should be in the commit data but if we get a smartass saying it's in the URL
	$tp->TemplateID=$args['templateid'];
	$tp->PortNumber=$args['portnum'];

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

$app->put( '/devicetemplate/{templateid}/slot/{slotnum}', function( Request $request, Response $response, $args ) use($person) {
	$s=new Slot();

	$vars = $request->getParsedBody();
	foreach($vars as $prop => $val){
		if ( property_exists($s, $prop)) {
			$s->$prop=$val;
		}
	}
	// This should be in the commit data but if we get a smartass saying it's in the URL
	$s->TemplateID=$args['templateid'];
	$s->Position=$args['slotnum'];

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

$app->put( '/manufacturer/{name}', function( Request $request, Response $response, $args ) use($person) {
	$man=new Manufacturer();

	$vars = $request->getParsedBody();

	foreach( $vars as $prop=>$val ) {
		if ( property_exists( $man, $prop )) {
			$man->$prop = $val;
		}
	}

	$man->Name=$args['name'];
	
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

?>