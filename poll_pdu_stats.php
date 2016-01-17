<?php
  define( 'AUTHENTICATION', 'Apache' );
  require( 'db.inc.php' );
  require( 'facilities.inc.php' );
  
  $PDU=new PowerDistribution();
  
  $PDU->UpdateStats();
?>
