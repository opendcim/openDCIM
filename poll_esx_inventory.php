<?php
  require_once('db.inc.php');
  require_once('facilities.inc.php');
 
  if ( php_sapi_name() != "cli" ) {
    echo "This script may only be run from the command line.";
    header( "Refresh: 5; url=" . redirect());    
  }

  $esx = new ESX();
  
  $esx->UpdateInventory();
  
?>
