<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/UsageTracker.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
UsageTracker::enforceRateLimit($conn, 'payment_api', 30); // 30 requests per IP per day for payment API

if ($action === 'create_order') {
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $package_id = (int)$_POST['package_id'];
    $fingerprint = UsageTracker::getVisitorIdentifier();
    $ip = $_SERVER['REMOTE_ADDR'];

    // Check if visitor exists or create
    $stmt = $conn->prepare("SELECT * FROM visitors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $visitor = $stmt->get_result()->fetch_assoc();

    if (!$visitor) {
        $user_id = 'SP-' . strtoupper(substr(md5(time() . $email), 0, 8));
        $stmt = $conn->prepare("INSERT INTO visitors (user_id, email, phone, fingerprint, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $user_id, $email, $phone, $fingerprint, $ip);
        $stmt->execute();
        $v_id = $conn->insert_id;

        // Welcome email
        $to = $email;
        $subject = "Welcome to SurePredictor!";
        $msg = "Thank you for joining us. Your unique Visitor ID is: $user_id\n\nYou can use this ID to track your analysis history and manage your credits.";
        $headers = "From: noreply@surepredictor.com";
        @mail($to, $subject, $msg, $headers);
    } else {
        $v_id = $visitor['id'];
        $user_id = $visitor['user_id'];
    }

    echo json_encode([
        'success' => true,
        'visitor_id' => $v_id,
        'user_id' => $user_id,
        'email' => $email,
        'phone' => $phone
    ]);
}

if ($action === 'bank_transfer') {
    $v_id = (int)$_POST['v_id'];
    $package_id = (int)$_POST['package_id'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];

    $proof_file = null;
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === 0) {
        $target_dir = "../assets/proofs/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);

        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $filename = "proof_" . time() . "_" . $v_id . "." . $ext;
            if (move_uploaded_file($_FILES["proof"]["tmp_name"], $target_dir . $filename)) {
                $proof_file = "assets/proofs/" . $filename;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.']);
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO payment_notifications (visitor_id, package_id, amount, currency, proof_file) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iidss", $v_id, $package_id, $amount, $currency, $proof_file);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Payment notification submitted! Admin will review soon.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Submission failed.']);
    }
}

if ($action === 'initiate_transaction') {
    $v_id = (int)$_POST['v_id'];
    $pkg_id = (int)$_POST['package_id'];
    $gateway = sanitize($_POST['gateway'] ?? '');
    $amount = (float)$_POST['amount'];
    $currency = sanitize($_POST['currency'] ?? 'USD');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    $ref = strtoupper($gateway[0]) . '_' . bin2hex(random_bytes(8));
    if ($gateway === 'payhub') $ref = 'PH_' . bin2hex(random_bytes(8));

    $stmt = $conn->prepare("INSERT INTO online_transactions (visitor_id, package_id, transaction_ref, amount, currency, gateway, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisdssss", $v_id, $pkg_id, $ref, $amount, $currency, $gateway, $email, $phone);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'ref' => $ref]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to initiate transaction.']);
    }
}

if ($action === 'verify_payment') {
    $ref = sanitize($_GET['ref'] ?? '');
    $v_id = (int)$_GET['v_id'];

    // Role-level security: Ensure only the authenticated user can verify their own payment
    if (isset($_SESSION['user_id'])) {
        $check_v = $conn->prepare("SELECT id FROM visitors WHERE user_id = ?");
        $check_v->bind_param("s", $_SESSION['user_id']);
        $check_v->execute();
        $real_v = $check_v->get_result()->fetch_assoc();
        if (!$real_v || $real_v['id'] !== $v_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
            exit;
        }
    }
    $pkg_id = (int)$_GET['pkg_id'];
    $provider = $_GET['provider'] ?? '';

    $settings = getSettings($conn);
    $verified = false;

    // Server-side verification with Secret Keys
    $merchant_ref = $ref;
    if ($provider === 'paystack') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . rawurlencode($ref));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $settings['paystack_secret_key']]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if ($response && $response['status'] && $response['data']['status'] === 'success') {
            $verified = true;
            $merchant_ref = $response['data']['reference'];
        }
    } elseif ($provider === 'flutterwave') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.flutterwave.com/v3/transactions/" . rawurlencode($ref) . "/verify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $settings['flutterwave_secret_key'], "Content-Type: application/json"]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if ($response && $response['status'] === 'success' && $response['data']['status'] === 'successful') {
            $verified = true;
            $merchant_ref = $response['data']['tx_ref'];
        }
    } elseif ($provider === 'payhub') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://payhub.datagifting.com.ng/api/transaction/verify/" . rawurlencode($ref));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $settings['payhub_secret_key']]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if ($response && $response['status'] === 'success' && $response['data']['status'] === 'success') {
            $verified = true;
            $merchant_ref = $response['data']['reference'];
        }
    }

    if (!$verified) {
        echo json_encode(['success' => false, 'message' => 'Payment verification failed.']);
        exit;
    }

    $pkg_stmt = $conn->prepare("SELECT credits FROM credit_packages WHERE id = ?");
    $pkg_stmt->bind_param("i", $pkg_id);
    $pkg_stmt->execute();
    $pkg = $pkg_stmt->get_result()->fetch_assoc();

    if ($pkg) {
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE visitors SET credits = credits + {$pkg['credits']} WHERE id = $v_id");

            // Get new balance
            $res = $conn->query("SELECT credits FROM visitors WHERE id = $v_id");
            $new_balance = $res->fetch_assoc()['credits'];

            $stmt_ot = $conn->prepare("UPDATE online_transactions SET status = 'success', is_disputed = 0, api_ref = ? WHERE transaction_ref = ?");
            $stmt_ot->bind_param("ss", $ref, $merchant_ref);
            $stmt_ot->execute();
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Payment successful! Credits added.', 'new_balance' => $new_balance]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to update transaction status.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Verification failed.']);
    }
}

if ($action === 'verify_paypal') {
    $orderID = $_GET['orderID'] ?? '';
    $v_id = (int)$_GET['v_id'];
    $pkg_id = (int)$_GET['pkg_id'];

    $settings = getSettings($conn);
    $verified = false;

    $api_url = $settings['paypal_mode'] === 'live' ? "https://api-m.paypal.com" : "https://api-m.sandbox.paypal.com";

    // Get Access Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$api_url/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_USERPWD, $settings['paypal_client_id'] . ":" . $settings['paypal_secret_key']);
    $token_res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($token_res['access_token'])) {
        $access_token = $token_res['access_token'];

        // Verify Order
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$api_url/v2/checkout/orders/$orderID");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token", "Content-Type: application/json"]);
        $order_res = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if ($order_res && $order_res['status'] === 'COMPLETED') {
            $verified = true;
        }
    }

    if (!$verified) {
        echo json_encode(['success' => false, 'message' => 'PayPal payment verification failed.']);
        exit;
    }

    $pkg_stmt = $conn->prepare("SELECT credits FROM credit_packages WHERE id = ?");
    $pkg_stmt->bind_param("i", $pkg_id);
    $pkg_stmt->execute();
    $pkg = $pkg_stmt->get_result()->fetch_assoc();

    if ($pkg) {
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE visitors SET credits = credits + {$pkg['credits']} WHERE id = $v_id");

            // Get new balance
            $res = $conn->query("SELECT credits FROM visitors WHERE id = $v_id");
            $new_balance = $res->fetch_assoc()['credits'];

            // Log transaction
            $stmt_ot = $conn->prepare("INSERT INTO online_transactions (visitor_id, package_id, transaction_ref, amount, currency, gateway, status, api_ref) VALUES (?, ?, ?, ?, 'USD', 'paypal', 'success', ?)");
            $amount = $pkg['price_usd'];
            $stmt_ot->bind_param("iisds", $v_id, $pkg_id, $orderID, $amount, $orderID);
            $stmt_ot->execute();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Payment successful! Credits added.', 'new_balance' => $new_balance]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to process payment.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Package not found.']);
    }
}

if ($action === 'fetch_payhub_account') {
    $v_id = (int)$_GET['v_id'];

    $stmt = $conn->prepare("SELECT email, phone, full_name, username, payhub_account_number, payhub_bank_name, payhub_account_name FROM visitors WHERE id = ?");
    $stmt->bind_param("i", $v_id);
    $stmt->execute();
    $visitor = $stmt->get_result()->fetch_assoc();

    if (!$visitor) {
        echo json_encode(['success' => false, 'message' => 'Visitor not found.']);
        exit;
    }

    // Return cached account if it exists
    if (!empty($visitor['payhub_account_number'])) {
        echo json_encode([
            'success' => true,
            'account_number' => $visitor['payhub_account_number'],
            'bank_name' => $visitor['payhub_bank_name'],
            'account_name' => $visitor['payhub_account_name']
        ]);
        exit;
    }

    $settings = getSettings($conn);
    $email = $visitor['email'];
    $phone = $visitor['phone'] ?: '08000000000';
    $name = $visitor['full_name'] ?: ($visitor['username'] ?: 'Customer');

    // Initialize transaction to trigger virtual account generation
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://payhub.datagifting.com.ng/api/transaction/initialize");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    $fields = [
        'email' => $email,
        'amount' => 10000, // Dummy amount for initialization
        'name' => $name,
        'phone' => $phone
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $settings['payhub_secret_key']]);
    $init_res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // After initialization, fetch the virtual account
    // Try to fetch specific account first if possible, otherwise list all
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://payhub.datagifting.com.ng/api/virtual-accounts");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $settings['payhub_secret_key']]);
    $accounts_res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if ($accounts_res && $accounts_res['status'] === 'success' && !empty($accounts_res['data'])) {
        // Find the account for this email
        $found_acc = null;
        foreach ($accounts_res['data'] as $acc) {
            if ($acc['email'] === $email) {
                $found_acc = $acc;
                break;
            }
        }

        if ($found_acc) {
            $acc_num = $found_acc['account_number'];
            $bank = $found_acc['bank_name'];
            $acc_name = $found_acc['account_name'];

            // Cache it
            $upd = $conn->prepare("UPDATE visitors SET payhub_account_number = ?, payhub_bank_name = ?, payhub_account_name = ? WHERE id = ?");
            $upd->bind_param("sssi", $acc_num, $bank, $acc_name, $v_id);
            $upd->execute();

            echo json_encode([
                'success' => true,
                'account_number' => $acc_num,
                'bank_name' => $bank,
                'account_name' => $acc_name
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Virtual account not found for this email.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch virtual accounts from PayHub.']);
    }
}
?>
