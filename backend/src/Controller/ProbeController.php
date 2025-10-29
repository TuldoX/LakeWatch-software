<?php

namespace App\Controller;

use App\Service\ProbeModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProbeController {
    protected ProbeModel $probeModel;

    public function __construct(ProbeModel $probeModel) {
        $this->probeModel = $probeModel;
    }

    public function getData(Request $request, Response $response, array $args){
        $probe_id = $args['id'];
        $hours = $args['hours'];

        try {
            if($this->probeModel->probeExists($probe_id)) {
                $data = $this->probeModel->getProbeValues($probe_id,$hours);
                $response->getBody()->write(json_encode([
                    'probe_id' => $probe_id,
                    'values'  => $data
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $response->getBody()->write(json_encode([
                    'error' => 'Probe not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Server error: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}