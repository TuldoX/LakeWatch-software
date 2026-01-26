<?php

namespace App\Services;

use App\Services\AuthService;

class KeycloakService
{
    private AuthService $authService;

    public function __construct()
    {
        $this->clientSecret = $_ENV['CLIENT_SECRET'] ?: '';
        $this->authService = new AuthService();
    }

    private string $baseUrl = 'http://keycloak:8080';
    private string $externalBaseUrl = 'http://accounts.lakewatch.com';
    private string $realm = 'lakewatch';
    private string $clientId = 'lakewatch-bff';
    private string $clientSecret;
    private string $redirectUri = 'http://app.lakewatch.com/bff/auth/callback';

    public function getLoginUrl(): string
    {
        return "{$this->externalBaseUrl}/realms/{$this->realm}/protocol/openid-connect/auth"
            . "?client_id={$this->clientId}"
            . "&response_type=code"
            . "&scope=openid profile email"
            . "&redirect_uri=" . urlencode($this->redirectUri);
    }

    public function exchangeCodeForTokens(string $code): array
    {
        $url = "{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/token";
        
        $postData = http_build_query([
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                           "Content-Length: " . strlen($postData) . "\r\n",
                'content' => $postData,
            ]
        ]);

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            error_log("Keycloak token exchange failed - no response from server");
            error_log("URL: {$url}");
            throw new \RuntimeException("Failed to connect to Keycloak");
        }

        $data = json_decode($response, true);

        if (!isset($data['access_token'])) {
            error_log("Keycloak token exchange error response: " . $response);
            throw new \RuntimeException("Failed to get access token from Keycloak: " . ($data['error_description'] ?? 'Unknown error'));
        }

        return $data;
    }

    public function refreshToken(string $refreshToken): array
    {
        $url = "{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/token";
        
        $postData = http_build_query([
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                           "Content-Length: " . strlen($postData) . "\r\n",
                'content' => $postData,
                'ignore_errors' => true,
            ]
        ]);

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            error_log("Keycloak token refresh failed - no response from server");
            return [];
        }

        $data = json_decode($response, true);

        if (!isset($data['access_token'])) {
            error_log("Keycloak token refresh error: " . $response);
            return [];
        }

        return $data;
    }

    public function getUserFromIdToken(string $idToken): array
    {
        try {
            $decoded = $this->authService->decodeToken($idToken);

            return [
                'id' => $decoded->sub ?? null,
                'username' => $decoded->preferred_username ?? null,
                'email' => $decoded->email ?? null,
                'firstName' => $decoded->given_name ?? null,
                'lastName' => $decoded->family_name ?? null,
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException("Invalid or unverified ID token");
        }
    }
}