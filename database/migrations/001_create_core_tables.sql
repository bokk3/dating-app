CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_admin BOOLEAN DEFAULT FALSE,
    INDEX idx_email (email),
    INDEX idx_verification_token (email_verification_token),
    INDEX idx_reset_token (reset_token)
);

CREATE TABLE profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    interested_in ENUM('male', 'female', 'both') NOT NULL,
    bio TEXT,
    location VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    max_distance INT DEFAULT 50,
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_location (latitude, longitude),
    INDEX idx_gender_interest (gender, interested_in)
);

CREATE TABLE profile_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    photo_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    order_position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    INDEX idx_profile_id (profile_id)
);

CREATE TABLE swipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    swiper_id INT NOT NULL,
    swiped_id INT NOT NULL,
    is_like BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (swiper_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (swiped_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_swipe (swiper_id, swiped_id),
    INDEX idx_swiper_id (swiper_id),
    INDEX idx_swiped_id (swiped_id)
);

CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_match (LEAST(user1_id, user2_id), GREATEST(user1_id, user2_id)),
    INDEX idx_user1_id (user1_id),
    INDEX idx_user2_id (user2_id)
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    message_type ENUM('text', 'image', 'gif') DEFAULT 'text',
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_match_id (match_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_sent_at (sent_at)
);

CREATE TABLE help_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    email VARCHAR(255),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    assigned_admin_id INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);