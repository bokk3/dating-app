<?php
require_once __DIR__ . '/../../config/database.php';

class Message {
    private $conn;
    private $table = 'messages';

    public $id;
    public $match_id;
    public $sender_id;
    public $message;
    public $sent_at;
    public $read_at;
    public $message_type;

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (match_id, sender_id, message, message_type) 
                  VALUES (:match_id, :sender_id, :message, :message_type)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':match_id', $this->match_id);
        $stmt->bindParam(':sender_id', $this->sender_id);
        $stmt->bindParam(':message', $this->message);
        $stmt->bindParam(':message_type', $this->message_type);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getChatMessages($matchId, $limit = 50, $offset = 0) {
        $query = "SELECT 
                    m.*,
                    p.first_name as sender_name,
                    p.profile_picture as sender_picture
                  FROM " . $this->table . " m
                  JOIN profiles p ON m.sender_id = p.user_id
                  WHERE m.match_id = :match_id
                  ORDER BY m.sent_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':match_id', $matchId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return array_reverse($stmt->fetchAll()); // Reverse to show oldest first
    }

    public function markMessagesAsRead($matchId, $userId) {
        $query = "UPDATE " . $this->table . " 
                  SET read_at = NOW() 
                  WHERE match_id = :match_id 
                    AND sender_id != :user_id 
                    AND read_at IS NULL";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':match_id', $matchId);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }

    public function getUnreadCount($userId) {
        $query = "SELECT COUNT(*) as unread_count
                  FROM " . $this->table . " m
                  JOIN matches mt ON m.match_id = mt.id
                  WHERE (mt.user1_id = :user_id OR mt.user2_id = :user_id)
                    AND m.sender_id != :user_id
                    AND m.read_at IS NULL
                    AND mt.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch()['unread_count'];
    }
}
?>