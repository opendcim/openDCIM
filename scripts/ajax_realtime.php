<?php
        require_once( "../db.inc.php" );
        require_once( "../facilities.inc.php" );

       

        $sensorID = $_POST['graphID'];
        $dev=new Device();
        


        header('Content-Type: application/json');
        echo json_encode($dev->GetLatestReading($sensorID));
?>
