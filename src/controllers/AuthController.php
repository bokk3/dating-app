<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserSession.php';
require_once __DIR__ . '/../utils/EmailService.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class AuthController {
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 900; // 15 minutes
    
    // New method for form-based login (returns array instead of JSON)
    public function loginWithCredentials($email, $password, $rememberMe = false) {
        $user = new User();
        
        if (!$user->findByEmail($email)) {
            $this->logActivity('login_failed', null, ['email' => $email, 'reason' => 'user_not_found']);
            return ['success' => false, 'error' => 'Invalid email or password'];
        }

        // Check if account is locked
        if ($user->isLocked()) {
            return [
                'success' => false, 
                'error' => 'Account temporarily locked due to too many failed attempts. Try again later.'
            ];
        }

        if ($user->authenticate($email, $password)) {
            // Reset login attempts on successful login
            $user->resetLoginAttempts();
            
            // Create session
            $sessionDuration = $rememberMe ? 30 * 24 * 60 * 60 : 24 * 60 * 60; // 30 days or 24 hours
                
            $session = new UserSession();
            $sessionId = $session->create($user->id, $sessionDuration);
            
            // Set session cookie
            setcookie('session_id', $sessionId, [
                'expires' => time() + $sessionDuration,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['is_admin'] = $user->is_admin;
            $_SESSION['session_id'] = $sessionId;
            
            $this->logActivity('login_success', $user->id);
            
            return [
                'success' => true,
                'user' => $user->toArray()
            ];
        } else {
            // Increment login attempts
            $user->incrementLoginAttempts();
            
            $this->logActivity('login_failed', $user->id, ['reason' => 'invalid_password']);
            
            return ['success' => false, 'error' => 'Invalid email or password'];
        }
    }
    
    // API method for JSON responses (existing method)
    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $validation = Validator::validate($data, [
            'email' => 'required|email',
            'password' => 'required',
            'remember_me' => 'boolean'
        ]);

        if (!$validation['valid']) {
            return $this->jsonResponse(['errors' => $validation['errors']], 400);
        }

        $result = $this->loginWithCredentials(
            $data['email'], 
            $data['password'], 
            $data['remember_me'] ?? false
        );
        
        if ($result['success']) {
            return $this->jsonResponse([
                'message' => 'Login successful',
                'user' => $result['user'],
                'redirect' => $result['user']['is_admin'] ? '/admin' : '/app'
            ]);
        } else {
            return $this->jsonResponse(['error' => $result['error']], 401);
        }
    }

    public function register() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $validation = Validator::validate($data, [
            'email' => 'required|email',
            'password' => 'required|min:8|password_strength',
            'terms_accepted' => 'required|boolean'
        ]);

        if (!$validation['valid']) {
            return $this->jsonResponse(['errors' => $validation['errors']], 400);
        }

        if (!$data['terms_accepted']) {
            return $this->jsonResponse(['error' => 'You must accept the terms and conditions'], 400);
        }

        $user = new User();
        
        // Check if user already exists
        if ($user->findByEmail($data['email'])) {
            return $this->jsonResponse(['error' => 'Email already registered'], 409);
        }

        // Create new user
        $user->email = $data['email'];
        $user->password_hash = $data['password']; // Will be hashed in model
        $user->email_verification_token = bin2hex(random_bytes(32));

        try {
            if ($user->create()) {
                // Send verification email
                $emailService = new EmailService();
                $emailService->sendVerificationEmail($user->email, $user->email_verification_token);
                
                // Log registration
                $this->logActivity('user_registered', $user->id);
                
                return $this->jsonResponse([
                    'message' => 'Registration successful. Please check your email to verify your account.',
                    'user_id' => $user->id
                ], 201);
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
        }

        return $this->jsonResponse(['error' => 'Registration failed'], 500);
    }

    public function verifyEmail() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            header('Location: /login?error=invalid_token');
            exit;
        }

        $user = new User();
        
        if ($user->verifyEmail($token)) {
            $this->logActivity('email_verified', $user->id);
            header('Location: /login?verified=1');
            exit;
        }

        header('Location: /login?error=invalid_token');
        exit;
    }

    public function logout() {
        $sessionId = $_SESSION['session_id'] ?? null;
        
        if ($sessionId) {
            $session = new UserSession();
            $session->invalidate($sessionId);
        }
        
        $this->logActivity('logout', $_SESSION['user_id'] ?? null);
        
        // Clear cookie
        setcookie('session_id', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true
        ]);
        
        session_destroy();
        header('Location: /login');
        exit;
    }

    public function getAuthStatus() {
        $user = AuthMiddleware::getCurrentUser();
        
        if ($user) {
            return $this->jsonResponse([
                'authenticated' => true,
                'user' => $user->toArray()
            ]);
        }

        return $this->jsonResponse(['authenticated' => false]);
    }

    private function logActivity($action, $userId = null, $metadata = []) {
        try {
            $conn = Database::getInstance()->getConnection();
            
            $stmt = $conn->prepare("
                INSERT INTO admin_logs (admin_id, action, target_type, target_id, new_values, ip_address, user_agent)
                VALUES (:admin_id, :action, 'user', :target_id, :metadata, :ip, :user_agent)
            ");
            
            $stmt->execute([
                ':admin_id' => $_SESSION['user_id'] ?? $userId,
                ':action' => $action,
                ':target_id' => $userId,
                ':metadata' => json_encode($metadata),
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }

    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>