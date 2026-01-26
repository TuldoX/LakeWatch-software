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

        error_log("ApiController.proxy called: method=$method, path=$path");

        $result = $this->api->forward(
            $method,
            "http://app.lakewatch.com/api/{$path}",
            $request
        );

        error_log("ApiClient.forward result: " . json_encode($result));

        if ($result === null) {
            error_log("ApiClient.forward returned null");
            return $response->withStatus(401);
        }

        $response->getBody()->write($result['body']);
        return $response
            ->withStatus($result['status'])
            ->withHeader('Content-Type', 'application/json');
    }
}
