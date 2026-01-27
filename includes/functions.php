<?php

function getSettings($conn) {
    $result = $conn->query("SELECT * FROM settings WHERE id = 1");
    return $result->fetch_assoc();
}

function getSeoSettings($conn) {
    $result = $conn->query("SELECT * FROM seo_settings WHERE id = 1");
    return $result->fetch_assoc();
}

function getAdsByLocation($conn, $location) {
    $now = date('Y-m-d');
    $stmt = $conn->prepare("SELECT * FROM ads WHERE location = ? AND is_active = 1 AND (expiry_date IS NULL OR expiry_date >= ?)");
    $stmt->bind_param("ss", $location, $now);
    $stmt->execute();
    return $stmt->get_result();
}

function getAd($conn, $slot_name) {
    // Mapping old slot names to new locations for backward compatibility
    $mapping = [
        'header_top' => 'header_text_link',
        'mid_content' => 'body_text_link',
        'result_footer' => 'footer_link'
    ];

    $location = $mapping[$slot_name] ?? $slot_name;

    $ads = getAdsByLocation($conn, $location);
    if ($ad = $ads->fetch_assoc()) {
        if ($ad['position'] == 'image') {
            return '<a href="'.htmlspecialchars($ad['anchor_link']).'" target="_blank"><img src="'.htmlspecialchars($ad['image_url']).'" alt="Ad"></a>';
        } elseif (!empty($ad['anchor_link']) && !empty($ad['anchor_text'])) {
            return '<a href="'.htmlspecialchars($ad['anchor_link']).'" target="_blank" class="text-emerald-600 font-bold hover:underline">'.htmlspecialchars($ad['anchor_text']).'</a>';
        }
        return $ad['ad_code'];
    }
    return '';
}

function getCurrentSeasonRange() {
    $year = (int)date('Y');
    $month = (int)date('n');
    $seasonStartYear = $month >= 7 ? $year : $year - 1;
    return $seasonStartYear . '/' . ($seasonStartYear + 1);
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function ensureDatabaseTablesExist($conn) {
    // Check for missing columns in settings
    $columns = [
        'ai_provider' => "VARCHAR(20) DEFAULT 'gemini'",
        'deepseek_api_key' => "VARCHAR(255)",
        'deepseek_model_prediction' => "VARCHAR(100)",
        'deepseek_model_suggestion' => "VARCHAR(100)",
        'deepseek_base_url' => "VARCHAR(255) DEFAULT 'https://api.deepseek.com/'",
        'site_logo' => "VARCHAR(255)",
        'site_icon' => "VARCHAR(255)",
        'gemini_model_prediction' => "VARCHAR(100)",
        'gemini_model_suggestion' => "VARCHAR(100)",
        'free_limit' => "INT DEFAULT 3",
        'prediction_charge' => "DECIMAL(10,2) DEFAULT 0.03",
        'whatsapp_number' => "VARCHAR(20)",
        'whatsapp_text' => "TEXT",
        'paystack_public_key' => "VARCHAR(255)",
        'paystack_secret_key' => "VARCHAR(255)",
        'flutterwave_public_key' => "VARCHAR(255)",
        'flutterwave_secret_key' => "VARCHAR(255)",
        'primary_currency' => "VARCHAR(10) DEFAULT 'USD'",
        'conversion_rate_ngn' => "DECIMAL(10,2) DEFAULT 1500.00",
        'conversion_rate_kes' => "DECIMAL(10,2) DEFAULT 130.00",
        'bank_details_ngn' => "TEXT",
        'bank_details_kes' => "TEXT",
        'ad_expiry_date' => "DATE",
        'news_enabled' => "TINYINT(1) DEFAULT 1",
        'history_enabled' => "TINYINT(1) DEFAULT 1"
    ];

    // Ensure total_predictions in visitors
    $conn->query("ALTER TABLE visitors ADD COLUMN IF NOT EXISTS total_predictions INT DEFAULT 0");

    foreach ($columns as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM `settings` LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE `settings` ADD `$col` $def");
        }
    }

    // Auto-create necessary tables with explicit charset for MariaDB/MySQL compatibility
    $charset = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query("CREATE TABLE IF NOT EXISTS `visitors` (`id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` VARCHAR(50) UNIQUE NOT NULL, `email` VARCHAR(255), `phone` VARCHAR(50), `credits` DECIMAL(10,2) DEFAULT 0.00, `fingerprint` VARCHAR(255), `ip_address` VARCHAR(45), `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP) $charset");
    $conn->query("CREATE TABLE IF NOT EXISTS `credit_packages` (`id` INT AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL, `price_usd` DECIMAL(10,2) NOT NULL, `credits` DECIMAL(10,2) NOT NULL, `description` TEXT, `status` TINYINT(1) DEFAULT 1) $charset");
    $conn->query("CREATE TABLE IF NOT EXISTS `payment_notifications` (`id` INT AUTO_INCREMENT PRIMARY KEY, `visitor_id` INT, `package_id` INT, `amount` DECIMAL(10,2), `currency` VARCHAR(10), `proof_file` VARCHAR(255), `status` ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP) $charset");
    $conn->query("CREATE TABLE IF NOT EXISTS `news` (`id` INT AUTO_INCREMENT PRIMARY KEY, `title` VARCHAR(255) NOT NULL, `content` LONGTEXT, `image_url` VARCHAR(255), `source` VARCHAR(100), `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY `news_title` (`title`)) $charset");
    $conn->query("CREATE TABLE IF NOT EXISTS `daily_usage` (`id` INT AUTO_INCREMENT PRIMARY KEY, `identifier` VARCHAR(255) NOT NULL, `usage_count` INT DEFAULT 0, `last_usage_date` DATE, UNIQUE KEY `daily_usage_idx` (`identifier`, `last_usage_date`)) $charset");
    $conn->query("CREATE TABLE IF NOT EXISTS `sessions` (`id` VARCHAR(128) NOT NULL PRIMARY KEY, `data` MEDIUMTEXT NOT NULL, `last_access` INT(11) NOT NULL) $charset");

    // Update ads table
    $conn->query("CREATE TABLE IF NOT EXISTS `ads` (`id` INT AUTO_INCREMENT PRIMARY KEY, `slot_name` VARCHAR(50), `ad_code` TEXT, `is_active` BOOLEAN DEFAULT TRUE) $charset");

    // Remove unique constraint from slot_name if it exists to allow new location-based system
    $check_unique = $conn->query("SHOW INDEX FROM `ads` WHERE Column_name = 'slot_name' AND Non_unique = 0");
    if ($check_unique && $check_unique->num_rows > 0) {
        $index_name = $check_unique->fetch_assoc()['Key_name'];
        $conn->query("ALTER TABLE `ads` DROP INDEX `$index_name` ");
    }

    $ad_columns = [
        'position' => "VARCHAR(50)",
        'location' => "VARCHAR(50)",
        'anchor_link' => "VARCHAR(255)",
        'anchor_text' => "VARCHAR(255)",
        'price' => "DECIMAL(10,2)",
        'expiry_date' => "DATE",
        'client_contact' => "VARCHAR(255)",
        'image_url' => "VARCHAR(255)"
    ];

    foreach ($ad_columns as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM `ads` LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE `ads` ADD `$col` $def");
        }
    }
}

?>
