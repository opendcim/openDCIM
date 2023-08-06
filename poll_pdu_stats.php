<?php
  require( 'db.inc.php' );
  require( 'facilities.inc.php' );

  if ( php_sapi_name() != "cli" ) {
    echo "This script may only be run from the command line.";
    header( "Refresh: 5; url=" . redirect());    
  }

  # Filter Types (Case Sensitive) - Filter Value
  # Country - 2 letter Country Code
  # Container - Numeric ContainerID
  # DataCenter - Numeric DataCenterID
  # Zone - Numeric ZoneID
  # Row - Numeric RowID
  # None - No filtering applied

  $filterType = "None";
  $filterValue = "";

  $PDU=new PowerDistribution( $filterType, $filterValue );
  
  $PDU->UpdateStats();
?>
