<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ApiProxyService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * ProxyController - Forwards requests to the backend API
 * 
 * All requests to /api/* are forwarded to the backend API
 * This controller acts as a proxy/gateway
 * 
 * Examples:
 * GET /api/users → GET http://api/users
 * POST /api/lakes → POST http://api/lakes
 * PUT /api/measurements/123 → PUT http://api/measurements/123
 */
class ProxyController
{
    public function __construct(
        private ApiProxyService $apiProxyService
    ) {}

    /**
     * Proxy any request to the backend API
     * 
     * This single method handles ALL HTTP methods (GET, POST, PUT, DELETE, PATCH)
     * 
     * Flow:
     * 1. Extract path from URL
     * 2. Extract query parameters
     * 3. Extract body data (for POST/PUT/PATCH)
     * 4. Forward to ApiProxyService
     * 5. Return response
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args Route arguments (contains 'path')
     * @return Response
     */
    public function proxy(Request $request, Response $response, array $args): Response
    {
        // STEP 1: Get the HTTP method (GET, POST, PUT, DELETE, PATCH)
        $method = $request->getMethod();
        
        // STEP 2: Extract the path
        // Example: /api/users/123 → path = "users/123"
        $path = '/' . ($args['path'] ?? '');
        
        // STEP 3: Add query parameters to path
        // Example: ?page=1&limit=10
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams)) {
            $path .= '?' . http_build_query($queryParams);
        }
        
        // STEP 4: Get request body (for POST/PUT/PATCH)
        $data = null;
        $contentType = $request->getHeaderLine('Content-Type');
        
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $body = $request->getBody()->getContents();
            
            // Parse JSON body
            if (str_contains($contentType, 'application/json')) {
                $data = json_decode($body, true);
            }
            // Parse form data
            elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
                parse_str($body, $data);
            }
        }
        
        // STEP 5: Forward request to API
        $result = $this->apiProxyService->request($method, $path, $data);
        
        // STEP 6: Build response
        return $this->buildResponse($response, $result);
    }

    /**
     * Build HTTP response from API result
     * 
     * @param Response $response
     * @param array $result Result from ApiProxyService
     * @return Response
     */
    private function buildResponse(Response $response, array $result): Response
    {
        $statusCode = $result['status'] ?? 500;
        
        // Success response
        if ($result['success']) {
            $response->getBody()->write(json_encode($result['data'] ?? []));
        }
        // Error response
        else {
            $response->getBody()->write(json_encode([
                'error' => $result['error'] ?? 'Unknown error',
                'status' => $statusCode,
            ]));
        }

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }
}