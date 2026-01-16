<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Controllers\DeviceController;

$app = AppFactory::create();

echo("Hello");