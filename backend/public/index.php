<?php
// Simple test endpoint
header('Content-Type: application/json');

// Check for Bearer token
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No authorization header']);
    exit;
}

// Return success
echo json_encode([
    'message' => 'API endpoint works!',
    'timestamp' => date('Y-m-d H:i:s'),
    'auth_header' => $headers['Authorization']
]);