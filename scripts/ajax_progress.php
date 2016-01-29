<?php
        require_once( "../db.inc.php" );
        require_once( "../facilities.inc.php" );

        class Response {
                var $Percentage;
        }

        $resp = new Response();

        session_start();

        if ( session_id() === "" ) {
                $resp->Percentage = 0;
        } else {
                $resp->Percentage = JobQueue::getStatus( session_id() );
        }

        header('Content-Type: application/json');
        echo json_encode($resp);
?>
