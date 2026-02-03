<?php

namespace App\Controller;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApiController
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => rtrim($_ENV['API_BASE_URL'], '/'),
            'timeout'  => 15,
        ]);
    }

    public function proxy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (empty($_SESSION['access_token'])) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $path   = $args['path'] ?? '';
        $method = $request->getMethod();

        try {
            $apiResponse = $this->client->request($method, '/' . $path, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_SESSION['access_token'],
                    'Accept'        => 'application/json',
                ],
                'query' => $request->getQueryParams(),
                'body'  => $request->getBody()->getContents(),
            ]);

            $response->getBody()->write((string)$apiResponse->getBody());

            return $response
                ->withStatus($apiResponse->getStatusCode())
                ->withHeader('Content-Type', $apiResponse->getHeaderLine('Content-Type'));
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode([
                'error' => 'API request failed',
                'message' => $e->getMessage(),
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(502);
        }
    }
}