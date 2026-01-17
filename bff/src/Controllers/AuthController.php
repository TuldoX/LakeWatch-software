<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\KeycloakService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * AuthController - Handles authentication flows
 * 
 * Endpoints:
 * - GET  /auth/login - Redirects to Keycloak login
 * - GET  /auth/callback - Handles Keycloak callback
 * - POST /auth/logout - Logs out user
 * - GET  /auth/me - Returns current user info
 */
class AuthController
{
    public function __construct(
        private KeycloakService $keycloakService
    ) {}

    /**
     * Login endpoint - Redirects user to Keycloak login page
     * 
     * URL: GET /auth/login?return_url=/dashboard
     * 
     * Flow:
     * 1. Frontend calls this endpoint
     * 2. We generate a CSRF state token
     * 3. We store the return URL (where to redirect after login)
     * 4. We redirect to Keycloak login page
     * 
     * @param Request $request
     * @param Response $response
     * @return Response Redirect response
     */
    public function login(Request $request, Response $response): Response
    {
        // Generate random state for CSRF protection
        // This prevents attackers from initiating login on behalf of users
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        // Store where to redirect user after successful login
        $queryParams = $request->getQueryParams();
        $returnUrl = $queryParams['return_url'] ?? $_ENV['FRONTEND_URL'] ?? '/';
        $_SESSION['return_url'] = $returnUrl;

        // Get Keycloak authorization URL
        $authUrl = $this->keycloakService->getAuthorizationUrl($state);

        // Redirect user's browser to Keycloak
        return $response
            ->withHeader('Location', $authUrl)
            ->withStatus(302);
    }

    /**
     * Callback endpoint - Keycloak redirects here after login
     * 
     * URL: GET /auth/callback?code=xxx&state=yyy
     * 
     * Flow:
     * 1. User logs in on Keycloak
     * 2. Keycloak redirects back here with authorization code
     * 3. We verify the state (CSRF check)
     * 4. We exchange code for tokens
     * 5. We store tokens in session
     * 6. We redirect user to their original destination
     * 
     * @param Request $request
     * @param Response $response
     * @return Response Redirect or error response
     */
    public function callback(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        
        // STEP 1: Verify state parameter (CSRF protection)
        if (!$this->verifyState($params)) {
            return $this->jsonResponse($response, [
                'error' => 'Invalid state parameter',
                'message' => 'Possible CSRF attack detected'
            ], 400);
        }

        // STEP 2: Check for errors from Keycloak
        if (isset($params['error'])) {
            return $this->jsonResponse($response, [
                'error' => $params['error'],
                'error_description' => $params['error_description'] ?? 'Authentication failed',
            ], 400);
        }

        // STEP 3: Verify authorization code is present
        if (!isset($params['code'])) {
            return $this->jsonResponse($response, [
                'error' => 'Authorization code not provided',
            ], 400);
        }

        // STEP 4: Exchange authorization code for tokens
        $tokens = $this->keycloakService->exchangeCodeForTokens($params['code']);
        
        if (!$tokens) {
            return $this->jsonResponse($response, [
                'error' => 'Failed to obtain access token',
                'message' => 'Token exchange with Keycloak failed'
            ], 500);
        }

        // STEP 5: Store tokens in session
        $_SESSION['access_token'] = $tokens['access_token'];
        $_SESSION['refresh_token'] = $tokens['refresh_token'];
        $_SESSION['id_token'] = $tokens['id_token'] ?? null;
        $_SESSION['expires_at'] = time() + $tokens['expires_in'];
        $_SESSION['authenticated'] = true;

        // STEP 6: Get and store user information
        $userInfo = $this->keycloakService->getUserInfo($tokens['access_token']);
        if ($userInfo) {
            $_SESSION['user'] = [
                'id' => $userInfo['sub'],
                'username' => $userInfo['preferred_username'] ?? '',
                'email' => $userInfo['email'] ?? '',
                'name' => $userInfo['name'] ?? '',
                'first_name' => $userInfo['given_name'] ?? '',
                'last_name' => $userInfo['family_name'] ?? '',
                'email_verified' => $userInfo['email_verified'] ?? false,
            ];
        }

        // STEP 7: Store user roles
        $roles = $this->keycloakService->getUserRoles($tokens['access_token']);
        $_SESSION['roles'] = $roles;

        // STEP 8: Clean up temporary state
        unset($_SESSION['oauth_state']);

        // STEP 9: Redirect to original destination
        $returnUrl = $_SESSION['return_url'] ?? $_ENV['FRONTEND_URL'] ?? '/';
        unset($_SESSION['return_url']);

        return $response
            ->withHeader('Location', $returnUrl)
            ->withStatus(302);
    }

    /**
     * Logout endpoint - Logs out the user
     * 
     * URL: POST /auth/logout
     * 
     * Flow:
     * 1. Frontend calls this endpoint
     * 2. We clear the PHP session
     * 3. We return Keycloak logout URL
     * 4. Frontend redirects to Keycloak logout (single sign-out)
     * 
     * @param Request $request
     * @param Response $response
     * @return Response JSON with logout URL
     */
    public function logout(Request $request, Response $response): Response
    {
        // Get logout URL before destroying session
        $logoutUrl = $this->keycloakService->getLogoutUrl(
            $_ENV['FRONTEND_URL'] ?? 'http://localhost'
        );

        // Clear all session data
        $_SESSION = [];
        
        // Destroy the session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Return logout URL so frontend can redirect
        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Logged out successfully',
            'logout_url' => $logoutUrl,
        ]);
    }

    /**
     * Me endpoint - Returns current user information
     * 
     * URL: GET /auth/me
     * 
     * Returns info about the currently logged-in user
     * 
     * @param Request $request
     * @param Response $response
     * @return Response JSON with user info
     */
    public function me(Request $request, Response $response): Response
    {
        // Check if user is authenticated
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            return $this->jsonResponse($response, [
                'authenticated' => false,
            ], 401);
        }

        // Decode token to get fresh data
        $tokenData = $this->keycloakService->decodeToken($_SESSION['access_token']);

        // Return user information
        return $this->jsonResponse($response, [
            'authenticated' => true,
            'user' => $_SESSION['user'] ?? [],
            'roles' => $_SESSION['roles'] ?? [],
            'expires_at' => $_SESSION['expires_at'] ?? null,
            'token_valid_until' => $tokenData['exp'] ?? null,
        ]);
    }

    /**
     * Verify the OAuth state parameter (CSRF protection)
     * 
     * @param array $params Query parameters
     * @return bool True if state is valid
     */
    private function verifyState(array $params): bool
    {
        return isset($params['state']) 
            && isset($_SESSION['oauth_state']) 
            && $params['state'] === $_SESSION['oauth_state'];
    }

    /**
     * Helper: Create JSON response
     * 
     * @param Response $response
     * @param array $data
     * @param int $status
     * @return Response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}