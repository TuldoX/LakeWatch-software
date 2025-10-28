<?php

namespace App\Service;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;

class ProbeModel {
    protected $db;

    public function __construct(ContainerInterface $container){
        $this->db = $container->get('db');
    }
}