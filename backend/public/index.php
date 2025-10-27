<?php

use App\Controller\AuthController;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

$authController = new AuthController();

$app->post('/api/login',[$authController,'login']);

$app->run();