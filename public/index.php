<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';

// Get the request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Parse the path
$path = parse_url($requestUri, PHP_URL_PATH);

// Basic routing
switch ($path) {
    case '/':
    case '/login':
        if (AuthMiddleware::isAuthenticated()) {
            $user = AuthMiddleware::getCurrentUser();
            if ($user && $user->is_admin) {
                header('Location: /admin');
            } else {
                header('Location: /app');
            }
            exit;
        }
        include __DIR__ . '/login.php';
        break;
        
    case '/admin':
        $user = AuthMiddleware::getCurrentUser();
        if (!$user || !$user->is_admin) {
            header('Location: /login');
            exit;
        }
        include __DIR__ . '/../admin/index.html';
        break;
        
    case '/app':
        if (!AuthMiddleware::isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        include __DIR__ . '/app.html'; // Your main dating app
        break;
        
    case '/register':
        include __DIR__ . '/register.php';
        break;
        
    case '/verify-email':
        include __DIR__ . '/verify-email.php';
        break;
        
    default:
        // Check if it's an API route
        if (strpos($path, '/api/') === 0) {
            include __DIR__ . '/../api/index.php';
        } else {
            http_response_code(404);
            echo "Page not found";
        }
}
?>