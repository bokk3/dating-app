<?php
require_once __DIR__ . '/../src/controllers/AuthController.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $authController = new AuthController();
        $result = $authController->loginWithCredentials($email, $password, $remember_me);
        
        if ($result['success']) {
            // Redirect based on user type
            if ($result['user']['is_admin']) {
                header('Location: /admin');
            } else {
                header('Location: /app');
            }
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

// Check for URL parameters
if (isset($_GET['verified']) && $_GET['verified'] == '1') {
    $success = 'Email verified successfully! You can now log in.';
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_token':
            $error = 'Invalid or expired verification token.';
            break;
        case 'session_expired':
            $error = 'Your session has expired. Please log in again.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dating App</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1><i class="heart-icon">💕</i> Dating App</h1>
                <p>Find your perfect match</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/login" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required 
                        autocomplete="email"
                        placeholder="Enter your email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >
                </div>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember_me" value="1">
                        <span class="checkmark"></span>
                        Remember me for 30 days
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    Sign In
                </button>
            </form>
            
            <div class="auth-links">
                <a href="/register">Don't have an account? Sign up</a>
                <a href="/forgot-password">Forgot your password?</a>
            </div>
            
            <div class="demo-accounts">
                <h4>Demo Accounts:</h4>
                <p><strong>Admin:</strong> admin@datingapp.com / admin123</p>
                <p><strong>User:</strong> user@datingapp.com / user123</p>
            </div>
        </div>
    </div>
</body>
</html>