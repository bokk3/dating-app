-- Migration 003: Admin Activity and Logging Tables
-- This migration adds comprehensive admin activity tracking and system logging

CREATE TABLE admin_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL, -- user_activate, user_deactivate, ticket_resolve, etc.
    target_type VARCHAR(50) NOT NULL, -- user, ticket, payment, etc.
    target_id INT,
    description TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON, -- Additional context data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_target_type (target_type),
    INDEX idx_created_at (created_at)
);

-- System-wide activity log for all user actions
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(128),
    action_type VARCHAR(50) NOT NULL, -- login, logout, swipe, message, profile_update, etc.
    entity_type VARCHAR(50), -- profile, match, message, etc.
    entity_id INT,
    description VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (session_id) REFERENCES user_sessions(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_entity_type (entity_type),
    INDEX idx_created_at (created_at)
);

-- Help request responses from admins
CREATE TABLE help_request_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    help_request_id INT NOT NULL,
    admin_id INT NOT NULL,
    response_text TEXT NOT NULL,
    is_internal_note BOOLEAN DEFAULT FALSE, -- Internal admin notes vs user-visible responses
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (help_request_id) REFERENCES help_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_help_request_id (help_request_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_created_at (created_at)
);

-- Content moderation actions and reports
CREATE TABLE content_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT,
    reported_user_id INT NOT NULL,
    report_type ENUM('inappropriate_photos', 'fake_profile', 'harassment', 'spam', 'other') NOT NULL,
    description TEXT,
    evidence_urls JSON, -- Screenshots or other evidence
    status ENUM('pending', 'investigating', 'resolved', 'dismissed') DEFAULT 'pending',
    admin_id INT, -- Admin who handled the report
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reporter_id (reporter_id),
    INDEX idx_reported_user_id (reported_user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- User blocks and restrictions
CREATE TABLE user_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_block (blocker_id, blocked_id),
    INDEX idx_blocker_id (blocker_id),
    INDEX idx_blocked_id (blocked_id)
);

-- System notifications and announcements
CREATE TABLE system_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    target_audience ENUM('all', 'admins', 'premium', 'specific') DEFAULT 'all',
    target_user_ids JSON, -- For specific user targeting
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_type (type),
    INDEX idx_target_audience (target_audience),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at)
);

-- User notification reads tracking
CREATE TABLE notification_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES system_notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_read (notification_id, user_id),
    INDEX idx_notification_id (notification_id),
    INDEX idx_user_id (user_id)
);