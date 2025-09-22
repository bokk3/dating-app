<?php
class FileUploader {
    private $uploadPath;
    
    public function __construct() {
        $this->uploadPath = UPLOAD_PATH;
        
        // Create upload directories if they don't exist
        $this->createDirectories();
    }
    
    public function uploadImage($file, $type = 'profile', $userId = null) {
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error: ' . $this->getUploadError($file['error'])];
        }
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }
        
        // Validate file size
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'error' => 'File too large'];
        }
        
        // Generate unique filename
        $extension = $this->getFileExtension($mimeType);
        $filename = $this->generateFilename($type, $userId, $extension);
        $fullPath = $this->uploadPath . $type . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            // Resize/optimize image
            $this->processImage($fullPath, $type);
            
            return [
                'success' => true,
                'filename' => $filename,
                'url' => '/uploads/' . $type . '/' . $filename
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to save file'];
    }
    
    private function createDirectories() {
        $dirs = ['profiles', 'photos', 'temp'];
        
        foreach ($dirs as $dir) {
            $path = $this->uploadPath . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
    
    private function generateFilename($type, $userId, $extension) {
        $prefix = $type === 'profile' ? 'profile' : 'photo';
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        
        return $prefix . '_' . $userId . '_' . $timestamp . '_' . $random . '.' . $extension;
    }
    
    private function getFileExtension($mimeType) {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];
        
        return $extensions[$mimeType] ?? 'jpg';
    }
    
    private function processImage($filePath, $type) {
        // Get image info
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) return;
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Define max dimensions based on type
        $maxWidth = $type === 'profile' ? 800 : 1200;
        $maxHeight = $type === 'profile' ? 800 : 1200;
        
        // Skip if image is already small enough
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return;
        }
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        // Create image resource
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($filePath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($filePath);
                break;
            default:
                return;
        }
        
        if (!$source) return;
        
        // Create new image
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefill($resized, 0, 0, $transparent);
        }
        
        // Resize
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save resized image
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($resized, $filePath, 85);
                break;
            case 'image/png':
                imagepng($resized, $filePath, 6);
                break;
            case 'image/webp':
                imagewebp($resized, $filePath, 85);
                break;
        }
        
        // Clean up
        imagedestroy($source);
        imagedestroy($resized);
    }
    
    private function getUploadError($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        
        return $errors[$errorCode] ?? 'Unknown upload error';
    }
    
    public function deleteFile($filename, $type = 'profile') {
        $filePath = $this->uploadPath . $type . '/' . $filename;
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }
}
?>