<?php
require 'db.inc.php';
require 'hdd.class.php';

$hddid   = intval($_POST['hddid']   ?? 0);
$deviceid= intval($_POST['deviceid']?? 0);
if(!$hddid || !$deviceid){
  echo json_encode(['success'=>false,'error'=>'<?= addslashes(__("Missing parameters")) ?>']);
  exit;
}

$h = new hdd();
$h->HDDID   = $hddid;
if(!$h->GetHDD()){
  echo json_encode(['success'=>false,'error'=>'<?= addslashes(__("HDD not found")) ?>']);
  exit;
}

$h->DeviceID = $deviceid;
if($h->Save()){
  echo json_encode(['success'=>true]);
} else {
  echo json_encode(['success'=>false,'error'=>'<?= addslashes(__("Save failed")) ?>']);
}
