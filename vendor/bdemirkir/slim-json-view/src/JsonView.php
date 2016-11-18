<?php

namespace Slim\Views;

use Psr\Http\Message\ResponseInterface;

/**
 * JSON View
 *
 * This class is a Slim Framework view helper for simple JSON API's
 */
class JsonView {
    /**
     * Output rendered template
     *
     * @param  ResponseInterface $response
     * @param  array $data Associative array of data to be returned
     * @param  int $status HTTP status code
     * @return ResponseInterface
     */
    public function render(ResponseInterface $response, $data = [], $status = 200)
    {
        $status = intval($status);
        $r = $response->withStatus($status)
                      ->withHeader('Content-Type', 'application/json');
        $r->getBody()->write(json_encode($data));
        return $r;
    }
};