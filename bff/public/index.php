<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use App\Controllers\AuthController;
use App\Controllers\ApiController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\KeycloakService;
use App\Services\TokenService;
use App\Services\ApiClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$sessionSecure = getenv('SESSION_SECURE') === 'true' ? 1 : 0;

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $sessionSecure);
ini_set('session.cookie_samesite', 'Lax');

session_start();

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([

]);

$container = $containerBuilder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();

$app->get('/auth/login', [AuthController::class, 'login']);
$app->get('/auth/callback', [AuthController::class, 'callback']);
$app->get('/auth/me', [AuthController::class, 'me']);
$app->any('/api/{path:.*}', [ApiController::class, 'proxy'])
    ->add(new AuthMiddleware());

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    $response->getBody()->write(json_encode('Route not found'));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
});

$app->run();