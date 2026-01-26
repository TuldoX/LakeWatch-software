<?php

namespace App\Controllers;

use App\Services\ApiClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApiController
{
    private ApiClient $api;

    public function __construct()
    {
        $this->api = new ApiClient();
    }

    public function proxy(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ) {
        $path = $args['path'];
        $method = $request->getMethod();

        $result = $this->api->forward(
            $method,
            "http://app.lakewatch.com/{$path}",
            $request
        );

        if ($result === null) {
            return $response->withStatus(401);
        }

        $response->getBody()->write($result['body']);
        return $response
            ->withStatus($result['status'])
            ->withHeader('Content-Type', 'application/json');
    }
}
