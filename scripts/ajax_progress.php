<?php
        require_once( "../db.inc.php" );
        require_once( "../facilities.inc.php" );

        class Response {
                var $Percentage;
        }

        $resp = new Response();

        // everyone hates error_log spam
        if(session_id()==""){
                session_start();
        }

        if ( session_id() === "" ) {
                $resp->Percentage = 0;
        } else {
                $job = JobQueue::getStatus( session_id() );
                $resp->SessionID = $job["SessionID"];
                $resp->Percentage = $job["Percentage"];
                $resp->Status = $job["Status"];
        }

        header('Content-Type: application/json');
        echo json_encode($resp);
?>
