<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$type = $_POST['type'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$reason = sanitize($_POST['reason'] ?? '');

if (!$id || !in_array($type, ['Online', 'Manual']) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$table = ($type === 'Online') ? 'online_transactions' : 'payment_notifications';

$stmt = $conn->prepare("UPDATE $table SET is_disputed = 1, dispute_reason = ? WHERE id = ?");
$stmt->bind_param("si", $reason, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Issue reported successfully. Admin will review it shortly.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to report issue. Please try again later.']);
}
?>
