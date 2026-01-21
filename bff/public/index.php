<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Controllers\AuthController;
use App\Controllers\ApiController;
use App\Middleware\AuthMiddleware;
use App\Services\KeycloakService;
use App\Services\TokenService;
use App\Services\ApiClient;

// Harden session cookie
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Lax');

session_start();

// Create DI Container
$container = new Container();

// Register services in the container
$container->set(KeycloakService::class, function () {
    return new KeycloakService();
});

$container->set(TokenService::class, function ($container) {
    return new TokenService();
});

$container->set(ApiClient::class, function () {
    return new ApiClient();
});

// Register controllers
$container->set(AuthController::class, function ($container) {
    return new AuthController();
});

$container->set(ApiController::class, function ($container) {
    return new ApiController();
});

// Create Slim app with container
AppFactory::setContainer($container);
$app = AppFactory::create();

// Define routes
$app->get('/auth/login', [AuthController::class, 'login']);
$app->get('/auth/callback', [AuthController::class, 'callback']);
$app->get('/auth/me', [AuthController::class, 'me']);

$app->any('/api/{path:.*}', [ApiController::class, 'proxy'])
    ->add(new AuthMiddleware());

// Run app
$app->run();