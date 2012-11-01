<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>Data Center PDU Detail</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui-1.8.18.custom.css" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  </head>
<body>
<div id="header"></div>
<div class="page pdu">
<div class="main">
<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );
	
	if ( ! isset( $_REQUEST["PDUID"] ) ) {
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}
	
	$pdu = new PowerDistribution();
	$pdu->PDUID = $_REQUEST["PDUID"];
	$pdu->GetPDU( $facDB );
	
	$template = new CDUTemplate();
	$template->TemplateID = $pdu->TemplateID;
	$template->GetTemplate( $facDB );
	
	printf( "<p>%s %s.<br>\n", _("Testing SNMP communication to CDU"), $pdu->Label );
	printf( "%s %s.<br>\n", _("Connecting to IP address"), $pdu->IPAddress );
	printf( "%s %s.</p>\n", _("Using SNMP Community string"), $pdu->SNMPCommunity );
	
	printf( "<div id=\"infopanel\"><fieldset><legend>%s</legend>\n", _("Results") );
	
	$command = "/usr/bin/snmpget";
	
	$upTime = $pdu->GetSmartCDUUptime( $facDB );
	if ( $upTime != "" ) {
		printf( "<p>%s: %s</p>\n", _("SNMP Uptime"), $upTime );
	} else {
		printf( "<p>%s</p>\n", _("SNMP Uptime did not return a valid value.") );
	}
	
	$pollCommand = sprintf( "%s -v 2c -c %s %s %s | /bin/cut -d: -f4", $command, $pdu->SNMPCommunity, $pdu->IPAddress, $template->VersionOID );
	exec( $pollCommand, $verOutput );
	
	if ( count( $verOutput ) > 0 ) {
		printf( "<p>%s %s.  %s</p>\n", _("VersionOID returned a value of"), $verOutput[0], _("Please check to see if it makes sense.") );
	} else {
		printf( "<p>%s</p>\n", _("The OID for Firmware Version did not return a value.  Please check your MIB table.") );
	}
	
	$OIDString = $template->OID1 . " " . $template->OID2 . " " . $template->OID3;
	
	$pollCommand = sprintf( "%s -v 2c -c %s %s %s | /bin/cut -d: -f4", $command, $pdu->SNMPCommunity, $pdu->IPAddress, $OIDString );
	
	exec( $pollCommand, $statsOutput );
	
	if ( count( $statsOutput ) > 0 ) {
		if ( $statsOutput[0] != "" ) {
			printf( "<p>%s %s.  %s</p>\n", _("OID1 returned a value of"), $statsOutput[0], _("Please check to see if it makes sense.") );
		} else {
			printf( "<p>%s</p>\n", _("OID1 did not return any data.  Please check your MIB table.") );
		}
		
		if ( ( strlen($template->OID2) > 0 ) && ( strlen($statsOutput[1]) > 0 ) ) {
			printf( "<p>%s %s.  %s</p>\n", _("OID2 returned a value of"), $statsOutput[1], _("Please check to see if it makes sense.") );
		} elseif ( strlen($template->OID2) > 0 ) {
			printf( "<p>%s</p>\n", _("OID2 did not return any data.  Please check your MIB table.") );
		}

		if ( ( strlen($template->OID3) > 0 ) && ( strlen($statsOutput[2]) > 0 ) ) {
			printf( "<p>%s %s.  %s</p>\n", _("OID3 returned a value of"), $statsOutput[2], _("Please check to see if it makes sense.") );
		} elseif ( strlen($template->OID3) ) {
			printf( "<p>%s</p>\n", _("OID3 did not return any data.  Please check your MIB table.") );
		}
		
		switch ( $template->ProcessingProfile ) {
			case "SingleOIDAmperes":
				$amps = intval( $statsOutput[0] ) * intval( $template->Multiplier );
				$watts = $amps * intval( $row["Voltage"] );
				break;
			case "Combine3OIDAmperes":
				$amps = ( intval( $statsOutput[0] ) + intval( $statsOutput[1] ) + intval( $statsOutput[2] ) ) * intval( $template->Multiplier );
				$watts = $amps * intval( $row["Voltage"] );
				break;
			case "Convert3PhAmperes":
				$amps = ( intval( $statsOutput[0] ) + intval( $statsOutput[1] ) + intval( $statsOutput[2] ) ) * intval( $template->Multiplier ) / 3;
				$watts = $amps * 1.732 * intval( $row["Voltage"] );
				break;
			case "Combine3OIDWatts":
				$watts = ( intval( $statsOutput[0] ) + intval( $statsOutput[1] ) + intval( $statsOutput[2] ) ) * intval( $template->Multiplier );
			default:
				$watts = intval( $statsOutput[0] ) * intval( $template->Multiplier );
				break;
		}
		
		printf( "<p>%s %.2fkW</p>", _("Resulting kW from this test is"), $watts / 1000 );
	}
?>
</fieldset></div>
</div>
</div>
</body>
</html>
