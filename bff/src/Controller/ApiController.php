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
        $this->client = new Client([
            'base_uri' => rtrim($_ENV['API_BASE_URL'], '/'),
            'timeout'  => 15,
        ]);
    }

    public function proxy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (empty($_SESSION['access_token'])) {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 401);
        }

        $path   = $args['path'] ?? '';
        $method = $request->getMethod();

        $attempts = 0;
        $maxAttempts = 2;

        while ($attempts < $maxAttempts) {
            $attempts++;

            try {
                $apiResponse = $this->client->request($method, '/' . $path, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $_SESSION['access_token'],
                        'Accept'        => 'application/json',
                    ],
                    'query' => $request->getQueryParams(),
                    'body'  => $request->getBody()->getContents(), // note: body readable only once
                ]);

                $response->getBody()->write((string)$apiResponse->getBody());

                return $response
                    ->withStatus($apiResponse->getStatusCode())
                    ->withHeader('Content-Type', $apiResponse->getHeaderLine('Content-Type'));

            } catch (RequestException $e) {
                $apiResp = $e->getResponse();

                // Try refresh on 401 or sometimes 403 (API-dependent)
                if ($apiResp && in_array($apiResp->getStatusCode(), [401, 403]) && $attempts < $maxAttempts) {
                    if (empty($_SESSION['refresh_token'])) {
                        error_log("No refresh token available for retry");
                        break;
                    }

                    try {
                        // Pass $_SESSION by reference so refreshToken can read/write id_token
                        $newTokens = $this->keycloak->refreshToken($_SESSION['refresh_token'], $_SESSION);

                        session_regenerate_id(true);

                        $_SESSION['access_token']  = $newTokens['access_token'];
                        $_SESSION['refresh_token'] = $newTokens['refresh_token'] ?? $_SESSION['refresh_token'];
                        $_SESSION['expires_at']    = time() + $newTokens['expires_in'];

                        // Refresh userinfo
                        $userInfo = $this->keycloak->getUserInfo($newTokens['access_token']);
                        $_SESSION['user'] = $userInfo;

                        error_log("Token refreshed successfully");

                        continue; // retry API call with new token
                    } catch (\Exception $refreshEx) {
                        error_log("Token refresh failed: " . $refreshEx->getMessage());
                        break;
                    }
                }

                // Other errors
                $errorMessage = $apiResp
                    ? (string)$apiResp->getBody()
                    : $e->getMessage();

                return $this->jsonResponse($response, [
                    'error'   => 'API request failed',
                    'message' => $errorMessage,
                ], $apiResp ? $apiResp->getStatusCode() : 502);
            }
        }

        return $this->jsonResponse($response, ['error' => 'Unauthorized - token refresh failed'], 401);
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}