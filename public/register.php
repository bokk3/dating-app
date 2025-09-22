<?php
/**
 * User Registration Page
 * 
 * Handles user sign-up with email verification
 */

session_start();

// Define application constants
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('SRC_PATH', ROOT_PATH . '/src');

// Load configuration
$config = require_once CONFIG_PATH . '/config.php';

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include required classes
    require_once SRC_PATH . '/utils/Validator.php';
    require_once SRC_PATH . '/controllers/AuthController.php';
    
    $validator = new Validator();
    $authController = new AuthController();
    
    // Validate input
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $birthDate = $_POST['birth_date'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $csrfToken = $_POST['_token'] ?? '';
    
    // CSRF validation
    if (!hash_equals($_SESSION['_token'] ?? '', $csrfToken)) {
        $errors[] = 'Invalid security token. Please try again.';
    }
    
    // Validate required fields
    if (empty($email)) $errors[] = 'Email is required.';
    if (empty($password)) $errors[] = 'Password is required.';
    if (empty($confirmPassword)) $errors[] = 'Please confirm your password.';
    if (empty($firstName)) $errors[] = 'First name is required.';
    if (empty($lastName)) $errors[] = 'Last name is required.';
    if (empty($birthDate)) $errors[] = 'Birth date is required.';
    if (empty($gender)) $errors[] = 'Gender is required.';
    
    // Validate email format
    if (!empty($email) && !$validator->validateEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // Validate password strength
    if (!empty($password) && !$validator->validatePassword($password)) {
        $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character.';
    }
    
    // Check password confirmation
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    
    // Validate age (must be 18+)
    if (!empty($birthDate)) {
        $birthDateTime = new DateTime($birthDate);
        $today = new DateTime();
        $age = $today->diff($birthDateTime)->y;
        
        if ($age < 18) {
            $errors[] = 'You must be at least 18 years old to register.';
        }
    }
    
    // If no errors, attempt registration
    if (empty($errors)) {
        $result = $authController->register([
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => $birthDate,
            'gender' => $gender
        ]);
        
        if ($result['success']) {
            $success = true;
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['_token'])) {
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo $config['app']['name']; ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Join thousands of people finding love</p>
            </div>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <h2>Registration Successful!</h2>
                    <p>Please check your email for a verification link to activate your account.</p>
                    <a href="/login.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                        <?php foreach ($errors as $error): ?>
                            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="auth-form">
                    <input type="hidden" name="_token" value="<?php echo $_SESSION['_token']; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <div class="password-requirements">
                            Must contain: 8+ characters, uppercase, lowercase, number, special character
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="birth_date">Birth Date</label>
                            <input type="date" id="birth_date" name="birth_date" 
                                   value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="non_binary" <?php echo ($_POST['gender'] ?? '') === 'non_binary' ? 'selected' : ''; ?>>Non-binary</option>
                                <option value="other" <?php echo ($_POST['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group terms">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms" required>
                            I agree to the <a href="/terms.php" target="_blank">Terms of Service</a> 
                            and <a href="/privacy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">Create Account</button>
                </form>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="/login.php">Sign in here</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/js/app.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const requirements = document.querySelector('.password-requirements');
            
            const hasLength = password.length >= 8;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            
            const allMet = hasLength && hasUpper && hasLower && hasNumber && hasSpecial;
            
            requirements.style.color = allMet ? 'green' : 'red';
        });
        
        // Confirm password validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>