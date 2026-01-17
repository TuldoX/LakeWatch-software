<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;

// Load Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configure PHP session settings from environment
ini_set('session.cookie_httponly', $_ENV['SESSION_HTTPONLY'] ?? '1');
ini_set('session.cookie_samesite', $_ENV['SESSION_SAMESITE'] ?? 'Lax');
ini_set('session.cookie_secure', $_ENV['SESSION_SECURE'] ?? '0');
ini_set('session.gc_maxlifetime', $_ENV['SESSION_LIFETIME'] ?? '7200');
ini_set('session.use_strict_mode', '1');

// Start PHP session
session_name($_ENV['SESSION_NAME'] ?? 'LAKEWATCH_SESSION');
session_start();

// Create Dependency Injection Container
$container = new Container();

// Register services in the container
$container->set(\App\Services\KeycloakService::class, function () {
    return new \App\Services\KeycloakService(
        baseUrl: $_ENV['KEYCLOAK_BASE_URL'],
        publicUrl: $_ENV['KEYCLOAK_PUBLIC_URL'],
        realm: $_ENV['KEYCLOAK_REALM'],
        clientId: $_ENV['KEYCLOAK_CLIENT_ID'],
        clientSecret: $_ENV['KEYCLOAK_CLIENT_SECRET'],
        redirectUri: $_ENV['KEYCLOAK_REDIRECT_URI']
    );
});

$container->set(\App\Services\ApiProxyService::class, function () use ($container) {
    return new \App\Services\ApiProxyService(
        baseUrl: $_ENV['API_BASE_URL'],
        keycloakService: $container->get(\App\Services\KeycloakService::class)
    );
});

$container->set(\App\Controllers\AuthController::class, function () use ($container) {
    return new \App\Controllers\AuthController(
        keycloakService: $container->get(\App\Services\KeycloakService::class)
    );
});

$container->set(\App\Controllers\ProxyController::class, function () use ($container) {
    return new \App\Controllers\ProxyController(
        apiProxyService: $container->get(\App\Services\ApiProxyService::class)
    );
});

$container->set(\App\Middleware\AuthMiddleware::class, function () use ($container) {
    return new \App\Middleware\AuthMiddleware(
        keycloakService: $container->get(\App\Services\KeycloakService::class)
    );
});

// Set container to Slim App
AppFactory::setContainer($container);

// Create Slim Application
$app = AppFactory::create();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(
    displayErrorDetails: $_ENV['APP_DEBUG'] === 'true',
    logErrors: true,
    logErrorDetails: true
);

// Add Routing Middleware
$app->addRoutingMiddleware();

// CORS Middleware (allow frontend to call BFF)
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', $_ENV['FRONTEND_URL'] ?? 'http://localhost')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

// Load routes
require __DIR__ . '/../src/Routes/web.php';

// Run the application
$app->run();