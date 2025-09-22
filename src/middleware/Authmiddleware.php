<?php
require_once __DIR__ . '/../models/User.php';

class AuthMiddleware {
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        return self::getCurrentUser();
    }

    public static function requireAdmin() {
        $user = self::requireAuth();
        if (!$user->is_admin) {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit;
        }
        return $user;
    }

    public static function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }

        $user = new User();
        if ($user->findById($_SESSION['user_id'])) {
            return $user;
        }

        // Invalid session, clear it
        self::logout();
        return null;
    }

    public static function logout() {
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    public static function refreshSession($userId) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();
    }
}
?>