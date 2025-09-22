-- Migration 002: User Sessions and Security Tables
-- This migration adds enhanced session management and security features

CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_mobile BOOLEAN DEFAULT FALSE,
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_last_activity (last_activity)
);

-- Login attempts tracking for security
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success BOOLEAN NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_agent TEXT,
    failure_reason VARCHAR(255),
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at)
);

-- Account lockouts for failed login protection
CREATE TABLE account_lockouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    locked_until TIMESTAMP NOT NULL,
    attempt_count INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_locked_until (locked_until)
);

-- Rate limiting tracking
CREATE TABLE rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL, -- IP address or user ID
    action_type VARCHAR(50) NOT NULL, -- login, api_call, message_send, etc.
    hit_count INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_hit TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    blocked_until TIMESTAMP NULL,
    UNIQUE KEY unique_identifier_action (identifier, action_type),
    INDEX idx_identifier (identifier),
    INDEX idx_window_start (window_start),
    INDEX idx_blocked_until (blocked_until)
);

-- Password reset tokens with enhanced security
CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Email verification tokens (separate from users table for better tracking)
CREATE TABLE email_verification_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_email (email)
);