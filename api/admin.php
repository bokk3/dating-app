<?php
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/HelpRequest.php';

$admin = AuthMiddleware::requireAdmin();

$pathParts = array_filter(explode('/', $_SERVER['REQUEST_URI']));
array_shift($pathParts); // remove 'api'
array_shift($pathParts); // remove 'admin'

$resource = $pathParts[0] ?? '';
$resourceId = $pathParts[1] ?? null;

switch ($resource) {
    case 'users':
        handleUsersAdmin($resourceId);
        break;
    case 'help-requests':
        handleHelpRequestsAdmin($resourceId);
        break;
    case 'stats':
        handleStatsAdmin();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Admin endpoint not found']);
}

function handleUsersAdmin($userId = null) {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($userId) {
                // Get specific user
                $user = new User();
                if ($user->findById($userId)) {
                    echo json_encode(['user' => $user->toArray(true)]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'User not found']);
                }
            } else {
                // Get all users with pagination
                $page = (int)($_GET['page'] ?? 1);
                $limit = min((int)($_GET['limit'] ?? 20), 100);
                $search = $_GET['search'] ?? '';
                
                $user = new User();
                $users = $user->getAllUsers($page, $limit, $search);
                $total = $user->getTotalUsers($search);
                
                echo json_encode([
                    'users' => $users,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
            }
            break;
        
        case 'PUT':
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID required']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Handle user updates (activate/deactivate, admin status, etc.)
            $user = new User();
            if ($user->findById($userId)) {
                // Update allowed fields
                if (isset($data['is_active'])) {
                    $query = "UPDATE users SET is_active = :is_active WHERE id = :id";
                    $stmt = Database::getInstance()->getConnection()->prepare($query);
                    $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_BOOL);
                    $stmt->bindParam(':id', $userId);
                    $stmt->execute();
                }
                
                if (isset($data['is_admin'])) {
                    $query = "UPDATE users SET is_admin = :is_admin WHERE id = :id";
                    $stmt = Database::getInstance()->getConnection()->prepare($query);
                    $stmt->bindParam(':is_admin', $data['is_admin'], PDO::PARAM_BOOL);
                    $stmt->bindParam(':id', $userId);
                    $stmt->execute();
                }
                
                echo json_encode(['message' => 'User updated successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
            }
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleHelpRequestsAdmin($requestId = null) {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $helpRequest = new HelpRequest();
            
            if ($requestId) {
                if ($helpRequest->findById($requestId)) {
                    echo json_encode(['request' => $helpRequest->toArray()]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Help request not found']);
                }
            } else {
                $page = (int)($_GET['page'] ?? 1);
                $limit = min((int)($_GET['limit'] ?? 20), 100);
                $status = $_GET['status'] ?? '';
                
                $requests = $helpRequest->getAll($page, $limit, $status);
                $total = $helpRequest->getTotalCount($status);
                
                echo json_encode([
                    'requests' => $requests,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
            }
            break;
        
        case 'PUT':
            if (!$requestId) {
                http_response_code(400);
                echo json_encode(['error' => 'Request ID required']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $helpRequest = new HelpRequest();
            if ($helpRequest->findById($requestId)) {
                if (isset($data['status'])) {
                    $helpRequest->status = $data['status'];
                }
                if (isset($data['assigned_admin_id'])) {
                    $helpRequest->assigned_admin_id = $data['assigned_admin_id'];
                }
                
                if ($helpRequest->update()) {
                    echo json_encode(['message' => 'Help request updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update help request']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Help request not found']);
            }
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleStatsAdmin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $conn = Database::getInstance()->getConnection();
    
    // Get various statistics
    $stats = [];
    
    // User stats
    $stmt = $conn->query("SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified_users,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as active_30_days
        FROM users");
    $stats['users'] = $stmt->fetch();
    
    // Profile stats
    $stmt = $conn->query("SELECT COUNT(*) as total_profiles FROM profiles");
    $stats['profiles'] = $stmt->fetch();
    
    // Match stats
    $stmt = $conn->query("SELECT 
        COUNT(*) as total_matches,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_matches
        FROM matches");
    $stats['matches'] = $stmt->fetch();
    
    // Message stats
    $stmt = $conn->query("SELECT 
        COUNT(*) as total_messages,
        COUNT(DISTINCT match_id) as active_conversations
        FROM messages 
        WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['messages'] = $stmt->fetch();
    
    // Swipe stats
    $stmt = $conn->query("SELECT 
        COUNT(*) as total_swipes,
        SUM(CASE WHEN is_like = 1 THEN 1 ELSE 0 END) as total_likes,
        ROUND(AVG(CASE WHEN is_like = 1 THEN 1 ELSE 0 END) * 100, 2) as like_rate
        FROM swipes 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['swipes'] = $stmt->fetch();
    
    echo json_encode(['stats' => $stats]);
}
?>