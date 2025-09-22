<?php
require_once __DIR__ . '/../../config/database.php';

class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $email;
    public $password_hash;
    public $email_verified;
    public $email_verification_token;
    public $reset_token;
    public $reset_token_expires;
    public $created_at;
    public $updated_at;
    public $last_login;
    public $login_attempts;
    public $locked_until;
    public $is_active;
    public $is_admin;

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (email, password_hash, email_verification_token) 
                  VALUES (:email, :password_hash, :email_verification_token)";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password with pepper
        $pepperedPassword = $this->password_hash . PASSWORD_PEPPER;
        $hashedPassword = password_hash($pepperedPassword, PASSWORD_ARGON2ID);
        
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $hashedPassword);
        $stmt->bindParam(':email_verification_token', $this->email_verification_token);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $this->mapFromArray($stmt->fetch());
            return true;
        }
        return false;
    }

    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $this->mapFromArray($stmt->fetch());
            return true;
        }
        return false;
    }

    public function authenticate($email, $password) {
        if ($this->findByEmail($email)) {
            $pepperedPassword = $password . PASSWORD_PEPPER;
            
            if (password_verify($pepperedPassword, $this->password_hash) && 
                $this->email_verified && $this->is_active) {
                $this->updateLastLogin();
                return true;
            }
        }
        return false;
    }

    public function isLocked() {
        if ($this->locked_until && strtotime($this->locked_until) > time()) {
            return true;
        }
        return false;
    }

    public function incrementLoginAttempts() {
        $this->login_attempts++;
        
        $lockUntil = null;
        if ($this->login_attempts >= 5) {
            $lockUntil = date('Y-m-d H:i:s', time() + 900); // 15 minutes
        }
        
        $query = "UPDATE " . $this->table . " 
                  SET login_attempts = :attempts, locked_until = :locked_until
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':attempts', $this->login_attempts);
        $stmt->bindParam(':locked_until', $lockUntil);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
    }

    public function resetLoginAttempts() {
        $query = "UPDATE " . $this->table . " 
                  SET login_attempts = 0, locked_until = NULL
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $this->login_attempts = 0;
        $this->locked_until = null;
    }

    public function verifyEmail($token) {
        $query = "UPDATE " . $this->table . " 
                  SET email_verified = 1, email_verification_token = NULL, updated_at = NOW()
                  WHERE email_verification_token = :token AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            // Find the user to get their ID for logging
            $findQuery = "SELECT id FROM " . $this->table . " WHERE email_verification_token IS NULL AND email_verified = 1 ORDER BY updated_at DESC LIMIT 1";
            $findStmt = $this->conn->prepare($findQuery);
            $findStmt->execute();
            if ($user = $findStmt->fetch()) {
                $this->id = $user['id'];
            }
            return true;
        }
        return false;
    }

    private function updateLastLogin() {
        $query = "UPDATE " . $this->table . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
    }

    private function mapFromArray($data) {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->password_hash = $data['password_hash'];
        $this->email_verified = $data['email_verified'];
        $this->email_verification_token = $data['email_verification_token'];
        $this->reset_token = $data['reset_token'];
        $this->reset_token_expires = $data['reset_token_expires'];
        $this->created_at = $data['created_at'];
        $this->updated_at = $data['updated_at'];
        $this->last_login = $data['last_login'];
        $this->login_attempts = $data['login_attempts'];
        $this->locked_until = $data['locked_until'];
        $this->is_active = $data['is_active'];
        $this->is_admin = $data['is_admin'];
    }

    public function toArray($includePrivate = false) {
        $data = [
            'id' => $this->id,
            'email' => $this->email,
            'email_verified' => (bool)$this->email_verified,
            'created_at' => $this->created_at,
            'last_login' => $this->last_login,
            'is_active' => (bool)$this->is_active,
            'is_admin' => (bool)$this->is_admin
        ];

        if ($includePrivate) {
            $data['updated_at'] = $this->updated_at;
            $data['login_attempts'] = $this->login_attempts;
            $data['locked_until'] = $this->locked_until;
        }

        return $data;
    }
}
?>