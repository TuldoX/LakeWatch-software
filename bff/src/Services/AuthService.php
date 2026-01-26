<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Exception;

class AuthService
{
    private string $jwksUrl;
    private const CACHE_FILE = __DIR__ . '/../../var/jwks.json';
    private const CACHE_TTL = 86400;

    public function __construct()
    {
        $this->jwksUrl = getenv('KEYCLOAK_JWKS_URL') 
            ?: 'http://keycloak:8080/realms/lakewatch/protocol/openid-connect/certs';
    }

    public function isAuthenticated(string $token): bool
    {
        try {
            $jwks = $this->getJwks();
            JWT::decode($token, JWK::parseKeySet($jwks));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getJwks(): array
    {
        if ($this->isCacheValid()) {
            $cachedContent = @file_get_contents(self::CACHE_FILE);
            if ($cachedContent !== false) {
                $jwks = json_decode($cachedContent, true);
                if (isset($jwks['keys'])) {
                    return $jwks;
                }
            }
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);

        $jwksContent = @file_get_contents($this->jwksUrl, false, $context);
        
        if ($jwksContent === false) {
            if (file_exists(self::CACHE_FILE)) {
                $cachedContent = @file_get_contents(self::CACHE_FILE);
                if ($cachedContent !== false) {
                    $jwks = json_decode($cachedContent, true);
                    if (isset($jwks['keys'])) {
                        return $jwks;
                    }
                }
            }
            throw new Exception('Unable to fetch JWKS');
        }

        $jwks = json_decode($jwksContent, true);

        if (!isset($jwks['keys'])) {
            throw new Exception('Invalid JWKS response');
        }

        if (!is_dir(dirname(self::CACHE_FILE))) {
            @mkdir(dirname(self::CACHE_FILE), 0775, true);
        }

        @file_put_contents(self::CACHE_FILE, json_encode($jwks));

        return $jwks;
    }

    private function isCacheValid(): bool
    {
        return file_exists(self::CACHE_FILE)
            && (time() - filemtime(self::CACHE_FILE)) < self::CACHE_TTL;
    }

    public function decodeToken(string $token): object
    {
        $jwks = $this->getJwks();
        return JWT::decode($token, JWK::parseKeySet($jwks));
    }
}