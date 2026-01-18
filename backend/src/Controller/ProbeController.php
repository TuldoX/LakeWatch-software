<?php
namespace App\Controller;

use App\Service\AuthService;
use App\Service\ProbeModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProbeController {

    private ProbeModel $probeModel;
    private AuthService $authService;

    public function __construct(ProbeModel $probeModel,AuthService $authService)
    {
        $this->probeModel = $probeModel;
        $this->authService = $authService;
    }

    public function postData(Request $request, Response $response): Response
    {    
        $data = $request->getParsedBody();

        $id = $data['id'] ?? null;
        $batteryLife = $data['batteryLife'] ?? null;
        $temperature = $data['temperature'] ?? null;
        $tds = $data['tds'] ?? null;
        $oxygen = $data['oxygen'] ?? null;
        $ph = $data['ph'] ?? null;

        //token validation
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        
        if(!$this->probeModel->getToken($token,$id))
        {
            $response->getBody()->write(json_encode(['Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(403);
        }

        //probe exists
        if(!$this->probeModel->probeExists($id))
        {
            $response->getBody()->write(json_encode(['Probe does not exist']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(404);
        }

        //values validation
        $fields = [
            'batteryLife' => 'int',
            'temperature' => 'float',
            'tds' => 'int',
            'oxygen' => 'int',
            'ph' => 'float'
        ];

        $errors = [];

        foreach ($fields as $field => $type) {
            $value = $data[$field] ?? null;
            if (!isset($value)) {
                $errors[$field] = "$field is required";
                continue;
            }
            if ($type === 'int' && !ctype_digit(strval($value))) {
                $errors[$field] = "$field must be an integer";
            }
            if ($type === 'float' && !is_numeric($value)) {
                $errors[$field] = "$field must be a float";
            }
            if ($type === 'int') $data[$field] = (int)$value;
            if ($type === 'float') $data[$field] = (float)$value;
        }

        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(400);
        }

        //insert into database
        if(!$this->probeModel->insertData($id,$batteryLife,$temperature,$tds,$oxygen,$ph)){
            $response->getBody()->write(json_encode('Database operation falied'));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(500);
        }

        return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(200);
    }
}