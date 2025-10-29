<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use App\Controller\UserController;
use Slim\Exception\HttpNotFoundException;

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    'db' => function () {
        $host = getenv('POSTGRES_HOST');
        $db = getenv('POSTGRES_DB');
        $user = getenv('POSTGRES_USER');
        $password = getenv('POSTGRES_PASSWORD');

        $connection = @pg_connect("host=$host dbname=$db user=$user password=$password");

        if (!$connection) {
            throw new Exception("Could not connect to PostgreSQL database.");
        }

        return $connection;
    },
]);

$container = $containerBuilder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function () use ($app) {
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write("Route not found.");
    return $response->withHeader('Content-Type', 'text/html')->withStatus(404);
});

$app->get('/users/{id}/probes', [UserController::class, 'getProbes']);

$app->run();