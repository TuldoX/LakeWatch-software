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

        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        
        try {
            $tokenValid = $this->probeModel->getToken($token, $id);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(500);
        }
        
        if(!$tokenValid)
        {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(403);
        }

        try {
            $exists = $this->probeModel->probeExists($id);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(500);
        }
        
        if(!$exists)
        {
            $response->getBody()->write(json_encode(['error' => 'Probe does not exist']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(404);
        }

        $fields = [
            'batteryLife' => 'int',
            'temperature' => 'float',
            'tds' => 'int',
            'oxygen' => 'float',
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

        if(!$this->probeModel->insertData($id,$batteryLife,$temperature,$tds,$oxygen,$ph)){
            $response->getBody()->write(json_encode(['error' => 'Database operation failed']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(500);
        }

        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(200);
    }

    public function getData(Request $request,Response $response,array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));

        if (!$this->authService->isAuthenticated($token)) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(403);
        }

        $id = $args['id'];
        
        try {
            $exists = $this->probeModel->probeExists($id);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(500);
        }
        
        if(!$exists)
        {
            $response->getBody()->write(json_encode(['error' => 'Probe does not exist']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(404);
        }

        $queryParams = $request->getQueryParams();
        $hoursParam = $queryParams['hours'] ?? null;

    
        if ($hoursParam === null || $hoursParam === '') {
            $hours = 24;
        } else {
            // integer
            if (!filter_var($hoursParam, FILTER_VALIDATE_INT)) {
                $response->getBody()->write(json_encode([
                    'error' => 'Invalid hours parameter',
                    'message' => 'Hours must be an integer between 1 and 720'
                ]));
                return $response->withHeader('Content-Type', 'application/json')
                                ->withStatus(400);
            }
            
            $hours = (int)$hoursParam;
            
            // Validate range
            if ($hours < 1 || $hours > 720) {
                $response->getBody()->write(json_encode([
                    'error' => 'Invalid hours range',
                    'message' => 'Hours must be between 1 and 720 (30 days)'
                ]));
                return $response->withHeader('Content-Type', 'application/json')
                                ->withStatus(400);
            }
        }

        try {
            $result = $this->probeModel->getData($id,$hours);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(500);
        }
        
        $response->getBody()->write(json_encode($result));

        return $response->withHeader('Content-Type', 'application/json')
                       ->withStatus(200);
    }
}