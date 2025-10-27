<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Database\Database;
use PDO;

class AuthController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    public function login(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $name = $data['name'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($name) || empty($password)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Name and password are required'
            ]));
            return $response->withStatus(400);
        }
        
        $stmt = $this->db->prepare("SELECT id, name, password FROM users WHERE name = ?");
        $stmt->execute([$name]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'User not found'
            ]));
            return $response->withStatus(401);
        }
        
        if (!password_verify($password, $user['password'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Wrong password'
            ]));
            return $response->withStatus(401);
        }
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name']
            ]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}