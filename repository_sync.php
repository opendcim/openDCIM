<?php

	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );
	
	global $dbh;

	$picturedir = getcwd() . "/pictures/";

	$t = new DeviceTemplate();
	$m = new Manufacturer();
	$ct = new CDUTemplate();
	$sen = new SensorTemplate();
	
	$tList = $t->GetTemplateShareList();
	
	$c = curl_init('https://repository.opendcim.org/api/template');
	
	curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, 30 );
	curl_setopt( $c, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
	curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $c, CURLOPT_COOKIEFILE, "/tmp/repocookies.txt" );
	curl_setopt( $c, CURLOPT_COOKIEJAR, "/tmp/repocookies.txt" );
	curl_setopt( $c, CURLOPT_CUSTOMREQUEST, "PUT" );
	curl_setopt( $c, CURLOPT_FOLLOWLOCATION, 1 );
	curl_setopt( $c, CURLOPT_HTTPHEADER, array( "UserID: " . $config->ParameterArray["APIUserID"], "APIKey: " . $config->ParameterArray["APIKey"], "Content-Type: application/json" ) );
	
	foreach ( $tList as $temp ) {
		if ( $temp->ManufacturerID != $m->ManufacturerID ) {
			$m->ManufacturerID = $temp->ManufacturerID;
			$m->GetManufacturerByID();
		}
		
		$temp->ManufacturerID = $m->GlobalID;
		
		$tp = new TemplatePorts();
		$tp->TemplateID = $temp->TemplateID;
		$tpList = $tp->getPorts();
		
		// Convert the base template object to an associative array for easier manipulation
		$postData["template"] = json_decode( json_encode($temp), true );
		$postData["templateports"] = array();
		foreach ( $tpList as $tport ) {
			array_push( $postData["templateports"], json_decode(json_encode($tport), true) );
		}
		
		$tpp = new TemplatePowerPorts();
		$tpp->TemplateID = $temp->TemplateID;
		$tppList = $tpp->getPorts();
		
		$postData["templatepowerports"] = array();
		foreach( $tppList as $pport ) { 
			array_push( $postData["templatepowerports"], json_decode( json_encode( $pport), true ) );
		}
		
		if ( $temp->DeviceType == "Chassis" ) {
			$sList = Slot::getSlots( $temp->TemplateID );

			$postData["slots"] = array();
			foreach( $sList as $s ) {
				array_push( $postData["slots"], json_decode(json_encode($s), true) );
			}
		}
		
		if ( $temp->DeviceType == "CDU" ) {
			$ct->TemplateID = $temp->TemplateID;
			$ct->GetTemplate();
			$postData["cdutemplate"] = json_decode( json_encode( $ct ), true );
		}
		
		if ( $temp->DeviceType == "Sensor" ) {
			$sen->TemplateID = $temp->TemplateID;
			$sen->GetTemplate();
			$postData["sensortemplate"] = json_decode( json_encode( $sen ), true );
		}
		
		curl_setopt( $c, CURLOPT_POSTFIELDS, json_encode( $postData ) );
		
		$result = curl_exec( $c );
		$jr = json_decode( $result ) ;

		$postData = array();
		
		if ( $temp->FrontPictureFile != "" ) {
			$postData["front"] = curl_file_create( "pictures/" . $temp->FrontPictureFile );
		}
		
		if ( $temp->RearPictureFile != "" ) {
			$postData["rear"] = curl_file_create( "pictures/" . $temp->RearPictureFile );
		}
		
		if ( @$jr->errorcode == 200 && sizeof( $postData ) > 0 ) {
			$p = curl_init( 'https://repository.opendcim.org/api/template/addpictures/' . $jr->template->RequestID );
			curl_setopt( $p, CURLOPT_CONNECTTIMEOUT, 30 );
			curl_setopt( $p, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt( $p, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $p, CURLOPT_POST, true );
			curl_setopt( $p, CURLOPT_COOKIEFILE, "/tmp/repocookies.txt" );
			curl_setopt( $p, CURLOPT_COOKIEJAR, "/tmp/repocookies.txt" );
			curl_setopt( $p, CURLOPT_FOLLOWLOCATION, 1 );
			curl_setopt( $p, CURLOPT_HTTPHEADER, array( "UserID: " . $config->ParameterArray["APIUserID"], "APIKey: " . $config->ParameterArray["APIKey"] ) );
			curl_setopt( $p, CURLOPT_POSTFIELDS, $postData );
			
			$result = curl_exec( $p );
			$pr = json_decode( $result );

			curl_close( $p );
		}
		
		if ( @$jr->errorcode == 200 && @$pr->errorcode == 200 ) {
			if ( sizeof( $tpList ) == 0 ) {
				$temp->clearShareFlag();
			}
		}
	}
	
	curl_setopt( $c, CURLOPT_URL, 'https://repository.opendcim.org/api/manufacturer' );
	curl_setopt( $c, CURLOPT_CUSTOMREQUEST, 'GET' );
	
	$result = curl_exec( $c );
	$jr = json_decode( $result );
	
	if ( is_array( $jr->manufacturers ) ) {
		foreach( $jr->manufacturers as $tmpman ) {
			$m->GlobalID = $tmpman->ManufacturerID;
			if ( $m->getManufacturerByGlobalID() ) { 
				$m->Name = $tmpman->Name;
				$m->UpdateManufacturer();
			} else {
				// We don't already have this one linked, so search for a candidate or add as a new one
				$m->Name = $tmpman->Name;
				if ( $m->GetManufacturerByName() ) {
					// Reset to the values from the repo (especially CaSe)
					$m->GlobalID = $tmpman->ManufacturerID;
					$m->Name = $tmpman->Name;
					$m->UpdateManufacturer();
				} else {
					$m->ManufacturerID = $tmpman->ManufacturerID;
					$m->Name = $tmpman->Name;
					$m->CreateManufacturer();
				}
			}
		}
	}

	$mList = $m->getSubscriptionList();
	foreach( $mList as $man ) {
		curl_setopt( $c, CURLOPT_URL, 'https://repository.opendcim.org/api/template/bymanufacturer/' . $man->GlobalID );
		curl_setopt( $c, CURLOPT_CUSTOMREQUEST, 'GET' );

		$result = curl_exec( $c );
		$jr = json_decode( $result );
		if ( @$jr->errorcode == 200 ) {
			if ( is_array( $jr->templates ) ) {
				foreach ( $jr->templates as $tem ) {
					$tm = new Manufacturer();
					$cs = new Slot();
					$tp = new TemplatePorts();
					$tpp = new TemplatePowerPorts();
					foreach ( $t as $prop=>$val ) {
						@$t->$prop = $tem->$prop;
					}
					
					$m->GlobalID = $t->ManufacturerID;
					$m->getManufacturerByGlobalID();
					$t->ManufacturerID = $m->ManufacturerID;

					// Snag the images
					if ( $t->FrontPictureFile != "" ) {
						$tmpname = explode( ".", basename($t->FrontPictureFile), 2 );
						$frontname = $picturedir.$tmpname[1];
						grab_image( $t->FrontPictureFile, $frontname );
						$t->FrontPictureFile = $tmpname[1];
					}

					if ( $t->RearPictureFile != "" ) {
						$tmpname = explode( ".", basename($t->RearPictureFile), 2 );
						$rearname = $picturedir.$tmpname[1];
						grab_image( $t->RearPictureFile, $rearname );
						$t->RearPictureFile = $tmpname[1];
					}
					
					// TemplateID from the repo is GlobalID for us
					$t->GlobalID = $tem->TemplateID;
					
					// Check the status of the Config globals for behavior
					// Specifically, if KeepLocal is set, then once a template has been downloaded, set the flag
					if ( $config->ParameterArray["KeepLocal"] == "enabled" ) {
						$t->KeepLocal = true;
					} else {
						$t->KeepLocal = false;
					}

					// Resolve the TemplateID so that we can make the rest of the tables match
					$st = $dbh->prepare( "select TemplateID, KeepLocal, count(*) as Total from fac_DeviceTemplate where GlobalID=:TemplateID or (ManufacturerID=:ManufacturerID and ucase(Model)=ucase(:Model))" );
					$st->execute( array( ":TemplateID"=>$t->GlobalID, ":ManufacturerID"=>$man->ManufacturerID, ":Model"=>$t->Model ) );
					$row = $st->fetch();
					if ( $row["Total"] > 0 ) {
						if ( $row["KeepLocal"] == 1 ) {
							// Anything marked as KeepLocal we ignore the repo completely
							continue;
						}
						$t->TemplateID = $row["TemplateID"];
						$t->UpdateTemplate();
						$updating = true;
					} else {
						$t->TemplateID = 0;
						$t->CreateTemplate();
						$updating = false;
					}


					if ( $t->DeviceType == "CDU" && is_object( $t->cdutemplate ) ) {
						$ct->ManufacturerID = $t->ManufacturerID;
						$ct->Model = $t->Model;
						foreach( $t->cdutemplate as $prop=>$val ) {
							$ct->$prop = $val;
						}
						$ct->TemplateID = $t->TemplateID;
						if ( ! $updating ) {
							$ct->CreateTemplate( $t->TemplateID );
						} else {
							$ct->UpdateTemplate();
						}
					} 

					if ( $t->DeviceType == "Chassis" && is_array( $t->slots ) ) {
						foreach( $t->slots as $sl ) {
							foreach( $sl as $prop=>$val ) {
								$cs->$prop = $val;
							}
							$cs->TemplateID = $t->TemplateID;
							if ( ! $updating ) {
								$cs->CreateSlot();
							} else {
								$cs->UpdateSlot();
							}
						}
					}

					if ( $t->DeviceType == "Sensor" && is_object( $t->sensortemplate ) ) {
						$sen->ManufacturerID = $t->ManufacturerID;
						$sen->Model = $t->Model;
						foreach( $t->sensortemplate as $prop=>$val ) {
							$sen->$prop = $val;
						}
						$sen->TemplateID = $t->TemplateID;
						if ( ! $updating ) {
							$sen->CreateTemplate( $t->TemplateID );
						} else {
							$sen->UpdateTemplate();
						}
					}

					if ( is_array( @$t->ports ) ) {
						if ( $updating ) {
							$tp->flushPorts( $t->TemplateID );
						}
						foreach( $t->ports as $tmpPort ) {
							foreach( $tmpPort as $prop=>$val ) {
								$tp->$prop = $val;
							}
							$tp->TemplateID = $t->TemplateID;
							$tp->createPort();
						}
					}

					if ( is_array( @$t->powerports ) ) {
						if ( $updating ) {
							$tpp->flushPorts( $t->TemplateID );
						}
						foreach( $t->powerports as $tmpPwr ) {
							foreach( $tmpPwr as $prop=>$val ) {
								$tpp->$prop = $val;
							}
							$tpp->TemplateID = $t->TemplateID;
							$tpp->createPort();
						}
					}
				}
			}
		}
	}
	
	curl_close( $c );

function grab_image($url,$saveto){
    $ch = curl_init ($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    $raw=curl_exec($ch);
    curl_close ($ch);
    if(file_exists($saveto)){
        unlink($saveto);
    }
    $fp = fopen($saveto,'x');
    fwrite($fp, $raw);
    fclose($fp);
}
?>
