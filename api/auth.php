<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';

$controller = new AuthController();
$pathParts = array_filter(explode('/', $_SERVER['REQUEST_URI']));

// Remove 'api' and 'auth' parts
array_shift($pathParts); // remove 'api'
array_shift($pathParts); // remove 'auth'

$action = $pathParts[0] ?? '';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        switch ($action) {
            case 'register':
                $controller->register();
                break;
            case 'login':
                $controller->login();
                break;
            case 'logout':
                $controller->logout();
                break;
            case 'forgot-password':
                $controller->forgotPassword();
                break;
            case 'reset-password':
                $controller->resetPassword();
                break;
            case 'change-password':
                $controller->changePassword();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Auth endpoint not found']);
        }
        break;
    
    case 'GET':
        switch ($action) {
            case 'verify-email':
                $controller->verifyEmail();
                break;
            case 'status':
                $controller->getAuthStatus();
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Auth endpoint not found']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>