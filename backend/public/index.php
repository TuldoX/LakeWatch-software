<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Controller\ProbeController;
use Slim\Exception\HttpNotFoundException;

$container = new Container();
AppFactory::setContainer($container);

$container->set('db', function () {
    $host = getenv('POSTGRES_HOST');
    $db = getenv('POSTGRES_DB');
    $user = getenv('POSTGRES_USER');
    $password = getenv('POSTGRES_PASSWORD');

    $connection = pg_connect("host=$host dbname=$db user=$user password=$password");
    return $connection;
});

$app = AppFactory::create();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function () use ($app) {
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write("Rout not found.");
    return $response->withHeader('Content-Type', 'text/html')->withStatus(404);
});

$app->get('/api/data',[ProbeController::class, 'insertData']);

$app->run();