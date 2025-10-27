<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Add error handling
$app->addErrorMiddleware(true, true, true);

// Test route - root
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write("Slim is working! Try /api/hello/yourname");
    return $response;
});

// API route
$app->get('/api/hello/{name}', function (Request $request, Response $response, $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name! API is working.");
    return $response;
});

// Remove the /api/ route to avoid conflicts
$app->run();