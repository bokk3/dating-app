<?php
require_once __DIR__ . '/../src/models/Swipe.php';
require_once __DIR__ . '/../src/models/Match.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';

$user = AuthMiddleware::requireAuth();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $validation = Validator::validate($data, [
            'swiped_id' => 'required|integer',
            'is_like' => 'required'
        ]);

        if (!$validation['valid']) {
            http_response_code(400);
            echo json_encode(['errors' => $validation['errors']]);
            break;
        }
        
        $swipe = new Swipe();
        $swipe->swiper_id = $user->id;
        $swipe->swiped_id = $data['swiped_id'];
        $swipe->is_like = (bool)$data['is_like'];
        
        try {
            $database = Database::getInstance();
            $database->beginTransaction();
            
            if ($swipe->create()) {
                $isMatch = false;
                
                // Check if it's a match (both users liked each other)
                if ($swipe->is_like) {
                    $isMatch = $swipe->checkForMatch();
                    
                    if ($isMatch) {
                        $match = new Match();
                        $match->user1_id = min($user->id, $data['swiped_id']);
                        $match->user2_id = max($user->id, $data['swiped_id']);
                        $match->create();
                    }
                }
                
                $database->commit();
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'match' => $isMatch
                ]);
            } else {
                $database->rollback();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to record swipe']);
            }
        } catch (Exception $e) {
            $database->rollback();
            error_log("Swipe error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>