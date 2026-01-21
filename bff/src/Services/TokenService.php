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
            return null;
        }

        if ($_SESSION['tokens']['expires_at'] > time() + 30) {
            return $_SESSION['tokens']['access_token'];
        }

        // Refresh token
        $newTokens = $this->keycloak->refreshToken(
            $_SESSION['tokens']['refresh_token']
        );

        if (!isset($newTokens['access_token'])) {
            unset($_SESSION['tokens'], $_SESSION['user']);
            return null;
        }

        $_SESSION['tokens'] = [
            'access_token' => $newTokens['access_token'],
            'refresh_token' => $newTokens['refresh_token']
                ?? $_SESSION['tokens']['refresh_token'],
            'expires_at' => time() + $newTokens['expires_in'],
        ];

        return $_SESSION['tokens']['access_token'];
    }
}
