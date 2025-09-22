<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/middleware/RateLimitMiddleware.php';

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse the request
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string and API prefix
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$pathParts = array_filter(explode('/', $path));

// Rate limiting
$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
RateLimitMiddleware::handleRateLimit($clientIp);

// Basic routing
try {
    switch ($pathParts[0] ?? '') {
        case 'auth':
            require_once __DIR__ . '/auth.php';
            break;
        case 'profile':
            require_once __DIR__ . '/profile.php';
            break;
        case 'discover':
            require_once __DIR__ . '/discover.php';
            break;
        case 'swipe':
            require_once __DIR__ . '/swipe.php';
            break;
        case 'matches':
            require_once __DIR__ . '/matches.php';
            break;
        case 'chat':
            require_once __DIR__ . '/chat.php';
            break;
        case 'upload':
            require_once __DIR__ . '/upload.php';
            break;
        case 'admin':
            require_once __DIR__ . '/admin.php';
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>