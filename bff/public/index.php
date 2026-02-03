<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use App\Controller\AuthController;
use App\Controller\ApiController;
use Dotenv\Dotenv;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$sessionSecure = ($_ENV['SESSION_SECURE'] ?? 'false') === 'true' ? 1 : 0;
$sessionName = $_ENV['SESSION_NAME'] ?? 'LAKEWATCH_SESSION';
$sessionLifetime = (int)($_ENV['SESSION_LIFETIME'] ?? 7200);

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $sessionSecure);
ini_set('session.cookie_samesite', $_ENV['SESSION_SAMESITE'] ?? 'Lax');
ini_set('session.gc_maxlifetime', $sessionLifetime);
ini_set('session.name', $sessionName);

session_start();

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    AuthController::class => function() {
        return new AuthController();
    },
    ApiController::class => function() {
        return new ApiController();
    }
]);

$container = $containerBuilder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();

$errorMiddleware = $app->addErrorMiddleware(
    $_ENV['APP_DEBUG'] === 'true',
    true,
    true
);

$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    
    return $response
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->get('/auth/login', [AuthController::class, 'login']);
$app->get('/auth/callback', [AuthController::class, 'callback']);
$app->get('/auth/me', [AuthController::class, 'me']);
$app->any('/api/{path:.*}', [ApiController::class, 'proxy']);

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'error' => 'Route not found'
    ]));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(404);
});

$app->run();