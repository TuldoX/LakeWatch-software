<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use Slim\Exception\HttpNotFoundException;

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);
$container = $containerBuilder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function () use ($app) {
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write("Route not found.");
    return $response->withHeader('Content-Type', 'text/html')->withStatus(404);
});

$app->get('/users/{id}/probes', [\App\Controller\UserController::class, 'getProbes']);

$app->run();