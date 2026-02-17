<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Service\NotificationModel;
use App\Service\AuthService;
use Ramsey\Uuid\Uuid;

class NotificationsController{
    private NotificationModel $notificationModel;
    private AuthService $authService;

    public function __construct(NotificationModel $notificationModel, AuthService $authService)
    {
        $this->notificationModel = $notificationModel;
        $this->authService = $authService;
    }

    public function markRead(Request $request, Response $response, array $args): Response {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));

        if (!$this->authService->isAuthenticated($token)) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(403);
        }

        $id = $args['id'];

        if (!Uuid::isValid($id)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid id format']));
            return $response->withHeader('Content-Type', 'application/json')
                            ->withStatus(400);
        }

        try {
            $result = $this->notificationModel->markRead($id);
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
