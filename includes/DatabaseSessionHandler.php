<?php
/**
 * Database Session Handler
 * Stores session data in the database for better persistence on shared hosting.
 */

class DatabaseSessionHandler implements SessionHandlerInterface {
    protected $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    /**
     * @return string
     */
    public function read($id) {
        try {
            $stmt = $this->conn->prepare("SELECT data FROM sessions WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    return $row['data'];
                }
            }
        } catch (Exception $e) {}
        return '';
    }

    /**
     * @return bool
     */
    public function write($id, $data) {
        try {
            $now = time();
            $stmt = $this->conn->prepare("INSERT INTO sessions (id, data, last_access) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data), last_access = VALUES(last_access)");
            if ($stmt) {
                $stmt->bind_param("ssi", $id, $data, $now);
                return $stmt->execute();
            }
        } catch (Exception $e) {}
        return false;
    }

    /**
     * @return bool
     */
    public function destroy($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM sessions WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("s", $id);
                return $stmt->execute();
            }
        } catch (Exception $e) {}
        return false;
    }

    /**
     * gc return type changed in PHP 8.0 from bool to int|false.
     * We omit the type hint to maintain maximum compatibility.
     */
    public function gc($maxlifetime) {
        try {
            $old = time() - $maxlifetime;
            $stmt = $this->conn->prepare("DELETE FROM sessions WHERE last_access < ?");
            if ($stmt) {
                $stmt->bind_param("i", $old);
                if ($stmt->execute()) {
                    return $stmt->affected_rows;
                }
            }
        } catch (Exception $e) {}
        return false;
    }
}
?>
