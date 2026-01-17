<?php

declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\AuthController;
use App\Controllers\ProxyController;
use App\Middleware\AuthMiddleware;

/**
 * Routes file - Maps URLs to Controller methods
 * 
 * Pattern: $app->METHOD('/path', [ControllerClass::class, 'methodName'])
 */

// Health check endpoint (no authentication needed)
$app->get('/', function ($request, $response) {
    $data = [
        'service' => 'LakeWatch BFF',
        'status' => 'running',
        'version' => '1.0.0',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Authentication Routes (Public - no middleware needed)
$app->group('/auth', function (RouteCollectorProxy $group) {
    
    // GET /auth/login - Redirects to Keycloak login page
    $group->get('/login', [AuthController::class, 'login']);
    
    // GET /auth/callback - Keycloak redirects here after login
    $group->get('/callback', [AuthController::class, 'callback']);
    
    // POST /auth/logout - Logs out the user
    $group->post('/logout', [AuthController::class, 'logout']);
    
    // GET /auth/me - Get current user info (protected)
    $group->get('/me', [AuthController::class, 'me'])
        ->add(AuthMiddleware::class);
});

// API Proxy Routes (Protected - requires authentication)
$app->group('/api', function (RouteCollectorProxy $group) {
    
    // Catch-all route that forwards everything to the backend API
    // Examples:
    // GET /api/users -> forwards to http://api/users
    // POST /api/lakes/123 -> forwards to http://api/lakes/123
    // PUT /api/measurements -> forwards to http://api/measurements
    
    $group->any('/{path:.*}', [ProxyController::class, 'proxy']);
    
})->add(AuthMiddleware::class); // This middleware runs before all routes in this group