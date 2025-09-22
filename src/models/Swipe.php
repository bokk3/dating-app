<?php
require_once __DIR__ . '/../../config/database.php';

class Swipe {
    private $conn;
    private $table = 'swipes';

    public $id;
    public $swiper_id;
    public $swiped_id;
    public $is_like;
    public $created_at;

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (swiper_id, swiped_id, is_like) 
                  VALUES (:swiper_id, :swiped_id, :is_like)
                  ON DUPLICATE KEY UPDATE 
                  is_like = VALUES(is_like), created_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':swiper_id', $this->swiper_id);
        $stmt->bindParam(':swiped_id', $this->swiped_id);
        $stmt->bindParam(':is_like', $this->is_like, PDO::PARAM_BOOL);
        
        return $stmt->execute();
    }

    public function checkForMatch() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE swiper_id = :swiped_id AND swiped_id = :swiper_id AND is_like = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':swiper_id', $this->swiper_id);
        $stmt->bindParam(':swiped_id', $this->swiped_id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    public function getSwipeStats($userId, $days = 30) {
        $query = "SELECT 
                    COUNT(*) as total_swipes,
                    SUM(CASE WHEN is_like = 1 THEN 1 ELSE 0 END) as likes,
                    SUM(CASE WHEN is_like = 0 THEN 1 ELSE 0 END) as passes
                  FROM " . $this->table . " 
                  WHERE swiper_id = :user_id 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':days', $days);
        $stmt->execute();
        
        return $stmt->fetch();
    }
}
?>