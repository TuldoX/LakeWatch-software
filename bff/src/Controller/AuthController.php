<?php

namespace App\Controller;

use App\Service\KeycloakService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController
{
    private KeycloakService $keycloak;

    public function __construct()
    {
        $this->keycloak = new KeycloakService();
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $loginUrl = $this->keycloak->getLoginUrl();

        return $response
            ->withHeader('Location', $loginUrl)
            ->withStatus(302);
    }

    public function callback(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();

        if (empty($params['code'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Missing authorization code'
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $tokenData = $this->keycloak->exchangeCodeForToken($params['code']);

        // Regenerate session ID after login (security best practice)
        session_regenerate_id(true);

        $_SESSION['access_token']  = $tokenData['access_token'];
        $_SESSION['refresh_token'] = $tokenData['refresh_token'];
        $_SESSION['expires_at']    = time() + $tokenData['expires_in'];

        $userInfo = $this->keycloak->getUserInfo($tokenData['access_token']);
        $_SESSION['user'] = $userInfo;

        return $response
            ->withHeader('Location', $_ENV['FRONTEND_URL'])
            ->withStatus(302);
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (empty($_SESSION['user'])) {
            $response->getBody()->write(json_encode([
                'authenticated' => false
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        $response->getBody()->write(json_encode([
            'authenticated' => true,
            'user' => $_SESSION['user'],
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}