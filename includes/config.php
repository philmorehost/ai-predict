<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'sure_predictor');
define('DB_PASS', 'Statement247@');
define('DB_NAME', 'sure_predictor');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
