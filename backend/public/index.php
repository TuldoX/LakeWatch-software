<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Controller\ProbeController;
use App\Database\Database;
use App\Service\AuthService;
use App\Service\ProbeModel;
use Dotenv\Dotenv;
use DI\ContainerBuilder;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

//build DI container
$containerBuilder = new ContainerBuilder();

//add database to DI
$containerBuilder->addDefinitions([
    Database::class => function() {
        return new Database();
    },
    AuthService::class => function() {
        return new AuthService();
    },
     ProbeModel::class => function($c) {
        return new ProbeModel($c->get(Database::class));
    },
    ProbeController::class => function($c) {
        return new ProbeController(
            $c->get(ProbeModel::class),
            $c->get(AuthService::class)
        );
    }
]);

$container = $containerBuilder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$app->post('/data', [ProbeController::class, 'postData']);

$app->run();