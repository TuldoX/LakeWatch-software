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

    public function getData(Request $request, Response $response, array $args) : Response{
        $probe_id = intval($args['id']);
        $hours = intval($args['hours']);

        if(!is_int($hours) || !is_int($probe_id)){
            $response->getBody()->write(json_encode([
                    'error' => 'Invalid data sent'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

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

    public function postData(Request $request, Response $response, array $args) : Response {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (empty($data)) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid data sent'
            ]));
            return $response->withHeader('Content-Type','application/json')->withStatus(400);
        }

        $requiredFields = ['probe_id', 'btr_life', 'temp', 'tds','o2','ph'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $response->getBody()->write(json_encode([
                'error' => 'Invalid data sent'
                ]));
                return $response->withHeader('Content-Type','application/json')->withStatus(400);
            }
        }

        if (!filter_var($data['probe_id'], FILTER_VALIDATE_INT)) return $this->error($response, 'id must be an integer');
        if (!filter_var($data['btr_life'], FILTER_VALIDATE_INT)) return $this->error($response, 'battery_life must be an integer');
        if (!is_numeric($data['temp'])) return $this->error($response, 'temp must be a float');
        if (!filter_var($data['tds'], FILTER_VALIDATE_INT)) return $this->error($response, 'tds must be an integer');
        if (!filter_var($data['o2'], FILTER_VALIDATE_INT)) return $this->error($response, 'o2 must be an integer');
        if (!is_numeric($data['ph'])) return $this->error($response, 'ph must be a float');
        

        try {
            $insertedRow = $this->probeModel->postData(
                (string)$data['probe_id'],
                (string)$data['btr_life'],
                (string)$data['temp'],
                (string)$data['tds'],
                (string)$data['o2'],
                (string)$data['ph']
            );

            $response->getBody()->write(json_encode([
                'status' => 'ok',
                'data' => $insertedRow[0] ?? null
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (\Exception $e) {
            return $this->error($response, 'Database error: ' . $e->getMessage());
        }
    }

    private function error(Response $response, string $message): Response {
        $response->getBody()->write(json_encode(['error' => 'Invalid data sent: ' . $message]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
}