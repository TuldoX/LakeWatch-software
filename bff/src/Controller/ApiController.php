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
                    'body'  => $request->getBody()->getContents(), // careful: body can only be read once
                    // Tip: if you need to support large/multipart bodies → read once before loop
                ]);

                $response->getBody()->write((string)$apiResponse->getBody());

                return $response
                    ->withStatus($apiResponse->getStatusCode())
                    ->withHeader('Content-Type', $apiResponse->getHeaderLine('Content-Type'));

            } catch (RequestException $e) {
                $apiResp = $e->getResponse();

                if ($apiResp && $apiResp->getStatusCode() === 401 && $attempts < $maxAttempts) {
                    // Try to refresh token
                    if (empty($_SESSION['refresh_token'])) {
                        break; // no refresh token → give up
                    }

                    try {
                        $newTokens = $this->keycloak->refreshToken($_SESSION['refresh_token']);

                        // Update session
                        session_regenerate_id(true); // good practice after credential change

                        $_SESSION['access_token']  = $newTokens['access_token'];
                        $_SESSION['refresh_token'] = $newTokens['refresh_token'] ?? $_SESSION['refresh_token'];
                        $_SESSION['expires_at']    = time() + $newTokens['expires_in'];

                        // Optional: refresh userinfo too
                        $userInfo = $this->keycloak->getUserInfo($newTokens['access_token']);
                        $_SESSION['user'] = $userInfo;

                        // Continue to retry the request with new token
                        continue;
                    } catch (\Exception $refreshEx) {
                        // Refresh failed → log it, but continue to return 401
                        error_log("Token refresh failed: " . $refreshEx->getMessage());
                        break;
                    }
                }

                // Other errors or refresh failed
                $errorMessage = $apiResp
                    ? (string)$apiResp->getBody()
                    : $e->getMessage();

                return $this->jsonResponse($response, [
                    'error'   => 'API request failed',
                    'message' => $errorMessage,
                ], $apiResp ? $apiResp->getStatusCode() : 502);
            }
        }

        // All attempts failed
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