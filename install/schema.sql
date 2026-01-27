-- Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `site_name` VARCHAR(255) DEFAULT 'SurePredictor',
  `site_description` TEXT,
  `ai_provider` VARCHAR(20) DEFAULT 'gemini',
  `gemini_api_key` VARCHAR(255),
  `gemini_model_prediction` VARCHAR(100) DEFAULT 'gemini-1.5-pro',
  `gemini_model_suggestion` VARCHAR(100) DEFAULT 'gemini-1.5-flash',
  `deepseek_api_key` VARCHAR(255),
  `deepseek_model_prediction` VARCHAR(100) DEFAULT 'deepseek-chat',
  `deepseek_model_suggestion` VARCHAR(100) DEFAULT 'deepseek-chat',
  `deepseek_base_url` VARCHAR(255) DEFAULT 'https://api.deepseek.com/',
  `footer_text` TEXT,
  `contact_email` VARCHAR(255),
  `primary_color` VARCHAR(20) DEFAULT '#059669',
  `dark_mode` TINYINT(1) DEFAULT 0,
  `site_logo` VARCHAR(255),
  `site_icon` VARCHAR(255),
  `free_limit` INT DEFAULT 3,
  `prediction_charge` DECIMAL(10,2) DEFAULT 0.03,
  `whatsapp_number` VARCHAR(20),
  `whatsapp_text` TEXT,
  `paystack_public_key` VARCHAR(255),
  `paystack_secret_key` VARCHAR(255),
  `flutterwave_public_key` VARCHAR(255),
  `flutterwave_secret_key` VARCHAR(255),
  `primary_currency` VARCHAR(10) DEFAULT 'USD',
  `conversion_rate_ngn` DECIMAL(10,2) DEFAULT 1500.00,
  `conversion_rate_kes` DECIMAL(10,2) DEFAULT 130.00,
  `bank_details_ngn` TEXT,
  `bank_details_kes` TEXT,
  `ad_expiry_date` DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT IGNORE INTO `settings` (`id`, `site_name`, `site_description`) VALUES (1, 'SurePredictor', 'Global football insights powered by advanced AI.');

-- Admin Users Table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `last_login` DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Advertising Management Table
CREATE TABLE IF NOT EXISTS `ads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slot_name` VARCHAR(50) UNIQUE, -- 'header_top', 'mid_content', 'result_footer'
  `ad_code` TEXT,
  `is_active` BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default ad slots
INSERT IGNORE INTO `ads` (`slot_name`, `ad_code`, `is_active`) VALUES
('header_top', '', 0),
('mid_content', '', 0),
('result_footer', '', 0);

-- Prediction Cache Table
CREATE TABLE IF NOT EXISTS `prediction_cache` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `match_hash` VARCHAR(64) UNIQUE,
  `home_team` VARCHAR(100),
  `away_team` VARCHAR(100),
  `result_json` LONGTEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Visitors Table
CREATE TABLE IF NOT EXISTS `visitors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` VARCHAR(50) UNIQUE NOT NULL,
  `email` VARCHAR(255),
  `phone` VARCHAR(50),
  `credits` DECIMAL(10,2) DEFAULT 0.00,
  `fingerprint` VARCHAR(255),
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Credit Packages Table
CREATE TABLE IF NOT EXISTS `credit_packages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `price_usd` DECIMAL(10,2) NOT NULL,
  `credits` DECIMAL(10,2) NOT NULL,
  `description` TEXT,
  `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Notifications Table
CREATE TABLE IF NOT EXISTS `payment_notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `visitor_id` INT,
  `package_id` INT,
  `amount` DECIMAL(10,2),
  `currency` VARCHAR(10),
  `proof_file` VARCHAR(255),
  `status` ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- News Table
CREATE TABLE IF NOT EXISTS `news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` LONGTEXT,
  `image_url` VARCHAR(255),
  `source` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `news_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily Usage Table
CREATE TABLE IF NOT EXISTS `daily_usage` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `identifier` VARCHAR(255) NOT NULL, -- fingerprint or IP
  `usage_count` INT DEFAULT 0,
  `last_usage_date` DATE,
  UNIQUE KEY `daily_usage_idx` (`identifier`, `last_usage_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SEO Settings Table
CREATE TABLE IF NOT EXISTS `seo_settings` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `meta_title` VARCHAR(255),
  `meta_description` TEXT,
  `og_image` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `seo_settings` (`id`, `meta_title`, `meta_description`) VALUES (1, 'SurePredictor - AI Football Predictions', 'Professional football insights and match forecasts generated by advanced AI.');
