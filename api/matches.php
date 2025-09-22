<?php
require_once __DIR__ . '/../src/models/Match.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';

$user = AuthMiddleware::requireAuth();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $match = new Match();
        $matches = $match->getUserMatches($user->id);
        
        $formattedMatches = array_map(function($m) {
            return [
                'match_id' => $m['id'],
                'user_id' => $m['match_user_id'],
                'first_name' => $m['first_name'],
                'last_name' => $m['last_name'],
                'profile_picture' => $m['profile_picture'] ? 
                    '/uploads/profiles/' . $m['profile_picture'] : 
                    '/images/default-avatar.png',
                'bio' => $m['bio'],
                'last_message' => $m['last_message'],
                'last_message_time' => $m['last_message_time'],
                'matched_at' => $m['created_at']
            ];
        }, $matches);
        
        http_response_code(200);
        echo json_encode(['matches' => $formattedMatches]);
        break;
    
    case 'DELETE':
        $pathParts = array_filter(explode('/', $_SERVER['REQUEST_URI']));
        $matchId = end($pathParts);
        
        if (!is_numeric($matchId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid match ID']);
            break;
        }
        
        $match = new Match();
        $match->id = $matchId;
        
        // Verify user is part of this match
        $matches = $match->getUserMatches($user->id);
        $userMatch = array_filter($matches, function($m) use ($matchId) {
            return $m['id'] == $matchId;
        });
        
        if (empty($userMatch)) {
            http_response_code(403);
            echo json_encode(['error' => 'Not authorized to unmatch']);
            break;
        }
        
        if ($match->unmatch()) {
            http_response_code(200);
            echo json_encode(['message' => 'Successfully unmatched']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to unmatch']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>