<?php
require_once __DIR__ . '/../src/utils/FileUploader.php';
require_once __DIR__ . '/../src/models/Profile.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';

$user = AuthMiddleware::requireAuth();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $uploadType = $_POST['type'] ?? 'profile';
        
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded']);
            break;
        }
        
        $validation = Validator::validate($_FILES['file'], [
            'file_type:' . implode(',', ALLOWED_IMAGE_TYPES),
            'file_size:' . MAX_FILE_SIZE
        ]);
        
        if (!$validation['valid']) {
            http_response_code(400);
            echo json_encode(['errors' => $validation['errors']]);
            break;
        }
        
        try {
            $uploader = new FileUploader();
            $result = $uploader->uploadImage($_FILES['file'], $uploadType, $user->id);
            
            if ($result['success']) {
                // Update profile picture if it's a profile upload
                if ($uploadType === 'profile') {
                    $profile = new Profile();
                    if ($profile->findByUserId($user->id)) {
                        $profile->profile_picture = $result['filename'];
                        $profile->update();
                    }
                }
                
                http_response_code(200);
                echo json_encode([
                    'message' => 'File uploaded successfully',
                    'url' => $result['url'],
                    'filename' => $result['filename']
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => $result['error']]);
            }
        } catch (Exception $e) {
            error_log("Upload error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Upload failed']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>