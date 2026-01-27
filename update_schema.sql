-- Create Visitors Table
CREATE TABLE IF NOT EXISTS `visitors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` VARCHAR(50) UNIQUE NOT NULL,
  `email` VARCHAR(255),
  `phone` VARCHAR(50),
  `credits` DECIMAL(10,2) DEFAULT 0.00,
  `fingerprint` VARCHAR(255),
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Credit Packages Table
CREATE TABLE IF NOT EXISTS `credit_packages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `price_usd` DECIMAL(10,2) NOT NULL,
  `credits` DECIMAL(10,2) NOT NULL,
  `description` TEXT,
  `status` TINYINT(1) DEFAULT 1
);

-- Create Payment Notifications Table
CREATE TABLE IF NOT EXISTS `payment_notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `visitor_id` INT,
  `package_id` INT,
  `amount` DECIMAL(10,2),
  `currency` VARCHAR(10),
  `proof_file` VARCHAR(255),
  `status` ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`visitor_id`) REFERENCES `visitors`(`id`),
  FOREIGN KEY (`package_id`) REFERENCES `credit_packages`(`id`)
);

-- Create News Table
CREATE TABLE IF NOT EXISTS `news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` LONGTEXT,
  `image_url` VARCHAR(255),
  `source` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Daily Usage Table
CREATE TABLE IF NOT EXISTS `daily_usage` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `identifier` VARCHAR(255) NOT NULL, -- fingerprint or IP
  `usage_count` INT DEFAULT 0,
  `last_usage_date` DATE,
  UNIQUE KEY `daily_usage_idx` (`identifier`, `last_usage_date`)
);

-- Update Settings for new features
-- Note: Using PHP for migration to avoid syntax issues on older MySQL versions
