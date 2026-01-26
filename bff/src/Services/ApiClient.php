<?php

namespace App\Services;

use Psr\Http\Message\ServerRequestInterface;

class ApiClient
{
    private TokenService $tokens;

    public function __construct()
    {
        $this->tokens = new TokenService();
    }

    public function forward(string $method,string $url,ServerRequestInterface $request): ?array {
        $accessToken = $this->tokens->getValidAccessToken();

        if (!$accessToken) return null;

        $headers = [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ];

        $body = (string) $request->getBody();

        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'ignore_errors' => true
            ]
        ];

        $context = stream_context_create($options);
        $responseBody = file_get_contents($url, false, $context);

        $statusLine = $http_response_header[0] ?? 'HTTP/1.1 500';
        preg_match('{HTTP/\S*\s(\d{3})}', $statusLine, $match);
        $status = (int)($match[1] ?? 500);

        if ($status === 401) {
            unset($_SESSION['tokens']['expires_at']);
            $accessToken = $this->tokens->getValidAccessToken();
            if (!$accessToken) return null;

            $headers[0] = "Authorization: Bearer {$accessToken}";
            $options['http']['header'] = implode("\r\n", $headers);
            $context = stream_context_create($options);
            $responseBody = file_get_contents($url, false, $context);

            $statusLine = $http_response_header[0] ?? 'HTTP/1.1 500';
            preg_match('{HTTP/\S*\s(\d{3})}', $statusLine, $match);
            $status = (int)($match[1] ?? 500);
        }

        return [
            'status' => $status,
            'body' => $responseBody
        ];
    }
}
