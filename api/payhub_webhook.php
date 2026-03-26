<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// PayHub Webhook Handler
$raw_body = file_get_contents('php://input');
$headers = getallheaders();
$signature = $headers['x-payhub-signature'] ?? $headers['X-Payhub-Signature'] ?? '';

$data = json_decode($raw_body, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON');
}

// Log incoming webhook for debugging
// file_put_contents('payhub_webhook.log', $raw_body . PHP_EOL, FILE_APPEND);

$settings = getSettings($conn);
$secret_key = $settings['payhub_secret_key'];
$computed_signature = hash_hmac('sha512', $raw_body, $secret_key);

if ($signature !== $computed_signature) {
    http_response_code(401);
    exit('Invalid Signature');
}

if (isset($data['event']) && $data['event'] === 'charge.success') {
    $ref = $data['data']['reference'] ?? '';
    $amount_kobo = $data['data']['amount'] ?? 0;
    $amount_ngn = $amount_kobo / 100;
    $email = $data['data']['customer']['email'] ?? $data['data']['email'] ?? '';

    // 1. First, check if this matches an existing online_transaction
    $stmt = $conn->prepare("SELECT * FROM online_transactions WHERE transaction_ref = ? AND status = 'pending' LIMIT 1");
    $stmt->bind_param("s", $ref);
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
                $stmt_status->bind_param("si", $ref, $transaction['id']);
                $stmt_status->execute();

                $conn->commit();
                http_response_code(200);
                exit('Success');
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                exit('DB Error');
            }
        }
    } else {
        // 2. Automated Deposit (Virtual Account) - No pre-existing online_transaction
        // Look up user by email
        $settings = getSettings($conn); // Already fetched above
        $u_stmt = $conn->prepare("SELECT id FROM visitors WHERE email = ? LIMIT 1");
        $u_stmt->bind_param("s", $email);
        $u_stmt->execute();
        $user = $u_stmt->get_result()->fetch_assoc();

        if ($user) {
            $v_id = $user['id'];
            $rate = $settings['conversion_rate_ngn'] ?: 1500;

            // Convert NGN amount to credits
            $credits_to_add = $amount_ngn / $rate;

            $conn->begin_transaction();
            try {
                // Update credits
                $stmt_upd = $conn->prepare("UPDATE visitors SET credits = credits + ? WHERE id = ?");
                $stmt_upd->bind_param("di", $credits_to_add, $v_id);
                $stmt_upd->execute();

                // Log as a successful transaction for record keeping
                $gateway = 'payhub_virtual';
                $status = 'success';
                $stmt_log = $conn->prepare("INSERT INTO online_transactions (visitor_id, transaction_ref, amount, currency, gateway, status, api_ref, email) VALUES (?, ?, ?, 'NGN', ?, ?, ?, ?)");
                $stmt_log->bind_param("isdssss", $v_id, $ref, $amount_ngn, $gateway, $status, $ref, $email);
                $stmt_log->execute();

                $conn->commit();
                http_response_code(200);
                exit('Success (Automated Deposit)');
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                exit('DB Error (Automated)');
            }
        } else {
            http_response_code(404);
            exit('User not found');
        }
    }
} else {
    http_response_code(200);
    exit('Ignored event');
}
