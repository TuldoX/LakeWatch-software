<?php

namespace App\Controllers;

use App\Services\KeycloakService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController
{
    private KeycloakService $keycloak;

    public function __construct()
    {
        $this->keycloak = new KeycloakService();
    }

    // STEP 1: Redirect to Keycloak login
    public function login(ServerRequestInterface $request, ResponseInterface $response)
    {
        $_SESSION['return_to'] = '/';
        return $response
            ->withHeader('Location', $this->keycloak->getLoginUrl())
            ->withStatus(302);
    }

    // STEP 2: Keycloak callback
    public function callback(ServerRequestInterface $request, ResponseInterface $response)
    {
        $code = $request->getQueryParams()['code'] ?? null;

        if (!$code) {
            return $response->withStatus(401);
        }

        $tokens = $this->keycloak->exchangeCodeForTokens($code);

        session_regenerate_id(true); // prevent session fixation

        $user = $this->keycloak->getUserFromIdToken($tokens['id_token']);

        $_SESSION['user'] = $user;
        $_SESSION['tokens'] = [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_at' => time() + $tokens['expires_in'],
        ];

        $returnTo = $_SESSION['return_to'] ?? '/';
        unset($_SESSION['return_to']);

        return $response
            ->withHeader('Location', 'http://app.lakewatch.com' . $returnTo)
            ->withStatus(302);
    }

    // STEP 3: Return logged-in user info
    public function me(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withStatus(401);
        }

        $response->getBody()->write(json_encode($_SESSION['user']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}