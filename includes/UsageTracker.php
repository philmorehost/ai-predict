<?php

class UsageTracker {
    private $conn;
    private $identifier;
    private $free_limit;

    public function __construct($conn, $identifier, $free_limit) {
        $this->conn = $conn;
        $this->identifier = $identifier;
        $this->free_limit = $free_limit;
    }

    public function checkLimit() {
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare("SELECT usage_count FROM daily_usage WHERE identifier = ? AND last_usage_date = ?");
        $stmt->bind_param("ss", $this->identifier, $today);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            return $row['usage_count'] < $this->free_limit;
        }

        // No entry for today, so limit not exceeded
        return true;
    }

    public function recordUsage() {
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare("INSERT INTO daily_usage (identifier, usage_count, last_usage_date) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE usage_count = usage_count + 1");
        $stmt->bind_param("ss", $this->identifier, $today);
        $stmt->execute();
    }

    public static function getVisitorIdentifier() {
        // Combined fingerprinting attempt
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $screen = $_COOKIE['v_screen'] ?? 'Unknown'; // Can be set via JS
        return md5($ip . $ua . $screen);
    }
}
?>
