<?php
  require_once('db.inc.php');
  require_once('facilities.inc.php');
  
  $esx = new ESX();
  
  $esx->UpdateInventory();
  
?>
