<?php
namespace App\Controller;

use App\Service\UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

class UserController {
    protected UserModel $userModel;

    public function __construct(ContainerInterface $container) {
        $this->userModel = new UserModel($container);
    }

    public function getProbes(Request $request, Response $response, array $args): Response {
        $userId = $args['id'];

        try {
            if ($this->userModel->userExists($userId)) {
                $data = $this->userModel->getProbesByUser($userId);
                $response->getBody()->write(json_encode([
                    'user_id' => $userId,
                    'probes' => $data
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $response->getBody()->write(json_encode([
                    'error' => 'User not found'
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
