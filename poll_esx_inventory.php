<?php
  require_once( 'vendor_preset.inc.php' );
  require_once( 'facilities.inc.php' );
  
  $esx = new ESX();
  
  $esx->UpdateInventory();
  
?>
