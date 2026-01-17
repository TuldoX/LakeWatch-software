<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * ApiProxyService - Forwards requests to the backend API
 * 
 * This service:
 * 1. Takes requests from frontend
 * 2. Adds authentication (access token)
 * 3. Forwards to backend API
 * 4. Returns API response to frontend
 * 5. Handles token refresh automatically
 */
class ApiProxyService
{
    private Client $httpClient;

    public function __construct(
        private string $baseUrl,                    // http://api
        private KeycloakService $keycloakService
    ) {
        // Create HTTP client for API calls
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'http_errors' => false, // Don't throw exceptions on 4xx/5xx
        ]);
    }

    /**
     * Forward a request to the backend API
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH)
     * @param string $path API endpoint path (e.g., /users, /lakes/123)
     * @param array|null $data Request body data (for POST/PUT/PATCH)
     * @param array $headers Additional headers
     * @return array Standardized response
     */
    public function request(
        string $method,
        string $path,
        ?array $data = null,
        array $headers = []
    ): array {
        // Get a valid access token (refreshing if needed)
        $accessToken = $this->getValidAccessToken();
        
        if (!$accessToken) {
            return $this->errorResponse(401, 'Not authenticated');
        }

        // Build request options
        $options = [
            'headers' => array_merge([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ], $headers),
        ];

        // Add body data for POST/PUT/PATCH requests
        if ($data !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $options['json'] = $data;
        }

        try {
            // Make the API call
            $response = $this->httpClient->request($method, $path, $options);
            
            // Get response body
            $body = $response->getBody()->getContents();
            $responseData = [];
            
            if (!empty($body)) {
                $responseData = json_decode($body, true) ?? ['raw_body' => $body];
            }
            
            // Return standardized response
            return $this->successResponse(
                status: $response->getStatusCode(),
                data: $responseData,
                headers: $response->getHeaders()
            );
            
        } catch (RequestException $e) {
            // Handle request errors (network errors, HTTP errors, etc.)
            error_log("API request failed: {$method} {$path} - " . $e->getMessage());
            
            $statusCode = 500;
            $message = $e->getMessage();

            // If API returned an error response, extract it
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();
                $errorData = json_decode($body, true);
                $message = $errorData['message'] ?? $errorData['error'] ?? $response->getReasonPhrase() ?? $message;
            }

            return $this->errorResponse($statusCode, $message);
        } catch (\Exception $e) {
            // Handle any other exceptions
            error_log("Unexpected error in API request: {$method} {$path} - " . $e->getMessage());
            return $this->errorResponse(500, 'Internal server error: ' . $e->getMessage());
        }
    }

    /**
     * Get a valid access token, refreshing if necessary
     * 
     * This method:
     * 1. Checks if we have an access token in session
     * 2. Checks if it's expired
     * 3. If expired, uses refresh token to get new one
     * 4. Updates session with new tokens
     * 
     * @return string|null Valid access token or null
     */
    private function getValidAccessToken(): ?string
    {
        // Check if user is logged in
        if (!isset($_SESSION['access_token'])) {
            return null;
        }

        $accessToken = $_SESSION['access_token'];

        // Check if token is expired
        if ($this->keycloakService->isTokenExpired($accessToken)) {
            
            // Try to refresh the token
            if (!isset($_SESSION['refresh_token'])) {
                return null; // No refresh token, user must login again
            }

            $newTokens = $this->keycloakService->refreshAccessToken($_SESSION['refresh_token']);
            
            if ($newTokens) {
                // Update session with new tokens
                $_SESSION['access_token'] = $newTokens['access_token'];
                $_SESSION['refresh_token'] = $newTokens['refresh_token'];
                $_SESSION['expires_at'] = time() + $newTokens['expires_in'];
                
                return $newTokens['access_token'];
            }

            // Refresh failed, clear session
            $this->clearAuthSession();
            return null;
        }

        return $accessToken;
    }

    /**
     * Clear authentication data from session
     */
    private function clearAuthSession(): void
    {
        unset($_SESSION['access_token']);
        unset($_SESSION['refresh_token']);
        unset($_SESSION['id_token']);
        unset($_SESSION['expires_at']);
        unset($_SESSION['user']);
        unset($_SESSION['authenticated']);
    }

    /**
     * Create a standardized success response
     */
    private function successResponse(int $status, $data, array $headers = []): array
    {
        return [
            'success' => true,
            'status' => $status,
            'data' => $data,
            'headers' => $headers,
        ];
    }

    /**
     * Create a standardized error response
     */
    private function errorResponse(int $status, string $message): array
    {
        return [
            'success' => false,
            'status' => $status,
            'error' => $message,
        ];
    }

    /**
     * Convenience method: GET request
     */
    public function get(string $path, array $headers = []): array
    {
        return $this->request('GET', $path, null, $headers);
    }

    /**
     * Convenience method: POST request
     */
    public function post(string $path, array $data, array $headers = []): array
    {
        return $this->request('POST', $path, $data, $headers);
    }

    /**
     * Convenience method: PUT request
     */
    public function put(string $path, array $data, array $headers = []): array
    {
        return $this->request('PUT', $path, $data, $headers);
    }

    /**
     * Convenience method: PATCH request
     */
    public function patch(string $path, array $data, array $headers = []): array
    {
        return $this->request('PATCH', $path, $data, $headers);
    }

    /**
     * Convenience method: DELETE request
     */
    public function delete(string $path, array $headers = []): array
    {
        return $this->request('DELETE', $path, null, $headers);
    }
}