<?php
	require_once( "../../Slim/Slim.php" );
	
	\Slim\Slim::registerAutoloader();
	
	$app = new \Slim\Slim();

	$app->get('/test', function() {
		$response['error'] = false;
		$response['errorcode'] = 200;
		echoResponse(200, $response);
	});
 
/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}

$app->run();
?>
