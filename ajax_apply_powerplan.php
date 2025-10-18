require_once "db.inc.php";
require_once "facilities.inc.php";

$cabinetid = intval($_POST['cabinetid']);
$plan = $_SESSION['autoplan_'.$cabinetid];

if(!$person->WriteAccess()){
    echo json_encode(["error"=>__("You do not have permission to apply this plan.")]);
    exit;
}

foreach($plan as $p){
    if(isset($p["portsA"])){
        foreach($p["portsA"] as $port){
            $pc=new PowerConnection();
            $pc->DeviceID=$p["deviceid"];
            $pc->PDUID=$pduA->PDUID;
            $pc->PDUPosition=$port;
            $pc->CreateConnection();
            LogActions::Insert('Device',$pc->DeviceID,'AutoLink','PDU',$pduA->Label,$port);
        }
        foreach($p["portsB"] as $port){
            $pc=new PowerConnection();
            $pc->DeviceID=$p["deviceid"];
            $pc->PDUID=$pduB->PDUID;
            $pc->PDUPosition=$port;
            $pc->CreateConnection();
            LogActions::Insert('Device',$pc->DeviceID,'AutoLink','PDU',$pduB->Label,$port);
        }
    } else {
        foreach($p["ports"] as $port){
            $targetPDU = ($p["pdu"]==$pduA->Label)?$pduA:$pduB;
            $pc=new PowerConnection();
            $pc->DeviceID=$p["deviceid"];
            $pc->PDUID=$targetPDU->PDUID;
            $pc->PDUPosition=$port;
            $pc->CreateConnection();
            LogActions::Insert('Device',$pc->DeviceID,'AutoLink','PDU',$targetPDU->Label,$port);
        }
    }
}
echo json_encode(["success"=>__("Power plan applied successfully.")]);
