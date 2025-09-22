-- Migration 004: Payment System Tables
-- This migration adds comprehensive payment processing and subscription management

CREATE TABLE subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price_monthly DECIMAL(10, 2) NOT NULL,
    price_yearly DECIMAL(10, 2),
    features JSON, -- Array of features included in the plan
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_sort_order (sort_order)
);

-- User subscriptions
CREATE TABLE user_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('active', 'cancelled', 'expired', 'pending', 'failed') NOT NULL,
    billing_cycle ENUM('monthly', 'yearly') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    starts_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    cancelled_at TIMESTAMP NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    payment_method_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
);

-- Payment methods (credit cards, PayPal, etc.)
CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('credit_card', 'debit_card', 'paypal', 'apple_pay', 'google_pay') NOT NULL,
    provider VARCHAR(50) NOT NULL, -- stripe, paypal, square, etc.
    provider_payment_method_id VARCHAR(255), -- External payment method ID
    last_four VARCHAR(4), -- Last 4 digits for cards
    brand VARCHAR(50), -- visa, mastercard, amex, etc.
    exp_month INT,
    exp_year INT,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_provider (provider),
    INDEX idx_is_default (is_default)
);

-- Transaction history
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_id INT,
    payment_method_id INT,
    type ENUM('subscription', 'one_time', 'refund', 'credit') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    description VARCHAR(255),
    provider VARCHAR(50) NOT NULL, -- stripe, paypal, etc.
    provider_transaction_id VARCHAR(255), -- External transaction ID
    provider_fee DECIMAL(10, 2), -- Payment processor fee
    net_amount DECIMAL(10, 2), -- Amount after fees
    invoice_url VARCHAR(500),
    receipt_url VARCHAR(500),
    failure_reason VARCHAR(255),
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE SET NULL,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    INDEX idx_provider_transaction_id (provider_transaction_id)
);

-- Premium features usage tracking
CREATE TABLE premium_feature_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    feature_type VARCHAR(50) NOT NULL, -- super_like, boost, read_receipts, etc.
    usage_count INT DEFAULT 1,
    reset_date DATE NOT NULL, -- When the counter resets (monthly/daily)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_feature_date (user_id, feature_type, reset_date),
    INDEX idx_user_id (user_id),
    INDEX idx_feature_type (feature_type),
    INDEX idx_reset_date (reset_date)
);

-- Promo codes and discounts
CREATE TABLE promo_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255),
    discount_type ENUM('percentage', 'fixed_amount') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL,
    minimum_amount DECIMAL(10, 2),
    max_uses INT,
    used_count INT DEFAULT 0,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_code (code),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at)
);

-- Promo code usage tracking
CREATE TABLE promo_code_uses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    promo_code_id INT NOT NULL,
    user_id INT NOT NULL,
    transaction_id INT,
    discount_amount DECIMAL(10, 2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_promo (user_id, promo_code_id),
    INDEX idx_promo_code_id (promo_code_id),
    INDEX idx_user_id (user_id),
    INDEX idx_used_at (used_at)
);

-- Revenue analytics and reporting
CREATE TABLE revenue_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    total_revenue DECIMAL(12, 2) DEFAULT 0,
    subscription_revenue DECIMAL(12, 2) DEFAULT 0,
    one_time_revenue DECIMAL(12, 2) DEFAULT 0,
    refunded_amount DECIMAL(12, 2) DEFAULT 0,
    new_subscriptions INT DEFAULT 0,
    cancelled_subscriptions INT DEFAULT 0,
    active_subscriptions INT DEFAULT 0,
    churn_rate DECIMAL(5, 2) DEFAULT 0,
    average_revenue_per_user DECIMAL(10, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date),
    INDEX idx_date (date)
);