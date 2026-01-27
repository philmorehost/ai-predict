<?php
/**
 * Standardized Session Helper
 * Ensures consistent session configuration across the platform.
 * Now supports Database-backed sessions for maximum reliability.
 */

// 1. Set PHP ini settings BEFORE starting the session
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 86400); // 24 hours

// 2. Determine if we are on HTTPS
$isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// 3. Include DB connection and set handler if possible
if (session_status() === PHP_SESSION_NONE) {
    // Try to include config to get $conn
    $config_file = dirname(__FILE__) . '/config.php';
    if (file_exists($config_file)) {
        require_once $config_file;
        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            // Ensure sessions table exists before starting
            // This prevents errors if ensureDatabaseTablesExist hasn't run yet
            $conn->query("CREATE TABLE IF NOT EXISTS `sessions` (`id` VARCHAR(128) NOT NULL PRIMARY KEY, `data` MEDIUMTEXT NOT NULL, `last_access` INT(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            require_once dirname(__FILE__) . '/DatabaseSessionHandler.php';
            $handler = new DatabaseSessionHandler($conn);
            session_set_save_handler($handler, true);
        }
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', // Automatically uses current domain
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_name('SurePredictorSess');
    session_start();
}

// Session persistence test for diagnostics
if (!isset($_SESSION['persistence_test'])) {
    $_SESSION['persistence_test'] = time();
}
?>
