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
  
$app->put('/people/:userid', function($userid) use ($person) {
	$r = array();

	if ( !$person->ContactAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	}
	
	$p = new People();
	$vars = getParsedBody();
	$p->UserID = $userid;

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
			$r['errorcode']=400;
			$r['message']=__("Unable to create People resource with the given parameters.");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['message']=__("People resource created successfully.");
			$r['people']=$p;
		}
	}

	echoResponse( $r );

});

//
//	URL:	/api/v1/colorcode/:name
//	Method:	PUT
//	Params: 
//		Required: Name
//		Optional: DefaultNote
//	Returns: record as created
//

$app->put( '/colorcode/:colorname', function($colorname) use ($person) {
	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$cc=new ColorCoding();
		$vars = getParsedBody();

		foreach( $vars as $prop=>$val ) {
			if ( property_exists( $cc, $prop )) {
				$cc->$prop = $val;
			}
		}

		if(!$cc->CreateCode()){
			$r['error']=true;
			$r['errorcode']=400;
			$r['message']=__("Error creating new color.");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['message']=__("New color created successfully.");
			$r['colorcode'][$cc->ColorID]=$cc;
		}
	}

	echoResponse( $r );
});

//
//	URL:	/api/v1/device/:devicelabel
//	Method:	PUT
//	Params:	deviceid (passed in URL)
//		Required: Label, cabinetid
//		Optional: everything else
//	Returns: record as created 
//

$app->put( '/device/:devicelabel', function($devicelabel) {
	$dev=new Device();

	$vars = getParsedBody();

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
			$r['errorcode']=401;
			$r['message']=__("Access Denied");
		}else{
			if(!$dev->CreateDevice()){
				$r['error']=true;
				$r['errorcode']=400;
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

	echoResponse( $r );
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

$app->put( '/device/:deviceid/copyto/:newposition', function($deviceid, $newposition) {
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
			$r['errorcode']=401;
			$r['message']=__("Access Denied");
		}else{
			if(!$dev->CopyDevice(null,$newposition,true)){
				$r['error']=true;
				$r['errorcode']=400;
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

	echoResponse( $r );
});

//
//	URL:	/api/v1/devicestatus/:status
//	Method:	PUT
//	Params: 
//		Required: Status
//		Optional: ColorCode
//	Returns: record as created
//

$app->put( '/devicestatus/:status', function($status) use ($person) {
	if ( ! $person->SiteAdmin ) {
		$r['error'] = true;
		$r['errorcode'] = 401;
		$r['message'] = __("Access Denied");
	} else {
		$ds=new DeviceStatus();
		$vars = getParsedBody();

		foreach( $vars as $prop=>$val ) {
			if ( property_exists( $ds, $prop )) {
				$ds->$prop = $val;
			}
		}
		$ds->Status=$status;

		if(!$ds->createStatus()){
			$r['error']=true;
			$r['errorcode']=400;
			$r['message']=__("Error creating new status.");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['message']=__("New status created successfully.");
			$r['devicestatus'][$ds->StatusID]=$ds;
		}
	}

	echoResponse( $r );
});

//
//	URL:	/api/v1/devicetemplate/:model
//	Method:	PUT
//	Params:	
//		Required: Label
//		Optional: everything else
//	Returns: record as created 
//

$app->put( '/devicetemplate/:model', function($model) use ($person) {
	$dt=new DeviceTemplate();

	$vars = getParsedBody();

	foreach( $vars as $prop=>$val ) {
		if ( property_exists( $dt, $prop )) {
			$dt->$prop = $val;
		}
	}

	// This should be in the commit data but if we get a smartass saying it's in the URL
	$dt->Model=$model;

	if(!$person->WriteAccess){
		$r['error']=true;
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$dt->CreateTemplate()){
			$r['error']=true;
			$r['errorcode']=400;
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

	echoResponse( $r );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/dataport/:portnum
//	Method:	PUT
//	Params:	
//		Required: templateid, portnum, portlabel
//		Optional: everything else
//	Returns: record as created 
//

$app->put( '/devicetemplate/:templateid/dataport/:portnum', function($templateid, $portnum) use($person) {
	$tp=new TemplatePorts();
	
	$vars = getParsedBody();

	foreach( $vars as $prop=>$val ) {
		if ( property_exists($tp, $prop)) {
			$tp->$prop = $val;
		}
	}

	// This should be in the commit data but if we get a smartass saying it's in the URL
	$tp->TemplateID=$templateid;
	$tp->PortNumber=$portnum;

	if(!$person->WriteAccess){
		$r['error']=true;
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$tp->CreatePort()){
			$r['error']=true;
			$r['errorcode']=400;
			$r['message']=__("Device template port creation failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['dataport']=$tp;
		}
	}

	echoResponse( $r );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/powerport/:portnum
//	Method:	PUT
//	Params:	
//		Required: templateid, portnum, portlabel
//		Optional: everything else
//	Returns: record as created 
//

$app->put( '/devicetemplate/:templateid/powerport/:portnum', function($templateid, $portnum) use ($person) {
	$tp=new TemplatePowerPorts();

	$vars = getParsedBody();

	foreach( $vars as $prop=>$val ) {
		if ( property_exists($tp, $prop)) {
			$tp->$prop = $val;
		}
	}

	// This should be in the commit data but if we get a smartass saying it's in the URL
	$tp->TemplateID=$templateid;
	$tp->PortNumber=$portnum;

	if(!$person->WriteAccess){
		$r['error']=true;
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$tp->CreatePort()){
			$r['error']=true;
			$r['errorcode']=400;
			$r['message']=__("Device template port creation failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['powerport']=$tp;
		}
	}

	echoResponse( $r );
});

//
//	URL:	/api/v1/devicetemplate/:templateid/slot/:slotnum
//	Method:	PUT
//	Params:	
//		Required: templateid, slotnum
//		Optional: everything else
//	Returns: record as created 
//

$app->put( '/devicetemplate/:templateid/slot/:slotnum', function($templateid, $slotnum) use($person) {
	$s=new Slot();

	$vars = getParsedBody();
	foreach($vars as $prop => $val){
		if ( property_exists($s, $prop)) {
			$s->$prop=$val;
		}
	}
	// This should be in the commit data but if we get a smartass saying it's in the URL
	$s->TemplateID=$templateid;
	$s->Position=$slotnum;

	if(!$person->WriteAccess){
		$r['error']=true;
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$s->CreateSlot()){
			$r['error']=true;
			$r['errorcode']=400;
			$r['message']=__("Device template slot creation failed");
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['powerport']=$s;
		}
	}

	echoResponse( $r );
});

//
//	URL:	/api/v1/manufacturer/:name
//	Method:	PUT
//	Params:	none
//	Returns: Record as created 
//

$app->put( '/manufacturer/:name', function($name) use($person) {
	$man=new Manufacturer();

	$vars = getParsedBody();

	foreach( $vars as $prop=>$val ) {
		if ( property_exists( $man, $prop )) {
			$man->$prop = $val;
		}
	}

	$man->Name=$name;
	
	$r['error']=true;
	$r['errorcode']=400;

	if(!$person->SiteAdmin){
		$r['errorcode']=401;
		$r['message']=__("Access Denied");
	}else{
		if(!$man->CreateManufacturer()){
			$r['message']=__("Manufacturer not created: ")." $manufacturerid";
		}else{
			$r['error']=false;
			$r['errorcode']=200;
			$r['manufacturer']=$man;
		}
	}

	echoResponse( $r );
});

?>
