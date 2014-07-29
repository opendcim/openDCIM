<?php
  require( 'db.inc.php' );
  require( 'facilities.inc.php' );
  
  $PDU=new PowerDistribution();
  $PDU->UpdateStats();
  
  $Panel=new PowerPanel();
  $Panel->UpdateStats();
?>
