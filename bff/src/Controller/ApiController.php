<?php

namespace App\Controller;

use App\Service\KeycloakService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApiController
{
    private Client $client;
    private KeycloakService $keycloak;

    public function __construct(KeycloakService $keycloak)
    {
        $this->keycloak = $keycloak;
        $this->client   = new Client([
            'base_uri' => rtrim($_ENV['API_BASE_URL'], '/'),
            'timeout'  => 15,
        ]);
    }

    public function proxy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (empty($_SESSION['access_token'])) {
            error_log("Proxy - no access token in session");
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 401);
        }

        // Proactively refresh before making the request if token is expired
        if (AuthController::refreshIfNeeded() === false) {
            error_log("Proxy - proactive refresh failed");
            return $this->jsonResponse($response, ['error' => 'Unauthorized - session expired'], 401);
        }

        $path    = $args['path'] ?? '';
        $method  = $request->getMethod();
        $body    = $request->getBody()->getContents(); // read once here
        $attempts    = 0;
        $maxAttempts = 2;

        while ($attempts < $maxAttempts) {
            $attempts++;

            error_log("Proxy attempt $attempts - calling API: $method /$path with token ending: " . substr($_SESSION['access_token'], -10));

            try {
                $apiResponse = $this->client->request($method, '/' . $path, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $_SESSION['access_token'],
                        'Accept'        => 'application/json',
                    ],
                    'query' => $request->getQueryParams(),
                    'body'  => $body,
                ]);

                $response->getBody()->write((string)$apiResponse->getBody());

                return $response
                    ->withStatus($apiResponse->getStatusCode())
                    ->withHeader('Content-Type', $apiResponse->getHeaderLine('Content-Type'));

            } catch (RequestException $e) {
                $apiResp   = $e->getResponse();
                $errorCode = $apiResp ? $apiResp->getStatusCode() : 0;

                error_log("Proxy attempt $attempts failed with status: $errorCode");

                // On 401 from the API, force a token refresh and retry once
                if ($errorCode === 401 && $attempts < $maxAttempts) {
                    error_log("API returned 401 - forcing token refresh");

                    // Force expires_at to 0 so refreshIfNeeded always attempts refresh
                    $_SESSION['expires_at'] = 0;

                    if (AuthController::refreshIfNeeded() === false) {
                        error_log("Forced refresh failed - session destroyed");
                        return $this->jsonResponse($response, ['error' => 'Unauthorized - token refresh failed'], 401);
                    }

                    error_log("Forced refresh succeeded - retrying API call");
                    continue;
                }

                $errorMessage = $apiResp ? (string)$apiResp->getBody() : $e->getMessage();

                return $this->jsonResponse($response, [
                    'error'   => 'API request failed',
                    'message' => $errorMessage,
                ], $errorCode ?: 502);
            }
        }

        return $this->jsonResponse($response, ['error' => 'Unauthorized - token refresh failed'], 401);
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}