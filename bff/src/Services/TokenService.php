<?php

namespace App\Services;

class TokenService
{
    private KeycloakService $keycloak;

    public function __construct()
    {
        $this->keycloak = new KeycloakService();
    }

    public function getValidAccessToken(): ?string
    {
        if (!isset($_SESSION['tokens'])) {
            error_log("TokenService: No tokens in session");
            return null;
        }

        // Check if token exists and is still valid
        if (isset($_SESSION['tokens']['expires_at']) && $_SESSION['tokens']['expires_at'] > time() + 30) {
            error_log("TokenService: Using cached token, expires at: " . $_SESSION['tokens']['expires_at']);
            return $_SESSION['tokens']['access_token'] ?? null;
        }

        // Token expired or doesn't have expires_at, try to refresh
        if (!isset($_SESSION['tokens']['refresh_token'])) {
            error_log("TokenService: No refresh token available");
            unset($_SESSION['tokens'], $_SESSION['user']);
            return null;
        }

        error_log("TokenService: Refreshing token");
        $newTokens = $this->keycloak->refreshToken(
            $_SESSION['tokens']['refresh_token']
        );

        if (!isset($newTokens['access_token'])) {
            error_log("TokenService: Token refresh failed, no access_token in response");
            unset($_SESSION['tokens'], $_SESSION['user']);
            return null;
        }

        $_SESSION['tokens'] = [
            'access_token' => $newTokens['access_token'],
            'refresh_token' => $newTokens['refresh_token']
                ?? $_SESSION['tokens']['refresh_token'],
            'expires_at' => time() + $newTokens['expires_in'],
        ];

        error_log("TokenService: Token refreshed successfully");
        return $_SESSION['tokens']['access_token'];
    }
}