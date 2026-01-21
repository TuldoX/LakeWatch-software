<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class KeycloakService
{
    // Internal URL for server-to-server communication within Docker
    private string $baseUrl = 'http://keycloak:8080';
    // External URL for browser redirects
    private string $externalBaseUrl = 'http://accounts.lakewatch.com';
    private string $realm = 'lakewatch';
    private string $clientId = 'lakewatch-bff';
    private string $clientSecret = 'iLkj7Fd8FeZCa2pRvkwUVvvqKJexHtDb'; // Replace with your actual secret
    private string $redirectUri = 'http://app.lakewatch.com/bff/auth/callback';

    public function getLoginUrl(): string
    {
        // Use external URL for browser redirect
        return "{$this->externalBaseUrl}/realms/{$this->realm}/protocol/openid-connect/auth"
            . "?client_id={$this->clientId}"
            . "&response_type=code"
            . "&scope=openid profile email"
            . "&redirect_uri=" . urlencode($this->redirectUri);
    }

    public function exchangeCodeForTokens(string $code): array
    {
        // Use internal URL for server-to-server communication
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
                'ignore_errors' => true, // Important: allows us to read error responses
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
        // Use internal URL for server-to-server communication
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
        // For now, decode without verification
        // In production, you should fetch and use Keycloak's public key
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new \RuntimeException("Invalid ID token format");
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        return [
            'id' => $payload['sub'] ?? null,
            'username' => $payload['preferred_username'] ?? null,
            'email' => $payload['email'] ?? null,
            'firstName' => $payload['given_name'] ?? null,
            'lastName' => $payload['family_name'] ?? null,
        ];
    }
}