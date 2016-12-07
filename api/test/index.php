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

	$app->get('/test', function( Request $in, Response $out ) {
		$r['error'] = false;
		$r['errorcode'] = 200;
		$out = $out->withJson($r, $r['errorcode'] );
		return $out;
	});
 
$app->run();
?>
