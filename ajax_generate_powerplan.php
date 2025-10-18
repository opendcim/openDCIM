require_once "db.inc.php";
require_once "facilities.inc.php";

$cabinetid = intval($_POST['cabinetid']);
$mode = sanitize($_POST['mode']);

$pdus = PowerDistribution::GetPDUbyCabinet($cabinetid);
if(count($pdus)<2){
    echo "<div class='alert alert-warning'>".__("This cabinet requires at least 2 PDUs for automatic planning.")."</div>";
    exit;
}

$pduA = $pdus[0];
$pduB = $pdus[1];

$devices = Device::GetDevicesByCabinet($cabinetid);
$plan = [];
$totalPowerA = $totalPowerB = 0;
$portA = PowerConnection::GetNextFreePort($pduA->PDUID);
$portB = PowerConnection::GetNextFreePort($pduB->PDUID);

foreach($devices as $dev){
  if(in_array($dev->DeviceType, ["Server","Switch","Appliance","Chassis","Storage Array"])){
      $portsNeeded = max(1, intval($dev->PowerSupplyCount)); // nombre d’alim du device
      $power = intval($dev->Power);
      $entry = ["device"=>$dev->Label,"deviceid"=>$dev->DeviceID];

      switch($mode){
        case "balanced":
            $target = ($totalPowerA <= $totalPowerB) ? "A" : "B";
            if($target=="A" && PowerConnection::HasFreePorts($pduA->PDUID, $portsNeeded)){
                $entry["pdu"]=$pduA->Label; $entry["pduname"]="A";
                $entry["ports"]=PowerConnection::ReservePorts($pduA->PDUID,$portsNeeded);
                $totalPowerA += $power;
            } elseif(PowerConnection::HasFreePorts($pduB->PDUID, $portsNeeded)) {
                $entry["pdu"]=$pduB->Label; $entry["pduname"]="B";
                $entry["ports"]=PowerConnection::ReservePorts($pduB->PDUID,$portsNeeded);
                $totalPowerB += $power;
            }
            break;

        case "dualpath":
            if(PowerConnection::HasFreePorts($pduA->PDUID,1) && PowerConnection::HasFreePorts($pduB->PDUID,1)){
                $entry["pdu"]="A/B";
                $entry["portsA"]=PowerConnection::ReservePorts($pduA->PDUID,1);
                $entry["portsB"]=PowerConnection::ReservePorts($pduB->PDUID,1);
                $totalPowerA+=$power/2; $totalPowerB+=$power/2;
            }
            break;

        case "intelligent":
            // Équilibrage réel selon puissance
            $target = ($totalPowerA <= $totalPowerB) ? "A" : "B";
            $bestPDU = ($target=="A") ? $pduA : $pduB;
            if(PowerConnection::HasFreePorts($bestPDU->PDUID,$portsNeeded)){
                $entry["pdu"]=$bestPDU->Label;
                $entry["ports"]=PowerConnection::ReservePorts($bestPDU->PDUID,$portsNeeded);
                if($target=="A") $totalPowerA+=$power; else $totalPowerB+=$power;
            } else {
                // bascule si pas assez de ports
                $altPDU = ($target=="A") ? $pduB : $pduA;
                if(PowerConnection::HasFreePorts($altPDU->PDUID,$portsNeeded)){
                    $entry["pdu"]=$altPDU->Label;
                    $entry["ports"]=PowerConnection::ReservePorts($altPDU->PDUID,$portsNeeded);
                    if($target=="A") $totalPowerB+=$power; else $totalPowerA+=$power;
                }
            }
            break;
      }
      $plan[]=$entry;
  }
}

// Rendu HTML
echo "<h4>".__("Proposed Power Connection Plan")."</h4>";
echo "<table class='table table-striped'>
<tr><th>".__("Device")."</th><th>".__("Ports")."</th><th>".__("PDU")."</th></tr>";
foreach($plan as $p){
    $ports=isset($p["portsA"]) ? "A:".implode(',',$p["portsA"])." / B:".implode(',',$p["portsB"]) : implode(',',$p["ports"]);
    echo "<tr><td>{$p['device']}</td><td>$ports</td><td>{$p['pdu']}</td></tr>";
}
echo "</table>";

echo "<div class='center'>";
if($person->WriteAccess()){
    echo "<button id='btnApplyPowerPlan' class='btn btn-success'>".__("Apply and Save")."</button>";
} else {
    echo "<div class='alert alert-info'>".__("Read-only mode: you can preview and print the plan but not apply changes.")."</div>";
}
echo " <button onclick='window.print()' class='btn btn-secondary'>".__("Print Power Plan")."</button></div>";

$_SESSION['autoplan_'.$cabinetid] = $plan; // stockage temporaire pour validation
