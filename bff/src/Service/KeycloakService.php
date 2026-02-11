<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class KeycloakService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => rtrim($_ENV['KEYCLOAK_BASE_URL'], '/'),
            'timeout'  => 10,
        ]);
    }

    public function getLoginUrl(): string
    {
        $query = http_build_query([
            'client_id'     => $_ENV['KEYCLOAK_CLIENT_ID'],
            'response_type' => 'code',
            'scope'         => 'openid profile email',
            'redirect_uri'  => $_ENV['KEYCLOAK_REDIRECT_URI'],
        ]);

        return $_ENV['KEYCLOAK_PUBLIC_URL']
            . '/realms/' . $_ENV['KEYCLOAK_REALM']
            . '/protocol/openid-connect/auth?' . $query;
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = $this->client->post(
            '/realms/' . $_ENV['KEYCLOAK_REALM'] . '/protocol/openid-connect/token',
            [
                'form_params' => [
                    'grant_type'    => 'authorization_code',
                    'client_id'     => $_ENV['KEYCLOAK_CLIENT_ID'],
                    'client_secret' => $_ENV['KEYCLOAK_CLIENT_SECRET'],
                    'redirect_uri'  => $_ENV['KEYCLOAK_REDIRECT_URI'],
                    'code'          => $code,
                ],
            ]
        );

        return json_decode((string)$response->getBody(), true);
    }

    public function getUserInfo(string $accessToken): array
    {
        $response = $this->client->get(
            '/realms/' . $_ENV['KEYCLOAK_REALM'] . '/protocol/openid-connect/userinfo',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]
        );

        return json_decode((string)$response->getBody(), true);
    }

    public function refreshToken(string $refreshToken, array &$session): array
    {
        try {
            $formParams = [
                'grant_type'    => 'refresh_token',
                'client_id'     => $_ENV['KEYCLOAK_CLIENT_ID'],
                'client_secret' => $_ENV['KEYCLOAK_CLIENT_SECRET'],
                'refresh_token' => $refreshToken,
            ];

            if (!empty($session['id_token'])) {
                $formParams['id_token_hint'] = $session['id_token'];
            }

            $response = $this->client->post(
                '/realms/' . $_ENV['KEYCLOAK_REALM'] . '/protocol/openid-connect/token',
                ['form_params' => $formParams]
            );

            $tokens = json_decode((string)$response->getBody(), true);

            if (!empty($tokens['id_token'])) {
                $session['id_token'] = $tokens['id_token'];
            }

            return $tokens;

        } catch (RequestException $e) {
            $response = $e->getResponse();
            $errorDetail = $e->getMessage();

            if ($response) {
                $body = (string)$response->getBody();
                $errorBody = json_decode($body, true);
                $errorDetail = $errorBody['error_description'] ?? $body;
            }

            error_log("Refresh token failed: " . $errorDetail);
            throw new \Exception("Token refresh failed: " . $errorDetail);
        }
    }
}