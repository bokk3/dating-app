<?php
require_once __DIR__ . '/../src/models/Profile.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';

$user = AuthMiddleware::requireAuth();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $profile = new Profile();
        
        if ($profile->findByUserId($user->id)) {
            http_response_code(200);
            echo json_encode(['profile' => $profile->toArray()]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Profile not found']);
        }
        break;
    
    case 'POST':
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $validation = Validator::validate($data, [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'date_of_birth' => 'required|date|age_range',
            'gender' => 'required|in:male,female,other',
            'interested_in' => 'required|in:male,female,both',
            'bio' => 'max:1000',
            'location' => 'max:255',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
            'max_distance' => 'integer|min:1|max:100'
        ]);

        if (!$validation['valid']) {
            http_response_code(400);
            echo json_encode(['errors' => $validation['errors']]);
            break;
        }
        
        $profile = new Profile();
        $isUpdate = $profile->findByUserId($user->id);
        
        // Map data to profile object
        $profile->user_id = $user->id;
        $profile->first_name = $data['first_name'];
        $profile->last_name = $data['last_name'];
        $profile->date_of_birth = $data['date_of_birth'];
        $profile->gender = $data['gender'];
        $profile->interested_in = $data['interested_in'];
        $profile->bio = $data['bio'] ?? null;
        $profile->location = $data['location'] ?? null;
        $profile->latitude = $data['latitude'] ?? null;
        $profile->longitude = $data['longitude'] ?? null;
        $profile->max_distance = $data['max_distance'] ?? DEFAULT_DISTANCE;
        
        // Keep existing profile picture if not updating it
        if (!isset($data['profile_picture'])) {
            // profile_picture will keep its current value for updates
        } else {
            $profile->profile_picture = $data['profile_picture'];
        }
        
        try {
            if ($isUpdate) {
                $success = $profile->update();
                $message = 'Profile updated successfully';
            } else {
                $success = $profile->create();
                $message = 'Profile created successfully';
            }
            
            if ($success) {
                http_response_code($isUpdate ? 200 : 201);
                echo json_encode([
                    'message' => $message,
                    'profile' => $profile->toArray()
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save profile']);
            }
        } catch (Exception $e) {
            error_log("Profile save error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
        break;
    
    case 'DELETE':
        $profile = new Profile();
        
        if ($profile->findByUserId($user->id)) {
            // Instead of deleting, we deactivate the user account
            $userModel = new User();
            if ($userModel->findById($user->id) && $userModel->deactivate()) {
                AuthMiddleware::logout();
                http_response_code(200);
                echo json_encode(['message' => 'Account deactivated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to deactivate account']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Profile not found']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>