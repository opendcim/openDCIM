<?php
	require_once "db.inc.php";
	require_once "facilities.inc.php";

	if ( php_sapi_name() != "cli" ) {
	echo "This script may only be run from the command line.";
	header( "Refresh: 5; url=" . redirect());    
	}
  

	$mail = new DCIMMail(true);
	$mail->addAttachment( $config->ParameterArray["PDFLogoFile"], "logo.png" );
	$mail->Subject = __("Recent Data Center Installations Report" );
	
	$style = "
<style type=\"text/css\">
@media print {
	h2 {
		page-break-before: always;
	}
}
</style>";

	$htmlMessage = sprintf( "<!doctype html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>%s</title>%s</head><body><div id=\"header\" style=\"padding: 5px 0;background: %s;\"><center><img src=\"%s\"></center></div><div class=\"page\"><p>\n", __("Data Center Inventory Reservations"), $style, $config->ParameterArray["HeaderColor"], "logo.png"  );

	$datedList = Device::GetReservationsByDate();
	
	$ownerList = array();
	foreach ( $datedList as $dev ) {
		if ( ! in_array( $dev->Owner, $ownerList ) ) {
			$ownerList[] = $dev->Owner;
		}
	}
	
	if ( sizeof( $datedList ) == 0 ) {
		$htmlMessage .= "<p>" . __("There are no reserved installations within the date range specified") . ".</p>\n";
	} else {
		$totalU = 0;
		$dept = new Department();

		foreach ( $ownerList as $owner ) {
			$deptU = 0;
			$cab = new Cabinet();
			$dc = new DataCenter();
			
			$dept->DeptID = $owner;
			$dept->GetDeptByID();
			
			$resList = Device::GetReservationsByOwner( $owner );
			
			$htmlMessage .= sprintf( "%s %s.", __("Scheduled Installations for Department"), $dept->Name );
			
			$htmlMessage .= "<table>\n<tr><th>Scheduled Date</th><th>Rack Units</th><th>Data Center</th><th>Location</th><th>Label</th></tr>\n";

			foreach ( $resList as $devRow ) {
				$cab->CabinetID = $devRow->Cabinet;
				$cab->GetCabinet();
				
				$dc->DataCenterID = $cab->DataCenterID;
				$dc->GetDataCenter();
				
				$deleteMe = false;

				if ( $config->ParameterArray["ReservationExpiration"] > 0 ) {
					if ( strtotime( $devRow->InstallDate ) < strtotime( $config->ParameterArray["ReservationExpiration"] . " days ago" ) ) {
						$InstallDate = __("Expired and Removed");
						$deleteMe = true;
					} else {
						$InstallDate = date( "d F Y", strtotime( $devRow->InstallDate ) );
					}
				} else {
					$InstallDate = date( "d F Y", strtotime( $devRow->InstallDate ) );
				}
				
				if ( $deleteMe ) {
					// Remove the links to the soon-to-be deleted device
					$htmlMessage .= sprintf( "<tr><td>%s</td><td>%d</td><td>%s</td><td><a href=\"%s/cabnavigator.php?cabinetid=%d\">%s</a></td><td>%s</td></tr>\n", $InstallDate, $devRow->Height, $dc->Name, $config->ParameterArray["InstallURL"], $cab->CabinetID, $cab->Location, $config->ParameterArray["InstallURL"], $devRow->DeviceID, $devRow->Label );
				$devRow->DeleteDevice();
				} else {
					$htmlMessage .= sprintf( "<tr><td>%s</td><td>%d</td><td>%s</td><td><a href=\"%s/cabnavigator.php?cabinetid=%d\">%s</a></td><td><a href=\"%s/devices.php?DeviceID=%d\">%s</a></td></tr>\n", $InstallDate, $devRow->Height, $dc->Name, $config->ParameterArray["InstallURL"], $cab->CabinetID, $cab->Location, $config->ParameterArray["InstallURL"], $devRow->DeviceID, $devRow->Label );
					$deptU += $devRow->Height;
				}
			}
			
			$totalU += $deptU;
			
			$htmlMessage .= sprintf( "</table><h3>%s %s: %d</h3><h2>&nbsp;</h2>\n", __("Total U Reserved for Department"), $dept->Name, $deptU );
		}
	}
	
	$htmlMessage .= sprintf( "<br><h3>%s: %d</h3>\n", __("Total U Reserved in DCIM"), $totalU );

	$mail->Body = $htmlMessage;
	try {
		$mail->send();
	} catch (Exception $e) {
		error_log( "Mailer error: {$mail->ErrorInfo}" );
	}
?>
