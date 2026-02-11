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

        session_regenerate_id(true);

        $_SESSION['access_token']  = $tokenData['access_token'];
        $_SESSION['refresh_token'] = $tokenData['refresh_token'] ?? null;
        $_SESSION['id_token']      = $tokenData['id_token']      ?? null;
        $_SESSION['expires_at']    = time() + ($tokenData['expires_in'] ?? 300);

        $userInfo = $this->keycloak->getUserInfo($tokenData['access_token']);
        $_SESSION['user'] = $userInfo;

        return $response
            ->withHeader('Location', $_ENV['FRONTEND_URL'])
            ->withStatus(302);
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (empty($_SESSION['user'])) {
            return $this->jsonResponse($response, ['authenticated' => false], 401);
        }

        return $this->jsonResponse($response, [
            'authenticated' => true,
            'user' => $_SESSION['user'],
        ], 200);
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $idToken = $_SESSION['id_token'] ?? null;

        // Clear session
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        // Build logout URL
        $logoutUrl = $this->buildKeycloakLogoutUrl($idToken);

        // Debug: log the final logout URL (check your error log)
        error_log("Logout redirecting to: " . $logoutUrl);

        return $response
            ->withHeader('Location', $logoutUrl)
            ->withStatus(302);
    }

    private function buildKeycloakLogoutUrl(?string $idToken): string
    {
        $base  = rtrim($_ENV['KEYCLOAK_PUBLIC_URL'], '/');
        $realm = $_ENV['KEYCLOAK_REALM'];

        $params = [
            'client_id' => $_ENV['KEYCLOAK_CLIENT_ID'],
        ];

        // Add post_logout_redirect_uri only if it's set and valid-looking
        if (!empty($_ENV['FRONTEND_URL'])) {
            $params['post_logout_redirect_uri'] = $_ENV['FRONTEND_URL'];
        }

        // Include id_token_hint if we have it (very important!)
        if ($idToken) {
            $params['id_token_hint'] = $idToken;
        }

        $query = http_build_query($params);

        return $base . '/realms/' . $realm . '/protocol/openid-connect/logout?' . $query;
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}