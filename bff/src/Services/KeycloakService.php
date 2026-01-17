<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * KeycloakService - Handles all communication with Keycloak
 * 
 * This service:
 * 1. Generates login URLs
 * 2. Exchanges authorization codes for tokens
 * 3. Refreshes expired tokens
 * 4. Gets user information
 * 5. Validates tokens
 */
class KeycloakService
{
    private Client $httpClient;
    private string $authorizationUrl;
    private string $tokenUrl;
    private string $userInfoUrl;
    private string $logoutUrl;

    public function __construct(
        private string $baseUrl,        // http://keycloak:8080 (internal)
        private string $publicUrl,      // http://accounts.lakewatch.com (external)
        private string $realm,          // lakewatch
        private string $clientId,       // lakewatch-bff
        private string $clientSecret,   // secret from Keycloak
        private string $redirectUri     // http://localhost/auth/callback
    ) {
        // Create HTTP client for making requests to Keycloak
        $this->httpClient = new Client([
            'timeout' => 10,
            'verify' => false, // For development only! Use true in production
        ]);
        
        // Build Keycloak endpoint URLs
        $realmPath = "/realms/{$this->realm}/protocol/openid-connect";
        
        // Users' browsers go to publicUrl (external)
        $this->authorizationUrl = "{$this->publicUrl}{$realmPath}/auth";
        $this->logoutUrl = "{$this->publicUrl}{$realmPath}/logout";
        
        // BFF server talks to baseUrl (internal Docker network)
        $this->tokenUrl = "{$this->baseUrl}{$realmPath}/token";
        $this->userInfoUrl = "{$this->baseUrl}{$realmPath}/userinfo";
    }

    /**
     * Step 1: Generate the URL to redirect users to Keycloak login page
     * 
     * @param string $state CSRF protection token
     * @return string The full URL to redirect the user's browser
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',              // We want an authorization code
            'scope' => 'openid profile email',      // What info we want
            'state' => $state,                      // CSRF protection
        ];

        return $this->authorizationUrl . '?' . http_build_query($params);
    }

    /**
     * Step 2: Exchange authorization code for access tokens
     * 
     * After user logs in, Keycloak redirects back with a "code".
     * We exchange this code for actual access/refresh tokens.
     * 
     * @param string $code Authorization code from Keycloak
     * @return array|null Token response or null on failure
     */
    public function exchangeCodeForTokens(string $code): ?array
    {
        try {
            $response = $this->httpClient->post($this->tokenUrl, [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri' => $this->redirectUri,
                ],
            ]);

            $tokens = json_decode($response->getBody()->getContents(), true);
            
            /**
             * Response structure:
             * {
             *   "access_token": "eyJhbGc...",     // Used to access APIs
             *   "refresh_token": "eyJhbGc...",    // Used to get new access token
             *   "id_token": "eyJhbGc...",         // Contains user info
             *   "token_type": "Bearer",
             *   "expires_in": 900                 // Seconds until expiration
             * }
             */
            
            return $tokens;
            
        } catch (GuzzleException $e) {
            error_log("Keycloak token exchange failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Step 3: Refresh an expired access token
     * 
     * Access tokens expire quickly (5-15 min). Instead of logging user out,
     * we use the refresh token to get a new access token.
     * 
     * @param string $refreshToken The refresh token
     * @return array|null New tokens or null on failure
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        try {
            $response = $this->httpClient->post($this->tokenUrl, [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
            
        } catch (GuzzleException $e) {
            error_log("Keycloak token refresh failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get detailed user information from Keycloak
     * 
     * @param string $accessToken Valid access token
     * @return array|null User info or null on failure
     */
    public function getUserInfo(string $accessToken): ?array
    {
        try {
            $response = $this->httpClient->get($this->userInfoUrl, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                ],
            ]);

            $userInfo = json_decode($response->getBody()->getContents(), true);
            
            /**
             * Response structure:
             * {
             *   "sub": "user-uuid",
             *   "email": "user@example.com",
             *   "email_verified": true,
             *   "name": "John Doe",
             *   "preferred_username": "john",
             *   "given_name": "John",
             *   "family_name": "Doe"
             * }
             */
            
            return $userInfo;
            
        } catch (GuzzleException $e) {
            error_log("Keycloak userinfo failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Decode and validate a JWT token
     * 
     * JWT tokens have 3 parts: header.payload.signature
     * We decode the payload to check expiration and get user info.
     * 
     * @param string $token JWT token
     * @return array|null Decoded payload or null if invalid
     */
    public function decodeToken(string $token): ?array
    {
        try {
            // Split token into parts
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return null;
            }

            // Decode the payload (middle part)
            $payload = json_decode(base64_decode($parts[1]), true);
            
            if (!$payload) {
                return null;
            }
            
            // Check if token is expired
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return null; // Token expired
            }

            /**
             * Payload structure:
             * {
             *   "exp": 1234567890,              // Expiration timestamp
             *   "iat": 1234567000,              // Issued at timestamp
             *   "sub": "user-uuid",             // User ID
             *   "preferred_username": "john",
             *   "email": "john@example.com",
             *   "realm_access": {
             *     "roles": ["user", "admin"]    // User's roles
             *   }
             * }
             */

            return $payload;
            
        } catch (\Exception $e) {
            error_log("Token decode failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if an access token is expired or about to expire
     * 
     * @param string $accessToken The access token to check
     * @return bool True if expired or expiring soon
     */
    public function isTokenExpired(string $accessToken): bool
    {
        $decoded = $this->decodeToken($accessToken);
        
        if (!$decoded || !isset($decoded['exp'])) {
            return true; // Invalid token
        }

        // Consider token expired 30 seconds before actual expiration
        // This prevents race conditions where token expires during API call
        $bufferTime = 30;
        
        return ($decoded['exp'] - $bufferTime) < time();
    }

    /**
     * Generate the logout URL for Keycloak
     * 
     * @param string $postLogoutRedirectUri Where to redirect after logout
     * @return string Full logout URL
     */
    public function getLogoutUrl(string $postLogoutRedirectUri): string
    {
        $params = [
            'client_id' => $this->clientId,
            'post_logout_redirect_uri' => $postLogoutRedirectUri,
        ];

        return $this->logoutUrl . '?' . http_build_query($params);
    }

    /**
     * Extract user roles from token
     * 
     * @param string $accessToken The access token
     * @return array Array of role names
     */
    public function getUserRoles(string $accessToken): array
    {
        $decoded = $this->decodeToken($accessToken);
        
        if (!$decoded) {
            return [];
        }

        // Roles are stored in realm_access.roles
        return $decoded['realm_access']['roles'] ?? [];
    }

    /**
     * Check if user has a specific role
     * 
     * @param string $accessToken The access token
     * @param string $role Role to check
     * @return bool True if user has the role
     */
    public function hasRole(string $accessToken, string $role): bool
    {
        $roles = $this->getUserRoles($accessToken);
        return in_array($role, $roles);
    }
}