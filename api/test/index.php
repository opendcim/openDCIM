<?php

	require_once "../../vendor/autoload.php";
	
	use Psr\Http\Message\ServerRequestInterface as Request;
	use Psr\Http\Message\ResponseInterface as Response;

	$configuration = [
		'settings' => [
			'displayErrorDetails' => true,
		],
	];

	$c = new \Slim\Container($configuration);
	
	$app = new \Slim\App($c);

	$app->get( '/test', function(Request $request, Response $response) {
		$r['error'] = false;
		$r['errorcode'] = 200;

		return $response->withJson($r, $r['errorcode'] );
	});
 
$app->run();
?>
