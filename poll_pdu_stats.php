<?php
  require( 'vendor_preset.inc.php' );
  require( 'facilities.inc.php' );
  
  $PDU=new PowerDistribution();
  
  $PDU->UpdateStats();
?>
