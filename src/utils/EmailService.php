<?php
require_once __DIR__ . '/../../config/config.php';

class EmailService {
    private $mailer;
    
    public function __construct() {
        // Configure PHPMailer or similar
        // This is a basic implementation
    }
    
    public function sendVerificationEmail($email, $token) {
        $verificationLink = APP_URL . "/verify-email?token=" . $token;
        
        $subject = "Verify Your Email - " . APP_NAME;
        $message = "
            <html>
            <head>
                <title>Email Verification</title>
            </head>
            <body>
                <h2>Welcome to " . APP_NAME . "!</h2>
                <p>Please click the link below to verify your email address:</p>
                <a href='{$verificationLink}' style='background: #ff6b6b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email</a>
                <p>If you didn't create an account, please ignore this email.</p>
            </body>
            </html>
        ";
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    public function sendPasswordResetEmail($email, $token) {
        $resetLink = APP_URL . "/reset-password?token=" . $token;
        
        $subject = "Password Reset - " . APP_NAME;
        $message = "
            <html>
            <head>
                <title>Password Reset</title>
            </head>
            <body>
                <h2>Password Reset Request</h2>
                <p>Click the link below to reset your password:</p>
                <a href='{$resetLink}' style='background: #ff6b6b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this, please ignore this email.</p>
            </body>
            </html>
        ";
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    private function sendEmail($to, $subject, $message) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . APP_NAME . ' <noreply@' . parse_url(APP_URL, PHP_URL_HOST) . '>',
            'Reply-To: noreply@' . parse_url(APP_URL, PHP_URL_HOST),
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
}
?>