<?php
require_once __DIR__ . '/../../config/database.php';

class UserSession {
    private $conn;
    private $table = 'user_sessions';

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function create($userId, $duration = 86400) {
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $duration);
        
        $query = "INSERT INTO " . $this->table . " 
                  (id, user_id, ip_address, user_agent, expires_at) 
                  VALUES (:id, :user_id, :ip_address, :user_agent, :expires_at)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':id' => $sessionId,
            ':user_id' => $userId,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ':expires_at' => $expiresAt
        ]);
        
        return $sessionId;
    }

    public function validate($sessionId) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id = :id AND expires_at > NOW() AND is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $sessionId]);
        
        if ($session = $stmt->fetch()) {
            // Update last activity
            $this->updateActivity($sessionId);
            return $session;
        }
        
        return false;
    }

    public function invalidate($sessionId) {
        $query = "UPDATE " . $this->table . " SET is_active = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $sessionId]);
    }

    public function invalidateAllUserSessions($userId) {
        $query = "UPDATE " . $this->table . " SET is_active = 0 WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':user_id' => $userId]);
    }

    private function updateActivity($sessionId) {
        $query = "UPDATE " . $this->table . " SET last_activity = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $sessionId]);
    }

    public function cleanupExpiredSessions() {
        $query = "DELETE FROM " . $this->table . " WHERE expires_at < NOW()";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }
}
?>