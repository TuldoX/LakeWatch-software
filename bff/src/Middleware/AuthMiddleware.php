<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\KeycloakService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * AuthMiddleware - Protects routes from unauthenticated access
 * 
 * This middleware runs BEFORE controller methods on protected routes
 * 
 * Flow:
 * 1. Check if user is authenticated
 * 2. Check if access token exists
 * 3. Check if token is valid/expired
 * 4. Try to refresh if expired
 * 5. Allow request if valid, reject if not
 */
class AuthMiddleware
{
    public function __construct(
        private KeycloakService $keycloakService
    ) {}

    /**
     * Middleware invocation
     * 
     * @param Request $request
     * @param RequestHandler $handler Next middleware/controller
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // STEP 1: Check if authenticated flag is set
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            return $this->unauthorizedResponse('Not authenticated. Please login.');
        }

        // STEP 2: Check if access token exists
        if (!isset($_SESSION['access_token'])) {
            return $this->unauthorizedResponse('No access token found.');
        }

        $accessToken = $_SESSION['access_token'];

        // STEP 3: Check if token is expired
        if ($this->keycloakService->isTokenExpired($accessToken)) {
            
            // STEP 4: Try to refresh the token
            if (isset($_SESSION['refresh_token'])) {
                $newTokens = $this->keycloakService->refreshAccessToken($_SESSION['refresh_token']);
                
                if ($newTokens) {
                    // Update session with new tokens
                    $_SESSION['access_token'] = $newTokens['access_token'];
                    $_SESSION['refresh_token'] = $newTokens['refresh_token'];
                    $_SESSION['expires_at'] = time() + $newTokens['expires_in'];
                    
                    // Log successful refresh
                    error_log("Token refreshed successfully");
                } else {
                    // Refresh failed - user must login again
                    $this->clearSession();
                    return $this->unauthorizedResponse('Token refresh failed. Please login again.');
                }
            } else {
                // No refresh token - user must login again
                $this->clearSession();
                return $this->unauthorizedResponse('Token expired. Please login again.');
            }
        }

        // STEP 5: Token is valid, proceed to controller
        // Add user info to request attributes (optional, for convenience)
        $request = $request
            ->withAttribute('user', $_SESSION['user'] ?? [])
            ->withAttribute('roles', $_SESSION['roles'] ?? [])
            ->withAttribute('access_token', $_SESSION['access_token']);

        return $handler->handle($request);
    }

    /**
     * Clear authentication session data
     */
    private function clearSession(): void
    {
        unset($_SESSION['authenticated']);
        unset($_SESSION['access_token']);
        unset($_SESSION['refresh_token']);
        unset($_SESSION['id_token']);
        unset($_SESSION['expires_at']);
        unset($_SESSION['user']);
        unset($_SESSION['roles']);
    }

    /**
     * Return 401 Unauthorized response
     * 
     * @param string $message Error message
     * @return Response
     */
    private function unauthorizedResponse(string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'error' => 'Unauthorized',
            'message' => $message,
            'login_url' => '/auth/login',
        ]));

        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}