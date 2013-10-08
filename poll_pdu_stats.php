<?php
  require( 'db.inc.php' );
  require( 'facilities.inc.php' );
  
  $PDU=new PowerDistribution();
  
  $PDU->UpdateStats();
?>
