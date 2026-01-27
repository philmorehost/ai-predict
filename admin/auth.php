<?php
// We must include config and functions BEFORE session_helper to ensure DB connection is ready for DB-based sessions
require_once '../includes/config.php';
require_once '../includes/functions.php';
ensureDatabaseTablesExist($conn);
require_once '../includes/session_helper.php';

if (!isset($_SESSION['admin_id'])) {
    session_write_close();
    header('Location: index.php?err=session_lost');
    exit;
}
?>
