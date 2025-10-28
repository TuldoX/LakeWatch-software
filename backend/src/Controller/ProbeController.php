<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ProbeController {
    public function insertData(Request $request, Response $response, array $args): Response {
        $response->getBody()->write("Hi there!");
        return $response->withStatus(200);
    }
}
