<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// BeeWave Webhook Handler
$raw_body = file_get_contents('php://input');
$data = json_decode($raw_body, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON');
}

if (isset($data['status']) && $data['status'] === true && isset($data['data']['status']) && $data['data']['status'] === 'success') {
    // Try to find the reference in various fields
    $beewave_ref = $data['data']['transaction_ref'] ?? '';
    $merchant_ref = $data['data']['reference'] ?? $data['data']['tx_ref'] ?? $data['data']['merchant_ref'] ?? '';
    $tracking_ref = $data['data']['customer']['tracking_ref'] ?? $data['data']['tracking_ref'] ?? '';

    // Look up transaction by our reference first, then by tracking ref, then by gateway reference
    $stmt = $conn->prepare("SELECT * FROM online_transactions WHERE (transaction_ref = ? OR transaction_ref = ? OR transaction_ref = ?) AND status = 'pending' LIMIT 1");
    $stmt->bind_param("sss", $merchant_ref, $tracking_ref, $beewave_ref);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();

    if ($transaction) {
        $v_id = $transaction['visitor_id'];
        $pkg_id = $transaction['package_id'];

        // Get credits from package
        $pkg_stmt = $conn->prepare("SELECT credits FROM credit_packages WHERE id = ?");
        $pkg_stmt->bind_param("i", $pkg_id);
        $pkg_stmt->execute();
        $pkg = $pkg_stmt->get_result()->fetch_assoc();

        if ($pkg) {
            $conn->begin_transaction();
            try {
                // Update credits
                $stmt_upd = $conn->prepare("UPDATE visitors SET credits = credits + ? WHERE id = ?");
                $stmt_upd->bind_param("di", $pkg['credits'], $v_id);
                $stmt_upd->execute();

                // Update transaction status
                $stmt_status = $conn->prepare("UPDATE online_transactions SET status = 'success', is_disputed = 0, api_ref = ? WHERE id = ?");
                $stmt_status->bind_param("si", $beewave_ref, $transaction['id']);
                $stmt_status->execute();

                $conn->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Package not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Transaction not found or already processed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Transaction status not successful']);
}
