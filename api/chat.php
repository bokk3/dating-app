<?php
require_once __DIR__ . '/../src/models/Message.php';
require_once __DIR__ . '/../src/models/Match.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';

$user = AuthMiddleware::requireAuth();

$pathParts = array_filter(explode('/', $_SERVER['REQUEST_URI']));
array_shift($pathParts); // remove 'api'
array_shift($pathParts); // remove 'chat'

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $otherUserId = $pathParts[0] ?? null;
        
        if (!$otherUserId || !is_numeric($otherUserId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid user ID']);
            break;
        }
        
        // Verify users are matched
        $match = new Match();
        if (!$match->findByUsers($user->id, $otherUserId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Not matched with this user']);
            break;
        }
        
        $message = new Message();
        $messages = $message->getChatMessages($match->id);
        
        // Mark messages as read
        $message->markMessagesAsRead($match->id, $user->id);
        
        http_response_code(200);
        echo json_encode(['messages' => $messages]);
        break;
    
    case 'POST':
        $action = $pathParts[0] ?? '';
        
        if ($action === 'send') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validation = Validator::validate($data, [
                'recipient_id' => 'required|integer',
                'message' => 'required|max:1000'
            ]);

            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode(['errors' => $validation['errors']]);
                break;
            }
            
            // Verify users are matched
            $match = new Match();
            if (!$match->findByUsers($user->id, $data['recipient_id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Not matched with this user']);
                break;
            }
            
            $message = new Message();
            $message->match_id = $match->id;
            $message->sender_id = $user->id;
            $message->message = trim($data['message']);
            $message->message_type = $data['message_type'] ?? 'text';
            
            if ($message->create()) {
                http_response_code(201);
                echo json_encode([
                    'message' => 'Message sent successfully',
                    'message_id' => $message->id
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to send message']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Chat endpoint not found']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>