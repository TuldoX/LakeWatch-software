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
            $response->getBody()->write(json_encode(['error' => 'Missing authorization code']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $tokenData = $this->keycloak->exchangeCodeForToken($params['code']);

        session_regenerate_id(true);

        $_SESSION['access_token']  = $tokenData['access_token'];
        $_SESSION['refresh_token'] = $tokenData['refresh_token'] ?? null;
        $_SESSION['id_token']      = $tokenData['id_token'] ?? null;
        $_SESSION['expires_at']    = time() + ($tokenData['expires_in'] ?? 300);

        $userInfo = $this->keycloak->getUserInfo($tokenData['access_token']);
        $_SESSION['user'] = $userInfo;

        error_log("Login callback - tokens stored. refresh_token present: " . (!empty($_SESSION['refresh_token']) ? 'yes' : 'no'));

        return $response
            ->withHeader('Location', $_ENV['FRONTEND_URL'])
            ->withStatus(302);
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (empty($_SESSION['user'])) {
            return $this->jsonResponse($response, ['authenticated' => false], 401);
        }

        $refreshed = self::refreshIfNeeded();

        if ($refreshed === false) {
            return $this->jsonResponse($response, ['authenticated' => false], 401);
        }

        return $this->jsonResponse($response, [
            'authenticated' => true,
            'user'          => $_SESSION['user'],
        ], 200);
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $idToken = $_SESSION['id_token'] ?? null;

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }

        session_destroy();

        $logoutUrl = $this->buildKeycloakLogoutUrl($idToken);
        error_log("Logout redirecting to: " . $logoutUrl);

        return $response->withHeader('Location', $logoutUrl)->withStatus(302);
    }

    /**
     * Attempts to refresh the access token if it is expired or about to expire.
     * Returns true if the session is valid (no refresh needed or refresh succeeded).
     * Returns false if the refresh failed and the session has been destroyed.
     */
    public static function refreshIfNeeded(): bool
    {
        $expiresAt = $_SESSION['expires_at'] ?? 0;
        $timeNow   = time();

        error_log("refreshIfNeeded - expires_at: $expiresAt, now: $timeNow, diff: " . ($expiresAt - $timeNow) . "s");

        // Token still valid for more than 60 seconds
        if ($expiresAt > $timeNow + 60) {
            error_log("Token still valid, no refresh needed");
            return true;
        }

        error_log("Token expired or expiring soon - attempting refresh");

        if (empty($_SESSION['refresh_token'])) {
            error_log("No refresh token in session - destroying session");
            $_SESSION = [];
            session_destroy();
            return false;
        }

        try {
            $keycloak  = new KeycloakService();
            $newTokens = $keycloak->refreshToken($_SESSION['refresh_token'], $_SESSION);

            session_regenerate_id(true);

            $_SESSION['access_token']  = $newTokens['access_token'];
            $_SESSION['refresh_token'] = $newTokens['refresh_token'] ?? $_SESSION['refresh_token'];
            $_SESSION['expires_at']    = time() + ($newTokens['expires_in'] ?? 300);

            $userInfo = $keycloak->getUserInfo($newTokens['access_token']);
            $_SESSION['user'] = $userInfo;

            error_log("Token refreshed successfully, new expires_at: " . $_SESSION['expires_at']);

            return true;

        } catch (\Exception $e) {
            error_log("Token refresh failed: " . $e->getMessage());
            $_SESSION = [];
            session_destroy();
            return false;
        }
    }

    private function buildKeycloakLogoutUrl(?string $idToken): string
    {
        $base  = rtrim($_ENV['KEYCLOAK_PUBLIC_URL'], '/');
        $realm = $_ENV['KEYCLOAK_REALM'];

        $params = ['client_id' => $_ENV['KEYCLOAK_CLIENT_ID']];

        if (!empty($_ENV['FRONTEND_URL'])) {
            $params['post_logout_redirect_uri'] = $_ENV['FRONTEND_URL'];
        }

        if ($idToken) {
            $params['id_token_hint'] = $idToken;
        }

        return $base . '/realms/' . $realm . '/protocol/openid-connect/logout?' . http_build_query($params);
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}