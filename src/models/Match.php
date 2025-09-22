<?php
require_once __DIR__ . '/../../config/database.php';

class Match {
    private $conn;
    private $table = 'matches';

    public $id;
    public $user1_id;
    public $user2_id;
    public $created_at;
    public $is_active;

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (user1_id, user2_id) 
                  VALUES (:user1_id, :user2_id)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user1_id', $this->user1_id);
        $stmt->bindParam(':user2_id', $this->user2_id);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getUserMatches($userId) {
        $query = "SELECT 
                    m.*,
                    p.first_name,
                    p.last_name,
                    p.profile_picture,
                    p.bio,
                    msg.message as last_message,
                    msg.sent_at as last_message_time,
                    CASE 
                        WHEN m.user1_id = :user_id THEN m.user2_id 
                        ELSE m.user1_id 
                    END as match_user_id
                  FROM " . $this->table . " m
                  JOIN profiles p ON (
                    (m.user1_id = :user_id AND p.user_id = m.user2_id) OR
                    (m.user2_id = :user_id AND p.user_id = m.user1_id)
                  )
                  LEFT JOIN (
                    SELECT 
                        match_id,
                        message,
                        sent_at,
                        ROW_NUMBER() OVER (PARTITION BY match_id ORDER BY sent_at DESC) as rn
                    FROM messages
                  ) msg ON m.id = msg.match_id AND msg.rn = 1
                  WHERE (m.user1_id = :user_id OR m.user2_id = :user_id) 
                    AND m.is_active = 1
                  ORDER BY COALESCE(msg.sent_at, m.created_at) DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function findByUsers($user1Id, $user2Id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE ((user1_id = :user1_id AND user2_id = :user2_id) OR 
                         (user1_id = :user2_id AND user2_id = :user1_id))
                    AND is_active = 1
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user1_id', $user1Id);
        $stmt->bindParam(':user2_id', $user2Id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $data = $stmt->fetch();
            $this->id = $data['id'];
            $this->user1_id = $data['user1_id'];
            $this->user2_id = $data['user2_id'];
            $this->created_at = $data['created_at'];
            $this->is_active = $data['is_active'];
            return true;
        }
        return false;
    }

    public function unmatch() {
        $query = "UPDATE " . $this->table . " SET is_active = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
?>