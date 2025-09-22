<?php
require_once __DIR__ . '/../../config/database.php';

class HelpRequest {
    private $conn;
    private $table = 'help_requests';

    public $id;
    public $user_id;
    public $email;
    public $subject;
    public $message;
    public $status;
    public $priority;
    public $created_at;
    public $updated_at;
    public $assigned_admin_id;

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, email, subject, message, priority) 
                  VALUES (:user_id, :email, :subject, :message, :priority)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':subject', $this->subject);
        $stmt->bindParam(':message', $this->message);
        $stmt->bindParam(':priority', $this->priority);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET status = :status, assigned_admin_id = :assigned_admin_id, updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':assigned_admin_id', $this->assigned_admin_id);
        
        return $stmt->execute();
    }

    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $this->mapFromArray($stmt->fetch());
            return true;
        }
        return false;
    }

    public function getAll($page = 1, $limit = 20, $status = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($status)) {
            $whereClause .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $query = "SELECT 
                    hr.*,
                    u.email as user_email,
                    admin.email as admin_email
                  FROM " . $this->table . " hr
                  LEFT JOIN users u ON hr.user_id = u.id
                  LEFT JOIN users admin ON hr.assigned_admin_id = admin.id
                  $whereClause
                  ORDER BY 
                    CASE hr.priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        ELSE 4 
                    END,
                    hr.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getTotalCount($status = '') {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($status)) {
            $whereClause .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " $whereClause";
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetch()['total'];
    }

    private function mapFromArray($data) {
        $this->id = $data['id'];
        $this->user_id = $data['user_id'];
        $this->email = $data['email'];
        $this->subject = $data['subject'];
        $this->message = $data['message'];
        $this->status = $data['status'];
        $this->priority = $data['priority'];
        $this->created_at = $data['created_at'];
        $this->updated_at = $data['updated_at'];
        $this->assigned_admin_id = $data['assigned_admin_id'];
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status,
            'priority' => $this->priority,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'assigned_admin_id' => $this->assigned_admin_id
        ];
    }
}
?>