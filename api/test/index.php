<?php

	require_once "../../vendor/autoload.php";
	
/* Code for when we go to Slim Framework v3	
	use Psr\Http\Message\ServerRequestInterface as Request;
	use Psr\Http\Message\ResponseInterface as Response;

	$configuration = [
		'settings' => [
			'displayErrorDetails' => true,
		],
	];

	$c = new \Slim\Container($configuration);
	
	$app = new \Slim\App($c);
*/

// Framework v2 Specific - we have to do our own output formatting
function echoResponse( $response ) {
	$app = \Slim\Slim::getInstance();

	if ( array_key_exists( 'errorcode', $response )) {
		$app->status( $response['errorcode'] );
	}

	$app->contentType( 'application/json' );

	echo json_encode( $response );
}

	\Slim\Slim::registerAutoLoader();
	$app = new \Slim\Slim();

//	$app->get('/test', function( Request $in, Response $out ) {
	$app->get( '/test', function() {
		$r['error'] = false;
		$r['errorcode'] = 200;
		/* v3 code 
		$out = $out->withJson($r, $r['errorcode'] );
		return $out;
		*/

		echoResponse( $r );
	});
 
$app->run();
?>
