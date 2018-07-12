<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	// This is really intended to be run from the command line, not in a browser.   However, just to be safe...

	$person = People::Current();
    
    if(!$person->SiteAdmin){
        // No soup for you.
        header('Location: '.redirect());
        exit;
    }

	$targetList = Device::getDevicesByTemplate(0);

	$sql = "select * from fac_GenericLog where Class='Device' and Property='Height' and ObjectID=:DeviceID order by Time Desc limit 1";

	$q = $dbh->prepare( $sql );
	$q->setFetchMode( PDO::FETCH_CLASS, "LogActions" );

	foreach ( $targetList as $tmpDev ) {
		if ( $tmpDev->Status != "Disposed" ) {
			$q->execute( array( ":DeviceID"=>$tmpDev->DeviceID ));

			if ( $row = $q->fetch() ) {
				// Test to see if the current value is the same as the last logged value
				// If not, set it back to the last logged value and print to screen that it was changed

				if ( $row->NewVal != $tmpDev->Height ) {
					print "Height mismatch on [" . $tmpDev->DeviceID . "] " . $tmpDev->Label . ", Height=" . $tmpDev->Height . " but log value of " . $row->NewVal . ".   Reverting to log value.\n";


					$tmpDev->Height = $row->NewVal;
					$tmpDev->UpdateDevice();
				}
			}
		}
	}
?>