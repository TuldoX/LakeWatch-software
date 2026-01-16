<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Bff\Controller\DeviceController;

$app = AppFactory::create();

$devicecontroller = new DeviceController();

$devicecontroller->hello();
