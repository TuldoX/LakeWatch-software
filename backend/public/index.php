<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Controller\ProbeController;
use App\Controller\UserController;
use App\Database\Database;
use App\Service\AuthService;
use App\Service\ProbeModel;
use App\Service\UserModel;
use Dotenv\Dotenv;
use DI\ContainerBuilder;
use Slim\Exception\HttpNotFoundException;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$containerBuilder = new ContainerBuilder();

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
     UserModel::class => function($c) {
        return new UserModel($c->get(Database::class));
    },
    ProbeController::class => function($c) {
        return new ProbeController(
            $c->get(ProbeModel::class),
            $c->get(AuthService::class)
        );
    },
    UserController::class => function($c){
        return new UserController(
            $c->get(UserModel::class),
            $c->get(AuthService::class)
        );
    }
]);

$container = $containerBuilder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://app.lakewatch.com')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->addBodyParsingMiddleware();

$app->post('/data', [ProbeController::class, 'postData']);
$app->get('/users/{id}/probes',[UserController::class,'getProbes']);

$app->map(['GET', 'POST'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
});

$app->run();